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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tam alan tamamlama + kütüphaneye kalıcı geri yazma (02.11.2026): App\Support\RiskAlanTamamlama,
 * risk:puan-tamamla, risk:repair-library-all-fields, risk:sync-library-recommendations.
 */
class RiskAlanTamamlamaTest extends TestCase
{
    use RefreshDatabase;

    private User $sahip;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => 'test-anahtar', 'services.gemini.yedek_modeller' => []]);
        $this->sahip = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->sahip);
    }

    /** Her "no" için aynı alanları döndüren sahte Gemini; $ozel[no] ile maddeye özel alanlar. */
    private function aiFake(array $alanlar, array $ozel = []): void
    {
        Http::fake(function ($istek) use ($alanlar, $ozel) {
            $metin = (string) data_get(json_decode($istek->body(), true), 'contents.0.parts.0.text');
            preg_match_all('/"no":\s*(\d+)/', $metin, $e);
            $yanit = array_map(fn ($no) => ['no' => (int) $no] + ($ozel[(int) $no] ?? []) + $alanlar, $e[1]);

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($yanit, JSON_UNESCAPED_UNICODE)]]]]]]);
        });
    }

    private function kutuphane(array $alanlar = [], string $kategori = 'ekipman_mikser', ?string $yontem = null): Tehlike
    {
        $k = TehlikeKategorisi::firstOrCreate(['anahtar' => $kategori], ['ad' => 'Ekipman – Beton mikseri', 'yontem' => $yontem, 'sira' => 1]);

        return Tehlike::create(array_merge(['tehlike_kategorisi_id' => $k->id, 'kod' => 'T-'.uniqid(), 'tehlike' => 'Mikserin geri manevrada çalışana çarpması'], $alanlar));
    }

    private function tamAi(): array
    {
        return [
            'risk' => 'Ezilme, iç kanama, uzuv kaybı ve ölüm', 'mevcut_onlem' => 'Geri vites ikaz sesi mevcut.',
            'oneri' => 'Geri manevrada işaretçi görevlendirilir, araç-yaya yolları ayrılır.',
            'mevzuat' => 'Yapı İşlerinde İSG Yönetmeliği Ek-4', 'sorumlu' => 'Şantiye Şefi', 'risk_uyumlu' => true,
            'olasilik' => 3, 'frekans' => 6, 'siddet' => 40, 'son_olasilik' => 1, 'son_frekans' => 6, 'son_siddet' => 40,
        ];
    }

    public function test_analizdeki_bos_alanlar_tamamlanir_kutuphaneye_kalici_yazilir(): void
    {
        $this->aiFake($this->tamAi());
        $t = $this->kutuphane();
        $firma = Firma::factory()->for($this->sahip)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $madde = $rd->maddeler()->create(['sira' => 1, 'tehlike_id' => $t->id, 'tehlike' => $t->tehlike, 'durum' => 'acik']);
        $elle = $rd->maddeler()->create(['sira' => 2, 'tehlike' => 'Elle yazılmış madde', 'durum' => 'acik',
            'oneri' => 'Kullanıcının kendi önerisi', 'sorumlu' => 'Ustabaşı']);

        Livewire::test(MaddelerRelationManager::class, ['ownerRecord' => $rd, 'pageClass' => EditRiskDegerlendirmesi::class])
            ->assertTableActionExists('eksikPuanlariTamamla')
            ->callTableAction('eksikPuanlariTamamla')
            ->assertNotified('✓ 2 adet maddenin eksik alanları (öneri, mevzuat, Fine-Kinney puan, sorumlu, termin) yapay zekâ ile mevzuata uygun olarak tamamlandı ve kütüphaneye kaydedildi');

        $m = $madde->fresh();
        $this->assertSame('Ezilme, iç kanama, uzuv kaybı ve ölüm', $m->risk);
        $this->assertSame('Geri manevrada işaretçi görevlendirilir, araç-yaya yolları ayrılır.', $m->oneri);
        $this->assertSame('Şantiye Şefi', $m->sorumlu);
        $this->assertSame(720.0, (float) $m->puan);               // 3 × 6 × 40
        $this->assertSame(RiskMaddesi::varsayilanTermin('fine_kinney', $m->duzey), $m->termin);

        // Kütüphane kaydı kalıcı doldu — bir sonraki analizde dolu gelir.
        $t->refresh();
        $this->assertSame('Geri manevrada işaretçi görevlendirilir, araç-yaya yolları ayrılır.', $t->oneri);
        $this->assertSame('Geri vites ikaz sesi mevcut.', $t->mevcut_onlem);
        $this->assertSame('Yapı İşlerinde İSG Yönetmeliği Ek-4', $t->mevzuat);
        $this->assertSame('Şantiye Şefi', $t->sorumlu);
        $this->assertSame('Ezilme, iç kanama, uzuv kaybı ve ölüm', $t->risk);
        $this->assertSame(3.0, (float) $t->olasilik);

        // Kullanıcının yazdığı öneri ve sorumlu korunur.
        $this->assertSame('Kullanıcının kendi önerisi', $elle->fresh()->oneri);
        $this->assertSame('Ustabaşı', $elle->fresh()->sorumlu);
        Http::assertSentCount(1);
    }

    public function test_elle_doldurulan_oneri_kutuphaneye_yazilir_sahip_olmayan_yazamaz(): void
    {
        $t = $this->kutuphane();
        $firma = Firma::factory()->for($this->sahip)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $madde = $rd->maddeler()->create(['sira' => 1, 'tehlike_id' => $t->id, 'tehlike' => $t->tehlike, 'durum' => 'acik']);

        // Gerçek düzenlemede madde veritabanından yeniden yüklenir (yeni oluşturulmuş örnek değil).
        $madde = $madde->fresh();
        $this->actingAs(User::factory()->create(['rol' => 'uzman']));
        $madde->update(['oneri' => 'Başka kullanıcının önerisi']);
        $this->assertNull($t->fresh()->oneri);

        $this->actingAs($this->sahip);
        $madde->update(['oneri' => '']);
        $madde->update(['oneri' => 'Sahibin elle yazdığı öneri']);
        $this->assertSame('Sahibin elle yazdığı öneri', $t->fresh()->oneri);

        // Dolu kütüphane önerisinin üzerine yazılmaz.
        $madde->update(['oneri' => 'Değiştirilmiş öneri']);
        $this->assertSame('Sahibin elle yazdığı öneri', $t->fresh()->oneri);
    }

    public function test_rapor_secenegi_belge_nosuyla_tamamlar(): void
    {
        $this->aiFake($this->tamAi());
        $firma = Firma::factory()->for($this->sahip)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        foreach (range(1, 30) as $i) {
            $rd->maddeler()->create(['sira' => $i, 'tehlike' => "Tehlike {$i}", 'durum' => 'acik']);
        }

        $this->artisan('risk:puan-tamamla', ['--rapor' => [$rd->belge_no]])
            ->expectsOutputToContain('25 tamamlandı')
            ->assertSuccessful();

        $this->assertSame(25, $rd->maddeler()->whereNotNull('oneri')->count());
        $this->assertTrue(\App\Console\Commands\RiskPuanTamamla::kuyruktaMi($rd->id));

        $this->artisan('risk:puan-tamamla')->assertSuccessful();
        $this->assertSame(30, $rd->maddeler()->whereNotNull('oneri')->count());
    }

    public function test_mevzuat_secenegi_yalniz_mevzuati_bos_maddeleri_doldurur(): void
    {
        // 12.11.2026: Excel'den gelen (kütüphanesiz) maddelerin mevzuatı AI ile, kullanıcının yazdığına dokunmadan.
        $this->aiFake($this->tamAi());
        $firma = Firma::factory()->for($this->sahip)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now(), 'belge_no' => 'RD-MVZ-1']);
        $tam = ['risk' => 'Yaralanma', 'mevcut_onlem' => 'Var', 'oneri' => 'Kullanıcının önerisi', 'sorumlu' => 'Ustabaşı',
            'olasilik' => 3, 'frekans' => 6, 'siddet' => 15, 'son_olasilik' => 1, 'son_frekans' => 6, 'son_siddet' => 15, 'durum' => 'acik'];
        $bos = $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Excel maddesi'] + $tam);
        $dolu = $rd->maddeler()->create(['sira' => 2, 'tehlike' => 'Mevzuatlı madde', 'mevzuat' => 'Kullanıcının mevzuatı'] + $tam);

        $this->artisan('risk:puan-tamamla', ['--rapor' => ['RD-MVZ-1'], '--mevzuat' => true])
            ->expectsOutputToContain('mevzuatı boş 1 maddeden 1 doldu, kalan 0')
            ->assertSuccessful();

        $this->assertSame('Yapı İşlerinde İSG Yönetmeliği Ek-4', $bos->fresh()->mevzuat);
        $this->assertSame('Kullanıcının önerisi', $bos->fresh()->oneri);
        $this->assertSame('Kullanıcının mevzuatı', $dolu->fresh()->mevzuat);
    }

    public function test_kutuphane_onarimi_once_analizlerden_kotasiz_kopyalar(): void
    {
        Http::fake();
        $t = $this->kutuphane();
        $firma = Firma::factory()->for($this->sahip)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        RiskMaddesi::withoutEvents(fn () => $rd->maddeler()->create(['sira' => 1, 'tehlike_id' => $t->id, 'tehlike' => $t->tehlike, 'durum' => 'acik',
            'oneri' => 'Analizde doldurulmuş öneri', 'mevcut_onlem' => 'Analizdeki önlem', 'sorumlu' => RiskMaddesi::VARSAYILAN_SORUMLU]));

        $this->artisan('risk:repair-library-all-fields', ['--limit' => 0])->assertSuccessful();

        $t->refresh();
        $this->assertSame('Analizde doldurulmuş öneri', $t->oneri);
        $this->assertSame('Analizdeki önlem', $t->mevcut_onlem);
        $this->assertNull($t->sorumlu);   // sistemin varsayılan sorumlusu kütüphaneye taşınmaz
        Http::assertNothingSent();
    }

    public function test_kutuphane_onarimi_ai_ile_doldurur_celisen_riski_duzeltir_hazopa_dokunmaz(): void
    {
        $yanlis = $this->kutuphane(['risk' => 'Elektrik çarpması', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 40]);
        $dogru = $this->kutuphane(['tehlike' => 'Kalıp sökümünde malzeme düşmesi', 'risk' => 'Kafa travması', 'oneri' => 'Mevcut öneri']);
        $hazop = $this->kutuphane(['tehlike' => 'HAZOP senaryosu'], 'hazop_x', 'hazop');

        $this->aiFake($this->tamAi(), [
            $yanlis->id => ['risk_uyumlu' => false],   // mikser çarpmasına "Elektrik çarpması" çelişkili
            $dogru->id => ['risk' => 'Başka bir şey', 'risk_uyumlu' => true, 'oneri' => 'AI önerisi'],
        ]);

        $this->artisan('risk:repair-library-all-fields', ['--limit' => 25])->assertSuccessful();

        $yanlis->refresh();
        $this->assertSame('Ezilme, iç kanama, uzuv kaybı ve ölüm', $yanlis->risk);   // çelişen risk düzeltildi
        $this->assertSame('Geri manevrada işaretçi görevlendirilir, araç-yaya yolları ayrılır.', $yanlis->oneri);
        $this->assertSame('Yapı İşlerinde İSG Yönetmeliği Ek-4', $yanlis->mevzuat);
        $this->assertSame(3.0, (float) $yanlis->olasilik);   // dolu puan korunur
        $this->assertSame(RiskMaddesi::varsayilanSonOlasilik('fine_kinney', 3, 6, 40), (float) $yanlis->son_olasilik);   // kotasız adımda hesaplandı
        $this->assertSame('Derhal', $yanlis->termin);         // 3 × 6 × 40 = 720 → çok yüksek

        $dogru->refresh();
        $this->assertSame('Kafa travması', $dogru->risk);     // uyumlu risk ve dolu öneri korunur
        $this->assertSame('Mevcut öneri', $dogru->oneri);
        $this->assertSame('Şantiye Şefi', $dogru->sorumlu);

        $this->assertNull($hazop->fresh()->oneri);
        Http::assertSentCount(1);

        // Doldurulamayan alanı kalan madde yeniden istenmez (kota döngüsü yok).
        $this->artisan('risk:repair-library-all-fields', ['--limit' => 25])->assertSuccessful();
        Http::assertSentCount(1);
    }

    public function test_kutuphane_onarimi_rezidueli_ve_termini_kotasiz_hesaplar(): void
    {
        Http::fake();
        $t = $this->kutuphane(['olasilik' => 3, 'frekans' => 6, 'siddet' => 40]);

        $this->artisan('risk:repair-library-all-fields', ['--limit' => 0])->assertSuccessful();

        $t->refresh();
        $this->assertSame(40.0, (float) $t->son_siddet);        // önlem şiddeti değiştirmez
        $this->assertSame(6.0, (float) $t->son_frekans);
        $this->assertSame(RiskMaddesi::varsayilanSonOlasilik('fine_kinney', 3, 6, 40), (float) $t->son_olasilik);
        $this->assertSame('Derhal', $t->termin);                // 720 → çok yüksek
        Http::assertNothingSent();
    }

    public function test_zamanlanmis_onarim_kota_dolunca_ertesi_gune_kadar_durur(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'kota'], 429)]);
        $this->kutuphane();

        $this->artisan('risk:repair-library-all-fields', ['--zamanlanmis' => true, '--limit' => 40])
            ->expectsOutputToContain('günlük kotası doldu')
            ->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has(\App\Console\Commands\RiskKutuphaneOnar::KOTA_ANAHTARI));

        // Kota dolu işaretliyken zamanlanmış çalıştırma istek atmaz.
        $this->artisan('risk:repair-library-all-fields', ['--zamanlanmis' => true, '--limit' => 40])->assertSuccessful();
        Http::assertSentCount(1);
    }

    public function test_oneri_senkronu_yalniz_onerisi_bos_maddeleri_isler(): void
    {
        $bos = $this->kutuphane(['tehlike' => 'Önerisi boş madde']);
        $dolu = $this->kutuphane(['tehlike' => 'Önerisi dolu madde', 'oneri' => 'Var olan öneri']);
        $this->aiFake($this->tamAi());

        $this->artisan('risk:sync-library-recommendations')
            ->expectsOutputToContain('önerisi boş kalan: 0')
            ->assertSuccessful();

        $this->assertSame('Geri manevrada işaretçi görevlendirilir, araç-yaya yolları ayrılır.', $bos->fresh()->oneri);
        $this->assertNull($dolu->fresh()->mevzuat);   // önerisi dolu madde bu komutun kapsamında değil
        Http::assertSent(fn ($istek) => ! str_contains($istek->body(), 'Önerisi dolu madde'));
    }

    public function test_bos_alan_suzgeci_eksik_maddeleri_listeler(): void
    {
        $firma = Firma::factory()->for($this->sahip)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $tam = ['risk' => 'Yaralanma', 'mevcut_onlem' => 'Var', 'oneri' => 'Öneri', 'sorumlu' => 'Ustabaşı',
            'olasilik' => 3, 'frekans' => 6, 'siddet' => 7, 'son_olasilik' => 1, 'son_frekans' => 6, 'son_siddet' => 7, 'durum' => 'acik'];
        $dolu = $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Dolu madde'] + $tam);
        $onlemsiz = $rd->maddeler()->create(['sira' => 2, 'tehlike' => 'Önlemsiz', 'mevcut_onlem' => null] + $tam);
        $varsayilan = $rd->maddeler()->create(['sira' => 3, 'tehlike' => 'Varsayılan sorumlu', 'sorumlu' => RiskMaddesi::VARSAYILAN_SORUMLU] + $tam);
        $kopya = $rd->maddeler()->create(['sira' => 4, 'tehlike' => 'Kaygan zemin', 'risk' => 'Kaygan zemin'] + $tam);

        $lw = Livewire::test(MaddelerRelationManager::class, ['ownerRecord' => $rd, 'pageClass' => EditRiskDegerlendirmesi::class]);
        $lw->filterTable('bos_alan', 'herhangi')->assertCanSeeTableRecords([$onlemsiz, $varsayilan, $kopya])->assertCanNotSeeTableRecords([$dolu]);
        $lw->filterTable('bos_alan', 'mevcut_onlem')->assertCanSeeTableRecords([$onlemsiz])->assertCanNotSeeTableRecords([$dolu, $varsayilan, $kopya]);
        $lw->filterTable('bos_alan', 'sorumlu')->assertCanSeeTableRecords([$varsayilan])->assertCanNotSeeTableRecords([$dolu, $onlemsiz, $kopya]);
        $lw->filterTable('bos_alan', 'risk')->assertCanSeeTableRecords([$kopya])->assertCanNotSeeTableRecords([$dolu, $onlemsiz, $varsayilan]);
    }

    public function test_kutuphanede_bos_alan_suzgeci(): void
    {
        $tam = ['risk' => 'Yaralanma', 'mevcut_onlem' => 'Var', 'oneri' => 'Öneri', 'mevzuat' => 'Yönetmelik', 'sorumlu' => 'Ustabaşı',
            'olasilik' => 3, 'frekans' => 6, 'siddet' => 7, 'son_olasilik' => 1, 'son_frekans' => 6, 'son_siddet' => 7];
        $dolu = $this->kutuphane($tam);
        $mevzuatsiz = $this->kutuphane(['mevzuat' => null] + $tam);
        $onlemsiz = $this->kutuphane(['mevcut_onlem' => ''] + $tam);

        Livewire::test(\App\Filament\Resources\Tehlikes\Pages\ListTehlikes::class)
            ->filterTable('bos_alan', 'herhangi')->assertCanSeeTableRecords([$mevzuatsiz, $onlemsiz])->assertCanNotSeeTableRecords([$dolu])
            ->filterTable('bos_alan', 'mevzuat')->assertCanSeeTableRecords([$mevzuatsiz])->assertCanNotSeeTableRecords([$dolu, $onlemsiz])
            ->filterTable('bos_alan', 'mevcut_onlem')->assertCanSeeTableRecords([$onlemsiz])->assertCanNotSeeTableRecords([$dolu, $mevzuatsiz]);
    }
}
