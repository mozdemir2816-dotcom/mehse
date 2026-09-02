<?php

namespace Tests\Feature;

use App\Filament\Pages\CezaTeblig as CezaSayfasi;
use App\Models\Calisan;
use App\Models\CezaTebligTutanagi;
use App\Models\Firma;
use App\Models\User;
use App\Support\CezaTebligTutanagiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class CezaTebligTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_calisanindan_hizli_secim_alanlari_doldurur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz', 'gorev' => 'Formen']);

        $component = Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanHizliSecId', $calisan->id);

        $this->assertSame('Ahmet Yılmaz', $component->get('calisanAdSoyad'));
        $this->assertSame('Formen', $component->get('calisanGorev'));
    }

    public function test_katalogdan_ihlal_toggle_edilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $ilkMadde = config('isg.ceza_teblig.ihlal_kategorileri.Kişisel Koruyucu Donanım.0.madde');
        $ilkDayanak = config('isg.ceza_teblig.ihlal_kategorileri.Kişisel Koruyucu Donanım.0.dayanak');

        $component = Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ihlalToggle', $ilkMadde, $ilkDayanak);

        $this->assertCount(1, $component->get('ihlaller'));

        $component->call('ihlalToggle', $ilkMadde, $ilkDayanak);
        $this->assertCount(0, $component->get('ihlaller'));
    }

    public function test_serbest_ihlal_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('serbestIhlalMetni', 'Vardiya değişiminde devir teslim yapılmadı.')
            ->call('serbestIhlalEkle');

        $this->assertCount(1, $component->get('ihlaller'));
        $this->assertSame('İşyeri İç Yönetmeliği', $component->get('ihlaller')[0]['dayanak']);
    }

    public function test_tanik_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTanikAd', 'Vardiya Amiri')
            ->call('tanikEkle');

        $this->assertCount(1, $component->get('taniklar'));

        $component->call('tanikSil', 0);
        $this->assertCount(0, $component->get('taniklar'));
    }

    public function test_pdf_aksiyonu_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanAdSoyad', 'Ahmet Yılmaz')
            ->set('istihdamSekli', 'alt_isveren')
            ->set('yaptirim', 'yazili_ihtar')
            ->set('imzaDurumu', 'imtina_etti')
            ->callAction('pdf');

        $t = CezaTebligTutanagi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Ahmet Yılmaz', $t->calisan_ad_soyad);
        $this->assertSame('alt_isveren', $t->istihdam_sekli);
        $this->assertSame('yazili_ihtar', $t->yaptirim);
        $this->assertSame('imtina_etti', $t->imza_durumu);
        $this->assertStringStartsWith('CT-'.now()->year.'-', $t->tutanak_no);
    }

    public function test_calisan_adi_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('ceza_teblig_tutanaklari', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = CezaTebligTutanagi::create([
            'firma_id' => $firma->id,
            'calisan_ad_soyad' => 'Test Kişi',
            'istihdam_sekli' => 'kadrolu',
            'yaptirim' => 'sozlu_uyari',
            'ihlaller' => [['madde' => 'KKD kullanmama', 'dayanak' => '6331 s.K. m.19']],
            'taniklar' => [],
        ]);

        $yanit = CezaTebligTutanagiUretici::pdf($t);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_tutanak_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $t = CezaTebligTutanagi::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'Test']);

        Livewire::test(CezaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $t->id);

        $this->assertDatabaseMissing('ceza_teblig_tutanaklari', ['id' => $t->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(CezaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
