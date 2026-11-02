<?php

namespace Tests\Feature;

use App\Filament\Resources\RiskDegerlendirmesis\Pages\EditRiskDegerlendirmesi;
use App\Filament\Resources\RiskDegerlendirmesis\RelationManagers\MaddelerRelationManager;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\GeminiRiskPuanTamamlayici;
use Database\Seeders\TehlikeKutuphanesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GeminiRiskPuanTamamlayiciTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        GeminiRiskPuanTamamlayici::devreyiSifirla();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    /** "Toplu Ekle" listesi değerlendirmenin yöntemine + NACE grubuna uyan kategorilerle sınırlıdır. */
    private function kutuphaneyiMatriseAyir(): void
    {
        TehlikeKategorisi::query()->update(['yontem' => 'matris_5x5', 'nace_gruplari' => ['ortak']]);
    }

    public function test_api_anahtari_yokken_pasif_ve_istek_atilmaz(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $this->assertFalse(GeminiRiskPuanTamamlayici::aktifMi());
        $this->assertNull(GeminiRiskPuanTamamlayici::oner('Kaygan zemin', 'Düşme', 'Depo', 'İstifleme', 'matris_5x5'));

        Http::assertNothingSent();
    }

    public function test_matris_5x5_yaniti_en_yakin_olcek_degerine_yuvarlanir(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode(['olasilik' => 3.7, 'siddet' => 4.2])]]]]],
            ]),
        ]);

        $oneri = GeminiRiskPuanTamamlayici::oner('Kaygan zemin', 'Düşme', 'Depo', 'İstifleme', 'matris_5x5');

        $this->assertSame(4.0, $oneri['olasilik']);
        $this->assertSame(4.0, $oneri['siddet']);
        $this->assertNull($oneri['frekans']);
    }

    public function test_fine_kinney_frekans_dahil_edilir_ve_izinli_degere_yuvarlanir(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode(['olasilik' => 0.4, 'siddet' => 8, 'frekans' => 4])]]]]],
            ]),
        ]);

        $oneri = GeminiRiskPuanTamamlayici::oner('Yüksekten düşme', 'Ağır yaralanma', 'Şantiye', 'İskele', 'fine_kinney');

        $this->assertSame(0.5, $oneri['olasilik']);
        $this->assertSame(7.0, $oneri['siddet']);
        $this->assertSame(3.0, $oneri['frekans']);
    }

    public function test_basarisiz_istekte_null_doner(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'kota asildi'], 429),
        ]);

        $this->assertNull(GeminiRiskPuanTamamlayici::oner('Kaygan zemin', 'Düşme', 'Depo', 'İstifleme', 'matris_5x5'));
    }

    public function test_bozuk_json_govdeyi_kirmadan_null_doner(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'JSON degil']]]]],
            ]),
        ]);

        $this->assertNull(GeminiRiskPuanTamamlayici::oner('Kaygan zemin', 'Düşme', 'Depo', 'İstifleme', 'matris_5x5'));
    }

    public function test_kutuphaneden_aktar_ai_aktif_olsa_bile_puanlama_yapmaz(): void
    {
        $this->seed(TehlikeKutuphanesiSeeder::class);
        $this->kutuphaneyiMatriseAyir();
        config(['services.gemini.key' => 'test-anahtar']);
        Http::fake();

        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $tehlike = Tehlike::first();

        Livewire::test(MaddelerRelationManager::class, [
            'ownerRecord' => $rd,
            'pageClass' => EditRiskDegerlendirmesi::class,
        ])->callTableAction('kutuphanedenAktar', data: ['tehlike_idler' => [$tehlike->id]]);

        $madde = RiskMaddesi::where('risk_degerlendirmesi_id', $rd->id)->first();
        $this->assertNotNull($madde);
        // Puanlar AI'dan değil kütüphane maddesinden gelir (maddeyeCevir).
        $this->assertSame($tehlike->mevcut_onlem, $madde->mevcut_onlem);

        // Toplu ekleme AI çağırmaz — performans/kota riski, ayrı "Eksik Puan/
        // Önlemleri AI ile Tamamla" butonuyla sonradan toplu tamamlanır.
        Http::assertNothingSent();
    }

    public function test_kutuphaneden_aktar_birden_fazla_tehlikeyi_tek_seferde_ekler(): void
    {
        $this->seed(TehlikeKutuphanesiSeeder::class);
        $this->kutuphaneyiMatriseAyir();
        Http::fake();

        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $tehlikeIdler = Tehlike::query()->take(3)->pluck('id')->all();
        $this->assertGreaterThanOrEqual(3, count($tehlikeIdler), 'Test için en az 3 tehlike gerekli');

        Livewire::test(MaddelerRelationManager::class, [
            'ownerRecord' => $rd,
            'pageClass' => EditRiskDegerlendirmesi::class,
        ])->callTableAction('kutuphanedenAktar', data: ['tehlike_idler' => $tehlikeIdler]);

        $this->assertSame(3, RiskMaddesi::where('risk_degerlendirmesi_id', $rd->id)->count());
        $this->assertSame([1, 2, 3], RiskMaddesi::where('risk_degerlendirmesi_id', $rd->id)->orderBy('sira')->pluck('sira')->all());

        Http::assertNothingSent();
    }

    /**
     * Toplu puan isteğine (02.11.2026) sahte yanıt: istemdeki her "no" için verilen değerler.
     * Toplu istek dizisi (ARRAY şema) döner.
     */
    private function topluPuanFake(array $degerler): void
    {
        Http::fake(function ($request) use ($degerler) {
            $metin = (string) data_get(json_decode($request->body(), true), 'contents.0.parts.0.text');
            preg_match_all('/"no":\s*(\d+)/', $metin, $e);
            $yanit = array_map(fn ($no) => ['no' => (int) $no] + $degerler, $e[1]);

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($yanit)]]]]]]);
        });
    }

    public function test_eksik_puanlari_tamamla_sadece_eksik_eksenleri_doldurur(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);
        $this->topluPuanFake(['olasilik' => 2, 'siddet' => 3, 'son_olasilik' => 1, 'son_siddet' => 3, 'mevcut_onlem' => 'Kaymaz zemin kaplaması ve uyarı levhası kullanılmalı.']);

        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $eksik = $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Puansız ve önlemsiz madde', 'durum' => 'acik']);
        $sadeceOnlemEksik = $rd->maddeler()->create(['sira' => 2, 'tehlike' => 'Puanlı, önlemsiz madde', 'durum' => 'acik', 'olasilik' => 4, 'siddet' => 4]);
        $tamOlan = $rd->maddeler()->create(['sira' => 3, 'tehlike' => 'Tam dolu madde', 'durum' => 'acik', 'olasilik' => 5, 'siddet' => 5, 'mevcut_onlem' => 'Zaten yazılmış önlem.']);

        Livewire::test(MaddelerRelationManager::class, [
            'ownerRecord' => $rd,
            'pageClass' => EditRiskDegerlendirmesi::class,
        ])->callTableAction('eksikPuanlariTamamla')
            ->assertNotified('✓ 2 adet maddenin eksik alanları (öneri, mevzuat, puan, sorumlu, termin) yapay zekâ ile mevzuata uygun olarak tamamlandı ve kütüphaneye kaydedildi');

        $this->assertSame(2.0, $eksik->fresh()->olasilik);
        $this->assertSame(3.0, $eksik->fresh()->siddet);
        $this->assertSame(1.0, $eksik->fresh()->son_olasilik);
        $this->assertSame('Kaymaz zemin kaplaması ve uyarı levhası kullanılmalı.', $eksik->fresh()->mevcut_onlem);
        // Termin, AI puanından hesaplanan düzeye göre atanır.
        $this->assertSame(\App\Models\RiskMaddesi::varsayilanTermin('matris_5x5', $eksik->fresh()->duzey), $eksik->fresh()->termin);

        // Puanı zaten dolu olan madde AI önerisiyle EZİLMEZ, yalnız eksik önlemi tamamlanır.
        $this->assertSame(4.0, $sadeceOnlemEksik->fresh()->olasilik);
        $this->assertSame(4.0, $sadeceOnlemEksik->fresh()->siddet);
        $this->assertSame('Kaymaz zemin kaplaması ve uyarı levhası kullanılmalı.', $sadeceOnlemEksik->fresh()->mevcut_onlem);

        // Dolu puan ve önlem hiç değişmez (öneri boş olduğu için madde kapsama girer ama yalnız boş alanlar dolar).
        $this->assertSame(5.0, $tamOlan->fresh()->olasilik);
        $this->assertSame('Zaten yazılmış önlem.', $tamOlan->fresh()->mevcut_onlem);

        // 02.11.2026: madde başına ayrı istek yerine tek toplu istek.
        Http::assertSentCount(1);
    }

    public function test_art_arda_basarisizlikta_devre_kesilir_ve_istek_atilmaz(): void
    {
        // Yedek model kapalı: devre kesici sayımı tek modelle (yedekli hâli GeminiIstemciTest).
        config(['services.gemini.key' => 'test-anahtar', 'services.gemini.yedek_modeller' => []]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'kota'], 429)]);

        GeminiRiskPuanTamamlayici::devreyiSifirla();

        // İlk 3 istek gider ve başarısız olur; 4.'de devre kesiktir, HTTP'ye hiç gidilmez.
        for ($i = 0; $i < 6; $i++) {
            GeminiRiskPuanTamamlayici::oner('Tehlike '.$i, null, null, null, 'matris_5x5');
        }

        $this->assertTrue(GeminiRiskPuanTamamlayici::devreKesikMi());
        Http::assertSentCount(3);
    }

    public function test_sure_butcesi_dolunca_devre_kesilir(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);

        GeminiRiskPuanTamamlayici::devreyiSifirla();
        $this->assertFalse(GeminiRiskPuanTamamlayici::devreKesikMi());

        // Bütçenin geçmişte bittiğini taklit et (reflection ile private static alan).
        $prop = new \ReflectionProperty(GeminiRiskPuanTamamlayici::class, 'sonlanma');
        $prop->setAccessible(true);
        $prop->setValue(null, microtime(true) - 1);

        $this->assertTrue(GeminiRiskPuanTamamlayici::devreKesikMi());
    }

    /** 02.11.2026: ilk 25 madde hemen (tek istek), kalanı arka plan kuyruğunda dakikada 25. */
    public function test_eksik_puanlari_tamamla_ilk_parti_hemen_kalan_kuyrukta(): void
    {
        config(['services.gemini.key' => 'test-anahtar']);
        $this->topluPuanFake(['olasilik' => 3, 'frekans' => 6, 'siddet' => 15, 'son_olasilik' => 1, 'son_frekans' => 6, 'son_siddet' => 15]);

        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        for ($i = 1; $i <= 55; $i++) {
            $rd->maddeler()->create(['sira' => $i, 'tehlike' => 'Puansız madde '.$i, 'mevcut_onlem' => 'var', 'durum' => 'acik']);
        }

        Livewire::test(MaddelerRelationManager::class, [
            'ownerRecord' => $rd,
            'pageClass' => EditRiskDegerlendirmesi::class,
        ])->callTableAction('eksikPuanlariTamamla');

        $this->assertSame(25, $rd->maddeler()->whereNotNull('olasilik')->count());
        $this->assertTrue(\App\Console\Commands\RiskPuanTamamla::kuyruktaMi($rd->id));

        // Zamanlayıcı her dakika bir parti: iki çalıştırmada kalan 30 madde biter, rapor kuyruktan çıkar.
        $this->artisan('risk:puan-tamamla')->assertSuccessful();
        $this->artisan('risk:puan-tamamla')->assertSuccessful();
        $this->assertSame(0, $rd->maddeler()->whereNull('olasilik')->count());
        $m = $rd->maddeler()->first();
        $this->assertSame(270.0, (float) $m->puan);   // 3×6×15 → Yüksek
        $this->assertSame('1 Ay', $m->termin);
        $this->artisan('risk:puan-tamamla')->assertSuccessful();
        $this->assertFalse(\App\Console\Commands\RiskPuanTamamla::kuyruktaMi($rd->id));
        Http::assertSentCount(3);
    }

    public function test_kutuphaneden_aktar_mevcut_onlem_bosluysa_bos_eklenir(): void
    {
        $this->seed(TehlikeKutuphanesiSeeder::class);
        $this->kutuphaneyiMatriseAyir();
        Http::fake();

        $tehlike = Tehlike::first();
        $tehlike->update(['mevcut_onlem' => null]);

        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        Livewire::test(MaddelerRelationManager::class, [
            'ownerRecord' => $rd,
            'pageClass' => EditRiskDegerlendirmesi::class,
        ])->callTableAction('kutuphanedenAktar', data: ['tehlike_idler' => [$tehlike->id]]);

        $madde = RiskMaddesi::where('risk_degerlendirmesi_id', $rd->id)->first();
        $this->assertNull($madde->mevcut_onlem);
        Http::assertNothingSent();
    }
}
