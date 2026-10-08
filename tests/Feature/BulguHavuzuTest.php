<?php

namespace Tests\Feature;

use App\Filament\Pages\AiSahaAnalizi;
use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\HizliSahaBulgusu;
use App\Filament\Pages\TespitOneriDefteri as TespitOneriSayfasi;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\SahaAnalizi;
use App\Models\SahaBulgusu;
use App\Models\TespitOneriDefteri;
use App\Models\User;
use App\Support\BulguHavuzu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Saha kontrolleri 3. aşama: raporların maddeleri ortak saha bulgularına
 * bağlanır, durum iki yönlü eşitlenir, raporlara açık bulgular eklenebilir.
 */
class BulguHavuzuTest extends TestCase
{
    use RefreshDatabase;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $uzman = User::factory()->create();
        $this->actingAs($uzman);
        $this->firma = Firma::factory()->for($uzman)->create();
    }

    public function test_dof_kaydinda_maddeler_bulguya_baglanir_tekrar_kayitta_kopya_acilmaz(): void
    {
        $dof = DofRaporu::create(['firma_id' => $this->firma->id, 'maddeler' => [
            ['tespit' => 'Korkuluk yok', 'oncelik' => 'kritik', 'oneri' => 'Korkuluk kurulmalı', 'termin' => '2026-10-15', 'durum' => 'acik'],
            ['tespit' => 'Etiket eksik', 'oncelik' => 'dusuk', 'durum' => 'tamamlandi'],
        ]]);

        $this->assertSame(2, SahaBulgusu::count());
        $maddeler = $dof->fresh()->maddeler;
        $b = SahaBulgusu::find($maddeler[0]['bulgu_id']);
        $this->assertSame('Korkuluk yok', $b->uygunsuzluk);
        $this->assertSame('kritik', $b->oncelikAnahtari());
        $this->assertSame('dof_raporlari', $b->kaynak_tablo);
        $this->assertSame($dof->id, $b->kaynak_kayit_id);
        $this->assertSame('kapandi', SahaBulgusu::find($maddeler[1]['bulgu_id'])->durum);

        $dof->fresh()->update(['alan_bolge' => 'Depo']);   // tekrar kayıt
        $this->assertSame(2, SahaBulgusu::count());
    }

    public function test_dof_maddesi_tamamlaninca_bulgu_kapanir(): void
    {
        $dof = DofRaporu::create(['firma_id' => $this->firma->id, 'maddeler' => [['tespit' => 'Kablo açıkta', 'durum' => 'acik']]]);
        $maddeler = $dof->fresh()->maddeler;
        $maddeler[0]['durum'] = 'tamamlandi';
        $maddeler[0]['kapatma_tarihi'] = '2026-10-08';
        $maddeler[0]['kapatma_notu'] = 'Kanala alındı';
        $dof->fresh()->update(['maddeler' => $maddeler]);

        $b = SahaBulgusu::sole();
        $this->assertSame('kapandi', $b->durum);
        $this->assertSame('2026-10-08', $b->kapanis_tarihi->toDateString());
        $this->assertSame('Kanala alındı', $b->kapanis_notu);
    }

    public function test_bulgu_kapaninca_bagli_dof_maddesi_tamamlanir(): void
    {
        $dof = DofRaporu::create(['firma_id' => $this->firma->id, 'maddeler' => [['tespit' => 'Kablo açıkta', 'durum' => 'acik']]]);
        $b = SahaBulgusu::sole();

        Livewire::test(HizliSahaBulgusu::class)
            ->callAction('kapat', ['kapanis_tarihi' => '2026-10-09', 'kapanis_notu' => 'Kablo kanala alındı'], ['id' => $b->id])
            ->assertHasNoActionErrors();

        $m = $dof->fresh()->maddeler[0];
        $this->assertSame('tamamlandi', $m['durum']);
        $this->assertSame('2026-10-09', $m['kapatma_tarihi']);
        $this->assertSame('Kablo kanala alındı', $m['kapatma_notu']);

        Livewire::test(HizliSahaBulgusu::class)->call('yenidenAc', $b->id);
        $this->assertSame('acik', $dof->fresh()->maddeler[0]['durum']);
    }

    public function test_tespit_oneri_maddesi_bulgu_olur(): void
    {
        $d = TespitOneriDefteri::firmaIcin($this->firma);
        $d->update(['maddeler' => [['tespit' => 'Yangın tüpü dolumu geçmiş', 'oneri' => 'Dolum yaptırılmalı', 'dayanak' => 'BYKHY', 'oncelik' => 'orta']]]);

        $b = SahaBulgusu::sole();
        $this->assertSame('BYKHY', $b->yasal_gerekce);
        $this->assertSame($b->id, $d->fresh()->maddeler[0]['bulgu_id']);
    }

    public function test_gozlem_raporu_taslakken_degil_tamamlaninca_baglanir_dof_ayni_bulguyu_kullanir(): void
    {
        $s = SahaAnalizi::create(['firma_id' => $this->firma->id, 'durum' => SahaAnalizi::TASLAK, 'bulgular' => [
            ['tespit' => 'İskele korkuluğu eksik', 'oneriler' => ['Korkuluk tamamlanmalı'], 'risk_derecesi' => 1, 'durum' => 'onaylandi', 'dof_acilacak' => true],
        ]]);
        $this->assertSame(0, SahaBulgusu::count());

        $s->update(['durum' => SahaAnalizi::TAMAMLANDI]);
        $b = SahaBulgusu::sole();
        $this->assertSame('kritik', $b->oncelikAnahtari());
        $this->assertSame('Korkuluk tamamlanmalı', $b->aksiyon);

        // Gözlemden açılan DÖF aynı bulguya bağlıysa yeni bulgu açılmaz
        DofRaporu::create(['firma_id' => $this->firma->id, 'maddeler' => [['bulgu_id' => $b->id, 'tespit' => '[SAHA] İskele korkuluğu eksik', 'durum' => 'acik']]]);
        $this->assertSame(1, SahaBulgusu::count());
    }

    public function test_baska_firmanin_bulgusuna_baglanilmaz(): void
    {
        $yabanci = SahaBulgusu::create(['firma_id' => Firma::factory()->for(User::factory())->create()->id, 'uygunsuzluk' => 'Yabancı']);

        $dof = DofRaporu::create(['firma_id' => $this->firma->id, 'maddeler' => [['bulgu_id' => $yabanci->id, 'tespit' => 'Kendi bulgum', 'durum' => 'acik']]]);

        $this->assertNotSame($yabanci->id, $dof->fresh()->maddeler[0]['bulgu_id']);
        $this->assertSame('Yabancı', $yabanci->fresh()->uygunsuzluk);
    }

    public function test_dof_sayfasina_saha_bulgularindan_eklenir(): void
    {
        $a = SahaBulgusu::create(['firma_id' => $this->firma->id, 'bolum' => 'Depo', 'uygunsuzluk' => 'Raf sabit değil', 'olasilik' => 3, 'siddet' => 4]);
        $kapali = SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Kapalı', 'durum' => 'kapandi']);

        $this->assertSame([$a->id], array_keys(BulguHavuzu::secenekler($this->firma->id)));

        $sayfa = Livewire::test(DofOlustur::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('bulgulardanEkle', ['idler' => [$a->id]])
            ->assertHasNoActionErrors();

        $m = $sayfa->get('maddeler')[0];
        $this->assertSame($a->id, $m['bulgu_id']);
        $this->assertSame('[Depo] Raf sabit değil', $m['tespit']);
        $this->assertSame('yuksek', $m['oncelik']);

        // Eklenen bulgu tekrar listelenmez; rapor kaydı yeni bulgu açmaz
        $this->assertSame([], BulguHavuzu::secenekler($this->firma->id, $sayfa->get('maddeler')));
        $sayfa->callAction('pdf', ['imzali' => '0']);
        $this->assertSame(2, SahaBulgusu::count());
        $this->assertNotNull($kapali);
    }

    public function test_tespit_oneri_ve_gozlem_sayfalarina_eklenir(): void
    {
        $a = SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Baret kullanılmıyor', 'aksiyon' => 'Baret zorunlu', 'yasal_gerekce' => 'KKD Yön.', 'oncelik' => 'yuksek']);

        Livewire::test(TespitOneriSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('bulgulardanEkle', ['idler' => [$a->id]])
            ->assertHasNoActionErrors();
        $madde = TespitOneriDefteri::where('firma_id', $this->firma->id)->sole()->maddeler[0];
        $this->assertSame($a->id, $madde['bulgu_id']);
        $this->assertSame('KKD Yön.', $madde['dayanak']);

        $gozlem = Livewire::test(AiSahaAnalizi::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('bulgulardanEkle', ['idler' => [$a->id]])
            ->assertHasNoActionErrors();
        $g = $gozlem->get('bulgular')[0];
        $this->assertSame($a->id, $g['bulgu_id']);
        $this->assertSame(2, $g['risk_derecesi']);
        $this->assertSame('onaylandi', $g['durum']);
        $this->assertSame(1, SahaBulgusu::count());
    }

    // ---------- 4. aşama: eski kayıtlar ve tek kaynak ----------

    /** 3. aşamadan önceki kayıt: kancalar çalışmadan yazılmış. */
    private function eskiDof(array $maddeler): DofRaporu
    {
        $d = new DofRaporu(['firma_id' => $this->firma->id, 'maddeler' => $maddeler]);
        $d->saveQuietly();

        return $d;
    }

    public function test_eski_dof_ve_defter_baglanir_gozlem_raporu_belge_olarak_kalir(): void
    {
        $dof = $this->eskiDof([
            ['tespit' => 'Korkuluk yok', 'oncelik' => 'kritik', 'durum' => 'acik', 'termin' => '2026-10-20'],
            ['tespit' => 'Eski kapalı', 'durum' => 'tamamlandi', 'kapatma_tarihi' => '2026-09-01'],
        ]);
        $defter = new TespitOneriDefteri(['firma_id' => $this->firma->id, 'maddeler' => [['tespit' => 'Tüp dolumu', 'oneri' => 'Dolum', 'oncelik' => 'orta']]]);
        $defter->saveQuietly();
        $gozlem = new SahaAnalizi(['firma_id' => $this->firma->id, 'durum' => SahaAnalizi::TAMAMLANDI, 'bulgular' => [['tespit' => 'Eski gözlem maddesi', 'risk_derecesi' => 3]]]);
        $gozlem->saveQuietly();

        $uzmanId = $this->firma->user_id;
        $this->assertSame(3, BulguHavuzu::baglanmamisMaddeSayisi($uzmanId));

        $this->assertSame(3, BulguHavuzu::eskileriBagla($uzmanId));
        $this->assertSame(0, BulguHavuzu::baglanmamisMaddeSayisi($uzmanId));
        $this->assertSame(0, BulguHavuzu::eskileriBagla($uzmanId), 'tekrar çalıştırmak kopya açmaz');

        $kapali = SahaBulgusu::find($dof->fresh()->maddeler[1]['bulgu_id']);
        $this->assertSame('kapandi', $kapali->durum);
        $this->assertSame('2026-09-01', $kapali->kapanis_tarihi->toDateString());
        $this->assertNotNull($defter->fresh()->maddeler[0]['bulgu_id']);
        $this->assertArrayNotHasKey('bulgu_id', $gozlem->fresh()->bulgular[0]);
        $this->assertFalse(SahaBulgusu::where('uygunsuzluk', 'Eski gözlem maddesi')->exists());
    }

    public function test_saha_bulgulari_sayfasindaki_dugme_eski_kayitlari_baglar(): void
    {
        $this->eskiDof([['tespit' => 'Eski DÖF maddesi', 'durum' => 'acik']]);

        Livewire::test(HizliSahaBulgusu::class)
            ->assertActionVisible('eskileriBagla')
            ->callAction('eskileriBagla')
            ->assertHasNoActionErrors()
            ->assertActionHidden('eskileriBagla');

        $this->assertSame('Eski DÖF maddesi', SahaBulgusu::sole()->uygunsuzluk);
    }

    public function test_komut_kuru_calisinca_kayit_acmaz(): void
    {
        $this->eskiDof([['tespit' => 'Eski DÖF maddesi', 'durum' => 'acik']]);

        $this->artisan('bulgu:havuz-esle', ['--kuru' => true])->expectsOutputToContain('1 madde bağlanacak')->assertSuccessful();
        $this->assertSame(0, SahaBulgusu::count());

        $this->artisan('bulgu:havuz-esle')->expectsOutputToContain('1 bulgu açıldı')->assertSuccessful();
        $this->assertSame(1, SahaBulgusu::count());
    }

    public function test_aksiyonlar_bulgu_ve_bagsiz_dof_tek_kez_sayilir(): void
    {
        SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Saha bulgusu', 'termin' => '2026-10-20']);
        DofRaporu::create(['firma_id' => $this->firma->id, 'maddeler' => [['tespit' => 'Bağlı DÖF', 'durum' => 'acik', 'termin' => '2026-10-25']]]);
        $this->eskiDof([['tespit' => 'Bağsız eski DÖF', 'durum' => 'acik', 'termin' => '2026-10-30']]);

        $aksiyonlar = BulguHavuzu::aksiyonlar($this->firma->id);

        $this->assertCount(3, $aksiyonlar);   // bağlı DÖF maddesi yalnız bulgu olarak
        $this->assertSame(['Saha bulgusu', 'Bağlı DÖF', 'Bağsız eski DÖF'], $aksiyonlar->pluck('baslik')->all());
        $this->assertSame(3, \App\Support\IsyeriDurumu::gostergeler($this->firma)['acik_dof']);
    }
}
