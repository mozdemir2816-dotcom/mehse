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
        $this->assertSame('bos', $program->ziyaretler[0]['durum']);
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
            ->call('ayGuncelle', 0, 'amac', 'Genel Saha Gözetimi')
            ->call('ayGuncelle', 0, 'tarih', '2026-01-15')
            ->call('ayGuncelle', 0, 'sure_saat', '2.5');

        $program = $component->instance()->program();
        $this->assertSame('Genel Saha Gözetimi', $program->ziyaretler[0]['amac']);
        $this->assertSame('2026-01-15', $program->ziyaretler[0]['tarih']);
        $this->assertSame('2.5', $program->ziyaretler[0]['sure_saat']);
    }

    public function test_durum_bos_planlandi_tamamlandi_sirasiyla_degisir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(ZiyaretSayfasi::class)->set('firmaId', $firma->id);

        $component->call('durumDegistir', 0);
        $this->assertSame('planlandi', $component->instance()->program()->ziyaretler[0]['durum']);

        $component->call('durumDegistir', 0);
        $this->assertSame('tamamlandi', $component->instance()->program()->ziyaretler[0]['durum']);

        $component->call('durumDegistir', 0);
        $this->assertSame('bos', $component->instance()->program()->ziyaretler[0]['durum']);
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
            ->call('aiAmacOner', 0);

        $this->assertSame('Kazı alanı iksa kontrolü yapılacak.', $component->instance()->program()->ziyaretler[0]['amac']);
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

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(ZiyaretSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
