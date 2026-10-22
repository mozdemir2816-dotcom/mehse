<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\User;
use App\Models\ZiyaretProgrami;
use App\Support\ZiyaretOtomatikPlanlayici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ZiyaretOtomatikPlanlayiciTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_fom_makina_her_hafta_persembe_planlanir(): void
    {
        Firma::factory()->for($this->uzman)->create(['unvan' => 'FOM MAKİNA OTOMOTİV SANAYİ VE TİCARET LİMİTED ŞİRKETİ', 'aktif' => true]);

        $baslangic = Carbon::create(2026, 11, 1);
        ZiyaretOtomatikPlanlayici::calistir(2026, $baslangic);

        $firma = Firma::first();
        $kasim = ZiyaretProgrami::where('firma_id', $firma->id)->where('yil', 2026)->first()->ziyaretler[10];

        $this->assertNotEmpty($kasim);
        foreach ($kasim as $girdi) {
            $this->assertSame(4, Carbon::parse($girdi['tarih'])->dayOfWeekIso, 'Perşembe = 4 olmalı');
            $this->assertEquals(4.0, $girdi['sure_saat']);
            $this->assertSame('Öğlene kadar', $girdi['notlar']);
        }
        // Kasım 2026'da 4 Perşembe var.
        $this->assertCount(4, $kasim);
    }

    public function test_orman_muduryogu_iki_haftada_bir_1_ve_3_hafta_sali(): void
    {
        Firma::factory()->for($this->uzman)->create(['unvan' => 'KARACABEY ORMAN İŞLETME MÜDÜRLÜĞÜ', 'aktif' => true]);

        $baslangic = Carbon::create(2026, 11, 1);
        ZiyaretOtomatikPlanlayici::calistir(2026, $baslangic);

        $firma = Firma::first();
        $kasim = ZiyaretProgrami::where('firma_id', $firma->id)->where('yil', 2026)->first()->ziyaretler[10];

        foreach ($kasim as $girdi) {
            $tarih = Carbon::parse($girdi['tarih']);
            $this->assertSame(2, $tarih->dayOfWeekIso, 'Salı = 2 olmalı');
            $this->assertContains((int) ceil($tarih->day / 7), [1, 3]);
        }
        $this->assertCount(2, $kasim);
    }

    public function test_varsayilan_firma_katip_suresine_gore_gunluk_10_saat_dagitir(): void
    {
        // tehlikeli: 20 dk/çalışan × 30 çalışan = 600 dk = 10 saat → tam 1 gün, 10 saat.
        Firma::factory()->for($this->uzman)->create(['unvan' => 'Sade Firma', 'tehlike_sinifi' => 'tehlikeli', 'calisan_sayisi' => 30, 'katip_aylik_dk' => null, 'aktif' => true]);

        $baslangic = Carbon::create(2026, 11, 1);
        $sonuc = ZiyaretOtomatikPlanlayici::calistir(2026, $baslangic);

        $firma = Firma::first();
        $kasim = ZiyaretProgrami::where('firma_id', $firma->id)->where('yil', 2026)->first()->ziyaretler[10];

        $this->assertCount(1, $kasim);
        $this->assertEquals(10.0, $kasim[0]['sure_saat']);
        $this->assertSame(1, $sonuc['firma']);
    }

    public function test_coklu_gun_gereken_firma_gunde_en_fazla_10_saat_olacak_sekilde_bolunur(): void
    {
        // cok_tehlikeli: 40 dk/çalışan × 50 çalışan = 2000 dk = 33.33 saat → ceil(33.33/10)=4 gün.
        Firma::factory()->for($this->uzman)->create(['unvan' => 'Büyük Firma', 'tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 50, 'katip_aylik_dk' => null, 'aktif' => true]);

        ZiyaretOtomatikPlanlayici::calistir(2026, Carbon::create(2026, 11, 1));

        $firma = Firma::first();
        $kasim = ZiyaretProgrami::where('firma_id', $firma->id)->where('yil', 2026)->first()->ziyaretler[10];

        $this->assertCount(4, $kasim);
        foreach ($kasim as $girdi) {
            $this->assertLessThanOrEqual(10.0, $girdi['sure_saat']);
        }
        $this->assertEqualsWithDelta(2000 / 60, array_sum(array_column($kasim, 'sure_saat')), 0.1);
    }

    public function test_zaten_dolu_ay_degistirilmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Dolu Firma', 'tehlike_sinifi' => 'tehlikeli', 'calisan_sayisi' => 30, 'aktif' => true]);

        $program = ZiyaretProgrami::firmaYilIcin($firma, 2026);
        $aylar = $program->ziyaretler;
        $aylar[10] = [['tarih' => '2026-11-05', 'amac' => 'Elle girildi', 'durum' => 'planlandi', 'sure_saat' => 2, 'notlar' => 'Kullanıcı seçti']];
        $program->update(['ziyaretler' => $aylar]);

        ZiyaretOtomatikPlanlayici::calistir(2026, Carbon::create(2026, 11, 1));

        $program->refresh();
        // Kasım (elle girilmiş) dokunulmadan kalır; Aralık hâlâ boştu, o doldurulur.
        $this->assertSame('Elle girildi', $program->ziyaretler[10][0]['amac']);
        $this->assertNotSame('bos', $program->ziyaretler[11][0]['durum']);
    }

    public function test_dk_sifir_olan_firma_atlanir(): void
    {
        Firma::factory()->for($this->uzman)->create(['unvan' => 'Boş Firma', 'tehlike_sinifi' => 'az_tehlikeli', 'calisan_sayisi' => 0, 'katip_aylik_dk' => null, 'aktif' => true]);

        $sonuc = ZiyaretOtomatikPlanlayici::calistir(2026, Carbon::create(2026, 11, 1));

        $this->assertSame(0, $sonuc['firma']);
    }

    public function test_pasif_firma_islenmez(): void
    {
        Firma::factory()->for($this->uzman)->create(['unvan' => 'Pasif Firma', 'tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 50, 'aktif' => false]);

        $sonuc = ZiyaretOtomatikPlanlayici::calistir(2026, Carbon::create(2026, 11, 1));

        $this->assertSame(0, $sonuc['firma']);
    }
}
