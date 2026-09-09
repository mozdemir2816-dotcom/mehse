<?php

namespace Tests\Feature;

use App\Filament\Pages\YillikPlanlar as PlanSayfasi;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\YillikPlanExcelIceAktarici;
use App\Support\YillikPlanUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class YillikPlanlarTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    /** varsayilan_aylar => [] olan bir faaliyetin indeksi (ayları hep boş başlar). */
    private function bosBaslayanFaaliyetIndeksi(): int
    {
        foreach (config('isg.yillik_plan.varsayilan_faaliyetler') as $i => $f) {
            if (empty($f['varsayilan_aylar'])) {
                return $i;
            }
        }

        return 0;
    }

    public function test_firma_ve_yil_secilince_varsayilan_faaliyetler_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2027);

        $plan = YillikPlan::where('firma_id', $firma->id)->where('yil', 2027)->firstOrFail();
        $this->assertCount(count(config('isg.yillik_plan.varsayilan_faaliyetler')), $plan->faaliyetler);
        // "Yıllık çalışma planının hazırlanması" varsayilan_aylar => [0] → otomatik Planlandı.
        $this->assertSame('planlandi', $plan->faaliyetler[0]['aylar'][0]);
    }

    public function test_kullanici_veri_girmeden_plan_otomatik_dolar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['sozlesme_baslangic' => null]);

        Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id)->set('yil', 2027);

        $plan = YillikPlan::where('firma_id', $firma->id)->where('yil', 2027)->firstOrFail();

        // "İşyeri saha gözetimi" (Sürekli) → 12 ayın tamamı Planlandı.
        $surekliSatir = collect($plan->faaliyetler)->firstWhere('frekans', 'Sürekli');
        $this->assertSame(array_fill(0, 12, 'planlandi'), $surekliSatir['aylar']);
        // Eğitimler de referans plana göre Aralık'ta dolu gelir.
        $this->assertSame('planlandi', $plan->egitimler[0]['aylar'][11]);
    }

    public function test_ay_durumu_tiklaninca_sirayla_degisir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $idx = $this->bosBaslayanFaaliyetIndeksi();

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ayDurumDegistir', 'faaliyetler', $idx, 0);

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('planlandi', $plan->faaliyetler[$idx]['aylar'][0]);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ayDurumDegistir', 'faaliyetler', $idx, 0);

        $this->assertSame('tamamlandi', $plan->fresh()->faaliyetler[$idx]['aylar'][0]);
    }

    public function test_atanmis_uzman_oncesi_aylar_secilemez_ve_otomatik_dolmaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['sozlesme_baslangic' => '2027-05-10']);

        $component = Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id)->set('yil', 2027);
        $component->assertSet('kilitAyIndeksi', 4);

        $plan = YillikPlan::where('firma_id', $firma->id)->where('yil', 2027)->firstOrFail();
        // "İşyeri saha gözetimi" (Sürekli): Ocak–Nisan boş, Mayıs'tan itibaren dolu.
        $surekliSatir = collect($plan->faaliyetler)->firstWhere('frekans', 'Sürekli');
        $this->assertSame(['bos', 'bos', 'bos', 'bos'], array_slice($surekliSatir['aylar'], 0, 4));
        $this->assertSame('planlandi', $surekliSatir['aylar'][4]);

        // Nisan'a (indeks 3) tıklama reddedilir.
        $surekliIndeks = collect($plan->faaliyetler)->search(fn ($f) => ($f['frekans'] ?? null) === 'Sürekli');
        $component->call('ayDurumDegistir', 'faaliyetler', $surekliIndeks, 3)
            ->assertNotified('Bu ay seçilemez');
        $this->assertSame('bos', $plan->fresh()->faaliyetler[$surekliIndeks]['aylar'][3]);
    }

    public function test_farkli_yillar_ayri_plan_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id)->set('yil', 2026);
        Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id)->set('yil', 2027);

        $this->assertSame(2, YillikPlan::where('firma_id', $firma->id)->count());
    }

    public function test_faaliyet_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniFaaliyet', 'Özel Denetim')
            ->set('yeniSorumlu', 'İSG Uzmanı')
            ->call('faaliyetEkle');

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $varsayilanSayisi = count(config('isg.yillik_plan.varsayilan_faaliyetler'));
        $this->assertCount($varsayilanSayisi + 1, $plan->faaliyetler);
        $this->assertSame('Özel Denetim', $plan->faaliyetler[$varsayilanSayisi]['faaliyet']);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('faaliyetSil', $varsayilanSayisi);

        $this->assertCount($varsayilanSayisi, $plan->fresh()->faaliyetler);
    }

    public function test_plani_kaydet_butonu_calisir_ve_bildirim_gonderir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('planiKaydet')
            ->assertNotified();

        $this->assertDatabaseHas('yillik_planlar', ['firma_id' => $firma->id]);
    }

    public function test_varsayilana_sifirlanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $idx = $this->bosBaslayanFaaliyetIndeksi();
        $component = Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id);
        $component->call('ayDurumDegistir', 'faaliyetler', $idx, 0)->call('varsayilanaSifirla');

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        // Sıfırlama otomatik doldurmayı da yeniden uygular: [0] auto-fill'li satır Planlandı olur.
        $this->assertSame('planlandi', $plan->faaliyetler[0]['aylar'][0]);
        $this->assertSame('bos', $plan->faaliyetler[$idx]['aylar'][0]);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $plan = YillikPlan::firmaYilIcin($firma, 2026);

        $yanit = YillikPlanUretici::pdf($plan);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(PlanSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    public function test_firma_secilince_varsayilan_egitimler_ve_degerlendirmeler_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id);

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(count(config('isg.yillik_plan.varsayilan_egitimler')), $plan->egitimler);
        // Referans plana göre eğitimler Aralık'ta otomatik Planlandı, diğer aylar boş.
        $this->assertSame('planlandi', $plan->egitimler[0]['aylar'][11]);
        $this->assertSame('bos', $plan->egitimler[0]['aylar'][0]);
        $this->assertCount(count(config('isg.yillik_plan.varsayilan_degerlendirmeler')), $plan->degerlendirmeler);
        $this->assertNull($plan->degerlendirmeler[0]['tarih']);
    }

    public function test_egitim_ay_durumu_degisir_ve_egitim_eklenip_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ayDurumDegistir', 'egitimler', 0, 0);

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('planlandi', $plan->egitimler[0]['aylar'][0]);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniEgitimKonu', 'Forklift Operatörlüğü')
            ->set('yeniEgitimEgitici', 'İSG Uzmanı')
            ->call('egitimEkle');

        $varsayilanSayisi = count(config('isg.yillik_plan.varsayilan_egitimler'));
        $this->assertCount($varsayilanSayisi + 1, $plan->fresh()->egitimler);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('egitimSil', $varsayilanSayisi);

        $this->assertCount($varsayilanSayisi, $plan->fresh()->egitimler);
    }

    public function test_degerlendirme_satiri_guncellenir_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('degerlendirmeGuncelle', 0, 'tarih', '2026-03-05')
            ->call('degerlendirmeGuncelle', 0, 'tekrar_sayisi', '2');

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('2026-03-05', $plan->degerlendirmeler[0]['tarih']);
        $this->assertSame('2', $plan->degerlendirmeler[0]['tekrar_sayisi']);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniDegerlendirmeCalisma', 'Gürültü haritası güncellemesi')
            ->call('degerlendirmeEkle');

        $varsayilanSayisi = count(config('isg.yillik_plan.varsayilan_degerlendirmeler'));
        $this->assertCount($varsayilanSayisi + 1, $plan->fresh()->degerlendirmeler);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('degerlendirmeSil', $varsayilanSayisi);

        $this->assertCount($varsayilanSayisi, $plan->fresh()->degerlendirmeler);
    }

    public function test_egitim_sekmesi_varsayilana_sifirlanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('sekme', 'egitim')
            ->call('ayDurumDegistir', 'egitimler', 0, 0)
            ->call('varsayilanaSifirla');

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('bos', $plan->egitimler[0]['aylar'][0]);
    }

    /** Referans "YILLIK ÇALIŞMA PLANI.xlsx" düzeninde küçük bir dosya üretir. */
    private function calismaPlaniXlsx(): string
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->fromArray(['FİRMA ADI: TEST', '', '', '', '', '', '2027 YILLIK ÇALIŞMA PLANI'], null, 'A1');
        //         A                B               C          D=OCA E=ŞUB F=MAR G=NIS H=MAY I=HAZ J=TEM K=AĞU L=EYL M=EKİ N=KAS O=ARA   P
        $s->fromArray(['YASAL GEREKLİLİK', 'ÖLÇÜM / RAPOR', 'SORUMLU', 'OCAK', 'ŞUBAT', 'MART', 'NİSAN', 'MAYIS', 'HAZİRAN', 'TEMMUZ', 'AĞUSTOS', 'EYLÜL', 'EKİM', 'KASIM', 'ARALIK', 'SON ÖLÇÜM'], null, 'A3');
        // Mevcut varsayılan satırla eşleşen bir isim (EKİM işaretli) + tamamen yeni bir satır (ŞUBAT + HAZİRAN).
        $s->fromArray(['Binaların Yangından Korunması Hakkında Yönetmelik', 'Yangın söndürme tüplerinin yıllık periyodik kontrolü', 'İşveren', '', '', '', '', '', '', '', '', '', 'X', '', '', 'Yılda 1'], null, 'A4');
        $s->fromArray(['Özel Yönetmelik', 'Vinç kabin içi kamera kontrolü', 'Teknik Ekip', '', 'X', '', '', '', 'X', '', '', '', '', '', '', '6 Ayda 1'], null, 'A5');

        $yol = tempnam(sys_get_temp_dir(), 'ycp').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_calisma_plani_serbest_excel_ile_yuklenir_isimle_eslenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['sozlesme_baslangic' => null]);
        $plan = YillikPlan::firmaYilIcin($firma, 2027);
        $oncekiSayi = count($plan->faaliyetler);

        $sonuc = YillikPlanExcelIceAktarici::iceAktar($this->calismaPlaniXlsx(), $plan, 'faaliyetler');

        $this->assertSame([], $sonuc['hatalar']);
        $this->assertSame(1, $sonuc['eklenen']);       // Vinç kabin içi kamera kontrolü
        $this->assertSame(1, $sonuc['guncellenen']);   // Yangın söndürme tüpleri (isim eşleşti)

        $plan->refresh();
        $this->assertCount($oncekiSayi + 1, $plan->faaliyetler);

        $yangin = collect($plan->faaliyetler)->firstWhere('faaliyet', 'Yangın söndürme tüplerinin yıllık periyodik kontrolü');
        $this->assertSame('planlandi', $yangin['aylar'][9]);  // EKİM
        $this->assertSame('bos', $yangin['aylar'][0]);

        $vinc = collect($plan->faaliyetler)->firstWhere('faaliyet', 'Vinç kabin içi kamera kontrolü');
        $this->assertSame('planlandi', $vinc['aylar'][1]);    // ŞUBAT
        $this->assertSame('planlandi', $vinc['aylar'][5]);    // HAZİRAN
    }

    public function test_excel_import_atanmis_uzman_oncesi_aylari_isaretlemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['sozlesme_baslangic' => '2027-04-01']);
        $plan = YillikPlan::firmaYilIcin($firma, 2027);

        YillikPlanExcelIceAktarici::iceAktar($this->calismaPlaniXlsx(), $plan, 'faaliyetler');
        $plan->refresh();

        // Excel'de ŞUBAT işaretliydi ama sözleşme Nisan'da başlıyor → boş kalmalı.
        $vinc = collect($plan->faaliyetler)->firstWhere('faaliyet', 'Vinç kabin içi kamera kontrolü');
        $this->assertSame('bos', $vinc['aylar'][1]);   // ŞUBAT — kilitli
        $this->assertSame('planlandi', $vinc['aylar'][5]); // HAZİRAN — serbest
    }

    public function test_degerlendirme_sistemden_doldur_gercek_kayitlardan_yazar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => '2027-02-15']);
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => '2027-08-01']);
        EgitimKatilim::create(['firma_id' => $firma->id, 'belge_tarihi' => '2027-06-10']);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2027)
            ->set('sekme', 'degerlendirme')
            ->callAction('degerlendirmeSistemdenDoldur')
            ->assertNotified();

        $plan = YillikPlan::where('firma_id', $firma->id)->where('yil', 2027)->firstOrFail();
        $risk = collect($plan->degerlendirmeler)->firstWhere('calisma', 'Risk değerlendirmesi');
        $this->assertSame('2027-08-01', $risk['tarih']);
        $this->assertSame(2, $risk['tekrar_sayisi']);

        $egitim = collect($plan->degerlendirmeler)->firstWhere('calisma', 'Eğitim çalışmaları');
        $this->assertSame('2027-06-10', $egitim['tarih']);
        $this->assertSame(1, $egitim['tekrar_sayisi']);
    }
}
