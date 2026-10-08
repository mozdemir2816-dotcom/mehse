<?php

namespace Tests\Feature;

use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\IseDonusBelgesi;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\IseDonusBelgesi as IseDonusBelgesiModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Çıktı almadan kaydetme (Concerns\KaydetSecenegi, kullanıcı 08.10.2026):
 * "Kaydet" aynı belgeyi günceller, çıktı alınınca belge tamamlanır ve sonraki
 * kayıt yeni belge olur.
 */
class KaydetSecenegiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    private function dofSayfasi()
    {
        return Livewire::test(DofOlustur::class)
            ->set('firmaId', $this->firma->id)
            ->set('maddeler', [['tespit' => 'Korkuluk yok', 'oncelik' => 'yuksek', 'oneri' => 'Korkuluk', 'sorumlu' => 'İşveren', 'termin' => null, 'durum' => 'acik', 'foto_yolu' => null]]);
    }

    public function test_kaydet_ayni_belgeyi_gunceller_cikti_sonrasi_yeni_belge_acilir(): void
    {
        $sayfa = $this->dofSayfasi()
            ->set('alanBolge', 'Depo')
            ->callAction('sadeceKaydet')
            ->assertHasNoActionErrors();

        $this->assertSame(1, DofRaporu::count());
        $ilk = DofRaporu::sole();
        $sayfa->assertSet('kayitNo', $ilk->belge_no);

        // Tekrar kaydet: aynı belge güncellenir
        $sayfa->set('alanBolge', 'Üretim')->callAction('sadeceKaydet');
        $this->assertSame(1, DofRaporu::count());
        $this->assertSame('Üretim', $ilk->fresh()->alan_bolge);

        // Çıktı al: aynı belge
        $sayfa->set('alanBolge', 'Bakım')->callAction('pdf', ['imzali' => '0']);
        $this->assertSame(1, DofRaporu::count());
        $this->assertSame('Bakım', $ilk->fresh()->alan_bolge);

        // Form değişmeden ikinci çıktı / kayıt: aynı belge (maddelere eklenen bulgu_id değişiklik sayılmaz)
        $sayfa->callAction('pdf', ['imzali' => '0'])->callAction('sadeceKaydet');
        $this->assertSame(1, DofRaporu::count());

        // Çıktıdan sonra form değişti → yeni belge (öncekinin üzerine yazılmaz)
        $sayfa->callAction('pdf', ['imzali' => '0'])->set('alanBolge', 'Başka alan')->callAction('sadeceKaydet');
        $this->assertSame(2, DofRaporu::count());
        $this->assertSame('Bakım', $ilk->fresh()->alan_bolge);
    }

    public function test_yeni_kayit_ve_firma_degisimi_hatirlanan_belgeyi_birakir(): void
    {
        $sayfa = $this->dofSayfasi()->callAction('sadeceKaydet');
        $sayfa->assertActionVisible('yeniKayit')->callAction('yeniKayit')->assertSet('kayitNo', null);
        $sayfa->callAction('sadeceKaydet');
        $this->assertSame(2, DofRaporu::count());

        $baska = Firma::factory()->for($this->uzman)->create();
        $sayfa->set('firmaId', $baska->id)->assertSet('kayitNo', null)->assertSet('kayitlar', []);
    }

    public function test_baska_firmanin_kaydi_hatirlanmis_olsa_da_uzerine_yazilmaz(): void
    {
        $yabanci = DofRaporu::create(['firma_id' => Firma::factory()->for(User::factory())->create()->id, 'alan_bolge' => 'Yabancı', 'maddeler' => []]);

        $this->dofSayfasi()
            ->set('kayitlar', [DofRaporu::class => $yabanci->id])
            ->set('alanBolge', 'Benim')
            ->callAction('sadeceKaydet');

        $this->assertSame('Yabancı', $yabanci->fresh()->alan_bolge);
        $this->assertSame(1, DofRaporu::where('firma_id', $this->firma->id)->count());
    }

    public function test_create_kullanan_sayfada_da_tek_kayit(): void
    {
        $sayfa = Livewire::test(IseDonusBelgesi::class)
            ->set('firmaId', $this->firma->id)
            ->set('calisanAdSoyad', 'Ali Veli')
            ->callAction('sadeceKaydet')
            ->set('calisanAdSoyad', 'Ali Veli Yılmaz')
            ->callAction('sadeceKaydet');

        $this->assertSame(1, IseDonusBelgesiModel::count());
        $this->assertSame('Ali Veli Yılmaz', IseDonusBelgesiModel::sole()->calisan_ad_soyad);
        $sayfa->assertSee('Kaydet ('.IseDonusBelgesiModel::sole()->belge_no.')');
    }
}
