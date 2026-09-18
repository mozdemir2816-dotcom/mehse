<?php

namespace Tests\Feature;

use App\Filament\Pages\ZiyaretProgrami as ZiyaretSayfasi;
use App\Models\Firma;
use App\Models\User;
use App\Models\ZiyaretProgrami;
use App\Support\GeminiZiyaretDanismani;
use App\Support\ZiyaretProgramiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class ZiyaretProgramiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_ve_yil_secilince_12_aylik_bos_program_olusur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(ZiyaretSayfasi::class)->set('firmaId', $firma->id);

        $program = $component->instance()->program();
        $this->assertCount(12, $program->ziyaretler);
        $this->assertSame('bos', $program->ziyaretler[0][0]['durum']);
    }

    public function test_ayni_firma_yil_icin_tekrar_cagrilinca_ayni_kayit_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $ilk = ZiyaretProgrami::firmaYilIcin($firma, 2026);
        $ikinci = ZiyaretProgrami::firmaYilIcin($firma, 2026);

        $this->assertSame($ilk->id, $ikinci->id);
    }

    public function test_ay_alani_guncellenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(ZiyaretSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ayGuncelle', 0, 0, 'amac', 'Genel Saha Gözetimi')
            ->call('ayGuncelle', 0, 0, 'tarih', '2026-01-15')
            ->call('ayGuncelle', 0, 0, 'sure_saat', '2.5');

        $program = $component->instance()->program();
        $this->assertSame('Genel Saha Gözetimi', $program->ziyaretler[0][0]['amac']);
        $this->assertSame('2026-01-15', $program->ziyaretler[0][0]['tarih']);
        $this->assertSame('2.5', $program->ziyaretler[0][0]['sure_saat']);
    }

    public function test_durum_bos_planlandi_tamamlandi_sirasiyla_degisir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(ZiyaretSayfasi::class)->set('firmaId', $firma->id);

        $component->call('durumDegistir', 0, 0);
        $this->assertSame('planlandi', $component->instance()->program()->ziyaretler[0][0]['durum']);

        $component->call('durumDegistir', 0, 0);
        $this->assertSame('tamamlandi', $component->instance()->program()->ziyaretler[0][0]['durum']);

        $component->call('durumDegistir', 0, 0);
        $this->assertSame('bos', $component->instance()->program()->ziyaretler[0][0]['durum']);
    }

    public function test_bir_aya_ikinci_ziyaret_eklenebilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(ZiyaretSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ziyaretEkle', 0)
            ->call('ayGuncelle', 0, 1, 'tarih', '2026-01-22')
            ->call('ayGuncelle', 0, 1, 'amac', 'İkinci Ziyaret');

        $girdiler = $component->instance()->program()->ziyaretler[0];
        $this->assertCount(2, $girdiler);
        $this->assertSame('2026-01-22', $girdiler[1]['tarih']);
        $this->assertSame('İkinci Ziyaret', $girdiler[1]['amac']);
    }

    public function test_ziyaret_satiri_silinebilir_ama_sonuncu_kalir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(ZiyaretSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ziyaretEkle', 0)
            ->call('ziyaretSil', 0, 0);

        $this->assertCount(1, $component->instance()->program()->ziyaretler[0]);

        $component->call('ziyaretSil', 0, 0);
        $this->assertCount(1, $component->instance()->program()->ziyaretler[0]);
    }

    public function test_gemini_ziyaret_onerisi_api_anahtari_yokken_pasif(): void
    {
        config(['services.gemini.key' => null]);

        $this->assertFalse(GeminiZiyaretDanismani::aktifMi());
        $this->assertNull(GeminiZiyaretDanismani::oner('Ocak', 'İnşaat', 'Tehlikeli'));
    }

    public function test_gemini_ziyaret_onerisi_gecerli_yanit_ay_alanina_yazilir(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Kazı alanı iksa kontrolü yapılacak.']]]]],
            ], 200),
        ]);

        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(ZiyaretSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('aiAmacOner', 0, 0);

        $this->assertSame('Kazı alanı iksa kontrolü yapılacak.', $component->instance()->program()->ziyaretler[0][0]['amac']);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $p = ZiyaretProgrami::firmaYilIcin($firma, 2026);

        $yanit = ZiyaretProgramiUretici::pdf($p);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_takvim_gunleri_sadece_secili_firmaya_ait_tarihleri_dondurur(): void
    {
        $a = Firma::factory()->for($this->uzman)->create();
        $b = Firma::factory()->for($this->uzman)->create();

        Livewire::test(ZiyaretSayfasi::class)
            ->set('firmaId', $a->id)
            ->call('ayGuncelle', 0, 0, 'tarih', '2026-01-15')
            ->call('ayGuncelle', 0, 0, 'amac', 'A Firma Ziyareti');

        Livewire::test(ZiyaretSayfasi::class)
            ->set('firmaId', $b->id)
            ->call('ayGuncelle', 1, 0, 'tarih', '2026-01-15');

        $component = Livewire::test(ZiyaretSayfasi::class)->set('firmaId', $a->id);
        $gunler = $component->instance()->takvimGunler();

        $this->assertArrayHasKey('2026-01-15', $gunler);
        $this->assertCount(1, $gunler['2026-01-15']);
        $this->assertSame('A Firma Ziyareti', $gunler['2026-01-15'][0]['amac']);
    }

    public function test_takvim_ay_degistir_gosterilen_ayi_ileri_geri_alir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(ZiyaretSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2026);

        $this->assertSame('2026-01', $component->instance()->takvimGosterilenAy());

        $component->call('takvimAyDegistir', 1);
        $this->assertSame('2026-02', $component->instance()->takvimGosterilenAy());

        $component->call('takvimAyDegistir', -1);
        $this->assertSame('2026-01', $component->instance()->takvimGosterilenAy());
    }

    public function test_tarih_girilince_takvim_o_aya_otomatik_kayar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(ZiyaretSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2026);

        $this->assertSame('2026-01', $component->instance()->takvimGosterilenAy());

        $component->call('ayGuncelle', 4, 0, 'tarih', '2026-05-20');

        $this->assertSame('2026-05', $component->instance()->takvimGosterilenAy());
        $this->assertSame('2026-05-20', $component->get('takvimSeciliTarih'));
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(ZiyaretSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
