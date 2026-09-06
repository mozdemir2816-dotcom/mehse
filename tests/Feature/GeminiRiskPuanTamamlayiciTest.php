<?php

namespace Tests\Feature;

use App\Filament\Resources\RiskDegerlendirmesis\Pages\EditRiskDegerlendirmesi;
use App\Filament\Resources\RiskDegerlendirmesis\RelationManagers\MaddelerRelationManager;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use App\Models\User;
use App\Support\GeminiRiskPuanTamamlayici;
use Database\Seeders\TehlikeKutuphanesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GeminiRiskPuanTamamlayiciTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_api_anahtari_yokken_pasif_ve_istek_atilmaz(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $this->assertFalse(GeminiRiskPuanTamamlayici::aktifMi());
        $this->assertNull(GeminiRiskPuanTamamlayici::oner('Kaygan zemin', 'Düşme', 'Depo', 'İstifleme', 'matris_5x5'));

        Http::assertNothingSent();
    }

    public function test_matris_5x5_yaniti_en_yakin_olcek_degerine_yuvarlanir(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode(['olasilik' => 3.7, 'siddet' => 4.2])]]]]],
            ]),
        ]);

        $oneri = GeminiRiskPuanTamamlayici::oner('Kaygan zemin', 'Düşme', 'Depo', 'İstifleme', 'matris_5x5');

        $this->assertSame(4.0, $oneri['olasilik']);
        $this->assertSame(4.0, $oneri['siddet']);
        $this->assertNull($oneri['frekans']);
    }

    public function test_fine_kinney_frekans_dahil_edilir_ve_izinli_degere_yuvarlanir(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode(['olasilik' => 0.4, 'siddet' => 8, 'frekans' => 4])]]]]],
            ]),
        ]);

        $oneri = GeminiRiskPuanTamamlayici::oner('Yüksekten düşme', 'Ağır yaralanma', 'Şantiye', 'İskele', 'fine_kinney');

        $this->assertSame(0.5, $oneri['olasilik']);
        $this->assertSame(7.0, $oneri['siddet']);
        $this->assertSame(3.0, $oneri['frekans']);
    }

    public function test_basarisiz_istekte_null_doner(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'kota asildi'], 429),
        ]);

        $this->assertNull(GeminiRiskPuanTamamlayici::oner('Kaygan zemin', 'Düşme', 'Depo', 'İstifleme', 'matris_5x5'));
    }

    public function test_bozuk_json_govdeyi_kirmadan_null_doner(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'JSON degil']]]]],
            ]),
        ]);

        $this->assertNull(GeminiRiskPuanTamamlayici::oner('Kaygan zemin', 'Düşme', 'Depo', 'İstifleme', 'matris_5x5'));
    }

    public function test_kutuphaneden_aktar_ai_aktifken_maddeyi_otomatik_puanlar(): void
    {
        $this->seed(TehlikeKutuphanesiSeeder::class);
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode(['olasilik' => 3, 'siddet' => 4])]]]]],
            ]),
        ]);

        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $tehlike = Tehlike::first();

        Livewire::test(MaddelerRelationManager::class, [
            'ownerRecord' => $rd,
            'pageClass' => EditRiskDegerlendirmesi::class,
        ])->callTableAction('kutuphanedenAktar', data: ['tehlike_id' => $tehlike->id]);

        $madde = RiskMaddesi::where('risk_degerlendirmesi_id', $rd->id)->first();
        $this->assertNotNull($madde);
        $this->assertSame(3.0, $madde->olasilik);
        $this->assertSame(4.0, $madde->siddet);
        $this->assertNotNull($madde->puan);
    }

    public function test_kutuphaneden_aktar_ai_kapaliyken_madde_puansiz_kalir(): void
    {
        $this->seed(TehlikeKutuphanesiSeeder::class);
        config(['services.gemini.key' => null]);
        Http::fake();

        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $tehlike = Tehlike::first();

        Livewire::test(MaddelerRelationManager::class, [
            'ownerRecord' => $rd,
            'pageClass' => EditRiskDegerlendirmesi::class,
        ])->callTableAction('kutuphanedenAktar', data: ['tehlike_id' => $tehlike->id]);

        $madde = RiskMaddesi::where('risk_degerlendirmesi_id', $rd->id)->first();
        $this->assertNotNull($madde);
        $this->assertNull($madde->olasilik);
        $this->assertNull($madde->siddet);

        Http::assertNothingSent();
    }

    public function test_eksik_puanlari_tamamla_sadece_puansiz_maddeleri_doldurur(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode(['olasilik' => 2, 'siddet' => 3])]]]]],
            ]),
        ]);

        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $eksik = $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Puansız madde', 'durum' => 'acik']);
        $dolu = $rd->maddeler()->create(['sira' => 2, 'tehlike' => 'Dolu madde', 'durum' => 'acik', 'olasilik' => 5, 'siddet' => 5]);

        Livewire::test(MaddelerRelationManager::class, [
            'ownerRecord' => $rd,
            'pageClass' => EditRiskDegerlendirmesi::class,
        ])->callTableAction('eksikPuanlariTamamla');

        $this->assertSame(2.0, $eksik->fresh()->olasilik);
        $this->assertSame(3.0, $eksik->fresh()->siddet);
        // dolu olan madde tekrar puanlanmasın diye tek istek atılmış olmalı.
        $this->assertSame(5.0, $dolu->fresh()->olasilik);
        $this->assertSame(5.0, $dolu->fresh()->siddet);
        Http::assertSentCount(1);
    }
}
