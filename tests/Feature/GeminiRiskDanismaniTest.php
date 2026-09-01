<?php

namespace Tests\Feature;

use App\Support\GeminiRiskDanismani;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiRiskDanismaniTest extends TestCase
{
    public function test_api_anahtari_yokken_pasif_ve_istek_atilmaz(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $this->assertFalse(GeminiRiskDanismani::aktifMi());
        $this->assertSame([], GeminiRiskDanismani::oner('Ofis', [], [], []));

        Http::assertNothingSent();
    }

    public function test_gecerli_yanit_ayristirilir(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        $govde = json_encode([
            ['tehlike' => 'Ergonomik olmayan çalışma istasyonu', 'risk' => 'Kas-iskelet rahatsızlığı',
                'oneri' => 'Ergonomik değerlendirme yapılır.', 'mevzuat' => '6331 SK', 'olasilik' => 2, 'siddet' => 2],
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => $govde]]]]],
            ]),
        ]);

        $adaylar = GeminiRiskDanismani::oner('Ofis / Hizmet / Finans', ['Çağrı merkezi'], ['calisan_sayisi' => 'kucuk'], []);

        $this->assertCount(1, $adaylar);
        $this->assertSame('Ergonomik olmayan çalışma istasyonu', $adaylar[0]['tehlike']);
    }

    public function test_basarisiz_istekte_bos_dizi_doner(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'kota asildi'], 429),
        ]);

        $this->assertSame([], GeminiRiskDanismani::oner('Ofis', [], [], []));
    }

    public function test_bozuk_json_govdeyi_kirmadan_bos_dizi_doner(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'JSON degil']]]]],
            ]),
        ]);

        $this->assertSame([], GeminiRiskDanismani::oner('Ofis', [], [], []));
    }

    public function test_tehlikesiz_aday_elenir(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    ['risk' => 'tehlike alani bos'],
                    ['tehlike' => 'Gecerli tehlike', 'risk' => 'r'],
                ])]]]]],
            ]),
        ]);

        $adaylar = GeminiRiskDanismani::oner('Ofis', [], [], []);

        $this->assertCount(1, $adaylar);
        $this->assertSame('Gecerli tehlike', $adaylar[0]['tehlike']);
    }
}
