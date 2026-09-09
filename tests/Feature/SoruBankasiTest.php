<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimSorulari;
use App\Filament\Pages\SoruBankasi;
use App\Models\Firma;
use App\Models\SoruBankasiSorusu;
use App\Models\User;
use Database\Seeders\SoruBankasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SoruBankasiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_manuel_soru_taslak_olarak_eklenir(): void
    {
        Livewire::test(SoruBankasi::class)
            ->set('yeniSoru', 'Baret ne zaman takılır?')
            ->set('yeniSecenekler', ['Her zaman şantiyede', 'Hiçbir zaman', 'Yağmurda', 'Molada'])
            ->set('yeniDogruIndex', 0)
            ->set('yeniKonu', 'kkd')
            ->set('yeniKaynak', 'Yapı İşlerinde İSG Yönetmeliği')
            ->call('soruEkle');

        $s = SoruBankasiSorusu::where('user_id', $this->uzman->id)->firstOrFail();
        $this->assertSame('taslak', $s->durum);
        $this->assertSame('manuel', $s->uretim_kaynagi);
        $this->assertSame('kkd', $s->konu);
        $this->assertSame(0, $s->dogru_index);
    }

    public function test_eksik_sikli_soru_reddedilir(): void
    {
        Livewire::test(SoruBankasi::class)
            ->set('yeniSoru', 'Yarım soru')
            ->set('yeniSecenekler', ['A', 'B', '', ''])
            ->call('soruEkle');

        $this->assertDatabaseCount('soru_bankasi_sorulari', 0);
    }

    public function test_soru_onaylanir_ve_taslaga_alinir(): void
    {
        $s = SoruBankasiSorusu::create([
            'user_id' => $this->uzman->id, 'konu' => 'genel_isg', 'zorluk' => 'orta',
            'soru' => 'X?', 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 1, 'durum' => 'taslak',
        ]);

        $c = Livewire::test(SoruBankasi::class)->call('onayla', $s->id);
        $s->refresh();
        $this->assertSame('onaylandi', $s->durum);
        $this->assertSame($this->uzman->name, $s->onaylayan);
        $this->assertNotNull($s->onay_tarihi);

        $c->call('taslagaAl', $s->id);
        $this->assertSame('taslak', $s->fresh()->durum);
        $this->assertNull($s->fresh()->onay_tarihi);
    }

    public function test_toplu_onay_filtredeki_taslaklari_onaylar(): void
    {
        foreach (range(1, 3) as $i) {
            SoruBankasiSorusu::create([
                'user_id' => $this->uzman->id, 'sektor_anahtari' => 'insaat', 'konu' => 'kazi', 'zorluk' => 'orta',
                'soru' => "Kazı sorusu {$i}?", 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 0, 'durum' => 'taslak',
            ]);
        }

        Livewire::test(SoruBankasi::class)
            ->set('sektorFiltre', 'insaat')
            ->set('konuFiltre', 'kazi')
            ->set('durumFiltre', 'taslak')
            ->call('tumTaslaklariOnayla');

        $this->assertSame(3, SoruBankasiSorusu::where('durum', 'onaylandi')->count());
    }

    public function test_sistem_havuzu_sorusu_baska_uzmana_gorunur_ama_silinemez(): void
    {
        $sistem = SoruBankasiSorusu::create([
            'user_id' => null, 'konu' => 'genel_isg', 'zorluk' => 'kolay',
            'soru' => 'Sistem sorusu?', 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 0, 'durum' => 'onaylandi',
        ]);

        $liste = Livewire::test(SoruBankasi::class)->set('durumFiltre', 'onaylandi')->instance()->sorular;
        $this->assertTrue($liste->contains('id', $sistem->id));

        Livewire::test(SoruBankasi::class)->call('sil', $sistem->id);
        $this->assertDatabaseHas('soru_bankasi_sorulari', ['id' => $sistem->id]);
    }

    public function test_ai_uretimi_bankaya_taslak_yazar(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    ['soru' => 'AI sorusu?', 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 2, 'aciklama' => 'çünkü', 'kaynak' => '6331 sK'],
                ])]]]]],
            ], 200),
        ]);

        Livewire::test(SoruBankasi::class)->call('aiUret', 'insaat', 'yuksekte_calisma', 'orta', 5);

        $s = SoruBankasiSorusu::where('uretim_kaynagi', 'ai')->firstOrFail();
        $this->assertSame('taslak', $s->durum);
        $this->assertSame('insaat', $s->sektor_anahtari);
        $this->assertSame('yuksekte_calisma', $s->konu);
        $this->assertSame('çünkü', $s->aciklama);
        $this->assertSame('6331 sK', $s->kaynak);
    }

    public function test_egitim_sorulari_bankadan_sektore_uygun_soru_ceker(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        SoruBankasiSorusu::create([
            'user_id' => null, 'sektor_anahtari' => 'insaat', 'konu' => 'kazi', 'zorluk' => 'orta',
            'soru' => 'İnşaat kazı?', 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 0, 'durum' => 'onaylandi',
        ]);
        SoruBankasiSorusu::create([
            'user_id' => null, 'sektor_anahtari' => 'saglik', 'konu' => 'biyolojik', 'zorluk' => 'orta',
            'soru' => 'Sağlık biyolojik?', 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 0, 'durum' => 'onaylandi',
        ]);

        $component = Livewire::test(EgitimSorulari::class)
            ->set('firmaId', $firma->id)
            ->set('sektorAnahtari', 'insaat')
            ->call('bankadanEkle', null, 10);

        $metinler = collect($component->get('sorular'))->pluck('soru');
        $this->assertTrue($metinler->contains('İnşaat kazı?'));
        $this->assertFalse($metinler->contains('Sağlık biyolojik?')); // farklı sektör
    }

    public function test_seeder_calistiginda_onayli_sistem_sorulari_gelir(): void
    {
        $this->seed(SoruBankasiSeeder::class);

        $this->assertGreaterThanOrEqual(20, SoruBankasiSorusu::whereNull('user_id')->where('durum', 'onaylandi')->count());

        // idempotent
        $sayi = SoruBankasiSorusu::count();
        $this->seed(SoruBankasiSeeder::class);
        $this->assertSame($sayi, SoruBankasiSorusu::count());
    }
}
