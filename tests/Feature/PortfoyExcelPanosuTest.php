<?php

namespace Tests\Feature;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\PortfoyExcelPanosuUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PortfoyExcelPanosuTest extends TestCase
{
    use RefreshDatabase;

    public function test_cok_sayfali_pano_uretilir_ve_veri_icerir(): void
    {
        $uzman = User::factory()->create(['name' => 'Test Uzman']);
        $firma = Firma::factory()->for($uzman)->create(['unvan' => 'Panolu İnşaat A.Ş.', 'calisan_sayisi' => 12]);
        Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Ahmet Yılmaz', 'gorev' => 'Kalıpçı', 'aktif' => true]);
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Yüksekten düşme', 'durum' => 'acik']);
        $firma->kimyasalUrunler()->create(['urun_adi' => 'Tiner', 'cas_no' => '108-88-3', 'ghs' => ['ghs02']]);

        $this->actingAs($uzman);
        $yanit = PortfoyExcelPanosuUretici::indir($uzman);

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'pano').'.xlsx';
        file_put_contents($tmp, $icerik);
        $kitap = IOFactory::createReader('Xlsx')->load($tmp);
        @unlink($tmp);

        $this->assertEqualsCanonicalizing(
            ['Genel Bakış', 'Firmalar', 'Çalışanlar', 'Risk Özeti', 'Kontrol Merkezi', 'Uzaktan Eğitim', 'Kimyasal', 'Periyodik Kontrol'],
            $kitap->getSheetNames(),
        );

        $firmalar = $kitap->getSheetByName('Firmalar')->toArray();
        $this->assertSame('Unvan', $firmalar[0][0]);
        $this->assertSame('Panolu İnşaat A.Ş.', $firmalar[1][0]);

        $risk = $kitap->getSheetByName('Risk Özeti')->toArray();
        $this->assertSame('Panolu İnşaat A.Ş.', $risk[1][0]);
        $this->assertEquals(1, $risk[1][3]); // madde sayısı

        $kimyasal = $kitap->getSheetByName('Kimyasal')->toArray();
        $this->assertSame('Tiner', $kimyasal[1][1]);
    }

    public function test_yalniz_kendi_portfoyu_gelir(): void
    {
        $ben = User::factory()->create();
        $baska = User::factory()->create();
        Firma::factory()->for($ben)->create(['unvan' => 'Benim Firmam']);
        Firma::factory()->for($baska)->create(['unvan' => 'Başkasının Firması']);

        $yanit = PortfoyExcelPanosuUretici::indir($ben);
        ob_start();
        $yanit->sendContent();
        $tmp = tempnam(sys_get_temp_dir(), 'pano').'.xlsx';
        file_put_contents($tmp, ob_get_clean());
        $kitap = IOFactory::createReader('Xlsx')->load($tmp);
        @unlink($tmp);

        $firmalar = collect($kitap->getSheetByName('Firmalar')->toArray())->flatten()->filter()->all();
        $this->assertContains('Benim Firmam', $firmalar);
        $this->assertNotContains('Başkasının Firması', $firmalar);
    }
}
