<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimKatilim as EgitimSayfasi;
use App\Models\Calisan;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Aynı firmada çalışan grubuna / iş koluna göre ayrı eğitim katılım formu (kullanıcı 03.11.2026). */
class EgitimKatilimIsKoluTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'VİZYON TEST', 'tehlike_sinifi' => 'cok_tehlikeli']);
    }

    private function ozguMetinler(array $icerik): string
    {
        return collect($icerik['isyerine_ozgu']['maddeler'] ?? [])->pluck('madde')->implode(' | ');
    }

    public function test_iki_grup_icin_ayri_form_ayri_konu_ve_katilimci(): void
    {
        $kazi = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Kazı Çalışanı', 'sube' => 'KAZI EKİBİ']);
        $kaba = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Kaba İnşaat Çalışanı', 'sube' => 'KABA EKİP']);

        $sayfa = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('sektorAnahtari', 'insaat')
            ->assertSee('Hafriyat ve Kazı İşleri');   // iş kolu seçenekleri

        // 1) Kazı grubu
        $sayfa->call('isKoluToggle', 'hafriyat_kazi')
            ->assertSet('isKolu', 'Hafriyat ve Kazı İşleri')
            ->set('calisanFiltre', 'KAZI EKİBİ')
            ->call('filtredekileriSec')
            ->callAction('sadeceKaydet');

        $birinci = EgitimKatilim::where('firma_id', $this->firma->id)->latest('id')->firstOrFail();
        $this->assertSame('Hafriyat ve Kazı İşleri', $birinci->is_kolu);
        $this->assertSame(['hafriyat_kazi'], $birinci->is_kolu_kalemleri);
        $this->assertSame(['Kazı Çalışanı'], array_column($birinci->katilimcilar, 'ad_soyad'));
        $this->assertStringContainsString('Kazı kenarı', $this->ozguMetinler($birinci->konu_secimleri));

        // 2) Kaba inşaat grubu: yeni kayıt, farklı iş kalemi, farklı katılımcı
        $sayfa->call('kayitUnut')
            ->call('isKoluToggle', 'hafriyat_kazi')       // kaldır
            ->call('isKoluToggle', 'kalip_iskele_demir')
            ->assertSet('isKolu', 'Kalıp, İskele ve Demir İşleri')
            ->set('calisanFiltre', 'KABA EKİP')
            ->call('filtredekileriSec')
            ->callAction('sadeceKaydet');

        $ikinci = EgitimKatilim::where('firma_id', $this->firma->id)->latest('id')->firstOrFail();
        $this->assertNotSame($birinci->id, $ikinci->id, 'ayrı belge');
        $this->assertNotSame($birinci->belge_no, $ikinci->belge_no);
        $this->assertSame('Kalıp, İskele ve Demir İşleri', $ikinci->is_kolu);
        $this->assertSame(['Kaba İnşaat Çalışanı'], array_column($ikinci->katilimcilar, 'ad_soyad'));

        $ozgu2 = $this->ozguMetinler($ikinci->konu_secimleri);
        $this->assertStringNotContainsString('Kazı kenarı', $ozgu2, 'kazı grubunun konusu kaba inşaat formuna karışmamalı');
        $this->assertNotSame('', $ozgu2);

        $this->assertSame(2, EgitimKatilim::where('firma_id', $this->firma->id)->count());
    }

    public function test_grubun_konu_sablonu_ayri_hatirlanir(): void
    {
        $c = Calisan::factory()->for($this->firma)->create();

        $sayfa = Livewire::test(EgitimSayfasi::class)->set('firmaId', $this->firma->id)->set('sektorAnahtari', 'insaat')
            ->call('isKoluToggle', 'hafriyat_kazi')
            ->callAction('sadeceKaydet');

        // Aynı grup yeniden: önceki kayıttan yüklenir; grup değişince o gruba ait kayıt yoksa taze kurulur.
        $yeni = Livewire::test(EgitimSayfasi::class)->set('firmaId', $this->firma->id)->set('sektorAnahtari', 'insaat')
            ->call('isKoluToggle', 'hafriyat_kazi');
        $this->assertTrue($yeni->get('oncekidenYuklendi'));

        $baska = Livewire::test(EgitimSayfasi::class)->set('firmaId', $this->firma->id)->set('sektorAnahtari', 'insaat')
            ->call('isKoluToggle', 'beton_sap');
        $this->assertFalse($baska->get('oncekidenYuklendi'));
        $this->assertStringNotContainsString('Kazı kenarı', $this->ozguMetinler($baska->get('icerik')));
        $this->assertNotNull($c->id);
    }

    public function test_serbest_grup_adi_ve_cikti_gorunumu(): void
    {
        Calisan::factory()->for($this->firma)->create();

        Livewire::test(EgitimSayfasi::class)->set('firmaId', $this->firma->id)
            ->set('isKolu', 'Boyahane Ekibi')
            ->callAction('sadeceKaydet');

        $kayit = EgitimKatilim::where('firma_id', $this->firma->id)->firstOrFail();
        $this->assertSame('Boyahane Ekibi', $kayit->is_kolu);
        $this->assertNull($kayit->is_kolu_kalemleri);

        $html = view('pdf.egitim-katilim', ['kayit' => $kayit->load('firma'), 'firma' => $this->firma, 'icerik' => $kayit->konu_secimleri, 'imzali' => true])->render();
        $this->assertStringContainsString('İş Kolu / Grup: Boyahane Ekibi', $html);

        // Grup adı olmayan eski kayıtlarda satır yok.
        $eski = EgitimKatilim::create(['firma_id' => $this->firma->id, 'baslik_anahtari' => 'genel', 'belge_tarihi' => now(), 'konu_secimleri' => $kayit->konu_secimleri, 'katilimcilar' => []]);
        $this->assertStringNotContainsString('İş Kolu / Grup', view('pdf.egitim-katilim', ['kayit' => $eski->load('firma'), 'firma' => $this->firma, 'icerik' => $eski->konu_secimleri, 'imzali' => true])->render());
    }

    public function test_katilimci_suzgeci_sube_ve_departmana_gore(): void
    {
        $a = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'A', 'sube' => 'X EKİBİ', 'departman' => null]);
        $b = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'B', 'sube' => null, 'departman' => 'X EKİBİ']);
        $c = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'C', 'sube' => 'Y EKİBİ', 'departman' => null]);

        $s = Livewire::test(EgitimSayfasi::class)->set('firmaId', $this->firma->id);
        $this->assertSame(['X EKİBİ', 'Y EKİBİ'], $s->instance()->calisanFiltreSecenekleri);

        $s->set('calisanFiltre', 'X EKİBİ')->call('filtredekileriSec');
        $secilen = $s->get('secilenCalisanIdler');
        sort($secilen);
        $this->assertSame([$a->id, $b->id], $secilen);
        $this->assertNotContains($c->id, $secilen);
    }
}
