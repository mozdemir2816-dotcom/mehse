<?php

namespace Tests\Feature;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use App\Models\YoklamaKaydi;
use App\Models\Ziyaretci;
use App\Support\CalisanOzlukZipUretici;
use App\Support\YasalTakvim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NanoIsgArtilarTest extends TestCase
{
    use RefreshDatabase;

    private function kullanici(): User
    {
        return User::factory()->create(['aktif' => true, 'rol' => 'sahip']);
    }

    public function test_n1_ziyaretci_giris_oncesi_brifing_ve_bilgi_testi_kilidi(): void
    {
        $u = $this->kullanici();
        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);

        $z = Ziyaretci::create([
            'firma_id' => $f->id,
            'ad_soyad' => 'Zeynep Acar',
            'gecerlilik_baslangic' => now()->subHour(),
            'gecerlilik_bitis' => now()->addHours(6),
            'egitim_zorunlu' => true,
            'isg_bilgilendirme' => false,
        ]);

        // Test geçilmeden eğitim tamamlanmamış görünür
        $this->assertFalse($z->egitimTamamlandiMi());
        $this->get(route('ziyaretci.dogrula', $z->token))
            ->assertOk()
            ->assertSee('İSG BRİFİNG &amp; TESTİ BEKLENİYOR', false);

        // Yanlış cevaplarla geçemez
        $this->post(route('ziyaretci.test', $z->token), [
            'cevaplar' => [0 => 'a', 1 => 'a', 2 => 'b'],
            'kvkk_onay' => 1,
        ])->assertRedirect();
        $this->assertFalse($z->fresh()->egitimTamamlandiMi());

        // Doğru cevaplarla testi geçer ve kart açılır
        $this->post(route('ziyaretci.test', $z->token), [
            'cevaplar' => [0 => 'b', 1 => 'b', 2 => 'a'],
            'kvkk_onay' => 1,
        ])->assertRedirect();

        $z->refresh();
        $this->assertTrue($z->egitimTamamlandiMi());
        $this->assertSame(100, $z->test_puani);

        $this->get(route('ziyaretci.dogrula', $z->token))
            ->assertOk()
            ->assertSee('GEÇERLİ — GİRİŞ ONAYLI');
    }

    public function test_n2_gunluk_yoklama_ve_puantaj_ekrani(): void
    {
        $u = $this->kullanici();
        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);
        $c = Calisan::create([
            'firma_id' => $f->id,
            'ad_soyad' => 'Murat Şahin',
            'aktif' => true,
        ]);

        YoklamaKaydi::create([
            'firma_id' => $f->id,
            'calisan_id' => $c->id,
            'tarih' => today(),
            'durum' => 'mevcut',
        ]);

        $this->assertSame(1, YoklamaKaydi::where('firma_id', $f->id)->where('durum', 'mevcut')->count());

        $this->actingAs($u)
            ->get('/admin/yoklama-puantaj?firma='.$f->id)
            ->assertOk()
            ->assertSee('Günlük Yoklama Listesi')
            ->assertSee('Murat Şahin');
    }

    public function test_n3_calisan_rol_rozetleri_mesleki_belgeler_yasal_takvim_ve_ozluk_zip(): void
    {
        $u = $this->kullanici();
        $this->actingAs($u);
        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);

        $c = Calisan::create([
            'firma_id' => $f->id,
            'ad_soyad' => 'Hakan Öztürk',
            'tc' => '12345678901',
            'aktif' => true,
            'rol_etiketleri' => ['ilkyardimci', 'forklift'],
            'myk_belge_no' => 'MYK-2026-9981',
            'myk_gecerlilik' => today()->addDays(12),
            'src_gecerlilik' => today()->subDays(2),
        ]);

        $belgeler = collect($c->meslekiBelgeler())->keyBy('ad');
        $this->assertSame('yaklasan', $belgeler['MYK']['durum']);
        $this->assertSame('dolmus', $belgeler['SRC']['durum']);

        // YasalTakvim yaklaşan/dolmuş mesleki belgeyi yakalamalı
        $takvim = YasalTakvim::kalemler($u->id, 60);
        $this->assertTrue($takvim->contains(fn ($t) => str_contains((string) $t['baslik'], 'Mesleki belge')));

        // Özlük ZIP üretimi çalışmalı
        $yanit = CalisanOzlukZipUretici::indir($c);
        $this->assertSame(200, $yanit->getStatusCode());
    }

    public function test_n4_yeni_sektor_sayfalari_ve_bilgi_guvenligi_sayfasi(): void
    {
        $this->get('/sektor/insaat-ve-santiye')->assertOk()->assertSee('İnşaat ve Şantiye');
        $this->get('/sektor/madencilik-ve-tas-ocagi')->assertOk()->assertSee('Madencilik ve Taş Ocakları');
        $this->get('/sektor/enerji-ve-tersane')->assertOk()->assertSee('Enerji ve Tersane');
        $this->get('/sektor/gida-ve-uretim')->assertOk()->assertSee('Gıda ve Fabrika Üretimi');
        $this->get('/bilgi-guvenligi')->assertOk()->assertSee('Bilgi Güvenliği');
        $this->get('/sitemap.xml')->assertOk()->assertSee('/bilgi-guvenligi')->assertSee('/sektor/insaat-ve-santiye');
    }
}
