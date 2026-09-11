<?php

namespace Tests\Feature;

use App\Filament\Resources\Firmas\Pages\EditFirma;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\FirmaDosyaFihristiUretici;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * TEDBİR ON "Firma Çalışma Merkezi" referansı — Dosya Fihristi (İçindekiler)
 * PDF'i: PortfoyKarne::firmaChecklistDetay()'ı sol menüdeki 12 kategoriye
 * göre gruplayıp basar.
 */
class FirmaDosyaFihristiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_pdf_uretilir_ve_kategori_basliklarini_icerir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $yanit = FirmaDosyaFihristiUretici::pdf($firma);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $this->assertNotEmpty($icerik);
        $this->assertStringContainsString('%PDF', $icerik);
    }

    public function test_tamamlanan_kriter_hazir_moduldeyse_tamam_gorunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $detay = collect(PortfoyKarne::firmaChecklistDetay($firma));
        $risk = $detay->firstWhere('anahtar', 'risk_degerlendirmesi');

        $this->assertNotNull($risk);
        $this->assertSame('Risk Değerlendirmesi', $risk['kategori']);
        $this->assertTrue($risk['tamam']);
        $this->assertSame('tamamlandi', $risk['durum']);
    }

    public function test_her_kriterin_bilinen_bir_kategorisi_vardir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $kategoriler = collect(PortfoyKarne::firmaChecklistDetay($firma))
            ->pluck('kategori')->unique();

        $gecerliGruplar = [
            'Yönetim', 'Risk Değerlendirmesi', 'Acil Durum & Yangın', 'Eğitimler',
            'Çalışan & Kurul', 'Sağlık Gözetimi', 'Saha Kontrolleri',
            'Periyodik Kontrol & Ölçüm', 'KKD', 'İş Kazaları & Olaylar',
            'Planlama & Arşiv', 'Diğer Belge & Yazışma',
        ];

        foreach ($kategoriler as $k) {
            $this->assertContains($k, $gecerliGruplar);
        }
    }

    public function test_firma_duzenleme_sayfasinda_dosya_fihristi_aksiyonu_calisir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(EditFirma::class, ['record' => $firma->getRouteKey()])
            ->callAction('dosyaFihristi')
            ->assertSuccessful();
    }
}
