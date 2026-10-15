<?php

namespace Tests\Feature;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Support\RiskKutuphanesi;
use Database\Seeders\MakineRiskKutuphanesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ekipman risk kütüphanesi (RA-MAK-2026-01 + CNC-RA-01 + TFD-RA-01). */
class MakineRiskKutuphanesiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ekipman_ve_santiye_maddeleri_46_kategoriye_yuklenir_ve_tekrar_calistirinca_cogalmaz(): void
    {
        $this->seed(MakineRiskKutuphanesiSeeder::class);
        $this->seed(MakineRiskKutuphanesiSeeder::class);

        $this->assertSame(56, TehlikeKategorisi::whereIn('anahtar', array_keys(MakineRiskKutuphanesiSeeder::KATEGORILER))->count());
        // MAK: torna → TFD, ekstrüzyon/kırıcı/merdane/bant zımpara/kesme → EKP ile güncellendi.
        $this->assertSame(176 - 20 - 84, Tehlike::where('kod', 'like', 'MAK-%')->count());
        $this->assertSame(80, Tehlike::where('kod', 'like', 'CNC-%')->count());
        $this->assertSame(156, Tehlike::where('kod', 'like', 'TFD-%')->count());
        // EKP-RA-01: 448 maddeden torna/freze/trafo (TFD ile birebir aynı, 156) atlandı;
        // forklift'in 12 maddesi 15.10.2026'da FRK (70 madde) ile değiştirildi.
        $this->assertSame(280, Tehlike::where('kod', 'like', 'EKP-%')->count());
        // FRK (Forklift_Fine_Kinney_70_Madde.xlsx): P-F-S sırası, Şiddet 40→30, 100→40; Sorumlu/Termin dosyadan.
        $forklift = TehlikeKategorisi::where('anahtar', 'ekipman_forklift')->sole()->tehlikeler();
        $this->assertSame(70, (clone $forklift)->count());
        $this->assertSame(70, (clone $forklift)->where('kod', 'like', 'FRK-%')->count());
        $frk = Tehlike::where('kod', 'FRK-E-001')->sole();
        $this->assertSame([3.0, 6.0, 30.0, 0.2], [(float) $frk->olasilik, (float) $frk->frekans, (float) $frk->siddet, (float) $frk->son_olasilik]);
        $this->assertSame('İşveren / İK / Lojistik', $frk->sorumlu);
        $this->assertSame(1, Tehlike::where('kod', 'like', 'FRK-%')->where('faaliyet', 'like', '%(Patlayıcı ortam)')->count());
        // SK-RA-01 (şantiye ve kamp, 14.10.2026): 119 iş + 30 eğitim + 30 acil durum, 15 ayrı kategori.
        $this->assertSame(179, Tehlike::where('kod', 'like', 'SK-%')->count());
        $this->assertSame(12, TehlikeKategorisi::where('anahtar', 'santiye_kazici_yukleyici')->sole()->tehlikeler()->count());
        // MV-RA (mobil vinçle kalıp taşıma, 14.10.2026): 51 iş + 6 eğitim + 1 acil; Şiddet 100→40, 40→30.
        $this->assertSame(58, Tehlike::where('kod', 'like', 'MV-%')->count());
        $this->assertSame(40.0, (float) Tehlike::where('kod', 'MV-R-001')->value('siddet'));
        $this->assertSame(6, Tehlike::where('kod', 'like', 'MV-E-%')->count());
        // YMK (yemekhane, 14.10.2026): 30 tüplü mutfak + 12 odun-kömür sobası, kaynak numaraları 59–100.
        $this->assertSame(30, TehlikeKategorisi::where('anahtar', 'yemekhane_tuplu_mutfak')->sole()->tehlikeler()->count());
        $this->assertSame(12, TehlikeKategorisi::where('anahtar', 'yemekhane_soba')->sole()->tehlikeler()->count());
        // ESY (eğitim, sağlık gözetimi, mesleki yeterlilik) 50 + OFS (ofis çalışmaları) 56, 14.10.2026.
        $this->assertSame(50, TehlikeKategorisi::where('anahtar', 'egitim_saglik_yeterlilik')->sole()->tehlikeler()->count());
        $this->assertSame(56, TehlikeKategorisi::where('anahtar', 'ofis_calismalari')->sole()->tehlikeler()->count());
        $this->assertSame(30.0, (float) Tehlike::where('kod', 'ESY-E-001')->value('siddet'));
        // Sorumlu / Termin kaynak Excel'den (EKP, MV, YMK, ESY, OFS).
        $this->assertSame(['İşveren / bölüm amiri / İK', 'İlgili işe başlamadan'], array_values(Tehlike::where('kod', 'ESY-E-001')->first(['sorumlu', 'termin'])->toArray()));
        $this->assertSame(498 - 12 + 70 + 190, Tehlike::whereNotNull('sorumlu')->count());
        // Kaldırma / platform dosyaları (15.10.2026): her biri ayrı kategori, aynı 26 sütun düzeni.
        foreach (['ekipman_kule_vinc' => 40, 'ekipman_manlift' => 40, 'ekipman_teleskopik_yukleyici' => 40, 'ekipman_kaldirma_ekipmanlari' => 30, 'kaldirma_islemleri' => 40] as $anahtar => $adet) {
            $this->assertSame($adet, TehlikeKategorisi::where('anahtar', $anahtar)->sole()->tehlikeler()->count(), $anahtar);
        }
        $this->assertSame(0, Tehlike::where('kod', 'like', 'KLV-%')->where('siddet', '>', 40)->count()); // 100 → 40
        $this->assertSame(1, Tehlike::where('kod', 'like', 'KLV-%')->where('faaliyet', 'like', '%(Raylı model)')->count());
        $this->assertSame(0, Tehlike::where('kod', 'like', 'MAK-%')->whereHas('kategori', fn ($q) => $q->where('anahtar', 'atolye_ekstruzyon_hatti'))->count());
        $this->assertSame(16, TehlikeKategorisi::where('anahtar', 'atolye_ekstruzyon_hatti')->sole()->tehlikeler()->count());
        $this->assertSame(1, Tehlike::where('kod', 'EKP-E-019')->where('bolum', 'Açık vals (iki silindir)')->count()); // sayfa harfi: numaralar sayfalar arası tekrar ediyor
        foreach (['atolye_cnc_isleme_merkezi' => 80, 'atolye_torna_tezgahi' => 52, 'atolye_dikey_freze' => 52, 'ekipman_trafo' => 52] as $anahtar => $adet) {
            $this->assertSame($adet, TehlikeKategorisi::where('anahtar', $anahtar)->sole()->tehlikeler()->count(), $anahtar);
        }
    }

    public function test_guncellenen_ekipmanda_eski_seed_maddesi_silinir_elle_eklenen_kalir(): void
    {
        $torna = TehlikeKategorisi::create(['anahtar' => 'atolye_torna_tezgahi', 'ad' => 'Atölye – Torna tezgâhı', 'sira' => 1]);
        $torna->tehlikeler()->create(['kod' => 'MAK-063', 'tehlike' => 'Eski dosyadan torna maddesi']);
        $elle = $torna->tehlikeler()->create(['kod' => 'T-1', 'tehlike' => 'Uzmanın elle eklediği madde']);

        $this->seed(MakineRiskKutuphanesiSeeder::class);

        $this->assertNull(Tehlike::where('kod', 'MAK-063')->first());
        $this->assertModelExists($elle);
        $this->assertSame(52 + 1, $torna->tehlikeler()->count());
    }

    public function test_tfd_dayanaklari_kisaltilmadan_aktarilir(): void
    {
        $this->seed(MakineRiskKutuphanesiSeeder::class);

        $trafo = Tehlike::where('kod', 'TFD-081')->sole();

        $this->assertSame('Trafo', $trafo->bolum);
        $this->assertStringContainsString('Ek III elektrik tesisatları ve transformatörler', $trafo->mevzuat);
        $this->assertStringContainsString('Makina Emniyeti kapsamı dışında', $trafo->mevzuat);
        $this->assertGreaterThan(191, mb_strlen($trafo->mevzuat));
    }

    public function test_madde_sihirbaza_tedbir_ve_fine_kinney_puaniyla_gelir(): void
    {
        $this->seed(MakineRiskKutuphanesiSeeder::class);

        $madde = RiskKutuphanesi::maddeyeCevir(Tehlike::where('kod', 'MAK-083')->sole());

        $this->assertSame('Sütunlu matkap', $madde['bolum']);
        $this->assertSame('Dönen mandrene doğrudan erişim', $madde['tehlike']);
        $this->assertStringStartsWith('İşleme uygun mandren ve işlem bölgesi', $madde['oneri']); // DÖF → Önerilen önlem
        $this->assertNull($madde['mevcut_onlem']);
        $this->assertSame([6.0, 6.0, 15.0], [$madde['olasilik'], $madde['frekans'], $madde['siddet']]);
        // Dosyadaki Hedef P/F/S → önlem sonrası (Son) puanlar.
        $this->assertSame([0.2, 6.0, 15.0], [$madde['son_olasilik'], $madde['son_frekans'], $madde['son_siddet']]);
        $this->assertStringContainsString('ISO 12100', $madde['mevzuat']);
    }
}
