<?php

namespace Tests\Feature;

use App\Filament\Pages\OnayliDefterNushalari;
use App\Models\Firma;
use App\Models\OnayliDefterNushasi;
use App\Models\TespitOneriDefteri;
use App\Models\User;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OnayliDefterNushalariTest extends TestCase
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

    public function test_nusha_yuklenir_ve_numara_otomatik_artar(): void
    {
        $sayfa = Livewire::test(OnayliDefterNushalari::class)->set('firmaId', $this->firma->id);

        $sayfa->callAction('nushaYukle', [
            'defter_turu' => 'tespit_oneri',
            'onay_tarihi' => '2026-03-10',
            'dosya' => File::create('nusha-1.pdf', 120, 'application/pdf'),
        ])->assertHasNoActionErrors();

        $sayfa->callAction('nushaYukle', [
            'defter_turu' => 'tespit_oneri',
            'dosya' => File::create('nusha-2.pdf', 90, 'application/pdf'),
        ])->assertHasNoActionErrors();

        $nushalar = OnayliDefterNushasi::where('firma_id', $this->firma->id)->orderBy('nusha_no')->get();
        $this->assertSame([1, 2], $nushalar->pluck('nusha_no')->all());
        $this->assertSame('2026-03-10', $nushalar[0]->onay_tarihi->format('Y-m-d'));
        Storage::disk('public')->assertExists($nushalar[0]->dosya_yolu);
    }

    public function test_farkli_defter_turu_kendi_numara_dizisini_tutar(): void
    {
        OnayliDefterNushasi::create(['firma_id' => $this->firma->id, 'defter_turu' => 'tespit_oneri']);
        OnayliDefterNushasi::create(['firma_id' => $this->firma->id, 'defter_turu' => 'tespit_oneri']);
        $hekim = OnayliDefterNushasi::create(['firma_id' => $this->firma->id, 'defter_turu' => 'isyeri_hekimi']);

        $this->assertSame(1, $hekim->nusha_no);
        $this->assertSame(3, OnayliDefterNushasi::sonrakiNo($this->firma->id, 'tespit_oneri'));
    }

    public function test_kontrol_merkezi_kriteri_nusha_varsa_karsilanir(): void
    {
        $kriterKarsilar = fn () => PortfoyKarne::firmaKriterKarsilarMi(
            $this->firma->fresh(),
            'onayli_defter_nushalari',
        );

        $this->assertFalse($kriterKarsilar());

        OnayliDefterNushasi::create(['firma_id' => $this->firma->id, 'defter_turu' => 'tespit_oneri']);

        $this->assertTrue($kriterKarsilar());
    }

    public function test_tespit_oneri_defteri_indir_aksiyonu_defter_varsa_gorunur(): void
    {
        $sayfa = Livewire::test(OnayliDefterNushalari::class)->set('firmaId', $this->firma->id);
        $sayfa->assertActionHidden('tespitOneriPdf');

        TespitOneriDefteri::create([
            'firma_id' => $this->firma->id,
            'maddeler' => [['tespit' => 'Örnek tespit', 'oneri' => 'Örnek öneri', 'oncelik' => 'orta']],
        ]);

        Livewire::test(OnayliDefterNushalari::class)
            ->set('firmaId', $this->firma->id)
            ->assertActionVisible('tespitOneriPdf');
    }

    public function test_nusha_silinir_dosya_da_kaldirilir(): void
    {
        $yol = 'onayli-defter/'.$this->firma->id.'/n.pdf';
        Storage::disk('public')->put($yol, 'x');
        $nusha = OnayliDefterNushasi::create([
            'firma_id' => $this->firma->id, 'defter_turu' => 'tespit_oneri', 'dosya_yolu' => $yol, 'dosya_adi' => 'n.pdf',
        ]);

        Livewire::test(OnayliDefterNushalari::class)
            ->set('firmaId', $this->firma->id)
            ->call('nushaSil', $nusha->id);

        $this->assertDatabaseMissing('onayli_defter_nushalari', ['id' => $nusha->id]);
        Storage::disk('public')->assertMissing($yol);
    }

    public function test_baska_uzmanin_firmasi_listede_yok(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(OnayliDefterNushalari::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
