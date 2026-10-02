<?php

namespace Tests\Feature;

use App\Filament\Pages\BildirimMerkezi;
use App\Models\AcilDurumPlani;
use App\Models\Bildirim;
use App\Models\Firma;
use App\Models\KimyasalUrun;
use App\Models\SaglikGozetimi;
use App\Models\User;
use App\Support\BildirimTarayici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BildirimMerkeziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Ahmet Yapı', 'katip_no' => null, 'igu_id' => null, 'isyeri_hekimi_id' => null,
            'sozlesme_bitis' => now()->subDays(3)->toDateString(),
        ]);
    }

    private function anahtarlar(): array
    {
        return Bildirim::query()->where('user_id', $this->uzman->id)->acik()->pluck('seviye', 'anahtar')->all();
    }

    public function test_tarama_sure_ve_eksiklik_bildirimleri_uretir(): void
    {
        AcilDurumPlani::create(['firma_id' => $this->firma->id, 'gecerlilik_tarihi' => now()->addDays(10)]);
        SaglikGozetimi::create(['firma_id' => $this->firma->id, 'satirlar' => [
            ['calisan' => 'Gizli Kişi', 'tetkik' => 'Akciğer grafisi', 'sonraki_tarih' => now()->subDays(2)->toDateString()],
        ]]);
        KimyasalUrun::create(['firma_id' => $this->firma->id, 'urun_adi' => 'Tiner', 'aktif' => true, 'sonraki_gozden_gecirme' => now()->addDays(5)]);

        $sonuc = BildirimTarayici::tara($this->uzman);
        $a = $this->anahtarlar();
        $f = 'f'.$this->firma->id.':';

        $this->assertSame('kritik', $a[$f.'sozlesme:gecti']);
        $this->assertSame('uyari', $a[$f.'katip']);
        $this->assertSame('kritik', $a[$f.'igu']);
        $this->assertSame('kritik', $a[$f.'hekim']);
        $this->assertSame('uyari', $a[$f.'adp:yakin']);
        $this->assertSame('kritik', $a[$f.'saglik:gecti']);
        $this->assertSame('uyari', $a[$f.'sds']);
        $this->assertSame($sonuc['acik'], $sonuc['yeni']);

        // Sağlık bildirimi klinik bilgi / ad içermez
        $saglik = Bildirim::where('anahtar', $f.'saglik:gecti')->first();
        $this->assertStringNotContainsString('Gizli Kişi', $saglik->aciklama);
        $this->assertStringNotContainsString('Akciğer', $saglik->aciklama);
    }

    public function test_sorun_giderilince_bildirim_kapanir_tekrar_taramada_cogalmaz(): void
    {
        BildirimTarayici::tara($this->uzman);
        $ilkSayi = Bildirim::count();
        BildirimTarayici::tara($this->uzman);
        $this->assertSame($ilkSayi, Bildirim::count());   // anahtar tekil

        $f = 'f'.$this->firma->id.':';
        Bildirim::where('anahtar', $f.'katip')->update(['okundu_at' => now()]);

        $this->firma->update(['katip_no' => '12345']);
        $sonuc = BildirimTarayici::tara($this->uzman, $this->firma->id);

        $this->assertSame(1, $sonuc['cozulen']);
        $this->assertArrayNotHasKey($f.'katip', $this->anahtarlar());

        // Sorun geri gelirse yeniden açılır ve okunmamış olur
        $this->firma->update(['katip_no' => null]);
        BildirimTarayici::tara($this->uzman);
        $this->assertNull(Bildirim::where('anahtar', $f.'katip')->first()->okundu_at);
    }

    public function test_sayfa_otomatik_tarar_filtreler_ve_okundu_isaretler(): void
    {
        $diger = Firma::factory()->for($this->uzman)->create(['unvan' => 'Beta', 'katip_no' => null]);
        Firma::factory()->for(User::factory())->create(['unvan' => 'Yabancı', 'katip_no' => null]);

        $sayfa = Livewire::test(BildirimMerkezi::class)
            ->assertSee('İş güvenliği uzmanı atanmamış')
            ->assertDontSee('Yabancı');

        $this->assertGreaterThan(0, BildirimMerkezi::getNavigationBadge());

        $sayfa->set('firmaId', $diger->id);
        $this->assertTrue($sayfa->instance()->bildirimler->every(fn ($b) => $b->firma_id === $diger->id));

        $sayfa->set('seviye', 'kritik');
        $this->assertTrue($sayfa->instance()->bildirimler->every(fn ($b) => $b->seviye === 'kritik'));

        $b = $sayfa->instance()->bildirimler->first();
        $sayfa->call('okunduIsaretle', $b->id);
        $this->assertNotNull($b->fresh()->okundu_at);

        $sayfa->set('firmaId', null)->set('seviye', null)->call('tumunuOkundu');
        $this->assertSame(0, Bildirim::okunmamisSayisi($this->uzman->id));

        $sayfa->call('sureleriKontrolEt')->assertNotified();
    }

    public function test_baskasinin_bildirimine_dokunulamaz(): void
    {
        $baska = User::factory()->create();
        $b = Bildirim::create(['user_id' => $baska->id, 'anahtar' => 'x', 'seviye' => 'kritik', 'baslik' => 'X']);

        Livewire::test(BildirimMerkezi::class)->call('okunduIsaretle', $b->id)->call('git', $b->id);

        $this->assertNull($b->fresh()->okundu_at);
    }

    public function test_ust_menude_zil_sayaci(): void
    {
        BildirimTarayici::tara($this->uzman);

        $this->get('/admin')->assertOk()->assertSee(BildirimMerkezi::getUrl(), false)->assertSee('okunmamış');
    }
}
