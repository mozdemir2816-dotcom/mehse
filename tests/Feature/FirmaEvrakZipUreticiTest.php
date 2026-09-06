<?php

namespace Tests\Feature;

use App\Filament\Resources\Firmas\Pages\ListFirmas;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\FirmaEvrakZipUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;
use ZipArchive;

class FirmaEvrakZipUreticiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_secenekler_yalniz_o_firmanin_evraklarini_listeler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $baskaFirma = Firma::factory()->for($this->uzman)->create();

        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        DofRaporu::create(['firma_id' => $firma->id, 'maddeler' => [['tespit' => 'X', 'oncelik' => 'orta', 'durum' => 'acik']]]);
        RiskDegerlendirmesi::create(['firma_id' => $baskaFirma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $secenekler = FirmaEvrakZipUretici::secenekler($firma);

        $this->assertCount(2, $secenekler);
        $this->assertTrue(collect($secenekler)->contains(fn ($v) => str_contains($v, 'Risk Değerlendirmesi')));
        $this->assertTrue(collect($secenekler)->contains(fn ($v) => str_contains($v, 'DÖF Raporu')));
    }

    public function test_secenek_yoksa_bos_dizi_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $this->assertSame([], FirmaEvrakZipUretici::secenekler($firma));
    }

    public function test_zip_secilen_evraklari_iceren_gecerli_bir_arsiv_uretir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['tehlike' => 'Test', 'olasilik' => 1, 'siddet' => 1]);
        DofRaporu::create(['firma_id' => $firma->id, 'maddeler' => [['tespit' => 'X', 'oncelik' => 'orta', 'durum' => 'acik']]]);

        $secenekler = array_keys(FirmaEvrakZipUretici::secenekler($firma));
        $yanit = FirmaEvrakZipUretici::zip($firma, $secenekler);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);

        ob_start();
        $yanit->sendContent();
        $zipIcerik = ob_get_clean();

        $geciciDosya = tempnam(sys_get_temp_dir(), 'test-zip').'.zip';
        file_put_contents($geciciDosya, $zipIcerik);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($geciciDosya) === true);
        $this->assertSame(2, $zip->numFiles);
        $zip->close();
        unlink($geciciDosya);
    }

    public function test_zip_yalniz_secilenleri_dahil_eder(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        DofRaporu::create(['firma_id' => $firma->id, 'maddeler' => [['tespit' => 'X', 'oncelik' => 'orta', 'durum' => 'acik']]]);

        $secenekler = array_keys(FirmaEvrakZipUretici::secenekler($firma));
        $yanit = FirmaEvrakZipUretici::zip($firma, [$secenekler[0]]); // yalnız 1 tanesi seçili

        ob_start();
        $yanit->sendContent();
        $zipIcerik = ob_get_clean();

        $geciciDosya = tempnam(sys_get_temp_dir(), 'test-zip').'.zip';
        file_put_contents($geciciDosya, $zipIcerik);

        $zip = new ZipArchive;
        $zip->open($geciciDosya);
        $this->assertSame(1, $zip->numFiles);
        $zip->close();
        unlink($geciciDosya);
    }

    public function test_firmalar_listesindeki_evrak_indir_aksiyonu_calisir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        Livewire::test(ListFirmas::class)
            ->callTableAction('evrakIndir', $firma, data: ['secilenler' => array_keys(FirmaEvrakZipUretici::secenekler($firma))])
            ->assertSuccessful();
    }

    public function test_evrak_yoksa_evrak_indir_aksiyonu_gorunmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(ListFirmas::class)
            ->assertTableActionHidden('evrakIndir', $firma);
    }
}
