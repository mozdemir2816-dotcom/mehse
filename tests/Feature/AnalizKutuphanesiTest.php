<?php

namespace Tests\Feature;

use App\Console\Commands\RiskPuanTamamla;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\RiskDegerlendirmesiOlusturucu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** 12.11.2026: analiz maddeleri kütüphanede kalır, mükerrer olmaz, dolan alan diğer analizlere yayılır. */
class AnalizKutuphanesiTest extends TestCase
{
    use RefreshDatabase;

    private User $sahip;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => 'test-anahtar', 'services.gemini.yedek_modeller' => []]);
        $this->sahip = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->sahip);
    }

    private function firma(string $nace = '25.11.01', ?User $sahip = null): Firma
    {
        return Firma::factory()->for($sahip ?? $this->sahip)->create(['nace_kodu' => $nace]);
    }

    private function maddeler(): array
    {
        return [
            ['bolum' => 'Torna', 'faaliyet' => 'Talaşlı imalat', 'tehlike' => 'Döner aksama kapılma', 'risk' => 'Uzuv kaybı',
                'oneri' => 'Koruyucu kapak takılmalı', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 15],
            ['bolum' => 'Kaynak', 'faaliyet' => 'Gazaltı kaynağı', 'tehlike' => 'Kaynak dumanı', 'risk' => 'Meslek hastalığı',
                'olasilik' => 3, 'frekans' => 6, 'siddet' => 7],
        ];
    }

    public function test_yuklenen_analiz_sektor_klasorune_eklenir_firma_silinse_de_kalir(): void
    {
        $firma = $this->firma();
        $rd = RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($firma, 'fine_kinney', $this->maddeler());

        $kategori = TehlikeKategorisi::where('anahtar', 'analiz_metal_fine_kinney')->firstOrFail();
        $this->assertSame(['metal'], $kategori->nace_gruplari);
        $this->assertSame(2, $kategori->tehlikeler()->count());
        $this->assertSame(0, $rd->maddeler()->whereNull('tehlike_id')->count());

        $t = Tehlike::where('tehlike', 'Döner aksama kapılma')->firstOrFail();
        $this->assertSame('Koruyucu kapak takılmalı', $t->oneri);
        $this->assertSame(15.0, (float) $t->siddet);

        // Firma kalıcı silinse de kütüphane maddesi kalır; aynı sektördeki yeni firmanın kütüphanesinde görünür.
        $firma->forceDelete();
        $this->assertTrue(Tehlike::whereKey($t->id)->exists());
        $this->assertTrue(TehlikeKategorisi::query()->uygun('fine_kinney', 'metal')->whereKey($kategori->id)->exists());
    }

    public function test_cop_kutusundaki_firmanin_analizi_de_kutuphaneye_aktarilir(): void
    {
        $firma = $this->firma();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now(), 'belge_no' => 'RD-COP-1']);
        \Illuminate\Support\Facades\DB::table('risk_maddeleri')->insert(['risk_degerlendirmesi_id' => $rd->id, 'sira' => 1,
            'tehlike' => 'Pres kalıbında ezilme', 'durum' => 'acik', 'created_at' => now(), 'updated_at' => now()]);
        $firma->delete();   // Çöp Kutusu

        $this->artisan('risk:kutuphane-esitle')->expectsOutputToContain('1 kütüphaneye eklendi')->assertSuccessful();
        $this->assertTrue(Tehlike::where('tehlike', 'Pres kalıbında ezilme')->exists());
    }

    public function test_ayni_maddeler_ikinci_firmada_mukerrer_olmaz(): void
    {
        RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($this->firma(), 'fine_kinney', $this->maddeler());
        // Büyük/küçük harf ve sondaki nokta farkı mükerrer sayılmaz.
        $ikinci = array_map(fn ($m) => ['tehlike' => mb_strtoupper($m['tehlike']).'.'] + $m, $this->maddeler());
        $rd2 = RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($this->firma(), 'fine_kinney', $ikinci);

        $this->assertSame(2, Tehlike::count());
        $this->assertSame(0, $rd2->maddeler()->whereNull('tehlike_id')->count());
    }

    public function test_baska_hesabin_analizi_kutuphaneye_yazilmaz(): void
    {
        $musteri = User::factory()->create(['rol' => 'uzman']);
        RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($this->firma(sahip: $musteri), 'fine_kinney', $this->maddeler());

        $this->assertSame(0, Tehlike::count());
    }

    public function test_dolan_alan_ayni_maddeyi_kullanan_diger_analize_yayilir_dolu_alana_dokunmaz(): void
    {
        $rd1 = RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($this->firma(), 'fine_kinney', $this->maddeler());
        $rd2 = RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($this->firma(), 'fine_kinney', $this->maddeler());

        $duman1 = $rd1->maddeler()->where('tehlike', 'Kaynak dumanı')->first();
        $duman2 = $rd2->maddeler()->where('tehlike', 'Kaynak dumanı')->first();
        $torna2 = $rd2->maddeler()->where('tehlike', 'Döner aksama kapılma')->first();
        $torna2->update(['oneri' => 'Firmaya özel öneri']);

        $duman1->update(['oneri' => 'Lokal havalandırma kurulmalı', 'mevzuat' => 'Tozla Mücadele Yönetmeliği']);
        $rd1->maddeler()->where('tehlike', 'Döner aksama kapılma')->first()->update(['oneri' => 'Başka öneri']);

        $this->assertSame('Lokal havalandırma kurulmalı', $duman2->fresh()->oneri);
        $this->assertSame('Tozla Mücadele Yönetmeliği', $duman2->fresh()->mevzuat);
        $this->assertSame('Firmaya özel öneri', $torna2->fresh()->oneri);   // dolu alan korunur
        $this->assertSame('Lokal havalandırma kurulmalı', Tehlike::where('tehlike', 'Kaynak dumanı')->value('oneri'));
    }

    public function test_yapay_zekadan_once_kutuphaneden_kotasiz_doldurulur(): void
    {
        Http::fake();
        $rd1 = RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($this->firma(), 'fine_kinney', [
            ['faaliyet' => 'Gazaltı kaynağı', 'tehlike' => 'Kaynak dumanı', 'risk' => 'Meslek hastalığı', 'mevcut_onlem' => 'Maske var',
                'oneri' => 'Lokal havalandırma', 'mevzuat' => 'Tozla Mücadele Yönetmeliği', 'sorumlu' => 'Atölye şefi',
                'olasilik' => 3, 'frekans' => 6, 'siddet' => 7, 'son_olasilik' => 1, 'son_frekans' => 6, 'son_siddet' => 7],
        ]);
        $rd1->update(['belge_no' => 'RD-KTS-1']);
        $rd2 = RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($this->firma(), 'fine_kinney', [
            ['faaliyet' => 'Gazaltı kaynağı', 'tehlike' => 'Kaynak dumanı', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 7],
        ]);

        $this->assertSame(1, RiskPuanTamamla::isle($rd2));
        Http::assertNothingSent();

        $m = $rd2->maddeler()->first();
        $this->assertSame('Lokal havalandırma', $m->oneri);
        $this->assertSame('Atölye şefi', $m->sorumlu);
        $this->assertSame(0, RiskPuanTamamla::eksikSorgu($rd2)->count());
    }

    public function test_formdan_eklenen_tek_madde_de_kutuphaneye_baglanir(): void
    {
        $rd = RiskDegerlendirmesi::create(['firma_id' => $this->firma()->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $m = $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Islak zeminde kayma', 'olasilik' => 3, 'siddet' => 3]);

        $this->assertNotNull($m->fresh()->tehlike_id);
        $this->assertSame(['olasilik' => 3, 'siddet' => 3, 'son_olasilik' => 1, 'son_siddet' => 3], Tehlike::find($m->fresh()->tehlike_id)->matris);
    }
}
