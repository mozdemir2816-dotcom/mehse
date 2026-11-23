<?php

namespace Tests\Feature;

use App\Filament\Pages\Araclar;
use App\Filament\Pages\CalisanTemsilcisiSecimi;
use App\Filament\Pages\OrtamOlcumleri;
use App\Filament\Pages\PkdSicili;
use App\Filament\Pages\SaglikGozetimi;
use App\Models\Firma;
use App\Models\OrtamOlcumu;
use App\Models\User;
use App\Support\SayfaAileleri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Faz2UxGruplamaVeStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_calisan_temsilcisi_aday_silince_secilen_kazanan_indeksi_kaymaz(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Temsilci A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        Livewire::test(CalisanTemsilcisiSecimi::class, ['firmaId' => $f->id])
            ->set('adaylar', [
                ['ad_soyad' => 'Aday 0', 'unvan' => 'İşçi'],
                ['ad_soyad' => 'Aday 1', 'unvan' => 'Usta'],
                ['ad_soyad' => 'Aday 2 (Kazanan)', 'unvan' => 'Teknisyen'],
            ])
            ->set('secilenAdayIndex', 2)
            ->call('adaySil', 0)
            ->assertSet('secilenAdayIndex', 1);
    }

    public function test_pkd_sicili_risk_sayfa_ailesinde_yer_alir(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);

        $gruplar = SayfaAileleri::sekmeGruplari([PkdSicili::class]);
        $this->assertNotNull($gruplar);
        $this->assertSame('risk', $gruplar['aile']);
    }

    public function test_ortam_olcumu_tarihi_degisince_sonraki_olcum_tarihi_guncellenir(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Ölçüm A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        $olcum = OrtamOlcumu::create([
            'firma_id' => $f->id,
            'olcumler' => [[
                'tur' => 'gurultu',
                'parametre' => 'Gürültü',
                'grup' => 'Fiziksel',
                'olcum_tarihi' => '2025-01-31',
                'periyot_ay' => 12,
                'sonraki_olcum_tarihi' => '2026-01-31',
            ]],
        ]);

        Livewire::test(OrtamOlcumleri::class)
            ->set('firmaId', $f->id)
            ->set('satirlar.0.olcum_tarihi', '2026-01-31')
            ->set('satirlar.0.periyot_ay', 13)
            ->call('kaydet', true);

        // 2026-01-31 + 13 ay (noOverflow) = 2027-02-28
        $this->assertSame('2027-02-28', $olcum->fresh()->olcumler[0]['sonraki_olcum_tarihi']);
    }

    public function test_araclar_kaza_sifirla_onceki_donem_kso_alanini_da_temizler(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);

        Livewire::test(Araclar::class)
            ->set('kazaSayisi', 5)
            ->set('oncekiDonemKso', 12.5)
            ->call('kazaHesaplayiciSifirla')
            ->assertSet('kazaSayisi', null)
            ->assertSet('oncekiDonemKso', null);
    }

    public function test_saglik_gozetimi_tetkik_aksiyonlari_yalniz_tetkik_sekmesinde_gorunur(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Revir A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        Livewire::test(SaglikGozetimi::class, ['firmaId' => $f->id])
            ->assertActionVisible('kaydet')
            ->call('sekmeSec', 'revir')
            ->assertActionHidden('kaydet');
    }
}
