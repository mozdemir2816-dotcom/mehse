<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\IsgKatipSozlesmesi;
use App\Models\User;
use App\Support\IsgKatipSozlesmeIceAktarici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class IsgKatipSozlesmeIceAktariciTest extends TestCase
{
    use RefreshDatabase;

    private const BASLIKLAR = [
        'Sözleşme ID', 'Sözleşme Adı', 'Oto. Güncelleme İzni Var Mı', 'Sözleşme Mevzuata Uygun Mu',
        'Görevlendirilen Kişi TC Kimlik No', 'Görevlendirilen Kişi Ad Soyad', 'Görevlendirilen Kişi Sertifika Tipi',
        'Görevlendirilen Kişi Sertifika No', 'Sözleşme Tipi', 'Çalışma Süresi',
        'Hizmet Veren İşyeri Unvanı', 'Hizmet Veren İşyeri SGK/DETSİS No', 'Hizmet Veren İşyeri İli', 'Hizmet Veren İşyeri Yetki Belgesi No',
        'Hizmet Alan İşyeri Unvanı', 'Hizmet Alan İşyeri SGK/DETSİS No', 'Hizmet Alan İşyeri İli',
        'Hizmet Alan İşyeri Çalışan Sayısı', 'Hizmet Alan İşyeri Tehlike Sınıfı',
        'Sözleşme Başlangıç Tarihi', 'Sözleşme Bitiş Tarihi', 'Sözleşme Statüsü', 'Sözleşme Onay Durumu', 'Sözleşme Onay Tarihi',
        'Gezici Araç Plaka No',
    ];

    private User $uzman;

    /** @param  array<int, array<int, mixed>>  $satirlar */
    private function xlsxOlustur(array $satirlar): string
    {
        $kitap = new Spreadsheet();
        $sayfa = $kitap->getActiveSheet();
        $sayfa->fromArray(self::BASLIKLAR, null, 'A1');

        foreach ($satirlar as $i => $satir) {
            $sayfa->fromArray($satir, null, 'A'.($i + 2));
        }

        $yol = tempnam(sys_get_temp_dir(), 'katip').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
    }

    public function test_sozlesmeler_iceaktarilir_ve_kisiye_gore_gruplanabilir(): void
    {
        $yol = $this->xlsxOlustur([
            [
                '103', 'OSGB İLE ÖZEL İŞYERİ ARASINDA İŞ GÜVENLİĞİ UZMANI HİZMET ALIMI SÖZLEŞMESİ', 'Evet', 'Uygun',
                '11111111111', 'MEHMET ÖZDEMİR', 'A Sınıfı İş Güvenliği Uzmanlığı Sertifikası', 'İGU-405044',
                'Kısmi Süreli', 'Kısmi Süreli',
                'YILDIZ GRUP OSGB', '28621010112382420161389000', 'BURSA', 'OSGB-4078',
                'BERT PLASTİK A.Ş.', '22212010112247420160872000', 'BURSA',
                45, 'Tehlikeli',
                '01.01.2026', now()->addDays(10)->format('d.m.Y'), 'Sözleşme Devam Ediyor', 'Sözleşme Onaylandı', '02.01.2026',
                null,
            ],
            [
                '104', 'OSGB İLE ÖZEL İŞYERİ ARASINDA İŞ GÜVENLİĞİ UZMANI HİZMET ALIMI SÖZLEŞMESİ', 'Evet', 'Uygun',
                '11111111111', 'MEHMET ÖZDEMİR', 'A Sınıfı İş Güvenliği Uzmanlığı Sertifikası', 'İGU-405044',
                'Tam Süreli', 'Tam Süreli',
                'YILDIZ GRUP OSGB', '28621010112382420161389000', 'BURSA', 'OSGB-4078',
                'ACME SANAYİ LTD.', '99912010112200000160000000', 'YALOVA',
                80, 'Çok Tehlikeli',
                '01.03.2025', '28.02.2026', 'Sözleşme Devam Ediyor', 'Sözleşme Onayı Bekliyor', null,
                null,
            ],
        ]);

        $sonuc = IsgKatipSozlesmeIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(2, $sonuc['basarili']);
        $this->assertSame([], $sonuc['hatalar']);
        $this->assertSame(2, IsgKatipSozlesmesi::where('user_id', $this->uzman->id)->count());

        $yaklasan = IsgKatipSozlesmesi::where('katip_sozlesme_id', '103')->first();
        $this->assertTrue($yaklasan->aktifMi());
        $this->assertTrue($yaklasan->suresiYaklasiyorMu(30));
        $this->assertFalse($yaklasan->suresiGecmisMi());

        $onayBekleyen = IsgKatipSozlesmesi::where('katip_sozlesme_id', '104')->first();
        $this->assertTrue($onayBekleyen->onayBekliyorMu());
        $this->assertTrue($onayBekleyen->suresiGecmisMi());

        unlink($yol);
    }

    public function test_hizmet_alan_sgk_no_eslesirse_firmaya_baglanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['sgk_sicil_no' => '22212010112247420160872000']);

        $yol = $this->xlsxOlustur([[
            '200', 'Sözleşme', 'Hayır', 'Uygun',
            '22222222222', 'AYŞE YILMAZ', 'B Sınıfı İş Güvenliği Uzmanlığı Sertifikası', 'İGU-999999',
            'Tam Süreli', 'Tam Süreli',
            'VERENOSGB', '11111010112247420160872000', 'BURSA', 'OSGB-1',
            'BERT PLASTİK A.Ş.', '22212010112247420160872000', 'BURSA',
            45, 'Tehlikeli',
            '01.01.2026', '01.01.2027', 'Sözleşme Devam Ediyor', 'Sözleşme Onaylandı', '02.01.2026',
            null,
        ]]);

        IsgKatipSozlesmeIceAktarici::iceAktar($yol, $this->uzman->id);

        $kayit = IsgKatipSozlesmesi::where('katip_sozlesme_id', '200')->first();
        $this->assertSame($firma->id, $kayit->firma_id);

        unlink($yol);
    }

    public function test_ayni_sozlesme_tekrar_yuklenince_coğalmaz_ve_guncellenir(): void
    {
        $yolV1 = $this->xlsxOlustur([[
            '300', 'Sözleşme', 'Hayır', 'Uygun Değil',
            '33333333333', 'CAN DEMİR', 'A Sınıfı İş Güvenliği Uzmanlığı Sertifikası', 'İGU-111111',
            'Kısmi Süreli', 'Kısmi Süreli',
            'OSGB X', '44444010112247420160872000', 'İSTANBUL', 'OSGB-2',
            'FİRMA A', '55555010112247420160872000', 'İSTANBUL',
            10, 'Az Tehlikeli',
            '01.01.2026', '01.06.2026', 'Sözleşme Devam Ediyor', 'Sözleşme Onayı Bekliyor', null,
            null,
        ]]);
        IsgKatipSozlesmeIceAktarici::iceAktar($yolV1, $this->uzman->id);

        $yolV2 = $this->xlsxOlustur([[
            '300', 'Sözleşme', 'Hayır', 'Uygun',
            '33333333333', 'CAN DEMİR', 'A Sınıfı İş Güvenliği Uzmanlığı Sertifikası', 'İGU-111111',
            'Kısmi Süreli', 'Kısmi Süreli',
            'OSGB X', '44444010112247420160872000', 'İSTANBUL', 'OSGB-2',
            'FİRMA A', '55555010112247420160872000', 'İSTANBUL',
            10, 'Az Tehlikeli',
            '01.01.2026', '01.06.2026', 'Sözleşme Devam Ediyor', 'Sözleşme Onaylandı', '10.01.2026',
            null,
        ]]);
        IsgKatipSozlesmeIceAktarici::iceAktar($yolV2, $this->uzman->id);

        $this->assertSame(1, IsgKatipSozlesmesi::where('user_id', $this->uzman->id)->count());
        $this->assertTrue(IsgKatipSozlesmesi::where('katip_sozlesme_id', '300')->first()->mevzuata_uygun_mu);

        unlink($yolV1);
        unlink($yolV2);
    }

    public function test_sozlesme_id_sutunu_eksikse_hata_doner(): void
    {
        $kitap = new Spreadsheet();
        $kitap->getActiveSheet()->fromArray(['Alakasız Sütun'], null, 'A1');
        $kitap->getActiveSheet()->fromArray(['bir şey'], null, 'A2');
        $yol = tempnam(sys_get_temp_dir(), 'katip').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        $sonuc = IsgKatipSozlesmeIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(0, $sonuc['basarili']);
        $this->assertNotEmpty($sonuc['hatalar']);

        unlink($yol);
    }
}
