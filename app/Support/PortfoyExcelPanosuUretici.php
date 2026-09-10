<?php

namespace App\Support;

use App\Models\EgitimAtamasi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Portföy Excel Panosu — İSG uzmanının tüm portföyünü tek çalışma kitabında
 * (çok sayfalı) dışa aktarır: Genel Bakış + Firmalar + Çalışanlar + Risk Özeti +
 * Kontrol Merkezi matrisi + Uzaktan Eğitim + Kimyasal + Periyodik Kontrol.
 * Kullanıcı Excel'de kendi notlarını/planlamasını elle ekleyebilir (tek yönlü çıktı).
 */
class PortfoyExcelPanosuUretici
{
    private const BASLIK_ARKA = 'E8E5F2';

    public static function indir(User $uzman): StreamedResponse
    {
        ExcelBellek::artir();

        $kitap = new Spreadsheet;
        $kitap->removeSheetByIndex(0);

        static::genelBakis($kitap, $uzman);
        static::firmalar($kitap, $uzman);
        static::calisanlar($kitap, $uzman);
        static::riskOzeti($kitap, $uzman);
        static::kontrolMatrisi($kitap, $uzman);
        static::uzaktanEgitim($kitap, $uzman);
        static::kimyasal($kitap, $uzman);
        static::periyodikKontrol($kitap, $uzman);

        $kitap->setActiveSheetIndex(0);

        $tmp = tempnam(sys_get_temp_dir(), 'pano').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'isg-portfoy-panosu-'.now()->format('Y-m-d').'.xlsx');
    }

    private static function sayfa(Spreadsheet $kitap, string $ad): Worksheet
    {
        return $kitap->createSheet()->setTitle(mb_substr($ad, 0, 31));
    }

    /** @param array<int, array<int, mixed>> $satirlar ilk satır başlık */
    private static function tablo(Worksheet $s, array $satirlar, int $baslangic = 1): void
    {
        if (! $satirlar) {
            return;
        }

        $s->fromArray($satirlar, null, 'A'.$baslangic);

        $sonSutun = Coordinate::stringFromColumnIndex(count($satirlar[0]));
        $s->getStyle("A{$baslangic}:{$sonSutun}{$baslangic}")->getFont()->setBold(true);
        $s->getStyle("A{$baslangic}:{$sonSutun}{$baslangic}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::BASLIK_ARKA);
        $s->getStyle("A{$baslangic}:{$sonSutun}{$baslangic}")->getAlignment()->setWrapText(true);
        $s->freezePane('A'.($baslangic + 1));

        foreach (range(1, count($satirlar[0])) as $i) {
            $s->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
    }

    private static function genelBakis(Spreadsheet $kitap, User $uzman): void
    {
        $s = static::sayfa($kitap, 'Genel Bakış');
        $ozet = PortfoyKarne::ozet($uzman->id);
        $kriterler = PortfoyKarne::kriterler($uzman->id);

        $s->setCellValue('A1', 'İSG PORTFÖY PANOSU — '.$uzman->name);
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $s->setCellValue('A2', 'Oluşturma: '.now()->format('d.m.Y H:i'));

        static::tablo($s, [
            ['Gösterge', 'Değer'],
            ['Aktif işyeri', $ozet['firma']],
            ['Toplam çalışan (bildirilen)', $ozet['calisan']],
            ['Risk değerlendirmesi olan işyeri', $ozet['risk_olan']],
            ['Risk değerlendirmesi eksik', $ozet['evrak_eksigi']],
            ['Tam uyumlu işyeri', $ozet['tam_uyumlu']],
            ['Genel uyum oranı (%)', $ozet['uyum_yuzde']],
        ], 4);

        $satir = 13;
        static::tablo($s, array_merge(
            [['Yasal Kriter', 'Karşılayan', 'Kapsam', 'Oran (%)', 'Modül Hazır']],
            array_map(fn ($k) => [$k['ad'], $k['tamam'], $k['toplam'], $k['yuzde'], $k['hazir'] ? 'Evet' : 'Hayır'], $kriterler),
        ), $satir);
    }

    private static function firmalar(Spreadsheet $kitap, User $uzman): void
    {
        $s = static::sayfa($kitap, 'Firmalar');

        $satirlar = [['Unvan', 'SGK Sicil', 'NACE', 'Tehlike Sınıfı', 'Çalışan', 'İGU', 'İşyeri Hekimi', 'Sözleşme Başl.', 'Aktif', 'Notunuz']];

        foreach (Firma::where('user_id', $uzman->id)->with(['igu', 'isyeriHekimi'])->orderBy('unvan')->get() as $f) {
            $satirlar[] = [
                $f->unvan, $f->sgk_sicil_no, $f->nace_kodu,
                config('isg.tehlike_siniflari.'.$f->tehlike_sinifi, $f->tehlike_sinifi),
                $f->calisan_sayisi,
                $f->igu?->ad_soyad, $f->isyeriHekimi?->ad_soyad,
                $f->sozlesme_baslangic?->format('d.m.Y'),
                $f->aktif ? 'Evet' : 'Hayır', '',
            ];
        }

        static::tablo($s, $satirlar);
    }

    private static function calisanlar(Spreadsheet $kitap, User $uzman): void
    {
        $s = static::sayfa($kitap, 'Çalışanlar');

        $satirlar = [['Ad Soyad', 'İşyeri', 'Görev', 'Departman', 'İşe Giriş', 'Ağır/Tehlikeli', 'Aktif', 'E-posta', 'Notunuz']];

        Firma::where('user_id', $uzman->id)->with('calisanlar')->get()->each(function (Firma $f) use (&$satirlar) {
            foreach ($f->calisanlar as $c) {
                $satirlar[] = [
                    $c->ad_soyad, $f->unvan, $c->gorev, $c->departman,
                    $c->ise_giris?->format('d.m.Y'),
                    $c->agir_tehlikeli_iste ? 'Evet' : '',
                    $c->aktif ? 'Evet' : 'Hayır', $c->eposta, '',
                ];
            }
        });

        static::tablo($s, $satirlar);
    }

    private static function riskOzeti(Spreadsheet $kitap, User $uzman): void
    {
        $s = static::sayfa($kitap, 'Risk Özeti');

        $satirlar = [['İşyeri', 'Belge No', 'Yöntem', 'Madde Sayısı', 'Rapor Tarihi', 'Geçerlilik', 'Durum']];

        foreach (RiskDegerlendirmesi::whereHas('firma', fn ($q) => $q->where('user_id', $uzman->id))
            ->withCount('maddeler')->with('firma:id,unvan')->get() as $rd) {
            $satirlar[] = [
                $rd->firma?->unvan,
                $rd->belge_no,
                config('isg.risk_yontemleri.'.$rd->yontem, $rd->yontem),
                $rd->maddeler_count,
                $rd->rapor_tarihi?->format('d.m.Y'),
                $rd->gecerlilik_tarihi?->format('d.m.Y'),
                $rd->durum,
            ];
        }

        static::tablo($s, $satirlar);
    }

    private static function kontrolMatrisi(Spreadsheet $kitap, User $uzman): void
    {
        $s = static::sayfa($kitap, 'Kontrol Merkezi');
        $kriterler = config('isg.kontrol_merkezi.kriterler', []);

        $baslik = array_merge(['İşyeri'], array_map(fn ($k) => $k['ad'], $kriterler), ['Oran (%)']);
        $satirlar = [$baslik];

        foreach (PortfoyKarne::firmaKriterMatrisi($uzman->id) as $sut) {
            $satir = [$sut['firma']->unvan];
            foreach ($kriterler as $k) {
                $satir[] = ($sut['hucreler'][$k['anahtar']] ?? false) ? '✓' : '';
            }
            $satir[] = $sut['oran'];
            $satirlar[] = $satir;
        }

        static::tablo($s, $satirlar);
    }

    private static function uzaktanEgitim(Spreadsheet $kitap, User $uzman): void
    {
        $s = static::sayfa($kitap, 'Uzaktan Eğitim');

        $satirlar = [['Çalışan', 'İşyeri', 'Eğitim', 'Atandı', 'Son Tarih', 'Durum', 'İzlenen Ders', 'Sınav %', 'Tamamlandı']];

        foreach (EgitimAtamasi::whereHas('calisan.firma', fn ($q) => $q->where('user_id', $uzman->id))
            ->with(['calisan.firma', 'paket'])->get() as $a) {
            $satirlar[] = [
                $a->calisan?->ad_soyad, $a->calisan?->firma?->unvan, $a->paket?->ad,
                $a->atandi_at?->format('d.m.Y'), $a->son_tarih?->format('d.m.Y'),
                $a->durumEtiketi(),
                $a->izlenenDersSayisi().'/'.$a->toplamDersSayisi(),
                $a->sonSinav()?->puan,
                $a->tamamlandi_at?->format('d.m.Y'),
            ];
        }

        static::tablo($s, $satirlar);
    }

    private static function kimyasal(Spreadsheet $kitap, User $uzman): void
    {
        $s = static::sayfa($kitap, 'Kimyasal');

        $satirlar = [['İşyeri', 'Ürün', 'CAS', 'Fiziksel Hal', 'GHS', 'SDS', 'SDS Tarihi', 'Gözden Geçirme', 'Durum']];

        Firma::where('user_id', $uzman->id)->with('kimyasalUrunler')->get()->each(function (Firma $f) use (&$satirlar) {
            foreach ($f->kimyasalUrunler as $u) {
                $satirlar[] = [
                    $f->unvan, $u->urun_adi, $u->cas_no,
                    config('isg.kimyasal.fiziksel_hal.'.$u->fiziksel_hal, ''),
                    implode(', ', array_map(fn ($e) => explode(' — ', $e)[0], $u->ghsEtiketleri())),
                    $u->sdsVarMi() ? 'Var' : 'YOK',
                    $u->sds_tarihi?->format('d.m.Y'),
                    $u->sonraki_gozden_gecirme?->format('d.m.Y'),
                    $u->gozdenGecirmeDurumu(),
                ];
            }
        });

        static::tablo($s, $satirlar);
    }

    private static function periyodikKontrol(Spreadsheet $kitap, User $uzman): void
    {
        $s = static::sayfa($kitap, 'Periyodik Kontrol');

        $satirlar = [['İşyeri', 'Ekipman', 'Kategori', 'Periyot (ay)', 'Son Muayene', 'Sonraki Vize', 'Vize Durumu', 'Muayene Yapan', 'Sonuç']];

        Firma::where('user_id', $uzman->id)->with('isEkipmanlari')->get()->each(function (Firma $f) use (&$satirlar) {
            foreach ($f->isEkipmanlari as $e) {
                $satirlar[] = [
                    $f->unvan, $e->ekipman_adi, $e->kategoriAdi(), $e->muayene_periyodu_ay,
                    $e->son_muayene_tarihi?->format('d.m.Y') ?? '',
                    $e->sonraki_vize_tarihi?->format('d.m.Y') ?? '',
                    $e->vizeDurumEtiketi(), $e->muayene_yapan ?? '',
                    $e->sonucEtiketi(),
                ];
            }
        });

        static::tablo($s, $satirlar);
    }
}
