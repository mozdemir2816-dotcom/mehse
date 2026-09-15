<?php

namespace Tests\Feature;

use App\Filament\Pages\ToolboxKonusmalari;
use App\Models\ToolboxKonusmasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ToolboxKonusmalariTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Storage::fake('public');
    }

    public function test_konusma_yuklenir_ve_listelenir(): void
    {
        Livewire::test(ToolboxKonusmalari::class)
            ->callAction('konusmaYukle', data: [
                'baslik' => 'Yüksekte Çalışma Toolbox',
                'dosya' => UploadedFile::fake()->create('yukseklik.pdf', 10, 'application/pdf'),
            ]);

        $this->assertDatabaseHas('toolbox_konusmalari', ['baslik' => 'Yüksekte Çalışma Toolbox']);
    }

    public function test_baska_kullanicinin_konusmasini_goremez(): void
    {
        $baskaKullanici = User::factory()->create();
        ToolboxKonusmasi::create([
            'user_id' => $baskaKullanici->id,
            'baslik' => 'Başkasının Konuşması',
            'dosya_adi' => 'x.pdf',
            'dosya_yolu' => 'toolbox-konusmalari/x.pdf',
        ]);

        $konusmalar = Livewire::test(ToolboxKonusmalari::class)->instance()->konusmalar();

        $this->assertCount(0, $konusmalar);
    }

    public function test_konusma_silinir(): void
    {
        $konusma = ToolboxKonusmasi::create([
            'user_id' => auth()->id(),
            'baslik' => 'Silinecek',
            'dosya_adi' => 'x.pdf',
            'dosya_yolu' => 'toolbox-konusmalari/x.pdf',
        ]);
        Storage::disk('public')->put($konusma->dosya_yolu, 'içerik');

        Livewire::test(ToolboxKonusmalari::class)->call('konusmaSil', $konusma->id);

        $this->assertDatabaseMissing('toolbox_konusmalari', ['id' => $konusma->id]);
        Storage::disk('public')->assertMissing($konusma->dosya_yolu);
    }

    public function test_seed_komutu_hazir_kutuphaneyi_tum_kullanicilara_ekler(): void
    {
        $digerKullanici = User::factory()->create();

        $this->artisan('toolbox:seed-ornekler')->assertExitCode(0);

        $ornekSayisi = count(config('isg.toolbox_ornekleri'));
        $this->assertGreaterThan(15, $ornekSayisi);

        $this->assertSame($ornekSayisi, ToolboxKonusmasi::where('user_id', auth()->id())->count());
        $this->assertSame($ornekSayisi, ToolboxKonusmasi::where('user_id', $digerKullanici->id)->count());

        $ilkOrnek = config('isg.toolbox_ornekleri.0');
        $this->assertDatabaseHas('toolbox_konusmalari', [
            'user_id' => auth()->id(),
            'baslik' => $ilkOrnek['baslik'],
            'dosya_yolu' => 'toolbox-konusmalari/'.$ilkOrnek['dosya_adi'],
        ]);
    }

    public function test_seed_komutu_tekrar_calisinca_kopya_olusturmaz(): void
    {
        $this->artisan('toolbox:seed-ornekler');
        $ilkSayi = ToolboxKonusmasi::count();

        $this->artisan('toolbox:seed-ornekler');

        $this->assertSame($ilkSayi, ToolboxKonusmasi::count());
    }

    public function test_seed_edilen_konusmalarin_gercek_dosyalari_diskte_mevcut(): void
    {
        foreach (config('isg.toolbox_ornekleri') as $o) {
            $yol = base_path('storage/app/public/toolbox-konusmalari/'.$o['dosya_adi']);
            $this->assertFileExists($yol, "Dosya bulunamadı: {$o['dosya_adi']}");
        }
    }
}
