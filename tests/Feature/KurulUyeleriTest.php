<?php

namespace Tests\Feature;

use App\Filament\Pages\KurulToplantisi as KurulSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\User;
use App\Support\KurulUyeleri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * İSG Kurulu üyelik kuralları (isgsuite.tr referansı) — kalıcı üyeler,
 * Md.6 zorunlu üyeler, taslak engeli, Md.9 periyodu, katılımcı snapshot'ı.
 */
class KurulUyeleriTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    /** Zorunlu 5 rolün hepsini dolduran firma. */
    private function tamKurullu(): Firma
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        foreach (['baskan', 'sekreter', 'hekim', 'ik', 'calisan_temsilcisi'] as $i => $rol) {
            $firma->kurulUyeleri()->create(['rol' => $rol, 'ad_soyad' => "Üye {$i}"]);
        }

        return $firma;
    }

    public function test_firma_kaydindan_isveren_ve_igu_onerilir_ve_uye_yapilir(): void
    {
        $igu = IsgProfesyoneli::factory()->create(['user_id' => $this->uzman->id, 'tip' => 'igu', 'ad_soyad' => 'Mehmet Uzman']);
        $firma = Firma::factory()->for($this->uzman)->create(['isveren_ad' => 'Hasan Patron', 'igu_id' => $igu->id]);

        $oneri = KurulUyeleri::oneriler($firma);
        $this->assertSame('Hasan Patron', $oneri['baskan']['ad_soyad']);
        $this->assertSame('Mehmet Uzman', $oneri['sekreter']['ad_soyad']);
        $this->assertNull($oneri['hekim']); // hekim atanmamış → uyarı

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('oneriyiEkle', 'sekreter');

        $this->assertDatabaseHas('kurul_uyeleri', ['firma_id' => $firma->id, 'rol' => 'sekreter', 'ad_soyad' => 'Mehmet Uzman']);
    }

    public function test_eksik_zorunlu_uye_varken_toplanti_yalniz_taslak_kaydedilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $firma->kurulUyeleri()->create(['rol' => 'baskan', 'ad_soyad' => 'Başkan']);

        $this->assertCount(4, KurulUyeleri::eksikZorunlular($firma));

        // Planla modalı: durum seçeneklerinde yalnız "taslak" var → reddedilir.
        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('toplantiPlanla', ['durum' => 'tamamlandi'])
            ->assertHasActionErrors(['durum']);

        $this->assertSame(0, $firma->kurulToplantilari()->count());

        // Kayıtlı toplantıyı düzenlerken de sunucu tarafında taslağa zorlanır.
        $toplanti = $firma->kurulToplantilari()->create(['tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->set('durum', 'tamamlandi')
            ->call('toplantiBilgileriniKaydet')
            ->assertSet('durum', 'taslak');

        $this->assertSame('taslak', $toplanti->fresh()->durum);
    }

    public function test_zorunlu_uyeler_tamsa_resmi_durum_verilebilir(): void
    {
        $firma = $this->tamKurullu();

        $this->assertSame([], KurulUyeleri::eksikZorunlular($firma));

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('toplantiPlanla', ['durum' => 'planlandi'])
            ->assertHasNoActionErrors();

        $toplanti = $firma->kurulToplantilari()->firstOrFail();
        $this->assertSame('planlandi', $toplanti->durum);
        $this->assertCount(5, $toplanti->katilimcilar);
    }

    public function test_sonraki_toplanti_tehlike_sinifina_gore_onerilir(): void
    {
        $cok = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);
        $az = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli']);

        $this->assertSame('2026-11-15', KurulUyeleri::sonrakiToplanti($cok, '2026-10-15')->toDateString());
        $this->assertSame('2027-01-15', KurulUyeleri::sonrakiToplanti($az, '2026-10-15')->toDateString());
        $this->assertSame('Ayda bir', KurulUyeleri::periyotEtiketi($cok));
    }

    public function test_personel_secilen_rolle_uye_yapilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ayşe Usta', 'gorev' => 'Formen', 'aktif' => true]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('uyeRol', 'formen')
            ->call('calisaniEkle', $calisan->id);

        $this->assertDatabaseHas('kurul_uyeleri', ['firma_id' => $firma->id, 'rol' => 'formen', 'calisan_id' => $calisan->id]);
    }

    public function test_uyeler_yeniden_aktarilinca_katilim_isaretleri_korunur(): void
    {
        $firma = $this->tamKurullu();
        $toplanti = $firma->kurulToplantilari()->create([
            'tarih' => now(), 'gundem' => [], 'kararlar' => [],
            'katilimcilar' => [
                ['ad_soyad' => 'Üye 0', 'gorev' => null, 'rol' => 'baskan', 'katildi' => false],
                ['ad_soyad' => 'Misafir', 'gorev' => 'Danışman', 'rol' => null, 'katildi' => true],
            ],
        ]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->call('uyeleriAktar');

        $katilimcilar = collect($toplanti->fresh()->katilimcilar)->keyBy('ad_soyad');
        $this->assertCount(6, $katilimcilar); // 5 üye + kurul dışı misafir
        $this->assertFalse($katilimcilar['Üye 0']['katildi']);
        $this->assertTrue($katilimcilar['Misafir']['katildi']);
    }

    public function test_sayfa_ve_uye_yonet_modali_acilir(): void
    {
        $firma = $this->tamKurullu();
        $firma->kurulToplantilari()->create(['tarih' => now(), 'katilimcilar' => [], 'gundem' => ['G'], 'kararlar' => [], 'durum' => 'planlandi']);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertOk()
            ->assertSee('Kurul üyeleri (5)')
            ->assertSee('Planlandı')
            ->mountAction('uyeYonet')
            ->assertActionMounted('uyeYonet');
    }

    public function test_pdf_kurul_rolunu_ve_turkce_tarihi_basar(): void
    {
        $firma = $this->tamKurullu();
        $toplanti = $firma->kurulToplantilari()->create([
            'tarih' => '2026-09-16', 'saat' => '23:49', 'bitis_saati' => '02:49', 'tur' => 'olagan',
            'katilimcilar' => KurulUyeleri::katilimciSnapshot($firma), 'gundem' => [], 'kararlar' => [],
        ]);

        $html = view('pdf.kurul-toplantisi', ['toplanti' => $toplanti, 'firma' => $firma])->render();

        $this->assertStringContainsString('İş Güvenliği Uzmanı (Sekreter)', $html);
        $this->assertStringContainsString('16.09.2026', $html);
        $this->assertStringContainsString('23:49 - 02:49', $html);
        $this->assertStringContainsString('Olağan', $html);
    }
}
