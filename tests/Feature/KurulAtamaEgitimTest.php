<?php

namespace Tests\Feature;

use App\Filament\Pages\KurulToplantisi as KurulSayfasi;
use App\Models\AtamaYazisi;
use App\Models\Calisan;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\User;
use App\Support\GorevDurumu;
use App\Support\KurulAtamaEgitim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** İSG Kurulu: üye atanınca/değişince atama yazısı otomatik hazırlanır, kurul eğitimi hatırlatılır (03.11.2026). */
class KurulAtamaEgitimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['calisan_sayisi' => 80]);
    }

    private function uye(string $rol, string $ad, ?int $calisanId = null): void
    {
        $this->firma->kurulUyeleri()->create(['rol' => $rol, 'ad_soyad' => $ad, 'gorev' => null, 'calisan_id' => $calisanId, 'aktif' => true]);
    }

    public function test_uye_eklenince_atama_yazisi_otomatik_hazirlanir_ve_kopya_birikmez(): void
    {
        $c = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Ayşe Temsilci', 'tc' => '11111111111']);

        $sayfa = Livewire::test(KurulSayfasi::class)->set('firmaId', $this->firma->id)
            ->set('uyeRol', 'calisan_temsilcisi')
            ->call('calisaniEkle', $c->id);

        $yazi = AtamaYazisi::where('firma_id', $this->firma->id)->where('rol_anahtari', 'isg_kurulu')->firstOrFail();
        $this->assertSame('Ayşe Temsilci', $yazi->uyeler[0]['ad_soyad']);
        $this->assertSame('11111111111', $yazi->uyeler[0]['tc']);
        $this->assertSame('Çalışan Temsilcisi', $yazi->uyeler[0]['gorev']);

        // Aynı gün ikinci üye: aynı kayıt güncellenir, yeni kayıt açılmaz.
        $sayfa->set('uyeRol', 'baskan')->set('elleUyeAd', 'Veli Başkan')->call('elleUyeEkle');
        $this->assertSame(1, AtamaYazisi::where('firma_id', $this->firma->id)->where('rol_anahtari', 'isg_kurulu')->count());
        $yazi->refresh();
        $this->assertSame(['Ayşe Temsilci', 'Veli Başkan'], array_column($yazi->uyeler, 'ad_soyad'));
        $this->assertTrue(collect($yazi->uyeler)->firstWhere('ad_soyad', 'Veli Başkan')['bas_uye']);
        $this->assertSame('Başkan (İşveren / İşveren Vekili)', collect($yazi->uyeler)->firstWhere('ad_soyad', 'Veli Başkan')['gorev']);

        // Üye çıkarılınca yazı güncellenir.
        $id = $this->firma->kurulUyeleri()->where('ad_soyad', 'Ayşe Temsilci')->value('id');
        $sayfa->call('uyeKaldir', $id);
        $this->assertSame(['Veli Başkan'], array_column($yazi->fresh()->uyeler, 'ad_soyad'));
    }

    public function test_atama_durumu_degisikligi_gosterir(): void
    {
        $this->uye('baskan', 'Veli Başkan');
        $this->assertFalse(KurulAtamaEgitim::atamaDurumu($this->firma)['var']);

        KurulAtamaEgitim::atamaYazisiHazirla($this->firma);
        $this->assertTrue(KurulAtamaEgitim::atamaDurumu($this->firma)['guncel']);

        // Yazı hazırlandıktan sonra (başka yoldan) üye eklenirse güncel değil.
        $this->uye('hekim', 'Dr. Hekim');
        $d = KurulAtamaEgitim::atamaDurumu($this->firma);
        $this->assertFalse($d['guncel']);
        $this->assertSame(['Dr. Hekim'], $d['eklenen']);
    }

    public function test_egitimi_olmayan_uyeler_listelenir_ve_egitim_formuna_aktarilir(): void
    {
        $this->uye('baskan', 'ALİ VELİ');
        $this->uye('hekim', 'Dr. Hekim');

        $this->assertSame(2, KurulAtamaEgitim::egitimDurumu($this->firma)['eksik']->count());

        // Kurul eğitimi alan (katıldı) üye eksik listesinden düşer — Türkçe İ/I farkı yok sayılır.
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'baslik_anahtari' => 'isg_kurulu', 'belge_tarihi' => now(), 'konu_secimleri' => [],
            'katilimcilar' => [['ad_soyad' => 'Ali Veli', 'tc' => null, 'gorev' => null, 'katildi' => true]]]);
        $d = KurulAtamaEgitim::egitimDurumu($this->firma);
        $this->assertSame(['Dr. Hekim'], $d['eksik']->pluck('ad_soyad')->all());
        $this->assertSame(1, $d['alan']);

        $aktarim = KurulAtamaEgitim::egitimAktarimi($this->firma);
        $this->assertSame('isg_kurulu', $aktarim['baslik_anahtari']);
        $this->assertSame(['Dr. Hekim'], array_column($aktarim['katilimcilar'], 'ad_soyad'));

        // Sayfadaki düğme: oturuma yazar ve Eğitim Katılım'a yönlendirir.
        Livewire::test(KurulSayfasi::class)->set('firmaId', $this->firma->id)->call('kurulEgitimFormunaGit')->assertRedirect();
        $this->assertSame('isg_kurulu', session('egitim_katilim_aktarim.baslik_anahtari'));
    }

    public function test_panel_ve_indirmeler(): void
    {
        $this->uye('baskan', 'Veli Başkan');

        $sayfa = Livewire::test(KurulSayfasi::class)->set('firmaId', $this->firma->id)
            ->assertSee('Atama ve eğitim')
            ->assertSee('Veli Başkan');

        $sayfa->call('atamaYazisiPdf')->assertFileDownloaded();
        $sayfa->call('atamaYazisiWord')->assertFileDownloaded();

        // Üyesi olmayan işyerinde panel görünmez.
        $bos = Firma::factory()->for($this->uzman)->create();
        Livewire::test(KurulSayfasi::class)->set('firmaId', $bos->id)->assertDontSee('Atama ve eğitim');
    }

    public function test_ana_sayfa_gorevlerine_duser_ve_tamamlaninca_kalkar(): void
    {
        $this->uye('baskan', 'Veli Başkan');

        $basliklar = fn () => GorevDurumu::tarihliGorevler($this->firma) ? collect(GorevDurumu::tarihliGorevler($this->firma))->pluck('baslik')->all() : [];

        $this->assertContains('İSG Kurulu atama yazısı', $basliklar());
        $this->assertContains('İSG Kurulu eğitimi', $basliklar());

        KurulAtamaEgitim::atamaYazisiHazirla($this->firma);
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'baslik_anahtari' => 'isg_kurulu', 'belge_tarihi' => now(), 'konu_secimleri' => [],
            'katilimcilar' => [['ad_soyad' => 'Veli Başkan', 'tc' => null, 'gorev' => null, 'katildi' => true]]]);

        $this->assertNotContains('İSG Kurulu atama yazısı', $basliklar());
        $this->assertNotContains('İSG Kurulu eğitimi', $basliklar());
    }

    public function test_egitim_katilimda_yalniz_aktarilan_uyeler_secili_gelir(): void
    {
        Calisan::factory()->for($this->firma)->count(5)->create();
        $this->uye('baskan', 'Veli Başkan');

        Livewire::test(KurulSayfasi::class)->set('firmaId', $this->firma->id)->call('kurulEgitimFormunaGit');

        $e = Livewire::test(\App\Filament\Pages\EgitimKatilim::class);
        $e->assertSet('baslikAnahtari', 'isg_kurulu')->assertSet('secilenCalisanIdler', []);
        $this->assertSame(['Veli Başkan'], array_column($e->get('manuelKatilimcilar'), 'ad_soyad'));
    }
}
