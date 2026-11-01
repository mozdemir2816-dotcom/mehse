<?php

namespace Tests\Feature;

use App\Models\Basvuru;
use App\Models\TeklifTalebi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YasalSayfalarTest extends TestCase
{
    use RefreshDatabase;

    public function test_kvkk_sayfasi_herkese_acik_ve_veri_sorumlusu_dolu(): void
    {
        $this->get('/kvkk')->assertOk()
            ->assertSee('KVKK Aydınlatma Metni')
            ->assertSee('Mehmet Özdemir - Mehse İş Sağlığı ve Güvenliği')
            ->assertSee('mozdemir2816@gmail.com')
            ->assertSee('0506 475 48 55')
            ->assertSee('Tanıtım sayfası teklif formu')
            ->assertSee('Sürüm: 1.3')
            ->assertSee(route('cerez'), false);
    }

    public function test_kvkk_metninde_yalniz_vergi_numarasi_taslak_kalir(): void
    {
        config(['isg.kayit.saglayici.vergi_no' => '1234567890']);
        $html = view('yasal.kvkk')->render();

        $this->assertStringNotContainsString('<mark', $html);
        $this->assertStringNotContainsString('[', strip_tags($html));
    }

    public function test_cerez_politikasi_herkese_acik_ve_yalniz_zorunlu_cerezleri_anlatir(): void
    {
        $this->get('/cerez-politikasi')->assertOk()
            ->assertSee('Çerez Politikası')
            ->assertSee('zorunlu çerezleri', false)
            ->assertSee(config('session.cookie'))
            ->assertSee('XSRF-TOKEN')
            ->assertSee(route('kvkk'), false);
    }

    public function test_tanitim_sayfasi_kvkk_ve_cerez_baglantilarini_verir(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('href="'.route('kvkk').'" target="_blank"', false)
            ->assertSee('href="'.route('cerez').'"', false)
            ->assertSee('name="kvkk"', false);
    }

    public function test_sozlesme_kvkk_ve_cerez_politikasina_atif_yapar(): void
    {
        $html = view('yasal.sozlesme')->render();

        $this->assertStringContainsString('mehse.com/kvkk', $html);
        $this->assertStringContainsString('mehse.com/cerez-politikasi', $html);
    }

    public function test_bir_yildan_eski_teklifler_ve_reddedilen_basvurular_silinir(): void
    {
        $eski = TeklifTalebi::create(['firma_adi' => 'Eski', 'kisi' => 'K', 'telefon' => '0532', 'calisan_sayisi' => 1, 'tehlike_sinifi' => 'Tehlikeli']);
        $eski->forceFill(['updated_at' => now()->subYear()->subDay()])->saveQuietly();
        $yeni = TeklifTalebi::create(['firma_adi' => 'Yeni', 'kisi' => 'K', 'telefon' => '0532', 'calisan_sayisi' => 1, 'tehlike_sinifi' => 'Tehlikeli']);

        $temel = ['tip' => 'osgb', 'ad_soyad' => 'A', 'eposta' => 'a@b.c', 'osgb_adi' => 'O'];
        $red = Basvuru::create([...$temel, 'durum' => 'reddedildi', 'incelendi_at' => now()->subYear()->subDay()]);
        $yeniRed = Basvuru::create([...$temel, 'eposta' => 'b@b.c', 'durum' => 'reddedildi', 'incelendi_at' => now()->subMonth()]);
        $onayli = Basvuru::create([...$temel, 'eposta' => 'c@b.c', 'durum' => 'onaylandi', 'incelendi_at' => now()->subYears(2)]);

        $this->artisan('model:prune', ['--model' => [TeklifTalebi::class, Basvuru::class]])->assertSuccessful();

        $this->assertModelMissing($eski);
        $this->assertModelExists($yeni);
        $this->assertModelMissing($red);
        $this->assertModelExists($yeniRed);
        $this->assertModelExists($onayli);
    }
}
