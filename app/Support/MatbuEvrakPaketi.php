<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\KimyasalUrun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Matbu Evrak Paketi (22.11.2026) — firmaya eklenen ekipman ve kimyasalların takip edilmesi
 * gereken tüm evrakı tek birleşik PDF'te, yazdırılıp işverene teslim edilmek üzere:
 *
 *   Kapak + içindekiler + ortak teslim/tebliğ tutanağı (işveren imzası)
 *   Her ekipman: çalışma talimatı, aylık günlük kontrol çizelgesi (yatay), güvenlik afişi
 *   Her kimyasal: kullanım alanına asılan tek sayfalık MSDS özeti afişi
 *
 * Basılan kayıt "gönderildi" olur (ekipman teblig_cikti_at, kimyasal evrak_cikti_at); boş olanlar
 * "yeni eklenen, evrakı gönderilmemiş" sayılır ve "Yalnız yenileri" ile ayrıca basılabilir.
 */
class MatbuEvrakPaketi
{
    /** @return Collection<int, IsEkipmani> */
    public static function ekipmanlar(Firma $firma): Collection
    {
        return $firma->isEkipmanlari()->where('aktif', true)->orderBy('ekipman_adi')->get();
    }

    /** @return Collection<int, KimyasalUrun> */
    public static function kimyasallar(Firma $firma): Collection
    {
        return $firma->kimyasalUrunler()->where('aktif', true)->orderBy('urun_adi')->get();
    }

    /**
     * Ekipman başına hangi belgenin hazır olduğu (sayfa tablosu için).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function ekipmanDurumlari(Firma $firma): array
    {
        $hazirAfisler = EkipmanAfisKutuphanesi::hazirAfisler();

        return static::ekipmanlar($firma)->map(fn (IsEkipmani $e) => [
            'e' => $e,
            'talimat' => EkipmanKatalogu::firmaTalimati($e) !== null,
            'form' => EkipmanKatalogu::kontrolFormu($e->tip) !== null,
            'afis' => EkipmanAfisKutuphanesi::ekipmanAfisi($e, $hazirAfisler),
            'gonderildi' => $e->teblig_cikti_at,
        ])->all();
    }

    /**
     * @param  array<int, int>|null  $ekipmanIdleri  null: hepsi
     * @param  array<int, int>|null  $kimyasalIdleri  null: hepsi
     * @return array{pdf: string, ekipman: int, kimyasal: int, sayfa: int}|null basılacak belge yoksa null
     */
    public static function olustur(Firma $firma, ?array $ekipmanIdleri = null, ?array $kimyasalIdleri = null, ?Carbon $ay = null): ?array
    {
        // Kalabalık firmada (onlarca makine + kimyasal) yüzlerce sayfa basılır; paylaşımlı sunucunun 30 sn sınırına takılmasın.
        @set_time_limit(app()->runningInConsole() ? 0 : 300);

        $firma->loadMissing('igu', 'user');
        $ay ??= Carbon::today()->startOfMonth();

        $ekipmanlar = static::ekipmanlar($firma)->when($ekipmanIdleri !== null, fn ($c) => $c->whereIn('id', $ekipmanIdleri))->values();
        $kimyasallar = static::kimyasallar($firma)->when($kimyasalIdleri !== null, fn ($c) => $c->whereIn('id', $kimyasalIdleri))->values();

        // Bölümler: [konu, belge adı, PDF içeriği]; konu = ekipman / kimyasal adı (içindekilerde gruplanır).
        $bolumler = [];
        foreach ($ekipmanlar as $e) {
            foreach (EkipmanTebligPaketi::belgeler($e, $ay, false) as $dosya => $icerik) {
                $bolumler[] = ['grup' => 'Ekipman', 'konu' => $e->ekipman_adi, 'belge' => static::belgeAdi($dosya), 'pdf' => $icerik];
            }
        }
        foreach ($kimyasallar as $k) {
            if (! $k->ozetVarMi()) {
                continue; // özeti olmayan kimyasalın basılacak içeriği yok; sayfada "MSDS özeti eksik" uyarısı çıkar
            }
            // 22.11.2026 kullanıcı kararı: kimyasal başına tek belge — kullanım alanına asılan tek sayfalık MSDS özeti afişi.
            $bolumler[] = ['grup' => 'Kimyasal', 'konu' => $k->urun_adi, 'belge' => 'MSDS özeti afişi (kullanım alanına asılır)', 'pdf' => KimyasalOzetleri::afisPdf($k)];
        }

        if ($bolumler === []) {
            return null;
        }

        foreach ($bolumler as &$b) {
            $b['sayfa'] = static::sayfaSayisi($b['pdf']);
        }
        unset($b);

        $basilanKimyasal = $kimyasallar->filter->ozetVarMi()->values();

        // Kapak kendi sayfa sayısını bilmeden içindekiler numarası veremez: önce say, değiştiyse yeniden bas.
        $kapak = static::kapak($firma, $bolumler, 1, $ekipmanlar, $basilanKimyasal, $ay);
        $kapakSayfa = static::sayfaSayisi($kapak);
        if ($kapakSayfa !== 1) {
            $kapak = static::kapak($firma, $bolumler, $kapakSayfa, $ekipmanlar, $basilanKimyasal, $ay);
        }

        $birlesik = static::birlestir(array_merge([$kapak], array_column($bolumler, 'pdf')));

        $simdi = now();
        foreach ($ekipmanlar as $e) {
            $e->forceFill(['teblig_cikti_at' => $simdi])->saveQuietly();
        }
        foreach ($basilanKimyasal as $k) {
            $k->forceFill(['evrak_cikti_at' => $simdi])->saveQuietly();
        }

        return ['pdf' => $birlesik['pdf'], 'ekipman' => $ekipmanlar->count(), 'kimyasal' => $basilanKimyasal->count(), 'sayfa' => $birlesik['sayfa']];
    }

    public static function indir(Firma $firma, ?array $ekipmanIdleri = null, ?array $kimyasalIdleri = null): ?StreamedResponse
    {
        $sonuc = static::olustur($firma, $ekipmanIdleri, $kimyasalIdleri);
        if (! $sonuc) {
            return null;
        }

        $ad = 'matbu-evrak-'.Str::slug(Str::limit($firma->kisa_ad ?: $firma->unvan, 40, '')).'-'.now()->format('Y-m-d').'.pdf';

        return response()->streamDownload(fn () => print ($sonuc['pdf']), $ad, ['Content-Type' => 'application/pdf']);
    }

    /** @param  array<int, array<string, mixed>>  $bolumler */
    private static function kapak(Firma $firma, array $bolumler, int $kapakSayfa, Collection $ekipmanlar, Collection $kimyasallar, Carbon $ay): string
    {
        $sayfa = $kapakSayfa + 1;
        $icindekiler = [];
        foreach ($bolumler as $b) {
            $icindekiler[] = ['grup' => $b['grup'], 'konu' => $b['konu'], 'belge' => $b['belge'], 'sayfa' => $sayfa];
            $sayfa += $b['sayfa'];
        }

        return Pdf::loadView('pdf.matbu-evrak.kapak', [
            'firma' => $firma,
            'logo' => TalimatUretici::firmaLogosu($firma),
            'tebligEden' => TalimatUretici::tebligEden($firma),
            'icindekiler' => collect($icindekiler)->groupBy(fn ($s) => $s['grup'].'|'.$s['konu']),
            'ekipmanSayisi' => $ekipmanlar->count(),
            'kimyasalSayisi' => $kimyasallar->count(),
            'toplamSayfa' => $sayfa - 1,
            'ayAdi' => $ay->translatedFormat('F Y'),
            'tarih' => now()->format('d.m.Y'),
        ])->setPaper('a4')->output();
    }

    /** @param  array<int, string>  $pdfler  @return array{pdf: string, sayfa: int} */
    private static function birlestir(array $pdfler): array
    {
        // Paket sayfa numarası ("Sayfa X / Y") alt kenarın en dibine, belgelerin kendi alt bilgisinin altına basılır.
        $toplam = array_sum(array_map(static::sayfaSayisi(...), $pdfler));
        $numarali = new Fpdi;
        $numarali->SetAutoPageBreak(false);
        $numarali->SetFont('Helvetica', '', 7);
        $numarali->SetTextColor(110, 110, 110);
        $no = 0;
        foreach ($pdfler as $icerik) {
            $adet = $numarali->setSourceFile(StreamReader::createByString($icerik));
            for ($i = 1; $i <= $adet; $i++) {
                $tpl = $numarali->importPage($i);
                $boyut = $numarali->getTemplateSize($tpl);
                $numarali->AddPage($boyut['orientation'], [$boyut['width'], $boyut['height']]);
                $numarali->useTemplate($tpl);
                $no++;
                $numarali->SetXY(0, $boyut['height'] - 4.5);
                $numarali->Cell($boyut['width'] - 6, 3, 'Sayfa '.$no.' / '.$toplam, 0, 0, 'R');
            }
        }

        return ['pdf' => $numarali->Output('S'), 'sayfa' => $toplam];
    }

    private static function sayfaSayisi(string $pdf): int
    {
        return (new Fpdi)->setSourceFile(StreamReader::createByString($pdf));
    }

    private static function belgeAdi(string $dosya): string
    {
        return match (true) {
            str_contains($dosya, 'talimat') => 'Çalışma ve güvenlik talimatı',
            str_contains($dosya, 'kontrol-cizelgesi') => 'Aylık günlük kontrol çizelgesi',
            str_contains($dosya, 'afisi') => 'Ekipman güvenlik afişi',
            default => Str::headline(pathinfo($dosya, PATHINFO_FILENAME)),
        };
    }
}
