<?php

namespace Tests\Feature;

use App\Filament\Resources\Firmas\Pages\EditFirma;
use App\Filament\Resources\Firmas\RelationManagers as RM;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FirmaKayitSekmeleriTest extends TestCase
{
    use RefreshDatabase;

    public function test_firma_sayfasindaki_tum_kayit_sekmeleri_acilir(): void
    {
        $uzman = User::factory()->create();
        $firma = Firma::factory()->for($uzman)->create();
        $this->actingAs($uzman);
        Filament::setCurrentPanel('admin');

        foreach ([
            RM\CalisanlarRelationManager::class, RM\RiskSekmesi::class, RM\EgitimKayitlariSekmesi::class,
            RM\SertifikalarSekmesi::class, RM\IsbasiEgitimleriSekmesi::class, RM\TatbikatlarRelationManager::class,
            RM\SahaDenetimleriSekmesi::class, RM\DofRaporlariSekmesi::class, RM\KkdZimmetleriSekmesi::class,
            RM\KurulToplantilariSekmesi::class, RM\OlaylarSekmesi::class, RM\IsKazalariSekmesi::class, RM\AtamaYazilariSekmesi::class,
        ] as $sinif) {
            Livewire::test($sinif, ['ownerRecord' => $firma, 'pageClass' => EditFirma::class])->assertSuccessful();
        }
    }

    public function test_risk_sekmesi_firmanin_kayitlarini_listeler_ve_pdf_verir(): void
    {
        $uzman = User::factory()->create();
        $firma = Firma::factory()->for($uzman)->create();
        $baska = Firma::factory()->for($uzman)->create();
        $this->actingAs($uzman);
        Filament::setCurrentPanel('admin');

        $risk = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $digeri = RiskDegerlendirmesi::create(['firma_id' => $baska->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);

        Livewire::test(RM\RiskSekmesi::class, ['ownerRecord' => $firma, 'pageClass' => EditFirma::class])
            ->assertCanSeeTableRecords([$risk])
            ->assertCanNotSeeTableRecords([$digeri])
            ->callTableAction('pdf', $risk)
            ->assertHasNoTableActionErrors();
    }

    public function test_firma_sayfasi_kayit_bolumlerini_alt_alta_gosterir(): void
    {
        $uzman = User::factory()->create();
        $firma = Firma::factory()->for($uzman)->create();
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $this->actingAs($uzman);
        Filament::setCurrentPanel('admin');

        Livewire::test(EditFirma::class, ['record' => $firma->getKey()])
            ->assertSuccessful()
            ->assertSee('Risk Analizleri (1)')
            ->assertSee('Eğitim Kayıtları (0)')
            ->assertSee('Çalışanlar')
            ->assertSee('Tatbikatlar');
    }
}
