<?php

namespace Tests\Feature;

use App\Filament\Pages\UzaktanEgitimAtama;
use App\Filament\Pages\UzaktanEgitimTakip;
use App\Filament\Portal\Pages\EgitimIzle;
use App\Filament\Portal\Pages\Egitimlerim;
use App\Filament\Portal\Pages\KvkkOnay;
use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimGirisi;
use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Models\User;
use App\Support\EgitimTakibi;
use App\Support\UzaktanEgitimBelgesiUretici;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class UzaktanEgitimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek İnşaat A.Ş.', 'tehlike_sinifi' => 'az_tehlikeli']);
        $this->actingAs($this->uzman);
    }

    private function paketKur(int $ders = 2, int $soru = 6): EgitimPaketi
    {
        $paket = EgitimPaketi::create([
            'user_id' => $this->uzman->id,
            'ad' => 'İnşaat Uzaktan İSG Eğitimi',
            'gecme_puani' => 70,
            'video_zorunlu_yuzde' => 90,
            'sinav_soru_sayisi' => 5,
        ]);

        for ($i = 1; $i <= $ders; $i++) {
            $paket->dersler()->create(['sira' => $i, 'baslik' => "Ders $i", 'video_url' => 'https://youtu.be/abc'.$i.'DEFG', 'sure_sn' => 100]);
        }

        for ($i = 1; $i <= $soru; $i++) {
            $paket->sorular()->create([
                'soru' => "Soru $i?",
                'secenekler' => ['Yanlış', 'Doğru', 'Yanlış 2', 'Yanlış 3'],
                'dogru_index' => 1,
            ]);
        }

        return $paket;
    }

    private function calisanKur(string $eposta = 'ali@ornek.test'): Calisan
    {
        return Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Ali Veli', 'eposta' => $eposta, 'aktif' => true, 'sifre' => 'gecici123', 'kvkk_onay_at' => now()]);
    }

    private function portalGirisi(Calisan $calisan): void
    {
        Filament::setCurrentPanel('portal');
        $this->actingAs($calisan, 'calisan');
    }

    public function test_paket_kod_otomatik_uretilir_ve_video_saglayici_tespit_edilir(): void
    {
        $paket = $this->paketKur();

        $this->assertStringStartsWith('UE-'.now()->year.'-', $paket->kod);
        $this->assertSame('youtube', $paket->dersler->first()->saglayici);
        $this->assertSame('abc1DEFG', $paket->dersler->first()->youtubeId());
    }

    public function test_atama_yapinca_calisana_gecici_sifre_uretilir(): void
    {
        $paket = $this->paketKur();
        $calisan = $this->calisanKur();

        Livewire::test(UzaktanEgitimAtama::class)
            ->set('firmaId', $this->firma->id)
            ->set('paketId', $paket->id)
            ->call('calisanToggle', $calisan->id)
            ->call('ata')
            ->assertHasNoErrors();

        $atama = EgitimAtamasi::where('calisan_id', $calisan->id)->sole();
        $this->assertSame('atandi', $atama->durum);
        $this->assertSame($paket->id, $atama->egitim_paketi_id);
        $this->assertNotNull($calisan->fresh()->sifre);
    }

    public function test_yeni_paket_atanirken_calisanin_eski_atamalari_silinir(): void
    {
        $eski = $this->paketKur();
        $yeni = $this->paketKur();
        $calisan = $this->calisanKur();
        $baskasi = $this->calisanKur('veli@ornek.test');

        $eskiAtama = $this->atamaKur($eski, $calisan);
        $baskasininAtamasi = $this->atamaKur($eski, $baskasi);

        Livewire::test(UzaktanEgitimAtama::class)
            ->set('firmaId', $this->firma->id)
            ->set('paketId', $yeni->id)
            ->call('calisanToggle', $calisan->id)
            ->call('ata');

        $this->assertModelMissing($eskiAtama);
        $this->assertModelExists($baskasininAtamasi);
        $this->assertSame($yeni->id, EgitimAtamasi::where('calisan_id', $calisan->id)->sole()->egitim_paketi_id);
    }

    public function test_epostasiz_calisana_gecici_kullanici_kodu_uretilip_atanir(): void
    {
        $paket = $this->paketKur();
        $calisan = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Postasız Kişi', 'aktif' => true]);

        Livewire::test(UzaktanEgitimAtama::class)
            ->set('firmaId', $this->firma->id)
            ->set('paketId', $paket->id)
            ->call('calisanToggle', $calisan->id)
            ->call('ata')
            ->assertHasNoErrors();

        $this->assertSame(1, EgitimAtamasi::count());

        $kod = $calisan->fresh()->eposta;
        $this->assertNotNull($kod);
        $this->assertStringNotContainsString('@', $kod);
        $this->assertStringStartsWith('postasiz.kisi', $kod);
    }

    public function test_ayni_ad_soyadli_iki_epostasiz_calisana_farkli_kod_uretilir(): void
    {
        $paket = $this->paketKur();
        $a = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Aynı İsim', 'aktif' => true]);
        $b = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Aynı İsim', 'aktif' => true]);

        Livewire::test(UzaktanEgitimAtama::class)
            ->set('firmaId', $this->firma->id)
            ->set('paketId', $paket->id)
            ->call('calisanToggle', $a->id)
            ->call('calisanToggle', $b->id)
            ->call('ata');

        $this->assertNotSame($a->fresh()->eposta, $b->fresh()->eposta);
    }

    public function test_eksik_paket_atanmaz(): void
    {
        $paket = EgitimPaketi::create(['user_id' => $this->uzman->id, 'ad' => 'Boş paket', 'sinav_soru_sayisi' => 5]);
        $calisan = $this->calisanKur();

        Livewire::test(UzaktanEgitimAtama::class)
            ->set('firmaId', $this->firma->id)
            ->set('paketId', $paket->id)
            ->call('calisanToggle', $calisan->id)
            ->call('ata');

        $this->assertSame(0, EgitimAtamasi::count());
    }

    /** Portalda ön testi gönderip eğitim ekranına gelir. */
    private function portalAc(EgitimAtamasi $atama)
    {
        $b = Livewire::test(EgitimIzle::class, ['atama' => $atama->id]);

        if ($b->get('mod') === 'on_test') {
            foreach ($b->get('sinavSorulari') as $s) {
                $b->set('cevaplar.'.$s['id'], 0);
            }
            $b->call('onTestiGonder');
        }

        return $b;
    }

    /** Videoyu gerçek zamanlı izlet: her 15 sn'de bir rapor (zaman yolculuğuyla). */
    private function izlet($bilesen, int $dersId, int $saniye): void
    {
        for ($konum = 15; $konum <= $saniye; $konum += 15) {
            $this->travel(15)->seconds();
            $bilesen->call('dersIlerleme', $dersId, $konum, 15, 100);
        }
    }

    private function atamaKur(EgitimPaketi $paket, Calisan $calisan, string $tur = 'yenileme'): EgitimAtamasi
    {
        return EgitimAtamasi::create([
            'egitim_paketi_id' => $paket->id, 'calisan_id' => $calisan->id, 'egitim_turu' => $tur,
            'atayan_user_id' => $this->uzman->id, 'atandi_at' => now(), 'durum' => 'atandi',
        ]);
    }

    public function test_portal_akisi_on_test_dersi_izle_sinavi_gec_belge_al(): void
    {
        $paket = $this->paketKur(ders: 2, soru: 6);
        $calisan = $this->calisanKur();
        $atama = $this->atamaKur($paket, $calisan);

        $this->portalGirisi($calisan);

        // Md.16/1: önce seviye tespit testi; doğru cevaplar istemciye gönderilmez
        $bilesen = Livewire::test(EgitimIzle::class, ['atama' => $atama->id])->assertSet('mod', 'on_test');
        $this->assertArrayNotHasKey('dogru_index', $bilesen->get('sinavSorulari')[0]);
        foreach ($bilesen->get('sinavSorulari') as $s) {
            $bilesen->set('cevaplar.'.$s['id'], 1);
        }
        $bilesen->call('onTestiGonder')->assertSet('mod', 'izle');
        $this->assertSame(100, $atama->refresh()->on_test_puani);

        foreach ($paket->dersler as $d) {
            $this->izlet($bilesen, $d->id, 90);
        }

        $atama->refresh();
        $this->assertTrue($atama->tumDerslerIzlendiMi());
        $this->assertSame('devam', $atama->durum);
        $this->assertSame(180, $atama->izlenenSure());

        $bilesen->call('sinavaBasla')->assertSet('mod', 'sinav');
        $sorular = $bilesen->get('sinavSorulari');
        $this->assertCount(6, $sorular);
        $this->assertArrayNotHasKey('dogru_index', $sorular[0]);

        foreach ($sorular as $s) {
            $bilesen->set('cevaplar.'.$s['id'], 1);
        }
        $bilesen->call('sinaviGonder')->assertSet('mod', 'sonuc');

        $atama->refresh();
        $this->assertTrue($atama->basariliMi());
        $this->assertSame('onay_bekliyor', $atama->durum);   // çift onay: önce İGU, sonra İH
        $this->assertFalse($atama->belgeAcikMi());
        $this->assertSame(100, $atama->sonSinav()->puan);

        $atama->iguOnayla($this->uzman->id);
        $this->assertSame('ih_onay_bekliyor', $atama->refresh()->durum);
        $atama->ihOnayla($this->uzman->id);
        $this->assertSame('tamamlandi', $atama->refresh()->durum);

        // Oturum: giriş + son etkinlik (çıkış) + fiili izleme süresi
        $oturum = $atama->girisler()->sole();
        $this->assertNotNull($oturum->cikis_at);
        $this->assertSame(180, $oturum->izleme_sn);

        $yanit = UzaktanEgitimBelgesiUretici::pdf($atama->fresh());
        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_kvkk_onayi_olmadan_egitimler_acilmaz_onaydan_sonra_acilir(): void
    {
        $paket = $this->paketKur();
        $calisan = $this->calisanKur();
        $calisan->forceFill(['kvkk_onay_at' => null])->save();
        $atama = $this->atamaKur($paket, $calisan);
        $this->portalGirisi($calisan);

        Livewire::test(Egitimlerim::class)->assertRedirect(KvkkOnay::getUrl());
        Livewire::test(EgitimIzle::class, ['atama' => $atama->id])->assertRedirect(KvkkOnay::getUrl());

        // iki kutu işaretlenmeden onaylanmaz
        Livewire::test(KvkkOnay::class)->call('onayla');
        $this->assertNull($calisan->fresh()->kvkk_onay_at);

        Livewire::test(KvkkOnay::class)->set('aydinlatma', true)->set('taahhut', true)->call('onayla')
            ->assertRedirect(Egitimlerim::getUrl());
        $this->assertNotNull($calisan->fresh()->kvkk_onay_at);

        Livewire::test(Egitimlerim::class)->assertNoRedirect();
    }

    public function test_giris_gunlugune_ip_ve_cihaz_yazilir(): void
    {
        $calisan = $this->calisanKur();
        request()->headers->set('User-Agent', 'Mozilla/5.0 (Linux; Android 14) Chrome/130');

        $giris = EgitimGirisi::kaydet($calisan->id);

        $this->assertStringContainsString('Android 14', $giris->cihaz);
        $this->assertNotNull($giris->ip);
    }

    public function test_belge_qr_dogrulama_sayfasi_maskeli_gosterir_ve_gecersiz_kodu_reddeder(): void
    {
        $paket = $this->paketKur(ders: 1);
        $calisan = $this->calisanKur();
        $atama = $this->atamaKur($paket, $calisan);
        $atama->sinavSonuclari()->create(['deneme_no' => 1, 'puan' => 90, 'gecti' => true, 'cevaplar' => [], 'tamamlandi_at' => now()]);
        $atama->durumuTazele();
        $atama->iguOnayla($this->uzman->id);
        $atama->ihOnayla($this->uzman->id);

        $kod = $atama->dogrulamaKodu();
        $this->assertSame($kod, $atama->fresh()->dogrulamaKodu());   // kod sabit kalır

        $this->get('/belge-dogrula/'.$kod)
            ->assertOk()
            ->assertSee('BELGE GEÇERLİ')
            ->assertSee('A** V**')
            ->assertDontSee('Ali Veli');

        $this->get('/belge-dogrula/YOKTUR0000000000')->assertNotFound();

        $this->assertStringStartsWith('data:image/svg+xml;base64,', \App\Support\UzaktanEgitimBelgesiUretici::qr($atama));
    }

    public function test_ileri_sarma_ve_sahte_sure_sayilmaz(): void
    {
        $this->freezeTime();   // iki rapor arası gerçek süre testte sıfır kalsın
        $paket = $this->paketKur(ders: 1);
        $calisan = $this->calisanKur();
        $atama = $this->atamaKur($paket, $calisan);
        $this->portalGirisi($calisan);
        $b = $this->portalAc($atama);
        $dersId = $paket->dersler->first()->id;

        // Açar açmaz sona sarıp "95 sn izledim" der → yalnız ilk rapor payı (20 sn) sayılır
        $b->call('dersIlerleme', $dersId, 95, 95, 100);
        // Hemen ardından tekrar → geçen süre ~0, artış kabul edilmez
        $b->call('dersIlerleme', $dersId, 99, 60, 100);

        $il = $atama->ilerlemeler()->sole();
        $this->assertLessThanOrEqual(23, $il->izlenen_sn);
        $this->assertLessThanOrEqual(33, $il->son_konum_sn);   // konum oynatılan kadar ilerler
        $this->assertFalse($il->izlendi);

        // YouTube derste "izledim" butonu çalışmaz
        $b->call('dersTamamla', $dersId);
        $this->assertFalse($il->refresh()->izlendi);
    }

    public function test_tum_dersler_izlenmeden_sinav_acilmaz(): void
    {
        $paket = $this->paketKur(ders: 2);
        $calisan = $this->calisanKur();
        $atama = $this->atamaKur($paket, $calisan);

        $this->portalGirisi($calisan);
        $b = $this->portalAc($atama);
        $this->izlet($b, $paket->dersler->first()->id, 90);

        $b->call('sinavaBasla')->assertSet('mod', 'izle');
    }

    public function test_dusuk_puanda_basarisiz_uc_hak_dolunca_egitim_bastan(): void
    {
        $paket = $this->paketKur(ders: 1, soru: 6);
        $paket->update(['gecme_puani' => 40]);   // yönetmelik alt sınırı 60 uygulanır
        $calisan = $this->calisanKur();
        $atama = $this->atamaKur($paket, $calisan);
        $this->assertSame(60, $atama->gecmePuani());

        $this->portalGirisi($calisan);
        $b = $this->portalAc($atama);
        $dersId = $paket->dersler->first()->id;
        $this->izlet($b, $dersId, 90);

        foreach ([3, 2, 1] as $kalan) {
            $this->assertSame($kalan, $atama->refresh()->kalanSinavHakki());
            $b->call('sinavaBasla')->assertSet('mod', 'sinav');
            foreach ($b->get('sinavSorulari') as $s) {
                $b->set('cevaplar.'.$s['id'], 0); // hepsi yanlış
            }
            $b->call('sinaviGonder');
            $this->travel(1)->minutes();
        }

        // Md.16/3: üçüncü başarısızlıkta eğitim baştan — dersler sıfırlanır, hak yenilenir
        $atama->refresh();
        $this->assertFalse($atama->basariliMi());
        $this->assertSame(1, $atama->yeniden_baslatma);
        $this->assertSame(0, $atama->ilerlemeler()->count());
        $this->assertSame('atandi', $atama->durum);
        $this->assertSame(3, $atama->kalanSinavHakki());
        $this->assertSame(3, $atama->sinavSonuclari()->count());   // eski sınavlar kayıtta

        $b->call('tekrarDene')->call('sinavaBasla')->assertSet('mod', 'izle');   // önce dersler
    }

    public function test_aktif_katilim_cevabi_oturuma_yazilir(): void
    {
        $paket = $this->paketKur();
        $calisan = $this->calisanKur();
        $atama = $this->atamaKur($paket, $calisan);
        $this->portalGirisi($calisan);

        $b = $this->portalAc($atama);
        $this->assertNotEmpty($b->instance()->yoklamaSorulari);
        $this->assertArrayNotHasKey('dogru_index', $b->instance()->yoklamaSorulari[0]);

        $b->call('yoklamaCevap', $paket->sorular->first()->id, 2)->call('nabiz');

        $oturum = $atama->girisler()->sole();
        $this->assertSame(1, $oturum->yoklama_sayisi);
        $this->assertNotNull($oturum->cikis_at);

        // Uzun aradan sonra (sayfa açık kalmış) yeni oturum açılır
        $this->travel(2)->hours();
        $b->call('nabiz');
        $this->assertSame(2, $atama->girisler()->count());
    }

    public function test_tehlikeli_isyerinde_dorduncu_konu_yuz_yuze_beklenir(): void
    {
        $this->firma->update(['tehlike_sinifi' => 'cok_tehlikeli']);
        $paket = $this->paketKur();
        $mehmet = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Mehmet Yılmaz', 'gorev' => 'İşçi', 'eposta' => 'myilmaz1', 'aktif' => true]);
        $atama = $this->atamaKur($paket, $mehmet, 'ilk_defa');

        $this->tamamla($atama, onayli: false);

        // Sınav geçildi ama 4. modül yüz yüze: İGU onayı gelene dek belge kapalı, kayıt işlenmez.
        $atama->refresh();
        $this->assertSame('onay_bekliyor', $atama->durum);
        $this->assertTrue($atama->iguOnayiBekliyorMu());
        $this->assertFalse($atama->belgeAcikMi());
        $this->assertSame(0, $mehmet->egitimKayitlari()->count());

        $this->assertSame('Uzaktan Bitti – Yüz Yüze Pratik Bekliyor', $atama->durumEtiketi());
        $this->hekimKur();

        $sayfa = Livewire::test(UzaktanEgitimTakip::class)
            ->set('firmaId', $this->firma->id)
            ->assertSee('Pratik + İGU Onayı')
            ->call('iguOnayla', $atama->id);

        $this->assertSame('ih_onay_bekliyor', $atama->refresh()->durum);
        $this->assertFalse($atama->belgeAcikMi());
        $sayfa->assertSee('İH Onayı')->call('ihOnayla', $atama->id);

        $atama->refresh();
        $this->assertSame('Dr. Ayşe Demir', $atama->ih_ad_soyad);
        $this->assertSame('IH-4521', $atama->ih_belge_no);
        $this->assertSame('tamamlandi', $atama->durum);
        $this->assertTrue($atama->belgeAcikMi());
        $this->assertNotNull($atama->igu_onay_at);
        $this->assertSame(1, $mehmet->egitimKayitlari()->count());   // onaydan sonra Eğitim Takibi'ne işlenir

        $html = view('pdf.uzaktan-egitim-belgesi', [
            'atama' => $atama, 'calisan' => $mehmet, 'firma' => $this->firma->fresh(), 'paket' => $paket, 'sinav' => $atama->sonSinav(),
        ])->render();
        $this->assertStringContainsString('TEMEL EĞİTİM BELGESİ', $html);
        $this->assertStringContainsString('Bağımlılık yapıcı maddelerin zararları ve teknoloji bağımlılığı', $html);
        $this->assertStringContainsString('Başlık 1, 2, 3', $html);
        $this->assertStringContainsString('4 — ayrıca yüz yüze verilir', $html);
    }

    public function test_isbasi_uzaktan_atanamaz_ve_sure_kontrolu(): void
    {
        $this->firma->update(['tehlike_sinifi' => 'tehlikeli']);
        $paket = $this->paketKur(ders: 2);   // 2 × 100 sn ≈ 4 dk
        $calisan = $this->calisanKur();

        $sayfa = Livewire::test(UzaktanEgitimAtama::class)
            ->set('firmaId', $this->firma->id)
            ->set('paketId', $paket->id)
            ->set('egitimTuru', 'ilk_defa');

        // Tehlikeli ilk defa: 12 saat − 4. konu 3 saat (yüz yüze) = 9 ders saati × 45 dk
        $k = $sayfa->instance()->sureKontrolu;
        $this->assertSame(405, $k['gereken_dk']);
        $this->assertFalse($k['yeterli']);
        $sayfa->assertSee('Süre yetersiz olabilir');

        $sayfa->set('egitimTuru', 'isbasi')->set('secilenCalisanlar', [$calisan->id])->call('ata');
        $this->assertSame(0, EgitimAtamasi::count());
    }

    public function test_temel_egitim_ise_giristen_uc_ay_icinde(): void
    {
        Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Yeni', 'aktif' => true, 'ise_giris' => now()->subMonths(2)->toDateString()]);

        $satir = EgitimTakibi::satirlar($this->firma)->first();
        $this->assertSame('kayit_yok', $satir['durum']);
        $this->assertSame(now()->subMonths(2)->addMonths(3)->toDateString(), $satir['ilk_son_tarih']->toDateString());
        $this->assertGreaterThan(0, $satir['ilk_kalan_gun']);
    }

    public function test_calisan_baska_atamayi_goremez(): void
    {
        $paket = $this->paketKur();
        $baskaCalisan = $this->calisanKur('baska@ornek.test');
        $atama = EgitimAtamasi::create([
            'egitim_paketi_id' => $paket->id, 'calisan_id' => $baskaCalisan->id,
            'atayan_user_id' => $this->uzman->id, 'atandi_at' => now(), 'durum' => 'atandi',
        ]);

        // "ben" de portala erişebilsin diye kendi ataması olsun.
        $ben = $this->calisanKur('ben@ornek.test');
        EgitimAtamasi::create([
            'egitim_paketi_id' => $paket->id, 'calisan_id' => $ben->id,
            'atayan_user_id' => $this->uzman->id, 'atandi_at' => now(), 'durum' => 'atandi',
        ]);
        $this->portalGirisi($ben);

        // Başkasının atamasına EgitimIzle 404 döner (mount'ta abort_unless).
        Livewire::test(EgitimIzle::class, ['atama' => $atama->id])
            ->assertStatus(404);
    }

    /** Dersleri izletir + sınavı geçirir; $onayli ise İGU ve İH onayları da verilir (çift onay, 08.11.2026). */
    private function tamamla(EgitimAtamasi $atama, bool $onayli = true): void
    {
        foreach ($atama->paket->dersler as $d) {
            $atama->ilerlemeler()->create(['egitim_dersi_id' => $d->id, 'izleme_yuzdesi' => 100, 'izlendi' => true, 'izlendi_at' => now()]);
        }
        $atama->sinavSonuclari()->create(['deneme_no' => 1, 'puan' => 90, 'gecti' => true, 'cevaplar' => [], 'tamamlandi_at' => now()]);
        $atama->durumuTazele();

        if ($onayli) {
            $atama->iguOnayla($this->uzman->id);
            $atama->ihOnayla($this->uzman->id);
        }
    }

    private function hekimKur(?Firma $firma = null): \App\Models\IsgProfesyoneli
    {
        $hekim = \App\Models\IsgProfesyoneli::create(['user_id' => $this->uzman->id, 'tip' => 'isyeri_hekimi', 'ad_soyad' => 'Dr. Ayşe Demir', 'sertifika_no' => 'IH-4521']);
        ($firma ?? $this->firma)->update(['isyeri_hekimi_id' => $hekim->id]);

        return $hekim;
    }

    public function test_egitime_giris_tarihleri_kaydedilir_ve_yenilemede_tekrar_yazilmaz(): void
    {
        $paket = $this->paketKur();
        $calisan = $this->calisanKur();
        $atama = EgitimAtamasi::create([
            'egitim_paketi_id' => $paket->id, 'calisan_id' => $calisan->id,
            'atayan_user_id' => $this->uzman->id, 'atandi_at' => now(), 'durum' => 'atandi',
        ]);

        // Portal girişi (kullanıcı kodu / e-posta) Login olayıyla günlüğe düşer
        event(new \Illuminate\Auth\Events\Login('calisan', $calisan, false));
        event(new \Illuminate\Auth\Events\Login('web', $this->uzman, false));   // uzman girişi sayılmaz

        $this->portalGirisi($calisan);
        Livewire::test(EgitimIzle::class, ['atama' => $atama->id]);
        Livewire::test(EgitimIzle::class, ['atama' => $atama->id]);   // sayfa yenileme → yeni satır yok

        $this->travel(2)->days();
        Livewire::test(EgitimIzle::class, ['atama' => $atama->id]);

        $this->assertSame(2, $atama->girisler()->count());
        $this->assertSame(1, EgitimGirisi::whereNull('egitim_atamasi_id')->count());

        $atama->delete();
        $this->assertSame(0, EgitimGirisi::whereNotNull('egitim_atamasi_id')->count());
    }

    public function test_temel_egitim_bitince_calisanin_egitim_kaydina_islenir(): void
    {
        $paket = $this->paketKur();
        $mehmet = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Mehmet Yılmaz', 'gorev' => 'İşçi', 'eposta' => 'myilmaz123', 'aktif' => true]);
        $mehmet->egitimKayitlari()->create(['tur' => EgitimTakibi::TEMEL_TUR, 'tarih' => now()->subYears(2)]);
        $atama = EgitimAtamasi::create([
            'egitim_paketi_id' => $paket->id, 'calisan_id' => $mehmet->id, 'egitim_turu' => 'yenileme',
            'atayan_user_id' => $this->uzman->id, 'atandi_at' => now(), 'durum' => 'atandi',
        ]);

        $this->tamamla($atama);

        $kayit = $mehmet->egitimKayitlari()->where('tur', EgitimTakibi::TEMEL_TUR)->sole();
        $this->assertSame(now()->toDateString(), $kayit->tarih->toDateString());
        $this->assertStringContainsString('Uzaktan eğitim', $kayit->notlar);
        $this->assertSame('gecerli', EgitimTakibi::satirlar($this->firma)->first()['durum']);

        // İşbaşı eğitimi temel eğitim kaydına yazılmaz
        $ayse = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Ayşe', 'eposta' => 'ayse1', 'aktif' => true]);
        $this->tamamla(EgitimAtamasi::create([
            'egitim_paketi_id' => $paket->id, 'calisan_id' => $ayse->id, 'egitim_turu' => 'isbasi',
            'atayan_user_id' => $this->uzman->id, 'atandi_at' => now(), 'durum' => 'atandi',
        ]));
        $this->assertSame(0, $ayse->egitimKayitlari()->count());
    }

    public function test_takip_detayi_video_oturum_ip_cihaz_ve_sinav_cevaplarini_gosterir(): void
    {
        $paket = $this->paketKur(ders: 2);
        $calisan = $this->calisanKur();
        $calisan->forceFill(['tc' => '12345678901', 'kvkk_onay_at' => now(), 'kvkk_onay_ip' => '10.1.2.3'])->save();
        $atama = $this->atamaKur($paket, $calisan);
        $ders = $paket->dersler()->first();

        $giris = EgitimGirisi::create(['calisan_id' => $calisan->id, 'egitim_atamasi_id' => $atama->id, 'ip' => '203.0.113.9',
            'cihaz' => 'Mozilla/5.0 (Linux; Android 14) Chrome/130', 'giris_at' => now()]);
        $giris->nabiz(95, true);
        $atama->ilerlemeler()->create(['egitim_dersi_id' => $ders->id, 'izleme_yuzdesi' => 95, 'izlenen_sn' => 95, 'izlendi' => true, 'izlendi_at' => now()]);
        $atama->sinavSonuclari()->create(['deneme_no' => 1, 'puan' => 50, 'gecti' => false, 'tamamlandi_at' => now(), 'cevaplar' => [
            ['soru' => 'Haftalık çalışma süresi?', 'secenekler' => ['35', '40', '45'], 'dogru_index' => 2, 'verilen_index' => 0],
            ['soru' => 'Bildirim süresi?', 'secenekler' => ['1 gün', '3 iş günü'], 'dogru_index' => 1, 'verilen_index' => 1],
        ]]);

        Livewire::test(UzaktanEgitimTakip::class)
            ->set('firmaId', $this->firma->id)
            ->call('detayAc', $atama->id)
            ->assertSee('Takip Detayı')
            ->assertSee('203.0.113.9')
            ->assertSee('Android 14')
            ->assertSee('10.1.2.3')
            ->assertSee('123******01')
            ->assertDontSee('12345678901')
            ->assertSee('1. deneme')
            ->assertSee('Haftalık çalışma süresi?')
            ->assertSee('Doğru:')
            ->call('detayAc', $atama->id)
            ->assertDontSee('Takip Detayı');
    }

    public function test_baska_firmanin_atamasinin_detayi_acilmaz(): void
    {
        $paket = $this->paketKur();
        $digerFirma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Diğer Firma']);
        $yabanci = Calisan::create(['firma_id' => $digerFirma->id, 'ad_soyad' => 'Yabancı Kişi', 'eposta' => 'y@ornek.test', 'aktif' => true, 'sifre' => 'x12345678']);
        $atama = $this->atamaKur($paket, $yabanci);

        Livewire::test(UzaktanEgitimTakip::class)
            ->set('firmaId', $this->firma->id)
            ->call('detayAc', $atama->id)
            ->assertDontSee('Yabancı Kişi');
    }

    public function test_atama_ekrani_gorev_giris_tarihi_tamamlandi_ve_excel(): void
    {
        $paket = $this->paketKur();
        $mehmet = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Mehmet Yılmaz', 'gorev' => 'İşçi', 'eposta' => 'myilmaz123', 'aktif' => true]);
        $atama = EgitimAtamasi::create([
            'egitim_paketi_id' => $paket->id, 'calisan_id' => $mehmet->id, 'egitim_turu' => 'ilk_defa',
            'atayan_user_id' => $this->uzman->id, 'atandi_at' => now(), 'durum' => 'atandi',
        ]);
        EgitimGirisi::create(['calisan_id' => $mehmet->id, 'egitim_atamasi_id' => $atama->id, 'giris_at' => now()->setDate(2026, 9, 28)->setTime(10, 15)]);
        $this->tamamla($atama);

        Livewire::test(UzaktanEgitimTakip::class)
            ->set('firmaId', $this->firma->id)
            ->assertSee('İşçi')
            ->assertSee('28.09.2026 10:15')
            ->assertSee('☑ Tamamlandı')
            ->assertSee('kayda işlendi')
            ->call('takipExcel')
            ->assertFileDownloaded('uzaktan-egitim-takibi-ornek-insaat-as.xlsx');
    }

    public function test_on_test_cevaplari_kaydedilir_ve_final_ayri_havuzdan_gelir(): void
    {
        $paket = $this->paketKur(ders: 1, soru: 0);
        $onTest = $finalSoru = [];
        foreach (range(1, 4) as $i) {
            $onTest[] = $paket->sorular()->create(['soru' => "Ön soru $i?", 'secenekler' => ['A', 'B'], 'dogru_index' => 1, 'kullanim' => 'on_test'])->id;
            $finalSoru[] = $paket->sorular()->create(['soru' => "Final soru $i?", 'secenekler' => ['A', 'B'], 'dogru_index' => 1, 'kullanim' => 'final'])->id;
        }
        $ortak = $paket->sorular()->create(['soru' => 'Ortak soru?', 'secenekler' => ['A', 'B'], 'dogru_index' => 1])->id;
        $paket->update(['sinav_soru_sayisi' => 20]);

        $calisan = $this->calisanKur();
        $atama = $this->atamaKur($paket, $calisan);
        $this->portalGirisi($calisan);

        $b = Livewire::test(EgitimIzle::class, ['atama' => $atama->id])->assertSet('mod', 'on_test');
        $ids = collect($b->get('sinavSorulari'))->pluck('id')->sort()->values()->all();
        $this->assertSame(collect([...$onTest, $ortak])->sort()->values()->all(), $ids);

        // İlk soruyu yanlış, kalanları doğru cevapla
        foreach ($b->get('sinavSorulari') as $i => $s) {
            $b->set('cevaplar.'.$s['id'], $i === 0 ? 0 : 1);
        }
        $b->call('onTestiGonder')->assertSet('mod', 'izle');

        $atama->refresh();
        $this->assertSame(80, $atama->on_test_puani);
        $this->assertCount(5, $atama->on_test_cevaplar);
        $this->assertSame(0, $atama->on_test_cevaplar[0]['verilen_index']);
        $this->assertSame(1, $atama->on_test_cevaplar[0]['dogru_index']);

        $this->izlet($b, $paket->dersler()->first()->id, 90);
        $b->call('sinavaBasla')->assertSet('mod', 'sinav');
        $finalIds = collect($b->get('sinavSorulari'))->pluck('id')->sort()->values()->all();
        $this->assertSame(collect([...$finalSoru, $ortak])->sort()->values()->all(), $finalIds);

        Filament::setCurrentPanel('admin');
        $this->actingAs($this->uzman);
        Livewire::test(UzaktanEgitimTakip::class)
            ->set('firmaId', $this->firma->id)
            ->call('detayAc', $atama->id)
            ->assertSee('Ön test (seviye tespiti')
            ->assertSee('4/5 doğru')
            ->assertSee('Ön soru');
    }

    public function test_eski_paket_soru_kullanimi_yoksa_tum_havuz_kullanilir(): void
    {
        $paket = $this->paketKur(soru: 6);

        $this->assertCount(6, $paket->sinavSorulari('on_test'));
        $this->assertCount(6, $paket->sinavSorulari('final'));
    }

    public function test_tum_firmalar_listesi_suzgec_ve_detaya_gecis(): void
    {
        $paket = $this->paketKur();
        $ikinciFirma = Firma::factory()->for($this->uzman)->create(['unvan' => 'İkinci Lojistik', 'tehlike_sinifi' => 'az_tehlikeli']);
        $baskaUzmanFirmasi = Firma::factory()->create(['unvan' => 'Başkasının Firması']);

        $ali = $this->calisanKur();
        $zeynep = Calisan::create(['firma_id' => $ikinciFirma->id, 'ad_soyad' => 'Zeynep Kaya', 'eposta' => 'z@ornek.test', 'aktif' => true]);
        $yabanci = Calisan::create(['firma_id' => $baskaUzmanFirmasi->id, 'ad_soyad' => 'Gizli Kişi', 'eposta' => 'g@ornek.test', 'aktif' => true]);

        $this->atamaKur($paket, $ali);
        $zAtama = $this->atamaKur($paket, $zeynep);
        $this->atamaKur($paket, $yabanci);
        $this->tamamla($zAtama);

        $sayfa = Livewire::test(UzaktanEgitimTakip::class)
            ->assertSee('Tüm Firmalar — Eğitim Takibi')
            ->assertSee('Ali Veli')
            ->assertSee('Zeynep Kaya')
            ->assertSee('İkinci Lojistik')
            ->assertDontSee('Gizli Kişi')
            ->set('genelFiltre', 'tamamlandi')
            ->assertSee('Zeynep Kaya')
            ->assertDontSee('Ali Veli')
            ->call('detayaGit', $zAtama->id)
            ->assertSet('firmaId', $ikinciFirma->id)
            ->assertSet('detayId', $zAtama->id)
            ->assertSee('Takip Detayı — Zeynep Kaya')
            ->assertDontSee('Tüm Firmalar — Eğitim Takibi');

        $this->assertNotNull($sayfa);
    }

    public function test_sinav_oncesi_ve_sonrasi_havuzdan_onar_soru_ceker(): void
    {
        $paket = $this->paketKur(soru: 0);
        foreach (['ortak' => 12, 'on_test' => 4, 'final' => 4] as $kullanim => $adet) {
            for ($i = 1; $i <= $adet; $i++) {
                $paket->sorular()->create(['soru' => "$kullanim $i?", 'secenekler' => ['A', 'B'], 'dogru_index' => 0, 'kullanim' => $kullanim]);
            }
        }

        $on = collect($paket->sinavSorulari('on_test'));
        $final = collect($paket->sinavSorulari('final'));
        $this->assertCount(10, $on);
        $this->assertCount(10, $final);
        $this->assertFalse($on->contains(fn ($s) => str_starts_with($s['soru'], 'final ')));
        $this->assertFalse($final->contains(fn ($s) => str_starts_with($s['soru'], 'on_test ')));
    }

    public function test_sinav_sorulari_sekmesinde_soru_eklenir_ve_havuz_ozeti_gorunur(): void
    {
        $paket = $this->paketKur(soru: 0);
        $geriAl = \Filament\Forms\Components\Repeater::fake();

        $sayfa = Livewire::test(\App\Filament\Pages\UzaktanEgitimSorulari::class)
            ->assertSet('paketId', $paket->id)
            ->assertSee('Sınav öncesi (ön test) havuzu')
            ->callAction(\Filament\Actions\Testing\TestAction::make('create')->table(), [
                'kullanim' => 'on_test', 'soru' => 'Yangında ilk ne yapılır?', 'secenekler' => [['secenek' => 'Kaç'], ['secenek' => 'Alarm ver']], 'dogru_index' => 1,
            ])->assertHasNoFormErrors();

        $soru = $paket->sorular()->sole();
        $this->assertSame('on_test', $soru->kullanim);
        $this->assertSame(['Kaç', 'Alarm ver'], array_values($soru->secenekler));
        $this->assertSame(['on_test' => 1, 'final' => 0], array_intersect_key($sayfa->instance()->ozet, ['on_test' => 0, 'final' => 0]));
        $sayfa->assertSee('Yangında ilk ne yapılır?')->assertSee('9 soru daha ekleyin');
        $geriAl();
    }

    public function test_takipte_firma_secmeden_onay_ve_sertifika(): void
    {
        $this->firma->update(['tehlike_sinifi' => 'cok_tehlikeli']);
        $paket = $this->paketKur();
        $calisan = $this->calisanKur();
        $atama = $this->atamaKur($paket, $calisan, 'ilk_defa');
        $this->tamamla($atama, onayli: false);
        $this->hekimKur();

        $sayfa = Livewire::test(UzaktanEgitimTakip::class)
            ->assertSee('İGU Onayı')
            ->call('iguOnayla', $atama->id)
            ->call('ihOnayla', $atama->id);

        $this->assertTrue($atama->refresh()->belgeAcikMi());
        $sayfa->set('genelFiltre', 'sertifika')->assertSee('Ali Veli')->assertSee('Sertifika');
        $this->assertNotNull($sayfa->instance()->belgeIndir($atama->id));
    }

    public function test_video_bitince_siradaki_videoya_gecis_baglantisi(): void
    {
        $paket = $this->paketKur(ders: 2);
        $calisan = $this->calisanKur();
        $atama = $this->atamaKur($paket, $calisan);
        $atama->forceFill(['on_test_puani' => 50, 'on_test_at' => now()])->save();
        [$ilk, $ikinci] = $paket->dersler->all();

        $this->portalGirisi($calisan);

        Livewire::test(EgitimIzle::class, ['atama' => $atama->id, 'ders' => $ilk->id])
            ->assertSee('?ders='.$ikinci->id.'\u0026oto=1', false);
        Livewire::test(EgitimIzle::class, ['atama' => $atama->id, 'ders' => $ikinci->id])
            ->assertSee('const sonrakiUrl = null', false);
    }

    public function test_final_sorulari_konulara_dagilir_ve_onceki_denemedekiler_tekrar_gelmez(): void
    {
        $paket = $this->paketKur(soru: 0);
        foreach ([1, 2, 3] as $modul) {
            for ($i = 1; $i <= 8; $i++) {
                $paket->sorular()->create(['soru' => "M$modul S$i?", 'secenekler' => ['A', 'B'], 'dogru_index' => 0, 'kullanim' => 'final', 'modul' => $modul]);
            }
        }

        $ilk = collect($paket->sinavSorulari('final'));
        $this->assertCount(10, $ilk);
        $dagilim = $ilk->countBy(fn ($s) => substr($s['soru'], 0, 2));
        $this->assertSame(3, $dagilim->count());
        $this->assertTrue($dagilim->every(fn ($n) => $n >= 3 && $n <= 4));

        $ikinci = collect($paket->sinavSorulari('final', $ilk->pluck('id')->all()));
        $this->assertCount(10, $ikinci);
        $this->assertEmpty($ikinci->pluck('id')->intersect($ilk->pluck('id')));
    }

    public function test_takipte_giris_bilgileri_denetim_tutanagi_ve_hatirlatma(): void
    {
        $paket = $this->paketKur();
        $ali = $this->calisanKur();
        $zeynep = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Zeynep Kaya', 'eposta' => 'zk1', 'telefon' => '05321234567', 'aktif' => true, 'sifre' => 'eski123', 'sifre_belirlendi_at' => now()]);
        $this->atamaKur($paket, $ali);
        $zAtama = $this->atamaKur($paket, $zeynep);
        $this->hekimKur();
        EgitimGirisi::create(['calisan_id' => $zeynep->id, 'egitim_atamasi_id' => $zAtama->id, 'giris_at' => now(), 'ip' => '198.51.100.7']);

        $sayfa = Livewire::test(UzaktanEgitimTakip::class)->set('firmaId', $this->firma->id)
            ->assertSee('Hatırlatma — eğitimi tamamlamayanlar (2)')
            ->assertSee('wa.me/905321234567', false);

        $eskiHash = $ali->fresh()->sifre;
        $this->assertInstanceOf(StreamedResponse::class, $sayfa->instance()->girisBilgileriIndir('excel'));
        $this->assertNotSame($eskiHash, $ali->fresh()->sifre);                 // kendi şifresini belirlemeyene yeni geçici şifre
        $this->assertSame($zeynep->sifre, $zeynep->fresh()->sifre);             // kendi şifresini belirleyene dokunulmaz

        $pdf = $sayfa->instance()->girisBilgileriIndir('pdf');
        ob_start(); $pdf->sendContent(); $this->assertStringStartsWith('%PDF', ob_get_clean());

        $html = view('pdf.uzaktan-egitim-denetim-tutanagi', [
            'firma' => $this->firma->fresh(['igu', 'isyeriHekimi']), 'atamalar' => $sayfa->instance()->atamalar, 'uzman' => $this->uzman,
        ])->render();
        $this->assertStringContainsString('KATILIM VE KAYIT (LOG) TUTANAĞI', $html);
        $this->assertStringContainsString('198.51.100.7', $html);
        $this->assertStringContainsString('Dr. Ayşe Demir', $html);
        $tutanak = $sayfa->instance()->denetimTutanagi();
        ob_start(); $tutanak->sendContent(); $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_ek2_belgede_hekim_ve_konu_sureleri_yazar(): void
    {
        $this->firma->update(['tehlike_sinifi' => 'tehlikeli']);
        $paket = $this->paketKur(ders: 1);
        $paket->dersler()->update(['modul' => 2]);
        $calisan = $this->calisanKur();
        $this->hekimKur();
        $atama = $this->atamaKur($paket, $calisan, 'ilk_defa');
        $this->tamamla($atama);
        $atama->ilerlemeler()->update(['izlenen_sn' => 600]);

        $html = view('pdf.uzaktan-egitim-belgesi', [
            'atama' => $atama->fresh(), 'calisan' => $calisan, 'firma' => $this->firma->fresh(), 'paket' => $paket, 'sinav' => $atama->sonSinav(),
        ])->render();
        $this->assertStringContainsString('Dr. Ayşe Demir', $html);
        $this->assertStringContainsString('IH-4521', $html);
        $this->assertStringContainsString('10 dk', $html);                      // sağlık konuları fiili süre
        $this->assertStringContainsString('3 ders saati (yüz yüze)', $html);    // tehlikeli: 4. konu yüz yüze
    }

    public function test_oynaticida_kalan_zorunlu_sure_gosterilir(): void
    {
        $paket = $this->paketKur(ders: 2);   // 2 × 100 sn, %90 zorunlu → 180 sn
        $calisan = $this->calisanKur();
        $atama = $this->atamaKur($paket, $calisan);
        $atama->forceFill(['on_test_puani' => 50, 'on_test_at' => now()])->save();

        $this->portalGirisi($calisan);
        Livewire::test(EgitimIzle::class, ['atama' => $atama->id])
            ->assertSee('Kalan zorunlu süre')
            ->assertSee('0:03:00');
    }
}
