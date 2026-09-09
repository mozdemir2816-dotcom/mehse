<?php

namespace Tests\Feature;

use App\Filament\Pages\UzaktanEgitimAtama;
use App\Filament\Portal\Pages\EgitimIzle;
use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Models\User;
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

    public function test_epostasiz_calisan_atlanir(): void
    {
        $paket = $this->paketKur();
        $calisan = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Postasız Kişi', 'aktif' => true]);

        Livewire::test(UzaktanEgitimAtama::class)
            ->set('firmaId', $this->firma->id)
            ->set('paketId', $paket->id)
            ->call('calisanToggle', $calisan->id)
            ->call('ata');

        $this->assertSame(0, EgitimAtamasi::count());
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
}
