<?php

namespace Tests\Feature;

use App\Models\AsiKaydi;
use App\Models\Calisan;
use App\Models\EczaDolabiKalemi;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\KkdTalebi;
use App\Models\RevirKaydi;
use App\Models\SahaBulgusu;
use App\Models\Taseron;
use App\Models\TaseronBasvurusu;
use App\Models\User;
use App\Support\CanliPortfoyAsistani;
use App\Support\IbysAktarimUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KontinusArtilarTest extends TestCase
{
    use RefreshDatabase;

    private function kullanici(): User
    {
        return User::factory()->create(['aktif' => true, 'rol' => 'sahip']);
    }

    public function test_k1_revir_protokol_ecza_dolabi_ve_asi_takibi(): void
    {
        $u = $this->kullanici();
        $this->actingAs($u);
        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);

        $revir = RevirKaydi::create([
            'firma_id' => $f->id,
            'calisan_ad_soyad' => 'Ahmet Yılmaz',
            'tarih' => now(),
            'basvuru_nedeni' => 'Parmak kesisi',
            'yapilan_islem' => 'Antiseptik pansuman',
            'sonuc' => 'ayakta',
        ]);
        $this->assertStringStartsWith('PR-', (string) $revir->protokol_no);
        $this->assertSame('Ayakta Müdahale / İşe Döndü', $revir->sonucEtiketi());

        $sktGecmis = EczaDolabiKalemi::create([
            'firma_id' => $f->id,
            'malzeme_adi' => 'Batikon 100ml',
            'kategori' => 'ilac',
            'miktar' => 5,
            'kritik_miktar' => 2,
            'skt' => today()->subDays(3),
        ]);
        $this->assertSame('suresi_gecmis', $sktGecmis->durum());

        $kritikStok = EczaDolabiKalemi::create([
            'firma_id' => $f->id,
            'malzeme_adi' => 'Steril Gazlı Bez',
            'kategori' => 'pansuman',
            'miktar' => 2,
            'kritik_miktar' => 5,
            'skt' => today()->addMonths(6),
        ]);
        $this->assertSame('kritik_stok', $kritikStok->durum());

        $asi = AsiKaydi::create([
            'firma_id' => $f->id,
            'calisan_ad_soyad' => 'Ahmet Yılmaz',
            'asi_turu' => 'Tetanoz',
            'doz' => '1. Doz',
            'yapilma_tarihi' => today()->subDays(30),
            'sonraki_doz_tarihi' => today()->addDays(10),
        ]);
        $this->assertSame('yaklasan', $asi->sonrakiDurum());
    }

    public function test_k2_taseron_self_servis_basvuru_portali_ve_onay(): void
    {
        $u = $this->kullanici();
        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);

        $basvuru = TaseronBasvurusu::create([
            'firma_id' => $f->id,
            'durum' => 'link_acik',
        ]);

        // Giriş gerektirmeden portal açılır ve taşeron formu gönderir
        $this->get(route('taseron.basvuru', $basvuru->token))
            ->assertOk()
            ->assertSee($f->unvan);

        $this->post(route('taseron.basvuru.kaydet', $basvuru->token), [
            'unvan' => 'Kaya Elektrik Taahhüt Ltd. Şti.',
            'vergi_no' => '1234567890',
            'faaliyet' => 'Elektrik pano montajı',
            'yetkili_ad' => 'Mehmet Kaya',
            'telefon' => '05321112233',
            'eposta' => 'info@kayaelektrik.test',
        ])->assertRedirect();

        $basvuru->refresh();
        $this->assertSame('bekliyor', $basvuru->durum);
        $this->assertSame('Kaya Elektrik Taahhüt Ltd. Şti.', $basvuru->unvan);
    }

    public function test_k3_canli_portfoy_asistani_gercek_veriyi_yanitlar(): void
    {
        $u = $this->kullanici();
        $this->actingAs($u);
        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true, 'unvan' => 'Örnek Metal A.Ş.']);

        SahaBulgusu::create([
            'firma_id' => $f->id,
            'uygunsuzluk' => 'Kompresör koruyucusu eksik',
            'olasilik' => 3,
            'siddet' => 4,
            'termin' => today()->subDays(2),
        ]);

        IsEkipmani::create([
            'firma_id' => $f->id,
            'kategori' => 'kaldirma',
            'ekipman_adi' => 'Forklift 3T',
            'aktif' => true,
            'son_muayene_tarihi' => today()->subDays(1),
            'sonraki_vize_tarihi' => today()->addYear(),
            'sonuc' => 'uygun_degil',
        ]);

        $dofYanit = CanliPortfoyAsistani::sor($u, 'Açık ve gecikmiş DÖF kayıtlarımı özetle');
        $this->assertNotNull($dofYanit);
        $this->assertTrue($dofYanit['canli_veri']);
        $this->assertStringContainsString('Örnek Metal A.Ş.', $dofYanit['yanit']);

        $ekipmanYanit = CanliPortfoyAsistani::sor($u, 'Periyodik kontrolü uygunsuz ekipmanlar');
        $this->assertNotNull($ekipmanYanit);
        $this->assertStringContainsString('Forklift 3T', $ekipmanYanit['yanit']);

        // Endpoint testi (/mehse/mevzuat-sor)
        $this->postJson(route('mehse.mevzuat.sor'), ['soru' => 'Portföy özetim nedir?'])
            ->assertOk()
            ->assertJsonPath('canli_veri', true);
    }

    public function test_k4_dof_yaslandirma_ve_gerekceli_ret_gecmisi(): void
    {
        $u = $this->kullanici();
        $this->actingAs($u);
        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);

        $b = SahaBulgusu::create([
            'firma_id' => $f->id,
            'uygunsuzluk' => 'Yangın tüpü önü kapalı',
            'olasilik' => 3,
            'siddet' => 3,
            'sorumlu' => 'Vardiya Amiri',
            'isveren_kapanis_talebi_at' => now(),
            'isveren_kapanis_notu' => 'Temizlendi',
        ]);

        $gecmis = [
            ['tarih' => now()->format('d.m.Y H:i'), 'neden' => 'Fotoğraf net değil, güncel açıdan çekiniz', 'reddeden' => 'A Sınıfı İGU'],
        ];
        $b->update([
            'isveren_kapanis_talebi_at' => null,
            'isveren_ret_gecmisi' => $gecmis,
        ]);

        $b->refresh();
        $this->assertCount(1, $b->isveren_ret_gecmisi);
        $this->assertStringContainsString('Fotoğraf net değil', $b->isveren_ret_gecmisi[0]['neden']);
    }

    public function test_k5_calisan_kkd_talebi_olusturma_ve_durum_guncelleme(): void
    {
        $u = $this->kullanici();
        $f = Firma::factory()->create(['user_id' => $u->id, 'aktif' => true]);
        $c = Calisan::create([
            'firma_id' => $f->id,
            'ad_soyad' => 'Veli Demir',
            'tc' => '11111111110',
            'aktif' => true,
        ]);

        $talep = KkdTalebi::create([
            'firma_id' => $f->id,
            'calisan_id' => $c->id,
            'calisan_ad_soyad' => $c->ad_soyad,
            'kkd_turu' => 'S3 İş Ayakkabısı',
            'beden' => '43',
            'neden' => 'yipranma',
            'durum' => 'bekliyor',
        ]);

        $this->assertSame('bekliyor', $talep->durum);
        $talep->update(['durum' => 'teslim_edildi']);
        $this->assertSame('teslim_edildi', $talep->fresh()->durum);
    }

    public function test_k6_ibys_uyumlu_xml_ve_json_paketi(): void
    {
        $u = $this->kullanici();
        $f = Firma::factory()->create([
            'user_id' => $u->id,
            'aktif' => true,
            'unvan' => 'Test Sanayi A.Ş.',
            'sgk_sicil_no' => '22562010112345670340100',
        ]);

        EgitimKatilim::create([
            'firma_id' => $f->id,
            'belge_tarihi' => today(),
            'baslik_anahtari' => 'genel',
            'sure_gun' => 2,
            'katilimcilar' => [['ad_soyad' => 'Ali Kaya', 'tc' => '12345678901', 'gorev' => 'Operatör']],
        ]);

        $veri = IbysAktarimUretici::veri($f);
        $this->assertSame('MEHSE-IBYS-PAKET-v1', $veri['sema']);
        $this->assertCount(1, $veri['egitim_bildirimleri']);

        $xml = IbysAktarimUretici::xmlMetni($f);
        $this->assertStringContainsString('<IbysAktarimPaketi', $xml);
        $this->assertStringContainsString('Test Sanayi A.Ş.', $xml);
        $this->assertStringContainsString('İş Sağlığı ve Güvenliği', $xml);
    }
}
