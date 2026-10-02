<?php

namespace Tests\Feature;

use App\Filament\Pages\KkdTakip;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\KkdStokKarti;
use App\Models\KkdZimmet;
use App\Models\User;
use App\Support\KkdStok;
use App\Support\KkdTakipUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KkdTakipTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    private function kart(int $mevcut = 10, int $asgari = 0, array $ek = []): KkdStokKarti
    {
        $kart = $this->firma->kkdStokKartlari()->create([
            'kategori' => 'bas_yuz', 'tur' => 'Endüstriyel Emniyet Bareti', 'marka' => 'X', 'asgari' => $asgari, ...$ek,
        ]);
        KkdStok::giris($kart, $mevcut, 'İlk stok');

        return $kart->fresh();
    }

    public function test_yeni_stok_karti_ilk_stok_hareketiyle_olusur(): void
    {
        Livewire::test(KkdTakip::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('yeniStokKarti', [
                'kategori' => 'el_kol', 'tur' => 'Kaynakçı Eldiveni', 'marka' => 'Ansell', 'beden' => '10',
                'mevcut' => 25, 'asgari' => 5,
            ])
            ->assertHasNoActionErrors();

        $kart = KkdStokKarti::sole();
        $this->assertSame(25, $kart->mevcut);
        $this->assertSame(1, $kart->hareketler()->count());
        $this->assertSame('giris', $kart->hareketler()->first()->tip);
        $this->assertSame('yeterli', $kart->durum());
    }

    public function test_zimmet_stoktan_duser_ve_calisan_bilgisi_dolar(): void
    {
        $kart = $this->kart(10);
        $c = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Veli Kaya', 'gorev' => 'Kaynakçı']);

        Livewire::test(KkdTakip::class)
            ->set('firmaId', $this->firma->id)
            ->mountAction('yeniZimmet')
            ->fillForm(['calisan_id' => $c->id])
            ->assertSchemaStateSet(['personel_ad_soyad' => 'Veli Kaya', 'bolum' => 'Kaynakçı'])
            ->fillForm(['kkd_stok_karti_id' => $kart->id])
            ->assertSchemaStateSet(['tur' => 'Endüstriyel Emniyet Bareti', 'marka' => 'X'])
            ->fillForm(['adet' => 3, 'yenileme_tarihi' => now()->addDays(10)->toDateString()])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $z = KkdZimmet::sole();
        $this->assertSame($c->id, $z->calisan_id);
        $this->assertSame(7, $kart->fresh()->mevcut);
        $this->assertSame('yaklasan', $z->takipDurumu());
        $this->assertSame(10, $z->kalanGun());
        $this->assertStringStartsWith('KKD-Z-', $z->zimmet_no);
    }

    public function test_takip_durumu_ve_stok_durumu_hesaplanir(): void
    {
        $gec = new KkdZimmet(['durum' => 'teslim_edildi', 'tur' => 'a', 'yenileme_tarihi' => now()->subDay(), 'son_kullanma' => now()->addYear()]);
        $this->assertSame('gecikmis', $gec->takipDurumu());

        $uzak = new KkdZimmet(['durum' => 'teslim_edildi', 'tur' => 'a', 'son_kullanma' => now()->addDays(200)]);
        $this->assertSame('aktif', $uzak->takipDurumu());
        $this->assertSame('aktif', (new KkdZimmet(['durum' => 'teslim_edildi', 'tur' => 'a']))->takipDurumu());
        $this->assertSame('iade_edildi', (new KkdZimmet(['durum' => 'iade_edildi', 'tur' => 'a', 'yenileme_tarihi' => now()->subDay()]))->takipDurumu());

        $this->assertSame('dusuk', $this->kart(3, 5)->durum());
        $this->assertSame('tukendi', $this->kart(0, 0)->durum());
        $this->assertSame('suresi_gecmis', $this->kart(5, 0, ['son_kullanma' => now()->subDays(2)])->durum());
    }

    public function test_iade_stoga_geri_ekler_ve_kayip_eklemez(): void
    {
        $kart = $this->kart(10);
        $a = $this->firma->kkdZimmetleri()->create(['tur' => 'Baret', 'personel_ad_soyad' => 'A', 'adet' => 2, 'kkd_stok_karti_id' => $kart->id]);
        KkdStok::zimmetCikisi($a);
        $b = $this->firma->kkdZimmetleri()->create(['tur' => 'Baret', 'personel_ad_soyad' => 'B', 'adet' => 1, 'kkd_stok_karti_id' => $kart->id]);
        KkdStok::zimmetCikisi($b);
        $this->assertSame(7, $kart->fresh()->mevcut);

        $sayfa = Livewire::test(KkdTakip::class)->set('firmaId', $this->firma->id);

        $sayfa->callAction('durumDegistir', ['durum' => 'iade_edildi', 'iade_tarihi' => now()->toDateString(), 'stoga_geri' => true], ['id' => $a->id])
            ->assertHasNoActionErrors();
        $sayfa->callAction('durumDegistir', ['durum' => 'kayip', 'iade_tarihi' => now()->toDateString()], ['id' => $b->id])
            ->assertHasNoActionErrors();

        $this->assertSame('iade_edildi', $a->fresh()->durum);
        $this->assertSame('kayip', $b->fresh()->durum);
        $this->assertSame(9, $kart->fresh()->mevcut);
    }

    public function test_yenileme_eskiyi_kapatip_yeni_zimmet_acar(): void
    {
        $kart = $this->kart(5);
        $eski = $this->firma->kkdZimmetleri()->create([
            'tur' => 'Baret', 'personel_ad_soyad' => 'Ali', 'adet' => 1, 'kkd_stok_karti_id' => $kart->id,
            'teslim_tarihi' => now()->subYear(), 'yenileme_tarihi' => now()->subDay(),
        ]);

        Livewire::test(KkdTakip::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('zimmetYenile', [
                'teslim_tarihi' => now()->toDateString(),
                'kkd_stok_karti_id' => $kart->id,
                'yenileme_tarihi' => now()->addYear()->toDateString(),
                'seri_no' => 'SN-2',
            ], ['id' => $eski->id])
            ->assertHasNoActionErrors();

        $this->assertSame('yenilendi', $eski->fresh()->durum);
        $yeni = KkdZimmet::whereKeyNot($eski->id)->sole();
        $this->assertSame('Ali', $yeni->personel_ad_soyad);
        $this->assertSame('SN-2', $yeni->seri_no);
        $this->assertTrue($yeni->aktifMi());
        $this->assertNotSame($eski->zimmet_no, $yeni->zimmet_no);
        $this->assertSame(4, $kart->fresh()->mevcut);
    }

    public function test_aktif_zimmet_silinince_stok_geri_eklenir(): void
    {
        $kart = $this->kart(5);
        $z = $this->firma->kkdZimmetleri()->create(['tur' => 'Baret', 'personel_ad_soyad' => 'A', 'adet' => 2, 'kkd_stok_karti_id' => $kart->id]);
        KkdStok::zimmetCikisi($z);

        Livewire::test(KkdTakip::class)
            ->set('firmaId', $this->firma->id)
            ->call('zimmetSil', $z->id);

        $this->assertSame(0, KkdZimmet::count());
        $this->assertSame(5, $kart->fresh()->mevcut);
    }

    public function test_stok_hareketi_fire_ve_duzenleme(): void
    {
        $kart = $this->kart(10);

        $sayfa = Livewire::test(KkdTakip::class)->set('firmaId', $this->firma->id);
        $sayfa->callAction('stokHareket', ['tip' => 'fire', 'miktar' => 4, 'tarih' => now()->toDateString(), 'aciklama' => 'Kırık'], ['id' => $kart->id])
            ->assertHasNoActionErrors();
        $sayfa->callAction('stokDuzenle', ['tur' => 'Baret Yeni', 'asgari' => 8], ['id' => $kart->id])
            ->assertHasNoActionErrors();

        $kart->refresh();
        $this->assertSame(6, $kart->mevcut);
        $this->assertSame('Baret Yeni', $kart->tur);
        $this->assertSame('dusuk', $kart->durum());
        $this->assertSame(-4, $kart->hareketler()->where('tip', 'fire')->value('miktar'));
    }

    public function test_ozet_filtre_ve_arama(): void
    {
        $this->firma->kkdZimmetleri()->create(['tur' => 'Baret', 'personel_ad_soyad' => 'Ali Veli', 'yenileme_tarihi' => now()->subDay()]);
        $this->firma->kkdZimmetleri()->create(['tur' => 'Eldiven', 'personel_ad_soyad' => 'Ayşe', 'yenileme_tarihi' => now()->addDays(5)]);
        $this->firma->kkdZimmetleri()->create(['tur' => 'Gözlük', 'personel_ad_soyad' => 'Can', 'durum' => 'iade_edildi']);
        $this->kart(1, 3);

        $sayfa = Livewire::test(KkdTakip::class)->set('firmaId', $this->firma->id);
        $this->assertSame(['aktif' => 2, 'yaklasan' => 1, 'gecikmis' => 1, 'dusuk_stok' => 1], $sayfa->instance()->ozet);

        $sayfa->set('durumFiltre', 'gecikmis');
        $this->assertSame(['Ali Veli'], $sayfa->instance()->zimmetler->pluck('personel_ad_soyad')->all());

        $sayfa->set('durumFiltre', null)->set('arama', 'eldi');
        $this->assertSame(['Ayşe'], $sayfa->instance()->zimmetler->pluck('personel_ad_soyad')->all());
    }

    public function test_baska_firmanin_zimmeti_duzenlenemez(): void
    {
        $baska = Firma::factory()->for(User::factory())->create();
        $z = $baska->kkdZimmetleri()->create(['tur' => 'Baret', 'personel_ad_soyad' => 'X']);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(KkdTakip::class)
            ->set('firmaId', $this->firma->id)
            ->call('zimmetSil', $z->id);
    }

    public function test_excel_ve_personel_zimmet_formu_uretilir(): void
    {
        $c = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Veli Kaya']);
        $z = $this->firma->kkdZimmetleri()->create([
            'calisan_id' => $c->id, 'personel_ad_soyad' => 'Veli Kaya', 'tur' => 'Endüstriyel Emniyet Bareti',
            'adet' => 1, 'teslim_tarihi' => now(), 'yenileme_tarihi' => now()->addYear(),
        ]);
        $this->firma->kkdZimmetleri()->create(['calisan_id' => $c->id, 'personel_ad_soyad' => 'Veli Kaya', 'tur' => 'Kulak Tıkacı', 'adet' => 2, 'teslim_tarihi' => now()]);

        $this->assertSame('TS EN 397', $z->standart());

        ob_start();
        KkdTakipUretici::personelZimmetFormu($z)->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        $tmp = tempnam(sys_get_temp_dir(), 'kkt').'.xlsx';
        ob_start();
        KkdTakipUretici::excel($this->firma, $this->firma->kkdZimmetleri()->get())->sendContent();
        file_put_contents($tmp, ob_get_clean());
        $sayfa = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);

        $this->assertSame('Personel', $sayfa->getCell('C1')->getValue());
        $this->assertSame(3, $sayfa->getHighestRow());
    }
}
