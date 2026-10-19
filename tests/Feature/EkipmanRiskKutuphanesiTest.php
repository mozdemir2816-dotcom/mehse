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
use App\Support\RiskKutuphanesi;
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
            ->set('naceGruplari', ['insaat'])
            ->call('kaydet')
            ->assertSet('onizleme', [])
            ->assertNotified();

        $kategori = TehlikeKategorisi::where('ad', 'Ekipman – Silindir (yol)')->sole();
        $this->assertSame(2, $kategori->tehlikeler()->count());
        $this->assertSame(['Silindir (Toprak / Asfalt)'], $kategori->ekipman_tipleri);
        $this->assertSame('fine_kinney', $kategori->yontem);
        $this->assertSame(['insaat'], $kategori->nace_gruplari);
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
            ->assertOk()->assertSee('Risk Analizi Yükle (Excel)');
        $this->get(EkipmanRiskYukle::getUrl())->assertOk()->assertSee('HAZOP')->assertSee('5x5 L Matris');
    }

    /** @param  array<int, array<int, mixed>>  $satirlar */
    private function matrisExcel(array $satirlar): string
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet()->setTitle('Risk Analizi');
        $s->fromArray(['No', 'Ekipman', 'Faaliyet', 'Tehlike', 'Risk / sonuç', 'Olasılık P', 'Şiddet S', 'Risk R', 'Açıklayıcı DÖF', 'Hedef P', 'Hedef S', 'Sorumlu', 'Önerilen termin'], null, 'A3');
        $s->fromArray($satirlar, null, 'A4');
        $yol = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid().'-matris.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_matris_5x5_kaynak_degerleri_5x5_degerlendirmeye_aynen_gelir(): void
    {
        $yol = $this->matrisExcel([[1, 'Silindir', 'Kenar', 'Devrilme', 'Ölüm', 3, 5, 15, 'Kenar işaretlenir.', 1, 5, 'Şef', 'Hemen']]);
        $maddeler = EkipmanRiskKutuphanesi::oku($yol, null, 'matris_5x5')[0]['maddeler'];
        $kategori = EkipmanRiskKutuphanesi::kaydet('Silindir 5x5', $maddeler)['kategori'];
        $t = Tehlike::where('tehlike_kategorisi_id', $kategori->id)->sole();

        // Kütüphane FK ölçeğinde (hedef O 0,2); 5x5'e geri dönüş 2 değil, kaynak 1 olmalı.
        $this->assertSame(0.2, (float) $t->son_olasilik);
        $m = RiskKutuphanesi::maddeyeCevir($t, 'matris_5x5');
        $this->assertSame([3, 5, 1, 5], array_map('intval', [$m['olasilik'], $m['siddet'], $m['son_olasilik'], $m['son_siddet']]));
        $this->assertNull($m['frekans']);

        // Kaynak 5x5 değeri olmayan (eski) maddede ölçek eşlemesine düşer.
        $t->update(['matris' => null]);
        $this->assertSame(2, (int) RiskKutuphanesi::maddeyeCevir($t->fresh(), 'matris_5x5')['son_olasilik']);

        // Fine-Kinney değerlendirmede dokunulmaz.
        $this->assertSame(30.0, RiskKutuphanesi::maddeyeCevir($t->fresh(), 'fine_kinney')['siddet']);
    }

    public function test_matris_5x5_puanlari_kutuphane_olcegine_cevrilir(): void
    {
        $yol = $this->matrisExcel([[1, 'Silindir', 'Kenar', 'Devrilme', 'Ölüm', 3, 5, 15, 'Kenar işaretlenir.', 1, 5, 'Şef', 'Hemen']]);
        $m = EkipmanRiskKutuphanesi::oku($yol, null, 'matris_5x5')[0]['maddeler'][0];

        // RiskYontemDonusturucu tablosu: O 3 → 3 (F 3), Ş 5 → 30; hedef O 1 → 0,2
        $this->assertSame([3.0, 3.0, 30.0], [$m['olasilik'], $m['frekans'], $m['siddet']]);
        $this->assertSame([0.2, 0.5, 30.0], [$m['son_olasilik'], $m['son_frekans'], $m['son_siddet']]);

        // Fine-Kinney seçilirse Frekans olmadığı için yöntem önerilir.
        $this->expectExceptionMessage('5x5 L Matris dosyasına benziyor');
        EkipmanRiskKutuphanesi::oku($yol, null, 'fine_kinney');
    }

    public function test_matris_5x5_secilip_fine_kinney_puani_gelirse_hata(): void
    {
        $this->expectExceptionMessage('1-5 arasında olmalı');
        EkipmanRiskKutuphanesi::oku($this->excel([$this->satir(1, 'Kenar', 'Devrilme')]), null, 'matris_5x5');
    }

    public function test_matris_5x5_olcekli_basliklar_ve_ikinci_risk_blogu_taninir(): void
    {
        // Firma risk analizi düzeni: "O (1–5)" / "Ş (1–5)" başlıkları, ikinci blok önlem sonrası risk.
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet()->setTitle('Sayfa1');
        $s->fromArray(['NO', 'BÖLÜM/ EKİPMAN', 'TEHLİKE KAYNAĞI', 'RİSK/SONUÇ', 'ETKİLENEN', 'O (1–5)', 'Ş (1–5)', '—', 'RİSK PUANI', 'RİSK DÜZEYİ', 'AÇIKLAMA', 'ALINACAK ÖNLEMLER', 'O (1–5)', 'Ş (1–5)', '—', 'RİSK PUANI', 'RİSK DÜZEYİ', 'SORUMLU', 'TERMİN'], null, 'A4');
        $s->fromArray([1, 'Temel İSG Eğitimi', 'Eğitim verilmemesi', 'Bilinmeyen tehlike', 'Tüm çalışanlar', 3, 5, '—', '=F5*G5', '', 'Açıklama', 'Eğitim verilir', 1, 5, '—', '=M5*N5', '', 'İşveren', 'Sürekli'], null, 'A5');
        $yol = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid().'-olcekli.xlsx';
        (new Xlsx($kitap))->save($yol);

        $m = EkipmanRiskKutuphanesi::oku($yol, null, 'matris_5x5')[0]['maddeler'][0];

        $this->assertSame([3.0, 3.0, 30.0], [$m['olasilik'], $m['frekans'], $m['siddet']]);
        $this->assertSame([0.2, 0.5, 30.0], [$m['son_olasilik'], $m['son_frekans'], $m['son_siddet']]);
    }

    private function hazopExcel(): string
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet()->setTitle('HAZOP');
        $s->fromArray(['BOYA ÜRETİM TESİSİ HAZOP ÇALIŞMASI'], null, 'A1');
        $s->fromArray(['Senaryo ID', 'Düğüm / ekipman', 'Parametre', 'Kılavuz sözcük', 'Sapma', 'Olası nedenler', 'Olası sonuçlar', 'Mevcut koruma durumu', 'Sahada araştırılacak korumalar', 'Önerilen aksiyon', 'Öncelik', 'Aksiyon ID', 'Sorumlu', 'Önerilen termin', 'Durum', 'Kapanış / doğrulama kanıtı', 'Kontrol tarihi', 'Fine–Kinney referansı'], null, 'A7');
        $s->fromArray([
            ['HZ-001', 'N1 – Dispersiyoner', 'Devir', 'Fazla', 'Devrin güvenli sınırı aşması', 'Yanlış sürücü ayarı', 'Disk hasarı, parça fırlaması', 'Doğrulanmadı', 'Devir sınırı, kap kilidi', 'Azami devri doğrulayın.', 'A – İşletme öncesi', 'AKS-001', 'Proses', 'İşletme öncesi', 'Açık', '', '', 'FK-001'],
            ['HZ-002', 'N1 – Dispersiyoner', 'Karıştırma', 'Yok', 'Karıştırmanın durması', 'Motor arızası', 'Topaklanma', 'Doğrulanmadı', '', 'Şarj kilidi kurun.', 'B – Planlı', 'AKS-002', 'Bakım', '3 ay', 'Açık', '', '', 'FK-002'],
            ['HZ-003', 'N2 – Basket Mill', 'Seviye', 'Az', 'Sepetin yetersiz daldırılması', 'Eksik dolum', 'Sıçrama', 'Doğrulanmadı', '', 'Seviye kilidi.', 'A – İşletme öncesi', 'AKS-003', 'Proses', 'İşletme öncesi', 'Açık', '', '', 'FK-011'],
        ], null, 'A8');
        $kitap->createSheet()->setTitle('Düğümler');
        $yol = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid().'-Boya_HAZOP.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_hazop_dugum_basina_kategori_puansiz_yuklenir(): void
    {
        $gruplar = EkipmanRiskKutuphanesi::oku($this->hazopExcel(), null, 'hazop');

        $this->assertSame(['HAZOP – Dispersiyoner', 'HAZOP – Basket mill'], array_column($gruplar, 'kategori_adi'));
        $m = $gruplar[0]['maddeler'][0];
        $this->assertSame('N1 – Dispersiyoner', $m['bolum']);
        $this->assertSame('Devir / Fazla', $m['faaliyet']);
        $this->assertSame('Devrin güvenli sınırı aşması', $m['tehlike']);
        $this->assertSame("Neden: Yanlış sürücü ayarı\nSonuç: Disk hasarı, parça fırlaması", $m['risk']);
        $this->assertSame("[A – İşletme öncesi] Azami devri doğrulayın.\nSahada doğrulanacak korumalar: Devir sınırı, kap kilidi", $m['oneri']);
        $this->assertNull($m['olasilik']);
        $this->assertNull($m['son_siddet']);

        $sonuc = EkipmanRiskKutuphanesi::kaydet($gruplar[0]['kategori_adi'], $gruplar[0]['maddeler']);
        $this->assertSame(2, $sonuc['eklenen']);
        $this->assertSame(['DIS-R-001', 'DIS-R-002'], $sonuc['kategori']->tehlikeler()->orderBy('kod')->pluck('kod')->all());
        $this->assertNull(Tehlike::where('kod', 'DIS-R-001')->value('olasilik'));
    }

    public function test_hazop_yeni_duzen_dugum_adi_ve_alanlar_okunur(): void
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet()->setTitle('HAZOP');
        $s->fromArray(['HAZIR SIVA ÜRETİM TESİSİ — HAZOP ÖN ÇALIŞMASI'], null, 'A2');
        $s->fromArray(['No', 'Düğüm', 'Parametre', 'Kılavuz kelime', 'Sapma', 'Olası nedenler', 'Olası sonuçlar', 'Doğrulanacak korumalar', 'Öneri / aksiyon', 'Öncelik', 'Sorumlu rol', 'Hedef tarih', 'Kapanış kanıtı', 'Durum'], null, 'A6');
        $s->fromArray([
            [1, 'N1 — Silo dolumu', 'Basınç', 'FAZLA', 'Silo basıncı tasarım sınırını aşar', 'Filtre tıkanması', 'Gövde hasarı, ölüm', 'Basınç alarmı, tahliye valfi', 'Tahliye kapasitesini doğrulayın.', 'P1', 'Üretim / bakım', 'Atanacak', '', 'Açık'],
        ], null, 'A7');
        $yol = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid().'-Siva_HAZOP.xlsx';
        (new Xlsx($kitap))->save($yol);

        $gruplar = EkipmanRiskKutuphanesi::oku($yol, null, 'hazop');

        $this->assertSame(['HAZOP – Silo dolumu'], array_column($gruplar, 'kategori_adi'));
        $m = $gruplar[0]['maddeler'][0];
        $this->assertSame('N1 — Silo dolumu', $m['bolum']);
        $this->assertSame('Basınç / FAZLA', $m['faaliyet']);
        $this->assertSame('Silo basıncı tasarım sınırını aşar', $m['tehlike']);
        $this->assertSame("Neden: Filtre tıkanması\nSonuç: Gövde hasarı, ölüm", $m['risk']);
        $this->assertSame("[P1] Tahliye kapasitesini doğrulayın.\nSahada doğrulanacak korumalar: Basınç alarmı, tahliye valfi", $m['oneri']);
        $this->assertSame('Üretim / bakım', $m['sorumlu']);
        $this->assertSame('Atanacak', $m['termin']);
        $this->assertNull($m['olasilik']);
        $this->assertSame(['dugum' => 'N1 — Silo dolumu', 'parametre' => 'Basınç', 'kilavuz' => 'FAZLA'], array_intersect_key($m['hazop'], array_flip(['dugum', 'parametre', 'kilavuz'])));
        $this->assertSame('Açık', $m['hazop']['durum']);
    }

    public function test_hazop_firma_duzeni_basliklari_taninir(): void
    {
        // 18.10 kaynak HAZOP dosyaları: "Düğüm / Süreç", "Parametre / İşlem", "Önerilen Önlem / Faaliyet" başlıkları.
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet()->setTitle('HAZOP');
        $s->fromArray(['HAZOP / Kaynak No', 'Düğüm / Süreç', 'Tasarım / İşletme Amacı', 'Parametre / İşlem', 'Kılavuz Kelime', 'Sapma', 'Olası Nedenler', 'Olası Sonuç / Etkilenen', 'Mevcut Koruma / Kanıt Durumu', 'Önerilen Önlem / Faaliyet', 'Kapanış İçin Doğrulama', 'Önerilen Sorumlu', 'Hedef / Termin Kuralı', 'Termin Tarihi', 'Faaliyet Durumu', 'Kapanış Kanıtı / Tarihi'], null, 'A6');
        $s->fromArray([
            ['1', 'Acil durum ekipleri', 'Ekip', 'Ekip organizasyonu', 'YOK', 'Ekiplerin oluşturulmaması', 'Görevlendirme yok', 'Tahliye yapılamaz', 'Ekip listesi', 'Ekip kur.', 'Liste', 'İşveren', 'Sürekli', 'Atanacak', 'Açık', ''],
        ], null, 'A7');
        $yol = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid().'-firma-hazop.xlsx';
        (new Xlsx($kitap))->save($yol);

        $gruplar = EkipmanRiskKutuphanesi::oku($yol, null, 'hazop');

        $this->assertSame('HAZOP – Acil durum ekipleri', $gruplar[0]['kategori_adi']);
        $m = $gruplar[0]['maddeler'][0];
        $this->assertSame('Ekiplerin oluşturulmaması', $m['tehlike']);
        $this->assertSame('Ekip organizasyonu / YOK', $m['faaliyet']);
        $this->assertSame('İşveren', $m['sorumlu']);
        $this->assertSame('Atanacak', $m['termin']);
        $this->assertStringContainsString('Tahliye yapılamaz', $m['risk']);
        $this->assertStringContainsString('Ekip kur.', $m['oneri']);
        $this->assertStringContainsString('Ekip listesi', $m['oneri']);
    }

    public function test_sayfada_hazop_secilince_hazop_okunur(): void
    {
        Livewire::test(EkipmanRiskYukle::class)
            ->set('yontem', 'hazop')
            ->set('dosyalar', [UploadedFile::fake()->createWithContent('Boya_HAZOP.xlsx', file_get_contents($this->hazopExcel()))])
            ->assertCount('onizleme', 2)
            ->assertSet('onizleme.0.kategori_adi', 'HAZOP – Dispersiyoner')
            ->assertSet('onizleme.0.yontem', 'HAZOP')
            ->assertSet('hatalar', [])
            ->set('naceGruplari', ['ortak'])
            ->call('kaydet');

        $this->assertSame(3, Tehlike::whereHas('kategori', fn ($q) => $q->where('ad', 'like', 'HAZOP – %'))->count());
        $this->assertSame('hazop', TehlikeKategorisi::where('ad', 'like', 'HAZOP – %')->first()->yontem);
    }
}
