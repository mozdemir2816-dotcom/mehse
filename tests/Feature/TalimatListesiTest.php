<?php

namespace Tests\Feature;

use App\Filament\Pages\TalimatOlustur as TalimatSayfasi;
use App\Models\Firma;
use App\Models\Talimat;
use App\Models\User;
use App\Support\TalimatListesiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Firmaya tanımlı talimatların listesi (PDF / Excel). */
class TalimatListesiTest extends TestCase
{
    use RefreshDatabase;

    public function test_firmanin_talimatlari_listelenir_ve_indirilir(): void
    {
        $uzman = User::factory()->create();
        $this->actingAs($uzman);
        $firma = Firma::factory()->for($uzman)->create(['unvan' => 'Vizyon Yapı A.Ş.']);
        $baska = Firma::factory()->for($uzman)->create();

        Talimat::create(['firma_id' => $firma->id, 'baslik' => 'Forklift Kullanma Talimatı', 'kkdler' => ['Baret'], 'maddeler' => ['Madde A', 'Madde B']]);
        Talimat::create(['firma_id' => $firma->id, 'baslik' => 'Taşlama Talimatı', 'dosya_adi' => 'taslama.pdf', 'dosya_yolu' => 'talimatlar/1/taslama.pdf']);
        Talimat::create(['firma_id' => $baska->id, 'baslik' => 'Başka Firmanın Talimatı', 'maddeler' => ['X']]);

        $liste = TalimatListesiUretici::talimatlar($firma);
        $this->assertSame(['Forklift Kullanma Talimatı', 'Taşlama Talimatı'], $liste->pluck('baslik')->sort()->values()->all());
        $this->assertSame('2 madde', TalimatListesiUretici::icerikEtiketi($liste->firstWhere('baslik', 'Forklift Kullanma Talimatı')));
        $this->assertSame('Yüklenen dosya (PDF)', TalimatListesiUretici::icerikEtiketi($liste->firstWhere('baslik', 'Taşlama Talimatı')));

        $html = view('pdf.talimat-listesi', ['firma' => $firma, 'talimatlar' => $liste, 'maddelerDahil' => true, 'logo' => null])->render();
        $this->assertStringContainsString('ÇALIŞMA TALİMATLARI LİSTESİ', $html);
        $this->assertStringContainsString('Madde B', $html);
        $this->assertStringNotContainsString('Başka Firmanın Talimatı', $html);

        $sayfa = Livewire::test(TalimatSayfasi::class)->set('firmaId', $firma->id)->assertActionVisible('talimatListesi');
        $sayfa->callAction('talimatListesi', ['bicim' => 'pdf', 'maddeler' => true])
            ->assertFileDownloaded('talimat-listesi-vizyon-yapi-as.pdf');
        $sayfa->callAction('talimatListesi', ['bicim' => 'excel'])
            ->assertFileDownloaded('talimat-listesi-vizyon-yapi-as.xlsx');
    }
}
