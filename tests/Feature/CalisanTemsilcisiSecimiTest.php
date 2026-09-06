<?php

namespace Tests\Feature;

use App\Filament\Pages\CalisanTemsilcisiSecimi as SecimSayfasi;
use App\Models\CalisanTemsilcisiSecimi as SecimModel;
use App\Models\Firma;
use App\Models\User;
use App\Support\CalisanTemsilcisiSecimiUretici;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;
use ZipArchive;

class CalisanTemsilcisiSecimiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_sayfa_acilir_ve_firma_secilince_zorunlu_temsilci_sayisi_onerilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        \App\Models\Calisan::factory()->count(5)->for($firma)->create();

        $component = Livewire::test(SecimSayfasi::class)
            ->assertOk()
            ->set('firmaId', $firma->id);

        $this->assertSame(5, $component->get('isyeriCalisanSayisi'));
        $this->assertSame(1, $component->get('zorunluTemsilciSayisi'));
    }

    public function test_zorunlu_temsilci_sayisi_kademesi_calisan_sayisina_gore_degisir(): void
    {
        $this->assertSame(1, SecimModel::zorunluTemsilciSayisi(50));
        $this->assertSame(2, SecimModel::zorunluTemsilciSayisi(51));
        $this->assertSame(2, SecimModel::zorunluTemsilciSayisi(100));
        $this->assertSame(3, SecimModel::zorunluTemsilciSayisi(101));
        $this->assertSame(4, SecimModel::zorunluTemsilciSayisi(600));
        $this->assertSame(5, SecimModel::zorunluTemsilciSayisi(1500));
        $this->assertSame(6, SecimModel::zorunluTemsilciSayisi(3000));
    }

    public function test_aday_ekle_ve_sil(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SecimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('adayEkle')
            ->call('adayEkle');

        $this->assertCount(2, $component->get('adaylar'));

        $component->call('adaySil', 0);
        $this->assertCount(1, $component->get('adaylar'));
    }

    public function test_duyuru_aday_listesi_ve_oy_pusulasi_pdf_uretir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SecimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('adayBasvuruSonTarihi', now()->addDays(7)->toDateString())
            ->set('secimTarihi', now()->addDays(14)->toDateString())
            ->set('secimSaati', '10:00')
            ->set('secimYeri', 'Toplantı Salonu')
            ->call('adayEkle')
            ->set('adaylar.0.ad_soyad', 'Ahmet Yılmaz')
            ->set('adaylar.0.unvan', 'İşçi')
            ->callAction('duyuru');

        $kayit = SecimModel::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Toplantı Salonu', $kayit->secim_yeri);
        $this->assertCount(1, $kayit->adaylarListesi());
        $this->assertStringStartsWith('ÇT-'.now()->year.'-', $kayit->dokuman_no);

        $component->callAction('adayListesi');
        $component->callAction('oyPusulasi');
        $component->callAction('basvuru');
    }

    public function test_kazanan_secilmeden_tutanak_aksiyonu_devre_disi(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SecimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('adayEkle')
            ->set('adaylar.0.ad_soyad', 'Ahmet Yılmaz')
            ->assertActionDisabled('tutanak');
    }

    public function test_kazanan_secilince_tutanak_pdf_uretir_ve_kontrol_merkezi_kriteri_karsilanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SecimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('adayEkle')
            ->set('adaylar.0.ad_soyad', 'Ahmet Yılmaz')
            ->call('kazananSec', 0)
            ->assertActionEnabled('tutanak')
            ->callAction('tutanak');

        $kayit = SecimModel::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame(0, $kayit->secilen_aday_index);
        $this->assertSame('Ahmet Yılmaz', $kayit->secilenAday()['ad_soyad']);

        $this->assertTrue(PortfoyKarne::firmaKriterKarsilarMi($firma->fresh(), 'calisan_temsilcisi'));
    }

    public function test_bos_sablon_seti_firma_secilmeden_de_uretilir(): void
    {
        // Firma hiç seçilmeden de erişilebilmeli — elle doldurulacak, sistemden
        // bağımsız bir şablon seti istendiği için.
        Livewire::test(SecimSayfasi::class)
            ->assertActionExists('bosSablon')
            ->callAction('bosSablon');
    }

    public function test_bos_sablon_zip_5_belge_icerir(): void
    {
        $yanit = CalisanTemsilcisiSecimiUretici::bosSablonZip();

        $this->assertInstanceOf(StreamedResponse::class, $yanit);

        ob_start();
        $yanit->sendContent();
        $zipIcerik = ob_get_clean();

        $geciciDosya = tempnam(sys_get_temp_dir(), 'test-ct-bos-zip').'.zip';
        file_put_contents($geciciDosya, $zipIcerik);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($geciciDosya) === true);
        $this->assertSame(5, $zip->numFiles);
        $zip->close();
        unlink($geciciDosya);
    }

    public function test_bos_sablonda_firma_adi_ve_aday_listesi_gercek_veri_icermez(): void
    {
        $firma = Firma::factory()->create(['unvan' => 'Gizli Kalması Gereken Firma A.Ş.']);
        $secim = SecimModel::firmaIcin($firma);
        $secim->forceFill(['adaylar' => [['ad_soyad' => 'Gerçek Aday', 'unvan' => 'İşçi']]])->save();

        // Boş şablon, seçili firma/kayıttan tamamen bağımsız üretilir.
        $html = view('pdf.calisan-temsilcisi-duyuru', [
            'secim' => new SecimModel,
            'firma' => null,
            'bos' => true,
        ])->render();

        $this->assertStringNotContainsString('Gizli Kalması Gereken Firma', $html);

        $adayHtml = view('pdf.calisan-temsilcisi-aday-listesi', [
            'secim' => new SecimModel,
            'firma' => null,
            'bos' => true,
        ])->render();

        $this->assertStringNotContainsString('Gerçek Aday', $adayHtml);
        $this->assertStringNotContainsString('Aday eklenmedi', $adayHtml);
    }

    public function test_baska_uzmanin_firmasi_listede_gorunmez(): void
    {
        Firma::factory()->create();

        $component = Livewire::test(SecimSayfasi::class);

        $this->assertEmpty($component->get('firmalar'));
    }
}
