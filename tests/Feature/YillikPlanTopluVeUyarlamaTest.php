<?php

namespace Tests\Feature;

use App\Filament\Pages\YillikPlan\YillikCalismaPlani;
use App\Models\Firma;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\YillikPlanTopluIndirme;
use App\Support\YillikPlanUyarlayici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

/** Yıllık planların toplu (ZIP) indirilmesi ve başka firmalara uyarlanması (11.11.2026). */
class YillikPlanTopluVeUyarlamaTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function insaat(array $ek = []): Firma
    {
        return Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Kaynak İnşaat Ltd. Şti.', 'kisa_ad' => 'KAYNAK', 'nace_kodu' => '41.00.01',
            'tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 80, 'sozlesme_baslangic' => null, 'is_kalemleri' => null, ...$ek,
        ]);
    }

    private function kurulSatiri(YillikPlan $plan): array
    {
        return collect($plan->faaliyetler)->firstWhere('ana_konu', 'İSG KURULU');
    }

    public function test_uc_plan_tek_zipte_ayri_tarihlerle_uretilir(): void
    {
        $plan = YillikPlan::firmaYilIcin($this->insaat(), 2027);

        foreach (['form', 'ydr'] as $bicim) {
            $zipYolu = YillikPlanTopluIndirme::zipOlustur($plan, Carbon::parse('2026-12-01'), Carbon::parse('2026-12-02'), Carbon::parse('2026-12-03'), $bicim);

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($zipYolu) === true);
            $adlar = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $adlar[] = $zip->getNameIndex($i);
            }
            $zip->close();
            @unlink($zipYolu);

            $this->assertCount(3, $adlar, $bicim);
            $this->assertNotEmpty(preg_grep('/^yillik-calisma-plani-.*2027\.xlsx$/', $adlar));
            $this->assertNotEmpty(preg_grep('/^yillik-egitim-plani-.*2027\.xlsx$/', $adlar));
            $this->assertNotEmpty(preg_grep('/degerlendirme/', $adlar));
        }
    }

    public function test_2027_hazirlanma_tarihi_04_01_2027_gelir_sonra_bugun_olur(): void
    {
        $firma = $this->insaat();
        YillikPlan::firmaYilIcin($firma, 2027);

        $this->travelTo(Carbon::parse('2026-11-11'));
        Livewire::test(YillikCalismaPlani::class)
            ->set('firmaId', $firma->id)->set('yil', 2027)
            ->mountAction('calismaExcel')
            ->assertSchemaStateSet(['hazirlanma_tarihi' => '2027-01-04']);
        Livewire::test(YillikCalismaPlani::class)
            ->set('firmaId', $firma->id)->set('yil', 2027)
            ->mountAction('topluIndir')
            ->assertSchemaStateSet(['calisma_tarihi' => '2027-01-04', 'egitim_tarihi' => '2027-01-04', 'degerlendirme_tarihi' => '2027-01-04']);

        // Tarih geçtikten sonra hazırlanan evrak bugünün tarihini alır; elle değiştirilebilir.
        $this->travelTo(Carbon::parse('2027-02-15'));
        Livewire::test(YillikCalismaPlani::class)
            ->set('firmaId', $firma->id)->set('yil', 2027)
            ->mountAction('calismaExcel')
            ->assertSchemaStateSet(['hazirlanma_tarihi' => '2027-02-15'])
            ->callMountedAction(['hazirlanma_tarihi' => '2027-01-20'])
            ->assertHasNoErrors()
            ->assertFileDownloaded();

        // Ayarı olmayan yıl: bugün.
        $this->assertSame('2027-02-15', YillikCalismaPlani::varsayilanHazirlanmaTarihi(2028));
    }

    public function test_toplu_indir_dugmesi_zip_indirir(): void
    {
        $firma = $this->insaat();
        YillikPlan::firmaYilIcin($firma, 2027);

        Livewire::test(YillikCalismaPlani::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2027)
            ->callAction('topluIndir', [
                'calisma_tarihi' => '2026-12-01', 'egitim_tarihi' => '2026-12-02', 'degerlendirme_tarihi' => '2026-12-03',
                'bicim' => 'form', 'imzali' => '1',
            ])
            ->assertHasNoErrors()
            ->assertFileDownloaded('yillik-planlar-kaynak-insaat-ltd-sti-2027.zip');
    }

    public function test_plan_hedef_firmanin_bilgilerine_gore_uyarlanir(): void
    {
        $kaynakFirma = $this->insaat();
        $kaynak = YillikPlan::firmaYilIcin($kaynakFirma, 2027);

        // Kaynağa özel satır (Ocak P, Şubat G) + elle yazılmış değerlendirme satırı.
        $faaliyetler = $kaynak->faaliyetler;
        $faaliyetler[] = ['ana_konu' => 'ÖZEL', 'faaliyet' => 'Vinç operatörü belge kontrolü', 'frekans' => 'Yılda 1',
            'aylar' => ['planlandi', 'tamamlandi', ...array_fill(0, 10, 'bos')]];
        $degerlendirmeler = $kaynak->degerlendirmeler;
        $degerlendirmeler[0] = [...$degerlendirmeler[0], 'sonuc' => 'KAYNAK şantiyesinde iskele denetimi yapıldı.', 'elle' => true];
        $kaynak->update(['faaliyetler' => $faaliyetler, 'degerlendirmeler' => $degerlendirmeler]);

        // Hedef: 20 çalışan (kurul zorunlu değil), sözleşme Mart 2027'de başlıyor.
        $hedef = $this->insaat(['unvan' => 'Hedef Yapı A.Ş.', 'kisa_ad' => 'HEDEF', 'calisan_sayisi' => 20, 'sozlesme_baslangic' => '2027-03-05']);

        $sonuc = YillikPlanUyarlayici::uyarla($kaynak->fresh(), $hedef, array_keys(YillikPlanUyarlayici::BOLUMLER));

        $ozel = collect($sonuc->faaliyetler)->firstWhere('faaliyet', 'Vinç operatörü belge kontrolü');
        $this->assertNotNull($ozel, 'kaynaktaki özel satır kopyalanır');
        // Ocak–Şubat sözleşme öncesi: boş. Kaynaktaki G başka firmaya P olarak bile geçmez çünkü ay kilitli.
        $this->assertSame(['bos', 'bos'], array_slice($ozel['aylar'], 0, 2));
        $this->assertNotContains('planlandi', $this->kurulSatiri($sonuc)['aylar'], '50 çalışan altı: kurul planlanmaz');
        $this->assertSame('HEDEF şantiyesinde iskele denetimi yapıldı.', $sonuc->degerlendirmeler[0]['sonuc']);
        $this->assertSame(count($kaynak->fresh()->egitimler), count($sonuc->egitimler));
    }

    public function test_kaynaktaki_gerceklesen_hedefte_planli_olur_hedefin_kendi_g_si_korunur(): void
    {
        $kaynak = YillikPlan::firmaYilIcin($this->insaat(), 2027);
        $faaliyetler = $kaynak->faaliyetler;
        $faaliyetler[0]['aylar'][5] = 'tamamlandi';
        $kaynak->update(['faaliyetler' => $faaliyetler]);

        $hedef = $this->insaat(['unvan' => 'Hedef Yapı A.Ş.', 'kisa_ad' => 'HEDEF']);
        $hedefPlan = YillikPlan::firmaYilIcin($hedef, 2027);
        $hf = $hedefPlan->faaliyetler;
        $hf[0]['aylar'][7] = 'tamamlandi';
        $hedefPlan->update(['faaliyetler' => $hf]);

        $sonuc = YillikPlanUyarlayici::uyarla($kaynak->fresh(), $hedef, ['faaliyetler']);

        $this->assertSame('planlandi', $sonuc->faaliyetler[0]['aylar'][5]);
        $this->assertSame('tamamlandi', $sonuc->faaliyetler[0]['aylar'][7]);
        // 50+ çalışan, çok tehlikeli: kurul her ay planlı.
        $this->assertSame(array_fill(0, 12, 'planlandi'), $this->kurulSatiri($sonuc)['aylar']);
    }

    public function test_uyarla_dugmesi_yalniz_kendi_firmalarina_uygular(): void
    {
        $kaynakFirma = $this->insaat();
        YillikPlan::firmaYilIcin($kaynakFirma, 2027);
        $insaat = $this->insaat(['unvan' => 'Diğer İnşaat', 'kisa_ad' => null]);
        $fabrika = Firma::factory()->for($this->uzman)->create(['unvan' => 'Fabrika', 'nace_kodu' => '10.71.01', 'is_kalemleri' => null]);
        $baskasininki = Firma::factory()->for(User::factory()->create())->create(['nace_kodu' => '41.00.01']);

        Livewire::test(YillikCalismaPlani::class)
            ->set('firmaId', $kaynakFirma->id)
            ->set('yil', 2027)
            ->mountAction('digerFirmalaraUyarla')
            ->assertSchemaStateSet(['firmalar' => [$insaat->id]])   // inşaat firmaları önceden seçili
            ->callMountedAction(['bolumler' => ['faaliyetler', 'egitimler', 'degerlendirmeler'], 'firmalar' => [$insaat->id, $baskasininki->id]])
            ->assertHasNoErrors();

        $this->assertTrue(YillikPlan::where('firma_id', $insaat->id)->where('yil', 2027)->exists());
        $this->assertFalse(YillikPlan::where('firma_id', $baskasininki->id)->exists());
        $this->assertFalse(YillikPlan::where('firma_id', $fabrika->id)->exists());
    }
}
