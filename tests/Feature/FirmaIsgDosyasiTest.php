<?php

namespace Tests\Feature;

use App\Filament\Pages\FirmaIsgDosyasi as Sayfa;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\FirmaIsgDosyasi;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FirmaIsgDosyasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bos_firmada_eksik_ve_uc_gruptan_fazlasi_listelenir(): void
    {
        $uzman = User::factory()->create();
        $firma = Firma::factory()->for($uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        $this->actingAs($uzman);
        Filament::setCurrentPanel('admin');

        $g = FirmaIsgDosyasi::gruplar($firma);

        $this->assertSame(['risk', 'adep', 'egitim', 'calisan', 'saha'], array_keys($g));
        $this->assertSame('Eksik', $g['risk']['satirlar'][0]['etiket']);
        $this->assertNull(FirmaIsgDosyasi::teftisPaketi($firma));   // pakete girecek evrak yok

        Livewire::test(Sayfa::class, ['firmaId' => $firma->id])->assertSee('Risk Değerlendirmesi')->assertSee('Eksik');
    }

    public function test_risk_gecerlilik_rozeti_ve_baska_firma_pdf_vermez(): void
    {
        $uzman = User::factory()->create();
        $firma = Firma::factory()->for($uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        $baska = Firma::factory()->for(User::factory()->create())->create();
        $this->actingAs($uzman);

        $risk = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()->subYears(5), 'gecerlilik_tarihi' => now()->subYear()]);

        $g = FirmaIsgDosyasi::gruplar($firma);
        $this->assertSame('Yenileme Gerekli', $g['risk']['satirlar'][0]['etiket']);
        $this->assertNull(FirmaIsgDosyasi::pdf($baska, 'risk:'.$risk->id));   // başka firmanın kaydı indirilemez

        // Teftiş Paketi: risk PDF'i ZIP'e girer
        $yanit = FirmaIsgDosyasi::teftisPaketi($firma);
        $this->assertNotNull($yanit);
        ob_start();
        $yanit->sendContent();
        $zip = ob_get_clean();
        $yol = tempnam(sys_get_temp_dir(), 't').'.zip';
        file_put_contents($yol, $zip);
        $z = new \ZipArchive;
        $this->assertTrue($z->open($yol));
        $this->assertNotFalse($z->locateName('1-risk-degerlendirmesi.pdf'));
        $this->assertStringStartsWith('%PDF', $z->getFromName('1-risk-degerlendirmesi.pdf'));
        $z->close();
        @unlink($yol);
    }
}
