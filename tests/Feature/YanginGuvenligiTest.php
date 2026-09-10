<?php

namespace Tests\Feature;

use App\Filament\Pages\YanginGuvenligi as YanginSayfasi;
use App\Models\Firma;
use App\Models\User;
use App\Models\YanginGuvenligiDegerlendirmesi;
use App\Support\YanginGuvenligiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class YanginGuvenligiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    public function test_yuksek_riskli_madde_secilince_sinif_yuksek_olur(): void
    {
        $d = new YanginGuvenligiDegerlendirmesi([
            'kullanim_turu' => 'buro',
            'riskler' => ['Basınçlı gaz tüpleri', 'Yanıcı / parlayıcı sıvı (tiner, solvent, yakıt)'],
        ]);

        $this->assertSame('yuksek', $d->hesaplaTehlikeSinifi());
    }

    public function test_endustriyel_yapi_risksiz_bile_en_az_orta(): void
    {
        $d = new YanginGuvenligiDegerlendirmesi(['kullanim_turu' => 'endustriyel', 'riskler' => []]);
        $this->assertSame('orta', $d->hesaplaTehlikeSinifi());
    }

    public function test_dusuk_riskli_buro_dusuk_sinif(): void
    {
        $d = new YanginGuvenligiDegerlendirmesi(['kullanim_turu' => 'buro', 'riskler' => []]);
        $this->assertSame('dusuk', $d->hesaplaTehlikeSinifi());
    }

    public function test_saving_hook_sinifi_yazar_elle_degilse(): void
    {
        $d = YanginGuvenligiDegerlendirmesi::firmaIcin($this->firma);
        $d->update(['kullanim_turu' => 'depolama', 'riskler' => ['Yüksek raflı depolama (> 4 m)']]);

        $this->assertSame('yuksek', $d->refresh()->belirlenen_tehlike_sinifi);
    }

    public function test_elle_sinif_secilince_hesap_ezilmez(): void
    {
        $d = YanginGuvenligiDegerlendirmesi::firmaIcin($this->firma);
        $d->belirlenen_tehlike_sinifi = 'dusuk';
        $d->sinif_elle = true;
        $d->riskler = ['Yanıcı / parlayıcı sıvı (tiner, solvent, yakıt)'];
        $d->save();

        $this->assertSame('dusuk', $d->refresh()->belirlenen_tehlike_sinifi);
    }

    public function test_kutuphaneden_tespit_eklenir_ve_kaydedilir(): void
    {
        Livewire::test(YanginSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('kullanimTuru', 'endustriyel')
            ->set('riskler', ['Boya kabini / solventli yüzey işlem'])
            ->call('tespitEkle', 0)
            ->call('tespitEkle', 0)   // kopya eklemez
            ->call('serbestTespitEkle', 'Yangın butonu ilave edilecek')
            ->call('kaydet');

        $d = YanginGuvenligiDegerlendirmesi::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(2, $d->tespitler);
        $this->assertSame('yuksek', $d->belirlenen_tehlike_sinifi);
    }

    public function test_pdf_uretilir(): void
    {
        $d = YanginGuvenligiDegerlendirmesi::firmaIcin($this->firma);
        $d->update([
            'kullanim_turu' => 'endustriyel', 'taban_alani_m2' => 2400, 'kat_sayisi' => 2, 'kullanici_yuku' => 120,
            'bolumler' => ['Üretim / atölye', 'Kimyasal depolama alanı'],
            'riskler' => ['Yanıcı kimyasal madde', 'Kaynak / taşlama / sıcak çalışma'],
            'tespitler' => [['madde' => 'Sprinkler değerlendirilmeli', 'oncelik' => 'yuksek']],
        ]);

        $yanit = YanginGuvenligiUretici::pdf($d);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(YanginSayfasi::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
