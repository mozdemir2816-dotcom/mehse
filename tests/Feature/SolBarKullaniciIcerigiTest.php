<?php

namespace Tests\Feature;

use App\Filament\Pages\KullaniciYonetimi;
use App\Filament\Pages\Profilim;
use App\Filament\Pages\ZiyaretProgrami;
use App\Models\Firma;
use App\Models\User;
use App\Support\CanliKonumTakibi;
use App\Support\KomutaFirma;
use App\Support\KullaniciAyarlari;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sol bar kullanıcı alanı (sidebar-alt), Profil & Kaşe/İmza, Kullanıcı Yetkileri ve
 * OSGB canlı konum görünürlüğü testleri.
 */
class SolBarKullaniciIcerigiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_sidebar_alt_paylasilan_firma_ve_sahip_icin_aktif_isyeri_baglamini_gosterir_ve_temizler(): void
    {
        $sahip = User::factory()->create(['rol' => 'sahip', 'name' => 'OSGB Sahibi']);
        $uzman = User::factory()->kisitli()->create(['name' => 'Saha Uzmanı']);

        $paylasilanFirma = Firma::factory()->create(['user_id' => $sahip->id, 'unvan' => 'Paylaşılan Makina A.Ş.']);
        $uzmanFirmasi = Firma::factory()->create(['user_id' => $uzman->id, 'unvan' => 'Uzman Şantiye Ltd.']);

        DB::table('kullanici_firmalar')->insert([
            'user_id' => $uzman->id,
            'firma_id' => $paylasilanFirma->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Kısıtlı uzman paylaşılan firmayı aktif işyeri olarak görebilmeli
        $this->actingAs($uzman);
        KomutaFirma::sec($paylasilanFirma->id);
        $htmlUzman = view('filament.components.sidebar-alt')->render();
        $this->assertStringContainsString('Paylaşılan Makina A.Ş.', $htmlUzman);
        $this->assertStringContainsString('mehse-sidebar-alt-profil-link', $htmlUzman);
        // saha-ekrani yetkisi yoksa telefon ikonu gizlenmeli (403 önlemi)
        $this->assertStringNotContainsString('Telefon Saha Uygulaması', $htmlUzman);

        // Sahip başka uzmanın firmasını da bağlam kartında görebilmeli
        $this->actingAs($sahip);
        KomutaFirma::sec($uzmanFirmasi->id);
        $htmlSahip = view('filament.components.sidebar-alt')->render();
        $this->assertStringContainsString('Uzman Şantiye Ltd.', $htmlSahip);
        $this->assertStringContainsString('Telefon Saha Uygulaması', $htmlSahip);
        $this->assertStringContainsString('mehse-sidebar-alt-disli', $htmlSahip);

        // Aktif firma temizleme rotası oturumu temizlemeli
        $this->postJson(route('mehse.aktif-firma.temizle'))
            ->assertOk()
            ->assertJson(['ok' => true]);
        $this->assertNull(KomutaFirma::oturumdaki());
    }

    public function test_canli_konum_osgb_sahibi_ekranlarinda_gorunur(): void
    {
        $sahip = User::factory()->create(['rol' => 'sahip', 'name' => 'OSGB Sahibi']);
        $uzman = User::factory()->kisitli()->create(['name' => 'Ali Saha Uzmanı']);

        $this->actingAs($uzman);
        $this->postJson(route('mehse.konum.bildir'), [
            'aktif' => true,
            'enlem' => 40.1885,
            'boylam' => 29.0610,
        ])->assertOk()->assertJson(['ok' => true, 'aktif' => true]);

        $konumlar = CanliKonumTakibi::aktifKonumlar();
        $this->assertCount(1, $konumlar);
        $this->assertSame('Ali Saha Uzmanı', $konumlar[0]['name']);
        $this->assertStringContainsString('google.com/maps?q=40.188500,29.061000', (string) $konumlar[0]['harita_url']);

        // Sahip Kullanıcı Yetkileri ve Ziyaret Programı'nda canlı konumu görmeli
        $this->actingAs($sahip);
        Livewire::test(KullaniciYonetimi::class)
            ->assertSee('Canlı Saha Konumu Paylaşan Ekip')
            ->assertSee('Ali Saha Uzmanı')
            ->assertSee('Haritada Gör');

        Livewire::test(ZiyaretProgrami::class)
            ->assertSee('Sahada Konumu Açık Ekip')
            ->assertSee('Ali Saha Uzmanı');
    }

    public function test_profilim_sertifika_rozeti_kase_kutuphanesi_ve_isg_katip_sure_ozetini_gosterir(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uzman-kase/kase1.png', 'png1');
        Storage::disk('public')->put('uzman-kase/kase2.png', 'png2');

        $uzman = User::factory()->create([
            'sertifika_no' => 'IGU-2026-9988',
            'sertifika_gecerlilik' => now()->addDays(45)->toDateString(),
            'kase_gorseli' => 'uzman-kase/kase1.png',
        ]);

        KullaniciAyarlari::kaydet($uzman, [
            'kase_kutuphanesi' => [
                ['id' => 'k1', 'ad' => 'A Sınıfı Kaşe', 'yol' => 'uzman-kase/kase1.png', 'eklenme' => '2026-01-01'],
                ['id' => 'k2', 'ad' => 'Yedek Kaşe', 'yol' => 'uzman-kase/kase2.png', 'eklenme' => '2026-02-01'],
            ],
        ]);

        Firma::factory()->for($uzman)->create([
            'unvan' => 'Örnek Metal A.Ş.',
            'tehlike_sinifi' => 'cok_tehlikeli',
            'calisan_sayisi' => 10,
            'aktif' => true,
        ]);

        $this->actingAs($uzman->fresh());

        Livewire::test(Profilim::class)
            ->assertSee('Belge No: IGU-2026-9988')
            ->assertSee('Kayıtlı Kaşe Kütüphanesi')
            ->assertSee('Yedek Kaşe')
            ->assertSee('İSG-KATİP Aylık Yasal Hizmet Süreleri')
            ->assertSee('400 dk') // 10 * 40 dk (çok tehlikeli İGU)
            ->call('kaseKutuphanedenSec', 'k2')
            ->assertNotified();

        $this->assertSame('uzman-kase/kase2.png', $uzman->fresh()->kase_gorseli);

        // Kullanılmayan kaşe kütüphaneden kaldırılabilmeli
        Livewire::test(Profilim::class)
            ->call('kaseKutuphanedenSil', 'k1')
            ->assertNotified();

        $kalan = $uzman->fresh()->ayarlar['kase_kutuphanesi'] ?? [];
        $this->assertCount(1, $kalan);
        $this->assertSame('k2', $kalan[0]['id']);
    }

    public function test_kullanici_yonetimi_arama_rol_sablonu_ve_temizleme_calisir(): void
    {
        $sahip = User::factory()->create(['rol' => 'sahip']);
        $uzman = User::factory()->kisitli()->create(['name' => 'Yeni Hekim']);
        $f1 = Firma::factory()->create(['user_id' => $sahip->id, 'unvan' => 'Anadolu Çelik A.Ş.']);
        $f2 = Firma::factory()->create(['user_id' => $sahip->id, 'unvan' => 'Ege Tekstil Ltd.']);

        $this->actingAs($sahip);

        Livewire::test(KullaniciYonetimi::class)
            ->call('ac', $uzman->id)
            ->call('rolSablonuUygula', 'hekim')
            ->assertSet('sayfaSecim.muayene-formu', true)
            ->assertSet('sayfaSecim.saglik-gozetimi', true)
            ->assertSet('sayfaSecim.risk-sihirbazi', false)
            ->set('sayfaArama', 'muayene')
            ->assertSee('Muayene Formu')
            ->set('firmaArama', 'çelik')
            ->assertSee('Anadolu Çelik A.Ş.')
            ->assertDontSee('Ege Tekstil Ltd.')
            ->call('tumFirmalariSec')
            ->assertSet("firmaSecim.{$f1->id}", true)
            ->assertSet("firmaSecim.{$f2->id}", true)
            ->call('tumFirmalariKaldir')
            ->assertSet('firmaSecim', [])
            ->call('tumSayfalariKaldir')
            ->assertSet('sayfaSecim.muayene-formu', false);
    }
}
