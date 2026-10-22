<?php

namespace Tests\Feature;

use App\Filament\Pages\IsgKatipRobot;
use App\Filament\Pages\IsgKatipSozlesmeleri;
use App\Models\IsgKatipSozlesmesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class IsgKatipSozlesmeleriTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function ornekXlsxYukle(): UploadedFile
    {
        $kitap = new Spreadsheet();
        $kitap->getActiveSheet()->fromArray([
            'Sözleşme ID', 'Sözleşme Adı', 'Oto. Güncelleme İzni Var Mı', 'Sözleşme Mevzuata Uygun Mu',
            'Görevlendirilen Kişi TC Kimlik No', 'Görevlendirilen Kişi Ad Soyad', 'Görevlendirilen Kişi Sertifika Tipi', 'Görevlendirilen Kişi Sertifika No',
            'Sözleşme Tipi', 'Çalışma Süresi',
            'Hizmet Veren İşyeri Unvanı', 'Hizmet Veren İşyeri SGK/DETSİS No', 'Hizmet Veren İşyeri İli', 'Hizmet Veren İşyeri Yetki Belgesi No',
            'Hizmet Alan İşyeri Unvanı', 'Hizmet Alan İşyeri SGK/DETSİS No', 'Hizmet Alan İşyeri İli',
            'Hizmet Alan İşyeri Çalışan Sayısı', 'Hizmet Alan İşyeri Tehlike Sınıfı',
            'Sözleşme Başlangıç Tarihi', 'Sözleşme Bitiş Tarihi', 'Sözleşme Statüsü', 'Sözleşme Onay Durumu', 'Sözleşme Onay Tarihi',
        ], null, 'A1');
        $kitap->getActiveSheet()->fromArray([
            '500', 'Sözleşme', 'Evet', 'Uygun',
            '77777777777', 'ZEYNEP KAYA', 'A Sınıfı İş Güvenliği Uzmanlığı Sertifikası', 'İGU-222222',
            'Kısmi Süreli', 'Kısmi Süreli',
            'OSGB Y', '66666010112247420160872000', 'ANKARA', 'OSGB-3',
            'FİRMA B', '77777010112247420160872000', 'ANKARA',
            20, 'Az Tehlikeli',
            '01.01.2026', '01.01.2027', 'Sözleşme Devam Ediyor', 'Sözleşme Onaylandı', '02.01.2026',
        ], null, 'A2');

        $yol = tempnam(sys_get_temp_dir(), 'katip').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return UploadedFile::fake()->createWithContent('sozlesmeler.xlsx', file_get_contents($yol));
    }

    public function test_bos_sayfa_acilir(): void
    {
        Livewire::test(IsgKatipSozlesmeleri::class)->assertOk();
    }

    public function test_excel_yuklenince_iceaktarilir_ve_kisiler_listesinde_gorunur(): void
    {
        Livewire::test(IsgKatipSozlesmeleri::class)
            ->mountAction('yukle')
            ->setActionData(['dosya' => $this->ornekXlsxYukle()])
            ->callMountedAction()
            ->assertNotified();

        $this->assertSame(1, IsgKatipSozlesmesi::where('user_id', $this->uzman->id)->count());
    }

    public function test_kisi_secilince_sozlesmeleri_gorunur(): void
    {
        Livewire::test(IsgKatipSozlesmeleri::class)
            ->mountAction('yukle')
            ->setActionData(['dosya' => $this->ornekXlsxYukle()])
            ->callMountedAction();

        $tc = '77777777777';

        Livewire::test(IsgKatipSozlesmeleri::class)
            ->call('kisiSec', $tc)
            ->assertSet('seciliKisi', $tc);
    }

    public function test_hazir_bot_karti_sozlesmeler_sayfasina_yonlendirir(): void
    {
        Livewire::test(IsgKatipRobot::class)
            ->call('botKur', 'Toplu Atama Sözleşmesi İndir')
            ->assertRedirect(IsgKatipSozlesmeleri::getUrl());
    }
}
