<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumEkipleri;
use App\Models\AcilEkip;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use App\Support\AcilEkipDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** 02.11.2026: otomatik lider / yedek dağıtımı, kartlarda "Lider Yap" ve destek elemanı tebellüğ belgesi. */
class AcilEkipOtomatikTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Beta Metal', 'sgk_sicil_no' => '123456', 'tehlike_sinifi' => 'az_tehlikeli', 'calisan_sayisi' => 8, 'isveren_ad' => 'Hasan Kaya']);
    }

    private function calisan(string $ad, ?string $tc = null): Calisan
    {
        return Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => $ad, 'tc' => $tc, 'gorev' => 'Operatör', 'aktif' => true]);
    }

    public function test_otomatik_dagit_lider_ve_yedek_atar_tam_ekip_artar(): void
    {
        $s = AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => 'sondurme', 'ad' => 'Söndürme Ekibi']);
        $k = AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => 'kurtarma', 'ad' => 'Kurtarma Ekibi']);
        $ali = $this->calisan('Ali Asıl');
        $s->uyeler()->create(['calisan_id' => $ali->id, 'ad_soyad' => 'Ali Asıl', 'uyelik' => 'asil']);
        $k->uyeler()->create(['ad_soyad' => 'Veli Asıl', 'uyelik' => 'asil']);
        $this->calisan('Veli Asıl');          // adı aynı → ekipte görevli sayılır
        $this->calisan('Cem Boşta');          // tek boştaki çalışan

        $this->assertSame(0, AcilEkipDurumu::ozet($this->firma)['tam']);

        Livewire::test(AcilDurumEkipleri::class)
            ->set('firmaId', $this->firma->id)
            ->assertSee('⚡ Otomatik Dağıt & Lider Ata', false)
            ->call('otomatikDagit')
            ->assertNotified('2 ekibe lider, 1 ekibe yedek üye atandı');

        $o = AcilEkipDurumu::ozet($this->firma);
        $this->assertSame(2, $o['lider']);
        $this->assertSame(2, $o['tam']);                        // asıl ≥ 1 (az tehlikeli, 8 çalışan) + lider
        $this->assertTrue($s->uyeler()->where('ad_soyad', 'Ali Asıl')->value('lider'));
        $this->assertSame('Cem Boşta', $s->uyeler()->where('uyelik', 'yedek')->value('ad_soyad'));
        $this->assertSame(0, $k->uyeler()->where('uyelik', 'yedek')->count());   // boşta çalışan kalmadı

        // Tekrar çalıştırmak mükerrer atamaz.
        $this->assertSame(['lider' => 0, 'yedek' => 0, 'yedeksiz' => 1], AcilEkipDurumu::otomatikTamamla($this->firma));
    }

    public function test_kartta_lider_yap_tek_lider_birakir_ve_rozetler_gorunur(): void
    {
        $s = AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => 'sondurme', 'ad' => 'Söndürme Ekibi']);
        $a = $s->uyeler()->create(['ad_soyad' => 'Ali', 'uyelik' => 'asil', 'lider' => true]);
        $b = $s->uyeler()->create(['ad_soyad' => 'Berk', 'uyelik' => 'yedek']);

        Livewire::test(AcilDurumEkipleri::class)
            ->set('firmaId', $this->firma->id)
            ->assertSee('👑 Lider Yap')
            ->assertSee('Yedek')
            ->call('liderYap', $b->id)
            ->assertNotified('Berk ekip lideri yapıldı');

        $this->assertFalse($a->fresh()->lider);
        $this->assertTrue($b->fresh()->lider);
    }

    public function test_tebellug_belgesi_her_uye_icin_sayfa_uretir(): void
    {
        $s = AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => 'ilk_yardim', 'ad' => 'İlk Yardım Ekibi']);
        $c = $this->calisan('Ayşe Yılmaz', '12345678901');
        $s->uyeler()->create(['calisan_id' => $c->id, 'ad_soyad' => 'Ayşe Yılmaz', 'uyelik' => 'asil', 'lider' => true]);
        $s->uyeler()->create(['ad_soyad' => 'Mert Demir', 'uyelik' => 'yedek']);

        $ekipler = AcilEkipDurumu::ekipler($this->firma);
        $html = view('pdf.acil-ekip-tebellug', [
            'firma' => $this->firma,
            'uyeler' => $ekipler->flatMap(fn ($e) => $e->uyeler->each(fn ($u) => $u->setRelation('ekip', $e)))->each->load('calisan'),
            'isveren' => 'Hasan Kaya', 'uzman' => ['ad' => 'Mehmet Özdemir', 'sinif' => 'A'], 'tarih' => '02.11.2026',
        ])->render();

        $this->assertSame(2, substr_count($html, 'ACİL DURUM DESTEK ELEMANI GÖREVLENDİRME VE TEBELLÜĞ BELGESİ'));
        $this->assertStringContainsString('12345678901', $html);
        $this->assertStringContainsString('İlk Yardım Ekibi Ekip Lideri', $html);
        $this->assertStringContainsString('İlk Yardım Ekibi Yedek Üye', $html);
        $this->assertStringContainsString('İlkyardım Yönetmeliği', $html);
        $this->assertStringContainsString('Ekibi acil durumda toplamak', $html);   // lider maddesi
        $this->assertSame(1, substr_count($html, 'Ekibi acil durumda toplamak'));  // yalnız liderde
        $this->assertStringContainsString('TEBLİĞ EDEN', $html);
        $this->assertStringContainsString('TEBELLÜĞ EDEN', $html);
        $this->assertStringContainsString('123456', $html);

        Livewire::test(AcilDurumEkipleri::class)->set('firmaId', $this->firma->id)
            ->call('tebellugPdf')->assertFileDownloaded('destek-elemani-tebellug-beta-metal.pdf');
        Livewire::test(AcilDurumEkipleri::class)->set('firmaId', $this->firma->id)
            ->call('tebellugPdf', $c->id === 0 ? 0 : $s->uyeler()->where('ad_soyad', 'Mert Demir')->value('id'))
            ->assertFileDownloaded('destek-elemani-tebellug-beta-metal-mert-demir.pdf');
    }
}
