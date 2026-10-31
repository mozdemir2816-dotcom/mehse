<?php

namespace Tests\Feature;

use App\Filament\Pages\YillikPlan\YillikCalismaPlani;
use App\Filament\Pages\YillikPlan\YillikDegerlendirmeRaporu;
use App\Filament\Pages\YillikPlan\YillikEgitimPlani;
use App\Models\Firma;
use App\Models\TatbikatTutanagi;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\SayfaAileleri;
use App\Support\YillikPlanGerceklesme;
use App\Support\YillikPlanSablonu;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/** İSG Yıllık Planlama & Değerlendirme Merkezi: tek menü girişi, otomatik G, gerçekleşme oranı, kurul periyodu. */
class YillikPlanMerkeziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 10:00:00');
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function firma(array $ek = []): Firma
    {
        return Firma::factory()->for($this->uzman)->create($ek + ['sozlesme_baslangic' => '2026-01-01', 'calisan_sayisi' => 80, 'tehlike_sinifi' => 'cok_tehlikeli']);
    }

    public function test_menude_tek_giris_ve_uc_ana_sekme(): void
    {
        // 31.10.2026: kullanıcı isteğiyle Eğitim merkezinden çıkarıldı; her yıl yenilendiği için grupsuz ayrı giriş, Eğitim'in altında.
        $grupsuz = collect(Filament::getNavigation())->first(fn ($g) => blank($g->getLabel()))
            ->getItems()->map(fn ($i) => $i->getLabel())->values()->all();
        $i = array_search('Yıllık Planlar & Değerlendirme', $grupsuz, true);
        $this->assertNotFalse($i, implode(' | ', $grupsuz));
        $this->assertStringStartsWith('Eğitim', $grupsuz[$i - 1], implode(' | ', $grupsuz));
        $this->assertFalse(YillikEgitimPlani::shouldRegisterNavigation());
        $this->assertFalse(YillikDegerlendirmeRaporu::shouldRegisterNavigation());
        $this->assertTrue(YillikCalismaPlani::shouldRegisterNavigation());

        $g = SayfaAileleri::sekmeGruplari([YillikDegerlendirmeRaporu::class]);
        $this->assertSame('yillik', $g['aile']);
        $this->assertSame(
            ['Yıllık Çalışma Planı (Master P/G Matrisi)', 'Yıllık Eğitim Planı', 'Yıl Sonu Değerlendirme Raporu'],
            array_column($g['ust'], 'etiket'),
        );
        $this->assertSame('Yıl Sonu Değerlendirme Raporu', collect($g['ust'])->firstWhere('aktif', true)['etiket']);

        foreach ([YillikCalismaPlani::class, YillikEgitimPlani::class, YillikDegerlendirmeRaporu::class] as $sayfa) {
            $this->get($sayfa::getUrl())->assertSuccessful()->assertSee('Yıl Sonu Değerlendirme Raporu', false);
        }
    }

    public function test_tatbikat_kaydi_plandaki_tatbikat_satirini_g_yapar(): void
    {
        $firma = $this->firma();
        TatbikatTutanagi::create(['firma_id' => $firma->id, 'senaryo_anahtari' => 'yangin', 'durum' => 'yapildi', 'tatbikat_tarihi' => '2026-03-12']);
        TatbikatTutanagi::create(['firma_id' => $firma->id, 'senaryo_anahtari' => 'yangin', 'durum' => 'taslak', 'tatbikat_tarihi' => '2026-05-12']);
        $plan = YillikPlan::firmaYilIcin($firma, 2026);

        $this->assertGreaterThan(0, YillikPlanGerceklesme::isle($plan));

        $satir = collect($plan->fresh()->faaliyetler)->first(fn ($f) => str_contains($f['faaliyet'], 'tatbikat'));
        $this->assertSame('tamamlandi', $satir['aylar'][2]);   // Mart: yapıldı
        $this->assertNotSame('tamamlandi', $satir['aylar'][4]);   // Mayıs: taslak, sayılmaz
        $this->assertSame(0, YillikPlanGerceklesme::isle($plan->fresh()));   // ikinci çalıştırma değişiklik yapmaz
    }

    public function test_gerceklesme_orani_ve_geciken_sayisi(): void
    {
        $plan = new YillikPlan([
            'yil' => 2026,
            'faaliyetler' => [['aylar' => ['tamamlandi', 'tamamlandi', 'planlandi', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'planlandi', 'planlandi', 'planlandi']]],
            'egitimler' => [['aylar' => ['planlandi', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos']]],
        ]);

        // Ekim 2026 (indeks 9): sınır = 9 → vadesi gelen: 2 G + P(Mart) + P(Ocak, eğitim) ; Ekim/Kasım/Aralık henüz vadesinde değil
        $oz = $plan->gerceklesmeOzeti();
        $this->assertSame(['planlanan' => 4, 'gerceklesen' => 2, 'geciken' => 2, 'oran' => 50], $oz);
        $this->assertSame(9, $plan->gecikmeAyIndeksi());

        $gelecek = new YillikPlan(['yil' => 2027, 'faaliyetler' => [['aylar' => ['planlandi']]], 'egitimler' => []]);
        $this->assertSame(0, $gelecek->gecikmeAyIndeksi());
        $this->assertNull($gelecek->gerceklesmeOzeti()['oran']);
    }

    public function test_kurul_aylari_tehlike_sinifina_ve_elli_calisana_gore(): void
    {
        $sablon = fn (Firma $f) => collect(YillikPlanSablonu::icerik($f, 2026)['faaliyetler'])->first(fn ($x) => $x['ana_konu'] === 'İSG KURULU');
        $planli = fn (array $s) => array_keys(array_filter($s['aylar'], fn ($d) => $d === 'planlandi'));

        $this->assertSame(range(0, 11), $planli($sablon($this->firma(['tehlike_sinifi' => 'cok_tehlikeli']))));
        $this->assertSame([0, 2, 4, 6, 8, 10], $planli($sablon($this->firma(['tehlike_sinifi' => 'tehlikeli']))));
        $this->assertSame([0, 3, 6, 9], $planli($sablon($this->firma(['tehlike_sinifi' => 'az_tehlikeli']))));
        // 50 çalışan altında İSG Kurulu zorunlu değil: plan satırı boş kalır.
        $this->assertSame([], $planli($sablon($this->firma(['calisan_sayisi' => 30]))));
        $this->assertSame([], $planli($sablon($this->firma(['calisan_sayisi' => 49, 'tehlike_sinifi' => 'cok_tehlikeli']))));
        $this->assertSame(range(0, 11), $planli($sablon($this->firma(['calisan_sayisi' => 50, 'tehlike_sinifi' => 'cok_tehlikeli']))));
    }

    public function test_mevcut_planda_zorunlu_olmayan_firmanin_planli_kurul_aylari_temizlenir(): void
    {
        $firma = $this->firma(['calisan_sayisi' => 80, 'tehlike_sinifi' => 'cok_tehlikeli']);
        $plan = YillikPlan::firmaYilIcin($firma, 2026);
        $faaliyetler = $plan->faaliyetler;
        $i = collect($faaliyetler)->search(fn ($f) => $f['ana_konu'] === 'İSG KURULU');
        $faaliyetler[$i]['aylar'][2] = 'tamamlandi';
        $plan->update(['faaliyetler' => $faaliyetler]);

        // Çalışan sayısı sonradan 50 altına inince plan açılırken planlı aylar boşalır, tamamlandı kalır.
        $firma->update(['calisan_sayisi' => 30]);
        $satir = collect(YillikPlan::firmaYilIcin($firma->fresh(), 2026)->faaliyetler)->first(fn ($f) => $f['ana_konu'] === 'İSG KURULU');

        $this->assertSame('tamamlandi', $satir['aylar'][2]);
        $this->assertSame([], array_keys(array_filter($satir['aylar'], fn ($d) => $d === 'planlandi')));
    }

    public function test_egitim_katilim_formundaki_ise_ozgu_konular_plan_4_bolumune_yazilir(): void
    {
        $firma = $this->firma(['nace_kodu' => '86.10.05']);
        $plan = YillikPlan::firmaYilIcin($firma, 2026);
        $once = collect($plan->egitimler)->where('kategori', 'ise_ozgu')->pluck('konu')->all();

        $kayit = \App\Models\EgitimKatilim::create([
            'firma_id' => $firma->id, 'baslik_anahtari' => 'genel', 'egitim_turu' => 'ilk', 'egitim_sekli' => 'yuz_yuze',
            'belge_tarihi' => '2026-03-10', 'sure_gun' => 1, 'katilimcilar' => [],
            'konu_secimleri' => ['tip' => 'genel', 'isyerine_ozgu' => ['maddeler' => [
                ['madde' => 'Ameliyathane radyasyon güvenliği', 'dakika' => 10, 'dahil' => true],
                ['madde' => 'Kesici delici alet yaralanmaları', 'dakika' => 10, 'dahil' => true],
                ['madde' => 'Çıkarılan konu', 'dakika' => 10, 'dahil' => false],
            ]]],
        ]);

        $this->assertTrue(\App\Support\IseOzguSenkron::formdanPlana($kayit->fresh()));

        $satirlar = collect(YillikPlan::firmaYilIcin($firma, 2026)->egitimler)->where('kategori', 'ise_ozgu')->values();
        $this->assertSame(['Ameliyathane radyasyon güvenliği', 'Kesici delici alet yaralanmaları'], $satirlar->pluck('konu')->all());
        $this->assertNotSame($once, $satirlar->pluck('konu')->all());
        $this->assertCount(12, $satirlar[0]['aylar']);

        // Aynı kayıt tekrar işlenince değişiklik yok; formda hiç işe özgü konu yoksa plan boşaltılmaz.
        $this->assertFalse(\App\Support\IseOzguSenkron::formdanPlana($kayit->fresh()));
        $bos = \App\Models\EgitimKatilim::create([
            'firma_id' => $firma->id, 'baslik_anahtari' => 'genel', 'egitim_turu' => 'ilk', 'egitim_sekli' => 'yuz_yuze',
            'belge_tarihi' => '2026-04-10', 'sure_gun' => 1, 'katilimcilar' => [],
            'konu_secimleri' => ['tip' => 'genel', 'isyerine_ozgu' => ['maddeler' => []]],
        ]);
        $this->assertFalse(\App\Support\IseOzguSenkron::formdanPlana($bos));
        $this->assertCount(2, collect(YillikPlan::firmaYilIcin($firma, 2026)->egitimler)->where('kategori', 'ise_ozgu'));
    }

    public function test_yeni_plan_4_bolumu_ayni_nace_kodlu_firmanin_planindan_gelir(): void
    {
        // Kütüphanede eşleşmeyen NACE (örnek kod) — kaynak firmanın planına elle konu eklenir.
        $kaynak = $this->firma(['nace_kodu' => '99.99.01']);
        $plan = YillikPlan::firmaYilIcin($kaynak, 2026);
        $plan->update(['egitimler' => \App\Support\YillikPlanSablonu::iseOzguYerlestir($plan->egitimler, YillikPlan::maddeAylarIle([[
            'konu' => 'Özel proses riskleri', 'kategori' => 'ise_ozgu', 'hedef' => 'x', 'egitici' => 'İŞ GÜVENLİĞİ UZMANI', 'hafta' => 2, 'varsayilan_aylar' => [3],
        ]], 0))]);

        $hedef = $this->firma(['nace_kodu' => '99.99.07']);   // aynı sınıf (99.99), farklı tam kod
        $satirlar = collect(YillikPlan::firmaYilIcin($hedef, 2026)->egitimler)->where('kategori', 'ise_ozgu')->values();

        $this->assertSame(['Özel proses riskleri'], $satirlar->pluck('konu')->all());

        $baska = $this->firma(['nace_kodu' => '94.99.01']);   // ne kütüphanede ne başka firmada eşleşme yok
        $this->assertSame([], collect(YillikPlan::firmaYilIcin($baska, 2026)->egitimler)->where('kategori', 'ise_ozgu')->values()->all());
    }

    public function test_sayfa_acilinca_g_islenir_ve_oran_gorunur(): void
    {
        $firma = $this->firma();
        TatbikatTutanagi::create(['firma_id' => $firma->id, 'senaryo_anahtari' => 'yangin', 'durum' => 'yapildi', 'tatbikat_tarihi' => '2026-03-12']);

        Livewire::test(YillikCalismaPlani::class)
            ->set('firmaId', $firma->id)
            ->assertSee('Yıllık Plan Gerçekleşme Oranı');

        $satir = collect(YillikPlan::firmaYilIcin($firma, 2026)->faaliyetler)->first(fn ($f) => str_contains($f['faaliyet'], 'tatbikat'));
        $this->assertSame('tamamlandi', $satir['aylar'][2]);
    }
}
