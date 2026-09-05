<?php

namespace Tests\Feature;

use App\Models\Calisan;
use App\Models\EgitimKaydi;
use App\Models\EgitimTuru;
use App\Models\Firma;
use App\Models\User;
use App\Support\EgitimKayitExcelIceAktarici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EgitimKayitExcelIceAktariciTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    private Calisan $calisan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek A.Ş.']);
        $this->calisan = Calisan::create([
            'firma_id' => $this->firma->id,
            'ad_soyad' => 'Ahmet Yılmaz',
            'tc' => '12345678901',
            'aktif' => true,
        ]);
    }

    /** @param  array<int, array<int, mixed>>  $satirlar */
    private function xlsxOlustur(array $satirlar): string
    {
        $kitap = new Spreadsheet();
        $sayfa = $kitap->getActiveSheet();

        foreach ($satirlar as $i => $satir) {
            $sayfa->fromArray($satir, null, 'A'.($i + 1));
        }

        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_tc_ile_eslesir_ve_varsayilan_egitim_kaydi_olusturur(): void
    {
        $yol = $this->xlsxOlustur([
            ['Çalışan', 'Firma', 'T.C. Kimlik No', 'Temel İş Sağlığı ve Güvenliği Eğitimi'],
            ['Ahmet Yılmaz', 'Örnek A.Ş.', '12345678901', '01.03.2026'],
        ]);

        $sonuc = EgitimKayitExcelIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(1, $sonuc['basarili']);
        $this->assertEmpty($sonuc['hatalar']);

        $kayit = EgitimKaydi::where('calisan_id', $this->calisan->id)->where('tur', 'is_sagligi_guvenligi_egitimi')->firstOrFail();
        $this->assertSame('2026-03-01', $kayit->tarih->toDateString());

        unlink($yol);
    }

    public function test_henuz_eklenmemis_konu_sutunu_yoksayilir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Çalışan', 'Firma', 'T.C. Kimlik No', 'Temel İş Sağlığı ve Güvenliği Eğitimi', 'Yüksekte Çalışma'],
            ['Ahmet Yılmaz', 'Örnek A.Ş.', '12345678901', '', '01.01.2026'],
        ]);

        $sonuc = EgitimKayitExcelIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(1, $sonuc['basarili']);
        // "Yüksekte Çalışma" henüz "Konu Ekle" ile eklenmediği için sütun tanınmaz, atlanır.
        $this->assertSame(0, EgitimKaydi::where('tur', 'yuksekte_calisma')->count());

        unlink($yol);
    }

    public function test_eklenmis_ozel_konuda_tc_bossa_ad_soyad_ve_firma_ile_eslesir(): void
    {
        EgitimTuru::aktifListe($this->uzman->id);
        EgitimTuru::create([
            'user_id' => $this->uzman->id, 'anahtar' => 'ilkyardim_temel',
            'ad' => 'İlkyardım (Temel Bilgilendirme)', 'gecerlilik_ay' => 36, 'sira' => 1,
        ]);

        $yol = $this->xlsxOlustur([
            ['Çalışan', 'Firma', 'T.C. Kimlik No', 'İlkyardım (Temel Bilgilendirme)'],
            ['Ahmet Yılmaz', 'Örnek A.Ş.', '', '15.06.2026'],
        ]);

        $sonuc = EgitimKayitExcelIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(1, $sonuc['basarili']);
        $kayit = EgitimKaydi::where('calisan_id', $this->calisan->id)->where('tur', 'ilkyardim_temel')->firstOrFail();
        $this->assertSame('2026-06-15', $kayit->tarih->toDateString());

        unlink($yol);
    }

    public function test_eslesmeyen_calisan_hata_verir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Çalışan', 'Firma', 'T.C. Kimlik No', 'Temel İş Sağlığı ve Güvenliği Eğitimi'],
            ['Bilinmeyen Kişi', 'Başka Firma', '', '15.06.2026'],
        ]);

        $sonuc = EgitimKayitExcelIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(0, $sonuc['basarili']);
        $this->assertCount(1, $sonuc['hatalar']);
        $this->assertStringContainsString('Satır 2', $sonuc['hatalar'][0]);

        unlink($yol);
    }

    public function test_bos_hucreler_kayit_olusturmaz(): void
    {
        $yol = $this->xlsxOlustur([
            ['Çalışan', 'Firma', 'T.C. Kimlik No', 'Temel İş Sağlığı ve Güvenliği Eğitimi'],
            ['Ahmet Yılmaz', 'Örnek A.Ş.', '12345678901', '—'],
        ]);

        $sonuc = EgitimKayitExcelIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(1, $sonuc['basarili']);
        $this->assertSame(0, EgitimKaydi::count());

        unlink($yol);
    }

    public function test_tekrar_yuklemede_guncellenir(): void
    {
        $yol1 = $this->xlsxOlustur([
            ['Çalışan', 'Firma', 'T.C. Kimlik No', 'Temel İş Sağlığı ve Güvenliği Eğitimi'],
            ['Ahmet Yılmaz', 'Örnek A.Ş.', '12345678901', '01.01.2025'],
        ]);
        EgitimKayitExcelIceAktarici::iceAktar($yol1, $this->uzman->id);
        unlink($yol1);

        $yol2 = $this->xlsxOlustur([
            ['Çalışan', 'Firma', 'T.C. Kimlik No', 'Temel İş Sağlığı ve Güvenliği Eğitimi'],
            ['Ahmet Yılmaz', 'Örnek A.Ş.', '12345678901', '01.01.2026'],
        ]);
        EgitimKayitExcelIceAktarici::iceAktar($yol2, $this->uzman->id);
        unlink($yol2);

        $this->assertSame(1, EgitimKaydi::count());
        $this->assertSame('2026-01-01', EgitimKaydi::first()->tarih->toDateString());
    }

    public function test_calisan_sutunu_yoksa_hata_doner(): void
    {
        $yol = $this->xlsxOlustur([
            ['Firma', 'Temel İş Sağlığı ve Güvenliği Eğitimi'],
            ['Örnek A.Ş.', '01.01.2026'],
        ]);

        $sonuc = EgitimKayitExcelIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(0, $sonuc['basarili']);
        $this->assertNotEmpty($sonuc['hatalar']);

        unlink($yol);
    }

    public function test_sablon_indirilebilir(): void
    {
        $yanit = EgitimKayitExcelIceAktarici::sablonIndir($this->uzman->id);

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $this->assertStringStartsWith('PK', $icerik);
    }
}
