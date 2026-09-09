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
        GeminiRiskPuanTamamlayici::devreyiSifirla();
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

    public function test_eksik_puanlari_tamamla_sadece_eksik_eksenleri_doldurur(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake(function ($request) {
            $govde = json_decode($request->body(), true);
            $ozellikler = array_keys(data_get($govde, 'generationConfig.responseSchema.properties', []));
            $onlemIstegi = in_array('onlem', $ozellikler, true);

            $json = $onlemIstegi
                ? ['onlem' => 'Kaymaz zemin kaplaması ve uyarı levhası kullanılmalı.']
                : ['olasilik' => 2, 'siddet' => 3];

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($json)]]]]]]);
        });

        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $eksik = $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Puansız ve önlemsiz madde', 'durum' => 'acik']);
        $sadeceOnlemEksik = $rd->maddeler()->create(['sira' => 2, 'tehlike' => 'Puanlı, önlemsiz madde', 'durum' => 'acik', 'olasilik' => 4, 'siddet' => 4]);
        $tamOlan = $rd->maddeler()->create(['sira' => 3, 'tehlike' => 'Tam dolu madde', 'durum' => 'acik', 'olasilik' => 5, 'siddet' => 5, 'mevcut_onlem' => 'Zaten yazılmış önlem.']);

        Livewire::test(MaddelerRelationManager::class, [
            'ownerRecord' => $rd,
            'pageClass' => EditRiskDegerlendirmesi::class,
        ])->callTableAction('eksikPuanlariTamamla');

        $this->assertSame(2.0, $eksik->fresh()->olasilik);
        $this->assertSame(3.0, $eksik->fresh()->siddet);
        $this->assertSame('Kaymaz zemin kaplaması ve uyarı levhası kullanılmalı.', $eksik->fresh()->mevcut_onlem);

        // Puanı zaten dolu olan madde AI önerisiyle EZİLMEZ, yalnız eksik önlemi tamamlanır.
        $this->assertSame(4.0, $sadeceOnlemEksik->fresh()->olasilik);
        $this->assertSame(4.0, $sadeceOnlemEksik->fresh()->siddet);
        $this->assertSame('Kaymaz zemin kaplaması ve uyarı levhası kullanılmalı.', $sadeceOnlemEksik->fresh()->mevcut_onlem);

        // Tamamen dolu madde hiç dokunulmadan kalır — sorgu kapsamına bile girmez.
        $this->assertSame(5.0, $tamOlan->fresh()->olasilik);
        $this->assertSame('Zaten yazılmış önlem.', $tamOlan->fresh()->mevcut_onlem);

        // eksik: 1 puan + 1 önlem; sadeceOnlemEksik: 1 önlem; tamOlan: 0 = toplam 3.
        Http::assertSentCount(3);
    }

    public function test_art_arda_basarisizlikta_devre_kesilir_ve_istek_atilmaz(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'kota'], 429)]);

        // İlk 4 istek gider ve başarısız olur; 5.'de devre kesiktir, HTTP'ye hiç gidilmez.
        for ($i = 0; $i < 6; $i++) {
            GeminiRiskPuanTamamlayici::oner('Tehlike '.$i, null, null, null, 'matris_5x5');
        }

        $this->assertTrue(GeminiRiskPuanTamamlayici::devreKesikMi());
        Http::assertSentCount(4);
    }

    public function test_eksik_puanlari_tamamla_tek_calistirmada_40_madde_ile_sinirli(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);
        Http::fake(fn () => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode(['olasilik' => 3, 'siddet' => 3])]]]]],
        ]));

        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        for ($i = 1; $i <= 55; $i++) {
            $rd->maddeler()->create(['sira' => $i, 'tehlike' => 'Puansız madde '.$i, 'mevcut_onlem' => 'var', 'durum' => 'acik']);
        }

        Livewire::test(MaddelerRelationManager::class, [
            'ownerRecord' => $rd,
            'pageClass' => EditRiskDegerlendirmesi::class,
        ])->callTableAction('eksikPuanlariTamamla');

        // 40 madde puanlandı, 15'i hâlâ eksik
        $this->assertSame(40, $rd->maddeler()->whereNotNull('olasilik')->count());
        $this->assertSame(15, $rd->maddeler()->whereNull('olasilik')->count());
    }

    public function test_kutuphaneden_aktar_mevcut_onlem_boluyorsa_ai_onlem_de_ekler(): void
    {
        $this->seed(TehlikeKutuphanesiSeeder::class);
        config(['services.gemini.key' => 'test-anahtar']);

        // Kütüphanedeki tehlikenin mevcut_onlem'i boş bırakılır ki AI'nin
        // önlem önerisi de devreye girsin.
        $tehlike = Tehlike::first();
        $tehlike->update(['mevcut_onlem' => null]);

        Http::fake(function ($request) {
            $govde = json_decode($request->body(), true);
            $ozellikler = array_keys(data_get($govde, 'generationConfig.responseSchema.properties', []));
            $onlemIstegi = in_array('onlem', $ozellikler, true);

            $json = $onlemIstegi
                ? ['onlem' => 'AI önlem önerisi.']
                : ['olasilik' => 3, 'siddet' => 3];

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($json)]]]]]]);
        });

        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        Livewire::test(MaddelerRelationManager::class, [
            'ownerRecord' => $rd,
            'pageClass' => EditRiskDegerlendirmesi::class,
        ])->callTableAction('kutuphanedenAktar', data: ['tehlike_id' => $tehlike->id]);

        $madde = RiskMaddesi::where('risk_degerlendirmesi_id', $rd->id)->first();
        $this->assertSame('AI önlem önerisi.', $madde->mevcut_onlem);
    }
}
