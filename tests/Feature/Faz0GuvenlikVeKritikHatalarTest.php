<?php

namespace Tests\Feature;

use App\Filament\Pages\KontrolMerkezi;
use App\Models\AcilDurumPlani;
use App\Models\Calisan;
use App\Models\CanliEgitimOturumu;
use App\Models\CezaTebligTutanagi;
use App\Models\EgitimKaydi;
use App\Models\EkipDuyurusu;
use App\Models\Firma;
use App\Models\IsKazasiRaporu;
use App\Models\KazaIstatistigi;
use App\Models\KkdZimmetFormu;
use App\Models\NaceKodu;
use App\Models\SaglikGozetimi;
use App\Models\TabelaTasarimi;
use App\Models\TaseronBasvurusu;
use App\Models\TatbikatTutanagi;
use App\Models\User;
use App\Support\AcilDurumHazirlik;
use App\Support\ArsivYukleyici;
use App\Support\CanliEgitimYonetici;
use App\Support\EgitimTakibi;
use App\Support\GuvenliFoto;
use App\Support\IsKazasiIstatistikUretici;
use App\Support\KontrolKartiUretici;
use App\Support\PortfoyKarne;
use App\Support\SmsGonderici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Faz0GuvenlikVeKritikHatalarTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_route_cross_tenant_idor_ve_svg_xss_engeller(): void
    {
        Storage::fake('public');

        $u1 = User::factory()->create(['rol' => 'kullanici', 'aktif' => true]);
        $u2 = User::factory()->create(['rol' => 'kullanici', 'aktif' => true]);
        $f1 = Firma::create(['user_id' => $u1->id, 'unvan' => 'Tenant 1 A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        Storage::disk('public')->put("arsiv/{$f1->id}/2026/gizli.pdf", '%PDF-1.4 test');
        Storage::disk('public')->put('test.svg', '<svg><script>alert(1)</script></svg>');

        // Başka tenant arsiv/{f1->id} dosyasına erişemez
        $this->actingAs($u2)
            ->get("/storage/arsiv/{$f1->id}/2026/gizli.pdf")
            ->assertForbidden();

        // Sahibi erişebilir
        $this->actingAs($u1)
            ->get("/storage/arsiv/{$f1->id}/2026/gizli.pdf")
            ->assertOk();

        // SVG dosyası sandbox CSP ve attachment ile döner
        $resp = $this->actingAs($u1)->get('/storage/test.svg');
        $resp->assertOk();
        $this->assertStringContainsString('sandbox', (string) $resp->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('attachment', (string) $resp->headers->get('Content-Disposition'));
    }

    public function test_ekip_duyurusu_multi_tenant_izole_calisir(): void
    {
        $u1 = User::factory()->create(['rol' => 'kullanici', 'aktif' => true]);
        $u2 = User::factory()->create(['rol' => 'kullanici', 'aktif' => true]);

        $d1 = EkipDuyurusu::create([
            'user_id' => $u1->id,
            'baslik' => 'Tenant 1 Gizli Duyuru',
            'icerik' => 'İç talimat',
            'oncelik' => 'normal',
            'okuyanlar' => [],
        ]);

        $this->assertNotContains($u1->id, EkipDuyurusu::ekipKullaniciIdleri($u2));
        $this->assertContains($u1->id, EkipDuyurusu::ekipKullaniciIdleri($u1));

        $this->actingAs($u2);
        $sayfa = new KontrolMerkezi;
        $sayfa->ekipDuyurusuOkundu($d1->id);

        $this->assertFalse($d1->fresh()->okuduMu($u2->id));
    }

    public function test_kontrol_karti_path_traversal_ve_tabela_svg_xss_engeller(): void
    {
        $this->assertNull(KontrolKartiUretici::foto('../.env'));
        $this->assertNull(KontrolKartiUretici::foto('uzman-imza/baska.png'));

        $temiz = TabelaTasarimi::temizle([
            'slotCustomImages' => [
                'data:image/svg+xml;base64,PHN2Zz48c2NyaXB0PmFsZXJ0KDEpPC9zY3JpcHQ+PC9zdmc+',
                'data:image/png;base64,iVBORw0KGgo=',
            ],
        ]);
        $this->assertNull($temiz['slotCustomImages'][0]);
        $this->assertNotNull($temiz['slotCustomImages'][1]);
    }

    public function test_arsiv_yukleyici_zararli_uzantilari_reddeder_ve_guvenli_foto_kontrolu(): void
    {
        Storage::fake('public');
        $u = User::factory()->create(['aktif' => true]);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Test Ltd.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        Storage::disk('public')->put('arsiv/gecici/kotu.php', '<?php echo 1;');
        Storage::disk('public')->put('arsiv/gecici/xss.html', '<script>alert(1)</script>');

        $this->assertNull(ArsivYukleyici::birlestir(['arsiv/gecici/kotu.php'], $f, 'Test'));
        $this->assertNull(ArsivYukleyici::birlestir(['arsiv/gecici/xss.html'], $f, 'Test'));

        $sahtePhp = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');
        $this->assertFalse(GuvenliFoto::gecerliMi($sahtePhp));
        $this->assertNull(GuvenliFoto::yukle($sahtePhp, 'dof-foto'));
    }

    public function test_sms_sifresi_sifreli_saklanir_ve_geriye_donuk_cozulur(): void
    {
        $sifreli = SmsGonderici::sifreSifrele('cok-gizli-123');
        $this->assertStringStartsWith('enc:', $sifreli);
        $this->assertNotSame('cok-gizli-123', $sifreli);
        $this->assertSame('cok-gizli-123', SmsGonderici::sifreCoz($sifreli));
        $this->assertSame('eski-duz-sifre', SmsGonderici::sifreCoz('eski-duz-sifre'));
    }

    public function test_kao_carpani_1000_ve_olumlu_kaza_ramak_kala_sayilmaz(): void
    {
        $u = User::factory()->create(['aktif' => true]);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Sanayi A.Ş.', 'tehlike_sinifi' => 'cok_tehlikeli', 'aktif' => true]);

        $ki = KazaIstatistigi::create([
            'firma_id' => $f->id,
            'yil' => 2026,
            'standart' => 'turkiye_1m',
            'aylik_veriler' => [['ay' => 1, 'calisan' => 100, 'calisma_saati' => 200000]],
            'harici_kazalar' => [['tarih' => '2026-01-15', 'kayip_gunu' => 20, 'olumlu' => false, 'aciklama' => 'Test']],
        ]);

        // KSO = 1 * 1.000.000 / 200.000 = 5.0
        // KAO = 20 * 1.000 / 200.000 = 0.1 (eskiden 100.0 çıkıyordu!)
        $this->assertSame(5.0, $ki->siklikOrani());
        $this->assertSame(0.1, $ki->agirlikOrani());

        // Ölümlü kaza (kayip_gun_sayisi = 0 olsa bile) ağır kaza sayılmalı, asla ramak kala sayılmamalı
        IsKazasiRaporu::create([
            'firma_id' => $f->id,
            'kaza_tarihi' => '2026-02-01',
            'kazazede_ad_soyad' => 'Ali Yılmaz',
            'agirlik_derecesi' => 'olumlu',
            'kayip_gun_sayisi' => 0,
        ]);

        $ist = IsKazasiIstatistikUretici::hesapla($f);
        $this->assertSame(1, $ist['kpi']['agir_kaza']);
        $this->assertSame(0, $ist['kpi']['ramak_kala']);
    }

    public function test_portfoy_karne_uygun_degil_saglik_kaydini_gecerli_saymaz(): void
    {
        $u = User::factory()->create(['aktif' => true]);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Sağlık Test A.Ş.', 'tehlike_sinifi' => 'cok_tehlikeli', 'aktif' => true]);
        $c1 = Calisan::create(['firma_id' => $f->id, 'ad_soyad' => 'Uygun Çalışan', 'aktif' => true]);
        $c2 = Calisan::create(['firma_id' => $f->id, 'ad_soyad' => 'Uygun Değil Çalışan', 'aktif' => true]);

        SaglikGozetimi::create([
            'firma_id' => $f->id,
            'satirlar' => [
                ['calisan_id' => $c1->id, 'calisan_adi' => $c1->ad_soyad, 'tetkik_turu' => 'periyodik', 'tarih' => now()->toDateString(), 'sonraki_tarih' => now()->addYear()->toDateString(), 'sonuc' => 'uygun'],
                ['calisan_id' => $c2->id, 'calisan_adi' => $c2->ad_soyad, 'tetkik_turu' => 'periyodik', 'tarih' => now()->toDateString(), 'sonraki_tarih' => now()->addYear()->toDateString(), 'sonuc' => 'uygun_degil'],
            ],
        ]);

        $karne = PortfoyKarne::calisanKarne($f);
        $eksikIdler = $karne['muayene_eksik']->pluck('id')->all();

        $this->assertNotContains($c1->id, $eksikIdler);
        $this->assertContains($c2->id, $eksikIdler);
    }

    public function test_acil_durum_hazirlik_yapildi_tatbikati_gorur_ve_egitim_takibi_en_yeni_kaydi_alir(): void
    {
        $u = User::factory()->create(['aktif' => true]);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Tatbikat A.Ş.', 'tehlike_sinifi' => 'cok_tehlikeli', 'aktif' => true]);

        $plan = AcilDurumPlani::create(['firma_id' => $f->id, 'hazirlama_tarihi' => now()->toDateString()]);
        TatbikatTutanagi::create([
            'firma_id' => $f->id,
            'tatbikat_tarihi' => now()->subDays(10)->toDateString(),
            'durum' => 'yapildi',
        ]);

        $kontrol = AcilDurumHazirlik::kontrol($plan);
        $tatbikatKalemi = collect($kontrol['kontroller'])->firstWhere('baslik', 'Tatbikat (yılda en az bir)');
        $this->assertNotNull($tatbikatKalemi);
        $this->assertTrue($tatbikatKalemi['tamam']);

        // EgitimTakibi güncel temel eğitim kaydını geçerli saymalı
        $c = Calisan::create(['firma_id' => $f->id, 'ad_soyad' => 'Mehmet Kaya', 'aktif' => true]);
        EgitimKaydi::create(['calisan_id' => $c->id, 'tur' => EgitimTakibi::TEMEL_TUR, 'tarih' => now()->subDays(5)->toDateString()]);

        $satirlar = EgitimTakibi::satirlar($f);
        $satir = $satirlar->first();
        $this->assertSame('gecerli', $satir['durum']);
    }

    public function test_ucretsiz_nace_araci_gercek_nace_tablosundan_okur_ve_ipc_cift_carpan_yapmaz(): void
    {
        NaceKodu::create([
            'kod' => '62.01.01',
            'tanim' => 'Bilgisayar programlama faaliyetleri',
            'sektor_adi' => 'Bilişim',
            'tehlike_sinifi' => 'az_tehlikeli',
        ]);

        $this->get('/araclar/tehlike-sinifi-sorgulama?q=62.01.01')
            ->assertOk()
            ->assertSee('62.01.01')
            ->assertSee('Az Tehlikeli');

        $this->get('/araclar/ipc-ceza-hesaplama')
            ->assertOk()
            ->assertSee('data-taban="88663"', false)
            ->assertDontSee('data-max="177326"', false);
    }

    public function test_kkd_coklu_calisanda_canli_imza_diger_calisanlara_basilmaz_ve_ceza_imtina_pdf(): void
    {
        $u = User::factory()->create(['aktif' => true]);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'KKD Test Ltd.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        $form = KkdZimmetFormu::create([
            'firma_id' => $f->id,
            'form_no' => 'KKD-2026-001',
            'teslim_tarihi' => now()->toDateString(),
            'calisanlar' => [
                ['ad_soyad' => 'Ali', 'imza' => null],
                ['ad_soyad' => 'Veli', 'imza' => null],
            ],
            'kkdler' => [['ad' => 'Baret']],
        ]);

        $html = view('pdf.kkd-zimmet-formu', [
            'form' => $form,
            'firma' => $f,
            'canliImza' => 'data:image/png;base64,FAKE_SIG',
        ])->render();
        $this->assertStringNotContainsString('FAKE_SIG', $html);

        $tutanak = CezaTebligTutanagi::create([
            'firma_id' => $f->id,
            'calisan_ad_soyad' => 'Ayşe Demir',
            'olay_tarihi' => now()->toDateString(),
            'teblig_tarihi' => now()->toDateString(),
            'imza_durumu' => 'imtina_etti',
            'yaptirim' => 'yazili_uyari',
        ]);
        $cezaHtml = view('pdf.ceza-teblig-tutanagi', ['tutanak' => $tutanak, 'firma' => $f, 'canliImza' => 'data:image/png;base64,FAKE_SIG'])->render();
        $this->assertStringContainsString('İmzadan İmtina Etti', $cezaHtml);
        $this->assertStringContainsString('Tebliğ Eden (İşveren / Vekili)', $cezaHtml);
        $this->assertStringNotContainsString('FAKE_SIG', $cezaHtml);
    }

    public function test_canli_egitim_oturumu_gecme_notu_60_ve_soru_secenekleri_gecerli(): void
    {
        $u = User::factory()->create(['aktif' => true]);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Eğitim A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        $oturum = CanliEgitimYonetici::oturumOlustur($u, $f, 'temel_isg', 'Temel İSG', now()->toDateTimeString(), 'javascript:alert(1)');
        $this->assertSame(60, (int) $oturum->gecme_notu);
        $this->assertNull($oturum->toplanti_url);
        $this->assertCount(5, $oturum->sorular);
        foreach ($oturum->sorular as $s) {
            $this->assertArrayHasKey($s['dogru'], $s['secenekler']);
        }
    }
}
