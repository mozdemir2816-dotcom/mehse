<?php

namespace Tests\Feature;

use App\Filament\Pages\DokumanYonetimi;
use App\Filament\Pages\KkdFormu;
use App\Filament\Pages\KkdSecimMatrisi;
use App\Filament\Pages\KkdTakip;
use App\Filament\Pages\OrtamOlcumleri;
use App\Filament\Pages\PeriyodikKontrol;
use App\Filament\Pages\TabelaTasarimi;
use App\Filament\Pages\TalimatOlustur;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\OrtamOlcumu;
use App\Models\User;
use App\Support\KomutaFirma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Grup7IcerikVeMantikTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Grup7 Fabrika A.Ş.']);
    }

    public function test_ekipman_ve_ortam_olcumu_yenileme_tarihi_degisince_sonraki_tarih_yeniden_hesaplanir(): void
    {
        $ekipman = IsEkipmani::create([
            'firma_id' => $this->firma->id,
            'kategori' => 'basinc',
            'tip' => 'Hava Tankı',
            'ekipman_adi' => 'Ana Kompresör Tankı',
            'muayene_periyodu_ay' => 12,
            'son_muayene_tarihi' => '2025-01-15',
            'sonuc' => 'uygun',
        ]);
        $this->assertSame('2026-01-15', $ekipman->sonraki_vize_tarihi->toDateString());

        // Sonraki yıl yeni kontrol tarihi girilince sonraki vize otomatik güncellenir
        $ekipman->update(['son_muayene_tarihi' => '2026-02-10']);
        $this->assertSame('2027-02-10', $ekipman->fresh()->sonraki_vize_tarihi->toDateString());

        // Ortam ölçümü yenilenince sonraki ölçüm tarihi de otomatik güncellenir
        $olcum = OrtamOlcumu::create([
            'firma_id' => $this->firma->id,
            'olcumler' => [[
                'parametre' => 'Gürültü Ölçümü',
                'periyot_ay' => 12,
                'olcum_tarihi' => '2025-03-01',
                'olculen_deger' => '89',
                'sinir_deger' => '85',
                'birim' => 'dB(A)',
                'yetki_no' => 'İSGÜM-1234',
            ]],
        ]);
        $this->assertSame('2026-03-01', $olcum->olcumler[0]['sonraki_olcum_tarihi']);

        $satirlar = $olcum->olcumler;
        $satirlar[0]['olcum_tarihi'] = '2026-04-01';
        $olcum->update(['olcumler' => $satirlar]);
        $this->assertSame('2027-04-01', $olcum->fresh()->olcumler[0]['sonraki_olcum_tarihi']);

        $html = view('pdf.ortam-olcumleri', [
            'olcum' => $olcum->fresh(),
            'firma' => $this->firma,
            'sonuclar' => config('isg.ortam_olcum.sonuclar'),
        ])->render();
        $this->assertStringContainsString('Yetki: İSGÜM-1234', $html);
    }

    public function test_periyodik_kontrol_ekipnet_kaydi_ve_komuta_firma_paylasimi(): void
    {
        KomutaFirma::sec($this->firma->id);

        foreach ([PeriyodikKontrol::class, OrtamOlcumleri::class, KkdTakip::class, KkdFormu::class, KkdSecimMatrisi::class, TalimatOlustur::class, TabelaTasarimi::class, DokumanYonetimi::class] as $sayfa) {
            Livewire::test($sayfa)->assertSet('firmaId', $this->firma->id);
        }

        $ekipman = IsEkipmani::create([
            'firma_id' => $this->firma->id,
            'kategori' => 'kaldirma',
            'tip' => 'Forklift',
            'ekipman_adi' => 'Linde Forklift',
            'muayene_periyodu_ay' => 12,
            'sonuc' => 'bekliyor',
        ]);

        Livewire::test(PeriyodikKontrol::class)
            ->callAction('kontrolGir', data: [
                'kontrol_tarihi' => now()->toDateString(),
                'kontrol_eden' => 'ABC Muayene',
                'ekipnet_no' => 'K99887766',
                'rapor_no' => 'PK-2026-01',
                'sonuc' => 'uygun',
            ], arguments: ['id' => $ekipman->id]);

        $this->assertStringContainsString('[EKİPNET: K99887766]', (string) $ekipman->fresh()->muayene_yapan);
    }
}
