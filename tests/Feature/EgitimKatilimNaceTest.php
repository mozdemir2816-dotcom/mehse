<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimKatilim as EgitimSayfasi;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\User;
use App\Support\EgitimIcerikOlusturucu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Eğitim Katılım: işyerine özgü konular NACE'si aynı başka firmadan gelir. */
class EgitimKatilimNaceTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function kayit(Firma $firma, string $madde): void
    {
        $icerik = EgitimIcerikOlusturucu::olustur('genel', 'enerji_elektrik', 'tehlikeli');
        $icerik['isyerine_ozgu']['maddeler'] = [['madde' => $madde, 'dakika' => 20, 'dahil' => true]];

        EgitimKatilim::create([
            'firma_id' => $firma->id, 'baslik_anahtari' => 'genel', 'egitim_turu' => 'ilk', 'sektor_anahtari' => 'enerji_elektrik',
            'belge_tarihi' => now()->subDay(), 'sure_gun' => 1, 'konu_secimleri' => $icerik, 'katilimcilar' => [],
        ]);
    }

    public function test_ayni_nace_firmasinin_sektoru_ve_ise_ozgu_konulari_gelir(): void
    {
        $metal1 = Firma::factory()->for($this->uzman)->create(['unvan' => 'Metal Bir', 'nace_kodu' => '25.11.01', 'tehlike_sinifi' => 'tehlikeli']);
        $this->kayit($metal1, 'Kaynak dumanı ve havalandırma');

        $metal2 = Firma::factory()->for($this->uzman)->create(['unvan' => 'Metal İki', 'nace_kodu' => '25.11.03', 'tehlike_sinifi' => 'tehlikeli']);

        $c = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $metal2->id)
            ->set('baslikAnahtari', 'genel')
            ->assertSet('sektorAnahtari', 'enerji_elektrik')   // sektör yeniden seçilmez
            ->assertSet('oncekidenYuklendi', true);

        $this->assertSame(['Kaynak dumanı ve havalandırma'], collect($c->get('icerik')['isyerine_ozgu']['maddeler'])->pluck('madde')->all());
        $this->assertStringContainsString('Metal Bir', $c->get('oncekiKaynak'));
    }

    public function test_farkli_nace_ve_baska_kullanici_kaydi_gelmez(): void
    {
        $gida = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '10.11.01']);
        $this->kayit($gida, 'Gıda konusu');
        $baska = Firma::factory()->for(User::factory()->create())->create(['nace_kodu' => '25.11.01']);
        $this->kayit($baska, 'Başkasının konusu');

        $metal = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '25.11.01']);

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $metal->id)
            ->set('baslikAnahtari', 'genel')
            ->assertSet('sektorAnahtari', 'nace_metal')   // NACE grubundan; geçmiş yok
            ->assertSet('oncekidenYuklendi', false);
    }

    public function test_ilk_kayitta_sektor_nace_grubundan_gelir_ve_elle_secenek_var(): void
    {
        $this->assertSame('nace_metal', EgitimIcerikOlusturucu::naceSektoru('25.11.01'));
        $this->assertSame('enerji_elektrik', EgitimIcerikOlusturucu::naceSektoru('35.11.01'));
        $this->assertNull(EgitimIcerikOlusturucu::naceSektoru(null));
        $this->assertGreaterThanOrEqual(20, count(EgitimIcerikOlusturucu::sektorler()));

        $gida = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '10.71.01', 'tehlike_sinifi' => 'tehlikeli']);
        $c = Livewire::test(EgitimSayfasi::class)->set('firmaId', $gida->id)->set('baslikAnahtari', 'genel')
            ->assertSet('sektorAnahtari', 'nace_gida');
        $this->assertContains('Kesme / doğrama makineleri', collect($c->get('icerik')['isyerine_ozgu']['maddeler'])->pluck('madde')->all());

        // Diğer: boş başlar, konular elle eklenir
        $c->set('sektorAnahtari', 'ozel')->call('isyerineOzguMaddeEkle');
        $this->assertSame('Firmaya özgü', $c->get('icerik')['isyerine_ozgu']['sektor']);
        $this->assertCount(1, $c->get('icerik')['isyerine_ozgu']['maddeler']);
    }
}
