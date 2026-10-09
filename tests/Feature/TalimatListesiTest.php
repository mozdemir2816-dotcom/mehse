<?php

namespace Tests\Feature;

use App\Filament\Pages\TalimatOlustur as TalimatSayfasi;
use App\Models\Firma;
use App\Models\Talimat;
use App\Models\User;
use App\Support\TalimatListesiUretici;
use App\Support\TalimatUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Firmaya verilen talimatların listesi = Talimat Teslim Tutanağı (PDF / Excel). */
class TalimatListesiTest extends TestCase
{
    use RefreshDatabase;

    public function test_firmanin_talimatlari_teslim_tutanaginda_listelenir_ve_indirilir(): void
    {
        $uzman = User::factory()->create(['name' => 'Mehmet Özdemir']);
        $this->actingAs($uzman);
        $firma = Firma::factory()->for($uzman)->create(['unvan' => 'Vizyon Yapı A.Ş.', 'isveren_ad' => 'Ali Veli']);
        $baska = Firma::factory()->for($uzman)->create();

        Talimat::create(['firma_id' => $firma->id, 'baslik' => 'Forklift Kullanma Talimatı', 'kkdler' => ['Baret'], 'maddeler' => ['Madde A', 'Madde B']]);
        Talimat::create(['firma_id' => $firma->id, 'baslik' => 'Taşlama Talimatı', 'dosya_adi' => 'taslama.pdf', 'dosya_yolu' => 'talimatlar/1/taslama.pdf']);
        Talimat::create(['firma_id' => $firma->id, 'baslik' => 'Kalıp Talimatı', 'bolumler' => [['baslik' => 'AMAÇ', 'aciklama' => 'X', 'maddeler' => []]]]);
        Talimat::create(['firma_id' => $baska->id, 'baslik' => 'Başka Firmanın Talimatı', 'maddeler' => ['X']]);

        $liste = TalimatListesiUretici::talimatlar($firma);
        $this->assertSame(['Forklift Kullanma Talimatı', 'Taşlama Talimatı', 'Kalıp Talimatı'], $liste->pluck('baslik')->all());
        $this->assertSame('2 madde', TalimatListesiUretici::icerikEtiketi($liste[0]));
        $this->assertSame('Yüklenen dosya (PDF)', TalimatListesiUretici::icerikEtiketi($liste[1]));
        $this->assertSame('1 bölüm', TalimatListesiUretici::icerikEtiketi($liste[2]));

        $html = view('pdf.talimat-listesi', [
            'firma' => $firma, 'talimatlar' => $liste, 'tarih' => '10.10.2026', 'logo' => null,
            'teslimEden' => TalimatUretici::tebligEden($firma),
        ])->render();
        $this->assertStringContainsString('TALİMAT TESLİM TUTANAĞI', $html);
        $this->assertStringContainsString(TalimatListesiUretici::GIRIS, $html);
        $this->assertStringContainsString('İşbu tutanak iki nüsha olarak düzenlenmiş', $html);
        $this->assertStringContainsString('Mehmet Özdemir', $html);   // Teslim Eden: İGU yoksa hesap sahibi
        $this->assertStringContainsString('Ali Veli', $html);         // Teslim Alan: işveren
        $this->assertStringNotContainsString('Başka Firmanın Talimatı', $html);

        $sayfa = Livewire::test(TalimatSayfasi::class)->set('firmaId', $firma->id)->assertActionVisible('talimatListesi');
        $sayfa->callAction('talimatListesi', ['bicim' => 'pdf', 'tarih' => '2026-10-10'])
            ->assertFileDownloaded('talimat-teslim-tutanagi-vizyon-yapi-as.pdf');
        $sayfa->callAction('talimatListesi', ['bicim' => 'excel', 'tarih' => '2026-10-10'])
            ->assertFileDownloaded('talimat-teslim-tutanagi-vizyon-yapi-as.xlsx');
    }
}
