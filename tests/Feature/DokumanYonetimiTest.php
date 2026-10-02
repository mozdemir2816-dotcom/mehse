<?php

namespace Tests\Feature;

use App\Filament\Pages\DokumanYonetimi;
use App\Models\ArsivDosya;
use App\Models\Bildirim;
use App\Models\Firma;
use App\Models\User;
use App\Support\BildirimTarayici;
use App\Support\IsyeriDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DokumanYonetimiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Ahmet Yapı']);
    }

    private function dokuman(array $ek = []): ArsivDosya
    {
        Storage::disk('public')->put('dokumanlar/x.pdf', '%PDF-1.4 deneme');

        return ArsivDosya::create([
            'firma_id' => $this->firma->id, 'dosya_adi' => 'x.pdf', 'dosya_yolu' => 'dokumanlar/x.pdf', 'boyut' => 15,
            'kategori' => 'yillik_plan', 'baslik' => 'Yıllık Plan 2026', 'versiyon' => '1.0', ...$ek,
        ]);
    }

    public function test_yeni_dokuman_kaydi_dosya_ile_olusur(): void
    {
        Livewire::test(DokumanYonetimi::class)
            ->callAction('yeni', [
                'firma_id' => $this->firma->id,
                'kategori' => 'risk_degerlendirmesi',
                'baslik' => 'Risk Değerlendirmesi Rev.2',
                'versiyon' => '2.0',
                'aciklama' => 'İmzalı nüsha',
                'baslangic_tarihi' => now()->toDateString(),
                'gecerlilik_sonu' => now()->addYears(2)->toDateString(),
                'dosya' => UploadedFile::fake()->create('rd.pdf', 120, 'application/pdf'),
            ])
            ->assertHasNoActionErrors();

        $d = ArsivDosya::sole();
        $this->assertSame('Risk Değerlendirmesi Rev.2', $d->baslik);
        $this->assertSame('risk_degerlendirmesi', $d->kategori);
        $this->assertTrue($d->aktif);
        $this->assertSame('gecerli', $d->gecerlilikDurumu());
        Storage::disk('public')->assertExists($d->dosya_yolu);
    }

    public function test_duzenle_pasife_al_filtre_ve_excel(): void
    {
        $d = $this->dokuman(['gecerlilik_sonu' => now()->subDay()]);
        $eskiArsiv = ArsivDosya::create(['firma_id' => $this->firma->id, 'dosya_adi' => 'eski.pdf', 'dosya_yolu' => 'arsiv/eski.pdf', 'boyut' => 1]);   // Profilim > Arşiv kaydı

        $sayfa = Livewire::test(DokumanYonetimi::class)
            ->assertSee('Yıllık Plan 2026')
            ->assertSee('eski.pdf')
            ->assertSee('Süresi doldu');

        $this->assertSame(['aktif' => 2, 'dolmus' => 1, 'yaklasan' => 0, 'pasif' => 0], $sayfa->instance()->ozet);
        $this->assertSame('Diğer', $eskiArsiv->kategoriEtiketi());

        $sayfa->callAction('duzenle', [
            'firma_id' => $this->firma->id, 'kategori' => 'yillik_plan', 'baslik' => 'Yıllık Plan 2026 (revize)',
            'versiyon' => '1.1', 'gecerlilik_sonu' => now()->addMonths(6)->toDateString(),
        ], ['id' => $d->id])->assertHasNoActionErrors();
        $this->assertSame('1.1', $d->fresh()->versiyon);
        $this->assertSame('dokumanlar/x.pdf', $d->fresh()->dosya_yolu);   // dosya korunur

        $sayfa->call('durumDegistir', $eskiArsiv->id);
        $this->assertFalse($eskiArsiv->fresh()->aktif);

        $sayfa->set('durum', 'pasif');
        $this->assertSame([$eskiArsiv->id], $sayfa->instance()->dokumanlar->pluck('id')->all());
        $sayfa->set('durum', '')->set('arama', 'revize');
        $this->assertSame([$d->id], $sayfa->instance()->dokumanlar->pluck('id')->all());

        $sayfa->call('excelRapor')->assertFileDownloaded('dokuman-raporu.xlsx');
        $sayfa->call('indir', $d->id)->assertFileDownloaded('x.pdf');

        $sayfa->call('sil', $d->id);
        $this->assertNull($d->fresh());
        Storage::disk('public')->assertMissing('dokumanlar/x.pdf');
    }

    public function test_baskasinin_dokumani_gorulemez_ve_degistirilemez(): void
    {
        $yabanciFirma = Firma::factory()->for(User::factory())->create();
        $yabanci = ArsivDosya::create(['firma_id' => $yabanciFirma->id, 'dosya_adi' => 'gizli.pdf', 'dosya_yolu' => 'a/gizli.pdf', 'boyut' => 1, 'baslik' => 'Gizli Doküman']);

        Livewire::test(DokumanYonetimi::class)
            ->assertDontSee('Gizli Doküman')
            ->call('durumDegistir', $yabanci->id)
            ->call('sil', $yabanci->id);

        $this->assertTrue($yabanci->fresh()->aktif);
    }

    public function test_suresi_dolan_dokuman_bildirim_ve_durum_merkezine_duser(): void
    {
        $this->dokuman(['gecerlilik_sonu' => now()->subDays(3)]);
        $this->dokuman(['baslik' => 'Talimat', 'gecerlilik_sonu' => now()->addDays(10)]);
        $this->dokuman(['baslik' => 'Pasif eski', 'gecerlilik_sonu' => now()->subYear(), 'aktif' => false]);   // pasif sayılmaz

        BildirimTarayici::tara($this->uzman);
        $a = Bildirim::query()->acik()->pluck('seviye', 'anahtar');
        $this->assertSame('kritik', $a['f'.$this->firma->id.':dokuman:gecti']);
        $this->assertSame('uyari', $a['f'.$this->firma->id.':dokuman:yakin']);
        $this->assertStringNotContainsString('Pasif eski', Bildirim::where('anahtar', 'f'.$this->firma->id.':dokuman:gecti')->value('aciklama'));

        $surec = collect(IsyeriDurumu::surecler($this->firma))->firstWhere('surec', 'Dokümanlar (İSG dosyası)');
        $this->assertSame('gecikmis', $surec['durum']);
        $this->assertStringContainsString('2 aktif doküman, 1 süresi geçmiş', $surec['sonuc']);
        $this->assertCount(2, IsyeriDurumu::takvim($this->firma)->where('kategori', 'Doküman'));
    }
}
