<?php

namespace Tests\Feature;

use App\Filament\Pages\SertifikaOlustur as SertifikaSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\Sertifika;
use App\Models\User;
use App\Support\SertifikaUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class SertifikaOlusturTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_secilince_calisanlar_ve_egitici_otomatik_dolar(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'igu', 'ad_soyad' => 'İGU Ayşe']);
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'isyeri_hekimi', 'ad_soyad' => 'Dr. Mehmet']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id, 'isyeri_hekimi_id' => $hekim->id]);
        $c = Calisan::factory()->for($firma)->create();

        $component = Livewire::test(SertifikaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('İGU Ayşe', $component->get('egiticiIguAdi'));
        $this->assertSame('Dr. Mehmet', $component->get('egiticiHekimAdi'));
        $this->assertSame([$c->id], $component->get('secilenCalisanIdler'));
    }

    public function test_tekli_tip_sabit_icerik_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('tip', 'yukseklik');

        $icerik = $component->get('icerik');
        $this->assertSame(config('isg.egitim.ozel_basliklar.yuksekte_calisma.maddeler'), $icerik['maddeler']);
    }

    public function test_isg_tipi_sektor_secilince_icerik_isyerine_ozgu_gelir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);

        $component = Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('sektorAnahtari', 'insaat');

        $icerik = $component->get('icerik');
        $this->assertSame('İnşaat', $icerik['isyerine_ozgu']['sektor']);
    }

    public function test_gecerlilik_otomatik_hesaplanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('egitimTarihleri.0', '2026-01-10')
            ->call('gecerlilikOtomatik', 'tehlikeli');

        $this->assertSame('2028-01-10', $component->get('gecerlilikTarihi'));
    }

    public function test_gun_sayisi_degisince_tarih_dizisi_boyutlanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('gunSayisi', 3);

        $this->assertCount(3, $component->get('egitimTarihleri'));
    }

    public function test_pdf_aksiyonu_kayit_olusturur_ve_kase_snapshotlanir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'igu', 'kase_gorseli' => 'isg-profesyonel-kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Test Çalışan']);

        Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('tip', 'isg')
            ->callAction('pdf');

        $s = Sertifika::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('isg', $s->tip);
        $this->assertSame('isg-profesyonel-kase/x.png', $s->egitici_igu_kase);
        $this->assertCount(1, $s->katilimcilar);
        $this->assertStringStartsWith('SRT-'.now()->year.'-', $s->belge_no);
    }

    public function test_katilimci_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('tumCalisanlar', false)
            ->callAction('pdf');

        $this->assertDatabaseCount('sertifikalar', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $s = Sertifika::create([
            'firma_id' => $firma->id,
            'tip' => 'isg',
            'katilimcilar' => [['ad_soyad' => 'Test Kişi', 'tc' => null, 'gorev' => null]],
            'konu_icerigi' => ['tip' => 'genel', 'genel_konular' => [], 'saglik_konulari' => [], 'teknik_konular' => []],
        ]);

        $yanit = SertifikaUretici::pdf($s);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $s = Sertifika::create(['firma_id' => $firma->id, 'tip' => 'isg', 'katilimcilar' => []]);

        Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $s->id);

        $this->assertDatabaseMissing('sertifikalar', ['id' => $s->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(SertifikaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
