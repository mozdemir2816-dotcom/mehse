<?php

namespace Tests\Feature;

use App\Filament\Pages\RiskSihirbazi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\Tehlike;
use App\Models\User;
use App\Support\RiskUretici;
use Database\Seeders\TehlikeKutuphanesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RiskSihirbaziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TehlikeKutuphanesiSeeder::class);
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_sayfa_acilir_ve_ilk_adimda_baslar(): void
    {
        Livewire::test(RiskSihirbazi::class)
            ->assertOk()
            ->assertSet('adim', 1)
            ->assertSet('raporTarihi', now()->toDateString());
    }

    public function test_gecerlilik_tarihi_tehlike_sinifina_gore_hesaplanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);

        Livewire::test(RiskSihirbazi::class)
            ->set('raporTarihi', '2026-01-01')
            ->set('firmaId', $firma->id)
            ->assertSet('gecerlilikTarihi', '2028-01-01'); // +2 yıl
    }

    public function test_firma_ve_yontem_secilmeden_ilerlenemez(): void
    {
        Livewire::test(RiskSihirbazi::class)
            ->call('ileri')
            ->assertSet('adim', 1)                  // firma yok
            ->set('firmaId', Firma::factory()->for($this->uzman)->create()->id)
            ->call('ileri')
            ->assertSet('adim', 2)
            ->call('yontemSec', 'excel')            // hazır değil
            ->assertSet('yontemSecim', null)
            ->call('yontemSec', 'manuel')
            ->assertSet('yontemSecim', 'manuel')
            ->call('ileri')
            ->assertSet('adim', 3);
    }

    public function test_kutuphaneden_tehlike_eklenir_ve_mukerrer_engellenir(): void
    {
        $tehlike = Tehlike::first();

        $component = Livewire::test(RiskSihirbazi::class)
            ->call('tehlikeEkle', $tehlike->id)
            ->call('tehlikeEkle', $tehlike->id);

        $this->assertCount(1, $component->get('secilenler'));
        $this->assertSame($tehlike->tehlike, $component->get('secilenler.0.tehlike'));
    }

    public function test_kategori_tumunu_ekle(): void
    {
        $kategori = Tehlike::first()->kategori;

        $component = Livewire::test(RiskSihirbazi::class)
            ->call('kategoriTumunuEkle', $kategori->id);

        $this->assertCount($kategori->tehlikeler()->count(), $component->get('secilenler'));
    }

    public function test_puansiz_madde_ile_6_adima_gecilemez(): void
    {
        Livewire::test(RiskSihirbazi::class)
            ->call('tehlikeEkle', Tehlike::first()->id)
            ->set('adim', 5)
            ->call('ileri')
            ->assertSet('adim', 5); // puan eksik
    }

    public function test_uctan_uca_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        $t1 = Tehlike::all()->get(0);
        $t2 = Tehlike::all()->get(1);

        Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->set('yontem', 'matris_5x5')
            ->call('ileri')                        // 1 -> 2
            ->call('yontemSec', 'manuel')
            ->call('ileri')                        // 2 -> 3
            ->call('tehlikeEkle', $t1->id)
            ->call('tehlikeEkle', $t2->id)
            ->call('ileri')                        // 3 -> 4
            ->set('varsayilanTermin', 'Sürekli')
            ->call('ileri')                        // 4 -> 5
            ->set('secilenler.0.olasilik', '3')
            ->set('secilenler.0.siddet', '5')
            ->set('secilenler.1.olasilik', '2')
            ->set('secilenler.1.siddet', '2')
            ->call('ileri')                        // 5 -> 6
            ->assertSet('adim', 6)
            ->call('kaydet')
            ->assertRedirect();

        $rd = RiskDegerlendirmesi::firstOrFail();
        $this->assertSame($firma->id, $rd->firma_id);
        $this->assertSame('matris_5x5', $rd->yontem);
        $this->assertCount(2, $rd->maddeler);

        $ilk = $rd->maddeler()->orderBy('sira')->first();
        $this->assertSame(15.0, $ilk->puan);
        $this->assertSame('Yüksek Risk', $ilk->duzey);
        $this->assertSame('Sürekli', $ilk->termin);
    }

    /*
    |--------------------------------------------------------------------------
    | Yapay Zeka (kural tabanlı) akışı — isgpratik 103-115
    |--------------------------------------------------------------------------
    */

    public function test_ai_yontemi_sektor_ve_alt_kategori_akisi(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->call('ileri')
            ->call('yontemSec', 'ai')
            ->assertSet('yontemSecim', 'ai')
            ->call('ileri')
            ->assertSet('adim', 3)
            ->call('aiBaslat')
            ->assertSet('aiAsama', 'sektor')
            ->call('aiSektorSec', 'insaat')
            ->call('aiSektorOnayla')
            ->assertSet('aiAsama', 'altkategori')
            ->call('aiAltKategoriToggle', 0)
            ->call('aiAltKategoriOnayla')
            ->assertSet('aiAsama', 'sohbet');
    }

    public function test_ai_sohbet_cevaplari_aday_risk_uretir_ve_maddelere_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->call('ileri')->call('yontemSec', 'ai')->call('ileri')
            ->call('aiBaslat')
            ->call('aiSektorSec', 'ofis')
            ->call('aiSektorOnayla')
            ->call('aiAltKategoriOnayla');

        // Soruları cevapla — bazı cevaplar eksiklik → risk tetikler
        $cevaplar = [
            'calisan_sayisi' => 'mikro',
            'vardiya' => 'uc',              // gece vardiyası riski
            'tatbikat' => 'hic',            // tatbikat riski
            'yangin_altyapi' => 'yok',      // yangın altyapı riski
            'elektrik' => 'tam',
            'havalandirma' => 'yetersiz',   // havalandırma riski
            'kaza_gecmisi' => 'yok',
            'psikososyal' => 'yonetiliyor',
        ];

        foreach ($cevaplar as $anahtar => $deger) {
            $component->call('aiCevapla', $anahtar, $deger, false);
        }
        // çoklu soru: egitimler → "hicbiri"
        $component->call('aiCevapla', 'egitimler', 'hicbiri', true)
            ->call('aiCokluGonder', 'egitimler');

        $component->assertSet('aiAsama', 'sonuc');
        $this->assertNotEmpty($component->get('aiAdaylar'));

        // "ai" kaynaklı adaylar varsayılan seçili
        $secili = $component->get('aiSecilenAdaylar');
        $this->assertNotEmpty($secili);

        $component->call('aiAdaylariEkle')
            ->assertSet('adim', 4);

        $this->assertNotEmpty($component->get('secilenler'));
        // gece vardiyası riski eklendi mi?
        $tehlikeler = collect($component->get('secilenler'))->pluck('tehlike')->implode(' | ');
        $this->assertStringContainsString('Gece çalışması', $tehlikeler);
    }

    public function test_risk_uretici_eksik_egitim_riskini_tetikler(): void
    {
        $adaylar = RiskUretici::uret('ofis', [], ['egitimler' => ['hicbiri']]);

        $this->assertTrue(
            collect($adaylar)->contains(fn ($a) => str_contains($a['tehlike'], 'eğitimi verilmemiş')),
        );
        // Kütüphane baz riskleri de gelir (genel_isyeri)
        $this->assertTrue(collect($adaylar)->contains('kaynak', 'kutuphane'));
    }

    public function test_ai_soru_atlanabilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->call('ileri')->call('yontemSec', 'ai')->call('ileri')
            ->call('aiBaslat')->call('aiSektorSec', 'ofis')->call('aiSektorOnayla')->call('aiAltKategoriOnayla')
            ->call('aiSoruAtla', 'calisan_sayisi')
            ->assertSet('aiAtlananlar', ['calisan_sayisi']);
    }

    public function test_baska_uzmanin_firmasi_listede_gorunmez(): void
    {
        $benim = Firma::factory()->for($this->uzman)->create();
        $baskasi = Firma::factory()->create();

        $firmalar = Livewire::test(RiskSihirbazi::class)->instance()->firmalar();

        $this->assertArrayHasKey($benim->id, $firmalar);
        $this->assertArrayNotHasKey($baskasi->id, $firmalar);
    }
}
