<?php

namespace App\Filament\Pages;

use App\Models\MykMeslek;
use App\Models\NaceKodu;
use App\Support\GeminiMevzuatDanismani;
use App\Support\SgkSicilCozumleyici;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Araçlar — bağımsız, kalıcı veri tutmayan İSG hesaplayıcıları.
 * isgpratik'te tam karşılığı görülmedi; standart İSG mevzuatı
 * formülleriyle (kaza sıklık/ağırlık hızı, gürültü Lex,8h) kuruldu.
 */
class Araclar extends Page
{
    use \App\Filament\Concerns\AileAnaSayfasi;

    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.araclar';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench';

    protected static string|UnitEnum|null $navigationGroup = 'Hesap & Arşiv';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'araclar';

    protected static ?string $title = 'Araçlar';

    protected static ?string $navigationLabel = 'Araçlar';

    // --- Kaza Sıklık Hızı / Ağırlık Hızı ---
    public ?int $kazaSayisi = null;

    public ?int $kayipGunSayisi = null;

    public ?float $toplamCalismaSaati = null;

    public ?int $kazaCalisanSayisi = null;

    public int $kazaDonemAy = 12;

    public function calisanSayisindanSaatHesapla(): void
    {
        if (! $this->kazaCalisanSayisi || $this->kazaCalisanSayisi <= 0) {
            return;
        }

        // Standart İSG hesabı: 1 çalışan = ayda 225 çalışma saati (günde 7.5 saat × 30 gün)
        $this->toplamCalismaSaati = (float) ($this->kazaCalisanSayisi * 225 * max(1, $this->kazaDonemAy));
    }

    public function kazaOrnekVeriDoldur(): void
    {
        $this->kazaSayisi = 3;
        $this->kayipGunSayisi = 45;
        $this->kazaCalisanSayisi = 100;
        $this->kazaDonemAy = 12;
        $this->toplamCalismaSaati = 270000;
    }

    // --- Gürültü Maruziyet Düzeyi (Lex,8h) ---
    /** @var array<int, array{db: ?float, saat: ?float}> */
    public array $gurultuOlcumleri = [
        ['db' => null, 'saat' => null],
    ];

    public function sikikHizi(): ?float
    {
        if (! $this->kazaSayisi || ! $this->toplamCalismaSaati) {
            return null;
        }

        return round(($this->kazaSayisi * 1_000_000) / $this->toplamCalismaSaati, 2);
    }

    public function agirlikHizi(): ?float
    {
        if (! $this->kayipGunSayisi || ! $this->toplamCalismaSaati) {
            return null;
        }

        return round(($this->kayipGunSayisi * 1_000) / $this->toplamCalismaSaati, 2);
    }

    public function gurultuOlcumEkle(): void
    {
        $this->gurultuOlcumleri[] = ['db' => null, 'saat' => null];
    }

    public function gurultuOlcumSil(int $index): void
    {
        unset($this->gurultuOlcumleri[$index]);
        $this->gurultuOlcumleri = array_values($this->gurultuOlcumleri);
    }

    public function lex8h(): ?float
    {
        $gecerliSatirlar = collect($this->gurultuOlcumleri)
            ->filter(fn (array $s): bool => is_numeric($s['db'] ?? null) && is_numeric($s['saat'] ?? null) && $s['saat'] > 0);

        if ($gecerliSatirlar->isEmpty()) {
            return null;
        }

        $toplamDoz = $gecerliSatirlar->sum(fn (array $s): float => (float) $s['saat'] * (10 ** ((float) $s['db'] / 10)));

        return round(10 * log10($toplamDoz / 8), 1);
    }

    /** @return array{esik: int, etiket: string, renk: string, aciklama: string}|null */
    public function gurultuSeviyesi(): ?array
    {
        $deger = $this->lex8h();

        if ($deger === null) {
            return null;
        }

        foreach (config('isg.araclar.gurultu_sinirlari') as $sinir) {
            if ($deger >= $sinir['esik']) {
                return $sinir;
            }
        }

        return null;
    }

    public function kazaHesaplayiciSifirla(): void
    {
        $this->reset(['kazaSayisi', 'kayipGunSayisi', 'toplamCalismaSaati', 'kazaCalisanSayisi']);
        $this->kazaDonemAy = 12;
    }

    public function gurultuHesaplayiciSifirla(): void
    {
        $this->gurultuOlcumleri = [['db' => null, 'saat' => null]];
    }

    // --- NACE Kod / SGK Sicil No / Faaliyet Adı → Tehlike Sınıfı Sorgula ---
    public ?string $naceKoduGirdi = null;

    public ?string $naceHata = null;

    public ?NaceKodu $naceSonuc = null;

    public ?string $sgkSicilGirdi = null;

    /** @var array<string, mixed>|null */
    public ?array $sgkCozumSonucu = null;

    /** @var Collection<int, NaceKodu> */
    public Collection $sgkNaceAdaylari;

    public ?string $faaliyetAramaGirdi = null;

    /** @var Collection<int, NaceKodu> */
    public Collection $faaliyetSonuclari;

    public function naceSorgula(): void
    {
        $this->naceHata = null;
        $this->naceSonuc = null;

        $girdi = trim((string) $this->naceKoduGirdi);

        if ($girdi === '') {
            return;
        }

        if (NaceKodu::normalizeKod($girdi) === null) {
            $this->naceHata = '6 haneli bir NACE kodu girin (ör. 01.11.14 veya 011114).';

            return;
        }

        $sonuc = NaceKodu::bul($girdi);

        if (! $sonuc) {
            $this->naceHata = 'Bu kod EK-1 listesinde bulunamadı — 6 haneli en alt kırılım (faaliyet) kodu olduğundan emin olun.';

            return;
        }

        $this->naceSonuc = $sonuc;
    }

    public function sgkSicilCozumle(): void
    {
        $this->sgkCozumSonucu = SgkSicilCozumleyici::cozumle((string) $this->sgkSicilGirdi);
        $this->sgkNaceAdaylari = new Collection;

        if ($this->sgkCozumSonucu['gecerli'] && $this->sgkCozumSonucu['nace4']) {
            $this->sgkNaceAdaylari = NaceKodu::nace4IleBul($this->sgkCozumSonucu['nace4']);
            if ($this->sgkNaceAdaylari->count() === 1) {
                $this->naceSonuc = $this->sgkNaceAdaylari->first();
                $this->naceKoduGirdi = $this->naceSonuc->kod;
            }
        }
    }

    public function ornekSgkSicilDene(): void
    {
        $this->sgkSicilGirdi = SgkSicilCozumleyici::ORNEK_SICIL_NO;
        $this->sgkSicilCozumle();
    }

    public function faaliyetIleNaceAra(): void
    {
        $this->faaliyetSonuclari = NaceKodu::faaliyetAra((string) $this->faaliyetAramaGirdi);
    }

    public function naceKodSec(string $kod): void
    {
        $this->naceKoduGirdi = $kod;
        $this->naceSorgula();
    }

    public function naceSifirla(): void
    {
        $this->reset(['naceKoduGirdi', 'naceHata', 'naceSonuc', 'sgkSicilGirdi', 'sgkCozumSonucu', 'faaliyetAramaGirdi']);
        $this->sgkNaceAdaylari = new Collection;
        $this->faaliyetSonuclari = new Collection;
    }

    // --- MYK Zorunluluk Sorgula ---
    public ?string $mykAramaTerimi = null;

    public bool $mykArandi = false;

    /** @var Collection<int, MykMeslek> */
    public Collection $mykSonuclar;

    public function mount(): void
    {
        $this->mykSonuclar = new Collection;
        $this->sgkNaceAdaylari = new Collection;
        $this->faaliyetSonuclari = new Collection;
    }

    public function mykAra(): void
    {
        $this->mykArandi = true;
        $this->mykSonuclar = MykMeslek::ara((string) $this->mykAramaTerimi);
    }

    public function mykSifirla(): void
    {
        $this->reset(['mykAramaTerimi', 'mykArandi']);
        $this->mykSonuclar = new Collection;
    }

    // --- Mevzuata Sor (İSG Mevzuat Danışmanı) ---
    public string $mevzuatSoru = '';

    public ?string $danismanYaniti = null;

    /** @var array<int, array{baslik: string, kategori: string, aciklama: string}> */
    public array $danismanKaynaklari = [];

    public function mevzuataSor(): void
    {
        if (blank(trim($this->mevzuatSoru))) {
            return;
        }

        $sonuc = GeminiMevzuatDanismani::sor($this->mevzuatSoru);
        $this->danismanYaniti = $sonuc['yanit'];
        $this->danismanKaynaklari = $sonuc['kaynaklar'];
    }

    public function hazirSoruSor(string $anahtar): void
    {
        $hazir = GeminiMevzuatDanismani::HAZIR_SORULAR[$anahtar] ?? null;
        if (! $hazir) {
            return;
        }

        $this->mevzuatSoru = $hazir['soru'];
        $this->mevzuataSor();
    }

    public function danismanTemizle(): void
    {
        $this->mevzuatSoru = '';
        $this->danismanYaniti = null;
        $this->danismanKaynaklari = [];
    }
}
