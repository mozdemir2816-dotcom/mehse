<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimYenilemeTakibi;
use App\Models\Calisan;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\IsbasiEgitimTutanagi;
use App\Models\User;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\EgitimTakibi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EgitimYenilemeTakibiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        // Çok tehlikeli → 1 yılda bir yenileme
        $this->firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);
    }

    private function calisan(string $ad, ?string $tc = null, array $ek = []): Calisan
    {
        return Calisan::factory()->for($this->firma)->create(['ad_soyad' => $ad, 'tc' => $tc, 'aktif' => true, ...$ek]);
    }

    public function test_durumlar_uc_kaynaktan_hesaplanir(): void
    {
        $kayitli = $this->calisan('Ali Veli', '11111111111');
        $kayitli->egitimKayitlari()->create(['tur' => EgitimTakibi::TEMEL_TUR, 'tarih' => now()->subMonths(2)]);

        $this->calisan('Ayşe Kaya', '22222222222');   // katılım formunda TC ile → süresi dolmuş
        $this->calisan('İSMAİL ÖZ');                  // katılım formunda adla (Türkçe büyük/küçük) → yaklaşan
        $this->calisan('Yeni Personel');              // kaydı yok
        $this->calisan('Ayrılan', null, ['aktif' => false]);

        EgitimKatilim::create([
            'firma_id' => $this->firma->id, 'baslik_anahtari' => 'genel', 'belge_tarihi' => now()->subMonths(14),
            'katilimcilar' => [['ad_soyad' => 'A. Kaya', 'tc' => '222 222 222 22']],
        ]);
        EgitimKatilim::create([
            'firma_id' => $this->firma->id, 'baslik_anahtari' => 'genel', 'belge_tarihi' => now()->subYears(2),
            'gun_tarihleri' => [now()->subYear()->addDays(20)->toDateString(), now()->subYear()->addDays(21)->toDateString()],
            'katilimcilar' => [['ad_soyad' => 'ismail öz', 'tc' => null]],
        ]);
        // Özel başlıklı eğitim temel sayılmaz
        EgitimKatilim::create([
            'firma_id' => $this->firma->id, 'baslik_anahtari' => 'yuksekte_calisma', 'belge_tarihi' => now(),
            'katilimcilar' => [['ad_soyad' => 'Yeni Personel']],
        ]);
        IsbasiEgitimTutanagi::create(['firma_id' => $this->firma->id, 'calisan_ad_soyad' => 'Ali Veli', 'calisan_tc' => '11111111111']);

        $satirlar = EgitimTakibi::satirlar($this->firma)->keyBy(fn ($s) => $s['calisan']->ad_soyad);

        $this->assertCount(4, $satirlar);
        $this->assertSame('gecerli', $satirlar['Ali Veli']['durum']);
        $this->assertSame('Eğitim kaydı', $satirlar['Ali Veli']['kaynak']);
        $this->assertTrue($satirlar['Ali Veli']['isbasi']);
        $this->assertSame('dolmus', $satirlar['Ayşe Kaya']['durum']);
        $this->assertSame('Yüz yüze katılım', $satirlar['Ayşe Kaya']['kaynak']);
        $this->assertSame('yaklasan', $satirlar['İSMAİL ÖZ']['durum']);
        $this->assertSame('kayit_yok', $satirlar['Yeni Personel']['durum']);
        $this->assertFalse($satirlar['Yeni Personel']['isbasi']);

        $ozet = EgitimTakibi::ozet($satirlar->values());
        $this->assertSame(['toplam' => 4, 'kayit_yok' => 1, 'dolmus' => 1, 'yaklasan' => 1, 'gecerli' => 1, 'isbasi_eksik' => 3, 'uyum' => 50], $ozet);
    }

    public function test_sayfa_filtre_arama_ve_bugun_ne_yapmaliyim(): void
    {
        $this->calisan('Ali Veli', null, ['gorev' => 'Kaynakçı']);
        $this->calisan('Mehmet Can');

        $sayfa = Livewire::test(EgitimYenilemeTakibi::class)
            ->set('firmaId', $this->firma->id)
            ->assertSee('Bugün ne yapmalıyım?')
            ->assertSee('Temel eğitim kaydı yok');

        $sayfa->set('arama', 'kaynak');
        $this->assertSame(['Ali Veli'], $sayfa->instance()->gosterilenSatirlar->map(fn ($s) => $s['calisan']->ad_soyad)->all());

        $sayfa->set('arama', null)->set('durumFiltre', 'gecerli');
        $this->assertCount(0, $sayfa->instance()->gosterilenSatirlar);
    }

    public function test_yuz_yuze_kayitlar_tum_firmalar_ve_baskasi_haric(): void
    {
        $diger = Firma::factory()->for($this->uzman)->create(['unvan' => 'Beta']);
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'baslik_anahtari' => 'genel', 'belge_tarihi' => now(), 'katilimcilar' => [['ad_soyad' => 'A'], ['ad_soyad' => 'B']]]);
        EgitimKatilim::create(['firma_id' => $diger->id, 'baslik_anahtari' => 'isyeri_hijyen', 'belge_tarihi' => now(), 'katilimcilar' => []]);
        EgitimKatilim::create(['firma_id' => Firma::factory()->for(User::factory())->create()->id, 'baslik_anahtari' => 'genel', 'belge_tarihi' => now()]);

        $sayfa = Livewire::test(EgitimYenilemeTakibi::class)->set('sekme', 'kayitlar');
        $this->assertCount(2, $sayfa->instance()->oturumlar);
        $sayfa->assertSee('İşyeri İçi Hijyen ve Sanitasyon Bilgilendirme Eğitimi');

        $sayfa->set('arama', 'hijyen');
        $this->assertSame([$diger->id], $sayfa->instance()->oturumlar->pluck('firma_id')->all());

        $sayfa->call('mountAction', 'oturum_excel')->assertFileDownloaded('yuz-yuze-egitim-kayitlari.xlsx');
    }

    public function test_sonuclandir_katilmayan_ve_basarisiz_belge_almaz_takipte_sayilmaz(): void
    {
        $this->calisan('Ali Veli', '11111111111');
        $this->calisan('Ayşe Kaya', '22222222222');
        $this->calisan('Can Er', '33333333333');

        $oturum = EgitimKatilim::create([
            'firma_id' => $this->firma->id, 'baslik_anahtari' => 'genel', 'belge_tarihi' => now()->subDays(3),
            'katilimcilar' => [
                ['ad_soyad' => 'Ali Veli', 'tc' => '11111111111', 'gorev' => 'Usta'],
                ['ad_soyad' => 'Ayşe Kaya', 'tc' => '22222222222'],
                ['ad_soyad' => 'Can Er', 'tc' => '33333333333'],
            ],
        ]);
        $this->assertFalse($oturum->sonuclandiMi());
        $this->assertCount(3, $oturum->belgeAlacakKatilimcilar());   // eski kayıtlar etkilenmez

        Livewire::test(EgitimYenilemeTakibi::class)
            ->set('sekme', 'kayitlar')
            ->assertSee('Sonuç bekliyor')
            ->callAction('sonucGir', [
                'katildi_0' => true, 'puan_0' => 85,
                'katildi_1' => true, 'puan_1' => 40,
                'katildi_2' => false, 'puan_2' => null,
            ], ['id' => $oturum->id])
            ->assertHasNoActionErrors()
            ->assertSee('1/3 başarılı');

        $oturum->refresh();
        $this->assertTrue($oturum->sonuclandiMi());
        $this->assertSame('Usta', $oturum->katilimcilar[0]['gorev']);
        $this->assertSame(85, $oturum->katilimcilar[0]['puan']);
        $this->assertSame(['Ali Veli'], array_column($oturum->belgeAlacakKatilimcilar(), 'ad_soyad'));

        $durum = EgitimTakibi::satirlar($this->firma)->keyBy(fn ($s) => $s['calisan']->ad_soyad)->map(fn ($s) => $s['durum'])->all();
        $this->assertSame(['Ali Veli' => 'gecerli', 'Ayşe Kaya' => 'kayit_yok', 'Can Er' => 'kayit_yok'], $durum);
    }

    public function test_sonuc_baska_kullanicinin_oturumuna_yazilamaz(): void
    {
        $yabanci = EgitimKatilim::create([
            'firma_id' => Firma::factory()->for(User::factory())->create()->id, 'baslik_anahtari' => 'genel', 'belge_tarihi' => now(),
            'katilimcilar' => [['ad_soyad' => 'X']],
        ]);

        Livewire::test(EgitimYenilemeTakibi::class)
            ->call('mountAction', 'sonucGir', ['id' => $yabanci->id])
            ->assertActionNotMounted('sonucGir');

        $this->assertArrayNotHasKey('katildi', $yabanci->refresh()->katilimcilar[0]);
    }

    public function test_hijyen_ozel_egitimleri_icerik_olusturucuda(): void
    {
        $icerik = EgitimIcerikOlusturucu::olustur('gida_su_hijyen', null, 'tehlikeli');

        $this->assertSame('ozel', $icerik['tip']);
        $this->assertSame('Gıda ve Su Sektöründe Hijyen Eğitimi', $icerik['ad']);
        $this->assertCount(6, $icerik['maddeler']);
    }
}
