<?php

namespace Tests\Feature;

use App\Filament\Resources\RiskDegerlendirmesis\Pages\CreateRiskDegerlendirmesi;
use App\Filament\Resources\RiskDegerlendirmesis\Pages\EditRiskDegerlendirmesi;
use App\Filament\Resources\RiskDegerlendirmesis\Pages\ListRiskDegerlendirmesis;
use App\Filament\Resources\Tehlikes\Pages\ListTehlikes;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\User;
use App\Support\RiskDegerlendirmesiUretici;
use App\Support\RiskSkorlama;
use Database\Seeders\TehlikeKutuphanesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class RiskDegerlendirmeTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_5x5_ve_fine_kinney_puan_duzeyi(): void
    {
        $m5 = RiskSkorlama::hesapla('matris_5x5', 4, 4);
        $this->assertSame(16, $m5['puan']);
        $this->assertSame('Yüksek Risk', $m5['duzey']);

        $fk = RiskSkorlama::hesapla('fine_kinney', 6, 6, 15);
        $this->assertSame(540.0, $fk['puan']);
        $this->assertSame('Çok Yüksek Risk', $fk['duzey']);

        $bos = RiskSkorlama::hesapla('matris_5x5', null, 3);
        $this->assertSame(0, $bos['puan']);
    }

    public function test_belge_no_ve_gecerlilik_otomatik(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);

        $rd = RiskDegerlendirmesi::create([
            'firma_id' => $firma->id,
            'yontem' => 'matris_5x5',
            'rapor_tarihi' => '2026-01-01',
        ]);

        $this->assertStringStartsWith('RD-'.now()->year.'-', $rd->belge_no);
        $this->assertSame('2028-01-01', $rd->gecerlilik_tarihi->toDateString()); // +2 yıl (çok tehlikeli)
        $this->assertSame($firma->unvan, $rd->firma_unvan);
    }

    public function test_risk_maddesi_saving_puan_hesaplar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $m = $rd->maddeler()->create([
            'tehlike' => 'Korkuluksuz kenar', 'olasilik' => 3, 'siddet' => 5,
            'son_olasilik' => 1, 'son_siddet' => 5,
        ]);

        $this->assertSame(15.0, $m->puan);
        $this->assertSame('Yüksek Risk', $m->duzey);
        $this->assertSame(5.0, $m->son_puan);
        $this->assertSame('Katlanılabilir Risk', $m->son_duzey);
    }

    public function test_sayfalar_acilir_ve_kutuphane_seed(): void
    {
        $this->seed(TehlikeKutuphanesiSeeder::class);
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        Livewire::test(ListRiskDegerlendirmesis::class)->assertOk();
        Livewire::test(CreateRiskDegerlendirmesi::class)->assertOk();
        Livewire::test(EditRiskDegerlendirmesi::class, ['record' => $rd->getRouteKey()])->assertOk();
        Livewire::test(ListTehlikes::class)->assertOk()->assertCountTableRecords(13);
    }

    public function test_pdf_matris_5x5_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        $rd = RiskDegerlendirmesi::create([
            'firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now(),
            'ekip' => [['ad' => 'Ali Veli', 'unvan' => 'İşveren']],
        ]);
        $rd->maddeler()->create(['bolum' => 'Şantiye', 'tehlike' => 'Korkuluksuz kenar', 'olasilik' => 3, 'siddet' => 5]);

        $yanit = RiskDegerlendirmesiUretici::pdf($rd);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_pdf_fine_kinney_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['tehlike' => 'Gürültü', 'olasilik' => 6, 'frekans' => 6, 'siddet' => 15]);

        $yanit = RiskDegerlendirmesiUretici::pdf($rd);

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_pdf_kapak_prosedur_form_sirasiyla_basilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['sgk_sicil_no' => '1234567', 'nace_kodu' => '41.00']);
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['bolum' => 'Şantiye', 'tehlike' => 'Test tehlikesi', 'olasilik' => 3, 'siddet' => 5]);
        $rd->refresh();

        $prosedur = \App\Models\RiskProsedur::aktifIcin($this->uzman->id, 'matris_5x5');

        $html = view('pdf.risk-degerlendirmesi', [
            'rd' => $rd, 'firma' => $firma, 'uzman' => $this->uzman,
            'hekim' => null, 'temsilci' => null, 'destekElemani' => null,
            'metodoloji' => config('isg.risk_matris_5x5'), 'prosedur' => $prosedur,
        ])->render();

        $kapakPos = strpos($html, 'RİSK DEĞERLENDİRMESİ');
        $prosedurPos = strpos($html, $prosedur->ad);
        $formPos = strpos($html, 'İŞYERİ KÜNYESİ');

        $this->assertNotFalse($kapakPos);
        $this->assertNotFalse($prosedurPos);
        $this->assertNotFalse($formPos);
        $this->assertTrue($kapakPos < $prosedurPos && $prosedurPos < $formPos, 'Sıra: Kapak → Prosedür → Form olmalı');
        $this->assertStringContainsString('AMAÇ', $html);
        $this->assertStringContainsString('1234567', $html); // kapakta SGK sicil no
        $this->assertStringContainsString('41.00', $html); // kapakta NACE kodu

        // Öneri/Sorumlu/Termin ayrı sütunlar olmalı.
        $this->assertStringContainsString('<th>Öneri</th>', $html);
        $this->assertStringContainsString('<th>Sorumlu</th>', $html);
        $this->assertStringContainsString('<th>Termin</th>', $html);
        $this->assertStringNotContainsString('Öneri / Sorumlu / Termin', $html);

        // Her sayfada tekrarlanan başlık (<thead>) ve alt bilgi imza şeridi.
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('İŞ GÜVENLİĞİ UZMANI', $html);
        $this->assertStringContainsString('İŞYERİ HEKİMİ', $html);
        $this->assertStringContainsString('ÇALIŞAN TEMSİLCİSİ', $html);
        $this->assertStringContainsString('DESTEK ELEMANI', $html);

        // Risk tablosunda sıra numarası sütunu.
        $this->assertStringContainsString('<th class="col-no">No</th>', $html);
        $this->assertStringContainsString('<td class="col-no">1</td>', $html);

        // Termin'in yanına önlem sonrası Olasılık/Şiddet sütunları eklendi
        // (mevcut O/Ş sütunlarıyla birlikte toplam 2'şer tane olmalı).
        $this->assertSame(2, substr_count($html, '<th>O</th>'));
        $this->assertSame(2, substr_count($html, '<th>Ş</th>'));
    }

    public function test_pdf_kapakta_toplam_sayfa_sayisi_hazirlayanin_altinda_yazilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['tehlike' => 'Test', 'olasilik' => 2, 'siddet' => 2]);

        $html = view('pdf.risk-degerlendirmesi', [
            'rd' => $rd, 'firma' => $firma, 'uzman' => $this->uzman,
            'hekim' => null, 'temsilci' => null, 'destekElemani' => null,
            'metodoloji' => config('isg.risk_matris_5x5'), 'prosedur' => null,
            'toplamSayfa' => 7,
        ])->render();

        $hazirlayanPos = strpos($html, 'Hazırlayan:');
        $toplamSayfaPos = strpos($html, 'Toplam Sayfa: 7');

        $this->assertNotFalse($hazirlayanPos);
        $this->assertNotFalse($toplamSayfaPos);
        $this->assertTrue($hazirlayanPos < $toplamSayfaPos, 'Toplam Sayfa, Hazırlayan\'ın altında olmalı');
    }

    public function test_risk_maddesi_duzey_dikey_harf_harf_alt_alta_yazar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $m = $rd->maddeler()->create(['tehlike' => 'Test', 'olasilik' => 6, 'frekans' => 6, 'siddet' => 15]);

        $this->assertSame(implode("\n", mb_str_split('Çok Yüksek Risk')), $m->duzeyDikey());
        // Önlem sonrası olasılık/şiddet girilmemişse varsayılan uygulanır:
        // olasılık 1'e düşer, şiddet+frekans öneri öncesiyle aynı kalır.
        $this->assertSame(1.0, $m->son_olasilik);
        $this->assertSame(15.0, $m->son_siddet);
        $this->assertSame(6.0, $m->son_frekans);
        $this->assertSame(90.0, $m->son_puan);
        $this->assertSame(implode("\n", mb_str_split('Önemli Risk')), $m->sonDuzeyDikey());
    }

    public function test_onlem_sonrasi_olasilik_siddet_varsayilani_kendisi_girilince_ezilmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $m = $rd->maddeler()->create([
            'tehlike' => 'Test', 'olasilik' => 4, 'siddet' => 4,
            'son_olasilik' => 2, 'son_siddet' => 3,
        ]);

        $this->assertSame(2.0, $m->son_olasilik);
        $this->assertSame(3.0, $m->son_siddet);
        $this->assertSame(6.0, $m->son_puan);
    }

    public function test_edit_sayfasinda_pdf_aksiyonu_calisir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['tehlike' => 'Test tehlike', 'olasilik' => 2, 'siddet' => 2]);

        Livewire::test(EditRiskDegerlendirmesi::class, ['record' => $rd->getRouteKey()])
            ->assertOk()
            ->callAction('pdf');
    }

    public function test_uzman_baskasinin_risk_degerlendirmesini_gormez(): void
    {
        $benim = RiskDegerlendirmesi::create([
            'firma_id' => Firma::factory()->for($this->uzman)->create()->id,
            'yontem' => 'matris_5x5', 'rapor_tarihi' => now(),
        ]);
        RiskDegerlendirmesi::create([
            'firma_id' => Firma::factory()->create()->id,
            'yontem' => 'matris_5x5', 'rapor_tarihi' => now(),
        ]);

        Livewire::test(ListRiskDegerlendirmesis::class)
            ->assertCanSeeTableRecords([$benim])
            ->assertCountTableRecords(1);
    }

    public function test_kayitli_degerlendirmeler_firmaya_gore_klasorlenir(): void
    {
        RiskDegerlendirmesi::create([
            'firma_id' => Firma::factory()->for($this->uzman)->create(['unvan' => 'A Firması'])->id,
            'yontem' => 'matris_5x5', 'rapor_tarihi' => now(),
        ]);
        RiskDegerlendirmesi::create([
            'firma_id' => Firma::factory()->for($this->uzman)->create(['unvan' => 'B Firması'])->id,
            'yontem' => 'matris_5x5', 'rapor_tarihi' => now(),
        ]);

        $component = Livewire::test(ListRiskDegerlendirmesis::class)->assertCountTableRecords(2);

        $grup = $component->instance()->getTable()->getDefaultGroup();

        $this->assertNotNull($grup);
        $this->assertSame('firma.unvan', $grup->getColumn());
    }
}
