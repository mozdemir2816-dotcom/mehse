<?php

namespace Tests\Feature;

use App\Filament\Pages\UzaktanEgitimAtama;
use App\Filament\Portal\Pages\EgitimIzle;
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
        return Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Ali Veli', 'eposta' => $eposta, 'aktif' => true, 'sifre' => 'gecici123']);
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
        $this->assertCount(5, $sorular);
        $this->assertArrayNotHasKey('dogru_index', $sorular[0]);

        foreach ($sorular as $s) {
            $bilesen->set('cevaplar.'.$s['id'], 1);
        }
        $bilesen->call('sinaviGonder')->assertSet('mod', 'sonuc');

        $atama->refresh();
        $this->assertTrue($atama->basariliMi());
        $this->assertSame('tamamlandi', $atama->durum);
        $this->assertSame(100, $atama->sonSinav()->puan);

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

    public function test_ileri_sarma_ve_sahte_sure_sayilmaz(): void
    {
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

        $this->tamamla($atama);

        $this->assertSame('tamamlandi', $atama->refresh()->durum);
        $this->assertSame(0, $mehmet->egitimKayitlari()->count());   // temel eğitim henüz tamam sayılmaz
        $satir = EgitimTakibi::satirlar($this->firma)->first();
        $this->assertSame('kayit_yok', $satir['durum']);
        $this->assertTrue($satir['dorduncu_bekleniyor']);

        Livewire::test(UzaktanEgitimAtama::class)
            ->set('firmaId', $this->firma->id)
            ->assertSee('4. konu yüz yüze bekleniyor');

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

    private function tamamla(EgitimAtamasi $atama): void
    {
        foreach ($atama->paket->dersler as $d) {
            $atama->ilerlemeler()->create(['egitim_dersi_id' => $d->id, 'izleme_yuzdesi' => 100, 'izlendi' => true, 'izlendi_at' => now()]);
        }
        $atama->sinavSonuclari()->create(['deneme_no' => 1, 'puan' => 90, 'gecti' => true, 'cevaplar' => [], 'tamamlandi_at' => now()]);
        $atama->durumuTazele();
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

        Livewire::test(UzaktanEgitimAtama::class)
            ->set('firmaId', $this->firma->id)
            ->assertSee('İşçi')
            ->assertSee('28.09.2026 10:15')
            ->assertSee('☑ Tamamlandı')
            ->assertSee('kayda işlendi')
            ->call('takipExcel')
            ->assertFileDownloaded('uzaktan-egitim-takibi-ornek-insaat-as.xlsx');
    }
}
