<?php

namespace Tests\Feature;

use App\Filament\Pages\CariHesap;
use App\Filament\Pages\KullaniciYonetimi;
use App\Filament\Pages\KurulToplantisi;
use App\Filament\Pages\RiskMerkezi;
use App\Filament\Pages\SaglikGozetimi;
use App\Filament\Pages\TehlikeRiskAnalitigi;
use App\Filament\Pages\UzaktanEgitimAtama;
use App\Filament\Resources\RiskSablonus\RiskSablonuResource;
use App\Models\ArsivDosya;
use App\Models\Calisan;
use App\Models\CanliImza;
use App\Models\EgitimAtamasi;
use App\Models\EgitimDersi;
use App\Models\EgitimDersIlerlemesi;
use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\KurulToplantisi as KurulModel;
use App\Models\KurulUyesi;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\RiskSablonu;
use App\Models\TeftisPaylasimi;
use App\Models\User;
use App\Models\Ziyaretci;
use App\Support\CariHesapYonetici;
use App\Support\ZiyaretOtomatikPlanlayici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Faz1EvrakKilidiKvkkPerformansTest extends TestCase
{
    use RefreshDatabase;

    public function test_mufettis_teftis_ekrani_dogru_arsiv_ve_ekipman_sutunlarini_basar(): void
    {
        $u1 = User::factory()->create(['rol' => 'kullanici', 'aktif' => true]);
        $u2 = User::factory()->create(['rol' => 'kullanici', 'aktif' => true]);
        $f = Firma::create(['user_id' => $u1->id, 'unvan' => 'Teftiş A.Ş.', 'tehlike_sinifi' => 'cok_tehlikeli', 'aktif' => true]);

        IsEkipmani::create([
            'firma_id' => $f->id,
            'ekipman_adi' => 'Vinç 10T',
            'kategori' => 'kaldirma_iletme',
            'muayene_yapan' => 'TÜV A Tipi Muayene',
        ]);

        ArsivDosya::create([
            'firma_id' => $f->id,
            'kategori' => 'risk_degerlendirmesi',
            'baslik' => '2026 Risk Analizi',
            'dosya_adi' => 'risk.pdf',
            'dosya_yolu' => 'arsiv/1/risk.pdf',
            'baslangic_tarihi' => '2026-05-15',
            'gecerlilik_sonu' => '2028-05-15',
            'aktif' => true,
        ]);

        $paylasim = TeftisPaylasimi::create([
            'user_id' => $u1->id,
            'firma_id' => $f->id,
            'token' => str_repeat('b', 32),
            'baslik' => 'Bakanlık Teftişi',
            'bitis_tarihi' => now()->addDays(5),
            'aktif' => true,
        ]);

        // Başka bir oturumlu kullanıcı linki açtığında global scope yüzünden 403 almamalı
        $this->actingAs($u2)
            ->get(route('mufettis.teftis', ['token' => $paylasim->token]))
            ->assertOk()
            ->assertSee('noindex, nofollow', false)
            ->assertSee('2026 Risk Analizi')
            ->assertSee('15.05.2028')
            ->assertSee('TÜV A Tipi Muayene');
    }

    public function test_ziyaretci_iptal_kartta_test_gonderimi_reddedilir_ve_canli_imza_maskelenir(): void
    {
        $u = User::factory()->create(['aktif' => true]);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Ziyaret A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        $z = Ziyaretci::create([
            'firma_id' => $f->id,
            'kart_no' => 'ZY-001',
            'token' => str_repeat('z', 32),
            'ad_soyad' => 'Kemal Sunal',
            'gecerlilik_baslangic' => now()->subHour(),
            'gecerlilik_bitis' => now()->addHours(4),
            'iptal' => true,
            'egitim_zorunlu' => true,
            'isg_bilgilendirme' => false,
        ]);

        $this->post(route('ziyaretci.test', ['token' => $z->token]), [
            'cevaplar' => [0 => 'b', 1 => 'b', 2 => 'a'],
            'kvkk_onay' => '1',
        ])->assertRedirect();

        $this->assertFalse((bool) $z->fresh()->isg_bilgilendirme);

        $imza = CanliImza::create([
            'user_id' => $u->id,
            'firma_id' => $f->id,
            'imza_token' => str_repeat('i', 32),
            'dogrulama_kodu' => 'IMZTEST0123456',
            'belge_turu' => 'kkd',
            'belge_baslik' => 'KKD Zimmet',
            'calisan_adi' => 'Mehmet Özdemir',
            'imza_gorseli' => 'data:image/png;base64,AAA',
            'imzalandi_at' => now(),
        ]);

        $this->get(route('canli-imza.dogrula', ['kod' => 'IMZTEST0123456']))
            ->assertOk()
            ->assertSee('noindex, nofollow', false)
            ->assertDontSee('Mehmet Özdemir')
            ->assertSee('Me**** Öz*****');
    }

    public function test_kilitli_risk_degerlendirmesi_degistirilemez_ve_yeni_versiyon_yeni_gecerlilik_alir(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Risk A.Ş.', 'tehlike_sinifi' => 'cok_tehlikeli', 'aktif' => true]);

        $rd = RiskDegerlendirmesi::create([
            'firma_id' => $f->id,
            'yontem' => 'matris_5x5',
            'rapor_tarihi' => '2022-01-01',
            'gecerlilik_tarihi' => '2024-01-01',
            'durum' => 'taslak',
        ]);

        $madde = RiskMaddesi::create([
            'risk_degerlendirmesi_id' => $rd->id,
            'sira' => 1,
            'tehlike' => 'Elektrik',
            'durum' => 'acik',
        ]);

        // Şimdi kilitle: artık ne Livewire'dan ne de doğrudan model üzerinden değiştirilemez / silinemez
        $rd->kilitle($u->id);

        Livewire::test(RiskMerkezi::class)->call('maddeDurumu', $madde->id, 'kapali');
        $this->assertSame('acik', $madde->fresh()->durum);

        Livewire::test(TehlikeRiskAnalitigi::class)->call('maddeGuncelle', $madde->id, 'maruz_kisi', 25);
        $this->assertNull($madde->fresh()->maruz_kisi);

        // Doğrudan model silme ve yeni madde ekleme de kilit engeline takılmalı
        $this->assertFalse((bool) $madde->fresh()->delete());
        $this->assertNotNull($madde->fresh());

        // Yeni versiyon eski 2024 geçerlilik tarihini kopyalamamalı
        $v2 = $rd->fresh()->yeniVersiyonAc('Yıllık güncelleme');
        $this->assertTrue($v2->gecerlilik_tarihi->year >= now()->addYears(2)->year);
        $this->assertSame(1, $v2->maddeler()->count());
    }

    public function test_kurul_uye_guncelleme_gecmis_tamamlanmis_tutanagi_ezmez(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Kurul A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        $uye = KurulUyesi::create(['firma_id' => $f->id, 'ad_soyad' => 'Eski Vekil', 'rol' => 'isveren_vekili', 'aktif' => true]);
        $gecmis = KurulModel::create([
            'firma_id' => $f->id,
            'toplanti_no' => '2025-01',
            'toplanti_tarihi' => '2025-01-15',
            'durum' => 'tamamlandi',
            'katilimcilar' => [['ad_soyad' => 'Eski Vekil', 'rol' => 'isveren_vekili']],
        ]);
        $taslak = KurulModel::create([
            'firma_id' => $f->id,
            'toplanti_no' => '2026-01',
            'toplanti_tarihi' => '2026-01-15',
            'durum' => 'taslak',
            'katilimcilar' => [['ad_soyad' => 'Eski Vekil', 'rol' => 'isveren_vekili']],
        ]);

        Livewire::test(KurulToplantisi::class, ['firmaId' => $f->id])
            ->set('duzenlenenUyeId', $uye->id)
            ->set('uyeForm', ['ad_soyad' => 'Yeni Vekil', 'gorev' => 'Müdür', 'rol' => 'isveren_vekili'])
            ->call('uyeGuncelle');

        $this->assertSame('Eski Vekil', $gecmis->fresh()->katilimcilar[0]['ad_soyad']);
        $this->assertSame('Yeni Vekil', $taslak->fresh()->katilimcilar[0]['ad_soyad']);
    }

    public function test_baslamis_lms_atamasi_silinemez_ve_baskasinin_sablonu_silinemez(): void
    {
        $u1 = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $u2 = User::factory()->create(['rol' => 'kullanici', 'aktif' => true]);
        $this->actingAs($u1);

        $f = Firma::create(['user_id' => $u1->id, 'unvan' => 'LMS A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);
        $c = Calisan::create(['firma_id' => $f->id, 'ad_soyad' => 'Ali', 'aktif' => true]);
        $paket = EgitimPaketi::create(['user_id' => $u1->id, 'ad' => 'Temel İSG', 'gecme_puani' => 60]);
        $ders = EgitimDersi::create(['egitim_paketi_id' => $paket->id, 'baslik' => 'Ders 1', 'sira' => 1, 'video_url' => 'https://youtu.be/abc']);
        $atama = EgitimAtamasi::create(['calisan_id' => $c->id, 'egitim_paketi_id' => $paket->id, 'atayan_user_id' => $u1->id, 'atandi_at' => now(), 'durum' => 'devam']);
        EgitimDersIlerlemesi::create(['egitim_atamasi_id' => $atama->id, 'egitim_dersi_id' => $ders->id, 'izlenen_sn' => 120, 'izleme_yuzdesi' => 50]);

        Livewire::test(UzaktanEgitimAtama::class, ['firmaId' => $f->id])
            ->call('atamaSil', $atama->id);
        $this->assertDatabaseHas('egitim_atamalari', ['id' => $atama->id]);

        $sablon = RiskSablonu::create(['user_id' => $u1->id, 'ad' => 'Ortak Şablon', 'yontem' => 'matris_5x5', 'paylasildi' => true, 'maddeler' => []]);
        $this->actingAs($u2);
        $this->assertFalse(RiskSablonuResource::canDelete($sablon));
    }

    public function test_igu_adi_revire_hekim_olarak_yazilmaz_ve_ziyaret_planlayici_tenant_izoledir(): void
    {
        $igu = User::factory()->create(['rol' => 'sahip', 'unvan' => 'a_sinifi', 'name' => 'Ahmet İGU', 'aktif' => true]);
        $baska = User::factory()->create(['rol' => 'kullanici', 'aktif' => true]);

        $f1 = Firma::create(['user_id' => $igu->id, 'unvan' => 'Firma 1', 'tehlike_sinifi' => 'tehlikeli', 'calisan_sayisi' => 30, 'aktif' => true]);
        $f2 = Firma::create(['user_id' => $baska->id, 'unvan' => 'Firma 2', 'tehlike_sinifi' => 'tehlikeli', 'calisan_sayisi' => 30, 'aktif' => true]);

        $this->actingAs($igu);
        Livewire::test(SaglikGozetimi::class, ['firmaId' => $f1->id])
            ->set('revirAdSoyad', 'Hasan')
            ->set('revirNeden', 'Baş ağrısı')
            ->call('revirEkle');

        $this->assertDatabaseHas('revir_kayitlari', [
            'firma_id' => $f1->id,
            'calisan_ad_soyad' => 'Hasan',
            'hekim_dsp' => null,
        ]);

        // ZiyaretOtomatikPlanlayici yalnız verilen firmaIdleri listesini planlar
        ZiyaretOtomatikPlanlayici::calistir(2026, now()->startOfYear(), false, [], 10.0, [$f1->id]);
        $this->assertDatabaseHas('ziyaret_programlari', ['firma_id' => $f1->id, 'yil' => 2026]);
        $this->assertDatabaseMissing('ziyaret_programlari', ['firma_id' => $f2->id, 'yil' => 2026]);
    }

    public function test_cari_hesap_validasyon_ve_kilitli_kayit_calisir(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Cari A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        Livewire::test(CariHesap::class)
            ->set('firmaId', $f->id)
            ->set('hareketTur', 'tahsilat')
            ->set('hareketTutar', -50)
            ->call('hareketEkle')
            ->assertHasErrors(['hareketTutar']);

        CariHesapYonetici::hareketEkle($u, $f->id, 'tahsilat', 1500.50, '2026-03-10', 'Mart Tahsilat');
        $ozet = CariHesapYonetici::firmaOzeti($u->fresh(), $f);
        $this->assertSame(1500.5, $ozet['toplam_tahsilat']);
    }
}
