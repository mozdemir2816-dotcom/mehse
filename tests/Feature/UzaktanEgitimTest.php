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
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek İnşaat A.Ş.']);
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
            $paket->dersler()->create(['sira' => $i, 'baslik' => "Ders $i", 'video_url' => 'https://youtu.be/abc'.$i.'DEFG']);
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

    public function test_portal_akisi_dersi_izle_sinavi_gec_belge_al(): void
    {
        $paket = $this->paketKur(ders: 2, soru: 6);
        $calisan = $this->calisanKur();
        $atama = EgitimAtamasi::create([
            'egitim_paketi_id' => $paket->id, 'calisan_id' => $calisan->id,
            'atayan_user_id' => $this->uzman->id, 'atandi_at' => now(), 'durum' => 'atandi',
        ]);

        $this->portalGirisi($calisan);

        $bilesen = Livewire::test(EgitimIzle::class, ['atama' => $atama->id]);

        // İki dersi de %95 izle
        foreach ($paket->dersler as $d) {
            $bilesen->call('dersIlerleme', $d->id, 95);
        }

        $atama->refresh();
        $this->assertTrue($atama->tumDerslerIzlendiMi());
        $this->assertSame('devam', $atama->durum);

        // Sınava gir, hepsini doğru (dogru_index = 1) cevapla
        $bilesen->call('sinavaBasla')->assertSet('mod', 'sinav');
        $sorular = $bilesen->get('sinavSorulari');
        $this->assertCount(5, $sorular);

        foreach ($sorular as $s) {
            $bilesen->set('cevaplar.'.$s['id'], 1);
        }
        $bilesen->call('sinaviGonder')->assertSet('mod', 'sonuc');

        $atama->refresh();
        $this->assertTrue($atama->basariliMi());
        $this->assertSame('tamamlandi', $atama->durum);
        $this->assertSame(100, $atama->sonSinav()->puan);

        $yanit = UzaktanEgitimBelgesiUretici::pdf($atama->fresh());
        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_tum_dersler_izlenmeden_sinav_acilmaz(): void
    {
        $paket = $this->paketKur(ders: 2);
        $calisan = $this->calisanKur();
        $atama = EgitimAtamasi::create([
            'egitim_paketi_id' => $paket->id, 'calisan_id' => $calisan->id,
            'atayan_user_id' => $this->uzman->id, 'atandi_at' => now(), 'durum' => 'atandi',
        ]);

        $this->portalGirisi($calisan);

        Livewire::test(EgitimIzle::class, ['atama' => $atama->id])
            ->call('dersIlerleme', $paket->dersler->first()->id, 95)
            ->call('sinavaBasla')
            ->assertSet('mod', 'izle');
    }

    public function test_dusuk_puanda_basarisiz_ve_belge_yok(): void
    {
        $paket = $this->paketKur(ders: 1, soru: 6);
        $calisan = $this->calisanKur();
        $atama = EgitimAtamasi::create([
            'egitim_paketi_id' => $paket->id, 'calisan_id' => $calisan->id,
            'atayan_user_id' => $this->uzman->id, 'atandi_at' => now(), 'durum' => 'atandi',
        ]);

        $this->portalGirisi($calisan);
        $b = Livewire::test(EgitimIzle::class, ['atama' => $atama->id])
            ->call('dersIlerleme', $paket->dersler->first()->id, 100)
            ->call('sinavaBasla');

        foreach ($b->get('sinavSorulari') as $s) {
            $b->set('cevaplar.'.$s['id'], 0); // hepsi yanlış
        }
        $b->call('sinaviGonder');

        $atama->refresh();
        $this->assertFalse($atama->basariliMi());
        $this->assertSame('basarisiz', $atama->durum);
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
