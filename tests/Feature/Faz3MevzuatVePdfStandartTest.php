<?php

namespace Tests\Feature;

use App\Filament\Pages\IsIzinFormu as IsIzinFormuSayfasi;
use App\Models\AcilEkip;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\IsgProfesyoneli;
use App\Models\IsIzinFormu;
use App\Models\KkdZimmetFormu;
use App\Models\KurulToplantisi;
use App\Models\MeslekHastaligiBildirimi;
use App\Models\OrtamOlcumu;
use App\Models\User;
use App\Support\AcilEkipDurumu;
use App\Support\EkipmanKatalogu;
use App\Support\SgkBildirimSayaci;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class Faz3MevzuatVePdfStandartTest extends TestCase
{
    use RefreshDatabase;

    public function test_ek3_2022_tezgah_ve_endustriyel_raf_periyodik_kontrole_tabi_merdiven_muaf(): void
    {
        $this->assertTrue(EkipmanKatalogu::periyodikMi('uretim_atolye', 'CNC Tezgâhı'));
        $this->assertTrue(EkipmanKatalogu::periyodikMi('diger_ekipman', 'Endüstriyel Raf'));
        $this->assertFalse(EkipmanKatalogu::periyodikMi('diger_ekipman', 'Seyyar / Çok Amaçlı Merdiven'));
    }

    public function test_acil_durum_yonetmeligi_md11_4_ondan_az_calisan_tek_destek_elemani_yeterli(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Küçük Ofis Ltd.', 'tehlike_sinifi' => 'az_tehlikeli', 'calisan_sayisi' => 6, 'aktif' => true]);

        AcilEkipDurumu::temelEkipleriOlustur($f);
        $sondurme = AcilEkip::where('firma_id', $f->id)->where('tur', 'sondurme')->firstOrFail();
        $sondurme->uyeler()->create(['ad_soyad' => 'Ali Destek', 'uyelik' => 'asil', 'lider' => true, 'belge_tarihi' => now()->subMonths(2)]);

        $ozet = AcilEkipDurumu::ozet($f);
        $kurtarma = AcilEkip::where('firma_id', $f->id)->where('tur', 'kurtarma')->firstOrFail();
        $koruma = AcilEkip::where('firma_id', $f->id)->where('tur', 'koruma')->firstOrFail();

        $this->assertSame('tam', $ozet['durumlar'][$kurtarma->id]['durum']);
        $this->assertSame('tam', $ozet['durumlar'][$koruma->id]['durum']);
    }

    public function test_sgk_3_is_gunu_resmi_tatili_atlar_ve_meslek_hastaligi_son_gun_icinde_gecmis_sayilmaz(): void
    {
        // 21 Nisan 2026 Salı + 3 iş günü: 22 Çar (1), 23 Perşembe RESMİ TATİL (atlanır), 24 Cum (2), 27 Pzt (3)
        $son = SgkBildirimSayaci::isGunuEkle(Carbon::parse('2026-04-21'), 3);
        $this->assertSame('2026-04-27', $son->toDateString());

        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Tatilli A.Ş.', 'tehlike_sinifi' => 'tehlikeli', 'aktif' => true]);

        $mh = MeslekHastaligiBildirimi::create([
            'firma_id' => $f->id,
            'calisan_ad_soyad' => 'Test Çalışan',
            'ogrenme_kaynagi' => 'isyeri_hekimi',
            'ogrenme_tarihi' => '2026-04-21',
            'sgk_bildirimi_yapildi' => false,
        ]);

        $this->assertSame('2026-04-27', $mh->bildirim_son_tarihi->toDateString());

        // Son günün öğlen saatinde (12:00) süre henüz geçmemiştir
        Carbon::setTestNow(Carbon::parse('2026-04-27 12:00:00'));
        $this->assertFalse($mh->fresh()->suresiGectiMi());

        // Ertesi gün 00:01'de süre geçmiştir
        Carbon::setTestNow(Carbon::parse('2026-04-28 00:01:00'));
        $this->assertTrue($mh->fresh()->suresiGectiMi());
        Carbon::setTestNow();
    }

    public function test_ortam_olcumu_gurultu_db_logaritmik_esikleriyle_degerlendirilir(): void
    {
        // 72 dB(A) gürültü için uygun olmalı (lineer %80 = 68 dB(A) tuzağına düşmemeli)
        $this->assertSame('uygun', OrtamOlcumu::otomatikSonuc('72', '85', 'dB(A)', 'Gürültü Maruziyeti'));
        $this->assertSame('sinir', OrtamOlcumu::otomatikSonuc('82', '85', 'dB(A)', 'Gürültü Maruziyeti'));
        $this->assertSame('asim', OrtamOlcumu::otomatikSonuc('88', '85', 'dB(A)', 'Gürültü Maruziyeti'));
    }

    public function test_ptw_suresi_dolmus_izni_onaylamaz_ve_taslak_izni_kapatmaz(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'PTW A.Ş.', 'tehlike_sinifi' => 'cok_tehlikeli', 'aktif' => true]);

        $suresiDolmus = IsIzinFormu::create([
            'firma_id' => $f->id,
            'calisma_alani' => 'Kazan',
            'durum' => 'onay_bekliyor',
            'baslangic' => now()->subDays(2),
            'bitis' => now()->subDay(),
            'onay1_durum' => 'bekliyor',
            'onay2_durum' => 'bekliyor',
        ]);

        Livewire::test(IsIzinFormuSayfasi::class)
            ->set('firmaId', $f->id)
            ->call('onayla', $suresiDolmus->id, 1);

        $this->assertSame('bekliyor', $suresiDolmus->fresh()->onay1_durum);

        $taslak = IsIzinFormu::create([
            'firma_id' => $f->id,
            'calisma_alani' => 'Çatı',
            'durum' => 'taslak',
        ]);

        Livewire::test(IsIzinFormuSayfasi::class)
            ->set('firmaId', $f->id)
            ->call('islemBaslat', $taslak->id, 'kapat')
            ->call('islemiUygula');

        $this->assertSame('taslak', $taslak->fresh()->durum);
    }

    public function test_resmi_pdf_sablonlari_mevzuat_ve_gercek_isim_alanlarini_basar(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $igu = IsgProfesyoneli::create(['user_id' => $u->id, 'ad_soyad' => 'Ayşe Uzman', 'tip' => 'igu', 'sertifika_no' => 'IGU-12345']);
        $f = Firma::create([
            'user_id' => $u->id,
            'unvan' => 'Standart A.Ş.',
            'tehlike_sinifi' => 'tehlikeli',
            'igu_id' => $igu->id,
            'isveren_ad' => 'Mehmet Patron',
            'aktif' => true,
        ]);

        // 1. Ekipman pasaportu: kategoriAdi() ve muayene_yapan basılır
        $ek = IsEkipmani::create([
            'firma_id' => $f->id,
            'kategori' => 'kaldirma_iletme',
            'ekipman_adi' => 'Tavan Vinci 5T',
            'muayene_yapan' => 'A Tipi Muayene A.Ş.',
        ]);
        $pasaportHtml = view('pdf.ekipman-emniyet-pasaportu', [
            'firma' => $f, 'ekipman' => $ek, 'ekipnetNo' => null, 'tarih' => '24.11.2026', 'qrUri' => '', 'operatorUrl' => 'https://mehse.com',
        ])->render();
        $this->assertStringContainsString('Kaldırma &amp; İletme Araçları', $pasaportHtml);
        $this->assertStringContainsString('A Tipi Muayene A.Ş.', $pasaportHtml);

        // 2. KKD zimmet formu: 6331 m.4/3'e aykırı sorumluluk devri metni yok, 6331 m.19 ve ücretsiz temin metni var
        $kkd = KkdZimmetFormu::create([
            'firma_id' => $f->id,
            'teslim_tarihi' => now(),
            'calisanlar' => [['ad_soyad' => 'Ahmet İşçi', 'departman' => 'Montaj']],
            'kkdler' => [['ad' => 'Baret', 'standart' => 'EN 397', 'donem' => '24 Ay', 'miktar' => 1]],
        ]);
        $kkdHtml = view('pdf.kkd-zimmet-formu', ['firma' => $f, 'form' => $kkd])->render();
        $this->assertStringNotContainsString('her türlü sorumluluğu kabul ederim', $kkdHtml);
        $this->assertStringContainsString('6331 sayılı İş Sağlığı ve Güvenliği Kanunu m.4', $kkdHtml);

        // 3. Kurul toplantısı: gerçek başkan adını basar
        $kurul = KurulToplantisi::create([
            'firma_id' => $f->id,
            'baskan' => 'Ali Kurul Başkanı',
            'tarih' => now(),
        ]);
        $kurulHtml = view('pdf.kurul-toplantisi', ['firma' => $f, 'toplanti' => $kurul])->render();
        $this->assertStringContainsString('Ali Kurul Başkanı', $kurulHtml);
    }
}
