<?php

namespace Tests\Feature;

use App\Filament\Pages\IseDonusBelgesi as IseDonusSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IseDonusBelgesi;
use App\Models\IsgProfesyoneli;
use App\Models\User;
use App\Support\IseDonusBelgesiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class IseDonusBelgesiTest extends TestCase
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

    public function test_calisan_hizli_secim_alanlari_doldurur(): void
    {
        $c = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Ali Veli', 'tc' => '12345678901', 'gorev' => 'İşçi']);

        Livewire::test(IseDonusSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('calisanHizliSecId', $c->id)
            ->assertSet('calisanAdSoyad', 'Ali Veli')
            ->assertSet('gorev', 'İşçi');
    }

    public function test_belge_olustur_kayit_yapar_ve_pdf_doner(): void
    {
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'isyeri_hekimi', 'ad_soyad' => 'Dr. Cüneyt', 'kase_gorseli' => 'x/y.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['isyeri_hekimi_id' => $hekim->id]);

        $yanit = Livewire::test(IseDonusSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanAdSoyad', 'Furkan Bal')
            ->set('neden', 'is_kazasi')
            ->set('devamsizlikBaslangic', '2026-04-05')
            ->set('devamsizlikBitis', '2026-04-20')
            ->set('uygunluk', 'kisitli')
            ->set('kisitlamalar', ['Yüksekte çalışma yasağı', 'Ağır kaldırma yasağı (10 kg üzeri)'])
            ->set('hekimGorusu', 'Kontrol muayenesine kadar hafif işte.')
            ->callAction('pdf');

        $b = IseDonusBelgesi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Furkan Bal', $b->calisan_ad_soyad);
        $this->assertSame('is_kazasi', $b->neden);
        $this->assertCount(2, $b->kisitlamalar);
        $this->assertSame('Dr. Cüneyt', $b->hekim_adi);
        $this->assertSame('x/y.png', $b->hekim_kase);
        $this->assertSame(16, $b->devamsizlikGunu());
        $this->assertStringStartsWith('IDB-'.now()->year.'-', $b->belge_no);

        $pdf = IseDonusBelgesiUretici::pdf($b);
        $this->assertInstanceOf(StreamedResponse::class, $pdf);
        ob_start();
        $pdf->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_tam_uygun_secilince_kisitlamalar_kaydedilmez(): void
    {
        Livewire::test(IseDonusSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('calisanAdSoyad', 'X')
            ->set('uygunluk', 'tam')
            ->set('kisitlamalar', ['Yüksekte çalışma yasağı'])
            ->callAction('pdf');

        $b = IseDonusBelgesi::where('firma_id', $this->firma->id)->firstOrFail();
        $this->assertSame([], $b->kisitlamalar);
    }

    public function test_calisan_adi_olmadan_kaydedilemez(): void
    {
        Livewire::test(IseDonusSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('ise_donus_belgeleri', 0);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(IseDonusSayfasi::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
