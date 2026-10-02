<?php

namespace Tests\Feature;

use App\Filament\Pages\KimyasalRiskDegerlendirmesi;
use App\Filament\Pages\KimyasalSicili;
use App\Filament\Pages\KimyasalYonetimi;
use App\Filament\Pages\PkdSicili;
use App\Models\Firma;
use App\Models\IsgAfis;
use App\Models\PkdKaydi;
use App\Models\User;
use App\Support\KimyasalEnvanterUretici;
use App\Support\PkdUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class KimyasalYonetimiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Kimya A.Ş.']);
    }

    private function pkd(array $ek = []): PkdKaydi
    {
        return PkdKaydi::create(['firma_id' => $this->firma->id, 'dokuman_no' => 'PKD-1', 'bolum' => 'Solvent deposu', ...$ek]);
    }

    public function test_merkez_dort_modulun_ozetini_gosterir_ve_alt_sayfalar_menude_yok(): void
    {
        $this->firma->kimyasalUrunler()->create(['urun_adi' => 'Tiner', 'ghs' => ['ghs02'], 'aktif' => true]);
        $this->firma->kimyasalUrunler()->create(['urun_adi' => 'Asit', 'sds_dosya_yolu' => 'x.pdf', 'aktif' => true]);
        IsgAfis::create(['user_id' => $this->uzman->id, 'baslik' => 'GHS', 'kategori' => 'kimyasal', 'dosya_adi' => 'a.pdf', 'dosya_yolu' => 'afis/a.pdf']);
        $this->pkd(['durum' => 'revizyon']);

        $sayfa = Livewire::test(KimyasalYonetimi::class)->set('firmaId', $this->firma->id);

        $this->assertSame(['toplam' => 2, 'sds_yok' => 1, 'etiketli' => 1, 'gecikmis' => 0], $sayfa->instance()->sds);
        $this->assertSame(1, $sayfa->instance()->afis);
        $this->assertSame(['toplam' => 1, 'dosyasiz' => 1, 'takip' => 1], $sayfa->instance()->pkd);
        $sayfa->assertSee('SDS / GBF Sicili')->assertSee('PKD Sicili')->assertSee('Kimyasal Afişleri')
            ->assertSee(KimyasalSicili::getUrl(['firma' => $this->firma->id]).'#afisler', false);

        $this->assertFalse(KimyasalSicili::shouldRegisterNavigation());
        $this->assertFalse(KimyasalRiskDegerlendirmesi::shouldRegisterNavigation());
        $this->assertFalse(PkdSicili::shouldRegisterNavigation());
        $this->assertTrue(KimyasalYonetimi::shouldRegisterNavigation());
    }

    public function test_pkd_kaydi_olusturulur_duzenlenir_ve_dosya_yuklenir(): void
    {
        Storage::fake('public');

        $sayfa = Livewire::test(PkdSicili::class)
            ->callAction('yeniPkd', [
                'firma_id' => $this->firma->id,
                'dokuman_no' => 'PKD-2026-001',
                'bolum' => 'Toz boya kabini',
                'ortam_turu' => 'toz',
                'durum' => 'aktif',
                'tehlikeli_maddeler' => 'Elektrostatik toz boya',
                'zonelar' => ['zone_22'],
                'tutusturucular' => ['statik'],
                'onlemler' => ['topraklama', 'toz_temizlik'],
                'sonraki_gozden_gecirme' => now()->addYear()->toDateString(),
            ])
            ->assertHasNoActionErrors();

        $p = PkdKaydi::sole();
        $this->assertSame(['zone_22'], $p->zonelar);
        $this->assertSame(['Zone 22'], $p->etiketler('zonelar'));
        $this->assertSame('Yanıcı toz', $p->ortamEtiketi());
        $this->assertFalse($p->takipGerekiyorMu());

        $sayfa->callAction('pkdDuzenle', ['dokuman_no' => 'PKD-2026-001', 'bolum' => 'Toz boya kabini', 'ortam_turu' => 'toz', 'durum' => 'revizyon', 'revizyon_no' => '2.0'], ['id' => $p->id])
            ->assertHasNoActionErrors();
        $this->assertSame('2.0', $p->fresh()->revizyon_no);
        $this->assertTrue($p->fresh()->takipGerekiyorMu());

        $sayfa->callAction('dosyaYukle', ['dosya' => UploadedFile::fake()->create('pkd.pdf', 80, 'application/pdf')], ['id' => $p->id])
            ->assertHasNoActionErrors();
        $p->refresh();
        $this->assertTrue($p->dosyaVarMi());
        Storage::disk('public')->assertExists($p->dosya_yolu);

        $sayfa->call('sil', $p->id);
        Storage::disk('public')->assertMissing($p->dosya_yolu);
        $this->assertSame(0, PkdKaydi::count());
    }

    public function test_pkd_ozet_arama_ve_gecikmis_inceleme(): void
    {
        $this->pkd(['dokuman_no' => 'PKD-A', 'durum' => 'aktif', 'sonraki_gozden_gecirme' => now()->subDay(), 'dosya_yolu' => 'pkd/a.pdf']);
        $this->pkd(['dokuman_no' => 'PKD-B', 'bolum' => 'Dolum hattı', 'durum' => 'taslak']);
        $this->pkd(['dokuman_no' => 'PKD-C', 'durum' => 'arsiv', 'sonraki_gozden_gecirme' => now()->subYear()]);
        PkdKaydi::create(['firma_id' => Firma::factory()->for(User::factory())->create()->id, 'dokuman_no' => 'X', 'bolum' => 'X']);

        $sayfa = Livewire::test(PkdSicili::class);
        $this->assertSame(['toplam' => 3, 'aktif' => 1, 'dosyali' => 1, 'dosyasiz' => 1, 'takip' => 1, 'gecikmis' => 1], $sayfa->instance()->ozet);

        $sayfa->set('arama', 'dolum');
        $this->assertSame(['PKD-B'], $sayfa->instance()->kayitlar->pluck('dokuman_no')->all());
    }

    public function test_pkd_kunye_pdf_ve_excel(): void
    {
        $p = $this->pkd(['zonelar' => ['zone_1', 'zone_2'], 'onlemler' => ['ex_ekipman'], 'notlar' => 'Ex ekipman listesi güncellenecek']);

        ob_start();
        PkdUretici::kunyePdf($p)->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        $tmp = tempnam(sys_get_temp_dir(), 'pkt').'.xlsx';
        ob_start();
        PkdUretici::excel(PkdKaydi::with('firma')->get())->sendContent();
        file_put_contents($tmp, ob_get_clean());
        $s = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);
        $this->assertSame('Zone 1, Zone 2', $s->getCell('F2')->getValue());
        $this->assertSame('Eksik', $s->getCell('M2')->getValue());
    }

    public function test_kimyasal_duzenlenir_aranir_ve_excel_alinir(): void
    {
        $u = $this->firma->kimyasalUrunler()->create(['urun_adi' => 'Aseton', 'cas_no' => '67-64-1', 'aktif' => true]);
        $this->firma->kimyasalUrunler()->create(['urun_adi' => 'Tiner', 'aktif' => true]);

        $sayfa = Livewire::test(KimyasalSicili::class)->set('firmaId', $this->firma->id);

        $sayfa->set('arama', '67-64');
        $this->assertSame(['Aseton'], $sayfa->instance()->gosterilenUrunler->pluck('urun_adi')->all());

        $sayfa->callAction('kimyasalDuzenle', ['urun_adi' => 'Aseton (teknik)', 'ghs' => ['ghs02', 'ghs07']], ['id' => $u->id])
            ->assertHasNoActionErrors();
        $this->assertSame('Aseton (teknik)', $u->fresh()->urun_adi);
        $this->assertSame(['ghs02', 'ghs07'], $u->fresh()->ghs);
        $this->assertSame(1, $sayfa->instance()->ozet['etiketli']);

        $tmp = tempnam(sys_get_temp_dir(), 'sdx').'.xlsx';
        ob_start();
        KimyasalEnvanterUretici::excel($this->firma)->sendContent();
        file_put_contents($tmp, ob_get_clean());
        $s = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);
        $this->assertSame('Aseton (teknik)', $s->getCell('A2')->getValue());
        $this->assertSame('Eksik', $s->getCell('H2')->getValue());
    }
}
