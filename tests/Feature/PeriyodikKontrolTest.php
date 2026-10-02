<?php

namespace Tests\Feature;

use App\Filament\Pages\PeriyodikKontrol as PeriyodikKontrolSayfasi;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\PeriyodikKontrol;
use App\Models\User;
use App\Support\PeriyodikKontrolUretici;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class PeriyodikKontrolTest extends TestCase
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

    public function test_yeni_ekipman_tanimla_aksiyonu_kayit_olusturur(): void
    {
        Livewire::test(PeriyodikKontrolSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('yeniEkipman', [
                'kategori' => 'kaldirma_iletme',
                'tip' => 'Forklift / Akülü & Dizel İstifleyici',
                'ekipman_adi' => 'Forklift / Akülü & Dizel İstifleyici',
                'muayene_periyodu_ay' => '12',
                'seri_no' => 'FRK-01',
                'son_muayene_tarihi' => '2026-03-15',
            ]);

        $e = IsEkipmani::where('firma_id', $this->firma->id)->sole();
        $this->assertSame('kaldirma_iletme', $e->kategori);
        $this->assertSame('TS 10689 / TS ISO 3691', $e->yasal_standart);
        // son muayene 2026-03-15 + 12 ay → 2027-03-15
        $this->assertSame('2027-03-15', $e->sonraki_vize_tarihi->toDateString());
    }

    public function test_vize_durumu_sonraki_tarihe_gore_belirlenir(): void
    {
        $gecerli = IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'basincli_kap', 'ekipman_adi' => 'Kompresör', 'muayene_periyodu_ay' => 12, 'son_muayene_tarihi' => now()->subMonths(2)->toDateString(), 'sonuc' => 'uygun']);
        $yaklasan = IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'basincli_kap', 'ekipman_adi' => 'Buhar Kazanı', 'muayene_periyodu_ay' => 12, 'son_muayene_tarihi' => now()->subMonths(12)->addDays(20)->toDateString(), 'sonuc' => 'uygun']);
        $dolmus = IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'basincli_kap', 'ekipman_adi' => 'Hidrofor', 'muayene_periyodu_ay' => 12, 'son_muayene_tarihi' => now()->subMonths(18)->toDateString(), 'sonuc' => 'uygun']);
        $bekliyor = IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'basincli_kap', 'ekipman_adi' => 'Yeni Tank', 'muayene_periyodu_ay' => 12, 'sonuc' => 'bekliyor']);

        $this->assertSame('gecerli', $gecerli->fresh()->vizeDurumu());
        $this->assertSame('yaklasan', $yaklasan->fresh()->vizeDurumu());
        $this->assertSame('dolmus', $dolmus->fresh()->vizeDurumu());
        $this->assertSame('bekliyor', $bekliyor->fresh()->vizeDurumu());
    }

    public function test_kpi_kartlari_vize_durumlarini_sayar(): void
    {
        IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'basincli_kap', 'ekipman_adi' => 'A', 'muayene_periyodu_ay' => 12, 'son_muayene_tarihi' => now()->subMonths(1)->toDateString(), 'sonuc' => 'uygun']);
        IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'basincli_kap', 'ekipman_adi' => 'B', 'muayene_periyodu_ay' => 12, 'son_muayene_tarihi' => now()->subMonths(20)->toDateString(), 'sonuc' => 'uygun']);

        $kpi = Livewire::test(PeriyodikKontrolSayfasi::class)->set('firmaId', $this->firma->id)->get('kpi');

        $this->assertSame(2, $kpi['toplam']);
        $this->assertSame(1, $kpi['gecerli']);
        $this->assertSame(1, $kpi['dolmus']);
    }

    public function test_satir_ici_muayene_bilgisi_kaydedilir_ve_vize_hesaplanir(): void
    {
        $e = IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'elektrik_topraklama', 'ekipman_adi' => 'Topraklama Tesisatı', 'muayene_periyodu_ay' => 12, 'sonuc' => 'bekliyor']);

        $c = Livewire::test(PeriyodikKontrolSayfasi::class)->set('firmaId', $this->firma->id);
        $c->set('satirlar.0.son_muayene_tarihi', '2026-05-01')
            ->set('satirlar.0.muayene_yapan', 'X Muayene A.Ş.')
            ->set('satirlar.0.sonuc', 'uygun')
            ->call('kaydet');

        $e->refresh();
        $this->assertSame('2026-05-01', $e->son_muayene_tarihi->toDateString());
        $this->assertSame('2027-05-01', $e->sonraki_vize_tarihi->toDateString());
        $this->assertSame('uygun', $e->sonuc);
    }

    public function test_kategori_ve_durum_filtresi_uygulanir(): void
    {
        IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'kaldirma_iletme', 'ekipman_adi' => 'Vinç', 'muayene_periyodu_ay' => 12, 'son_muayene_tarihi' => now()->subMonths(1)->toDateString(), 'sonuc' => 'uygun']);
        IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'tesisat_yangin', 'ekipman_adi' => 'YSC', 'muayene_periyodu_ay' => 12, 'son_muayene_tarihi' => now()->subMonths(20)->toDateString(), 'sonuc' => 'uygun']);

        $c = Livewire::test(PeriyodikKontrolSayfasi::class)->set('firmaId', $this->firma->id);
        $this->assertCount(2, $c->get('satirlar'));

        $c->set('kategoriFiltre', 'kaldirma_iletme');
        $this->assertCount(1, $c->get('satirlar'));

        $c->set('kategoriFiltre', '')->set('durumFiltre', 'dolmus');
        $this->assertCount(1, $c->get('satirlar'));
        $this->assertSame('YSC', $c->get('satirlar')[0]['ekipman_adi']);
    }

    public function test_kontrol_merkezi_kriteri_muayene_tarihi_girilince_karsilanir(): void
    {
        $kriter = fn () => PortfoyKarne::firmaKriterKarsilarMi($this->firma->fresh(), 'periyodik_kontrol_raporu');

        $e = IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'kaldirma_iletme', 'ekipman_adi' => 'Forklift', 'muayene_periyodu_ay' => 12, 'sonuc' => 'bekliyor']);
        $this->assertFalse($kriter());

        $e->update(['son_muayene_tarihi' => '2026-05-01', 'sonuc' => 'uygun']);
        $this->assertTrue($kriter());
    }

    public function test_pdf_ve_excel_uretilir(): void
    {
        IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'basincli_kap', 'ekipman_adi' => 'Buhar Kazanı', 'tip' => 'Buhar Kazanı', 'yasal_standart' => 'TS 2025', 'muayene_periyodu_ay' => 12, 'son_muayene_tarihi' => '2026-02-01', 'muayene_yapan' => 'A Tipi Muayene Ltd.', 'sonuc' => 'uygun']);
        $kontrol = PeriyodikKontrol::firmaIcin($this->firma);

        foreach (['pdf' => '%PDF', 'excel' => 'PK'] as $metod => $imza) {
            $yanit = PeriyodikKontrolUretici::$metod($kontrol);
            $this->assertInstanceOf(StreamedResponse::class, $yanit);
            ob_start();
            $yanit->sendContent();
            $this->assertStringStartsWith($imza, ob_get_clean());
        }
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(PeriyodikKontrolSayfasi::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    private function ekipman(array $ek = []): IsEkipmani
    {
        return IsEkipmani::create([
            'firma_id' => $this->firma->id, 'kategori' => 'tesisat_yangin', 'ekipman_adi' => 'Yangın Tüpü 6 kg',
            'muayene_periyodu_ay' => 12, ...$ek,
        ]);
    }

    public function test_kontrol_gir_gecmisi_korur_ve_rapor_dosyasi_ekler(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $e = $this->ekipman(['son_muayene_tarihi' => '2025-03-01', 'muayene_yapan' => 'Eski Firma', 'sonuc' => 'uygun']);
        $this->assertSame(1, $e->kontroller()->count());

        Livewire::test(PeriyodikKontrolSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('kontrolGir', [
                'kontrol_tarihi' => '2026-03-01',
                'kontrol_eden' => 'ABC Muayene',
                'rapor_no' => 'R-77',
                'sonuc' => 'sartli',
                'notu' => 'Manometre değişecek',
                'dosya' => \Illuminate\Http\UploadedFile::fake()->create('rapor.pdf', 40, 'application/pdf'),
            ], ['id' => $e->id])
            ->assertHasNoActionErrors();

        $e->refresh();
        $this->assertSame('2026-03-01', $e->son_muayene_tarihi->toDateString());
        $this->assertSame('2027-03-01', $e->sonraki_vize_tarihi->toDateString());
        $this->assertSame('ABC Muayene', $e->muayene_yapan);

        $gecmis = $e->kontroller()->get();
        $this->assertCount(2, $gecmis);
        $this->assertSame('2026-03-01', $gecmis[0]->kontrol_tarihi->toDateString());
        $this->assertSame('R-77', $gecmis[0]->rapor_no);
        $this->assertSame('Manometre değişecek', $gecmis[0]->notu);
        $this->assertNotNull($gecmis[0]->dosya_yolu);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($gecmis[0]->dosya_yolu);
        $this->assertSame('Eski Firma', $gecmis[1]->kontrol_eden);
    }

    public function test_satir_ici_ayni_tarih_duzeltmesi_gecmisi_cogaltmaz_ve_silme_temizler(): void
    {
        $e = $this->ekipman(['son_muayene_tarihi' => '2026-01-10']);

        $e->update(['rapor_no' => 'R-1', 'sonuc' => 'uygun']);
        $this->assertSame(1, $e->kontroller()->count());
        $this->assertSame('R-1', $e->kontroller()->first()->rapor_no);

        // Değişiklik olmadan kaydetmek yeni geçmiş satırı açmaz
        $e->save();
        $this->assertSame(1, $e->kontroller()->count());

        $e->delete();
        $this->assertDatabaseCount('is_ekipmani_kontrolleri', 0);
    }

    public function test_tum_firmalar_listesi_termine_gore_siralanir_ve_excel_uretir(): void
    {
        $diger = Firma::factory()->for($this->uzman)->create(['unvan' => 'Beta A.Ş.']);
        $this->ekipman(['ekipman_adi' => 'Uzak', 'son_muayene_tarihi' => now()->subMonth()->toDateString()]);
        IsEkipmani::create([
            'firma_id' => $diger->id, 'kategori' => 'kaldirma_iletme', 'ekipman_adi' => 'Forklift',
            'muayene_periyodu_ay' => 12, 'son_muayene_tarihi' => now()->subMonths(13)->toDateString(),
        ]);
        IsEkipmani::create([
            'firma_id' => Firma::factory()->for(User::factory()->kisitli())->create()->id,
            'kategori' => 'kaldirma_iletme', 'ekipman_adi' => 'Başkasının', 'muayene_periyodu_ay' => 12,
        ]);

        $sayfa = Livewire::test(PeriyodikKontrolSayfasi::class);
        $liste = $sayfa->instance()->portfoyEkipmanlari;
        $this->assertSame(['Forklift', 'Uzak'], $liste->pluck('ekipman_adi')->all());

        $sayfa->set('durumFiltre', 'dolmus');
        $this->assertSame(['Forklift'], $sayfa->instance()->portfoyEkipmanlari->pluck('ekipman_adi')->all());

        $sayfa->call('firmaSec', $diger->id)->assertSet('firmaId', $diger->id);

        $tmp = tempnam(sys_get_temp_dir(), 'pkx').'.xlsx';
        ob_start();
        PeriyodikKontrolUretici::portfoyExcel($liste)->sendContent();
        file_put_contents($tmp, ob_get_clean());
        $s = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);
        $this->assertSame('Beta A.Ş.', $s->getCell('A2')->getValue());
        $this->assertSame('Süresi Dolan / Yasak', $s->getCell('K2')->getValue());
    }

    public function test_yangin_alt_kategorileri_katalogda(): void
    {
        $adlar = collect(config('isg.periyodik_kontrol.tipler.tesisat_yangin'))->pluck('ad');

        foreach (['Yangın Dolapları', 'Yangın Algılama ve Alarm Sistemi', 'Otomatik Sprinkler Sistemi'] as $ad) {
            $this->assertContains($ad, $adlar);
        }
    }
}
