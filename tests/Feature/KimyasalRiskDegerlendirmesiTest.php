<?php

namespace Tests\Feature;

use App\Filament\Pages\KimyasalRiskDegerlendirmesi as KimyasalRiskSayfasi;
use App\Models\Firma;
use App\Models\KimyasalRiskDegerlendirmesi;
use App\Models\KimyasalUrun;
use App\Models\User;
use App\Support\KimyasalRiskUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KimyasalRiskDegerlendirmesiTest extends TestCase
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

    /** @return array<string, array{0: string, 1: string, 2: string, 3: bool, 4: int}> */
    public static function bantlamaSaglar(): array
    {
        return [
            'düşük tehlike, az, düşük uçuculuk → 1' => ['A', 'az', 'dusuk', false, 1],
            'orta tehlike, orta miktar → 2-3' => ['B', 'orta', 'orta', false, 2],
            'yüksek tehlike + çok + yüksek → 4' => ['D', 'cok', 'yuksek', false, 4],
            'CMR her koşulda ≥ 3' => ['B', 'az', 'dusuk', true, 3],
            'E grubu + yüksek maruziyet → 4' => ['E', 'orta', 'yuksek', false, 4],
        ];
    }

    #[DataProvider('bantlamaSaglar')]
    public function test_kontrol_yaklasimi_bantlama_dogru(string $grup, string $miktar, string $ucuculuk, bool $cmr, int $beklenen): void
    {
        $this->assertSame($beklenen, KimyasalRiskDegerlendirmesi::kontrolYaklasimi($grup, $miktar, $ucuculuk, $cmr));
    }

    public function test_envanterden_urun_eklenince_ghs_den_grup_tahmini_yapilir(): void
    {
        $urun = KimyasalUrun::create([
            'firma_id' => $this->firma->id, 'urun_adi' => 'Aseton', 'aktif' => true,
            'ghs' => ['ghs02', 'ghs07'], 'fiziksel_hal' => 'sivi',
        ]);

        Livewire::test(KimyasalRiskSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('yeniUrunId', $urun->id)
            ->call('envanterdenEkle')
            ->call('kaydet');

        $kayit = KimyasalRiskDegerlendirmesi::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(1, $kayit->satirlar);
        $this->assertSame('Aseton', $kayit->satirlar[0]['kimyasal_adi']);
        $this->assertSame('B', $kayit->satirlar[0]['tehlike_grubu']);   // ghs07 → B
    }

    public function test_saving_ile_kontrol_yaklasimi_yeniden_hesaplanir(): void
    {
        $kayit = KimyasalRiskDegerlendirmesi::firmaIcin($this->firma);
        $kayit->update(['satirlar' => [
            ['kimyasal_adi' => 'Tiner', 'tehlike_grubu' => 'C', 'miktar' => 'cok', 'ucuculuk' => 'yuksek', 'kontrol_yaklasimi' => 1],
        ]]);

        $this->assertSame(4, $kayit->refresh()->satirlar[0]['kontrol_yaklasimi']);
    }

    public function test_bos_kimyasal_adi_saving_ile_temizlenir(): void
    {
        $kayit = KimyasalRiskDegerlendirmesi::firmaIcin($this->firma);
        $kayit->update(['satirlar' => [
            ['kimyasal_adi' => 'Geçerli', 'tehlike_grubu' => 'A'],
            ['kimyasal_adi' => '   ', 'tehlike_grubu' => 'B'],
        ]]);

        $this->assertCount(1, $kayit->refresh()->satirlar);
    }

    public function test_pdf_uretilir(): void
    {
        $kayit = KimyasalRiskDegerlendirmesi::firmaIcin($this->firma);
        $kayit->update(['satirlar' => [
            ['kimyasal_adi' => 'Sülfürik Asit', 'kullanim_alani' => 'Akü dolum', 'tehlike_grubu' => 'C', 'miktar' => 'orta', 'ucuculuk' => 'orta', 'maruziyet_yollari' => ['deri', 'goz'], 'alinan_onlemler' => 'Yüz siperi, göz duşu, LEV'],
        ]]);

        $yanit = KimyasalRiskUretici::pdf($kayit);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(KimyasalRiskSayfasi::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
