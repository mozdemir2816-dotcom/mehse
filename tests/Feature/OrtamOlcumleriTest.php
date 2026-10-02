<?php

namespace Tests\Feature;

use App\Filament\Pages\OrtamOlcumleri as OrtamOlcumleriSayfasi;
use App\Models\Firma;
use App\Models\OrtamOlcumu;
use App\Models\User;
use App\Support\OrtamOlcumuUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrtamOlcumleriTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    public function test_katalogdan_olcum_eklenir_ve_kaydedilir(): void
    {
        Livewire::test(OrtamOlcumleriSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('katalogdanEkle', 'Fiziksel Etkenler', 'Gürültü Maruziyeti (LEX,8h)')
            ->call('katalogdanEkle', 'Toz Maruziyeti', 'Solunabilir Toz')
            ->call('kaydet');

        $olcum = OrtamOlcumu::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(2, $olcum->olcumler);
        $this->assertSame('Gürültü Maruziyeti (LEX,8h)', $olcum->olcumler[0]['parametre']);
        $this->assertSame('dB(A)', $olcum->olcumler[0]['birim']);
        $this->assertSame('bekliyor', $olcum->olcumler[0]['sonuc']);
    }

    public function test_olcum_tarihinden_sonraki_tarih_periyoda_gore_hesaplanir(): void
    {
        $olcum = OrtamOlcumu::firmaIcin($this->firma);
        $olcum->update(['olcumler' => [
            ['parametre' => 'Gürültü', 'periyot_ay' => 24, 'olcum_tarihi' => '2026-03-10', 'sonuc' => 'uygun'],
            ['parametre' => 'Kuvars', 'periyot_ay' => 12, 'olcum_tarihi' => '2026-03-10', 'sonuc' => 'asim'],
        ]]);

        $olcum->refresh();
        $this->assertSame('2028-03-10', $olcum->olcumler[0]['sonraki_olcum_tarihi']);
        $this->assertSame('2027-03-10', $olcum->olcumler[1]['sonraki_olcum_tarihi']);
    }

    public function test_elle_verilen_sonraki_tarih_korunur(): void
    {
        $olcum = OrtamOlcumu::firmaIcin($this->firma);
        $olcum->update(['olcumler' => [
            ['parametre' => 'Aydınlatma', 'periyot_ay' => 24, 'olcum_tarihi' => '2026-01-01', 'sonraki_olcum_tarihi' => '2026-07-01', 'sonuc' => 'uygun'],
        ]]);

        $this->assertSame('2026-07-01', $olcum->refresh()->olcumler[0]['sonraki_olcum_tarihi']);
    }

    public function test_serbest_olcum_eklenir(): void
    {
        Livewire::test(OrtamOlcumleriSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('yeniParametre', 'Kaynak Dumanı - Kaynakhane')
            ->set('yeniPeriyot', 12)
            ->call('serbestOlcumEkle')
            ->call('kaydet');

        $olcum = OrtamOlcumu::where('firma_id', $this->firma->id)->sole();
        $this->assertSame('Kaynak Dumanı - Kaynakhane', $olcum->olcumler[0]['parametre']);
        $this->assertSame(12, $olcum->olcumler[0]['periyot_ay']);
    }

    public function test_baslatilmis_ve_yaklasan_ozeti(): void
    {
        $olcum = OrtamOlcumu::firmaIcin($this->firma);
        $this->assertFalse($olcum->baslatilmisMi());

        $olcum->update(['olcumler' => [
            ['parametre' => 'Gürültü', 'periyot_ay' => 1, 'olcum_tarihi' => now()->subMonths(2)->toDateString(), 'sonuc' => 'uygun'],
            ['parametre' => 'Toz', 'periyot_ay' => 24, 'olcum_tarihi' => now()->toDateString(), 'sonuc' => 'uygun'],
        ]]);
        $olcum->refresh();

        $this->assertTrue($olcum->baslatilmisMi());
        $this->assertCount(1, $olcum->yaklasanlar());   // ilki geçmiş, ikincisi 2 yıl sonra
        $this->assertSame(2, $olcum->ozet()['toplam']);
    }

    public function test_pdf_uretilir(): void
    {
        $olcum = OrtamOlcumu::firmaIcin($this->firma);
        $olcum->update(['olcumler' => [
            ['parametre' => 'Gürültü Maruziyeti', 'grup' => 'Fiziksel Etkenler', 'bolge' => 'Pres hattı', 'periyot_ay' => 24, 'olcum_tarihi' => '2026-02-01', 'laboratuvar' => 'X Hijyen Lab.', 'olculen_deger' => '88', 'sinir_deger' => '85', 'birim' => 'dB(A)', 'sonuc' => 'asim'],
        ]]);

        $yanit = OrtamOlcumuUretici::pdf($olcum);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(OrtamOlcumleriSayfasi::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    public function test_otomatik_sonuc_sayisal_karsilastirma(): void
    {
        $this->assertSame('asim', OrtamOlcumu::otomatikSonuc('88', '85'));
        $this->assertSame('sinir', OrtamOlcumu::otomatikSonuc('70', '85'));
        $this->assertSame('uygun', OrtamOlcumu::otomatikSonuc('3,2', '5'));
        $this->assertNull(OrtamOlcumu::otomatikSonuc('300', '—'));
        $this->assertNull(OrtamOlcumu::otomatikSonuc(null, '85'));
    }

    public function test_deger_girilince_sonuc_otomatik_guncellenir_ama_elle_secim_kaydedilir(): void
    {
        $sayfa = Livewire::test(OrtamOlcumleriSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('katalogdanEkle', 'Fiziksel Etkenler', 'Gürültü Maruziyeti (LEX,8h)')
            ->set('satirlar.0.olculen_deger', '91');

        $this->assertSame('asim', $sayfa->get('satirlar.0.sonuc'));

        // Sınırı "—" olan ölçümde elle seçilen sonuç korunur.
        $sayfa->call('katalogdanEkle', 'Fiziksel Etkenler', 'Aydınlatma Şiddeti')
            ->set('satirlar.1.olculen_deger', '250')
            ->set('satirlar.1.sonuc', 'uygun')
            ->call('kaydet');

        $olcumler = OrtamOlcumu::where('firma_id', $this->firma->id)->sole()->olcumler;
        $this->assertSame('asim', $olcumler[0]['sonuc']);
        $this->assertSame('uygun', $olcumler[1]['sonuc']);
    }

    public function test_termin_durumu(): void
    {
        $this->assertSame('olculmedi', OrtamOlcumu::terminDurumu(['olcum_tarihi' => null]));
        $this->assertSame('gecikmis', OrtamOlcumu::terminDurumu(['olcum_tarihi' => '2024-01-01', 'sonraki_olcum_tarihi' => now()->subDay()->toDateString()]));
        $this->assertSame('yaklasan', OrtamOlcumu::terminDurumu(['olcum_tarihi' => '2025-01-01', 'sonraki_olcum_tarihi' => now()->addDays(30)->toDateString()]));
        $this->assertSame('guncel', OrtamOlcumu::terminDurumu(['olcum_tarihi' => '2026-01-01', 'sonraki_olcum_tarihi' => now()->addYear()->toDateString()]));
    }

    public function test_yeni_olcum_eskisini_gecmise_alir_ve_excel_iki_sayfa_uretir(): void
    {
        $sayfa = Livewire::test(OrtamOlcumleriSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('katalogdanEkle', 'Fiziksel Etkenler', 'Gürültü Maruziyeti (LEX,8h)')
            ->set('satirlar.0.bolge', 'Pres hattı')
            ->set('satirlar.0.olcum_tarihi', '2024-03-01')
            ->set('satirlar.0.laboratuvar', 'ABC Lab')
            ->set('satirlar.0.olculen_deger', '82')
            ->call('kaydet')
            ->call('yeniOlcum', 0)
            ->set('satirlar.0.olcum_tarihi', '2026-03-01')
            ->set('satirlar.0.olculen_deger', '86')
            ->call('kaydet');

        $m = OrtamOlcumu::where('firma_id', $this->firma->id)->sole()->olcumler[0];
        $this->assertSame('Pres hattı', $m['bolge']);
        $this->assertSame('ABC Lab', $m['laboratuvar']);
        $this->assertSame('86', $m['olculen_deger']);
        $this->assertSame('asim', $m['sonuc']);
        $this->assertSame('2028-03-01', $m['sonraki_olcum_tarihi']);
        $this->assertCount(1, $m['gecmis']);
        $this->assertSame('82', $m['gecmis'][0]['olculen_deger']);
        $this->assertSame('sinir', $m['gecmis'][0]['sonuc']);

        $tmp = tempnam(sys_get_temp_dir(), 'oot').'.xlsx';
        ob_start();
        OrtamOlcumuUretici::excel(OrtamOlcumu::sole())->sendContent();
        file_put_contents($tmp, ob_get_clean());
        $kitap = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
        @unlink($tmp);

        $this->assertSame(['Ölçüm Defteri', 'Geçmiş Ölçümler'], $kitap->getSheetNames());
        $this->assertSame('Güncel', $kitap->getSheet(0)->getCell('M2')->getValue());
        $this->assertSame('01.03.2024', $kitap->getSheet(1)->getCell('C2')->getValue());

        ob_start();
        OrtamOlcumuUretici::pdf(OrtamOlcumu::sole())->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }
}
