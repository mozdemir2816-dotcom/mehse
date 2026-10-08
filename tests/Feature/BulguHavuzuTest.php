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
}
