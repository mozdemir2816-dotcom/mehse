<?php

namespace Tests\Feature;

use App\Filament\Pages\KurulToplantisi as KurulSayfasi;
use App\Models\Firma;
use App\Models\KurulToplantisi;
use App\Models\User;
use App\Support\KurulKatilimciAktarici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** İSG Kurulu: önceki toplantıda katılanları otomatik aktarma (ortak toplantı yapan iki firma). */
class KurulKatilimciAktarimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function toplanti(Firma $firma, string $tarih, string $no, array $katilimcilar): KurulToplantisi
    {
        return $firma->kurulToplantilari()->create(['tarih' => $tarih, 'toplanti_no' => $no, 'katilimcilar' => $katilimcilar, 'gundem' => [], 'kararlar' => []]);
    }

    private function k(string $ad, bool $katildi, ?string $rol = null, ?string $gorev = null): array
    {
        return ['ad_soyad' => $ad, 'gorev' => $gorev, 'rol' => $rol, 'katildi' => $katildi];
    }

    public function test_yalniz_katilanlar_aktarilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $once = $this->toplanti($firma, '2026-01-10', '2026/1', [$this->k('Ali Veli', true, 'baskan', 'İşveren'), $this->k('Ayşe Kaya', false), $this->k('Can Er', true)]);
        $yeni = $this->toplanti($firma, '2026-04-10', '2026/2', [$this->k('Eski Üye', true)]);

        $this->assertSame(2, KurulKatilimciAktarici::aktar($yeni, $once, 'degistir'));
        $liste = $yeni->fresh()->katilimcilar;

        $this->assertSame(['Ali Veli', 'Can Er'], array_column($liste, 'ad_soyad'));
        $this->assertSame('baskan', $liste[0]['rol']);
        $this->assertTrue($liste[0]['katildi']);
    }

    public function test_ekle_modu_mevcutlari_korur_ve_eksik_alanlari_tamamlar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $once = $this->toplanti($firma, '2026-01-10', '2026/1', [$this->k('Ali Veli', true, 'baskan', 'İşveren'), $this->k('Can Er', true)]);
        $yeni = $this->toplanti($firma, '2026-04-10', '2026/2', [$this->k('ALİ VELİ', false), $this->k('Elle Eklenen', true)]);

        $this->assertSame(1, KurulKatilimciAktarici::aktar($yeni, $once, 'ekle'));
        $liste = collect($yeni->fresh()->katilimcilar);

        $this->assertSame(['ALİ VELİ', 'Elle Eklenen', 'Can Er'], $liste->pluck('ad_soyad')->all());
        $ali = $liste->firstWhere('ad_soyad', 'ALİ VELİ');
        $this->assertFalse($ali['katildi'], 'mevcut işaret korunur');
        $this->assertSame('baskan', $ali['rol'], 'eksik rol tamamlanır');
    }

    public function test_ortak_toplanti_icin_diger_firmanin_toplantisi_kaynak_olabilir_baska_kullanicininki_olamaz(): void
    {
        $a = Firma::factory()->for($this->uzman)->create(['unvan' => 'A Firması']);
        $b = Firma::factory()->for($this->uzman)->create(['unvan' => 'B Firması']);
        $bToplanti = $this->toplanti($b, '2026-01-10', '2026/1', [$this->k('Ortak Üye', true)]);
        $yeni = $this->toplanti($a, '2026-04-10', '2026/1', []);

        $secenekler = KurulKatilimciAktarici::kaynakSecenekleri($a, $yeni->id, $this->uzman->id);
        $this->assertArrayHasKey($bToplanti->id, $secenekler);
        $this->assertStringContainsString('[B Firması]', $secenekler[$bToplanti->id]);

        $this->assertSame(1, KurulKatilimciAktarici::aktar($yeni, $bToplanti));
        $this->assertSame(['Ortak Üye'], array_column($yeni->fresh()->katilimcilar, 'ad_soyad'));

        // Başka kullanıcının toplantısı hiçbir yoldan aktarılmaz.
        $yabanci = $this->toplanti(Firma::factory()->for(User::factory())->create(), '2026-01-10', 'Y/1', [$this->k('Yabancı', true)]);
        $this->assertSame(0, KurulKatilimciAktarici::aktar($yeni, $yabanci));
        $this->assertArrayNotHasKey($yabanci->id, KurulKatilimciAktarici::kaynakSecenekleri($a, null, $this->uzman->id));
    }

    public function test_varsayilan_kaynak_firmanin_onceki_katilanli_toplantisidir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $bos = $this->toplanti($firma, '2026-03-01', '2026/2', [$this->k('Katılmadı', false)]);
        $dolu = $this->toplanti($firma, '2026-01-10', '2026/1', [$this->k('Ali', true)]);
        $yeni = $this->toplanti($firma, '2026-04-10', '2026/3', []);

        $this->assertSame($dolu->id, KurulKatilimciAktarici::varsayilanKaynakId($firma, $yeni));
        $this->assertNull(KurulKatilimciAktarici::varsayilanKaynakId(Firma::factory()->for($this->uzman)->create()));
        $this->assertNotNull($bos->id);
    }

    public function test_yeni_toplanti_onceki_katilanlari_otomatik_alir_secenek_kapaliysa_uyeleri(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $firma->kurulUyeleri()->create(['rol' => 'baskan', 'ad_soyad' => 'Güncel Başkan', 'gorev' => 'İşveren']);
        $firma->kurulUyeleri()->create(['rol' => 'isg_uzmani', 'ad_soyad' => 'Güncel Uzman']);
        $once = $this->toplanti($firma, '2026-01-10', '2026/1', [$this->k('Ali Veli', true), $this->k('Gelmeyen', false), $this->k('Can Er', true)]);

        // Varsayılan (açık): önceki toplantıdaki katılanlar.
        Livewire::test(KurulSayfasi::class)->set('firmaId', $firma->id)
            ->callAction('toplantiPlanla', ['tarih' => '2026-04-10', 'onceki_katilanlar' => true, 'katilim_kaynak' => $once->id])
            ->assertHasNoActionErrors();
        $yeni = $firma->kurulToplantilari()->whereDate('tarih', '2026-04-10')->firstOrFail();
        $this->assertSame(['Ali Veli', 'Can Er'], array_column($yeni->katilimcilar, 'ad_soyad'));

        // Seçenek kapalı: güncel kurul üyeleri.
        Livewire::test(KurulSayfasi::class)->set('firmaId', $firma->id)
            ->callAction('toplantiPlanla', ['tarih' => '2026-07-10', 'onceki_katilanlar' => false])
            ->assertHasNoActionErrors();
        $yeni2 = $firma->kurulToplantilari()->whereDate('tarih', '2026-07-10')->firstOrFail();
        $this->assertContains('Güncel Başkan', array_column($yeni2->katilimcilar, 'ad_soyad'));
        $this->assertNotContains('Ali Veli', array_column($yeni2->katilimcilar, 'ad_soyad'));
    }

    public function test_acik_toplantida_eylem_katilimciyi_aktarir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $once = $this->toplanti($firma, '2026-01-10', '2026/1', [$this->k('Ali Veli', true), $this->k('Can Er', true)]);
        $yeni = $this->toplanti($firma, '2026-04-10', '2026/2', [$this->k('Eski', true)]);

        Livewire::test(KurulSayfasi::class)->set('firmaId', $firma->id)->call('toplantiSec', $yeni->id)
            ->callAction('oncekindenAktar', ['kaynak' => $once->id, 'mod' => 'degistir'])
            ->assertHasNoActionErrors();

        $this->assertSame(['Ali Veli', 'Can Er'], array_column($yeni->fresh()->katilimcilar, 'ad_soyad'));
    }
}
