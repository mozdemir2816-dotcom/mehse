<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimKatilim;
use App\Models\EgitimKaydi;
use App\Models\EgitimTuru;
use App\Models\Firma;
use App\Models\MuayeneFormu;
use App\Models\SaglikGozetimi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Firma çalışanlarının toplu dosyası — tek Excel, dört sayfa:
 *  1. Özet       — çalışan başına son eğitim / son muayene / sonraki muayene
 *  2. Personel   — Excel Rapor ile aynı (geri yüklenebilir) kimlik + kişisel bilgiler
 *  3. Eğitimler  — Eğitim Kayıtları + Eğitim Katılım formları + Uzaktan Eğitim atamaları
 *  4. Sağlık     — Muayene Formları (EK-2) + Sağlık Gözetimi tetkikleri, kan grubu
 *
 * Katılım formlarındaki katılımcılar çalışana TC (yoksa ad soyad) ile eşlenir.
 * Sağlık verisi KVKK md.6 özel nitelikli veridir — çıktı yalnız yetkili paylaşım içindir.
 */
class CalisanDosyasiUretici
{
    public static function excel(Firma $firma, string $gorunum = 'aktif', ?string $sube = null): StreamedResponse
    {
        ExcelBellek::artir();

        $yazici = new Xlsx(static::kitap($firma, $gorunum, $sube));

        return response()->streamDownload(
            fn () => $yazici->save('php://output'),
            'personel-dosyasi-'.Str::slug($firma->unvan).'-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    public static function kitap(Firma $firma, string $gorunum = 'aktif', ?string $sube = null): Spreadsheet
    {
        $calisanlar = CalisanListesiUretici::calisanlar($firma, $gorunum, $sube);
        $egitimler = static::egitimler($firma, $calisanlar);
        $saglik = static::saglik($firma, $calisanlar);

        // Personel sayfası Excel Rapor'un kendisi — sonra diğer sayfalar eklenir.
        $kitap = CalisanListesiUretici::excelKitabi($firma, $gorunum, $sube);
        $kitap->getActiveSheet()->setTitle('Personel');

        static::ozetSayfasi($kitap->createSheet(0), $firma, $calisanlar, $egitimler, $saglik);

        static::tabloSayfasi($kitap->createSheet(), 'Eğitimler',
            ['Ad Soyad', 'T.C. Kimlik No', 'Kaynak', 'Eğitim', 'Tarih', 'Süre / Bilgi', 'Geçerlilik', 'Durum'],
            $egitimler->map(fn (array $e) => [
                $e['calisan']->ad_soyad, $e['calisan']->tc, $e['kaynak'], $e['egitim'],
                $e['tarih']?->format('d.m.Y'), $e['bilgi'], $e['gecerlilik']?->format('d.m.Y'), $e['durum'],
            ])->all());

        static::tabloSayfasi($kitap->createSheet(), 'Sağlık',
            ['Ad Soyad', 'T.C. Kimlik No', 'Kan Grubu', 'Kaynak', 'Muayene / Tetkik', 'Tarih', 'Sonraki / Kontrol', 'Sonuç', 'Hekim / Rapor No', 'Not'],
            $saglik->map(fn (array $s) => [
                $s['calisan']->ad_soyad, $s['calisan']->tc, $s['calisan']->kan_grubu, $s['kaynak'], $s['tur'],
                $s['tarih']?->format('d.m.Y'), $s['sonraki']?->format('d.m.Y'), $s['sonuc'], $s['hekim'], $s['not'],
            ])->all());

        $kitap->setActiveSheetIndex(0);

        return $kitap;
    }

    /** @return Collection<int, array{calisan: Calisan, kaynak: string, egitim: string, tarih: ?Carbon, bilgi: ?string, gecerlilik: ?Carbon, durum: ?string}> */
    public static function egitimler(Firma $firma, Collection $calisanlar): Collection
    {
        $idler = $calisanlar->pluck('id');
        $satirlar = collect();

        $turler = EgitimTuru::where('user_id', $firma->user_id)->get()->keyBy('anahtar');
        $durumlar = ['gecerli' => 'Geçerli', 'yakinda' => 'Süresi yaklaşıyor', 'dolmus' => 'Süresi dolmuş'];

        foreach (EgitimKaydi::whereIn('calisan_id', $idler)->orderBy('tarih')->get() as $k) {
            $k->setRelation('calisan', $calisanlar->firstWhere('id', $k->calisan_id)->setRelation('firma', $firma));
            $tur = $turler->get($k->tur);
            $satirlar->push([
                'calisan' => $k->calisan, 'kaynak' => 'Eğitim Kaydı', 'egitim' => $k->turEtiketi($tur),
                'tarih' => $k->tarih, 'bilgi' => $k->notlar, 'gecerlilik' => $k->gecerlilikTarihi($tur),
                'durum' => $durumlar[$k->durum($tur)] ?? null,
            ]);
        }

        // Katılım formları: katılımcı JSON'unda calisan_id yok → TC, yoksa ad soyad ile eşle.
        $tcIle = $calisanlar->filter(fn ($c) => filled($c->tc))->keyBy('tc');
        $adIle = $calisanlar->keyBy(fn ($c) => static::anahtar(trim($c->ad_soyad)));

        foreach (EgitimKatilim::where('firma_id', $firma->id)->orderBy('belge_tarihi')->get() as $form) {
            foreach ($form->katilimcilar ?? [] as $k) {
                $calisan = (filled($k['tc'] ?? null) ? $tcIle->get(trim((string) $k['tc'])) : null)
                    ?? $adIle->get(static::anahtar(trim((string) ($k['ad_soyad'] ?? ''))));

                if (! $calisan) {
                    continue;
                }

                $satirlar->push([
                    'calisan' => $calisan, 'kaynak' => 'Eğitim Katılım Formu', 'egitim' => $form->basliklarEtiketi(),
                    'tarih' => $form->belge_tarihi,
                    'bilgi' => trim(($form->sure_gun ? $form->sure_gun.' gün' : '').($form->belge_no ? ' · Belge '.$form->belge_no : ''), ' ·'),
                    'gecerlilik' => null, 'durum' => null,
                ]);
            }
        }

        foreach (EgitimAtamasi::with('paket')->whereIn('calisan_id', $idler)->get() as $a) {
            $satirlar->push([
                'calisan' => $calisanlar->firstWhere('id', $a->calisan_id), 'kaynak' => 'Uzaktan Eğitim',
                'egitim' => $a->paket?->ad ?? 'Eğitim paketi',
                'tarih' => $a->tamamlandi_at ?? $a->atandi_at,
                'bilgi' => $a->tamamlandi_at ? 'Tamamlandı' : 'Atandı'.($a->son_tarih ? ' · son tarih '.$a->son_tarih->format('d.m.Y') : ''),
                'gecerlilik' => null, 'durum' => $a->durumEtiketi(),
            ]);
        }

        return static::sirala($satirlar);
    }

    /** @return Collection<int, array{calisan: Calisan, kaynak: string, tur: string, tarih: ?Carbon, sonraki: ?Carbon, sonuc: ?string, hekim: ?string, not: ?string}> */
    public static function saglik(Firma $firma, Collection $calisanlar): Collection
    {
        $satirlar = collect();

        foreach (MuayeneFormu::whereIn('calisan_id', $calisanlar->pluck('id'))->orderBy('muayene_tarihi')->get() as $m) {
            $satirlar->push([
                'calisan' => $calisanlar->firstWhere('id', $m->calisan_id), 'kaynak' => 'Muayene Formu (EK-2)',
                'tur' => $m->muayeneTuruEtiketi(), 'tarih' => $m->muayene_tarihi, 'sonraki' => $m->onerilen_kontrol_tarihi,
                'sonuc' => $m->sonuc_kanaati ? $m->sonucKanaatiEtiketi() : null,
                'hekim' => $m->hekim_adi, 'not' => $m->sart_aciklamasi,
            ]);
        }

        $gozetim = SaglikGozetimi::where('firma_id', $firma->id)->first();
        $sonuclar = config('isg.saglik_tetkik.sonuclar');

        foreach ($gozetim?->satirlar ?? [] as $s) {
            $calisan = $calisanlar->firstWhere('id', $s['calisan_id'] ?? null);

            if (! $calisan) {
                continue;
            }

            $satirlar->push([
                'calisan' => $calisan, 'kaynak' => 'Sağlık Gözetimi',
                'tur' => config('isg.saglik_tetkik.turleri.'.($s['tetkik_turu'] ?? '').'.ad', $s['tetkik_turu'] ?? '—'),
                'tarih' => filled($s['tarih'] ?? null) ? Carbon::parse($s['tarih']) : null,
                'sonraki' => filled($s['sonraki_tarih'] ?? null) ? Carbon::parse($s['sonraki_tarih']) : null,
                'sonuc' => $sonuclar[$s['sonuc'] ?? ''] ?? null,
                'hekim' => $s['rapor_no'] ?? null, 'not' => $s['not'] ?? null,
            ]);
        }

        return static::sirala($satirlar);
    }

    /** Türkçe büyük/küçük harf duyarsız eşleme anahtarı (İ/I → i/ı). */
    private static function anahtar(string $metin): string
    {
        return mb_strtolower(strtr($metin, ['İ' => 'i', 'I' => 'ı']));
    }

    private static function sirala(Collection $satirlar): Collection
    {
        return $satirlar
            ->sortBy(fn (array $s) => static::anahtar($s['calisan']->ad_soyad).'|'.($s['tarih']?->format('Ymd') ?? '0'))
            ->values();
    }

    private static function ozetSayfasi(Worksheet $s, Firma $firma, Collection $calisanlar, Collection $egitimler, Collection $saglik): void
    {
        $s->setTitle('Özet');
        $s->setCellValue('A1', $firma->unvan.' — Personel Dosyası');
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $s->setCellValue('A2', 'Rapor tarihi: '.now()->format('d.m.Y H:i').' · Sağlık bilgileri KVKK kapsamında özel nitelikli kişisel veridir.');
        $s->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
        $s->mergeCells('A1:J1');
        $s->mergeCells('A2:J2');

        $basliklar = ['Ad Soyad', 'T.C. Kimlik No', 'Görevi', 'Durum', 'Eğitim Sayısı', 'Son Eğitim', 'Süresi Dolmuş Eğitim', 'Son Muayene / Tetkik', 'Sonraki Muayene', 'Son Sağlık Sonucu'];
        $satirlar = $calisanlar->map(function (Calisan $c) use ($egitimler, $saglik) {
            $e = $egitimler->filter(fn ($x) => $x['calisan']->is($c));
            $h = $saglik->filter(fn ($x) => $x['calisan']->is($c) && $x['tarih']);
            $sonH = $h->sortBy(fn ($x) => $x['tarih']->format('Ymd'))->last();
            $sonraki = $h->pluck('sonraki')->filter()->filter(fn ($t) => $t->isFuture() || $t->isToday())->sort()->first()
                ?? $h->pluck('sonraki')->filter()->sort()->last();

            return [
                $c->ad_soyad, $c->tc, $c->gorev, $c->aktif ? 'Aktif' : 'Pasif',
                $e->count(), $e->pluck('tarih')->filter()->sort()->last()?->format('d.m.Y'),
                $e->where('durum', 'Süresi dolmuş')->count() ?: null,
                $sonH ? $sonH['tarih']->format('d.m.Y').' ('.$sonH['tur'].')' : null,
                $sonraki?->format('d.m.Y'), $sonH['sonuc'] ?? null,
            ];
        })->all();

        static::tablo($s, 4, $basliklar, $satirlar);
    }

    private static function tabloSayfasi(Worksheet $s, string $ad, array $basliklar, array $satirlar): void
    {
        $s->setTitle($ad);
        static::tablo($s, 1, $basliklar, $satirlar);
    }

    private static function tablo(Worksheet $s, int $ilkSatir, array $basliklar, array $satirlar): void
    {
        $s->fromArray($basliklar, null, "A{$ilkSatir}");
        $son = $s->getHighestColumn($ilkSatir);
        $s->getStyle("A{$ilkSatir}:{$son}{$ilkSatir}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $s->getStyle("A{$ilkSatir}:{$son}{$ilkSatir}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F4C5C');

        $r = $ilkSatir + 1;
        foreach ($satirlar as $satir) {
            $s->fromArray($satir, null, "A{$r}", true);
            // TC her tabloda B sütununda — baştaki sıfır kaybolmasın
            $s->setCellValueExplicit("B{$r}", (string) ($satir[1] ?? ''), DataType::TYPE_STRING);
            $r++;
        }

        if (! $satirlar) {
            $s->setCellValue("A{$r}", 'Kayıt bulunamadı.');
        }

        $s->freezePane('B'.($ilkSatir + 1));
        $s->setAutoFilter("A{$ilkSatir}:{$son}".max($ilkSatir, $r - 1));
        foreach (range('A', $son) as $harf) {
            $s->getColumnDimension($harf)->setAutoSize(true);
        }
    }
}
