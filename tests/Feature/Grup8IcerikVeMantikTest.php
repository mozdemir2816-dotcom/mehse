<?php

namespace Tests\Feature;

use App\Filament\Pages\CariHesap;
use App\Filament\Pages\Guvenlik;
use App\Filament\Pages\KullaniciYonetimi;
use App\Filament\Pages\ZiyaretProgrami;
use App\Filament\Resources\RiskDegerlendirmesis\Pages\EditRiskDegerlendirmesi;
use App\Models\CanliImza;
use App\Models\DenetimKaydi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Models\ZiyaretProgrami as ZiyaretProgramiModel;
use App\Support\CanliImzaYonetici;
use App\Support\CariHesapYonetici;
use App\Support\KomutaFirma;
use App\Support\KullaniciAyarlari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class Grup8IcerikVeMantikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        KullaniciAyarlari::onbellegiTemizle();
    }

    public function test_sahip_merkezi_ziyaret_politikasini_acinca_uzman_kilitlenir_sahip_serbest_kalir(): void
    {
        $sahip = User::factory()->create(['rol' => 'sahip']);
        KullaniciAyarlari::kaydet($sahip, ['politika' => ['merkezi_ziyaret_programi' => true, 'islak_imza_zorunlu' => false]]);

        $uzman = User::factory()->create(['rol' => 'kullanici']);
        $uzman->sayfaYetkileri()->create(['sayfa_anahtari' => 'ziyaret-programi']);
        $firma = Firma::factory()->create(['user_id' => $uzman->id]);

        $this->actingAs($uzman);
        $this->assertTrue(KullaniciAyarlari::merkeziZiyaretProgramiMi($uzman));

        $test = Livewire::withQueryParams(['firma' => $firma->id])->test(ZiyaretProgrami::class)
            ->assertSee('Ziyaret programı merkezden yönetiliyor');

        $programOnce = ZiyaretProgramiModel::firmaYilIcin($firma, (int) now()->format('Y'));
        $ilkSatirSayisi = count(ZiyaretProgramiModel::ayGirdileri($programOnce->ziyaretler[0] ?? null));

        $test->call('ziyaretEkle', 0)
            ->assertNotified('Ziyaret programı merkezi olarak yönetiliyor');

        $programSonra = $programOnce->fresh();
        $this->assertCount($ilkSatirSayisi, ZiyaretProgramiModel::ayGirdileri($programSonra->ziyaretler[0] ?? null));

        // Sahip hesap kendi firmasında satır ekleyebilmeli
        $sahipFirma = Firma::factory()->create(['user_id' => $sahip->id]);
        $this->actingAs($sahip);

        Livewire::withQueryParams(['firma' => $sahipFirma->id])->test(ZiyaretProgrami::class)
            ->assertDontSee('Ziyaret programı merkezden yönetiliyor')
            ->call('ziyaretEkle', 0);

        $sahipProgram = ZiyaretProgramiModel::firmaYilIcin($sahipFirma, (int) now()->format('Y'))->fresh();
        $this->assertCount(2, ZiyaretProgramiModel::ayGirdileri($sahipProgram->ziyaretler[0] ?? null));
    }

    public function test_islak_imza_zorunlu_politikasi_risk_degerlendirmesi_ust_basliginda_hatirlatma_gosterir(): void
    {
        $sahip = User::factory()->create(['rol' => 'sahip']);
        KullaniciAyarlari::kaydet($sahip, ['politika' => ['islak_imza_zorunlu' => true, 'merkezi_ziyaret_programi' => false]]);

        $firma = Firma::factory()->create(['user_id' => $sahip->id]);
        $rd = RiskDegerlendirmesi::create([
            'user_id' => $sahip->id,
            'firma_id' => $firma->id,
            'firma_unvan' => $firma->unvan,
            'yontem' => 'matris_5x5',
            'hazirlama_tarihi' => now()->toDateString(),
            'gecerlilik_tarihi' => now()->addYears(4)->toDateString(),
            'tehlike_sinifi' => 'tehlikeli',
        ]);

        $this->actingAs($sahip);

        Livewire::test(EditRiskDegerlendirmesi::class, ['record' => $rd->getRouteKey()])
            ->assertSee('Islak / canlı imza taraması zorunludur');

        // İmzalı canlı imza eklendiğinde hatırlatma kalkar
        CanliImza::create([
            'user_id' => $sahip->id,
            'firma_id' => $firma->id,
            'belge_turu' => 'risk_degerlendirmesi',
            'belge_id' => $rd->id,
            'belge_baslik' => 'Risk Değerlendirmesi',
            'imza_token' => str_repeat('a', 40),
            'calisan_adi' => 'Ali Yılmaz',
            'imzalandi_at' => now(),
            'dogrulama_kodu' => 'DOGRULAMA123456789',
        ]);

        Livewire::test(EditRiskDegerlendirmesi::class, ['record' => $rd->getRouteKey()])
            ->assertDontSee('Islak / canlı imza taraması zorunludur');
    }

    public function test_cari_hesap_kdv_haric_net_ciro_prim_hesabi_ve_komuta_firma_senkronu(): void
    {
        $user = User::factory()->create(['rol' => 'sahip']);
        $firmaA = Firma::factory()->create(['user_id' => $user->id, 'unvan' => 'Alpha A.Ş.', 'aktif' => true, 'katip_aylik_dk' => 120]);
        $firmaB = Firma::factory()->create(['user_id' => $user->id, 'unvan' => 'Beta Ltd.', 'aktif' => true, 'katip_aylik_dk' => 60]);

        CariHesapYonetici::ucretKaydet($user, $firmaA->id, 10000.0, 20);
        CariHesapYonetici::ucretKaydet($user, $firmaB->id, 5000.0, 20);
        $eklenen = CariHesapYonetici::buAyTahakkukOlustur($user, '2026-11');
        $this->assertSame(2, $eklenen);
        $this->assertSame(0, CariHesapYonetici::buAyTahakkukOlustur($user, '2026-11'));

        $ozet = CariHesapYonetici::portfoyOzeti($user->fresh());
        $this->assertSame(15000.0, $ozet['aylik_ciro_net']);
        $this->assertSame(18000.0, $ozet['aylik_ciro']);

        $this->actingAs($user);
        KomutaFirma::sec($firmaB->id);

        Livewire::test(CariHesap::class)
            ->assertSet('firmaId', $firmaB->id)
            ->assertSee('KDV Hariç Net:')
            ->assertSee('15.000,00 ₺')
            ->call('firmaSec', $firmaA->id)
            ->callAction('personelHakedis', data: [
                'prim_yuzde' => 10,
                'saatlik_birim' => 400,
            ])
            ->assertNotified('Aylık Hakediş Özeti');

        $this->assertSame($firmaA->id, KomutaFirma::oturumdaki());
    }

    public function test_guvenlik_denetim_modulleri_kapsami_ve_kullanici_yonetimi_denetim_kaydi(): void
    {
        $sahip = User::factory()->create(['rol' => 'sahip']);
        $uzman = User::factory()->create(['rol' => 'kullanici', 'name' => 'Deneme Uzman']);

        DenetimKaydi::create([
            'user_id' => $sahip->id,
            'kullanici_adi' => $sahip->name,
            'modul' => 'Cari Hesap',
            'islem' => 'sildi',
            'ozet' => 'Sahip hareketi',
        ]);
        DenetimKaydi::create([
            'user_id' => $uzman->id,
            'kullanici_adi' => $uzman->name,
            'modul' => 'Saha Denetimi',
            'islem' => 'ekledi',
            'ozet' => 'Uzman kaydı',
        ]);

        // Uzman sadece kendi modülünü görür
        $this->actingAs($uzman);
        $uzmanPage = Livewire::test(Guvenlik::class)->instance();
        $this->assertSame(['Saha Denetimi'], $uzmanPage->denetimModulleri);

        // Sahip tüm modülleri görür + kullanıcı yetki / aktiflik değişikliği loglanır
        $this->actingAs($sahip);
        $sahipPage = Livewire::test(Guvenlik::class)->instance();
        $this->assertContains('Cari Hesap', $sahipPage->denetimModulleri);
        $this->assertContains('Saha Denetimi', $sahipPage->denetimModulleri);

        Livewire::test(KullaniciYonetimi::class)
            ->call('ac', $uzman->id)
            ->set('abonelikPlani', 'osgb')
            ->call('kaydet')
            ->call('aktifDegistir', $uzman->id);

        $this->assertDatabaseHas('denetim_kayitlari', [
            'user_id' => $sahip->id,
            'modul' => 'Kullanıcı Yetkileri',
            'islem' => 'guncelledi',
        ]);
        $this->assertSame(
            2,
            DenetimKaydi::where('user_id', $sahip->id)->where('modul', 'Kullanıcı Yetkileri')->count(),
        );
    }

    public function test_canli_imza_pad_noindex_ve_opsiyonel_tc_algoritma_dogrulamasi(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['rol' => 'sahip']);
        $firma = Firma::factory()->create(['user_id' => $user->id]);

        $imza = CanliImzaYonetici::oturumOlustur($user, $firma, 'egitim_katilim', null, 'Temel İSG Eğitimi');

        // 1) Pad sayfası arama motorlarına kapalı olmalı
        $this->get(route('canli-imza.form', ['token' => $imza->imza_token]))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        // Küçük geçerli PNG (200+ byte)
        $sahtePng = 'data:image/png;base64,'.base64_encode(str_repeat('PNGDATA1234567890', 20));

        // 2) Geçersiz T.C. reddedilmeli
        $this->post(route('canli-imza.kaydet', ['token' => $imza->imza_token]), [
            'calisan_adi' => 'Mehmet Kaya',
            'calisan_tc' => '11111111111',
            'imza_veri' => $sahtePng,
            'onay' => '1',
        ])->assertSessionHasErrors('calisan_tc');

        // 3) Geçerli T.C. ile başarıyla kaydedilmeli (10000000146 geçerli TC algoritması)
        $this->post(route('canli-imza.kaydet', ['token' => $imza->imza_token]), [
            'calisan_adi' => 'Mehmet Kaya',
            'calisan_tc' => '10000000146',
            'imza_veri' => $sahtePng,
            'onay' => '1',
        ])->assertRedirect(route('canli-imza.form', ['token' => $imza->imza_token]))
            ->assertSessionHasNoErrors();

        $imza->refresh();
        $this->assertTrue($imza->imzalandiMi());
        $this->assertSame('10000000146', $imza->calisan_tc);

        // 4) Boş T.C. yine opsiyonel olarak kabul edilmeli
        $imza2 = CanliImzaYonetici::oturumOlustur($user, $firma, 'kkd_zimmet', null, 'KKD Teslimi');
        $this->post(route('canli-imza.kaydet', ['token' => $imza2->imza_token]), [
            'calisan_adi' => 'Ayşe Demir',
            'calisan_tc' => '',
            'imza_veri' => $sahtePng,
            'onay' => '1',
        ])->assertRedirect(route('canli-imza.form', ['token' => $imza2->imza_token]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($imza2->fresh()->imzalandiMi());
    }
}
