<?php

namespace Tests\Feature;

use App\Filament\Pages\AtamaYazilari;
use App\Filament\Pages\CezaTeblig;
use App\Filament\Pages\IsIzinFormu;
use App\Filament\Pages\KurulToplantisi;
use App\Filament\Pages\TalimatOlustur;
use App\Filament\Pages\KurulToplantisi as KurulSayfasi;
use App\Models\Firma;
use Livewire\Livewire;
use App\Models\IsEkipmani;
use App\Models\KurulToplantisi as Toplanti;
use App\Models\SahaBulgusu;
use App\Models\User;
use App\Support\KararDofAktarici;
use App\Support\KurulUyeleri;
use App\Support\TalimatOnerisi;
use App\Support\SayfaAileleri;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Resmi Belge & Kurul Yönetim Merkezi: tek menü girişi ve 3 ana sekme. */
class BelgeKurulMerkeziTest extends TestCase
{
    use RefreshDatabase;

    public function test_menude_tek_giris_ve_uc_ana_sekme(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $grup = collect(Filament::getNavigation())->first(fn ($g) => $g->getLabel() === 'Belgeler & Yazışmalar');
        $this->assertNotNull($grup);
        $this->assertCount(1, $grup->getItems());
        $this->assertFalse(KurulToplantisi::shouldRegisterNavigation());

        $g = SayfaAileleri::sekmeGruplari([CezaTeblig::class]);
        $this->assertSame('belge', $g['aile']);
        $this->assertSame(
            ['İş İzni (PTW) & Güvenli Çalışma Talimatları', 'İSG Kurulu & Toplantı Yönetimi', 'Yasal Atamalar, Temsilci & Tebliğler'],
            array_column($g['ust'], 'etiket'),
        );
        $this->assertSame('Yasal Atamalar, Temsilci & Tebliğler', collect($g['ust'])->firstWhere('aktif', true)['etiket']);
        $this->assertCount(3, $g['alt']);
        $this->assertCount(2, SayfaAileleri::sekmeGruplari([TalimatOlustur::class])['alt']);

        foreach ([IsIzinFormu::class, TalimatOlustur::class, KurulToplantisi::class, AtamaYazilari::class, CezaTeblig::class] as $sayfa) {
            $this->get($sayfa::getUrl())->assertSuccessful()->assertSee('Yasal Atamalar, Temsilci &amp; Tebliğler', false);
        }
    }

    public function test_kurul_karari_dof_havuzuna_bir_kez_aktarilir(): void
    {
        $uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($uzman);
        $firma = Firma::factory()->for($uzman)->create(['calisan_sayisi' => 80]);
        $t = Toplanti::create([
            'firma_id' => $firma->id, 'toplanti_no' => 'K-1', 'tarih' => '2026-10-01', 'katilimcilar' => [], 'gundem' => ['Yangın'],
            'kararlar' => [['gundem_maddesi' => 'Yangın', 'karar_metni' => 'Tüpler yenilensin', 'sorumlu' => 'Ali', 'termin' => '2026-11-15', 'durum' => 'beklemede']],
        ]);

        $b = KararDofAktarici::aktar($t, 0);

        $this->assertSame('Tüpler yenilensin', $b->uygunsuzluk);
        $this->assertSame('Ali', $b->sorumlu);
        $this->assertSame('acik', $b->durum);
        $this->assertSame('kurul_toplantilari', $b->kaynak_tablo);
        $this->assertSame($b->id, $t->fresh()->kararlar[0]['bulgu_id']);

        $this->assertSame($b->id, KararDofAktarici::aktar($t->fresh(), 0)->id);
        $this->assertSame(1, SahaBulgusu::count());
        $this->assertNull(KararDofAktarici::aktar($t->fresh(), 5));
    }

    public function test_elliden_az_calisanda_zorunlu_uye_eksigi_engel_olmaz(): void
    {
        $uzman = User::factory()->create(['rol' => 'sahip']);
        $kucuk = Firma::factory()->for($uzman)->create(['calisan_sayisi' => 30]);
        $buyuk = Firma::factory()->for($uzman)->create(['calisan_sayisi' => 50]);

        $this->assertSame([], KurulUyeleri::eksikZorunlular($kucuk));
        $this->assertNotEmpty(KurulUyeleri::eksikZorunlular($buyuk));
    }

    public function test_talimat_onerisi_ekipman_envanterine_gore(): void
    {
        $uzman = User::factory()->create(['rol' => 'sahip']);
        $firma = Firma::factory()->for($uzman)->create();
        IsEkipmani::create(['firma_id' => $firma->id, 'kategori' => 'kaldirma', 'tip' => 'forklift', 'ekipman_adi' => 'Forklift (Dizel)', 'aktif' => true]);
        IsEkipmani::create(['firma_id' => $firma->id, 'kategori' => 'uretim', 'tip' => 'pres', 'ekipman_adi' => 'Eksantrik Pres', 'aktif' => false]);

        $anahtarlar = TalimatOnerisi::anahtarlar($firma);

        $this->assertContains('forklift', $anahtarlar);
        $this->assertNotContains('pres', $anahtarlar);   // pasif ekipman önerilmez
        $this->assertTrue(TalimatOnerisi::onerilenMi('Forklift Güvenli Kullanım Talimatı', $anahtarlar));
        $this->assertTrue(TalimatOnerisi::onerilenMi('FORKLİFT OPERATÖRÜ TALİMATI', $anahtarlar));
        $this->assertFalse(TalimatOnerisi::onerilenMi('Torna Tezgâhı Talimatı', $anahtarlar));
    }

    public function test_kurul_sayfasi_karar_butonu_ve_elliden_az_bilgilendirmesi(): void
    {
        $uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($uzman);
        $firma = Firma::factory()->for($uzman)->create(['calisan_sayisi' => 20]);
        $t = Toplanti::create([
            'firma_id' => $firma->id, 'toplanti_no' => 'K-2', 'tarih' => '2026-10-01', 'katilimcilar' => [], 'gundem' => ['Genel'],
            'kararlar' => [['gundem_maddesi' => 'Genel', 'karar_metni' => 'Uyarı levhaları asılsın', 'durum' => 'beklemede']],
        ]);

        $sayfa = Livewire::test(KurulSayfasi::class, ['firmaId' => $firma->id, 'toplantiId' => $t->id])
            ->assertSee("50'den az çalışan", false)
            ->assertSee('DÖF Havuzuna Aktar')
            ->call('kararDofAktar', 0)
            ->assertSee("DÖF'te", false)
            ->assertDontSee('DÖF Havuzuna Aktar');

        $this->assertSame(1, SahaBulgusu::where('firma_id', $firma->id)->count());
    }

    public function test_talimat_kutuphanesinde_onerilenler_en_uste_gelir(): void
    {
        $uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($uzman);
        $firma = Firma::factory()->for($uzman)->create();
        IsEkipmani::create(['firma_id' => $firma->id, 'kategori' => 'kaldirma', 'tip' => 'forklift', 'ekipman_adi' => 'Forklift', 'aktif' => true]);

        $sablonlar = Livewire::test(TalimatOlustur::class, ['firmaId' => $firma->id])->instance()->sablonlar();

        $this->assertTrue($sablonlar[0]['onerilen']);
        $this->assertStringContainsStringIgnoringCase('forklift', $sablonlar[0]['baslik']);
        $ilkOnerilmeyen = collect($sablonlar)->search(fn ($s) => ! $s['onerilen']);
        $this->assertSame(0, collect($sablonlar)->skip($ilkOnerilmeyen)->where('onerilen', true)->count());
    }

    public function test_ptw_cikti_qr_izin_kimligini_tasir(): void
    {
        $uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($uzman);
        $firma = Firma::factory()->for($uzman)->create(['unvan' => 'Ahmet Yapı']);
        $form = \App\Models\IsIzinFormu::create(['firma_id' => $firma->id]);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', \App\Support\IsIzinFormuUretici::qrDataUri($form->load('firma')));
        $this->assertSame(200, \App\Support\IsIzinFormuUretici::pdf($form)->getStatusCode());
    }
}
