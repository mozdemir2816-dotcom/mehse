<?php

namespace Tests\Feature;

use App\Filament\Pages\PeriyodikKontrol as PeriyodikKontrolSayfasi;
use App\Filament\Resources\RiskDegerlendirmesis\Pages\EditRiskDegerlendirmesi;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\RiskDegerlendirmesi;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\BildirimTarayici;
use App\Support\EkipmanKatalogu;
use Database\Seeders\MakineRiskKutuphanesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ekipman Kataloğu (13.10.2026): firmaya ekipman eklenince risk değerlendirmesi,
 * kontrol formu ve periyodik muayene uyarısı.
 */
class EkipmanKatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MakineRiskKutuphanesiSeeder::class);
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    private function ekipman(string $kategori, string $tip, array $ek = []): IsEkipmani
    {
        return IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => $kategori, 'tip' => $tip, 'ekipman_adi' => $tip, 'aktif' => true] + $ek);
    }

    private function torna(): IsEkipmani
    {
        return $this->ekipman('uretim_atolye', 'Torna Tezgâhı');
    }

    private function degerlendirme(string $yontem = 'fine_kinney'): RiskDegerlendirmesi
    {
        return RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => $yontem, 'rapor_tarihi' => now(), 'revizyon_no' => '00']);
    }

    private function kategoriMaddeleri(string $anahtar)
    {
        return TehlikeKategorisi::where('anahtar', $anahtar)->sole()->tehlikeler()->orderBy('kod')->get();
    }

    public function test_katalog_tutarli_tip_form_ve_risk_kategorileri_var(): void
    {
        $tipler = collect(config('isg.periyodik_kontrol.tipler'))->flatten(1)->pluck('ad');
        $formlar = collect(config('isg.haftalik_ekipman_kontrol.tipler'))->pluck('ad');
        $riskKategorileri = array_keys(MakineRiskKutuphanesiSeeder::KATEGORILER);

        foreach (config('ekipman_katalogu.tipler') as $tip => $tanim) {
            $this->assertContains($tip, $tipler, "Periyodik kontrol tiplerinde yok: $tip");
            $this->assertContains($tanim['kontrol_formu'], $formlar, "Kontrol formu yok: {$tanim['kontrol_formu']}");
            foreach ($tanim['risk_kategorileri'] as $k) {
                $this->assertContains($k, $riskKategorileri, "Risk kategorisi yok: $k");
            }
        }
        foreach (collect(config('ekipman_katalogu.kategori_ortak_riskleri'))->flatten() as $k) {
            $this->assertContains($k, $riskKategorileri);
        }
        foreach (collect(config('isg.haftalik_ekipman_kontrol.tipler'))->where('taslak', true) as $form) {
            $this->assertCount(12, $form['sorular'], $form['ad']);
        }
    }

    public function test_torna_eklenince_kendi_52_maddesi_eksik_gorunur(): void
    {
        $this->torna();
        $this->degerlendirme();

        $eksik = EkipmanKatalogu::riskEksikleri($this->firma);

        // TFD-RA-01 torna: 40 risk + 6 eğitim + 6 acil durum; ortak atölye maddelerini tetiklemez.
        $this->assertSame(['Torna Tezgâhı'], array_keys($eksik['gruplar']));
        $this->assertSame(52, $eksik['toplam']);
        $this->assertTrue($eksik['gruplar']['Torna Tezgâhı']->every(fn (Tehlike $t) => str_starts_with($t->kod, 'TFD-')));
    }

    public function test_dikey_freze_ve_trafo_kendi_maddeleriyle_gelir(): void
    {
        $this->ekipman('uretim_atolye', 'Dikey Freze Tezgâhı');
        $this->ekipman('elektrik_topraklama', 'Trafo & Yüksek Gerilim İşletmesi');
        $this->degerlendirme();

        $eksik = EkipmanKatalogu::riskEksikleri($this->firma);

        $this->assertEqualsCanonicalizing(['Dikey Freze Tezgâhı', 'Trafo & Yüksek Gerilim İşletmesi'], array_keys($eksik['gruplar']));
        $this->assertSame(52 + 52, $eksik['toplam']);
        // Trafo periyodik muayeneye tabi: tarih girilmeyince uyarı listesinde.
        $this->assertSame(['Trafo & Yüksek Gerilim İşletmesi'], EkipmanKatalogu::muayeneTarihiEksik($this->firma)->pluck('tip')->all());
    }

    public function test_ortak_atolye_maddeleri_yalniz_kendi_dosyasi_olmayan_makinede_gelir(): void
    {
        $this->ekipman('uretim_atolye', 'CNC Tezgâhı');
        $this->degerlendirme();

        $yalnizCnc = EkipmanKatalogu::riskEksikleri($this->firma);
        $this->assertSame(['CNC Tezgâhı'], array_keys($yalnizCnc['gruplar']));
        $this->assertSame(80, $yalnizCnc['toplam']);

        // Sütunlu matkap (RA-MAK, eğitim/elektrik maddesi yok) eklenince ortak maddeler gelir.
        $this->ekipman('uretim_atolye', 'Sütunlu Matkap');
        $ikisi = EkipmanKatalogu::riskEksikleri($this->firma);
        $this->assertSame(['CNC Tezgâhı', 'Sütunlu Matkap', EkipmanKatalogu::ORTAK_ETIKET], array_keys($ikisi['gruplar']));
        $this->assertSame(80 + 14 + 22, $ikisi['toplam']);
    }

    public function test_degerlendirmede_olan_madde_id_veya_benzer_metinle_tekrar_onerilmez(): void
    {
        $this->torna();
        $rd = $this->degerlendirme();
        [$ilk, $ikinci] = $this->kategoriMaddeleri('atolye_torna_tezgahi')->take(2)->all();

        // Kütüphaneden gelmiş, sonra metni değiştirilmiş madde (tehlike_id ile tanınır).
        $rd->maddeler()->create(['tehlike_id' => $ilk->id, 'tehlike' => $ilk->tehlike.' (tezgâh 2)']);
        // Elle yazılmış, yazımı farklı madde (metin benzerliğiyle tanınır).
        $rd->maddeler()->create(['tehlike' => mb_strtoupper(rtrim($ikinci->tehlike, '.')).'.']);
        // Alakasız madde hiçbir şeyi kapsamaz.
        $rd->maddeler()->create(['tehlike' => 'Ofiste ekranlı araçla uzun süre çalışma']);

        $torna = EkipmanKatalogu::riskEksikleri($this->firma)['gruplar']['Torna Tezgâhı'];

        $this->assertCount(50, $torna);
        $this->assertNotContains($ilk->id, $torna->pluck('id'));
        $this->assertNotContains($ikinci->id, $torna->pluck('id'));
    }

    public function test_revize_secilenleri_ekler_gerekmeyenler_bir_daha_gelmez(): void
    {
        $this->torna();
        $rd = $this->degerlendirme();
        $listelenen = collect(EkipmanKatalogu::riskEksikleri($this->firma)['gruplar'])->flatten()->pluck('id');
        $secilen = $listelenen->take(5)->all();

        $eklenen = EkipmanKatalogu::revizeEt($rd, $secilen, $listelenen->skip(5)->values()->all(), ['Torna Tezgâhı']);

        $this->assertSame(5, $eklenen);
        $rd->refresh();
        $this->assertSame('01', $rd->revizyon_no);
        $this->assertStringContainsString('Yeni ekipman: Torna Tezgâhı', $rd->revizyon_nedeni);
        $this->assertSame(5, $rd->maddeler()->whereNotNull('tehlike_id')->count());
        $this->assertSame(0, EkipmanKatalogu::riskEksikleri($this->firma)['toplam']);

        // Fine-Kinney puanları kütüphaneden aynen gelir.
        $m = $rd->maddeler()->where('tehlike_id', $secilen[0])->sole();
        $t = Tehlike::find($secilen[0]);
        $this->assertSame([(float) $t->olasilik, (float) $t->frekans, (float) $t->siddet], [$m->olasilik, $m->frekans, $m->siddet]);
        $this->assertNotNull($m->puan);
    }

    public function test_matris_degerlendirmesine_puan_donusturulerek_eklenir(): void
    {
        $this->torna();
        $rd = $this->degerlendirme('matris_5x5');
        $t = Tehlike::where('kod', 'TFD-001')->sole(); // P=6, S=40

        EkipmanKatalogu::revizeEt($rd, [$t->id], [], ['Torna Tezgâhı']);

        $m = $rd->maddeler()->sole();
        $this->assertSame([4.0, null, 5.0], [$m->olasilik, $m->frekans, $m->siddet]);
    }

    public function test_revize_et_aksiyonu_sayfadan_calisir(): void
    {
        $this->torna();
        $rd = $this->degerlendirme();
        $torna = $this->kategoriMaddeleri('atolye_torna_tezgahi');

        Livewire::test(EditRiskDegerlendirmesi::class, ['record' => $rd->getRouteKey()])
            ->assertActionVisible('ekipmanEksikleri')
            ->callAction('ekipmanEksikleri', ['grup_0' => $torna->take(3)->pluck('id')->map(fn ($id) => (string) $id)->all()])
            ->assertHasNoActionErrors();

        $rd->refresh();
        $this->assertSame(3, $rd->maddeler()->count());
        $this->assertCount(52 - 3, $rd->haric_tehlikeler);
        $this->assertSame(0, EkipmanKatalogu::riskEksikleri($this->firma)['toplam']);
    }

    public function test_atolye_makinesi_periyodik_muayeneden_muaf(): void
    {
        $cnc = $this->ekipman('uretim_atolye', 'CNC Tezgâhı', ['son_muayene_tarihi' => now()->subYears(2)]);
        $this->ekipman('kaldirma_iletme', 'Forklift / Akülü & Dizel İstifleyici');

        $this->assertSame('muaf', $cnc->vizeDurumu());
        $this->assertNull($cnc->fresh()->sonraki_vize_tarihi);
        $this->assertSame(['Forklift / Akülü & Dizel İstifleyici'], EkipmanKatalogu::muayeneTarihiEksik($this->firma)->pluck('tip')->all());
    }

    public function test_kontrol_formu_firmanin_ekipmanlarindan_secilir(): void
    {
        $this->torna();
        $this->ekipman('uretim_atolye', 'CNC Tezgâhı');
        $this->ekipman('kaldirma_iletme', 'Forklift / Akülü & Dizel İstifleyici');

        $adlar = collect(EkipmanKatalogu::kontrolFormuIndeksleri($this->firma))
            ->map(fn ($i) => config('isg.haftalik_ekipman_kontrol.tipler')[$i]['ad'])->sort()->values()->all();

        $this->assertSame(['CNC Tezgâhı', 'Forklift', 'Torna Tezgâhı'], $adlar);

        Livewire::test(PeriyodikKontrolSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->assertActionVisible('kontrolFormu')
            ->callAction('kontrolFormu')
            ->assertFileDownloaded();
    }

    public function test_bildirimler_eksik_risk_ve_muayene_tarihi_icin_uyarir(): void
    {
        $this->torna();
        $this->ekipman('kaldirma_iletme', 'Forklift / Akülü & Dizel İstifleyici');
        $this->degerlendirme();

        $anahtarlar = BildirimTarayici::firmaBildirimleri($this->firma)->pluck('anahtar');

        $this->assertContains('f'.$this->firma->id.':ekipman_risk', $anahtarlar);
        $this->assertContains('f'.$this->firma->id.':ekipman_muayene', $anahtarlar);
    }

    public function test_sayfadan_cnc_eklenir_periyot_istenmez(): void
    {
        Livewire::test(PeriyodikKontrolSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('yeniEkipman', ['kategori' => 'uretim_atolye', 'tip' => 'CNC Tezgâhı', 'ekipman_adi' => 'CNC-1'])
            ->assertHasNoActionErrors();

        $cnc = IsEkipmani::where('firma_id', $this->firma->id)->sole();
        $this->assertSame('muaf', $cnc->vizeDurumu());
        $this->assertSame('Üretim & Atölye Makineleri', $cnc->kategori_adi);
    }
}
