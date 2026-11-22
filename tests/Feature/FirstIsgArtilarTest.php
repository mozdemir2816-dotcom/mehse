<?php

namespace Tests\Feature;

use App\Models\DenetimKaydi;
use App\Models\EkipDuyurusu;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\IsgProfesyoneli;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\SahaBulgusu;
use App\Models\SahaDenetimi;
use App\Models\User;
use App\Support\DenetimKaydedici;
use App\Support\IsyeriDurumu;
use App\Support\KullaniciAyarlari;
use App\Support\OsgbKadroAnalizi;
use App\Support\PortfoyKarne;
use App\Support\SanalMufettis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstIsgArtilarTest extends TestCase
{
    use RefreshDatabase;

    private function kullanici(): User
    {
        return User::factory()->create(['aktif' => true, 'rol' => 'sahip']);
    }

    public function test_f1_kapali_devre_dof_ve_isveren_kanit_talebi(): void
    {
        $u = $this->kullanici();
        $this->actingAs($u);

        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);
        $b = SahaBulgusu::create([
            'firma_id' => $f->id,
            'uygunsuzluk' => 'Yüksekte çalışma korkuluğu eksik',
            'olasilik' => 4,
            'siddet' => 5,
            'kontrol_hiyerarsisi' => 'muhendislik',
        ]);

        $this->assertSame('Mühendislik', $b->kontrolHiyerarsisiEtiketi());
        $this->assertFalse($b->isverenOnayiBekliyorMu());

        // İşveren portalından kapanış kanıtı gönderir — doğrudan kapanmaz, onay bekler
        $b->update([
            'isveren_kapanis_talebi_at' => now(),
            'isveren_kapanis_gonderen' => 'Fabrika Müdürü',
            'isveren_kapanis_notu' => 'Korkuluk takıldı',
            'durum' => 'devam_ediyor',
        ]);
        $this->assertTrue($b->fresh()->isverenOnayiBekliyorMu());

        // İGU doğrular ve artık risk + etkinlik puanıyla kapatır
        $b->update([
            'hedef_olasilik' => 1,
            'hedef_siddet' => 3,
            'etkinlik_puani' => 5,
            'kapanis_tarihi' => today(),
            'kapanis_notu' => 'Yerinde doğrulandı',
            'durum' => 'kapandi',
        ]);
        $b->refresh();
        $this->assertSame(3, $b->artikRiskSkoru());
        $this->assertFalse($b->isverenOnayiBekliyorMu());
    }

    public function test_f2_periyodik_kontrol_uygun_degil_guvenlik_kilidi(): void
    {
        $u = $this->kullanici();
        $this->actingAs($u);

        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);
        $ekipman = IsEkipmani::create([
            'firma_id' => $f->id,
            'kategori' => 'kaldirma',
            'ekipman_adi' => 'Tavan Vinci 5 Ton',
            'aktif' => true,
            'son_muayene_tarihi' => today()->subDays(5),
            'sonraki_vize_tarihi' => today()->addMonths(12),
            'sonuc' => 'uygun_degil',
        ]);

        // Tarihi ileri olsa dahi "uygun_degil" ekipman asla yeşil/geçerli sayılmaz
        $this->assertSame('uygun_degil', $ekipman->vizeDurumu());
        $this->assertFalse(PortfoyKarne::firmaKriterKarsilarMi($f, 'periyodik_kontrol_raporu'));

        $surecler = collect(IsyeriDurumu::surecler($f))->keyBy('surec');
        $this->assertSame('gecikmis', $surecler['Periyodik kontroller']['durum']);
        $this->assertStringContainsString('UYGUN DEĞİL', $surecler['Periyodik kontroller']['sonuc']);

        $denetim = SanalMufettis::denetle($f);
        $eqp = collect($denetim['bulgular'])->firstWhere('kod', 'EQP-001');
        $this->assertSame('eksik', $eqp['durum']);
        $this->assertStringContainsString('UYGUN DEĞİL', $eqp['aksiyon']);

        // Uygunsuzluk giderilip "uygun" yapılınca geçerli olur
        $ekipman->update(['sonuc' => 'uygun']);
        $this->assertSame('gecerli', $ekipman->fresh()->vizeDurumu());
        $this->assertTrue(PortfoyKarne::firmaKriterKarsilarMi($f, 'periyodik_kontrol_raporu'));
    }

    public function test_f3_imzalanmis_risk_kilidi_yeni_versiyon_ve_md6_ekip_kontrolu(): void
    {
        $u = $this->kullanici();
        $this->actingAs($u);

        $f = Firma::factory()->create([
            'user_id' => $u->id,
            'aktif' => true,
            'isveren_ad' => null,
            'isveren_vekili' => null,
            'igu_id' => null,
            'isyeri_hekimi_id' => null,
        ]);

        $rd = RiskDegerlendirmesi::create([
            'firma_id' => $f->id,
            'yontem' => 'matris_5x5',
            'rapor_tarihi' => today(),
            'durum' => 'taslak',
            'revizyon_no' => 0,
        ]);

        RiskMaddesi::create([
            'risk_degerlendirmesi_id' => $rd->id,
            'sira' => 1,
            'tehlike' => 'Elektrik panosu açık',
            'risk' => 'Elektrik çarpması',
            'olasilik' => 3,
            'siddet' => 5,
            'skor' => 15,
        ]);

        $eksikler = $rd->md6EkipEksikleri();
        $this->assertNotEmpty($eksikler);
        $this->assertTrue(collect($eksikler)->contains(fn ($e) => str_contains($e, 'Destek Elemanı')));

        // İmzala & kilitle
        $rd->kilitle($u->id);
        $this->assertTrue($rd->fresh()->kilitliMi());
        $this->assertSame('yayinlandi', $rd->fresh()->durum);

        // Yeni versiyon aç: eski kilitli kalır, yeni taslak v1 maddeleriyle açılır
        $v2 = $rd->fresh()->yeniVersiyonAc('Yeni hat eklendi');
        $this->assertFalse($v2->kilitliMi());
        $this->assertSame('taslak', $v2->durum);
        $this->assertSame(1, (int) $v2->revizyon_no);
        $this->assertSame($rd->id, $v2->onceki_versiyon_id);
        $this->assertSame(1, $v2->maddeler()->count());
        $this->assertTrue($rd->fresh()->kilitliMi());
    }

    public function test_f4_f5_f6_osgb_kadro_sinif_uyusmazligi_muafiyet_tam_sureli_karne_ve_duyuru(): void
    {
        $u = $this->kullanici();
        $this->actingAs($u);

        $cUzman = IsgProfesyoneli::create([
            'user_id' => $u->id,
            'tip' => 'igu',
            'ad_soyad' => 'Ali Veli',
            'unvan' => 'C Sınıfı İş Güvenliği Uzmanı',
            'aktif' => true,
        ]);

        $f1 = Firma::factory()->create([
            'user_id' => $u->id,
            'aktif' => true,
            'tehlike_sinifi' => 'cok_tehlikeli',
            'calisan_sayisi' => 60,
            'igu_id' => $cUzman->id,
            'hekim_hizmeti_yok' => true,
            'tam_sureli_igu' => true,
        ]);

        // F4: Belge sınıfı uyuşmazlığı yakalanmalı (C sınıfı → Çok Tehlikeli)
        $uyusmaz = OsgbKadroAnalizi::sinifUyusmazliklari($u->id);
        $this->assertCount(1, $uyusmaz);
        $this->assertSame('C', $uyusmaz[0]['belge_sinifi']);

        // F4: "Hekim hizmetini biz vermiyoruz" muafiyeti Sanal Müfettiş & PortfoyKarne'de işler
        $checklist = collect(PortfoyKarne::firmaChecklistDetay($f1))->keyBy('anahtar');
        $this->assertTrue($checklist['hekim_atamasi']['muaf']);
        $mufettis = SanalMufettis::denetle($f1);
        $this->assertNull(collect($mufettis['bulgular'])->firstWhere('kod', 'ORG-002'));

        // F5: Tam süreli kilit çakışmayı yakalar
        $f2 = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);
        $cakisan = OsgbKadroAnalizi::tamSureliCakisanFirma($cUzman->id, 'igu', $f2->id);
        $this->assertNotNull($cakisan);
        $this->assertSame($f1->id, $cakisan->id);

        // F4: Kadro panosu & ihmal edilen firmalar
        $kadro = OsgbKadroAnalizi::kadroPanosu($u->id);
        $this->assertGreaterThan(0, $kadro['toplam_igu_dk']);
        $this->assertCount(1, $kadro['personeller']);
        $this->assertSame(100, $kadro['personeller'][0]['doluluk_yuzde']);

        $ihmal = OsgbKadroAnalizi::ihmalEdilenFirmalar($u->id);
        $this->assertGreaterThanOrEqual(2, count($ihmal));
        $this->assertNull($ihmal[0]['gecen_gun']);

        // F6: Uzman aylık karne 0-100 + erken uyarı
        $karneler = OsgbKadroAnalizi::uzmanKarneleri($u->id);
        $this->assertCount(1, $karneler);
        $this->assertNotEmpty($karneler[0]['uyarilar']);

        // F6: Ekip duyurusu okundu takibi
        $duyuru = EkipDuyurusu::create([
            'user_id' => $u->id,
            'baslik' => 'Yeni 2026 teftiş kontrol listesi',
            'icerik' => 'Tüm uzmanlar okusun.',
            'oncelik' => 'onemli',
            'okuyanlar' => [],
        ]);
        $this->assertFalse($duyuru->okuduMu($u->id));
        $duyuru->okunduIsaretle($u);
        $this->assertTrue($duyuru->fresh()->okuduMu($u->id));
        $this->assertSame(1, $duyuru->fresh()->okuyanSayisi());

        // F5: OSGB politika ayarları
        KullaniciAyarlari::kaydet($u, [
            'politika' => ['islak_imza_zorunlu' => true, 'merkezi_ziyaret_programi' => true],
        ]);
        $this->assertTrue(KullaniciAyarlari::islakImzaZorunluMu($u->fresh()));
        $this->assertTrue(KullaniciAyarlari::merkeziZiyaretProgramiMi($u->fresh()));
    }

    public function test_f7_denetim_kayitlari_ve_7_gun_hacim(): void
    {
        DenetimKaydedici::onbellekSifirla();
        $u = $this->kullanici();
        $this->actingAs($u);

        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);
        $this->assertGreaterThanOrEqual(1, DenetimKaydi::where('firma_id', $f->id)->count());

        $hacim = DenetimKaydedici::son7GunHacim($u->id);
        $this->assertCount(7, $hacim);
        $this->assertGreaterThanOrEqual(1, end($hacim)['sayi']);
    }

    public function test_f8_denetim_skor_trendi(): void
    {
        $u = $this->kullanici();
        $this->actingAs($u);

        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);
        SahaDenetimi::create([
            'firma_id' => $f->id,
            'denetim_tarihi' => today()->subDays(10),
            'denetleyen' => 'A Sınıfı İGU',
            'cevaplar' => [
                ['sonuc' => 'uygun'],
                ['sonuc' => 'uygun'],
                ['sonuc' => 'uygun_degil'],
                ['sonuc' => 'uygun'],
            ],
        ]);

        $trend = IsyeriDurumu::denetimSkorTrendi($f);
        $this->assertCount(1, $trend);
        $this->assertSame(75, $trend[0]['skor']);
    }

    public function test_f9_yeni_seo_araclari_ve_canli_demo_erisim(): void
    {
        $this->get('/araclar/ipc-ceza-hesaplama')->assertOk()->assertSee('6331 m.26');
        $this->get('/araclar/ilkyardimci-destek-elemani-hesaplama')->assertOk()->assertSee('Sertifikalı İlkyardımcı');
        $this->get('/araclar/fine-kinney-risk-hesaplama')->assertOk()->assertSee('Fine-Kinney');
        $this->get('/demo')->assertOk()->assertSee('CANLI DEMO OSGB');
        $this->get('/sitemap.xml')->assertOk()->assertSee('/demo')->assertSee('/araclar/ipc-ceza-hesaplama');
    }
}
