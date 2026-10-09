<?php

namespace App\Support;

use App\Models\KurulToplantisi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory as WordIO;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İSG Kurulu Toplantıya Çağrı Formu — İSG Kurulları Hakkında Yönetmelik Md.9:
 * "Toplantının gündemi, yeri, günü ve saati toplantıdan en az kırk sekiz saat
 * önce kurul üyelerine bildirilir." Olağanüstü toplantıda (ölüm, uzuv kaybı,
 * ağır iş kazası vb.) süre aciliyete göre belirlenir — 48 saat şartı aranmaz.
 *
 * Davetliler toplantının katılımcı kopyasından (kurul üyeleri), gündem
 * toplantının gündeminden gelir. Çağrı tarihi / olağanüstü nedeni çıktı
 * penceresinde seçilir, kaydedilmez. PDF ve Word aynı veriden.
 */
class KurulCagriUretici
{
    /** Md.9 — olağan toplantıda çağrı ile toplantı arasındaki en az gün (48 saat). */
    public const EN_AZ_GUN = 2;

    /** Karar takip durumları (sayfadaki seçim kutusuyla aynı). */
    private const KARAR_DURUMLARI = ['beklemede' => 'Beklemede', 'devam_ediyor' => 'Devam ediyor', 'tamamlandi' => 'Tamamlandı'];

    /**
     * Olağan toplantıda çağrı tarihi Md.9'daki 48 saati karşılıyor mu?
     * Tarih bazlı: toplantı günü − çağrı günü ≥ 2 gün. Olağanüstüde hep true.
     */
    public static function sureYeterli(KurulToplantisi $toplanti, Carbon|string|null $cagriTarihi): bool
    {
        if ($toplanti->tur === 'olaganustu' || ! $toplanti->tarih || ! $cagriTarihi) {
            return true;
        }

        return Carbon::parse($cagriTarihi)->startOfDay()->diffInDays($toplanti->tarih->copy()->startOfDay(), false) >= self::EN_AZ_GUN;
    }

    /**
     * PDF ve Word ortak içerik.
     *
     * @param  array{cagri_tarihi?: ?string, olaganustu_nedeni?: ?string, onceki_kararlar?: bool}  $secim
     * @return array<string, mixed>
     */
    public static function veri(KurulToplantisi $toplanti, array $secim = []): array
    {
        $toplanti->loadMissing('firma');
        $firma = $toplanti->firma;
        $cagri = Carbon::parse($secim['cagri_tarihi'] ?? now());
        $davetliler = KurulUyeleri::tutanakKatilimcilari($toplanti);

        $kisi = fn (string $rol): ?string => collect($toplanti->katilimcilar ?? [])
            ->firstWhere('rol', $rol)['ad_soyad'] ?? null;
        $oneriler = $firma ? KurulUyeleri::oneriler($firma) : [];

        $olaganustu = $toplanti->tur === 'olaganustu';
        // Gün/saat/yer künyede; metinde Türkçe ek uyumu (saat'te/yerinde) bozulmasın diye tekrar edilmez.
        $metin = 'İş Sağlığı ve Güvenliği Kurulları Hakkında Yönetmelik\'in 9. maddesi uyarınca işyerimiz '
            .'İş Sağlığı ve Güvenliği Kurulu\'nun '
            .($toplanti->toplanti_no ? $toplanti->toplanti_no.' sayılı ' : '')
            .($olaganustu ? 'olağanüstü' : 'olağan').' toplantısı, yukarıda belirtilen gün, saat ve yerde '
            .'aşağıdaki gündemi görüşmek üzere yapılacaktır. Toplantıya katılımınızı rica ederim.';

        return [
            'toplanti' => $toplanti,
            'firma' => $firma,
            'logo' => KurulToplantisiUretici::logoYolu($firma),
            'cagri_tarihi' => $cagri->format('d.m.Y'),
            'olaganustu' => $olaganustu,
            'olaganustu_nedeni' => trim((string) ($secim['olaganustu_nedeni'] ?? '')),
            'metin' => $metin,
            'gundem' => array_values($toplanti->gundem ?? []),
            'davetliler' => $davetliler,
            'baskan' => $kisi('baskan') ?: $toplanti->baskan ?: ($oneriler['baskan']['ad_soyad'] ?? ''),
            'sekreter' => $kisi('sekreter') ?: ($oneriler['sekreter']['ad_soyad'] ?? ''),
            'sure_yeterli' => self::sureYeterli($toplanti, $cagri),
            'onceki_kararlar' => ($secim['onceki_kararlar'] ?? true) ? self::oncekiKararlar($toplanti) : [],
            'notlar' => self::bilgiNotlari($olaganustu),
        ];
    }

    /**
     * Md.9: "Her toplantıda başkan veya sekreter, önceki kararlar ve uygulamaları
     * hakkında kurula bilgi verir." — aynı firmanın bir önceki toplantısının
     * tamamlanmamış kararları çağrıya eklenir.
     *
     * @return array<int, array{karar_metni: string, sorumlu: string, termin: string, durum: string}>
     */
    public static function oncekiKararlar(KurulToplantisi $toplanti): array
    {
        if (! $toplanti->firma || ! $toplanti->tarih) {
            return [];
        }

        $onceki = $toplanti->firma->kurulToplantilari()
            ->whereKeyNot($toplanti->id)
            ->whereDate('tarih', '<=', $toplanti->tarih)
            ->latest('tarih')->latest('id')
            ->first();

        return collect($onceki?->kararlar ?? [])
            ->reject(fn (array $k) => ($k['durum'] ?? null) === 'tamamlandi')
            ->map(fn (array $k) => [
                'karar_metni' => (string) ($k['karar_metni'] ?? '—'),
                'sorumlu' => (string) (($k['sorumlu'] ?? null) ?: '—'),
                'termin' => filled($k['termin'] ?? null) ? Carbon::parse($k['termin'])->format('d.m.Y') : '—',
                'durum' => self::KARAR_DURUMLARI[$k['durum'] ?? 'beklemede'] ?? 'Beklemede',
            ])
            ->values()
            ->all();
    }

    /** @return array<int, string> Md.9'dan üyelere hatırlatmalar */
    public static function bilgiNotlari(bool $olaganustu): array
    {
        return array_values(array_filter([
            'Gündem, sorunların ve varsa iş sağlığı ve güvenliğine ilişkin projelerin önem sırasına göre belirlenmiştir. '
                .'Gündemde değişiklik veya ekleme isteğinizi toplantıdan önce kurul başkanına ya da sekreterine iletebilirsiniz; '
                .'isteğiniz kurulca uygun bulunursa gündeme alınır.',
            'Kurul, üye tam sayısının salt çoğunluğu ve işveren veya işveren vekilinin başkanlığında toplanır. '
                .'Kararlar katılanların salt çoğunluğu ile alınır; çekimser oy kullanılamaz, oyların eşitliği halinde başkanın oyu kararı belirler.',
            'Toplantıda başkan veya sekreter tarafından önceki kararlar ve uygulamaları hakkında bilgi verilecektir.',
            $olaganustu
                ? 'Bu toplantı Yönetmeliğin 9. maddesi kapsamında olağanüstü toplantıdır; toplantı zamanı konunun aciliyeti ve önemine göre belirlenmiştir.'
                : 'Bu çağrı, Yönetmeliğin 9. maddesi gereğince toplantıdan en az kırk sekiz saat önce bildirilmektedir.',
            'Kurul toplantılarında geçen süre çalışma süresinden sayılır. Mazeretiniz nedeniyle katılamayacaksanız kurul sekreterine önceden bildiriniz.',
        ]));
    }

    public static function pdf(KurulToplantisi $toplanti, array $secim = []): StreamedResponse
    {
        $v = self::veri($toplanti, $secim);
        $pdf = Pdf::loadView('pdf.kurul-cagri', $v + ['bilgi' => self::belgeBilgisi($v), 'kunye' => self::kunye($v)])->setPaper('a4');

        return response()->streamDownload(fn () => print ($pdf->output()), self::dosyaAdi($toplanti, 'pdf'));
    }

    /** Aynı çağrı Word olarak — kullanıcı metni Word'de düzeltip dağıtabilsin. */
    public static function word(KurulToplantisi $toplanti, array $secim = []): StreamedResponse
    {
        $v = self::veri($toplanti, $secim);
        $firma = $v['firma'];

        $word = new PhpWord;
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(9);

        $kenar = ['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 70, 'width' => 100 * 50, 'unit' => 'pct'];
        $baslikHucre = ['bgColor' => 'DBEAFE', 'valign' => 'center'];
        $kalin = ['bold' => true, 'color' => '1E3A5F'];
        $h2 = ['bold' => true, 'size' => 11, 'color' => '1E3A5F'];
        $h2p = ['spaceBefore' => 200, 'spaceAfter' => 80, 'keepNext' => true];

        $bolum = $word->addSection(['marginTop' => 600, 'marginBottom' => 700, 'marginLeft' => 650, 'marginRight' => 650]);
        $bolum->addFooter()->addPreserveText(
            'İSG Kurulu Toplantıya Çağrı Formu · '.($firma?->unvan ?? '').' · Sayfa {PAGE} / {NUMPAGES}',
            ['size' => 7, 'color' => '64748B'], ['alignment' => Jc::END]
        );

        // Üst başlık: logo | başlık | belge bilgisi
        $ust = $bolum->addTable(['borderSize' => 6, 'borderColor' => '94A3B8', 'cellMargin' => 80, 'width' => 100 * 50, 'unit' => 'pct']);
        $ust->addRow(900);
        $logoHucre = $ust->addCell(2600, ['valign' => 'center']);
        if ($v['logo']) {
            $logoHucre->addImage($v['logo'], ['width' => 115, 'height' => 42, 'alignment' => Jc::CENTER, 'wrappingStyle' => 'inline']);
        }
        $baslik = $ust->addCell(4800, ['valign' => 'center', 'bgColor' => 'EFF6FF'])->addTextRun(['alignment' => Jc::CENTER]);
        $baslik->addText('İŞ SAĞLIĞI VE GÜVENLİĞİ KURULU', ['bold' => true, 'size' => 13, 'color' => '1E3A5F']);
        $baslik->addTextBreak();
        $baslik->addText('TOPLANTIYA ÇAĞRI FORMU', ['bold' => true, 'size' => 13, 'color' => '1E3A5F']);
        $bilgi = $ust->addCell(2800, ['valign' => 'center']);
        foreach (self::belgeBilgisi($v) as $e => $d) {
            $run = $bilgi->addTextRun(['spaceAfter' => 0]);
            $run->addText($e.': ', ['bold' => true, 'size' => 8, 'color' => '475569']);
            $run->addText($d, ['size' => 8]);
        }

        // Künye
        $bolum->addTextBreak(1, ['size' => 4]);
        $kunye = $bolum->addTable($kenar);
        foreach (self::kunye($v) as $s) {
            $kunye->addRow();
            $kunye->addCell(1700, ['bgColor' => 'F1F5F9'])->addText($s[0], ['bold' => true, 'color' => '475569']);
            if ($s[2] === null) {
                $kunye->addCell(8500, ['gridSpan' => 3])->addText($s[1]);

                continue;
            }
            $kunye->addCell(3400)->addText($s[1]);
            $kunye->addCell(1700, ['bgColor' => 'F1F5F9'])->addText($s[2], ['bold' => true, 'color' => '475569']);
            $kunye->addCell(3400)->addText($s[3]);
        }

        // Çağrı metni
        $bolum->addText('Sayın Kurul Üyesi,', ['bold' => true], ['spaceBefore' => 200, 'spaceAfter' => 80]);
        $bolum->addText($v['metin'], [], ['alignment' => Jc::BOTH, 'spaceAfter' => 80, 'lineHeight' => 1.3]);
        if ($v['olaganustu'] && $v['olaganustu_nedeni'] !== '') {
            $run = $bolum->addTextRun(['spaceAfter' => 80]);
            $run->addText('Olağanüstü toplantı nedeni: ', ['bold' => true]);
            $run->addText($v['olaganustu_nedeni']);
        }

        // Gündem
        $bolum->addText('Gündem', $h2, $h2p);
        $t = $bolum->addTable($kenar);
        foreach ($v['gundem'] ?: ['Gündem maddesi eklenmedi.'] as $i => $madde) {
            $t->addRow();
            $t->addCell(500)->addText((string) ($i + 1), [], ['alignment' => Jc::CENTER]);
            $t->addCell(9700)->addText($madde);
        }
        $t->addRow();
        $t->addCell(500)->addText((string) (count($v['gundem']) + 1), [], ['alignment' => Jc::CENTER]);
        $t->addCell(9700)->addText('Dilek ve temenniler', ['color' => '475569']);

        if ($v['onceki_kararlar']) {
            $bolum->addText('Önceki Toplantıdan Takipteki Kararlar', $h2, $h2p);
            $t = $bolum->addTable($kenar);
            $t->addRow();
            foreach ([['#', 500], ['Karar', 5200], ['Sorumlu', 1700], ['Termin', 1300], ['Durum', 1500]] as [$b, $g]) {
                $t->addCell($g, $baslikHucre)->addText($b, $kalin);
            }
            foreach ($v['onceki_kararlar'] as $i => $k) {
                $t->addRow();
                $t->addCell(500)->addText((string) ($i + 1), [], ['alignment' => Jc::CENTER]);
                $t->addCell(5200)->addText($k['karar_metni']);
                $t->addCell(1700)->addText($k['sorumlu']);
                $t->addCell(1300)->addText($k['termin']);
                $t->addCell(1500)->addText($k['durum']);
            }
        }

        // Md.9 hatırlatmaları
        $bolum->addText('Bilgilendirme (Yönetmelik Md.9)', $h2, $h2p);
        foreach ($v['notlar'] as $not) {
            $bolum->addListItem($not, 0, ['size' => 8, 'color' => '334155'], null, ['spaceAfter' => 40]);
        }

        // Çağrıyı yapanlar
        $bolum->addTextBreak(1, ['size' => 4]);
        $imza = $bolum->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 60]);
        $imza->addRow(null, ['cantSplit' => true]);
        foreach ([['Kurul Başkanı', 'İşveren / İşveren Vekili', $v['baskan']], ['Kurul Sekreteri', 'İş Güvenliği Uzmanı', $v['sekreter']]] as [$unvan, $alt, $ad]) {
            $h = $imza->addCell(5100, ['valign' => 'top']);
            $h->addText($unvan, ['bold' => true, 'color' => '1E3A5F'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'keepNext' => true]);
            $h->addText($alt, ['size' => 8, 'color' => '64748B'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'keepNext' => true]);
            $h->addText($ad ?: '……………………………', [], ['alignment' => Jc::CENTER, 'spaceBefore' => 80, 'keepNext' => true]);
            $h->addText('İmza', ['size' => 8, 'color' => '94A3B8'], ['alignment' => Jc::CENTER, 'spaceBefore' => 500]);
        }

        // Tebliğ — tebellüğ föyü (bölünmez)
        $birlikte = ['keepNext' => true, 'keepLines' => true];
        $bolum->addText('Tebliğ – Tebellüğ', $h2, $h2p);
        $bolum->addText('Toplantı çağrısını ve gündemini tebellüğ ettim.', ['size' => 8, 'color' => '475569'], ['spaceAfter' => 60, 'keepNext' => true]);
        $t = $bolum->addTable($kenar);
        $t->addRow(null, ['cantSplit' => true]);
        foreach ([['#', 500], ['Ad Soyad', 2700], ['Kuruldaki Görevi', 3300], ['Tebliğ Tarihi', 1500], ['İmza', 2200]] as [$b, $g]) {
            $t->addCell($g, $baslikHucre)->addText($b, $kalin, $birlikte);
        }
        $son = count($v['davetliler']) - 1;
        foreach ($v['davetliler'] as $i => $k) {
            $p = $i < $son ? $birlikte : [];
            $t->addRow(600, ['cantSplit' => true]);
            $t->addCell(500, ['valign' => 'center'])->addText((string) ($i + 1), [], $p + ['alignment' => Jc::CENTER]);
            $t->addCell(2700, ['valign' => 'center'])->addText($k['ad_soyad'], [], $p);
            $t->addCell(3300, ['valign' => 'center'])->addText($k['kurul_gorevi'], [], $p);
            $t->addCell(1500, ['valign' => 'center'])->addText('…../…../……..', ['color' => '94A3B8'], $p);
            $t->addCell(2200)->addText('', [], $p);
        }

        $gecici = tempnam(sys_get_temp_dir(), 'mehse-cagri').'.docx';
        WordIO::createWriter($word, 'Word2007')->save($gecici);

        // deleteFileAfterSend bu ortamda takılabiliyor — streamDownload + elle sil.
        return response()->streamDownload(function () use ($gecici) {
            echo file_get_contents($gecici);
            @unlink($gecici);
        }, self::dosyaAdi($toplanti, 'docx'));
    }

    /** @return array<string, string> */
    public static function belgeBilgisi(array $v): array
    {
        return [
            'Belge No' => $v['toplanti']->belge_no ?: '—',
            'Toplantı No' => $v['toplanti']->toplanti_no ?: '—',
            'Revizyon' => $v['toplanti']->revizyon_no ?: '00',
            'Çağrı Tarihi' => $v['cagri_tarihi'],
        ];
    }

    /** @return array<int, array{0: string, 1: string, 2: ?string, 3: ?string}> */
    public static function kunye(array $v): array
    {
        $t = $v['toplanti'];
        $f = $v['firma'];

        return [
            ['İşyeri', $f?->unvan ?? '—', 'Adres', collect([$f?->adres, $f?->ilce, $f?->il])->filter()->implode(', ') ?: '—'],
            ['Toplantı tarihi', $t->tarih?->copy()->locale('tr')->isoFormat('DD.MM.YYYY dddd') ?: '—', 'Saat', $t->saatAraligi() ?: '—'],
            ['Toplantı yeri', $t->yer ?: '—', 'Toplantı türü', $t->turEtiketi()],
            ['Davet edilen', count($v['davetliler']).' kurul üyesi', null, null],
        ];
    }

    private static function dosyaAdi(KurulToplantisi $toplanti, string $uzanti): string
    {
        $ek = $toplanti->toplanti_no
            ? Str::slug(str_replace('/', '-', $toplanti->toplanti_no))
            : $toplanti->tarih?->format('Y-m-d');

        return 'kurul-cagri-'.Str::slug($toplanti->firma?->unvan ?? 'firma').'-'.$ek.'.'.$uzanti;
    }
}
