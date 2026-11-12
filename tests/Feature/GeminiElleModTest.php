<?php

namespace Tests\Feature;

use App\Livewire\GeminiEllePencere;
use App\Models\User;
use App\Support\GeminiElleMod;
use App\Support\GeminiTalimatUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/** 12.11.2026: yapay zekâ başarısız olunca "Gemini'de elle yap" — istem saklanır, yapıştırılan yanıt bir kez kullanılır. */
class GeminiElleModTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test-key', 'services.gemini.yedek_modeller' => []]);
        $this->actingAs(User::factory()->create());
    }

    public function test_api_basarisiz_olunca_istem_saklanir_yapistirilan_yanit_ayni_dugmede_kullanilir(): void
    {
        Http::fake(['*' => Http::response(['error' => 'kota'], 429)]);
        $this->assertSame([], GeminiTalimatUretici::uret('Kompresör Talimatı', 'Genel', []));

        // Saklanan istek: istem metni + yanıt biçimi.
        $token = GeminiElleMod::$sonToken;
        $istek = GeminiElleMod::istek($token);
        $this->assertStringContainsString('Kompresör Talimatı', $istek['istem']);
        $this->assertStringContainsString('YANIT BİÇİMİ', $istek['istem']);
        $this->assertSame(429, $istek['durum']);

        // Pencere: geçersiz yanıt reddedilir, çitli JSON ayıklanır.
        Livewire::test(GeminiEllePencere::class)
            ->call('ac', $token)->assertSet('json', true)
            ->set('yanit', 'Üzgünüm, yardımcı olamam.')->call('kaydet')->assertHasErrors('yanit')
            ->set('yanit', "İşte:\n```json\n[\"Madde A\", \"Madde B\"]\n```")->call('kaydet')->assertHasNoErrors()->assertSet('kaydedildi', true);

        // Aynı düğme: API'ye gitmeden yapıştırılan yanıt; bir kez kullanılır.
        Http::fake(['*' => Http::response(['error' => 'kota'], 429)]);
        $this->assertSame(['Madde A', 'Madde B'], GeminiTalimatUretici::uret('Kompresör Talimatı', 'Genel', []));
        Http::assertNothingSent();   // yapıştırılan yanıt kullanıldı, API.ye gidilmedi
        $this->assertSame([], GeminiTalimatUretici::uret('Kompresör Talimatı', 'Genel', []));
    }

    public function test_farkli_istem_yapistirilan_yaniti_kullanmaz(): void
    {
        Http::fake(['*' => Http::response(['error' => 'yogun'], 503)]);
        GeminiTalimatUretici::uret('Forklift Talimatı', 'Genel', []);
        $token = GeminiElleMod::$sonToken;
        $this->assertNull(GeminiElleMod::yanitKaydet($token, '["X"]'));

        $this->assertSame([], GeminiTalimatUretici::uret('Torna Talimatı', 'Genel', []));   // başka istem → API (başarısız)
        $this->assertSame(['X'], GeminiTalimatUretici::uret('Forklift Talimatı', 'Genel', []));
    }

    public function test_json_ayiklama(): void
    {
        $this->assertSame('{"a":1}', GeminiElleMod::jsonAyikla("Tabii!\n```json\n{\"a\":1}\n```\nBaşka?"));
        $this->assertSame('[1,2]', GeminiElleMod::jsonAyikla('Sonuç: [1,2]'));
        $this->assertNull(GeminiElleMod::jsonAyikla('json yok'));
        $this->assertNull(GeminiElleMod::jsonAyikla('[1, 2'));
    }
}
