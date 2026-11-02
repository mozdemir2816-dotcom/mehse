<?php

namespace Tests\Feature;

use App\Filament\Pages\RiskSihirbazi;
use App\Models\Firma;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\RiskKutuphanesi;
use App\Support\RiskUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Risk Sihirbazı AI akışı (02.11.2026): zorunlu ortak riskler soru sorulmadan eklenir, sorular NACE grubuna
 * göre süzülür, "İşyerinde Kullanılan Ekipman ve Prosesler" adımı ve "önce kütüphane, yoksa AI" ekleme.
 */
class RiskAiNaceEkipmanTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
    }

    private function kategori(string $anahtar, string $ad, array $tehlikeler, ?string $yontem = null): TehlikeKategorisi
    {
        $k = TehlikeKategorisi::create(['anahtar' => $anahtar, 'ad' => $ad, 'yontem' => $yontem, 'sira' => 500]);
        foreach ($tehlikeler as $i => $t) {
            Tehlike::create(['tehlike_kategorisi_id' => $k->id, 'kod' => strtoupper($anahtar).'-'.$i, 'tehlike' => $t,
                'risk' => 'Yaralanma', 'oneri' => 'Önlem alınır', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 15]);
        }

        return $k;
    }

    private function aiAkisi(Firma $firma, string $yontem = 'fine_kinney')
    {
        return Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->set('yontem', $yontem)
            ->call('ileri')->call('yontemSec', 'ai')->call('ileri');
    }

    /** Akıştaki soru anahtarları (cevaplanmadan, sırayla atlayarak). */
    private function sorulanlar($c): array
    {
        $sorulan = [];
        while (($soru = $c->instance()->aiSiradakiSoru()) !== null) {
            $sorulan[] = $soru['anahtar'];
            $c->set('aiAtlananlar', [...$c->get('aiAtlananlar'), $soru['anahtar']]);
        }

        return $sorulan;
    }

    public function test_zorunlu_ortak_riskler_soru_sorulmadan_ve_fine_kinney_puaniyla_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '41.00.01']);

        $c = $this->aiAkisi($firma)->call('aiBaslat')
            ->assertSee('ortak risk')
            ->assertSet('aiOrtakEklenen', count(config('isg.risk_ai.zorunlu_ortak')));

        $secilen = collect($c->get('secilenler'));
        $this->assertCount(count(config('isg.risk_ai.zorunlu_ortak')), $secilen);
        $this->assertTrue($secilen->contains(fn ($m) => str_contains($m['tehlike'], 'Yangın söndürücü')));
        $this->assertTrue($secilen->contains(fn ($m) => str_contains($m['tehlike'], 'ilk yardımcı')));
        $this->assertTrue($secilen->contains(fn ($m) => str_contains($m['tehlike'], 'aydınlatma')));
        // 5x5 ölçekli puanlar Fine-Kinney'e çevrildi — "puan yok" madde kalmaz.
        $this->assertTrue($secilen->every(fn ($m) => $m['olasilik'] !== null && $m['frekans'] !== null && $m['siddet'] !== null));

        // Tekrar başlatmak mükerrer eklemez.
        $c->call('aiBaslat');
        $this->assertCount(count(config('isg.risk_ai.zorunlu_ortak')), $c->get('secilenler'));
    }

    public function test_sorular_nace_grubuna_gore_suzulur_genel_bina_sorulmaz(): void
    {
        $genelBina = ['egitimler', 'tatbikat', 'yangin_altyapi', 'elektrik', 'havalandirma'];

        $insaat = $this->sorulanlar($this->aiAkisi(Firma::factory()->for($this->uzman)->create(['nace_kodu' => '41.00.01']))->call('aiBaslat'));
        $this->assertContains('insaat_yuksekte_calisma', $insaat);
        $this->assertContains('insaat_derin_kazi', $insaat);
        $this->assertContains('insaat_santiye_panosu', $insaat);
        $this->assertContains('insaat_iskele', $insaat);
        $this->assertNotContains('metal_presler', $insaat);
        $this->assertEmpty(array_intersect($genelBina, $insaat));

        $metal = $this->sorulanlar($this->aiAkisi(Firma::factory()->for($this->uzman)->create(['nace_kodu' => '25.62.01']))->call('aiBaslat'));
        $this->assertContains('metal_presler', $metal);
        $this->assertContains('metal_talasli', $metal);
        $this->assertContains('metal_kaldirma', $metal);
        $this->assertNotContains('kimya_statik', $metal);
        $this->assertNotContains('insaat_iskele', $metal);

        $kimya = $this->sorulanlar($this->aiAkisi(Firma::factory()->for($this->uzman)->create(['nace_kodu' => '20.30.01']))->call('aiBaslat'));
        $this->assertContains('kimya_havalandirma_ex', $kimya);
        $this->assertContains('kimya_statik', $kimya);
        $this->assertNotContains('metal_presler', $kimya);

        // Ofis (bilişim) firması: genel bina sorusu da sektörel soru da yok — yalnız organizasyon soruları.
        $ofis = $this->sorulanlar($this->aiAkisi(Firma::factory()->for($this->uzman)->create(['nace_kodu' => '62.01.01']))->call('aiBaslat'));
        $this->assertEqualsCanonicalizing(['calisan_sayisi', 'vardiya', 'kaza_gecmisi', 'psikososyal'], $ofis);
    }

    public function test_nace_siz_firmada_sorular_secilen_sektore_gore_gelir(): void
    {
        $this->assertTrue(RiskUretici::soruUygun(['anahtar' => 'x', 'sektorler' => ['fabrika'], 'nace_gruplari' => ['kimya']], 'fabrika'));
        $this->assertFalse(RiskUretici::soruUygun(['anahtar' => 'x', 'sektorler' => ['fabrika'], 'nace_gruplari' => ['kimya']], 'fabrika', 'metal'));
        $this->assertFalse(RiskUretici::soruUygun(['anahtar' => 'x', 'genel_bina' => true], 'ofis'));
    }

    public function test_ekipman_adimi_nace_onerileri_secilenlerin_kutuphane_risklerini_ekler(): void
    {
        $this->kategori('ekipman_kule_vinc', 'Ekipman – Kule vinç', ['Kule vinçte yük düşmesi', 'Rüzgârda devrilme'], 'fine_kinney');
        $this->kategori('ekipman_konveyor', 'Ekipman – Konveyör', ['Konveyöre kapılma'], 'fine_kinney');
        $firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '41.00.01']);

        $c = $this->aiAkisi($firma)->call('aiBaslat')
            ->assertSet('aiAsama', 'ekipman')
            ->assertSee('İşyerinde Kullanılan Ekipman ve Prosesler')
            ->assertSee('Kule Vinç')
            ->assertSee('Listede Olmayan Ekipman / Özel İş Ekle');

        $kutular = $c->instance()->aiEkipmanKutulari;
        $this->assertArrayHasKey('oz_kule_vinc', $kutular['one_cikan']);
        // Kütüphanede maddesi olmayan öneriler (ör. iskele) gösterilmez; diğer klasörler aranabilir listede.
        $this->assertArrayNotHasKey('oz_iskele', $kutular['one_cikan']);
        $this->assertArrayHasKey('kat_ekipman_konveyor', $kutular['diger']);
        $this->assertArrayNotHasKey('kat_ekipman_kule_vinc', $kutular['diger']);

        $once = count($c->get('secilenler'));
        $c->call('hizliEkipmanToggle', 'oz_kule_vinc')
            ->call('hizliEkipmanToggle', 'kat_ekipman_konveyor')
            ->call('aiEkipmanAdiminiTamamla')
            ->assertSet('aiAsama', 'sohbet')
            ->assertSet('hizliEkipmanSecim', []);

        $eklenen = collect($c->get('secilenler'))->slice($once);
        $this->assertCount(3, $eklenen);
        $this->assertTrue($eklenen->every(fn ($m) => $m['kaynak'] === 'kutuphane' && $m['olasilik'] === 3.0 && $m['siddet'] === 15.0));
    }

    public function test_ozel_ekipman_once_kutuphanede_aranir_api_cagrilmaz(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);
        Http::fake();
        $this->kategori('ekipman_forklift', 'Ekipman – Forklift', ['Forklift devrilmesi', 'Yayaya çarpma'], 'fine_kinney');
        $firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '46.90.01']);

        $c = $this->aiAkisi($firma)->call('aiBaslat')
            ->set('aiEkipmanAdi', 'forklıft')
            ->call('aiOzelEkipmanEkle')
            ->assertNotified('2 madde kütüphaneden eklendi');

        Http::assertNothingSent();
        $this->assertStringContainsString('Kütüphanede bulundu: Forklift', $c->get('aiOzelSonuc'));
        $this->assertTrue(collect($c->get('secilenler'))->contains('tehlike', 'Yayaya çarpma'));
    }

    public function test_kutuphanede_olmayan_ekipman_ai_ile_uretilir_ozel_ekipman_olarak_kaydedilir_sonra_kutuphaneden_gelir(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode([
                ['bolum' => 'Ultrasonik kaynak', 'faaliyet' => 'Kaynak', 'tehlike' => 'Sonotrot altında el sıkışması', 'kok_neden' => 'İki el kumanda yok',
                    'risk' => 'Ezilme', 'mevcut_onlem' => 'Belirlenmedi', 'oneri' => 'İki el kumanda ve ışık perdesi',
                    'mevzuat' => 'Makine Emniyeti Yönetmeliği',
                    'puanlar' => ['ilk' => ['matris_o' => 4, 'matris_s' => 4, 'fk_o' => 3, 'fk_f' => 6, 'fk_s' => 15],
                        'reziduel' => ['reziduel_matris_o' => 1, 'reziduel_matris_s' => 4, 'reziduel_fk_o' => 0.5, 'reziduel_fk_f' => 6, 'reziduel_fk_s' => 15]]],
            ], JSON_UNESCAPED_UNICODE)]]]]],
        ])]);
        $firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '22.29.01', 'tehlike_sinifi' => 'tehlikeli']);

        $c = $this->aiAkisi($firma)->call('aiBaslat')
            ->set('aiEkipmanAdi', 'Ultrasonik kaynak makinesi')
            ->call('aiOzelEkipmanEkle');

        Http::assertSent(fn ($istek) => str_contains(json_encode($istek->data(), JSON_UNESCAPED_UNICODE), 'NACE 22.29.01')
            && str_contains(json_encode($istek->data(), JSON_UNESCAPED_UNICODE), 'Tehlike sınıfı'));

        $madde = collect($c->get('secilenler'))->firstWhere('tehlike', 'Sonotrot altında el sıkışması');
        $this->assertNotNull($madde);
        $this->assertSame(3.0, (float) $madde['olasilik']);
        $this->assertSame(15.0, (float) $madde['siddet']);
        $this->assertStringContainsString('Özel Ekipman', $c->get('aiOzelSonuc'));

        $kategori = TehlikeKategorisi::where('anahtar', 'like', 'ekipman_ai_%')->firstOrFail();
        $this->assertSame('Ekipman – Ultrasonik kaynak makinesi (Özel Ekipman)', $kategori->ad);
        $this->assertSame(1, $kategori->tehlikeler()->count());

        // İkinci firmada aynı ekipman kütüphaneden gelir, yapay zekâya yeniden sorulmaz.
        Http::fake();
        $this->assertCount(1, RiskKutuphanesi::ekipmanAra('ultrasonik kaynak makinesi', 'fine_kinney'));
        $this->aiAkisi(Firma::factory()->for($this->uzman)->create(['nace_kodu' => '22.29.01']))->call('aiBaslat')
            ->set('aiEkipmanAdi', 'Ultrasonik Kaynak Makinesi')
            ->call('aiOzelEkipmanEkle')
            ->assertNotified('1 madde kütüphaneden eklendi');
        Http::assertNothingSent();
    }

    public function test_risk_uretici_kutuphane_adaylarini_puanlariyla_verir(): void
    {
        $this->kategori('insaat', 'İnşaat / Yapı', ['İskeleden düşme']);

        $aday = collect(RiskUretici::uret('insaat', [], [], 'fine_kinney'))->firstWhere('tehlike', 'İskeleden düşme');

        $this->assertSame('kutuphane', $aday['kaynak']);
        $this->assertSame(3.0, $aday['olasilik']);
        $this->assertSame(6.0, $aday['frekans']);
    }
}
