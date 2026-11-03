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

    public function test_is_koluna_gore_kayit_kutuphaneye_yazilir_ve_baska_firmaya_gelir(): void
    {
        Calisan::factory()->for($this->firma)->create();

        // Kullanıcı kazı grubuna kendi konusunu ekleyip kaydeder.
        $sayfa = Livewire::test(EgitimSayfasi::class)->set('firmaId', $this->firma->id)->set('sektorAnahtari', 'insaat')
            ->call('isKoluToggle', 'hafriyat_kazi');
        $icerik = $sayfa->get('icerik');
        $icerik['isyerine_ozgu']['maddeler'][] = ['madde' => 'Şev stabilitesi ve iksa kontrolü', 'dakika' => 15, 'dahil' => true];
        $sayfa->set('icerik', $icerik)->callAction('sadeceKaydet');

        $k = \App\Models\IseOzguEgitimKonusu::where('user_id', $this->uzman->id)->where('is_kolu', 'Hafriyat ve Kazı İşleri')->get();
        $this->assertSame(['Şev stabilitesi ve iksa kontrolü'], $k->pluck('ad')->all(), 'yalnız kullanıcının eklediği konu; sistem iş kalemi konusu tekrar kaydedilmez');
        $this->assertSame(['hafriyat_kazi'], $k->first()->is_kalemleri);

        // Aynı iş koluyla tekrar kayıt: tekrar eklenmez.
        $sayfa->call('kayitUnut')->callAction('sadeceKaydet');
        $this->assertSame(1, \App\Models\IseOzguEgitimKonusu::where('user_id', $this->uzman->id)->where('is_kolu', 'Hafriyat ve Kazı İşleri')->count());

        // BAŞKA firmada aynı iş koluyla yeni form: kütüphanedeki konu gelir.
        $diger = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        $yeni = Livewire::test(EgitimSayfasi::class)->set('firmaId', $diger->id)->set('sektorAnahtari', 'insaat')
            ->call('isKoluToggle', 'hafriyat_kazi');
        $this->assertStringContainsString('Şev stabilitesi ve iksa kontrolü', $this->ozguMetinler($yeni->get('icerik')));

        // İş koluna bağlı konu, iş kolu OLMAYAN formlara ve firma geneli plan önerisine girmez.
        $genel = Livewire::test(EgitimSayfasi::class)->set('firmaId', $diger->id)->set('sektorAnahtari', 'insaat');
        $this->assertStringNotContainsString('Şev stabilitesi', $this->ozguMetinler($genel->get('icerik')));
        $this->assertSame(0, \App\Support\IseOzguEgitimKutuphanesi::firmaIcin($diger)->filter(fn ($x) => str_contains($x['ad'], 'Şev stabilitesi'))->count());

        // Başka kullanıcının kütüphanesi karışmaz.
        $this->assertCount(0, \App\Support\IseOzguEgitimKutuphanesi::isKoluKonulari(User::factory()->create()->id, 'Hafriyat ve Kazı İşleri'));
    }

    public function test_grup_formu_yillik_plani_daraltmaz(): void
    {
        $c = Calisan::factory()->for($this->firma)->create();
        \App\Models\YillikPlan::create(['firma_id' => $this->firma->id, 'yil' => now()->year, 'egitimler' => [
            ['konu' => 'Plandaki eski konu', 'kategori' => 'ise_ozgu', 'hedef' => '', 'egitici' => 'İŞ GÜVENLİĞİ UZMANI', 'hafta' => 2, 'aylar' => range(0, 11)],
        ]]);

        Livewire::test(EgitimSayfasi::class)->set('firmaId', $this->firma->id)->set('sektorAnahtari', 'insaat')
            ->call('isKoluToggle', 'hafriyat_kazi')
            ->callAction('sadeceKaydet');

        $konular = collect(\App\Models\YillikPlan::where('firma_id', $this->firma->id)->first()->egitimler)->pluck('konu')->implode(' | ');
        $this->assertStringContainsString('Plandaki eski konu', $konular, 'diğer grubun/planın konusu silinmemeli');
        $this->assertStringContainsString('Kazı kenarı', $konular, 'bu grubun konusu eklenir');
        $this->assertNotNull($c->id);
    }

    public function test_bos_form_firmanin_guncel_konularini_sablona_ekler(): void
    {
        $this->firma->update(['is_kalemleri' => ['hafriyat_kazi']]);
        \App\Models\YillikPlan::create(['firma_id' => $this->firma->id, 'yil' => now()->year, 'egitimler' => [
            ['konu' => 'Firmaya özel yeni 4. konu', 'kategori' => 'ise_ozgu', 'hedef' => '', 'egitici' => 'İŞ GÜVENLİĞİ UZMANI', 'hafta' => 2, 'aylar' => range(0, 11)],
        ]]);

        $icerik = \App\Support\EgitimFirmaIcerigi::bosForm($this->firma, 'genel', 'insaat', 'ilk');
        $metin = $this->ozguMetinler($icerik);

        $sablon = $this->ozguMetinler(\App\Support\EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'cok_tehlikeli', 'ilk'));
        $this->assertStringContainsString($sablon, $metin, 'şablon konuları yerinde kalır (yerine geçmez)');
        $this->assertStringContainsString('Firmaya özel yeni 4. konu', $metin, 'yıllık plan 4. bölüm konusu eklenir');
        $this->assertStringContainsString('Kazı kenarı', $metin, 'iş kalemi konusu eklenir');
        $this->assertSame(substr_count($metin, 'Firmaya özel yeni 4. konu'), 1);

        // Çıktı: firma adlı boş form PDF'i üretilir (Evrakları İndir ile aynı çağrı).
        $yanit = \App\Support\EgitimKatilimUretici::bosFormPdf('genel', 'insaat', 'cok_tehlikeli', 'ilk', $this->firma);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        // Firmasız (genel) boş form sektör şablonu olarak kalır.
        $this->assertSame($sablon, $this->ozguMetinler(\App\Support\EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'cok_tehlikeli', 'ilk')));
    }
}
