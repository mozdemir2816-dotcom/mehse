<?php

namespace Tests\Feature;

use App\Filament\Resources\Tehlikes\Pages\EkipmanRiskYukle;
use App\Filament\Resources\Tehlikes\TehlikeResource;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\EkipmanKatalogu;
use App\Support\EkipmanRiskKutuphanesi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Risk Kütüphanesi → Ekipman Risk Analizi Yükle (17.10.2026): kullanıcının 26 sütunlu
 * ekipman Fine-Kinney şablonu panelden kütüphaneye + ekipman tipine bağ.
 */
class EkipmanRiskKutuphanesiTest extends TestCase
{
    use RefreshDatabase;

    private const BASLIK = [
        'No', 'Ekipman', 'Faaliyet', 'Tehlike', 'Risk / sonuç', 'Etkilenen kişiler', 'Mevcut durum / saha tespiti',
        'Olasılık P', 'Frekans F', 'Şiddet S', 'Risk R', 'Risk derecesi', 'Açıklayıcı DÖF',
        'Hedef P', 'Hedef F', 'Hedef S', 'Hedef R', 'Hedef risk derecesi', 'Sorumlu', 'Önerilen termin',
        'DÖF durumu', 'Doğrulama / kapanış kanıtı', 'Tamamlanma / kontrol tarihi',
        'Standart dayanağı', 'Makina Emniyeti dayanağı', 'İş Ekipmanları dayanağı',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    /** @param  array<int, array<int, mixed>>  $satirlar */
    private function excel(array $satirlar, string $ad = 'Silindir_Fine_Kinney_30_Madde.xlsx'): string
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet()->setTitle('Risk Analizi');
        $s->fromArray(['SİLİNDİR — 30 MADDELİK FINE-KINNEY'], null, 'A1');
        $s->fromArray(['Firma: ..........'], null, 'A2');
        $s->fromArray(self::BASLIK, null, 'A7');
        $s->fromArray($satirlar, null, 'A8');
        $kitap->createSheet()->setTitle('Standartlar');

        $yol = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid().'-'.$ad;
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    private function satir(int $no, string $faaliyet, string $tehlike, array $puan = [3, 6, 40], array $hedef = [0.2, 6, 40], string $ekipman = 'Silindir — Genel'): array
    {
        return [$no, $ekipman, $faaliyet, $tehlike, 'Devrilme ve ölüm', 'Operatör', 'Saha doğrulaması bekliyor',
            $puan[0], $puan[1], $puan[2], null, 'Çok yüksek', 'Kenar mesafesi işaretlenir.',
            $hedef[0], $hedef[1], $hedef[2], null, 'Olası', 'Saha sorumlusu', 'İş başlamadan',
            'Açık', '', '', 'ISO 20500-1', '2006/42/AT Ek I', 'İEY Md. 5'];
    }

    public function test_sablon_okunur_siddet_cevrilir_onlem_ve_dayanak_tasinir(): void
    {
        $gruplar = EkipmanRiskKutuphanesi::oku($this->excel([
            $this->satir(1, 'Kenar sıkıştırması', 'Kenara yaklaşma'),
            $this->satir(2, 'Operatör eğitimi', 'Yetkisiz kullanım', [1, 6, 100], [0.1, 6, 100], 'Silindir — Asfalt modeli — varsa'),
            ['', 'Not satırı'],
        ]));

        $this->assertCount(1, $gruplar);
        $this->assertSame('Silindir', $gruplar[0]['ekipman']);
        $this->assertSame('Ekipman – Silindir', $gruplar[0]['kategori_adi']);
        [$a, $b] = $gruplar[0]['maddeler'];

        $this->assertSame([3.0, 6.0, 30.0], [$a['olasilik'], $a['frekans'], $a['siddet']]);
        $this->assertSame([0.2, 6.0, 30.0], [$a['son_olasilik'], $a['son_frekans'], $a['son_siddet']]);
        $this->assertSame(40.0, $b['siddet']);
        $this->assertSame('Kenar mesafesi işaretlenir.', $a['oneri']);
        $this->assertNull($a['mevcut_onlem']);
        $this->assertSame('Saha sorumlusu', $a['sorumlu']);
        $this->assertSame('İş başlamadan', $a['termin']);
        $this->assertSame('Standart: ISO 20500-1; Makina Emniyeti Yön.: 2006/42/AT Ek I; İş Ekipmanları Yön.: İEY Md. 5', $a['mevzuat']);
        $this->assertSame('Operatör eğitimi (Asfalt modeli)', $b['faaliyet']);
        $this->assertSame(['R', 'E'], [$a['harf'], $b['harf']]);
    }

    public function test_cok_ekipmanli_dosyada_her_ekipman_ayri_grup(): void
    {
        $gruplar = EkipmanRiskKutuphanesi::oku($this->excel([
            $this->satir(1, 'Ayna', 'Dönen ayna', ekipman: 'Torna'),
            $this->satir(2, 'Kesme', 'Disk kırılması', ekipman: 'İnce kesme tezgâhı'),
        ]));

        $this->assertSame(['Torna', 'İnce kesme tezgâhı'], array_column($gruplar, 'ekipman'));
        $this->assertSame('Ekipman – İnce kesme tezgâhı', $gruplar[1]['kategori_adi']);
    }

    public function test_tanimsiz_dosya_hata_verir(): void
    {
        $kitap = new Spreadsheet;
        $kitap->getActiveSheet()->fromArray([['Ad', 'Soyad'], ['Ali', 'Veli']]);
        $yol = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid().'.xlsx';
        (new Xlsx($kitap))->save($yol);

        $this->expectExceptionMessage('şablonu tanınmadı');
        EkipmanRiskKutuphanesi::oku($yol);
    }

    public function test_kaydet_kod_uretir_tekrar_yuklemede_gunceller_ve_eskiyi_siler(): void
    {
        $maddeler = EkipmanRiskKutuphanesi::oku($this->excel([
            $this->satir(1, 'Kenar sıkıştırması', 'Kenara yaklaşma'),
            $this->satir(2, 'Operatör eğitimi', 'Yetkisiz kullanım'),
            $this->satir(3, 'Yakıt ikmali', 'Yangın'),
        ]))[0]['maddeler'];

        $ilk = EkipmanRiskKutuphanesi::kaydet('Ekipman – Silindir', $maddeler, ['Silindir (Toprak / Asfalt)']);
        $this->assertTrue($ilk['yeni_kategori']);
        $this->assertSame(3, $ilk['eklenen']);

        $kategori = TehlikeKategorisi::where('anahtar', 'ekipman_silindir')->sole();
        $this->assertSame(['Silindir (Toprak / Asfalt)'], $kategori->ekipman_tipleri);
        $this->assertSame(['SIL-E-002', 'SIL-R-001', 'SIL-R-003'], $kategori->tehlikeler()->orderBy('kod')->pluck('kod')->all());

        // Elle eklenmiş (kodsuz) madde revizyonda korunur.
        Tehlike::create(['tehlike_kategorisi_id' => $kategori->id, 'tehlike' => 'Elle eklenen']);

        $revizyon = array_slice($maddeler, 0, 2);
        $revizyon[0]['oneri'] = 'Yeni önlem';
        $ikinci = EkipmanRiskKutuphanesi::kaydet('Ekipman – Silindir', $revizyon);

        $this->assertFalse($ikinci['yeni_kategori']);
        $this->assertSame([0, 2, 1], [$ikinci['eklenen'], $ikinci['guncellenen'], $ikinci['silinen']]);
        $this->assertSame('Yeni önlem', Tehlike::where('kod', 'SIL-R-001')->value('oneri'));
        $this->assertSame(3, $kategori->tehlikeler()->count());
        $this->assertSame(['Silindir (Toprak / Asfalt)'], $kategori->fresh()->ekipman_tipleri, 'tip boş gelince bağ silinmez');
    }

    public function test_bagli_tip_firmaya_eklenince_maddeler_beklenir(): void
    {
        $maddeler = EkipmanRiskKutuphanesi::oku($this->excel([$this->satir(1, 'Kenar sıkıştırması', 'Kenara yaklaşma')]))[0]['maddeler'];
        EkipmanRiskKutuphanesi::kaydet('Ekipman – Silindir', $maddeler, ['Silindir (Toprak / Asfalt)']);

        $this->assertSame(['ekipman_silindir'], EkipmanKatalogu::tanim('Silindir (Toprak / Asfalt)')['risk_kategorileri']);
        $this->assertNull(EkipmanKatalogu::tanim('Bilinmeyen Tip'));

        // Config'te tanımlı tipe bağ eklenince ikisi birleşir.
        EkipmanRiskKutuphanesi::kaydet('Ekipman – Damperli kamyon', $maddeler, ['Damperli Kamyon']);
        $this->assertSame(['santiye_damperli_kamyon', 'ekipman_damperli_kamyon'], EkipmanKatalogu::tanim('Damperli Kamyon')['risk_kategorileri']);

        $firma = Firma::factory()->for(auth()->user())->create();
        IsEkipmani::create(['firma_id' => $firma->id, 'kategori' => 'diger_ekipman', 'tip' => 'Silindir (Toprak / Asfalt)', 'ekipman_adi' => 'Silindir', 'aktif' => true]);

        $beklenen = EkipmanKatalogu::beklenenRiskler($firma);
        $this->assertSame(['Kenara yaklaşma'], $beklenen['Silindir (Toprak / Asfalt)']->pluck('tehlike')->all());
    }

    public function test_sayfadan_yukle_onizle_ve_kaydet(): void
    {
        $yol = $this->excel([
            $this->satir(1, 'Kenar sıkıştırması', 'Kenara yaklaşma'),
            $this->satir(2, 'Titreşim', 'El-kol titreşimi'),
        ]);

        $sayfa = Livewire::test(EkipmanRiskYukle::class)
            ->set('dosyalar', [UploadedFile::fake()->createWithContent('Silindir_Fine_Kinney_30_Madde.xlsx', file_get_contents($yol))])
            ->assertHasNoErrors()
            ->assertSet('onizleme.0.ekipman', 'Silindir')
            ->assertSet('onizleme.0.madde', 2)
            ->assertSee('Yeni kategori');

        $sayfa->set('onizleme.0.ekipman_tipi', 'Silindir (Toprak / Asfalt)')
            ->set('onizleme.0.kategori_adi', 'Ekipman – Silindir (yol)')
            ->call('kaydet')
            ->assertSet('onizleme', [])
            ->assertNotified();

        $kategori = TehlikeKategorisi::where('ad', 'Ekipman – Silindir (yol)')->sole();
        $this->assertSame(2, $kategori->tehlikeler()->count());
        $this->assertSame(['Silindir (Toprak / Asfalt)'], $kategori->ekipman_tipleri);
    }

    public function test_sayfa_tip_adini_birebir_eslesince_onerir_ve_hatali_dosyayi_bildirir(): void
    {
        $yol = $this->excel([$this->satir(1, 'Damper', 'Devrilme', ekipman: 'Damperli Kamyon — Genel')], 'Damperli_Kamyon.xlsx');
        $kitap = new Spreadsheet;
        $kitap->getActiveSheet()->fromArray([['Ad'], ['x']]);
        $bozuk = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid().'.xlsx';
        (new Xlsx($kitap))->save($bozuk);

        Livewire::test(EkipmanRiskYukle::class)
            ->set('dosyalar', [
                UploadedFile::fake()->createWithContent('Damperli_Kamyon.xlsx', file_get_contents($yol)),
                UploadedFile::fake()->createWithContent('liste.xlsx', file_get_contents($bozuk)),
            ])
            ->assertSet('onizleme.0.ekipman_tipi', 'Damperli Kamyon')
            ->assertCount('onizleme', 1)
            ->assertCount('hatalar', 1)
            ->assertSee('liste.xlsx');
    }

    public function test_risk_kutuphanesinde_yukleme_butonu_var(): void
    {
        $this->get(TehlikeResource::getUrl('index'))
            ->assertOk()->assertSee('Ekipman Risk Analizi Yükle');
        $this->get(EkipmanRiskYukle::getUrl())->assertOk();
    }
}
