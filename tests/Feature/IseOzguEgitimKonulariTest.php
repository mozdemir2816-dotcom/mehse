<?php

namespace Tests\Feature;

use App\Filament\Pages\IseOzguEgitimKonulari;
use App\Filament\Pages\YillikPlan\YillikEgitimPlani;
use App\Models\Firma;
use App\Models\IseOzguEgitimKonusu;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\IseOzguEgitimKutuphanesi;
use App\Support\YillikPlanSablonu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IseOzguEgitimKonulariTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function iseOzgu(Firma $firma): array
    {
        return collect(YillikPlanSablonu::icerik($firma, (int) now()->year)['egitimler'])->where('kategori', 'ise_ozgu')->pluck('konu')->values()->all();
    }

    public function test_nace_ve_is_kalemine_gore_otomatik_secim(): void
    {
        $insaat = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '41.00.01', 'is_kalemleri' => null]);
        $kalemli = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '41.00.01', 'is_kalemleri' => ['hafriyat_kazi', 'kaynak_kesim']]);
        $gida = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '10.11.01', 'is_kalemleri' => null]);
        $ofis = Firma::factory()->for($this->uzman)->create(['nace_kodu' => null, 'is_kalemleri' => null]);

        // İnşaat, iş kalemi yok → VİZYON şablonunun 6 konusu (eski davranış)
        $this->assertSame(['Yüksekte Çalışma', 'Kazı ve zemin işleri', 'Kaldırma ve saha trafiği', 'Elektrik ve özel işler', 'Sahaya özgü acil durum', 'Kalıp, demir ve beton'], $this->iseOzgu($insaat));

        // İş kalemi seçili → yapılan işe özgü konular
        $this->assertSame(['Hafriyat ve Kazı İşleri', 'Kaynak ve Kesim İşleri'], $this->iseOzgu($kalemli));

        // Gıda → NACE grubunun konuları
        $this->assertContains('Kesme / doğrama makineleri', $this->iseOzgu($gida));
        $this->assertNotContains('Yüksekte Çalışma', $this->iseOzgu($gida));

        $this->assertSame([], $this->iseOzgu($ofis));

        // 4. bölüm "Diğer Eğitimler"den önce
        $kategoriler = collect(YillikPlanSablonu::icerik($gida, (int) now()->year)['egitimler'])->pluck('kategori')->unique()->values()->all();
        $this->assertSame(['genel', 'saglik', 'teknik', 'ise_ozgu', 'diger'], $kategoriler);
    }

    public function test_kendi_konusu_kaydedilir_sistem_konusu_duzenlenir_ve_gizlenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '47.11.01', 'is_kalemleri' => null]);

        Livewire::test(IseOzguEgitimKonulari::class)
            ->callAction('yeniKonu', ['ad' => 'Market raf istifi', 'hedef' => 'İstif güvenliği', 'egitici' => 'İGU', 'nace' => '47.11', 'is_kalemleri' => []])
            ->assertHasNoActionErrors()
            ->callAction('konuDuzenle', ['ad' => 'Yüksekte Çalışma (VİZYON)', 'hedef' => 'Yeni hedef', 'egitici' => 'İGU', 'nace' => '41, 42, 43', 'is_kalemleri' => []], ['anahtar' => 'vizyon_20'])
            ->call('kaldir', 'nace_gida_0')
            ->assertSee('Market raf istifi');

        $this->assertContains('Market raf istifi', $this->iseOzgu($firma));

        $tumu = IseOzguEgitimKutuphanesi::tumu($this->uzman->id);
        $this->assertSame('Yüksekte Çalışma (VİZYON)', $tumu['vizyon_20']['ad']);
        $this->assertFalse($tumu->has('nace_gida_0'));
        $this->assertTrue(IseOzguEgitimKutuphanesi::gizlenenler($this->uzman->id)->has('nace_gida_0'));

        // Başka kullanıcıyı etkilemez
        $this->assertSame('Yüksekte Çalışma', IseOzguEgitimKutuphanesi::tumu(User::factory()->create()->id)['vizyon_20']['ad']);

        Livewire::test(IseOzguEgitimKonulari::class)->call('sistemeDondur', 'nace_gida_0')->call('sistemeDondur', 'vizyon_20');
        $this->assertSame('Yüksekte Çalışma', IseOzguEgitimKutuphanesi::tumu($this->uzman->id)['vizyon_20']['ad']);
    }

    public function test_planda_konu_secimi_ve_kutuphaneye_kayit(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '10.11.01', 'is_kalemleri' => null]);

        $lw = Livewire::test(YillikEgitimPlani::class)->set('firmaId', $firma->id)->assertSee('İşe Özgü Konuları Seç');
        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $once = collect($plan->egitimler)->where('kategori', 'ise_ozgu');
        $this->assertNotEmpty($once);

        // Elle eklenen 4. bölüm satırı seçimde korunur
        $lw->set('yeniEgitimKategori', 'ise_ozgu')->set('yeniEgitimKonu', 'Kasap bıçağı kullanımı')->call('egitimEkle');

        $lw->callAction('iseOzguSec', ['secilenler' => ['nace_gida_0', 'vizyon_20']])->assertHasNoActionErrors();
        $konular = collect($plan->fresh()->egitimler)->where('kategori', 'ise_ozgu')->pluck('konu')->all();
        $this->assertSame(['Kesme / doğrama makineleri', 'Kasap bıçağı kullanımı', 'Yüksekte Çalışma'], $konular);

        $index = collect($plan->fresh()->egitimler)->search(fn ($e) => $e['konu'] === 'Kasap bıçağı kullanımı');
        $lw->callAction('kutuphaneyeKaydet', ['ad' => 'Kasap bıçağı kullanımı', 'hedef' => 'Kesik önleme', 'egitici' => 'İGU', 'nace' => '10.11', 'is_kalemleri' => []], ['index' => $index])
            ->assertHasNoActionErrors();

        $k = IseOzguEgitimKonusu::firstOrFail();
        $this->assertSame(',1011,', $k->nace_onekleri);
        $this->assertSame('ozel_'.$k->id, $plan->fresh()->egitimler[$index]['kutuphane_anahtari']);

        // Başka gıda firmasına otomatik önerilir
        $diger = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '10.11.02', 'is_kalemleri' => null]);
        $this->assertContains('Kasap bıçağı kullanımı', $this->iseOzgu($diger));
    }
}
