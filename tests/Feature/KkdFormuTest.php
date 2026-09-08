<?php

namespace Tests\Feature;

use App\Filament\Pages\KkdFormu as KkdSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\KkdZimmetFormu;
use App\Models\User;
use App\Support\KkdZimmetFormuUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class KkdFormuTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_calisani_ve_kkd_secilip_pdf_kaydedilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz']);
        $ilkKkd = config('isg.kkd.kategoriler.bas_yuz.maddeler.0.ad');

        Livewire::test(KkdSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('calisanToggle', $calisan->id)
            ->call('kkdToggle', 'bas_yuz', $ilkKkd)
            ->callAction('pdf');

        $form = KkdZimmetFormu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(1, $form->calisanlar);
        $this->assertSame('Ahmet Yılmaz', $form->calisanlar[0]['ad_soyad']);
        $this->assertCount(1, $form->kkdler);
        $this->assertSame($ilkKkd, $form->kkdler[0]['ad']);
        $this->assertStringStartsWith('KKD-'.now()->year.'-', $form->form_no);
    }

    public function test_kategorinin_tumunu_sec(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $adet = count(config('isg.kkd.kategoriler.isitme.maddeler'));

        $component = Livewire::test(KkdSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('kategoriTumunuSec', 'isitme');

        $this->assertCount($adet, $component->get('secilenKkdler'));
    }

    public function test_manuel_calisan_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(KkdSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('manuelAdSoyad', 'Zeynep Kaya')
            ->call('manuelCalisanEkle');

        $this->assertCount(1, $component->get('manuelCalisanlar'));

        $component->call('manuelCalisanSil', 0);
        $this->assertCount(0, $component->get('manuelCalisanlar'));
    }

    public function test_calisan_veya_kkd_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(KkdSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('kkd_zimmet_formlari', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $form = KkdZimmetFormu::create([
            'firma_id' => $firma->id,
            'teslim_tarihi' => now(),
            'calisanlar' => [['ad_soyad' => 'Test Kişi', 'tc' => null, 'departman' => null]],
            'kkdler' => [['ad' => 'Endüstriyel Emniyet Bareti', 'standart' => 'TS EN 397', 'kategori' => 'Baş ve Yüz Koruyucular']],
        ]);

        $yanit = KkdZimmetFormuUretici::pdf($form);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_pdf_gercek_teslim_tutanagi_sablonunu_kullanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek İnşaat A.Ş.']);
        $form = KkdZimmetFormu::create([
            'firma_id' => $firma->id,
            'teslim_tarihi' => now(),
            'teslim_eden' => 'Mehmet Özdemir',
            'calisanlar' => [['ad_soyad' => 'Ali Veli', 'tc' => null, 'departman' => 'Kalıpçı']],
            'kkdler' => [['ad' => 'Endüstriyel Emniyet Bareti', 'standart' => 'TS EN 397', 'kategori' => 'Baş ve Yüz Koruyucular']],
        ]);

        $html = view('pdf.kkd-zimmet-formu', ['form' => $form, 'firma' => $firma])->render();

        $this->assertStringContainsString('KİŞİSEL KORUYUCU DONANIM TESLİM TUTANAĞI', $html);
        $this->assertStringContainsString('MALZEMENİN TÜRÜ', $html);
        $this->assertStringContainsString('KULLANMA DÖNEMİ', $html);
        $this->assertStringContainsString('4857 sayılı Kanun 25. maddesi', $html);
        $this->assertStringContainsString('TESLİM ALAN', $html);
        $this->assertStringContainsString('TESLİM VEREN', $html);
        $this->assertStringContainsString('Ali Veli', $html);
        $this->assertStringContainsString('Mehmet Özdemir', $html);
        $this->assertStringContainsString('TS EN 397', $html);
    }

    public function test_gecmis_form_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $form = KkdZimmetFormu::create([
            'firma_id' => $firma->id, 'teslim_tarihi' => now(), 'calisanlar' => [], 'kkdler' => [],
        ]);

        Livewire::test(KkdSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $form->id);

        $this->assertDatabaseMissing('kkd_zimmet_formlari', ['id' => $form->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(KkdSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
