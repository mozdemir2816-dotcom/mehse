<?php

namespace Tests\Feature;

use App\Filament\Resources\Calisans\Pages\ListCalisans;
use App\Filament\Resources\Firmas\Pages\ListFirmas;
use App\Filament\Resources\IsgProfesyonelis\Pages\ListIsgProfesyonelis;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\KkdZimmet;
use App\Models\MuayeneFormu;
use App\Models\User;
use App\Support\CalisanIsgDurumu;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Firmalar & Çalışanlar grubu liste düzeni (Firmalar, Çalışanlar, İSG Profesyonelleri). */
class VeriYonetimiListeleriTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function durum(Calisan $c): array
    {
        $kayit = CalisanIsgDurumu::sorgu(Calisan::query())->with('firma')->findOrFail($c->id);

        return collect(CalisanIsgDurumu::rozetler($kayit))->pluck('etiket')->all();
    }

    public function test_isg_durumu_bos_calisanda_uc_eksik_rozeti_verir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        $c = Calisan::factory()->for($firma)->create();

        $this->assertSame(['Eğitim Eksik', 'KKD Eksik', 'Muayene Yok'], $this->durum($c));
    }

    public function test_isg_durumu_egitim_kkd_ve_muayene_kayitlarindan_hesaplanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        $c = Calisan::factory()->for($firma)->create();

        \App\Models\EgitimKaydi::create(['calisan_id' => $c->id, 'tur' => 'Temel İSG', 'tarih' => now()->subMonth()]);
        KkdZimmet::create(['firma_id' => $firma->id, 'calisan_id' => $c->id, 'tur' => 'Baret', 'durum' => 'teslim_edildi', 'teslim_tarihi' => now()->subMonth(), 'yenileme_tarihi' => now()->addYear()]);
        MuayeneFormu::create(['firma_id' => $firma->id, 'calisan_id' => $c->id, 'muayene_tarihi' => now()->subMonths(2)]);

        $this->assertSame(['Eğitim Tam', 'KKD Tam', 'Muayene Tam'], $this->durum($c));

        // Yenileme tarihi geçen zimmet "KKD Gecikmiş"; çok tehlikelide 1 yıllık muayene periyodu aşılınca "Muayene Gecikmiş".
        KkdZimmet::query()->update(['yenileme_tarihi' => now()->subDay()]);
        $firma->update(['tehlike_sinifi' => 'cok_tehlikeli']);
        MuayeneFormu::query()->update(['muayene_tarihi' => now()->subYears(2)]);

        $this->assertSame(['Eğitim Tam', 'KKD Gecikmiş', 'Muayene Gecikmiş'], $this->durum($c->fresh()));
    }

    public function test_calisan_listesi_isg_durumu_ve_portfoy_sayaci_gosterir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->count(3)->create(['aktif' => true]);
        Calisan::factory()->for($firma)->count(2)->create(['aktif' => false]);

        Livewire::test(ListCalisans::class)
            ->assertSee('Eğitim Eksik')
            ->assertSee('3 aktif · 2 pasif · portföyde toplam 5 çalışan')
            ->assertCountTableRecords(3);
    }

    public function test_calisan_listesi_ust_butonlari_tek_menude_toplanir(): void
    {
        $html = Livewire::test(ListCalisans::class)->html();

        $this->assertSame(1, substr_count($html, 'Dışa Aktar / İçe Aktar'));
        $this->assertSame(1, substr_count($html, 'Çalışan Ekle'));
    }

    public function test_firma_listesi_icerik_dis_aktar_tek_menude_ve_birlesik_firma_hucresi(): void
    {
        Firma::factory()->for($this->uzman)->create(['unvan' => 'BEKMAK ELEKTRİK SAN. A.Ş.', 'kisa_ad' => 'BEKMAK']);

        $sayfa = Livewire::test(ListFirmas::class)
            ->assertSee('İçe / Dışa Aktar')
            ->assertSee('BEKMAK ELEKTRİK SAN. A.Ş.')
            ->assertSee('BEKMAK')
            ->assertSee('Firma Ekle');

        $this->assertSame(1, substr_count($sayfa->html(), 'İçe / Dışa Aktar'));
    }

    public function test_isg_profesyoneli_tek_rol_unvan_sutunu_ve_islem_menusu(): void
    {
        IsgProfesyoneli::create(['tip' => 'igu', 'ad_soyad' => 'Ayşe Uzman', 'unvan' => 'A Sınıfı İGU']);
        IsgProfesyoneli::create(['tip' => 'isyeri_hekimi', 'ad_soyad' => 'Dr. Veli']);

        Livewire::test(ListIsgProfesyonelis::class)
            ->assertSee('Rol / Unvan')
            ->assertSee('A Sınıfı İGU')
            ->assertSee('İşyeri Hekimi');
    }

    public function test_teklif_talepleri_bos_durum_ve_elle_giris(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $sayfa = Livewire::test(\App\Filament\Resources\TeklifTalepleris\Pages\ListTeklifTalepleris::class)
            ->assertSee('Bekleyen Teklif Talebi Bulunmuyor')
            ->assertSee('mehse.com')
            ->assertSee('Web Formu Bağlantısını Al')
            ->assertSee('Yeni Teklif Girişi Yap');

        // Liste dolunca aynı düğmeler üst çubukta durur; elle giriş kaydı ekler.
        \App\Models\TeklifTalebi::create(['firma_adi' => 'İlk Ltd.', 'kisi' => 'X', 'telefon' => '1', 'calisan_sayisi' => 1, 'tehlike_sinifi' => 'Tehlikeli']);
        $sayfa = Livewire::test(\App\Filament\Resources\TeklifTalepleris\Pages\ListTeklifTalepleris::class);

        $sayfa->callAction(TestAction::make('yeniGiris')->table(), data: [
            'firma_adi' => 'Deneme Ltd.', 'kisi' => 'Ali Veli', 'telefon' => '05551112233',
            'calisan_sayisi' => 12, 'tehlike_sinifi' => 'Tehlikeli', 'durum' => 'yeni',
        ])->assertHasNoFormErrors();

        $this->assertDatabaseHas('teklif_talepleri', ['firma_adi' => 'Deneme Ltd.', 'calisan_sayisi' => 12]);
    }

    public function test_ekip_evraklari_merkezi_risk_havuzu_adiyla_acilir(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $this->assertSame('Risk & Evrak Havuzu', \App\Filament\Pages\EkipEvraklari::getNavigationLabel());
        Livewire::test(\App\Filament\Pages\EkipEvraklari::class)->assertSee('Merkezi Risk')->assertSee('Hazırlayan kişi');
    }
}
