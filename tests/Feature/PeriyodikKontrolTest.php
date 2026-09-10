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
}
