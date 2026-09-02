<?php

namespace Tests\Feature;

use App\Filament\Pages\IsIzinFormu as IzinSayfasi;
use App\Models\Firma;
use App\Models\IsIzinFormu;
use App\Models\User;
use App\Support\IsIzinFormuUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class IsIzinFormuTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_izin_turu_secilince_ilgili_onlemler_gorunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('izinTuruToggle', 'kapali_alan');

        $onlemler = $component->get('onlemler');
        $this->assertContains('Gaz ölçümü yapıldı ve uygun', $onlemler);
        $this->assertNotContains('Enerji kesildi ve kilitlendi (LOTO)', $onlemler);
        $this->assertContains('Çalışma alanı sınırlandırıldı / Uyarı levhaları asıldı', $onlemler); // genel madde
    }

    public function test_izin_turu_kaldirilinca_uygun_olmayan_secili_onlem_temizlenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('izinTuruToggle', 'elektrik')
            ->call('onlemToggle', 'Topraklama kontrolü yapıldı')
            ->call('izinTuruToggle', 'elektrik'); // tur kaldırıldı

        $this->assertNotContains('Topraklama kontrolü yapıldı', $component->get('secilenOnlemler'));
    }

    public function test_kkd_secimi_toggle_edilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('kkdToggle', 'Baret');

        $this->assertContains('Baret', $component->get('secilenKkdler'));

        $component->call('kkdToggle', 'Baret');
        $this->assertNotContains('Baret', $component->get('secilenKkdler'));
    }

    public function test_pdf_aksiyonu_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calismaAlani', 'Kazan Dairesi')
            ->call('izinTuruToggle', 'sicak_is')
            ->call('onlemToggle', 'Yangın söndürme tüpü hazır')
            ->call('kkdToggle', 'Kaynak Maskesi')
            ->set('onay1Baslik', 'Formen')
            ->set('onay1Ad', 'Ali Veli')
            ->callAction('pdf');

        $form = IsIzinFormu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Kazan Dairesi', $form->calisma_alani);
        $this->assertContains('sicak_is', $form->izin_turleri);
        $this->assertContains('Yangın söndürme tüpü hazır', $form->guvenlik_onlemleri);
        $this->assertContains('Kaynak Maskesi', $form->gerekli_kkdler);
        $this->assertStringStartsWith('PTW-'.now()->year.'-', $form->izin_no);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $form = IsIzinFormu::create([
            'firma_id' => $firma->id,
            'calisma_alani' => 'Test Alan',
            'izin_turleri' => ['yukseklik'],
            'guvenlik_onlemleri' => ['Yaşam hattı / Emniyet kemeri kontrol edildi'],
            'gerekli_kkdler' => ['Emniyet Kemeri'],
        ]);

        $yanit = IsIzinFormuUretici::pdf($form);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_form_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $form = IsIzinFormu::create(['firma_id' => $firma->id]);

        Livewire::test(IzinSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $form->id);

        $this->assertDatabaseMissing('is_izin_formlari', ['id' => $form->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(IzinSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
