<?php

namespace Tests\Feature;

use App\Filament\Resources\Calisans\Pages\ListCalisans;
use App\Filament\Resources\Firmas\Pages\EditFirma;
use App\Filament\Resources\Firmas\RelationManagers\CalisanlarRelationManager;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use App\Support\CalisanExcelIceAktarici;
use App\Support\CalisanListesiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * isgsuite "Personel Yönetimi" karşılaştırmasıyla eklenen alanlar ve aksiyonlar:
 * cinsiyet/şube/özel durum, pasife alma, Excel/PDF rapor, isgsuite şablonunun okunması.
 */
class PersonelKayitTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Deneme İnşaat']);
    }

    private function xlsx(array $satirlar): string
    {
        $kitap = new Spreadsheet;
        foreach ($satirlar as $i => $satir) {
            $kitap->getActiveSheet()->fromArray($satir, null, 'A'.($i + 1));
        }
        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_isgsuite_sablon_basliklari_okunur_ve_cikisli_kayit_pasif_olur(): void
    {
        $yol = $this->xlsx([
            ['#', 'Adı Soyadı', 'TC Kimlik No', 'Görevi', 'Departman', 'Şube', 'Cinsiyet', 'İşe Giriş', 'İşten Çıkış', 'Özel Durum'],
            [1, 'Ayşe Demir', '12345678901', 'Teknisyen', 'Bakım', 'Merkez', 'Kadın', '02.09.2026', '', 'Engelli'],
            [2, 'Mehmet Kaya', '', 'Usta', '', 'Şantiye 2', 'Erkek', '01.01.2025', '15.09.2026', ''],
        ]);

        $sonuc = CalisanExcelIceAktarici::iceAktar($yol, $this->firma->id);
        unlink($yol);

        $this->assertSame(2, $sonuc['basarili']);

        $ayse = Calisan::where('ad_soyad', 'Ayşe Demir')->firstOrFail();
        $this->assertSame('kadin', $ayse->cinsiyet);
        $this->assertSame('Merkez', $ayse->sube);
        $this->assertSame('Engelli', $ayse->ozel_durum);
        $this->assertTrue($ayse->aktif);

        $mehmet = Calisan::where('ad_soyad', 'Mehmet Kaya')->firstOrFail();
        $this->assertSame('erkek', $mehmet->cinsiyet);
        $this->assertFalse($mehmet->aktif, 'Çıkış tarihi girilen kayıt pasife alınmalı');
    }

    public function test_bos_hucre_mevcut_bilgiyi_silmez_durum_sutunu_okunur(): void
    {
        Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Ali Veli', 'tc' => '11111111111', 'gorev' => 'Kaynakçı', 'aktif' => false]);

        $yol = $this->xlsx([
            ['Adı Soyadı', 'TC Kimlik No', 'Görevi', 'Durum'],
            ['Ali Veli', '11111111111', '', 'Aktif'],
        ]);
        CalisanExcelIceAktarici::iceAktar($yol, $this->firma->id);
        unlink($yol);

        $ali = Calisan::where('tc', '11111111111')->sole();
        $this->assertSame('Kaynakçı', $ali->gorev);
        $this->assertTrue($ali->aktif);
    }

    public function test_excel_rapor_geri_yuklenebilir(): void
    {
        Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Zeynep Ak', 'tc' => '01234567890', 'cinsiyet' => 'kadin', 'sube' => 'Merkez', 'ise_giris' => '2026-01-05']);

        $kitap = CalisanListesiUretici::excelKitabi($this->firma, 'tumu');
        $this->assertSame('01234567890', $kitap->getActiveSheet()->getCell('C2')->getValue(), 'TC başındaki sıfır korunmalı');

        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($kitap))->save($yol);
        Calisan::query()->delete();

        $sonuc = CalisanExcelIceAktarici::iceAktar($yol, $this->firma->id);
        unlink($yol);

        $this->assertSame(1, $sonuc['basarili']);
        $z = Calisan::sole();
        $this->assertSame('kadin', $z->cinsiyet);
        $this->assertSame('Merkez', $z->sube);
        $this->assertSame('2026-01-05', $z->ise_giris->toDateString());
        $this->assertTrue($z->aktif);
    }

    public function test_ozet_ve_gorunum_filtresi(): void
    {
        Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'A', 'cinsiyet' => 'kadin', 'ozel_durum' => 'Engelli']);
        Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'B', 'cinsiyet' => 'erkek']);
        Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'C']);
        Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'D', 'aktif' => false]);

        $aktif = CalisanListesiUretici::calisanlar($this->firma);
        $this->assertSame(['toplam' => 3, 'kadin' => 1, 'erkek' => 1, 'belirtilmemis' => 1, 'engelli' => 1], CalisanListesiUretici::ozet($aktif));
        $this->assertCount(1, CalisanListesiUretici::calisanlar($this->firma, 'pasif'));
        $this->assertCount(4, CalisanListesiUretici::calisanlar($this->firma, 'tumu'));
    }

    public function test_pdf_rapor_uretilir(): void
    {
        Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Pdf Çalışan', 'cinsiyet' => 'erkek']);

        ob_start();
        CalisanListesiUretici::pdf($this->firma, 'tumu')->sendContent();
        $icerik = ob_get_clean();

        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_toplu_personel_dosyasi_egitim_ve_saglik_icerir(): void
    {
        $ayse = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Ayşe İnce', 'tc' => '01234567890', 'kan_grubu' => 'A Rh+']);
        $ali = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Ali Veli']);

        \App\Models\EgitimKaydi::create(['calisan_id' => $ayse->id, 'tur' => 'temel_isg', 'tarih' => '2026-03-01']);
        \App\Models\EgitimKatilim::create(['firma_id' => $this->firma->id, 'baslik_anahtari' => 'genel', 'belge_tarihi' => '2026-04-10', 'sure_gun' => 1,
            'katilimcilar' => [['ad_soyad' => 'AYŞE İNCE', 'tc' => '01234567890'], ['ad_soyad' => 'ali veli'], ['ad_soyad' => 'Firmada Olmayan']]]);
        \App\Models\MuayeneFormu::create(['firma_id' => $this->firma->id, 'calisan_id' => $ayse->id, 'muayene_turu' => 'periyodik',
            'muayene_tarihi' => '2026-02-01', 'onerilen_kontrol_tarihi' => '2029-02-01', 'hekim_adi' => 'Dr. Hekim']);
        \App\Models\SaglikGozetimi::create(['firma_id' => $this->firma->id, 'satirlar' => [
            ['calisan_id' => $ali->id, 'calisan_adi' => 'Ali Veli', 'tetkik_turu' => 'goz', 'tarih' => '2026-05-01', 'sonuc' => 'uygun'],
        ]]);

        $kitap = \App\Support\CalisanDosyasiUretici::kitap($this->firma, 'tumu');

        $this->assertSame(['Özet', 'Personel', 'Eğitimler', 'Sağlık'], $kitap->getSheetNames());

        $egitim = $kitap->getSheetByName('Eğitimler')->toArray();
        $this->assertCount(4, $egitim, 'başlık + 1 kayıt + 2 katılım (eşleşmeyen katılımcı atlanır)');
        $this->assertSame('Ali Veli', $egitim[1][0], 'ada göre sıralı');
        $this->assertSame('01234567890', $egitim[2][1]);

        $saglik = $kitap->getSheetByName('Sağlık')->toArray();
        $this->assertCount(3, $saglik);
        $this->assertSame('A Rh+', $saglik[2][2]);
        $this->assertSame('Ali Veli', $saglik[1][0]);
        $this->assertSame('2027-05-01', \Illuminate\Support\Carbon::createFromFormat('d.m.Y', $saglik[1][6])->toDateString(), 'göz muayenesi 12 ayda bir');

        $ozet = $kitap->getSheetByName('Özet')->toArray();
        $this->assertSame('Ali Veli', $ozet[4][0]);
        $this->assertEquals(1, $ozet[4][4]);
        $this->assertEquals(2, $ozet[5][4]);
        $this->assertSame('01.02.2029', $ozet[5][8]);

        ob_start();
        \App\Support\CalisanDosyasiUretici::excel($this->firma)->sendContent();
        $this->assertStringStartsWith('PK', ob_get_clean());
    }

    public function test_toplu_personel_dosyasi_aksiyonu_calisir(): void
    {
        Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Bir']);

        Livewire::test(CalisanlarRelationManager::class, ['ownerRecord' => $this->firma, 'pageClass' => EditFirma::class])
            ->callTableAction('personelDosyasi', data: ['gorunum' => 'aktif'])
            ->assertHasNoTableActionErrors()
            ->assertFileDownloaded();
    }

    public function test_maskeli_tc(): void
    {
        $this->assertSame('123******01', (new Calisan(['tc' => '12345678901']))->maskeliTc());
        $this->assertNull((new Calisan)->maskeliTc());
    }

    public function test_pasife_al_cikis_tarihi_yazar_aktiflestir_temizler(): void
    {
        $c = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Pasif Olacak']);

        Livewire::test(ListCalisans::class)
            ->callTableAction('pasifeAl', $c);

        $c->refresh();
        $this->assertFalse($c->aktif);
        $this->assertSame(now()->toDateString(), $c->isten_cikis->toDateString());

        Livewire::test(ListCalisans::class)
            ->filterTable('aktif', false)
            ->callTableAction('aktifEt', $c);

        $c->refresh();
        $this->assertTrue($c->aktif);
        $this->assertNull($c->isten_cikis);
    }

    public function test_toplu_pasife_al(): void
    {
        $a = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Bir']);
        $b = Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => 'İki', 'isten_cikis' => '2026-05-01']);

        Livewire::test(ListCalisans::class)
            ->callTableBulkAction('topluPasif', [$a, $b]);

        $this->assertFalse($a->refresh()->aktif);
        $this->assertFalse($b->refresh()->aktif);
        $this->assertSame('2026-05-01', $b->isten_cikis->toDateString(), 'Mevcut çıkış tarihi ezilmemeli');
    }

    public function test_yeni_alanlar_formdan_kaydedilir(): void
    {
        Livewire::test(CalisanlarRelationManager::class, ['ownerRecord' => $this->firma, 'pageClass' => EditFirma::class])
            ->callTableAction('create', data: [
                'ad_soyad' => 'Form Çalışan',
                'cinsiyet' => 'kadin',
                'sube' => 'Depo',
                'ozel_durum' => 'Hükümlü',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('calisanlar', ['ad_soyad' => 'Form Çalışan', 'firma_id' => $this->firma->id, 'cinsiyet' => 'kadin', 'sube' => 'Depo', 'ozel_durum' => 'Hükümlü']);
    }

    public function test_bagimsiz_sayfada_excel_yukle_firma_secer(): void
    {
        $yol = $this->xlsx([['Ad Soyad'], ['Sayfadan Yüklenen']]);

        Livewire::test(ListCalisans::class)
            ->callAction('excelYukle', data: [
                'firma_id' => $this->firma->id,
                'dosya' => \Illuminate\Http\UploadedFile::fake()->createWithContent('p.xlsx', file_get_contents($yol)),
            ])
            ->assertHasNoActionErrors();
        unlink($yol);

        $this->assertDatabaseHas('calisanlar', ['ad_soyad' => 'Sayfadan Yüklenen', 'firma_id' => $this->firma->id]);
    }

    public function test_baska_kullanicinin_firmasina_yuklenemez(): void
    {
        $yabanci = Firma::factory()->for(User::factory())->create();
        $yol = $this->xlsx([['Ad Soyad'], ['Sızma']]);

        Livewire::test(ListCalisans::class)
            ->callAction('excelYukle', data: [
                'firma_id' => $yabanci->id,
                'dosya' => \Illuminate\Http\UploadedFile::fake()->createWithContent('p.xlsx', file_get_contents($yol)),
            ]);
        unlink($yol);

        $this->assertDatabaseMissing('calisanlar', ['ad_soyad' => 'Sızma']);
    }
}
