<?php

namespace Tests\Feature;

use App\Filament\Pages\EkipmanAfisleri;
use App\Filament\Pages\PeriyodikKontrol as PeriyodikKontrolSayfasi;
use App\Models\EkipmanAfisi;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\User;
use App\Support\EkipmanAfisKutuphanesi as Kutuphane;
use App\Support\EkipmanTebligPaketi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/** 21.11.2026: ekipman afiş kütüphanesi — resim/yazı toplu yükleme, eşleme, A4 afiş PDF, tebliğ paketi. */
class EkipmanAfisleriTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    private function resim(string $ad): string
    {
        $im = imagecreatetruecolor(40, 30);
        $yol = sys_get_temp_dir().'/'.$ad;
        imagepng($im, $yol);

        return $yol;
    }

    private function tablo(): string
    {
        return "| Dosya adı | Makine | YAPIN | YAPMAYIN | KKD |\n|---|---|---|---|---|\n"
            ."| forklift | Forklift | Günlük kontrolü yapın • Kemerinizi takın | Yolcu taşımayın • Yük altında durmayın | Baret, İş ayakkabısı, Uçan pelerin |\n"
            ."| bilinmeyen-makine | Uzay Aracı | A • B | C | Baret |\n";
    }

    public function test_kayitlar_configten_acilir_ve_resim_dosya_adiyla_eslesir(): void
    {
        $sonuc = Kutuphane::gorselleriYukle([$this->resim('Forklift (1).png'), $this->resim('olmayan.png')]);

        $this->assertSame(count(config('ekipman_afis.tipler')), EkipmanAfisi::count());
        $this->assertSame(['Forklift'], $sonuc['yuklenen']);
        $this->assertSame(['olmayan.png'], $sonuc['eslesmeyen']);
        Storage::disk('public')->assertExists('ekipman-afis/forklift.png');
    }

    public function test_zip_icindeki_resimler_yuklenir(): void
    {
        $zipYol = sys_get_temp_dir().'/afisler-test.zip';
        $zip = new \ZipArchive;
        $zip->open($zipYol, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($this->resim('dozer.png'), 'Afis_Gorselleri/dozer.png');
        $zip->addFromString('__MACOSX/._dozer.png', 'x');
        $zip->close();

        $sonuc = Kutuphane::gorselleriYukle([$zipYol]);

        $this->assertSame(['Dozer (Buldozer)'], $sonuc['yuklenen']);
        Storage::disk('public')->assertExists('ekipman-afis/dozer.png');
    }

    public function test_gemini_tablosu_yapistirilinca_yazilar_taslak_olarak_gelir(): void
    {
        $sonuc = Kutuphane::metniIceAktar($this->tablo());

        $afis = EkipmanAfisi::where('anahtar', 'forklift')->first();
        $this->assertSame(['Günlük kontrolü yapın', 'Kemerinizi takın'], $afis->yapin);
        $this->assertSame(['Baret', 'İş ayakkabısı'], $afis->kkd);
        $this->assertTrue($afis->onayBekliyorMu());
        $this->assertSame(['bilinmeyen-makine'], $sonuc['eslesmeyen']);
        $this->assertSame(['Uçan pelerin'], $sonuc['atlanan_kkd']);
    }

    public function test_excelin_sonuc_sekmesi_okunur(): void
    {
        $kitap = new Spreadsheet;
        $kitap->getActiveSheet()->setTitle('Promptlar')->fromArray([['No', 'Grup']]);
        $s = $kitap->createSheet()->setTitle('Afiş Yazıları Sonuç');
        $s->fromArray([
            ['Dosya adı', 'Makine', 'YAPIN', 'YAPMAYIN', 'KKD'],
            ['kule-vinc', 'Kule Vinç', '1. Rüzgârı kontrol edin • 2. İşaretçiyle çalışın', 'Yük altında durmayın', 'Baret; Yüksek görünürlüklü yelek'],
        ]);
        $yol = sys_get_temp_dir().'/afis-yazi-test.xlsx';
        (new Xlsx($kitap))->save($yol);

        $sonuc = Kutuphane::yazilariIceAktar($yol);

        $this->assertSame(['Kule Vinç'], $sonuc['guncellenen']);
        $this->assertSame(['Rüzgârı kontrol edin', 'İşaretçiyle çalışın'], EkipmanAfisi::where('anahtar', 'kule-vinc')->value('yapin'));
    }

    public function test_firma_ekipmani_kontrol_formu_adindan_afise_eslesir_ve_pdf_tek_sayfa(): void
    {
        Kutuphane::gorselleriYukle([$this->resim('forklift.png')]);
        Kutuphane::metniIceAktar($this->tablo());
        $e = IsEkipmani::create([
            'firma_id' => $this->firma->id, 'kategori' => 'kaldirma_iletme', 'tip' => 'Forklift / Akülü & Dizel İstifleyici',
            'ekipman_adi' => 'Forklift 1', 'aktif' => true,
        ]);

        $afis = Kutuphane::ekipmanAfisi($e);
        $this->assertSame('forklift', $afis?->anahtar);

        $pdf = Kutuphane::pdfIcerik($afis, $e);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page[^s]/', $pdf));
        $this->assertSame('FORKLİFT 1', Kutuphane::baslik('Forklift 1'));
        $this->assertSame('KULE VİNÇ', Kutuphane::baslik('Kule Vinç (Şantiye) / Tower'));

        $this->assertArrayHasKey('4-guvenlik-afisi-forklift-1.pdf', EkipmanTebligPaketi::belgeler($e));

        Livewire::test(PeriyodikKontrolSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->assertSee('Güvenlik afişi')
            ->call('afisPdf', $e->id)
            ->assertFileDownloaded('afis-forklift-1.pdf');
    }

    public function test_sayfa_acilir_onay_ve_yetki(): void
    {
        Kutuphane::gorselleriYukle([$this->resim('forklift.png')]);
        Kutuphane::metniIceAktar($this->tablo());
        $id = EkipmanAfisi::where('anahtar', 'forklift')->value('id');

        Livewire::test(EkipmanAfisleri::class)
            ->assertSee('Ekipman Afişleri')
            ->assertSee('Uzman onayı bekliyor')
            ->call('onayla', $id);
        $this->assertFalse(EkipmanAfisi::find($id)->onayBekliyorMu());

        Livewire::test(EkipmanAfisleri::class)
            ->set('durum', 'yazi_yok')
            ->assertDontSee('afis-'.$id);

        $calisan = User::factory()->create(['rol' => 'uzman']);
        $this->actingAs($calisan);
        Livewire::test(EkipmanAfisleri::class)->call('onayla', $id)->assertForbidden();
    }
}
