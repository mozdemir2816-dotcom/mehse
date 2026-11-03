<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumEkipleri;
use App\Filament\Pages\CalisanTemsilcisiSecimi;
use App\Models\AcilEkip;
use App\Models\AtamaYazisi;
use App\Models\Calisan;
use App\Models\CalisanTemsilcisiSecimi as Secim;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\User;
use App\Support\EkipAtamaEgitim;
use App\Support\GorevDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Acil durum ekipleri ve çalışan temsilcisi: değişince atama yazısı otomatik, eğitim hatırlatma (03.11.2026). */
class EkipAtamaEgitimTest extends TestCase
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

    private function ekip(string $tur = 'sondurme'): AcilEkip
    {
        return AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => $tur, 'ad' => config('isg.acil_durum.ekip_turleri.'.$tur.'.ad', $tur)]);
    }

    public function test_acil_ekip_uyesi_eklenince_atama_yazisi_otomatik_hazirlanir_ve_kopya_birikmez(): void
    {
        $ekip = $this->ekip();
        $c = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Ali Söndürücü', 'tc' => '11111111111', 'gorev' => 'Usta']);

        $sayfa = Livewire::test(AcilDurumEkipleri::class)->set('firmaId', $this->firma->id)
            ->callAction('uyeEkle', ['acil_ekip_id' => $ekip->id, 'calisan_id' => $c->id, 'ad_soyad' => 'Ali Söndürücü', 'gorev' => 'Usta', 'uyelik' => 'asil', 'lider' => true]);

        $yazi = AtamaYazisi::where('firma_id', $this->firma->id)->where('rol_anahtari', 'sondurme_ekibi')->firstOrFail();
        $this->assertSame('Ali Söndürücü', $yazi->uyeler[0]['ad_soyad']);
        $this->assertSame('11111111111', $yazi->uyeler[0]['tc']);
        $this->assertTrue($yazi->uyeler[0]['bas_uye']);

        // İkinci üye: aynı günün kaydı güncellenir.
        $sayfa->callAction('uyeEkle', ['acil_ekip_id' => $ekip->id, 'ad_soyad' => 'Veli Yardımcı', 'uyelik' => 'asil']);
        $this->assertSame(1, AtamaYazisi::where('firma_id', $this->firma->id)->where('rol_anahtari', 'sondurme_ekibi')->count());
        $this->assertSame(['Ali Söndürücü', 'Veli Yardımcı'], array_column($yazi->fresh()->uyeler, 'ad_soyad'));

        // Üye çıkarılınca yazı güncellenir.
        $veli = $ekip->uyeler()->where('ad_soyad', 'Veli Yardımcı')->value('id');
        $sayfa->call('uyeSil', $veli);
        $this->assertSame(['Ali Söndürücü'], array_column($yazi->fresh()->uyeler, 'ad_soyad'));
    }

    public function test_ekip_egitimi_belge_veya_egitim_kaydina_gore_hesaplanir(): void
    {
        $ekip = $this->ekip('ilk_yardim');
        $ekip->uyeler()->create(['ad_soyad' => 'Belgesiz Kişi', 'uyelik' => 'asil']);
        $ekip->uyeler()->create(['ad_soyad' => 'Belgeli Kişi', 'uyelik' => 'asil', 'belge_no' => 'B-1', 'belge_tarihi' => now()->subMonth()]);
        $ekip->uyeler()->create(['ad_soyad' => 'KATILIM KAYITLI', 'uyelik' => 'asil']);
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'baslik_anahtari' => 'ilkyardim_ekibi', 'belge_tarihi' => now(), 'konu_secimleri' => [],
            'katilimcilar' => [['ad_soyad' => 'Katılım Kayıtlı', 'tc' => null, 'gorev' => null, 'katildi' => true]]]);

        $ekip = EkipAtamaEgitim::acilEkipler($this->firma)->first();
        $d = EkipAtamaEgitim::acilEgitimDurumu($ekip);
        $this->assertSame(['Belgesiz Kişi'], $d['eksik']->pluck('ad_soyad')->all());

        $aktarim = EkipAtamaEgitim::acilEgitimAktarimi($ekip);
        $this->assertSame('ilkyardim_ekibi', $aktarim['baslik_anahtari']);
        $this->assertSame(['Belgesiz Kişi'], array_column($aktarim['katilimcilar'], 'ad_soyad'));

        // Sayfa düğmesi oturuma yazıp Eğitim Katılım'a yönlendirir.
        Livewire::test(AcilDurumEkipleri::class)->set('firmaId', $this->firma->id)->call('ekipEgitimFormunaGit', $ekip->id)->assertRedirect();
        $this->assertSame('ilkyardim_ekibi', session('egitim_katilim_aktarim.baslik_anahtari'));
    }

    public function test_temsilci_kazanan_kaydedilince_atama_yazisi_hazirlanir_tekrar_acilmaz(): void
    {
        Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Ayşe Temsilci', 'tc' => '22222222222']);
        $secim = Secim::firmaIcin($this->firma);
        $secim->forceFill(['adaylar' => [['ad_soyad' => 'Ayşe Temsilci', 'unvan' => 'Kaynakçı']], 'secilen_aday_index' => 0])->save();

        Livewire::test(CalisanTemsilcisiSecimi::class)->set('firmaId', $this->firma->id)->call('kaydetVeBildir');

        $yazi = AtamaYazisi::where('firma_id', $this->firma->id)->where('rol_anahtari', 'calisan_temsilcisi')->firstOrFail();
        $this->assertSame('Ayşe Temsilci', $yazi->uyeler[0]['ad_soyad']);
        $this->assertSame('22222222222', $yazi->uyeler[0]['tc']);
        $this->assertSame('Kaynakçı', $yazi->uyeler[0]['gorev']);

        // Tekrar kaydedince aynı kişi için ikinci yazı açılmaz.
        Livewire::test(CalisanTemsilcisiSecimi::class)->set('firmaId', $this->firma->id)->call('kaydetVeBildir');
        $this->assertSame(1, AtamaYazisi::where('firma_id', $this->firma->id)->where('rol_anahtari', 'calisan_temsilcisi')->count());

        // Eğitim henüz yok → aktarılır; eğitim kaydı girilince düşer.
        $this->assertSame(['Ayşe Temsilci'], EkipAtamaEgitim::temsilciEgitimDurumu($this->firma)['eksik']->pluck('ad_soyad')->all());
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'baslik_anahtari' => 'calisan_temsilcisi', 'belge_tarihi' => now(), 'konu_secimleri' => [],
            'katilimcilar' => [['ad_soyad' => 'AYŞE TEMSİLCİ', 'tc' => null, 'gorev' => null, 'katildi' => true]]]);
        $this->assertTrue(EkipAtamaEgitim::temsilciEgitimDurumu($this->firma)['eksik']->isEmpty());
    }

    public function test_temsilci_atama_yazisindan_ve_kurul_uyesinden_de_taninir_aktarim_calisir(): void
    {
        $this->firma->kurulUyeleri()->create(['rol' => 'calisan_temsilcisi', 'ad_soyad' => 'Kurul Temsilci', 'aktif' => true]);
        AtamaYazisi::create(['firma_id' => $this->firma->id, 'rol_anahtari' => 'calisan_temsilcisi', 'tarih' => now()->subMonths(3), 'uyeler' => [['ad_soyad' => 'Yazıdaki Temsilci', 'gorev' => 'Usta']]]);
        AtamaYazisi::create(['firma_id' => $this->firma->id, 'rol_anahtari' => 'calisan_temsilcisi', 'tarih' => now()->subYears(5), 'uyeler' => [['ad_soyad' => 'Eski Temsilci']]]);

        $ad = EkipAtamaEgitim::temsilciler($this->firma)->pluck('ad_soyad')->all();
        $this->assertEqualsCanonicalizing(['Kurul Temsilci', 'Yazıdaki Temsilci'], $ad, '5 yıl önceki temsilci güncel sayılmaz');

        Livewire::test(CalisanTemsilcisiSecimi::class)->set('firmaId', $this->firma->id)->call('temsilciEgitimFormunaGit')->assertRedirect();
        $this->assertSame('calisan_temsilcisi', session('egitim_katilim_aktarim.baslik_anahtari'));
    }

    public function test_ana_sayfa_gorevleri_acil_ekip_ve_temsilci(): void
    {
        $ekip = $this->ekip();
        $ekip->uyeler()->create(['ad_soyad' => 'Belgesiz Kişi', 'uyelik' => 'asil']);
        $this->firma->kurulUyeleri()->create(['rol' => 'calisan_temsilcisi', 'ad_soyad' => 'Kurul Temsilci', 'aktif' => true]);

        $basliklar = fn () => collect(GorevDurumu::tarihliGorevler($this->firma))->pluck('baslik')->all();

        $b = $basliklar();
        foreach (['Acil durum ekibi atama yazıları', 'Acil durum ekibi eğitimi', 'Çalışan temsilcisi atama yazısı', 'Çalışan temsilcisi eğitimi'] as $beklenen) {
            $this->assertContains($beklenen, $b);
        }

        // Hepsi tamamlanınca görevler kalkar.
        EkipAtamaEgitim::acilHepsiniHazirla($this->firma);
        $ekip->uyeler()->update(['belge_no' => 'B-1', 'belge_tarihi' => now()->subMonth()]);
        AtamaYazisi::create(['firma_id' => $this->firma->id, 'rol_anahtari' => 'calisan_temsilcisi', 'tarih' => now(), 'uyeler' => [['ad_soyad' => 'Kurul Temsilci']]]);
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'baslik_anahtari' => 'calisan_temsilcisi', 'belge_tarihi' => now(), 'konu_secimleri' => [],
            'katilimcilar' => [['ad_soyad' => 'Kurul Temsilci', 'tc' => null, 'gorev' => null, 'katildi' => true]]]);

        $this->assertSame([], array_intersect($basliklar(), ['Acil durum ekibi atama yazıları', 'Acil durum ekibi eğitimi', 'Çalışan temsilcisi atama yazısı', 'Çalışan temsilcisi eğitimi']));
    }
}
