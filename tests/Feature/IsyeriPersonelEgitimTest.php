<?php

namespace Tests\Feature;

use App\Filament\Isyeri\Pages\OnlineEgitim;
use App\Filament\Isyeri\Pages\Personel;
use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Models\IsyeriHesabi;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** 13.11.2026: işveren paneli — personel ekle / çıkış, online eğitime ekle / çıkar (yalnız kendi firması). */
class IsyeriPersonelEgitimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Deneme Yapı A.Ş.', 'tehlike_sinifi' => 'az_tehlikeli']);
        $this->actingAs(IsyeriHesabi::firmaIcin($this->firma), 'isyeri');
        Filament::setCurrentPanel(Filament::getPanel('isyeri'));
    }

    private function paketKur(int $dersSn = 30000): EgitimPaketi
    {
        $paket = EgitimPaketi::create(['user_id' => $this->uzman->id, 'ad' => 'Temel İSG', 'gecme_puani' => 70, 'video_zorunlu_yuzde' => 90, 'sinav_soru_sayisi' => 5, 'aktif' => true]);
        $paket->dersler()->create(['sira' => 1, 'baslik' => 'Ders 1', 'video_url' => 'https://youtu.be/abcDEFG1', 'sure_sn' => $dersSn]);
        for ($i = 1; $i <= 6; $i++) {
            $paket->sorular()->create(['soru' => "Soru $i?", 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 1]);
        }

        return $paket;
    }

    public function test_sayfalar_acilir_ve_baska_firmanin_personeli_gorunmez(): void
    {
        Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Kendi Çalışan', 'aktif' => true]);
        Calisan::create(['firma_id' => Firma::factory()->create()->id, 'ad_soyad' => 'Rakip Çalışan', 'aktif' => true]);

        $this->get('/isyeri/personel')->assertOk()->assertSee('Kendi Çalışan')->assertDontSee('Rakip Çalışan');
        $this->get('/isyeri/online-egitim')->assertOk()->assertSee('Kendi Çalışan')->assertDontSee('Rakip Çalışan');
    }

    public function test_personel_ekler_tc_tekrarini_engeller_ve_cikis_yapar(): void
    {
        Livewire::test(Personel::class)
            ->callTableAction('ekle', data: ['ad_soyad' => 'Ayşe Kaya', 'tc' => '12345678901', 'gorev' => 'Kalıpçı', 'ise_giris' => now()->toDateString()])
            ->assertHasNoTableActionErrors();

        $c = Calisan::where('firma_id', $this->firma->id)->sole();
        $this->assertTrue((bool) $c->aktif);
        $this->assertStringContainsString('İşveren panelinden', $c->notlar);

        Livewire::test(Personel::class)
            ->callTableAction('ekle', data: ['ad_soyad' => 'Başka', 'tc' => '12345678901', 'gorev' => 'Usta', 'ise_giris' => now()->toDateString()])
            ->assertHasTableActionErrors(['tc']);

        Livewire::test(Personel::class)
            ->callTableAction('cikis', $c, data: ['isten_cikis' => now()->toDateString()]);

        $c->refresh();
        $this->assertFalse((bool) $c->aktif);
        $this->assertNotNull($c->isten_cikis);
        $this->assertDatabaseCount('calisanlar', 1);   // silinmez
    }

    public function test_egitime_ekler_baslamamisi_cikarir_baslamisi_cikarmaz(): void
    {
        $paket = $this->paketKur();
        $a = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Ali Veli', 'aktif' => true]);
        $b = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Can Er', 'aktif' => true]);

        $sayfa = Livewire::test(OnlineEgitim::class)
            ->callTableBulkAction('topluEkle', [$a, $b], data: ['paket_id' => $paket->id, 'egitim_turu' => 'ilk_defa']);

        $this->assertSame(2, EgitimAtamasi::where('egitim_paketi_id', $paket->id)->count());
        $this->assertCount(2, $sayfa->get('girisBilgileri'));
        $this->assertNotNull($a->fresh()->eposta, 'e-postasız çalışana kullanıcı kodu');
        $this->assertSame($this->uzman->id, EgitimAtamasi::first()->atayan_user_id);

        // Başlamış eğitim (ön test girilmiş) çıkarılamaz; başlamamış çıkarılır.
        EgitimAtamasi::where('calisan_id', $b->id)->update(['on_test_at' => now()]);
        Livewire::test(OnlineEgitim::class)->assertTableActionHidden('cikar', $b)->callTableAction('cikar', $a);

        $this->assertFalse(EgitimAtamasi::where('calisan_id', $a->id)->exists());
        $this->assertTrue(EgitimAtamasi::where('calisan_id', $b->id)->exists());
    }

    public function test_suresi_yetersiz_paket_atanmaz(): void
    {
        $paket = $this->paketKur(dersSn: 600);
        $a = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Ali Veli', 'aktif' => true]);

        Livewire::test(OnlineEgitim::class)
            ->callTableAction('ekle', $a, data: ['paket_id' => $paket->id, 'egitim_turu' => 'ilk_defa'])
            ->assertNotified('Bu eğitimin süresi yetersiz');

        $this->assertSame(0, EgitimAtamasi::count());
    }
}
