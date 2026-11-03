<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumEkipleri;
use App\Filament\Pages\CalisanTemsilcisiSecimi;
use App\Filament\Pages\KurulToplantisi as KurulSayfasi;
use App\Models\AcilEkip;
use App\Models\Calisan;
use App\Models\CalisanTemsilcisiSecimi as Secim;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\User;
use App\Support\EgitimPlanlayici;
use App\Support\EkipAtamaEgitim;
use App\Support\KurulAtamaEgitim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Atama + eğitim doğrudan kendi modülünde: eğitimi olmayan üyeler için Eğitim Katılım'da otomatik PLANLI kayıt (03.11.2026). */
class EgitimPlanlayiciTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['calisan_sayisi' => 80, 'tehlike_sinifi' => 'tehlikeli']);
    }

    private function e(string $ad): array
    {
        return ['ad_soyad' => $ad, 'tc' => null, 'gorev' => 'Görev'];
    }

    public function test_planli_kayit_acilir_egitim_alinmis_sayilmaz_ve_kopya_birikmez(): void
    {
        $k = EgitimPlanlayici::planla($this->firma, 'isg_kurulu', [$this->e('Ali Veli'), $this->e('Ayşe Kaya')], 'kurul');

        $this->assertNotNull($k);
        $this->assertSame('isg_kurulu', $k->baslik_anahtari);
        $this->assertSame('kurul', $k->konu_secimleri['planli_kaynak']);
        $this->assertSame([false, false], array_column($k->katilimcilar, 'katildi'));
        $this->assertSame([], $k->belgeAlacakKatilimcilar(), 'planlı katılımcı belge/eğitim sayılmaz');
        $this->assertNotEmpty($k->konu_secimleri['maddeler'] ?? $k->konu_secimleri);

        // Aynı kaynak tekrar planlanınca aynı kayıt güncellenir.
        $k2 = EgitimPlanlayici::planla($this->firma, 'isg_kurulu', [$this->e('Ali Veli'), $this->e('Ayşe Kaya'), $this->e('Can Er')], 'kurul');
        $this->assertSame($k->id, $k2->id);
        $this->assertSame(1, EgitimKatilim::where('firma_id', $this->firma->id)->count());
        $this->assertSame(['Ali Veli', 'Ayşe Kaya', 'Can Er'], array_column($k2->fresh()->katilimcilar, 'ad_soyad'));

        // Eksik olmayan (eğitimi alınan / gruptan çıkan) bekleyen çıkarılır.
        $k3 = EgitimPlanlayici::planla($this->firma, 'isg_kurulu', [$this->e('Can Er')], 'kurul');
        $this->assertSame(['Can Er'], array_column($k3->fresh()->katilimcilar, 'ad_soyad'));

        // Kimse kalmazsa boş planlı kayıt silinir.
        $this->assertNull(EgitimPlanlayici::planla($this->firma, 'isg_kurulu', [], 'kurul'));
        $this->assertSame(0, EgitimKatilim::where('firma_id', $this->firma->id)->count());
    }

    public function test_sonuclanan_katilimcilar_korunur_ve_yeni_kayit_acilir(): void
    {
        $k = EgitimPlanlayici::planla($this->firma, 'isg_kurulu', [$this->e('Ali Veli'), $this->e('Ayşe Kaya')], 'kurul');

        // Eğitim yapıldı: Ali "Katıldı" işaretlendi (mevcut Katılım / Sonuç akışı), Ayşe hâlâ bekliyor.
        $liste = $k->katilimcilar;
        $liste[0]['katildi'] = true;
        $k->update(['katilimcilar' => $liste]);

        // Ali artık eksik değil → bekleyenlerden yalnız Ayşe + yeni gelen Can planlanır; Ali'nin sonucu korunur.
        $son = EgitimPlanlayici::planla($this->firma, 'isg_kurulu', [$this->e('Ayşe Kaya'), $this->e('Can Er')], 'kurul');
        $this->assertSame($k->id, $son->id);
        $liste = collect($son->fresh()->katilimcilar);
        $this->assertTrue($liste->firstWhere('ad_soyad', 'Ali Veli')['katildi']);
        $this->assertFalse($liste->firstWhere('ad_soyad', 'Can Er')['katildi']);

        // Hepsi tamamlanınca açık kayıt kalmaz; sonra gelen yeni eksik için YENİ planlı kayıt açılır.
        $tum = collect($son->fresh()->katilimcilar)->map(fn ($x) => [...$x, 'katildi' => true])->all();
        $son->update(['katilimcilar' => $tum]);
        $this->assertNull(EgitimPlanlayici::acikKayit($this->firma, 'isg_kurulu', 'kurul'));

        $yeni = EgitimPlanlayici::planla($this->firma, 'isg_kurulu', [$this->e('Zeynep Yeni')], 'kurul');
        $this->assertNotSame($son->id, $yeni->id);
        $this->assertSame(2, EgitimKatilim::where('firma_id', $this->firma->id)->count());
    }

    public function test_kurul_uyesi_eklenince_planli_egitim_acilir_ve_sonuclaninca_gorev_kalkar(): void
    {
        $c = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Ayşe Temsilci', 'tc' => '11111111111']);

        Livewire::test(KurulSayfasi::class)->set('firmaId', $this->firma->id)
            ->set('uyeRol', 'calisan_temsilcisi')
            ->call('calisaniEkle', $c->id)
            ->assertSee('planlandı:');

        $planli = KurulAtamaEgitim::planliEgitim($this->firma);
        $this->assertNotNull($planli);
        $this->assertSame(['Ayşe Temsilci'], array_column($planli->katilimcilar, 'ad_soyad'));
        $this->assertSame('11111111111', $planli->katilimcilar[0]['tc']);

        // Planlıyken eğitimi hâlâ eksik (görev sürer).
        $this->assertSame(1, KurulAtamaEgitim::egitimDurumu($this->firma)['eksik']->count());

        // "Katılım / Sonuç" ile katıldı işaretlenince eğitim alındı sayılır.
        $planli->update(['katilimcilar' => [['ad_soyad' => 'Ayşe Temsilci', 'tc' => '11111111111', 'gorev' => 'x', 'katildi' => true]]]);
        $this->assertSame(0, KurulAtamaEgitim::egitimDurumu($this->firma)['eksik']->count());
        $this->assertNull(KurulAtamaEgitim::planliEgitim($this->firma));
    }

    public function test_acil_ekip_uyesi_eklenince_ekibe_ozel_planli_kayit_acilir(): void
    {
        $ekip = AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => 'ilk_yardim', 'ad' => 'İlk Yardım Ekibi']);

        $sayfa = Livewire::test(AcilDurumEkipleri::class)->set('firmaId', $this->firma->id)
            ->callAction('uyeEkle', ['acil_ekip_id' => $ekip->id, 'ad_soyad' => 'Belgesiz Kişi', 'uyelik' => 'asil']);

        $planli = EkipAtamaEgitim::acilPlanliEgitim($this->firma, 'ilk_yardim');
        $this->assertNotNull($planli);
        $this->assertSame('ilkyardim_ekibi', $planli->baslik_anahtari);
        $this->assertSame('acil_ilk_yardim', $planli->konu_secimleri['planli_kaynak']);
        $this->assertSame(['Belgesiz Kişi'], array_column($planli->katilimcilar, 'ad_soyad'));

        // Eğitim belgesi girilen üye eksik olmaz → bekleyen çıkar, kayıt boşalınca silinir.
        $uye = $ekip->uyeler()->first();
        $ekip->uyeler()->update(['belge_no' => 'B-1', 'belge_tarihi' => now()->subMonth()]);
        $sayfa->call('liderYap', $uye->id);
        $this->assertNull(EkipAtamaEgitim::acilPlanliEgitim($this->firma, 'ilk_yardim'));
        $this->assertSame(0, EgitimKatilim::where('firma_id', $this->firma->id)->where('baslik_anahtari', 'ilkyardim_ekibi')->count());

        // Ekip silinince (üyesi kalmayınca) başka ekiplerin kayıtları etkilenmez; ilgili planlı kayıt temizlenir.
        $ekip2 = AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => 'sondurme', 'ad' => 'Söndürme Ekibi']);
        $sayfa->callAction('uyeEkle', ['acil_ekip_id' => $ekip2->id, 'ad_soyad' => 'Söndürücü', 'uyelik' => 'asil']);
        $this->assertNotNull(EkipAtamaEgitim::acilPlanliEgitim($this->firma, 'sondurme'));
        $sayfa->call('ekipSil', $ekip2->id);
        $this->assertNull(EkipAtamaEgitim::acilPlanliEgitim($this->firma, 'sondurme'));
    }

    public function test_temsilci_kazanan_kaydedilince_planli_egitim_acilir(): void
    {
        Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Ayşe Temsilci', 'tc' => '22222222222']);
        $secim = Secim::firmaIcin($this->firma);
        $secim->forceFill(['adaylar' => [['ad_soyad' => 'Ayşe Temsilci', 'unvan' => 'Kaynakçı']], 'secilen_aday_index' => 0])->save();

        Livewire::test(CalisanTemsilcisiSecimi::class)->set('firmaId', $this->firma->id)->call('kaydetVeBildir');

        $planli = EkipAtamaEgitim::temsilciPlanliEgitim($this->firma);
        $this->assertNotNull($planli);
        $this->assertSame('calisan_temsilcisi', $planli->baslik_anahtari);
        $this->assertSame(['Ayşe Temsilci'], array_column($planli->katilimcilar, 'ad_soyad'));

        // Tekrar kaydetmek ikinci kayıt açmaz.
        Livewire::test(CalisanTemsilcisiSecimi::class)->set('firmaId', $this->firma->id)->call('kaydetVeBildir');
        $this->assertSame(1, EgitimKatilim::where('firma_id', $this->firma->id)->where('baslik_anahtari', 'calisan_temsilcisi')->count());
    }

    public function test_grup_egitiminde_uyeler_otomatik_gelir_gorev_gruptaki_roldur_ek_katilimci_kalir(): void
    {
        $sef = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Kemal Şef', 'tc' => '11111111111', 'gorev' => 'Şantiye Şefi']);
        $isci = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Ahmet İşçi', 'tc' => '22222222222', 'gorev' => 'Kalıpçı']);
        $diger = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Karışmayan', 'gorev' => 'Depocu']);

        // Kurul: Kemal İK sorumlusu, Ahmet çalışan temsilcisi, dışarıdan İGU (çalışan değil).
        $this->firma->kurulUyeleri()->create(['rol' => 'ik', 'ad_soyad' => 'Kemal Şef', 'calisan_id' => $sef->id, 'aktif' => true]);
        $this->firma->kurulUyeleri()->create(['rol' => 'calisan_temsilcisi', 'ad_soyad' => 'AHMET İŞÇİ', 'aktif' => true]);
        $this->firma->kurulUyeleri()->create(['rol' => 'sekreter', 'ad_soyad' => 'Dışarıdan İGU', 'aktif' => true]);

        $e = Livewire::test(\App\Filament\Pages\EgitimKatilim::class)
            ->set('firmaId', $this->firma->id)
            ->set('baslikAnahtari', 'isg_kurulu')
            ->assertSet('uyelerOtomatik', true);

        // Firma çalışanı olan üyeler işaretli (id veya ad eşleşmesiyle), diğer çalışanlar işaretsiz; çalışan olmayan üye ek katılımcı.
        $secilen = $e->get('secilenCalisanIdler');
        sort($secilen);
        $this->assertSame([$sef->id, $isci->id], $secilen);
        $this->assertNotContains($diger->id, $secilen);
        $this->assertSame(['Dışarıdan İGU'], array_column($e->get('manuelKatilimcilar'), 'ad_soyad'));

        // Görev = gruptaki rol (normal işi değil).
        $this->assertSame('İK / Personel / İdari-Mali İşler Sorumlusu', $e->get('grupGorevleri')[$sef->id]);
        $this->assertSame('Çalışan Temsilcisi (Baş Temsilci)', $e->get('grupGorevleri')[$isci->id], 'ad eşleşmesiyle bulunan temsilcinin görevi de kurul rolü');

        // Kayıtta katılımcı görevi rol olarak yazılır; ek katılımcı eklenebilir (mevcut mekanizma).
        $e->call('calisanToggle', $diger->id);   // fazladan çalışan seçildi (ek katılımcı)
        $e->callAction('sadeceKaydet');
        $kayit = EgitimKatilim::where('firma_id', $this->firma->id)->where('baslik_anahtari', 'isg_kurulu')->latest('id')->firstOrFail();
        $gorevler = collect($kayit->katilimcilar)->pluck('gorev', 'ad_soyad');
        $this->assertSame('İK / Personel / İdari-Mali İşler Sorumlusu', $gorevler['Kemal Şef']);
        $this->assertSame('Depocu', $gorevler['Karışmayan'], 'elle eklenen çalışanın normal görevi');
        $this->assertTrue($gorevler->has('Dışarıdan İGU'));
        // Çalışan olmayan grup üyesi çalışan listesine EKLENMEZ.
        $this->assertSame(0, Calisan::where('firma_id', $this->firma->id)->where('ad_soyad', 'Dışarıdan İGU')->count());

        // Grup eğitimi olmayan başlığa dönünce herkes yeniden işaretli, ek katılımcılar temizlenir.
        $e->set('baslikAnahtari', 'genel')->assertSet('uyelerOtomatik', false)->assertSet('manuelKatilimcilar', []);
        $this->assertCount(3, $e->get('secilenCalisanIdler'));
    }

    public function test_acil_ekip_ve_temsilci_egitiminde_roller_gelir(): void
    {
        $c1 = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Lider Kişi', 'gorev' => 'Formen']);
        $ekip = AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => 'sondurme', 'ad' => 'Söndürme Ekibi']);
        $ekip->uyeler()->create(['calisan_id' => $c1->id, 'ad_soyad' => 'Lider Kişi', 'uyelik' => 'asil', 'lider' => true]);
        $ekip->uyeler()->create(['ad_soyad' => 'Yedek Dışarıdan', 'uyelik' => 'yedek']);
        $ekip2 = AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => 'kurtarma', 'ad' => 'Kurtarma Ekibi']);
        $ekip2->uyeler()->create(['calisan_id' => $c1->id, 'ad_soyad' => 'Lider Kişi', 'uyelik' => 'asil']);

        $e = Livewire::test(\App\Filament\Pages\EgitimKatilim::class)->set('firmaId', $this->firma->id)->set('baslikAnahtari', 'sondurme_ekibi');
        $this->assertSame([$c1->id], $e->get('secilenCalisanIdler'));
        $this->assertSame('Söndürme Ekibi — Ekip Lideri', $e->get('grupGorevleri')[$c1->id]);
        $this->assertSame(['Yedek Dışarıdan'], array_column($e->get('manuelKatilimcilar'), 'ad_soyad'));
        $this->assertSame('Söndürme Ekibi — Yedek Üye', $e->get('manuelKatilimcilar')[0]['gorev']);

        // Destek elemanları: tüm ekipler, aynı kişinin rolleri birleşir.
        $d = Livewire::test(\App\Filament\Pages\EgitimKatilim::class)->set('firmaId', $this->firma->id)->set('baslikAnahtari', 'destek_elemanlari');
        $this->assertSame('Söndürme Ekibi — Ekip Lideri, Kurtarma Ekibi — Üye', $d->get('grupGorevleri')[$c1->id]);

        // Çalışan temsilcisi: görev "Çalışan Temsilcisi".
        $this->firma->kurulUyeleri()->create(['rol' => 'calisan_temsilcisi', 'ad_soyad' => 'Temsilci Kişi', 'aktif' => true]);
        $t = Livewire::test(\App\Filament\Pages\EgitimKatilim::class)->set('firmaId', $this->firma->id)->set('baslikAnahtari', 'calisan_temsilcisi');
        $this->assertSame('Çalışan Temsilcisi', $t->get('manuelKatilimcilar')[0]['gorev']);

        // Üyesi olmayan grup: uyarı, mevcut seçim bozulmaz.
        $bos = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($bos)->count(2)->create();
        $b = Livewire::test(\App\Filament\Pages\EgitimKatilim::class)->set('firmaId', $bos->id)->set('baslikAnahtari', 'koruma_ekibi');
        $b->assertSet('uyelerOtomatik', false);
        $this->assertCount(2, $b->get('secilenCalisanIdler'));
    }
}
