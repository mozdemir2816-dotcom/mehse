<?php

namespace Tests\Feature;

use App\Support\GeminiRiskPdfOkuyucu;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiRiskPdfOkuyucuTest extends TestCase
{
    private string $sahteYol;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sahteYol = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($this->sahteYol, '%PDF-1.4 sahte içerik');
    }

    protected function tearDown(): void
    {
        @unlink($this->sahteYol);
        parent::tearDown();
    }

    public function test_api_anahtari_yokken_pasif_ve_istek_atilmaz(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $this->assertFalse(GeminiRiskPdfOkuyucu::aktifMi());

        $sonuc = GeminiRiskPdfOkuyucu::oku($this->sahteYol);

        $this->assertSame(0, $sonuc['basarili']);
        $this->assertSame([], $sonuc['adaylar']);
        $this->assertNotEmpty($sonuc['hatalar']);
        Http::assertNothingSent();
    }

    public function test_gecerli_yanit_aday_dizisine_cevrilir(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    ['bolum' => 'ŞALT SAHASI', 'tehlike' => 'Yüksek gerilim işletme araçlarına dokunma', 'risk' => 'Elektrik çarpması', 'olasilik' => 10, 'frekans' => 2, 'siddet' => 40, 'son_olasilik' => 0.5, 'son_frekans' => 2, 'son_siddet' => 40],
                    ['bolum' => 'ŞALT SAHASI', 'tehlike' => 'Sahaya izinsiz giriş', 'risk' => 'Trafik kazası'],
                ])]]]]],
            ]),
        ]);

        $sonuc = GeminiRiskPdfOkuyucu::oku($this->sahteYol);

        $this->assertSame(2, $sonuc['basarili']);
        $this->assertCount(2, $sonuc['adaylar']);
        $this->assertSame([], $sonuc['hatalar']);

        $ilk = $sonuc['adaylar'][0];
        $this->assertSame('pdf', $ilk['kaynak']);
        $this->assertSame('ŞALT SAHASI', $ilk['bolum']);
        $this->assertSame('Yüksek gerilim işletme araçlarına dokunma', $ilk['tehlike']);
        $this->assertSame(10.0, $ilk['olasilik']);
        $this->assertSame(0.5, $ilk['son_olasilik']);

        // İkinci maddede puan verilmemiş — uydurulmamalı, null kalmalı.
        $this->assertNull($sonuc['adaylar'][1]['olasilik']);

        $govde = json_decode(Http::recorded()[0][0]->body(), true);
        $this->assertSame('application/pdf', data_get($govde, 'contents.0.parts.1.inline_data.mime_type'));
    }

    public function test_tehlike_alani_bos_olan_maddeler_atlanir(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    ['bolum' => 'X', 'tehlike' => ''],
                    ['bolum' => 'X', 'tehlike' => 'Gerçek tehlike'],
                ])]]]]],
            ]),
        ]);

        $sonuc = GeminiRiskPdfOkuyucu::oku($this->sahteYol);

        $this->assertSame(1, $sonuc['basarili']);
        $this->assertSame('Gerçek tehlike', $sonuc['adaylar'][0]['tehlike']);
    }

    public function test_basarisiz_istekte_hata_mesaji_doner(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'kota'], 429)]);

        $sonuc = GeminiRiskPdfOkuyucu::oku($this->sahteYol);

        $this->assertSame(0, $sonuc['basarili']);
        $this->assertNotEmpty($sonuc['hatalar']);
    }

    public function test_bozuk_json_govdeyi_kirmadan_hata_doner(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'JSON değil']]]]],
            ]),
        ]);

        $sonuc = GeminiRiskPdfOkuyucu::oku($this->sahteYol);

        $this->assertSame(0, $sonuc['basarili']);
        $this->assertNotEmpty($sonuc['hatalar']);
    }
}
