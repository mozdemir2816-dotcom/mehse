<?php

namespace Tests\Feature;

use App\Filament\Pages\DofOlustur as DofSayfasi;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\User;
use App\Support\DofRaporuUretici;
use App\Support\GeminiOneriDanismani;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class DofOlusturTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_secilince_igu_bilgileri_otomatik_dolar(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'İGU Ayşe', 'sertifika_no' => 'A-12345']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id, 'isveren_ad' => 'Ali Patron']);

        $component = Livewire::test(DofSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('İGU Ayşe', $component->get('gozetimYapan'));
        $this->assertSame('A-12345', $component->get('gozetimYapanSertifikaNo'));
        $this->assertSame('Ali Patron', $component->get('isverenVekiliAdi'));
    }

    public function test_madde_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTespit', 'Acil çıkış kapısı önü dolu')
            ->set('yeniOncelik', 'yuksek')
            ->call('maddeEkle');

        $this->assertCount(1, $component->get('maddeler'));
        $this->assertSame('acik', $component->get('maddeler')[0]['durum']);

        $component->call('maddeSil', 0);
        $this->assertCount(0, $component->get('maddeler'));
    }

    public function test_durum_guncellenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTespit', 'Test tespit')
            ->call('maddeEkle')
            ->call('durumGuncelle', 0, 'tamamlandi');

        $this->assertSame('tamamlandi', $component->get('maddeler')[0]['durum']);
    }

    public function test_ai_onerisi_gecerli_yanit_maddeye_yazilir(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Kapı önü derhal boşaltılmalı.']]]]],
            ], 200),
        ]);

        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTespit', 'Acil çıkış kapısı önü dolu')
            ->call('aiOnerisiAl');

        $this->assertSame('Kapı önü derhal boşaltılmalı.', $component->get('yeniOneri'));
    }

    public function test_ai_api_anahtari_yokken_pasif(): void
    {
        config(['services.gemini.key' => null]);

        $this->assertFalse(GeminiOneriDanismani::aktifMi());
    }

    public function test_pdf_aksiyonu_kayit_olusturur_ve_kase_snapshotlanir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'isg-profesyonel-kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTespit', 'Test tespit')
            ->call('maddeEkle')
            ->callAction('pdf');

        $rapor = DofRaporu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('isg-profesyonel-kase/x.png', $rapor->gozetim_yapan_kase);
        $this->assertCount(1, $rapor->maddeler);
        $this->assertStringStartsWith('DOF-'.now()->year.'-', $rapor->belge_no);
    }

    public function test_madde_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('dof_raporlari', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rapor = DofRaporu::create([
            'firma_id' => $firma->id,
            'maddeler' => [['tespit' => 'Test', 'oncelik' => 'orta', 'oneri' => null, 'sorumlu' => null, 'termin' => null, 'durum' => 'acik']],
        ]);

        $yanit = DofRaporuUretici::pdf($rapor);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rapor = DofRaporu::create(['firma_id' => $firma->id, 'maddeler' => []]);

        Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $rapor->id);

        $this->assertDatabaseMissing('dof_raporlari', ['id' => $rapor->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(DofSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
