<?php

namespace Tests\Feature;

use App\Support\GeminiIstemci;
use App\Support\GeminiTalimatUretici;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Ana Gemini modeli yoğunken (503) yedek modele geçiş — 09.10.2026 Talimat/Saha "AI ile Üret" hatası. */
class GeminiIstemciTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test', 'services.gemini.model' => 'ana', 'services.gemini.yedek_modeller' => ['yedek1', 'yedek2']]);
    }

    public function test_ana_model_yogunsa_yedek_modelle_uretilir(): void
    {
        Http::fake([
            '*models/ana:*' => Http::response(['error' => ['code' => 503, 'message' => 'high demand']], 503),
            '*models/yedek1:*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '["Madde 1","Madde 2"]']]]]]]),
        ]);

        $this->assertSame(['Madde 1', 'Madde 2'], GeminiTalimatUretici::uret('Forklift', 'Makine', []));
        Http::assertSentCount(2);
    }

    public function test_kalici_hatada_yedek_denenmez(): void
    {
        Http::fake(['*' => Http::response(['error' => 'gecersiz'], 400)]);

        $this->assertSame(400, GeminiIstemci::post(['contents' => []], 10)->status());
        Http::assertSentCount(1);
    }

    public function test_tum_modeller_yogunsa_son_yanit_doner(): void
    {
        Http::fake(['*' => Http::response(['error' => 'yogun'], 503)]);

        $this->assertSame([], GeminiTalimatUretici::uret('Forklift', 'Makine', []));
        Http::assertSentCount(3);
    }
}
