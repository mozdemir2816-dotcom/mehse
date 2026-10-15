<?php

namespace Tests\Feature;

use App\Filament\Isyeri\Pages\IsyeriEvraklari;
use App\Filament\Pages\OnBilgilendirmeler;
use App\Models\Firma;
use App\Models\IsyeriHesabi;
use App\Models\OnBilgilendirme;
use App\Models\SahaBulgusu;
use App\Models\TespitOneriDefteri;
use App\Models\User;
use App\Support\OnBilgilendirmeUretici;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory as WordIO;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class OnBilgilendirmeTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->uzman = User::factory()->create();
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Yakup Meral']);
        $this->actingAs($this->uzman);
    }

    private function word(): UploadedFile
    {
        $word = new PhpWord;
        $word->addTitleStyle(1, ['bold' => true]);
        $b = $word->addSection();
        $b->addText('İskele ve Elektrik Hatları Güvenliği');
        $b->addText('İşyeri: Yakup Meral   Tarih: 14.10.2026');
        $b->addText('İskelenin yakınında elektrik hatları bulunduğu bildirilmiştir.');
        $b->addTitle('1 Elektrik hatları yakınında alınacak önlemler', 1);
        $b->addText('Hattın enerjisi kesilmelidir.');
        $b->addText('Bilgilerinize sunarım.');
        $yol = tempnam(sys_get_temp_dir(), 'onb').'.docx';
        WordIO::createWriter($word, 'Word2007')->save($yol);

        return UploadedFile::fake()->createWithContent('iskele ve elektrik hattı.docx', file_get_contents($yol));
    }

    private function yukle(array $ek = []): OnBilgilendirme
    {
        Livewire::test(OnBilgilendirmeler::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('yukle', data: ['dosya' => $this->word(), ...$ek])
            ->assertHasNoActionErrors();

        return OnBilgilendirme::firstOrFail();
    }

    public function test_word_yazisi_saklanir_konu_tarih_okunur_deftere_on_bilgilendirme_duser(): void
    {
        $b = $this->yukle();

        $this->assertSame('İskele ve Elektrik Hatları Güvenliği', $b->konu);
        $this->assertSame('2026-10-14', $b->bildirim_tarihi->toDateString());
        $this->assertSame('iskele ve elektrik hattı.docx', $b->dosya_adi);
        $this->assertNotNull($b->isyerine_gonderildi_at);
        Storage::disk('local')->assertExists($b->dosya_yolu);

        $madde = TespitOneriDefteri::where('firma_id', $this->firma->id)->firstOrFail()->maddeler[0];
        $this->assertSame('bilgilendirme', $madde['tur']);
        $this->assertSame($b->id, $madde['on_bilgilendirme_id']);
        $this->assertStringContainsString('• Hattın enerjisi kesilmelidir.', $madde['oneri']);
        $this->assertSame(0, SahaBulgusu::count());
    }

    public function test_panele_gonderilmezse_ve_deftere_eklenmezse_yalniz_kayit_olusur(): void
    {
        $b = $this->yukle(['panele_gonder' => false, 'deftere_ekle' => false, 'konu' => 'Elle konu']);

        $this->assertSame('Elle konu', $b->konu);
        $this->assertNull($b->isyerine_gonderildi_at);
        $this->assertSame('teblig_bekliyor', $b->durum());
        $this->assertNull(TespitOneriDefteri::where('firma_id', $this->firma->id)->first());
    }

    public function test_isveren_panelde_gorur_indirir_ve_tebellug_eder(): void
    {
        $b = $this->yukle();
        $gizli = OnBilgilendirme::create(['user_id' => $this->uzman->id, 'firma_id' => $this->firma->id, 'konu' => 'Gönderilmemiş',
            'dosya_adi' => 'x.pdf', 'dosya_yolu' => 'x.pdf']);

        $this->actingAs(IsyeriHesabi::firmaIcin($this->firma), 'isyeri');
        Filament::setCurrentPanel(Filament::getPanel('isyeri'));

        $sayfa = Livewire::test(IsyeriEvraklari::class)
            ->assertSee('İskele ve Elektrik Hatları Güvenliği')
            ->assertDontSee('Gönderilmemiş')
            ->assertSee('Okudum, tebellüğ ettim');

        $sayfa->call('bilgilendirmeIndir', $b->id)->assertFileDownloaded('iskele ve elektrik hattı.docx');

        $sayfa->callAction('tebellug', data: ['ad' => 'Yakup Meral — İşveren'], arguments: ['id' => $b->id])
            ->assertHasNoActionErrors();

        $b->refresh();
        $this->assertNotNull($b->isyeri_okundu_at);
        $this->assertSame('Yakup Meral — İşveren', $b->isyeri_onaylayan);
        $this->assertSame('teblig_edildi', $b->durum());

        // Gönderilmemiş yazı panelden onaylanamaz.
        $sayfa->callAction('tebellug', data: ['ad' => 'X'], arguments: ['id' => $gizli->id]);
        $this->assertNull($gizli->fresh()->isyeri_okundu_at);
    }

    public function test_baska_firmanin_hesabi_yaziyi_goremez(): void
    {
        $b = $this->yukle();
        $diger = Firma::factory()->for($this->uzman)->create();

        $this->actingAs(IsyeriHesabi::firmaIcin($diger), 'isyeri');
        Filament::setCurrentPanel(Filament::getPanel('isyeri'));

        Livewire::test(IsyeriEvraklari::class)
            ->assertDontSee('İskele ve Elektrik Hatları Güvenliği')
            ->call('bilgilendirmeIndir', $b->id)
            ->assertNoFileDownloaded();
    }

    public function test_elden_teslim_tutanak_ve_silme(): void
    {
        $b = $this->yukle();

        $sayfa = Livewire::test(OnBilgilendirmeler::class)
            ->set('firmaId', $this->firma->id)
            ->callAction('eldenTeslim', data: ['elden_teslim_alan' => 'Şantiye Şefi', 'elden_teslim_tarihi' => '2026-10-15'], arguments: ['id' => $b->id])
            ->assertHasNoActionErrors();

        $this->assertSame('Şantiye Şefi', $b->fresh()->elden_teslim_alan);

        $html = view('pdf.on-bilgilendirme-tutanagi', ['b' => $b->fresh(), 'firma' => $this->firma])->render();
        $this->assertStringContainsString('TEBLİĞ - TEBELLÜĞ TUTANAĞI', $html);
        $this->assertStringContainsString('15.10.2026 tarihinde elden teslim edildi (Şantiye Şefi)', $html);
        $this->assertInstanceOf(StreamedResponse::class, OnBilgilendirmeUretici::tutanak($b->fresh()));

        $sayfa->call('sil', $b->id);

        $this->assertSame(0, OnBilgilendirme::count());
        Storage::disk('local')->assertMissing($b->dosya_yolu);
        $this->assertSame([], TespitOneriDefteri::where('firma_id', $this->firma->id)->firstOrFail()->maddeler);
    }
}
