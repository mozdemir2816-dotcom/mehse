<?php

namespace Tests\Feature;

use App\Filament\Pages\RiskSihirbazi;
use App\Models\Firma;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/** Risk Sihirbazı: kütüphanede olmayan ekipman için yapay zekâ ile madde üretimi + genel kütüphaneye kayıt. */
class AiEkipmanRiskTest extends TestCase
{
    use RefreshDatabase;

    private function geminiYaniti(): void
    {
        $maddeler = [
            ['bolum' => 'Şerit testere', 'faaliyet' => 'Kesim', 'tehlike' => 'Açık şerit bıçağa temas', 'kok_neden' => 'Bıçak koruyucusu kesim boyuna ayarlanmıyor',
                'risk' => 'El kesilmesi', 'mevcut_onlem' => 'Koruyucu en üst konumda', 'oneri' => 'Ayarlanabilir koruyucu kesim parçasına indirilmeli',
                'mevzuat' => 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği', 'fk_olasilik' => 5, 'fk_frekans' => 6, 'fk_siddet' => 30,
                'x5_olasilik' => 4, 'x5_siddet' => 4],
            ['bolum' => 'Şerit testere', 'faaliyet' => 'Bakım', 'tehlike' => 'Bıçak değişiminde enerji kesilmemesi', 'kok_neden' => 'LOTO yok',
                'risk' => 'Kesik, ezilme', 'mevcut_onlem' => 'Belirlenmedi', 'oneri' => 'LOTO talimatı', 'mevzuat' => 'TS EN ISO 14118',
                'fk_olasilik' => 3, 'fk_frekans' => 2, 'fk_siddet' => 15, 'x5_olasilik' => 3, 'x5_siddet' => 4],
            ['tehlike' => '', 'oneri' => 'eksik madde atlanmalı'],
        ];

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode($maddeler, JSON_UNESCAPED_UNICODE)]]]]],
        ])]);
    }

    private function sihirbaz(User $user, string $yontem = 'fine_kinney')
    {
        config(['services.gemini.key' => 'test-anahtar']);
        $this->actingAs($user);
        $firma = Firma::factory()->for($user)->create(['tehlike_sinifi' => 'tehlikeli']);

        return Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)->set('yontem', $yontem)->call('ileri')
            ->call('yontemSec', 'hizli')->call('ileri')
            ->assertSee('Kütüphanede Olmayan Ekipman / Proses Ekle (AI)');
    }

    public function test_sahip_uretir_secer_ekler_ve_kutuphaneye_tekrarsiz_kaydeder(): void
    {
        $this->geminiYaniti();
        $s = $this->sihirbaz(User::factory()->create(['rol' => 'sahip']))
            ->assertSee('Genel Kütüphaneye de kaydet')
            ->set('aiEkipmanAdi', 'Şerit testere')
            ->call('aiEkipmanUret')
            ->assertSet('aiEkipmanSecim', [0, 1])
            ->assertSee('Açık şerit bıçağa temas')
            ->assertSee('O 6 × F 6 × Ş 40 = 1440'); // 5/30 en yakın ölçek değerine yuvarlandı

        $aday = $s->get('aiEkipmanAdaylar')[0];
        $this->assertSame('Kök neden: Bıçak koruyucusu kesim boyuna ayarlanmıyor → El kesilmesi', $aday['risk']);

        $s->call('aiEkipmanAdayToggle', 1)->call('aiEkipmanEkle')
            ->assertNotified('Maddeler risk tablosuna eklendi ve Genel Kütüphaneye kaydedildi');

        $kategori = TehlikeKategorisi::where('anahtar', 'ekipman_ai_serit_testere')->firstOrFail();
        $this->assertSame('Ekipman – Şerit testere (AI)', $kategori->ad);
        $this->assertSame(1, Tehlike::where('tehlike_kategorisi_id', $kategori->id)->count());
        $this->assertCount(1, $s->get('secilenler'));
        $this->assertSame(Tehlike::first()->id, $s->get('secilenler')[0]['tehlike_id']);

        // Aynı ekipman tekrar: kütüphanede çoğalmaz, analize de ikinci kez girmez; seçilmeyen ikinci madde eklenir
        $s->set('aiEkipmanAdi', 'Şerit testere')->call('aiEkipmanUret')->call('aiEkipmanEkle');
        $this->assertSame(2, Tehlike::where('tehlike_kategorisi_id', $kategori->id)->count());
        $this->assertCount(2, $s->get('secilenler'));
    }

    public function test_sahip_olmayan_kutuphaneye_yazamaz_5x5_puaniyla_ekler(): void
    {
        $this->geminiYaniti();
        $uzman = User::factory()->create(['rol' => 'uzman']);
        $uzman->sayfaYetkileri()->create(['sayfa_anahtari' => 'risk-sihirbazi']);
        $s = $this->sihirbaz($uzman, 'matris_5x5')
            ->assertDontSee('Genel Kütüphaneye de kaydet')
            ->set('aiEkipmanAdi', 'Şerit testere')
            ->call('aiEkipmanUret')
            ->assertSee('O 4 × Ş 4 = 16')
            ->set('aiKutuphaneyeKaydet', true)
            ->call('aiEkipmanEkle')
            ->assertNotified('Maddeler risk tablosuna eklendi');

        $this->assertSame(0, TehlikeKategorisi::count());
        $maddeler = collect($s->get('secilenler'));
        $this->assertCount(2, $maddeler);
        $this->assertSame([4, 4, null], [$maddeler[0]['olasilik'], $maddeler[0]['siddet'], $maddeler[0]['frekans']]);
        $this->assertSame('ai', $maddeler[0]['kaynak']);
    }

    public function test_reziduel_puan_eski_duz_yanittan_hedefe_dusurulur_ve_kutuphaneye_yazilir(): void
    {
        $this->geminiYaniti();
        $s = $this->sihirbaz(User::factory()->create(['rol' => 'sahip']))
            ->set('aiEkipmanAdi', 'Şerit testere')
            ->call('aiEkipmanUret')
            // Rezidüel önerisi yok: Ş ve F sabit, O hedefin (70) altına inene kadar düşer → 0,2×6×40 = 48
            ->assertSee('Mevcut Risk: O 6 × F 6 × Ş 40 = 1440')
            ->assertSee('Önlem Sonrası Risk: O 0,2 × F 6 × Ş 40 = 48');

        $aday = $s->get('aiEkipmanAdaylar')[0];
        $this->assertSame([0.2, 6.0, 40.0], [$aday['son_olasilik'], $aday['son_frekans'], $aday['son_siddet']]);
        $this->assertSame(['olasilik' => 4, 'siddet' => 4, 'son_olasilik' => 1, 'son_siddet' => 4], $aday['matris']);

        $s->call('aiEkipmanEkle');
        $t = Tehlike::firstOrFail();
        $this->assertSame([0.2, 6.0, 40.0], [(float) $t->son_olasilik, (float) $t->son_frekans, (float) $t->son_siddet]);
        $this->assertSame(1, $t->matris['son_olasilik']);
        $this->assertEquals(0.2, $s->get('secilenler')[0]['son_olasilik']);
    }

    public function test_yeni_puanlar_semasi_siddeti_sabit_tutar_ve_5x5_rezidueli_ekler(): void
    {
        $madde = fn (string $tehlike, array $ilk, array $rez) => [
            'bolum' => 'Pres', 'faaliyet' => 'Operasyon', 'tehlike' => $tehlike, 'kok_neden' => 'Koruyucu yok', 'risk' => 'Ezilme',
            'mevcut_onlem' => 'Belirlenmedi', 'oneri' => 'Işık perdesi', 'mevzuat' => 'Makine Emniyeti Yönetmeliği',
            'puanlar' => ['ilk' => $ilk, 'reziduel' => $rez],
        ];
        $maddeler = [
            $madde('Kalıp arasına el girmesi',
                ['matris_o' => 5, 'matris_s' => 3, 'fk_o' => 10, 'fk_f' => 6, 'fk_s' => 15],
                // Yapay zekâ şiddeti de düşürmeye çalışıyor → yok sayılır; O=1 hedefe yetmez (90) → 0,5 (45)
                ['reziduel_matris_o' => 2, 'reziduel_matris_s' => 1, 'reziduel_fk_o' => 1, 'reziduel_fk_f' => 1, 'reziduel_fk_s' => 3]),
            $madde('Volan kopması',
                ['matris_o' => 3, 'matris_s' => 5, 'fk_o' => 3, 'fk_f' => 10, 'fk_s' => 100],
                ['reziduel_matris_o' => 1, 'reziduel_matris_s' => 5, 'reziduel_fk_o' => 0.2, 'reziduel_fk_f' => 10, 'reziduel_fk_s' => 100]),
        ];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode($maddeler, JSON_UNESCAPED_UNICODE)]]]]],
        ])]);

        $uzman = User::factory()->create(['rol' => 'uzman']);
        $uzman->sayfaYetkileri()->create(['sayfa_anahtari' => 'risk-sihirbazi']);

        $this->sihirbaz(User::factory()->create(['rol' => 'sahip']))
            ->set('aiEkipmanAdi', 'Pres')->call('aiEkipmanUret')
            ->assertSee('Önlem Sonrası Risk: O 0,5 × F 6 × Ş 15 = 45')
            // 0,2×10×100 = 200: en düşük olasılıkta bile hedefe inilemiyor → uyarı
            ->assertSee('Önlem Sonrası Risk: O 0,2 × F 10 × Ş 100 = 200')
            ->assertSee('ek mühendislik önlemi gerekir');

        $s = $this->sihirbaz($uzman, 'matris_5x5')
            ->set('aiEkipmanAdi', 'Pres')->call('aiEkipmanUret')
            ->assertSee('Mevcut Risk: O 5 × Ş 3 = 15')
            ->assertSee('Önlem Sonrası Risk: O 2 × Ş 3 = 6')
            ->assertSee('Önlem Sonrası Risk: O 1 × Ş 5 = 5')
            ->call('aiEkipmanEkle');

        $ilk = $s->get('secilenler')[0];
        $this->assertSame([5, 3, 2, 3, null, null], [$ilk['olasilik'], $ilk['siddet'], $ilk['son_olasilik'], $ilk['son_siddet'], $ilk['frekans'], $ilk['son_frekans']]);
    }

    public function test_hata_ve_anahtarsiz_durum_bildirilir(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response('hata', 503)]);
        $s = $this->sihirbaz(User::factory()->create(['rol' => 'sahip']))
            ->set('aiEkipmanAdi', 'Şerit testere')->call('aiEkipmanUret')
            ->assertSee('Yapay zekâ şu an yanıt vermedi');
        $this->assertSame([], $s->get('aiEkipmanAdaylar'));

        config(['services.gemini.key' => null]);
        $s->call('aiEkipmanUret')->assertSee('Yapay zekâ anahtarı (Gemini) tanımlı değil.');

        $s->set('aiEkipmanAdi', 'ab')->call('aiEkipmanUret')->assertHasErrors('aiEkipmanAdi');
    }
}
