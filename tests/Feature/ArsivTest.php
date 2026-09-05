<?php

namespace Tests\Feature;

use App\Filament\Pages\Profilim;
use App\Models\ArsivDosya;
use App\Models\Firma;
use App\Models\User;
use Illuminate\Http\Testing\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArsivTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    public function test_arsiv_yukle_action_dosya_olusturur(): void
    {
        Livewire::test(Profilim::class)
            ->call('arsivFirmaSec', $this->firma->id)
            ->callAction('arsivYukle', [
                'dosya' => File::create('risk-analizi.pdf', 100, 'application/pdf'),
            ])
            ->assertHasNoActionErrors();

        $dosya = ArsivDosya::where('firma_id', $this->firma->id)->firstOrFail();
        $this->assertSame('risk-analizi.pdf', $dosya->dosya_adi);
        Storage::disk('public')->assertExists($dosya->dosya_yolu);
    }

    public function test_baskasinin_firmasi_secilemez_ve_dosya_yuklenemez(): void
    {
        $baskasi = User::factory()->create();
        $baskasininFirmasi = Firma::factory()->for($baskasi)->create();

        $test = Livewire::test(Profilim::class)
            ->call('arsivFirmaSec', $baskasininFirmasi->id);

        // Seçim reddedildi — mevcut seçim (kendi firması) değişmedi.
        $this->assertNotSame($baskasininFirmasi->id, $test->get('arsivSeciliFirmaId'));

        $test->set('arsivSeciliFirmaId', $baskasininFirmasi->id) // saldırgan doğrudan property'yi değiştirmeye çalışırsa
            ->callAction('arsivYukle', [
                'dosya' => File::create('dosya.pdf', 50, 'application/pdf'),
            ]);

        $this->assertDatabaseMissing('arsiv_dosyalari', ['firma_id' => $baskasininFirmasi->id]);

        // Arşiv sekmesindeki firma listesi de yalnız kendi portföyünü döner.
        $firmaIdler = Livewire::test(Profilim::class)->instance()->arsivFirmalar()->pluck('id');
        $this->assertNotContains($baskasininFirmasi->id, $firmaIdler);
    }

    public function test_arsiv_dosya_sil_diskten_ve_kayittan_kaldirir(): void
    {
        $dosya = ArsivDosya::create([
            'firma_id' => $this->firma->id, 'dosya_adi' => 'silinecek.pdf',
            'dosya_yolu' => 'arsiv/'.$this->firma->id.'/silinecek.pdf', 'boyut' => 1024,
        ]);
        Storage::disk('public')->put($dosya->dosya_yolu, 'içerik');

        Livewire::test(Profilim::class)->call('arsivDosyaSil', $dosya->id);

        $this->assertDatabaseMissing('arsiv_dosyalari', ['id' => $dosya->id]);
        Storage::disk('public')->assertMissing($dosya->dosya_yolu);
    }

    public function test_baskasinin_dosyasini_silemez(): void
    {
        $baskasi = User::factory()->create();
        $baskasininFirmasi = Firma::factory()->for($baskasi)->create();
        $dosya = ArsivDosya::create([
            'firma_id' => $baskasininFirmasi->id, 'dosya_adi' => 'yabanci.pdf',
            'dosya_yolu' => 'arsiv/yabanci.pdf', 'boyut' => 512,
        ]);
        Storage::disk('public')->put($dosya->dosya_yolu, 'içerik');

        Livewire::test(Profilim::class)->call('arsivDosyaSil', $dosya->id);

        $this->assertDatabaseHas('arsiv_dosyalari', ['id' => $dosya->id]);
        Storage::disk('public')->assertExists($dosya->dosya_yolu);
    }

    public function test_kullanilan_mb_dogru_toplar(): void
    {
        ArsivDosya::create(['firma_id' => $this->firma->id, 'dosya_adi' => 'a.pdf', 'dosya_yolu' => 'a.pdf', 'boyut' => 1024 * 1024]);
        ArsivDosya::create(['firma_id' => $this->firma->id, 'dosya_adi' => 'b.pdf', 'dosya_yolu' => 'b.pdf', 'boyut' => 512 * 1024]);

        $mb = Livewire::test(Profilim::class)->instance()->arsivKullanilanMb();

        $this->assertSame(1.5, $mb);
    }
}
