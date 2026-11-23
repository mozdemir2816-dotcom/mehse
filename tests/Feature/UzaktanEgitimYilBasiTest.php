<?php

namespace Tests\Feature;

use App\Filament\Pages\UzaktanEgitimYilBasi;
use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimKaydi;
use App\Models\EgitimPaketi;
use App\Models\EgitimTopluBildirimi;
use App\Models\Firma;
use App\Models\UzaktanEgitimFirmaAyari;
use App\Models\User;
use App\Support\EgitimTakibi;
use App\Support\YilBasiTopluEgitim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** 23.11.2026 — Yıl başı toplu online eğitim: kayıtlı → 4 saat yenileme, kaydı yok → 8 saat temel. */
class UzaktanEgitimYilBasiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek Metal A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'eposta' => 'ik@ornek.test', 'telefon' => '0532 111 22 33']);
        $this->actingAs($this->uzman);
    }

    private function paket(string $tur): EgitimPaketi
    {
        $p = EgitimPaketi::create(['user_id' => $this->uzman->id, 'ad' => $tur === 'yenileme' ? 'Temel İSG Yenileme (4 Saat)' : 'Temel İSG Eğitimi (8 Saat)',
            'egitim_turu' => $tur, 'gecme_puani' => 60, 'video_zorunlu_yuzde' => 90, 'sinav_soru_sayisi' => 5]);
        $p->dersler()->create(['sira' => 1, 'modul' => 1, 'baslik' => 'Genel', 'video_url' => 'https://youtu.be/abcDEFG1', 'sure_sn' => 600]);
        for ($i = 1; $i <= 6; $i++) {
            $p->sorular()->create(['soru' => "Soru $i?", 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 1]);
        }

        return $p;
    }

    private function onayla(EgitimPaketi $p, ?Firma $firma = null): void
    {
        $ayar = UzaktanEgitimFirmaAyari::firmaIcin(($firma ?? $this->firma)->id) ?? new UzaktanEgitimFirmaAyari(['firma_id' => ($firma ?? $this->firma)->id]);
        $ayar->onayYaz($p, 'Uzman');
        $ayar->save();
    }

    private function calisan(string $ad, ?string $sonEgitim = null): Calisan
    {
        $c = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => $ad, 'aktif' => true]);
        if ($sonEgitim) {
            EgitimKaydi::create(['calisan_id' => $c->id, 'tur' => EgitimTakibi::TEMEL_TUR, 'tarih' => $sonEgitim]);
        }

        return $c;
    }

    public function test_onizleme_kayitliyi_yenilemeye_kaydi_olmayani_temele_ayirir(): void
    {
        $temel = $this->paket('ilk_defa');
        $yenileme = $this->paket('yenileme');
        $this->calisan('Ali Dolan', '2025-03-10');      // tehlikeli: 2 yıl → 2027-03-10 (2027 içinde)
        $this->calisan('Veli Gecerli', '2026-06-01');   // → 2028: atlanır
        $this->calisan('Ayşe Kayitsiz');                // kaydı yok → 8 saat

        $o = YilBasiTopluEgitim::firmaOnizleme($this->firma, 2027, $temel, $yenileme, 'yenilemesi_dolan');

        $this->assertSame(['Ali Dolan'], $o['yenileme']->map(fn ($s) => $s['calisan']->ad_soyad)->all());
        $this->assertSame(['Ayşe Kayitsiz'], $o['ilk_defa']->map(fn ($s) => $s['calisan']->ad_soyad)->all());
        $this->assertSame(1, $o['gecerli']);
        $this->assertNotEmpty($o['eksik']);   // içerik onayı yok
    }

    public function test_kaydi_olan_herkes_kapsaminda_onceki_egitimi_olan_herkes_yenileme_alir(): void
    {
        $temel = $this->paket('ilk_defa');
        $yenileme = $this->paket('yenileme');
        $this->calisan('Ali Dolan', '2025-03-10');
        $this->calisan('Veli Gecerli', '2026-06-01');
        $this->calisan('Can BuYil', '2027-01-15');      // seçilen yıl içinde eğitim almış → atlanır
        $this->calisan('Ayşe Kayitsiz');

        $o = YilBasiTopluEgitim::firmaOnizleme($this->firma, 2027, $temel, $yenileme);   // varsayılan: kaydi_olan

        $this->assertSame(['Ali Dolan', 'Veli Gecerli'], $o['yenileme']->map(fn ($s) => $s['calisan']->ad_soyad)->sort()->values()->all());
        $this->assertSame(['Ayşe Kayitsiz'], $o['ilk_defa']->map(fn ($s) => $s['calisan']->ad_soyad)->all());
        $this->assertSame(1, $o['gecerli']);
    }

    public function test_tum_firmalara_toplu_icerik_onayi(): void
    {
        $temel = $this->paket('ilk_defa');
        $yenileme = $this->paket('yenileme');
        $ikinci = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli']);
        $baskasi = Firma::factory()->create();

        Livewire::test(UzaktanEgitimYilBasi::class)
            ->set('temelPaketId', $temel->id)
            ->set('yenilemePaketId', $yenileme->id)
            ->call('topluOnayVer');

        foreach ([$this->firma, $ikinci] as $f) {
            $this->assertTrue(UzaktanEgitimFirmaAyari::gonderimeHazirMi($f, $temel));
            $this->assertTrue(UzaktanEgitimFirmaAyari::gonderimeHazirMi($f, $yenileme));
        }
        $this->assertFalse(UzaktanEgitimFirmaAyari::gonderimeHazirMi($baskasi, $yenileme));
    }

    public function test_iki_paket_ayni_firmada_birlikte_onayli_kalir(): void
    {
        $temel = $this->paket('ilk_defa');
        $yenileme = $this->paket('yenileme');
        $this->onayla($temel);
        $this->onayla($yenileme);

        $this->assertTrue(UzaktanEgitimFirmaAyari::gonderimeHazirMi($this->firma, $temel));
        $this->assertTrue(UzaktanEgitimFirmaAyari::gonderimeHazirMi($this->firma, $yenileme));
    }

    public function test_gonderim_atar_sifre_uretir_ve_firma_eposta_gonderir(): void
    {
        Mail::fake();
        $temel = $this->paket('ilk_defa');
        $yenileme = $this->paket('yenileme');
        $this->onayla($temel);
        $this->onayla($yenileme);
        $ali = $this->calisan('Ali Dolan', '2025-03-10');
        $ayse = $this->calisan('Ayşe Kayitsiz');

        $s = YilBasiTopluEgitim::gonder($this->firma, 2027, $temel, $yenileme, $this->uzman->id);

        $this->assertSame(1, $s['yenileme']);
        $this->assertSame(1, $s['ilk_defa']);
        $this->assertNull($s['eposta_hatasi']);
        $this->assertSame('yenileme', EgitimAtamasi::where('calisan_id', $ali->id)->first()->egitim_turu);
        $this->assertSame($yenileme->id, EgitimAtamasi::where('calisan_id', $ali->id)->first()->egitim_paketi_id);
        $this->assertSame($temel->id, EgitimAtamasi::where('calisan_id', $ayse->id)->first()->egitim_paketi_id);
        $this->assertSame(2027, EgitimAtamasi::where('calisan_id', $ayse->id)->first()->donem_yili);
        $this->assertNotNull($ayse->fresh()->eposta);   // kullanıcı kodu üretildi
        $this->assertCount(2, $s['giris']);
        $this->assertNotNull(EgitimTopluBildirimi::where('firma_id', $this->firma->id)->where('yil', 2027)->first()->eposta_gonderildi_at);

        // İkinci kez önizlemede ikisi de "zaten atanmış" görünür, tekrar gönderilmez.
        $o = YilBasiTopluEgitim::firmaOnizleme($this->firma, 2027, $temel, $yenileme);
        $this->assertTrue($o['yenileme']->isEmpty() && $o['ilk_defa']->isEmpty());
        $this->assertSame(2, $o['zaten_atanmis']);
    }

    public function test_tamamlanan_eski_atama_silinmez_ve_ayni_paket_yeni_yilda_yeniden_atanir(): void
    {
        $yenileme = $this->paket('yenileme');
        $temel = $this->paket('ilk_defa');
        $c = $this->calisan('Ali Dolan');
        $eski = EgitimAtamasi::create(['egitim_paketi_id' => $temel->id, 'calisan_id' => $c->id, 'atayan_user_id' => $this->uzman->id,
            'atandi_at' => '2025-01-05', 'durum' => 'tamamlandi', 'tamamlandi_at' => '2025-02-01', 'egitim_turu' => 'ilk_defa']);
        $gecenYil = EgitimAtamasi::create(['egitim_paketi_id' => $yenileme->id, 'calisan_id' => $c->id, 'atayan_user_id' => $this->uzman->id,
            'atandi_at' => '2026-01-05', 'durum' => 'tamamlandi', 'tamamlandi_at' => '2026-02-01', 'egitim_turu' => 'yenileme']);

        \App\Support\UzaktanEgitimAtayici::ata($yenileme, collect([$c]), 'yenileme', null, $this->uzman->id, 2027);

        $this->assertNotNull($eski->fresh());
        $this->assertNotNull($gecenYil->fresh());
        $this->assertSame(2, EgitimAtamasi::where('egitim_paketi_id', $yenileme->id)->where('calisan_id', $c->id)->count());
    }

    public function test_yenileme_paketi_4_saat_online_kalan_4_saat_her_sinifta_yuz_yuze(): void
    {
        $yenileme = $this->paket('yenileme');
        $temel = $this->paket('ilk_defa');

        $this->assertSame(180, $yenileme->gerekenSure('az_tehlikeli')['gereken_dk']);
        $this->assertSame(4, $yenileme->gerekenSure('az_tehlikeli')['yuz_yuze_saat']);
        $this->assertSame(4, $yenileme->gerekenSure('cok_tehlikeli')['yuz_yuze_saat']);
        // 8 saatlik temel paket eski davranışı korur.
        $this->assertSame(360, $temel->gerekenSure('az_tehlikeli')['gereken_dk']);
        $this->assertSame(0, $temel->gerekenSure('az_tehlikeli')['yuz_yuze_saat']);
        $this->assertSame(8, $temel->gerekenSure('cok_tehlikeli')['yuz_yuze_saat']);
    }

    public function test_az_tehlikelide_yenileme_ig_onayi_olmadan_kayda_islenmez(): void
    {
        $this->firma->update(['tehlike_sinifi' => 'az_tehlikeli']);
        $yenileme = $this->paket('yenileme');
        $c = $this->calisan('Ali Dolan');
        $a = EgitimAtamasi::create(['egitim_paketi_id' => $yenileme->id, 'calisan_id' => $c->id, 'atayan_user_id' => $this->uzman->id,
            'atandi_at' => now(), 'durum' => 'onay_bekliyor', 'tamamlandi_at' => now(), 'egitim_turu' => 'yenileme']);

        $this->assertTrue($a->yuzYuzeTamamlamaGerekirMi());
        $this->assertFalse($a->egitimKaydinaIslenirMi());
    }

    public function test_sayfa_acilir_secip_gonderir(): void
    {
        Mail::fake();
        $temel = $this->paket('ilk_defa');
        $yenileme = $this->paket('yenileme');
        $this->onayla($temel);
        $this->onayla($yenileme);
        $this->calisan('Ayşe Kayitsiz');

        Livewire::test(UzaktanEgitimYilBasi::class)
            ->set('yil', 2027)
            ->set('temelPaketId', $temel->id)
            ->set('yenilemePaketId', $yenileme->id)
            ->assertSee('Örnek Metal A.Ş.')
            ->call('hazirlariSec')
            ->assertSet('seciliFirmalar', [$this->firma->id])
            ->call('gonder')
            ->assertSee('Gönderim sonucu')
            ->assertSee('ik@ornek.test')
            ->assertSee('wa.me/905321112233', false);

        $this->assertSame(1, EgitimAtamasi::count());
    }
}
