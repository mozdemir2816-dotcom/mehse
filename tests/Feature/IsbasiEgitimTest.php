<?php

namespace Tests\Feature;

use App\Filament\Pages\IsbasiEgitim as IsbasiSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsbasiEgitimTutanagi;
use App\Models\User;
use App\Support\IsbasiEgitimTutanagiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class IsbasiEgitimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_calisanindan_hizli_secim_alanlari_doldurur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz', 'tc' => '12345678901']);

        $component = Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanHizliSecId', $calisan->id);

        $this->assertSame('Ahmet Yılmaz', $component->get('calisanAdSoyad'));
        $this->assertSame('12345678901', $component->get('calisanTc'));
    }

    public function test_konu_toggle_ve_tumunu_sec(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplam = collect(config('isg.isbasi_egitim.konu_kategorileri'))->flatten()->count();

        $component = Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('tumKonular', true);

        $this->assertCount($toplam, $component->get('secilenKonular'));

        $component->call('tumKonular', false);
        $this->assertCount(0, $component->get('secilenKonular'));
    }

    public function test_pdf_aksiyonu_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $ilkMadde = collect(config('isg.isbasi_egitim.konu_kategorileri'))->flatten()->first();

        Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanAdSoyad', 'Ahmet Yılmaz')
            ->call('konuToggle', $ilkMadde)
            ->callAction('pdf');

        $t = IsbasiEgitimTutanagi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Ahmet Yılmaz', $t->calisan_ad_soyad);
        $this->assertContains($ilkMadde, $t->konular);
    }

    public function test_calisan_adi_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('isbasi_egitim_tutanaklari', 0);
    }

    public function test_tc_gizli_isaretlenince_pdf_gorunumu_maskelenir(): void
    {
        $t = IsbasiEgitimTutanagi::create([
            'firma_id' => Firma::factory()->for($this->uzman)->create()->id,
            'calisan_ad_soyad' => 'Test Kişi',
            'calisan_tc' => '12345678901',
            'tc_gizli' => true,
        ]);

        $this->assertSame('123'.str_repeat('*', 8), $t->tcGorunur());
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = IsbasiEgitimTutanagi::create([
            'firma_id' => $firma->id,
            'calisan_ad_soyad' => 'Test Kişi',
            'konular' => ['İşyeri ve organizasyonun tanıtımı'],
        ]);

        $yanit = IsbasiEgitimTutanagiUretici::pdf($t);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_tutanak_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = IsbasiEgitimTutanagi::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'Test']);

        Livewire::test(IsbasiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $t->id);

        $this->assertDatabaseMissing('isbasi_egitim_tutanaklari', ['id' => $t->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(IsbasiSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
