<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumEkipleri;
use App\Models\AcilDurumPlani;
use App\Models\AcilEkip;
use App\Models\AcilEkipUyesi;
use App\Models\Firma;
use App\Models\User;
use App\Support\AcilEkipDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AcilDurumEkipleriTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Ahmet İnşaat', 'tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 65]);
    }

    public function test_yasal_minimum_tehlike_sinifina_gore(): void
    {
        $this->assertSame(3, AcilEkip::yasalMinimum('sondurme', 'cok_tehlikeli', 65));   // 30'a 1
        $this->assertSame(2, AcilEkip::yasalMinimum('sondurme', 'az_tehlikeli', 65));    // 50'ye 1
        $this->assertSame(7, AcilEkip::yasalMinimum('ilk_yardim', 'cok_tehlikeli', 65)); // 10'a 1
        $this->assertSame(1, AcilEkip::yasalMinimum('tahliye', 'cok_tehlikeli', 65));    // oran yok
        $this->assertSame(1, AcilEkip::yasalMinimum('sondurme', 'cok_tehlikeli', 0));
    }

    public function test_ekip_durumu_ve_belge_suresi(): void
    {
        AcilEkipDurumu::temelEkipleriOlustur($this->firma);
        $this->assertSame(6, AcilEkip::where('firma_id', $this->firma->id)->count());
        $this->assertSame(0, AcilEkipDurumu::temelEkipleriOlustur($this->firma));

        $s = AcilEkip::where('firma_id', $this->firma->id)->where('tur', 'sondurme')->first();
        $s->update(['min_uye' => 2]);
        $s->uyeler()->create(['ad_soyad' => 'Ali Veli', 'lider' => true, 'belge_tarihi' => now()->subMonths(13)]);   // 12 ay → dolmuş
        $s->uyeler()->create(['ad_soyad' => 'Ayşe Kaya', 'belge_tarihi' => now()->subMonths(12)->addDays(10)]);       // 10 gün kaldı
        $s->uyeler()->create(['ad_soyad' => 'Yedek Kişi', 'uyelik' => 'yedek']);

        $o = AcilEkipDurumu::ozet($this->firma);
        $d = $o['durumlar'][$s->id];

        $this->assertSame(2, $d['asil']);
        $this->assertSame(1, $d['yedek']);
        $this->assertSame('eksik', $d['durum']);   // belge süresi dolmuş
        $this->assertSame(1, $o['belge_dolan']);
        $this->assertSame(1, $o['yaklasan']);
        $this->assertSame(5, $o['kritik']);
        $this->assertSame('Ali Veli', $d['lider']);

        $iy = AcilEkip::where('firma_id', $this->firma->id)->where('tur', 'ilk_yardim')->first();
        $u = $iy->uyeler()->create(['ad_soyad' => 'Hemşire', 'belge_tarihi' => now()->subMonths(30)]);
        $this->assertSame('gecerli', $u->fresh()->belgeDurumu());   // ilkyardımcı 36 ay
    }

    public function test_plan_ekip_listesi_kayitli_ekipten_gelir(): void
    {
        $plan = AcilDurumPlani::create(['firma_id' => $this->firma->id, 'ekipler' => ['sondurme' => ['Eski İsim'], 'koruma' => ['Koruyucu']]]);
        $e = AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => 'sondurme', 'ad' => 'Söndürme Ekibi']);
        $e->uyeler()->create(['ad_soyad' => 'Yeni İsim']);

        $liste = $plan->fresh()->ekipListesi();
        $this->assertSame(['Yeni İsim'], $liste['sondurme']);
        $this->assertSame(['Koruyucu'], $liste['koruma']);   // kayıt yoksa elle yazılan kalır
    }

    public function test_sayfa_ekip_uye_ekler_siler_geri_alir(): void
    {
        $yabanci = AcilEkip::create(['firma_id' => Firma::factory()->create()->id, 'tur' => 'sondurme', 'ad' => 'Yabancı']);

        $lw = Livewire::test(AcilDurumEkipleri::class)
            ->assertOk()
            ->assertSee('henüz acil durum ekibi yok')
            ->call('temelEkipler')
            ->assertSee('Haberleşme Ekibi')
            ->assertSee('Bu ekibe henüz üye atanmamış');

        $ekip = AcilEkip::where('firma_id', $this->firma->id)->where('tur', 'kurtarma')->first();

        $lw->callAction('uyeEkle', ['acil_ekip_id' => $ekip->id, 'ad_soyad' => 'Mehmet Işık', 'uyelik' => 'asil', 'lider' => true, 'vardiya' => 'Gece'], ['ekip' => $ekip->id])
            ->assertHasNoActionErrors()
            ->callAction('uyeEkle', ['acil_ekip_id' => $ekip->id, 'ad_soyad' => 'Can Ak', 'uyelik' => 'yedek', 'lider' => true])
            ->assertSee('Mehmet Işık');

        $this->assertSame(['Can Ak'], AcilEkipUyesi::where('acil_ekip_id', $ekip->id)->where('lider', true)->pluck('ad_soyad')->all());   // tek lider

        $lw->set('vardiyaFiltre', 'Gece')->assertSee('1 sonuç');

        $u = AcilEkipUyesi::where('ad_soyad', 'Mehmet Işık')->first();
        $lw->call('uyeSil', $u->id)->set('silinenlerAcik', true)->assertSee('Geri Al')
            ->call('geriAl', 'uye', $u->id)
            ->call('geriAl', 'ekip', $yabanci->id);   // başkasının kaydı — dokunulmaz
        $this->assertNotNull($u->fresh());
        $this->assertFalse($u->fresh()->trashed());

        $lw->call('ekipSil', $ekip->id);
        $this->assertTrue($ekip->fresh()->trashed());

        $lw->call('excel')->assertFileDownloaded('acil-durum-ekipleri-ahmet-insaat.xlsx');
        $lw->call('pdf')->assertFileDownloaded('acil-durum-ekipleri-ahmet-insaat.pdf');
    }
}
