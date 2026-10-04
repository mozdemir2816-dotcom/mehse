<?php

namespace Tests\Feature;

use App\Filament\Resources\Firmas\Pages\ListFirmas;
use App\Models\Firma;
use App\Models\User;
use App\Support\KatipSozlesmeIceAktarici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class KatipSozlesmeIceAktariciTest extends TestCase
{
    use RefreshDatabase;

    /** İSG-KATİP "Dışa Aktar" dosyasının gerçek başlık satırı (46 sütun). */
    private const BASLIK = [
        'Sözleşme Süreç ID', 'Sözleşme Adı', 'Sözleşme ID', 'Otomatik Güncelleme Onay İzni Var Mı',
        'Görevlendirilen Kişi TC Kimlik No', 'Görevlendirilen Kişi Ad Soyad', 'Görevlendirilen Kişi Sertifika Tipi',
        'Görevlendirilen Kişi Sertifika No', 'Sözleşme Tipi', 'Çalışma Süresi', 'Çalışma Periyodu',
        'Hizmet Veren İşyeri ID ', 'Hizmet Veren İşyeri Unvanı', 'Hizmet Veren İşyeri SGK/DETSİS No',
        'Hizmet Veren İşyeri İli', 'Hizmet Veren İşyeri Yetki Belgesi Tipi', 'Hizmet Veren İşyeri Yetki Belgesi No',
        'Hizmet Alan İşyeri ID', 'Hizmet Alan İşyeri Unvanı', 'Hizmet Alan İşyeri SGK/DETSİS No',
        'Hizmet Alan İşyeri İli', 'Hizmet Alan İşyeri Çalışan Sayısı', 'Hizmet Alan İşyeri Tehlike Sınıfı',
        'Hizmet Alan İşyeri Nace Kodu', 'Sözleşme Başlangıç Tarihi', 'Sözleşme Bitiş Tarihi', 'Sözleşme Statü',
        'Süre Güncelleme Kaydı Var Mı', 'Sözleşme Onay Durumu', 'Sözleşme Onay Tarihi', 'Gezici Araç Plaka No',
        'İlgili Basit Tetkikler', 'İlgili Ölçüm Metotları', 'İlgili İş Ekipmanları', 'Hizmet Alan İşyeri Onay Statüsü',
        'Hizmet Alan İşyeri Onay Tarihi', 'Onaylayan Kişi Ad Soyad', 'Görevlendirilen Kişi Onay Statüsü',
        'Görevlendirilen Kişi Onay Tarihi', 'Sözleşme Tanımlanma Tarihi', 'Tanımlayan Kişi Ad Soyad',
        'Sözleşmeyi Sonlandıran Ad Soyad', 'Sözleşme Sonlandırılma Nedeni', 'Sözleşme Mevzuata Uygun Mu',
        'Eski Sözleşme ID', 'Lab. Raporu Var Mı',
    ];

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function satir(string $isyeriId, string $unvan, string $sgk, int $calisan, string $tehlike, string $nace,
        string $baslangic, string $bitis = '', string $statu = 'Sözleşme Devam Ediyor'): array
    {
        $s = array_fill_keys(self::BASLIK, '');
        $s['Sözleşme Adı'] = 'OSGB İLE ÖZEL İŞYERİ ARASINDA İŞ GÜVENLİĞİ UZMANI HİZMET ALIMI SÖZLEŞMESİ';
        $s['Hizmet Veren İşyeri Unvanı'] = 'ÖRNEK OSGB LİMİTED ŞİRKETİ';
        $s['Hizmet Veren İşyeri İli'] = 'İSTANBUL';
        $s['Hizmet Alan İşyeri ID'] = $isyeriId;
        $s['Hizmet Alan İşyeri Unvanı'] = $unvan;
        $s['Hizmet Alan İşyeri SGK/DETSİS No'] = $sgk;
        $s['Hizmet Alan İşyeri İli'] = 'BURSA';
        $s['Hizmet Alan İşyeri Çalışan Sayısı'] = (string) $calisan;
        $s['Hizmet Alan İşyeri Tehlike Sınıfı'] = $tehlike;
        $s['Hizmet Alan İşyeri Nace Kodu'] = $nace;
        $s['Sözleşme Başlangıç Tarihi'] = $baslangic;
        $s['Sözleşme Bitiş Tarihi'] = $bitis;
        $s['Sözleşme Statü'] = $statu;

        return array_values($s);
    }

    private function dosya(array ...$satirlar): string
    {
        $kitap = new Spreadsheet;
        $sayfa = $kitap->getActiveSheet();
        $sayfa->fromArray(self::BASLIK, null, 'A1');

        foreach ($satirlar as $i => $satir) {
            // KATİP numaraları metin olarak verir; SGK no gibi uzun sayılar bilimsel gösterime dönmesin.
            foreach ($satir as $j => $deger) {
                $sayfa->setCellValueExplicit([$j + 1, $i + 2], $deger, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $yol = tempnam(sys_get_temp_dir(), 'katip').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_yeni_isyerleri_hizmet_alan_sutunlarindan_eklenir(): void
    {
        $yol = $this->dosya(
            $this->satir('2283739', 'BERT PLASTİK KAUÇUK SANAYİ VE TİCARET LİMİTED ŞİRKETİ', '22212010112247420160872000', 2, 'Tehlikeli', '22.12.01', '23.09.2026'),
            $this->satir('13600032', 'FOM MAKİNA__OTOMOTİV SANAYİ VE TİCARET LİMİTED ŞİRKETİ', '22841010115350010161228000', 10, 'Çok Tehlikeli', '28.41.03', '22.09.2026'),
        );

        $this->assertTrue(KatipSozlesmeIceAktarici::katipDosyasiMi($yol));

        $sonuc = KatipSozlesmeIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertCount(2, $sonuc['eklenen']);
        $this->assertEmpty($sonuc['hatalar']);

        $bert = Firma::where('katip_no', '2283739')->firstOrFail();
        $this->assertSame('BERT PLASTİK KAUÇUK SANAYİ VE TİCARET LİMİTED ŞİRKETİ', $bert->unvan);
        $this->assertSame('22212010112247420160872000', $bert->sgk_sicil_no);
        $this->assertSame('BURSA', $bert->il);
        $this->assertSame(2, $bert->calisan_sayisi);
        $this->assertSame('tehlikeli', $bert->tehlike_sinifi);
        $this->assertSame('22.12.01', $bert->nace_kodu);
        $this->assertSame('2026-09-23', $bert->sozlesme_baslangic->toDateString());
        $this->assertSame($this->uzman->id, $bert->user_id);

        // OSGB (hizmet veren) firma olarak eklenmez; KATİP'in "__" ayıracı boşluğa çevrilir.
        $this->assertSame('FOM MAKİNA OTOMOTİV SANAYİ VE TİCARET LİMİTED ŞİRKETİ', Firma::where('katip_no', '13600032')->value('unvan'));
        $this->assertSame(0, Firma::where('unvan', 'like', '%OSGB%')->count());
    }

    public function test_kayitli_firma_mukerrer_acilmaz_guncellenir_elle_girilenler_korunur(): void
    {
        $firma = Firma::create([
            'user_id' => $this->uzman->id, 'unvan' => 'Bert Plastik', 'kisa_ad' => 'Bert',
            'sgk_sicil_no' => '2221 2010 1122 4742 0160 872000', 'il' => 'Gemlik',
            'calisan_sayisi' => 1, 'tehlike_sinifi' => 'az_tehlikeli',
        ]);

        $yol = $this->dosya($this->satir('2283739', 'BERT PLASTİK KAUÇUK SANAYİ VE TİCARET LİMİTED ŞİRKETİ', '22212010112247420160872000', 5, 'Tehlikeli', '22.12.01', '23.09.2026'));

        $sonuc = KatipSozlesmeIceAktarici::iceAktar($yol, $this->uzman->id);
        $this->assertSame(['Bert Plastik'], $sonuc['guncellenen']);
        $this->assertSame(1, Firma::count());

        $firma->refresh();
        $this->assertSame('Bert Plastik', $firma->unvan);
        $this->assertSame('Bert', $firma->kisa_ad);
        $this->assertSame('Gemlik', $firma->il);
        $this->assertSame(5, $firma->calisan_sayisi);
        $this->assertSame('tehlikeli', $firma->tehlike_sinifi);
        $this->assertSame('2283739', $firma->katip_no);

        // Aynı dosya tekrar yüklenince değişiklik yok.
        $tekrar = KatipSozlesmeIceAktarici::iceAktar($yol, $this->uzman->id);
        $this->assertSame([], $tekrar['eklenen']);
        $this->assertSame([], $tekrar['guncellenen']);
    }

    public function test_sona_ermis_sozlesme_yeni_firma_acmaz_mevcutta_bitis_isler(): void
    {
        $mevcut = Firma::create(['user_id' => $this->uzman->id, 'unvan' => 'ENGİN MEN', 'sgk_sicil_no' => '24334010113000520161413000', 'sozlesme_baslangic' => '2026-01-01']);

        $yol = $this->dosya(
            $this->satir('1', 'BİTMİŞ İŞYERİ LTD', '11111111111111111111111111', 3, 'Tehlikeli', '25.11.01', '01.01.2026', '01.05.2026', 'Sözleşme Sonlandırıldı'),
            $this->satir('8357458', 'ENGİN MEN', '24334010113000520161413000', 8, 'Çok Tehlikeli', '43.34.01', '25.03.2026', '30.09.2026', 'Sözleşme Sonlandırıldı'),
        );

        $sonuc = KatipSozlesmeIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(['BİTMİŞ İŞYERİ LTD (sözleşme sona ermiş)'], $sonuc['atlanan']);
        $this->assertSame(0, Firma::where('unvan', 'BİTMİŞ İŞYERİ LTD')->count());
        $this->assertSame('2026-09-30', $mevcut->refresh()->sozlesme_bitis->toDateString());
    }

    public function test_ayni_isyerinde_en_son_sozlesme_esas_alinir(): void
    {
        $yol = $this->dosya(
            $this->satir('8357458', 'ENGİN MEN', '24334010113000520161413000', 8, 'Çok Tehlikeli', '43.34.01', '25.03.2026'),
            $this->satir('8357458', 'ENGİN MEN', '24334010113000520161413000', 5, 'Çok Tehlikeli', '43.34.01', '02.03.2026', '24.03.2026', 'Sözleşme İptal Edildi'),
        );

        KatipSozlesmeIceAktarici::iceAktar($yol, $this->uzman->id);

        $firma = Firma::sole();
        $this->assertSame(8, $firma->calisan_sayisi);
        $this->assertSame('2026-03-25', $firma->sozlesme_baslangic->toDateString());
        $this->assertNull($firma->sozlesme_bitis);
    }

    public function test_eski_tarihli_dosya_guncel_firma_bilgisini_geri_almaz(): void
    {
        $firma = Firma::create(['user_id' => $this->uzman->id, 'unvan' => 'ENGİN MEN', 'sgk_sicil_no' => '24334010113000520161413000',
            'calisan_sayisi' => 8, 'sozlesme_baslangic' => '2026-03-25']);

        $yol = $this->dosya(
            $this->satir('8357458', 'ENGİN MEN', '24334010113000520161413000', 5, 'Çok Tehlikeli', '43.34.01', '02.03.2026'),
        );

        $sonuc = KatipSozlesmeIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame([], $sonuc['guncellenen']);
        $this->assertSame(8, $firma->refresh()->calisan_sayisi);
        $this->assertSame('2026-03-25', $firma->sozlesme_baslangic->toDateString());
    }

    public function test_ayni_unvanli_farkli_sgk_ayri_firma_olur(): void
    {
        $yol = $this->dosya(
            $this->satir('659361', 'SONEL DEKORASYON A.Ş.', '22030010111688310161233000', 4, 'Çok Tehlikeli', '20.30.90', '26.03.2026'),
            $this->satir('1530715', 'SONEL DEKORASYON A.Ş.', '24334010110223200161289000', 4, 'Çok Tehlikeli', '43.34.01', '26.03.2026'),
        );

        $sonuc = KatipSozlesmeIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertCount(2, $sonuc['eklenen']);
        $this->assertSame(2, Firma::where('unvan', 'SONEL DEKORASYON A.Ş.')->count());
    }

    public function test_firmalar_sayfasindan_katip_dosyasi_yuklenir(): void
    {
        $yol = $this->dosya($this->satir('2283739', 'BERT PLASTİK LTD', '22212010112247420160872000', 2, 'Tehlikeli', '22.12.01', '23.09.2026'));
        $yukleme = UploadedFile::fake()->createWithContent('ISG_HIZMET_SOZLESME_SURECI_DISA_AKTAR_1.xlsx', file_get_contents($yol));

        Livewire::test(ListFirmas::class)
            ->callAction('katipAktar', ['dosya' => $yukleme])
            ->assertHasNoActionErrors()
            ->assertNotified('1 firma eklendi, 0 firma güncellendi');

        $this->assertSame('2283739', Firma::sole()->katip_no);
    }

    public function test_genel_excel_yukle_katip_dosyasini_taniyip_dogru_aktarir(): void
    {
        $yol = $this->dosya($this->satir('2283739', 'BERT PLASTİK LTD', '22212010112247420160872000', 2, 'Tehlikeli', '22.12.01', '23.09.2026'));
        $yukleme = UploadedFile::fake()->createWithContent('katip.xlsx', file_get_contents($yol));

        Livewire::test(ListFirmas::class)
            ->callAction('excelYukle', ['dosya' => $yukleme])
            ->assertNotified('1 firma eklendi, 0 firma güncellendi');

        $firma = Firma::sole();
        $this->assertSame('BERT PLASTİK LTD', $firma->unvan);
        $this->assertSame('tehlikeli', $firma->tehlike_sinifi);
    }
}
