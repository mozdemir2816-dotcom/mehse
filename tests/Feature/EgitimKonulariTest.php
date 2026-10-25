<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimKatilim as EgitimSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\EgitimKonulari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EgitimKonulariTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_ana_liste_kodlu_ve_farkli_yazimlar_ayni_koda_baglanir(): void
    {
        $this->assertCount(21, EgitimKonulari::tumu());
        $this->assertSame('S5', EgitimKonulari::kodBul('Tütün ürünlerinin zararları ve pasif etkilenim,'));
        $this->assertSame('T12', EgitimKonulari::kodBul('Tahliye ve Kurtarma'));
        $this->assertSame('T9', EgitimKonulari::kodBul('Güvenlik ve sağlık işaretleri'));
        $this->assertSame('T8', EgitimKonulari::kodBul('İş Kazalarının Sebepleri'));
        $this->assertSame(['T3', 'T4'], EgitimKonulari::kodlarBul('Parlama, Patlama, Yangın ve Yangından Korunma'));
        $this->assertNull(EgitimKonulari::kodBul('Forklift operatörlüğü'));

        // Eğitim Katılım içeriği maddeleri kodla taşır.
        $icerik = EgitimIcerikOlusturucu::olustur('genel', null, 'tehlikeli');
        $this->assertSame('G1', $icerik['genel_konular'][0]['kod']);
        $this->assertSame('T12', collect($icerik['teknik_konular'])->last()['kod']);
    }

    public function test_yeni_plan_ana_listeyle_acilir(): void
    {
        $plan = YillikPlan::firmaYilIcin(Firma::factory()->for($this->uzman)->create(), 2026);
        $temel = collect($plan->egitimler)->whereIn('kategori', ['genel', 'saglik', 'teknik']);

        $this->assertSame(array_keys(EgitimKonulari::tumu()), $temel->pluck('kod')->values()->all());
        $this->assertSame('Bağımlılık yapıcı maddelerin zararları ve teknoloji bağımlılığı', $temel->firstWhere('kod', 'S5')['konu']);
        // Şablonun "hedef/eğitici/hafta" bilgisi korunur.
        $this->assertSame('İŞ GÜVENLİĞİ UZMANI', $temel->firstWhere('kod', 'G1')['egitici']);
    }

    public function test_eski_plan_bir_kez_cevrilir_ay_durumlari_korunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $ay = fn (string $d, int $i) => array_replace(array_fill(0, 12, 'bos'), [$i => $d]);

        YillikPlan::create(['firma_id' => $firma->id, 'yil' => 2026, 'baslangic_ayi' => 1, 'faaliyetler' => [['faaliyet' => 'x', 'ana_konu' => 'A', 'aylar' => array_fill(0, 12, 'bos')]], 'egitimler' => [
            ['konu' => 'Çalışma mevzuatı ile ilgili bilgiler', 'kategori' => 'genel', 'hafta' => 2, 'aylar' => $ay('tamamlandi', 2)],
            ['konu' => 'İş Kazalarının Sebepleri', 'kategori' => 'genel', 'hafta' => 2, 'aylar' => $ay('planlandi', 4)],
            ['konu' => 'Parlama, Patlama, Yangın ve Yangından Korunma', 'kategori' => 'teknik', 'hafta' => 2, 'aylar' => $ay('planlandi', 5)],
            ['konu' => 'Yüksekte Çalışma', 'kategori' => 'ise_ozgu', 'hafta' => 1, 'aylar' => $ay('planlandi', 1)],
        ]]);

        $egitimler = collect(YillikPlan::firmaYilIcin($firma, 2026)->egitimler);

        $this->assertSame(21, $egitimler->whereNotNull('kod')->count());
        $this->assertSame('tamamlandi', $egitimler->firstWhere('kod', 'G1')['aylar'][2]);
        // Yanlış bölümdeki eski satır doğru kategoriye geçer.
        $this->assertSame('teknik', $egitimler->firstWhere('kod', 'T8')['kategori']);
        $this->assertSame('planlandi', $egitimler->firstWhere('kod', 'T8')['aylar'][4]);
        // Birleşik satır ikiye bölünür.
        $this->assertSame('planlandi', $egitimler->firstWhere('kod', 'T3')['aylar'][5]);
        $this->assertSame('planlandi', $egitimler->firstWhere('kod', 'T4')['aylar'][5]);
        // Eksik konu eklendi; işe özgü satıra dokunulmadı.
        $this->assertNotNull($egitimler->firstWhere('kod', 'S5'));
        $this->assertSame('Yüksekte Çalışma', $egitimler->firstWhere('kategori', 'ise_ozgu')['konu']);

        // Kullanıcı bir konuyu silerse geri gelmez.
        $plan = YillikPlan::firmaYilIcin($firma, 2026);
        $plan->update(['egitimler' => collect($plan->egitimler)->reject(fn ($e) => ($e['kod'] ?? null) === 'T6')->values()->all()]);
        $this->assertNull(collect(YillikPlan::firmaYilIcin($firma, 2026)->egitimler)->firstWhere('kod', 'T6'));
    }

    public function test_egitim_kaydedilince_plan_hucresi_tamamlandi_olur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->create();
        YillikPlan::firmaYilIcin($firma, 2026);

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('belgeTarihi', '2026-03-10')
            ->set('icerik.teknik_konular.5.dahil', false) // Ekranlı araçlar işlenmedi
            ->callAction('word');

        $egitimler = collect(YillikPlan::firmaYilIcin($firma, 2026)->egitimler);

        $this->assertSame('tamamlandi', $egitimler->firstWhere('kod', 'G1')['aylar'][2]);
        $this->assertSame('tamamlandi', $egitimler->firstWhere('kod', 'T12')['aylar'][2]);
        $this->assertNotSame('tamamlandi', $egitimler->firstWhere('kod', 'T6')['aylar'][2]);
        $this->assertNotSame('tamamlandi', $egitimler->firstWhere('kod', 'G1')['aylar'][3]);
    }

    public function test_plan_yoksa_egitim_kaydi_plan_acmaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->create();

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('belgeTarihi', '2026-03-10')
            ->callAction('word');

        $this->assertDatabaseCount('yillik_planlar', 0);
    }
}
