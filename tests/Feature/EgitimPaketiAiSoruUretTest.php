<?php

namespace Tests\Feature;

use App\Filament\Resources\EgitimPaketis\Pages\EditEgitimPaketi;
use App\Models\EgitimPaketi;
use App\Models\SoruBankasiSorusu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class EgitimPaketiAiSoruUretTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_gemini_anahtari_yokken_ai_ile_soru_uret_butonu_gorunmez(): void
    {
        config(['services.gemini.key' => null]);

        $paket = EgitimPaketi::create(['user_id' => $this->uzman->id, 'ad' => 'Test Paketi']);

        Livewire::test(EditEgitimPaketi::class, ['record' => $paket->getRouteKey()])
            ->assertActionHidden('aiSoruUret');
    }

    public function test_ai_ile_uretilen_sorular_pakete_eklenir(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    ['soru' => 'AI sorusu?', 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 1, 'aciklama' => 'çünkü öyle', 'kaynak' => '6331 sK m.14'],
                ])]]]]],
            ], 200),
        ]);

        $paket = EgitimPaketi::create(['user_id' => $this->uzman->id, 'ad' => 'İnşaat Uzaktan Eğitimi', 'sektor' => 'insaat']);
        $paket->dersler()->create(['baslik' => 'İş Güvenliği Giriş', 'video_url' => 'https://youtube.com/watch?v=abc', 'sira' => 1]);

        Livewire::test(EditEgitimPaketi::class, ['record' => $paket->getRouteKey()])
            ->callAction('aiSoruUret', data: ['adet' => 5, 'zorluk' => 'karisik']);

        $paket->refresh();
        $this->assertSame(1, $paket->sorular()->count());

        $soru = $paket->sorular()->first();
        $this->assertSame('AI sorusu?', $soru->soru);
        $this->assertSame(1, $soru->dogru_index);
        $this->assertStringContainsString('çünkü öyle', $soru->aciklama);
        $this->assertStringContainsString('6331 sK m.14', $soru->aciklama);
    }

    public function test_ai_servisi_bos_donerse_soru_eklenmez(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response(['candidates' => []], 200),
        ]);

        $paket = EgitimPaketi::create(['user_id' => $this->uzman->id, 'ad' => 'Test Paketi']);

        Livewire::test(EditEgitimPaketi::class, ['record' => $paket->getRouteKey()])
            ->callAction('aiSoruUret', data: ['adet' => 5, 'zorluk' => 'karisik']);

        $this->assertSame(0, $paket->sorular()->count());
    }

    public function test_soru_bankasindan_genel_onayli_sorular_pakete_eklenir(): void
    {
        SoruBankasiSorusu::create([
            'user_id' => null, 'sektor_anahtari' => null, 'konu' => 'genel', 'zorluk' => 'orta',
            'soru' => 'Bankadaki genel soru?', 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 0,
            'durum' => 'onaylandi', 'kaynak' => '6331 sK',
        ]);
        SoruBankasiSorusu::create([
            'user_id' => null, 'sektor_anahtari' => 'saglik', 'konu' => 'genel', 'zorluk' => 'orta',
            'soru' => 'Başka sektöre özel soru?', 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 0,
            'durum' => 'onaylandi',
        ]);
        SoruBankasiSorusu::create([
            'user_id' => null, 'sektor_anahtari' => null, 'konu' => 'genel', 'zorluk' => 'orta',
            'soru' => 'Henüz onaylanmamış soru?', 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 0,
            'durum' => 'taslak',
        ]);

        $paket = EgitimPaketi::create(['user_id' => $this->uzman->id, 'ad' => 'Test Paketi']);

        Livewire::test(EditEgitimPaketi::class, ['record' => $paket->getRouteKey()])
            ->callAction('bankadanEkle', data: ['adet' => 10]);

        $paket->refresh();
        $this->assertSame(1, $paket->sorular()->count());
        $this->assertSame('Bankadaki genel soru?', $paket->sorular()->first()->soru);
        $this->assertStringContainsString('6331 sK', $paket->sorular()->first()->aciklama);
    }

    public function test_bankada_uygun_soru_yoksa_bos_bildirim_verir(): void
    {
        $paket = EgitimPaketi::create(['user_id' => $this->uzman->id, 'ad' => 'Test Paketi']);

        Livewire::test(EditEgitimPaketi::class, ['record' => $paket->getRouteKey()])
            ->callAction('bankadanEkle', data: ['adet' => 10]);

        $this->assertSame(0, $paket->sorular()->count());
    }
}
