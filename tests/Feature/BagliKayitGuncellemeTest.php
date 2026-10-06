<?php

namespace Tests\Feature;

use App\Filament\Resources\Calisans\Pages\EditCalisan;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\KurulToplantisi;
use App\Models\User;
use App\Support\BagliKayitGuncelleyici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Personel / firma / İGU bilgisi düzeltilince kopyalandığı evrakların da
 * güncellenmesi (BagliKayitGuncelleyici, kullanıcı isteği 06.10.2026).
 */
class BagliKayitGuncellemeTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        BagliKayitGuncelleyici::$sonuc = [];
    }

    private function jsonOku(string $tablo, int $id, string $sutun): array
    {
        return json_decode((string) DB::table($tablo)->where('id', $id)->value($sutun), true);
    }

    public function test_calisan_duzeltilince_tum_kopyalar_guncellenir_digerleri_dokunulmaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $baskaFirma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'AYSE YILMAZ', 'tc' => '11111111110', 'gorev' => 'Operatör', 'aktif' => true]);

        $kurul = KurulToplantisi::create(['firma_id' => $firma->id, 'tarih' => now(), 'gundem' => [], 'kararlar' => [], 'katilimcilar' => [
            ['ad_soyad' => 'Ayse  Yılmaz', 'gorev' => 'Operatör', 'rol' => null, 'katildi' => true], // büyük/küçük + boşluk farkı
            ['ad_soyad' => 'Başka Kişi', 'gorev' => 'Usta', 'rol' => null, 'katildi' => true],
        ]]);
        $atama = $firma->atamaYazilari()->create(['rol_anahtari' => 'calisan_temsilcisi', 'uyeler' => [['ad_soyad' => 'X Y', 'tc' => '11111111110', 'gorev' => 'Operatör']]]);
        $izin = DB::table('is_izin_formlari')->insertGetId(['firma_id' => $firma->id, 'calisanlar' => json_encode([['ad_soyad' => 'AYSE YILMAZ', 'tc' => '11111111110', 'departman' => 'Üretim']])]);
        $plan = DB::table('acil_durum_planlari')->insertGetId(['firma_id' => $firma->id, 'ekipler' => json_encode(['sondurme' => ['AYSE YILMAZ', 'Diğer Kişi']])]);
        $muayene = DB::table('muayene_formlari')->insertGetId(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'AYSE YILMAZ', 'calisan_tc' => '11111111110', 'calisan_gorevi' => 'Farklı elle yazılmış görev']);
        $uye = $firma->kurulUyeleri()->create(['rol' => 'calisan_temsilcisi', 'ad_soyad' => 'AYSE YILMAZ', 'gorev' => 'Operatör', 'calisan_id' => $calisan->id, 'aktif' => true]);
        // Başka firmadaki aynı ad — dokunulmamalı
        $baskaMuayene = DB::table('muayene_formlari')->insertGetId(['firma_id' => $baskaFirma->id, 'calisan_ad_soyad' => 'AYSE YILMAZ']);

        $calisan->update(['ad_soyad' => 'AYŞE YILMAZ', 'tc' => '22222222220', 'gorev' => 'Kalite Teknisyeni']);

        $k = $kurul->fresh()->katilimcilar;
        $this->assertSame('AYŞE YILMAZ', $k[0]['ad_soyad']);
        $this->assertSame('Kalite Teknisyeni', $k[0]['gorev']);
        $this->assertSame('Başka Kişi', $k[1]['ad_soyad']);

        $u = $atama->fresh()->uyeler[0]; // T.C. ile eşleşti (ad farklı yazılmıştı)
        $this->assertSame(['AYŞE YILMAZ', '22222222220', 'Kalite Teknisyeni'], [$u['ad_soyad'], $u['tc'], $u['gorev']]);

        $this->assertSame('22222222220', $this->jsonOku('is_izin_formlari', $izin, 'calisanlar')[0]['tc']);
        $this->assertSame(['AYŞE YILMAZ', 'Diğer Kişi'], $this->jsonOku('acil_durum_planlari', $plan, 'ekipler')['sondurme']);

        $m = DB::table('muayene_formlari')->find($muayene);
        $this->assertSame('AYŞE YILMAZ', $m->calisan_ad_soyad);
        $this->assertSame('Farklı elle yazılmış görev', $m->calisan_gorevi); // eski görevle eşleşmiyordu → korunur

        $this->assertSame('AYŞE YILMAZ', $uye->fresh()->ad_soyad);
        $this->assertSame('AYSE YILMAZ', DB::table('muayene_formlari')->find($baskaMuayene)->calisan_ad_soyad);

        $this->assertSame(6, BagliKayitGuncelleyici::sonucuAl());
    }

    public function test_firma_isveren_vekili_degisince_kopyalari_guncellenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['isveren_vekili' => 'Eski Müdür']);
        $atama = $firma->atamaYazilari()->create(['rol_anahtari' => 'calisan_temsilcisi', 'isveren_vekili_adi' => 'Eski Müdür', 'uyeler' => []]);
        $kurul = KurulToplantisi::create(['firma_id' => $firma->id, 'tarih' => now(), 'gundem' => [], 'kararlar' => [], 'katilimcilar' => [
            ['ad_soyad' => 'Eski Müdür', 'gorev' => 'İşveren Vekili', 'rol' => 'baskan', 'katildi' => true],
        ]]);
        $uye = $firma->kurulUyeleri()->create(['rol' => 'baskan', 'ad_soyad' => 'Eski Müdür', 'aktif' => true]);
        $olay = DB::table('olay_kayitlari')->insertGetId(['firma_id' => $firma->id, 'isveren_vekili' => 'eski müdür']);

        $firma->update(['isveren_vekili' => 'Yeni Müdür']);

        $this->assertSame('Yeni Müdür', $atama->fresh()->isveren_vekili_adi);
        $this->assertSame('Yeni Müdür', $kurul->fresh()->katilimcilar[0]['ad_soyad']);
        $this->assertSame('İşveren Vekili', $kurul->fresh()->katilimcilar[0]['gorev']);
        $this->assertSame('Yeni Müdür', $uye->fresh()->ad_soyad);
        $this->assertSame('Yeni Müdür', DB::table('olay_kayitlari')->find($olay)->isveren_vekili);
    }

    public function test_igu_adi_duzeltilince_egitim_ve_sertifika_kopyalari_guncellenir(): void
    {
        $igu = IsgProfesyoneli::create(['user_id' => $this->uzman->id, 'tip' => 'igu', 'ad_soyad' => 'Mehmet Ozdemir']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);
        $katilim = DB::table('egitim_katilimlari')->insertGetId(['firma_id' => $firma->id, 'isg_uzmani_adi' => 'Mehmet Ozdemir']);
        $sertifika = DB::table('sertifikalar')->insertGetId(['firma_id' => $firma->id, 'tip' => 'genel', 'egitici_igu_adi' => 'MEHMET OZDEMIR']);

        $igu->update(['ad_soyad' => 'Mehmet Özdemir']);

        $this->assertSame('Mehmet Özdemir', DB::table('egitim_katilimlari')->find($katilim)->isg_uzmani_adi);
        $this->assertSame('Mehmet Özdemir', DB::table('sertifikalar')->find($sertifika)->egitici_igu_adi);
    }

    public function test_calisan_duzenle_sayfasinda_kaydedince_bildirim_cikar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Ali Veli', 'aktif' => true]);
        KurulToplantisi::create(['firma_id' => $firma->id, 'tarih' => now(), 'gundem' => [], 'kararlar' => [], 'katilimcilar' => [
            ['ad_soyad' => 'Ali Veli', 'gorev' => null, 'rol' => null, 'katildi' => true],
        ]]);

        Livewire::test(EditCalisan::class, ['record' => $calisan->getKey()])
            ->fillForm(['ad_soyad' => 'Ali Veli Kaya'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Bağlı 1 evrak da güncellendi');
    }
}
