<?php

namespace Tests\Feature;

use App\Filament\Pages\MuayeneFormu as MuayeneSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\MuayeneFormu;
use App\Models\User;
use App\Support\MuayeneFormuUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class MuayeneFormuTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_secilince_kontrol_listeleri_konfigurasyondan_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(MuayeneSayfasi::class)->set('firmaId', $firma->id);

        $this->assertCount(count(config('isg.muayene.sistemik_muayene_basliklari')), $component->get('sistemikMuayene'));
        $this->assertCount(count(config('isg.muayene.tetkikler')), $component->get('tetkikler'));
        $this->assertSame('normal', $component->get('sistemikMuayene')[0]['sonuc']);
    }

    public function test_firma_calisanindan_hizli_secim_alanlari_doldurur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz', 'tc' => '12345678901', 'gorev' => 'Operatör']);

        $component = Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanHizliSecId', $c->id);

        $this->assertSame('Ahmet Yılmaz', $component->get('calisanAdSoyad'));
        $this->assertSame('12345678901', $component->get('calisanTc'));
        $this->assertSame('Operatör', $component->get('calisanGorevi'));
    }

    public function test_firma_secilince_hekim_adi_otomatik_dolar(): void
    {
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'Dr. Ayşe Kaya', 'tip' => 'isyeri_hekimi']);
        $firma = Firma::factory()->for($this->uzman)->create(['isyeri_hekimi_id' => $hekim->id]);

        $component = Livewire::test(MuayeneSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('Dr. Ayşe Kaya', $component->get('hekimAdi'));
    }

    public function test_tehlike_sinifina_gore_kontrol_tarihi_onerilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);

        $component = Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('muayeneTarihi', '2026-01-15')
            ->call('kontrolTarihiOner');

        $this->assertSame('2027-01-15', $component->get('onerilenKontrolTarihi'));
    }

    public function test_calisan_veya_sonuc_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('muayene_formlari', 0);
    }

    public function test_pdf_aksiyonu_kayit_olusturur_ve_kase_snapshotlanir(): void
    {
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'isg-profesyonel-kase/x.png', 'tip' => 'isyeri_hekimi']);
        $firma = Firma::factory()->for($this->uzman)->create(['isyeri_hekimi_id' => $hekim->id]);

        Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanAdSoyad', 'Ahmet Yılmaz')
            ->set('sonucKanaati', 'sartli_uygun')
            ->set('sartAciklamasi', 'Gece vardiyasında çalışamaz')
            ->set('sistemikMuayene.0.sonuc', 'anormal')
            ->set('tetkikler.0.yapildi', true)
            ->set('tetkikler.0.sonuc', 'normal')
            ->callAction('pdf');

        $m = MuayeneFormu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Ahmet Yılmaz', $m->calisan_ad_soyad);
        $this->assertSame('sartli_uygun', $m->sonuc_kanaati);
        $this->assertSame('Gece vardiyasında çalışamaz', $m->sart_aciklamasi);
        $this->assertSame('anormal', $m->sistemik_muayene[0]['sonuc']);
        $this->assertTrue($m->tetkikler[0]['yapildi']);
        $this->assertSame('isg-profesyonel-kase/x.png', $m->hekim_kase);
        $this->assertStringStartsWith('MF-'.now()->year.'-', $m->belge_no);
    }

    public function test_uygun_disinda_sonucta_sart_aciklamasi_kaydedilmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanAdSoyad', 'Ahmet Yılmaz')
            ->set('sonucKanaati', 'uygun')
            ->set('sartAciklamasi', 'bu asla kaydedilmemeli')
            ->callAction('pdf');

        $m = MuayeneFormu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertNull($m->sart_aciklamasi);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $m = MuayeneFormu::create([
            'firma_id' => $firma->id,
            'calisan_ad_soyad' => 'Test Kişi',
            'sonuc_kanaati' => 'uygun',
        ]);

        $yanit = MuayeneFormuUretici::pdf($m);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $m = MuayeneFormu::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'X', 'sonuc_kanaati' => 'uygun']);

        Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $m->id);

        $this->assertDatabaseMissing('muayene_formlari', ['id' => $m->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(MuayeneSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
