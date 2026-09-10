<?php

namespace Tests\Feature;

use App\Filament\Pages\SaglikGozetimi as SaglikGozetimiSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\SaglikGozetimi;
use App\Models\User;
use App\Support\SaglikGozetimiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SaglikGozetimiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
    }

    public function test_calisan_ve_tetkik_secilip_satir_eklenir(): void
    {
        $c = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Ali Veli', 'gorev' => 'Operatör']);

        Livewire::test(SaglikGozetimiSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('yeniCalisanId', $c->id)
            ->set('yeniTur', 'odyometri')
            ->call('satirEkle')
            ->call('kaydet');

        $g = SaglikGozetimi::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(1, $g->satirlar);
        $this->assertSame('Ali Veli', $g->satirlar[0]['calisan_adi']);
        $this->assertSame('odyometri', $g->satirlar[0]['tetkik_turu']);
    }

    public function test_tum_calisanlara_ekle_kopya_olusturmaz(): void
    {
        Calisan::factory()->for($this->firma)->count(3)->create();

        $c = Livewire::test(SaglikGozetimiSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('yeniTur', 'periyodik')
            ->call('tumCalisanlaraEkle')
            ->call('tumCalisanlaraEkle')   // ikinci kez — kopya eklemez
            ->call('kaydet');

        $g = SaglikGozetimi::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(3, $g->satirlar);
    }

    public function test_periyot_ay_olan_tetkik_tarihten_sonraki_tarihi_hesaplar(): void
    {
        $g = SaglikGozetimi::firmaIcin($this->firma);
        $g->setRelation('firma', $this->firma);
        $g->update(['satirlar' => [
            ['calisan_adi' => 'X', 'tetkik_turu' => 'portor', 'tarih' => '2026-01-10', 'sonuc' => 'uygun'], // 3 ay
            ['calisan_adi' => 'Y', 'tetkik_turu' => 'periyodik', 'tarih' => '2026-01-10', 'sonuc' => 'uygun'], // tehlikeli → 3 yıl
        ]]);

        $g->refresh();
        $this->assertSame('2026-04-10', $g->satirlar[0]['sonraki_tarih']);
        $this->assertSame('2029-01-10', $g->satirlar[1]['sonraki_tarih']);
    }

    public function test_yaklasanlar_suresi_gecmis_tetkigi_yakalar(): void
    {
        $g = SaglikGozetimi::firmaIcin($this->firma);
        $g->setRelation('firma', $this->firma);
        $g->update(['satirlar' => [
            ['calisan_adi' => 'X', 'tetkik_turu' => 'portor', 'tarih' => now()->subMonths(6)->toDateString(), 'sonuc' => 'uygun'],
        ]]);

        $this->assertCount(1, $g->refresh()->yaklasanlar());
    }

    public function test_pdf_uretilir(): void
    {
        $g = SaglikGozetimi::firmaIcin($this->firma);
        $g->setRelation('firma', $this->firma);
        $g->update(['satirlar' => [
            ['calisan_adi' => 'Ali Veli', 'gorev' => 'Kaynakçı', 'tetkik_turu' => 'sft', 'tarih' => '2026-03-01', 'sonuc' => 'uygun', 'rapor_no' => 'SFT-12'],
        ]]);

        $yanit = SaglikGozetimiUretici::pdf($g);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(SaglikGozetimiSayfasi::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
