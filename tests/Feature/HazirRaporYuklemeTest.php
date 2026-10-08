<?php

namespace Tests\Feature;

use App\Filament\Pages\AiSahaAnalizi;
use App\Filament\Pages\DofOlustur;
use App\Models\ArsivDosya;
use App\Models\Firma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HazirRaporYuklemeTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    /** DÖF tablosu formatında (Tespit / Öneri sütunlu) Word içeriği. */
    private function dofTablosuWord(): string
    {
        $word = new \PhpOffice\PhpWord\PhpWord;
        $s = $word->addSection();
        $bilgi = $s->addTable();
        $bilgi->addRow();
        $bilgi->addCell()->addText('Alan / Bölge');
        $bilgi->addCell()->addText('Bina cephesi');
        $t = $s->addTable();
        $t->addRow();
        foreach (['#', 'Tespit', 'Öncelik', 'Öneri / Düzeltici Faaliyet', 'Sorumlu', 'Termin', 'Durum', 'Foto'] as $b) {
            $t->addCell()->addText($b);
        }
        $t->addRow();
        foreach (['1', 'Korkuluk yok', 'Kritik', 'Korkuluk kurulmalı', 'İşveren', '15.10.2026', 'Açık', ''] as $d) {
            $t->addCell()->addText($d);
        }
        $yol = tempnam(sys_get_temp_dir(), 'dof').'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($yol);

        return (string) file_get_contents($yol);
    }

    /** Kullanıcı 08.10.2026: yapay zekaya hazırlatılan DÖF "yüklediğim gibi iniyor". */
    public function test_dof_sayfasinda_hazir_rapor_dof_tablosuysa_maddeler_aktarilir_arsivlenmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(DofOlustur::class)
            ->set('firmaId', $firma->id)
            ->callAction('hazirRaporYukle', data: [
                'dosyalar' => [UploadedFile::fake()->createWithContent('DOF_ornek.docx', $this->dofTablosuWord())],
                'tarih' => '2026-10-08',
            ])
            ->assertHasNoActionErrors()
            ->assertCount('maddeler', 1)
            ->assertSet('alanBolge', 'Bina cephesi')
            ->assertSee('Korkuluk yok');

        $this->assertSame(0, ArsivDosya::count());
    }

    public function test_saha_gozlem_sayfasinda_dof_tablosu_dof_olustura_aktarilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(AiSahaAnalizi::class)
            ->set('firmaId', $firma->id)
            ->callAction('hazirRaporYukle', data: [
                'dosyalar' => [UploadedFile::fake()->createWithContent('DOF_ornek.docx', $this->dofTablosuWord())],
                'tarih' => '2026-10-08',
            ])
            ->assertHasNoActionErrors()
            ->assertRedirect(DofOlustur::getUrl());

        $this->assertSame(0, ArsivDosya::count());

        Livewire::test(DofOlustur::class)
            ->assertSet('firmaId', $firma->id)
            ->assertCount('maddeler', 1)
            ->assertSet('alanBolge', 'Bina cephesi')
            ->assertSet('gozetimYapan', $this->uzman->name);
    }

    public function test_dof_sayfasindan_excel_yuklenir_ve_arsive_dof_kategorisiyle_girer(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(DofOlustur::class)
            ->set('firmaId', $firma->id)
            ->callAction('hazirRaporYukle', data: [
                'dosyalar' => [UploadedFile::fake()->createWithContent('dof-ekim.xlsx', str_repeat('a', 2048))],
                'tarih' => '2026-10-05',
                'aciklama' => 'Ekim ziyareti',
            ])
            ->assertHasNoActionErrors()
            ->assertSee('dof-ekim.xlsx');

        $d = ArsivDosya::sole();
        $this->assertSame($firma->id, $d->firma_id);
        $this->assertSame('dof', $d->kategori);
        $this->assertSame('dof-ekim.xlsx', $d->dosya_adi);
        $this->assertSame('2026-10-05', $d->baslangic_tarihi->toDateString());
        $this->assertSame('Ekim ziyareti', $d->aciklama);
        Storage::disk('public')->assertExists($d->dosya_yolu);
    }

    public function test_saha_gozlem_sayfasindan_word_yuklenir_indirilir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $sayfa = Livewire::test(AiSahaAnalizi::class)
            ->set('firmaId', $firma->id)
            ->callAction('hazirRaporYukle', data: [
                'dosyalar' => [UploadedFile::fake()->createWithContent('saha-gozlem.docx', str_repeat('b', 2048))],
                'tarih' => '2026-10-01',
            ])
            ->assertHasNoActionErrors();

        $d = ArsivDosya::sole();
        $this->assertSame('saha_gozlem', $d->kategori);

        $sayfa->call('hazirRaporIndir', $d->id)->assertFileDownloaded('saha-gozlem.docx');

        $sayfa->call('hazirRaporSil', $d->id);
        $this->assertSame(0, ArsivDosya::count());
        Storage::disk('public')->assertMissing($d->dosya_yolu);
    }

    public function test_baska_firmanin_raporu_indirilemez_ve_silinemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $baska = Firma::factory()->for(User::factory())->create();
        $yabanci = ArsivDosya::create([
            'firma_id' => $baska->id, 'kategori' => 'dof', 'baslik' => 'X',
            'dosya_adi' => 'x.xlsx', 'dosya_yolu' => 'arsiv/x.xlsx', 'boyut' => 1,
        ]);

        Livewire::test(DofOlustur::class)
            ->set('firmaId', $firma->id)
            ->call('hazirRaporSil', $yabanci->id);

        $this->assertNotNull($yabanci->fresh());
    }
}
