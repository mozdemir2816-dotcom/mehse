<?php

namespace App\Support;

use App\Models\AcilDurumKrokisi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcilDurumKrokisiUretici
{
    public const SVG_BOYUTLAR = [
        'A4' => ['etiket' => 'A4 Yatay (297 × 210 mm)', 'w' => '297mm', 'h' => '210mm'],
        'A3' => ['etiket' => 'A3 Yatay (420 × 297 mm)', 'w' => '420mm', 'h' => '297mm'],
        'A0' => ['etiket' => 'A0 Yatay — Büyük Pano (1189 × 841 mm)', 'w' => '1189mm', 'h' => '841mm'],
    ];

    public static function pdf(AcilDurumKrokisi $kroki): StreamedResponse
    {
        $kroki->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.acil-durum-krokisi', [
            'kroki' => $kroki,
            'firma' => $kroki->firma,
        ])->setPaper('a4', 'landscape');

        $ad = 'acil-durum-krokisi-'.str($kroki->firma?->unvan ?: 'firma')->slug().'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /**
     * A4 / A3 / A0 ölçeklenebilir vektör SVG çıktısı — duvarlar, ISO 7010 sembolleri,
     * otomatik adet sayan Lejant tablosu, 112 uyarısı ve onay anteti dahil.
     */
    public static function svg(AcilDurumKrokisi $kroki, string $boyut = 'A3'): StreamedResponse
    {
        $kroki->loadMissing('firma.igu');
        $b = self::SVG_BOYUTLAR[$boyut] ?? self::SVG_BOYUTLAR['A3'];
        $svg = self::svgIcerigi($kroki, $boyut);

        $ad = 'acil-durum-krokisi-'.Str::slug($kroki->firma?->unvan ?: 'firma').'-'.strtolower(array_key_exists($boyut, self::SVG_BOYUTLAR) ? $boyut : 'a3').'.svg';

        return response()->streamDownload(
            fn () => print ($svg),
            $ad,
            ['Content-Type' => 'image/svg+xml; charset=UTF-8']
        );
    }

    public static function svgIcerigi(AcilDurumKrokisi $kroki, string $boyut = 'A3'): string
    {
        $b = self::SVG_BOYUTLAR[$boyut] ?? self::SVG_BOYUTLAR['A3'];
        $surum2 = ($kroki->antet['surum'] ?? 1) >= 2;
        $cw = $surum2 ? (int) config('isg.kroki.genislik', 1400) : 1000;
        $ch = $surum2 ? (int) config('isg.kroki.yukseklik', 990) : 700;
        $lejant = $kroki->lejant();
        $ustYukseklik = 72;
        $altYukseklik = max(90, 48 + (int) ceil(max(1, count($lejant)) / 4) * 24);
        $toplamH = $ustYukseklik + $ch + $altYukseklik;

        $esc = fn (?string $s): string => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $firma = $kroki->firma;
        $tarih = $kroki->hazirlanma_tarihi?->format('d.m.Y') ?? now()->format('d.m.Y');

        $out = [];
        $out[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $out[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="%s" height="%s" viewBox="0 0 %d %d">',
            $b['w'], $b['h'], $cw, $toplamH
        );
        $out[] = '<rect x="0" y="0" width="'.$cw.'" height="'.$toplamH.'" fill="#ffffff" />';

        // Antet üst bant
        $out[] = '<rect x="0" y="0" width="'.$cw.'" height="'.$ustYukseklik.'" fill="#0f294a" />';
        $out[] = '<text x="20" y="30" fill="#ffffff" font-family="DejaVu Sans, Arial, sans-serif" font-size="22" font-weight="bold">ACİL DURUM VE TAHLİYE KROKİSİ</text>';
        $out[] = '<text x="20" y="55" fill="#cbd5e1" font-family="DejaVu Sans, Arial, sans-serif" font-size="13">'
            .$esc(($firma?->unvan ?: 'FİRMA').' · '.($firma?->adres ?: '').' · Tarih: '.$tarih)
            .'</text>';
        $out[] = '<rect x="'.($cw - 240).'" y="14" width="220" height="44" rx="6" fill="#dc2626" />';
        $out[] = '<text x="'.($cw - 130).'" y="42" text-anchor="middle" fill="#ffffff" font-family="DejaVu Sans, Arial, sans-serif" font-size="16" font-weight="bold">ACİL ÇAĞRI: 112</text>';

        // Kroki çizim alanı
        $out[] = '<g transform="translate(0, '.$ustYukseklik.')">';
        $out[] = '<rect x="0" y="0" width="'.$cw.'" height="'.$ch.'" fill="#f8fafc" stroke="#94a3b8" stroke-width="2" />';

        foreach (($kroki->duvarlar ?? []) as $d) {
            $out[] = sprintf(
                '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#1e293b" stroke-width="5" stroke-linecap="round" />',
                (float) ($d['x1'] ?? 0), (float) ($d['y1'] ?? 0), (float) ($d['x2'] ?? 0), (float) ($d['y2'] ?? 0)
            );
        }

        foreach (($kroki->semboller ?? []) as $s) {
            $out[] = trim(view('filament.pages.partials.kroki-sembol', [
                'tip' => $s['tip'] ?? '',
                'x' => $s['x'] ?? 0,
                'y' => $s['y'] ?? 0,
                'etiket' => $s['etiket'] ?? null,
            ])->render());
        }
        $out[] = '</g>';

        // Alt otomatik sembol sayacı lejant + onay bandı
        $ly = $ustYukseklik + $ch;
        $out[] = '<rect x="0" y="'.$ly.'" width="'.$cw.'" height="'.$altYukseklik.'" fill="#f1f5f9" stroke="#cbd5e1" stroke-width="1" />';
        $out[] = '<text x="20" y="'.($ly + 22).'" fill="#0f172a" font-family="DejaVu Sans, Arial, sans-serif" font-size="13" font-weight="bold">OTOMATİK ISO 7010 SEMBOL LEJANTI &amp; SAYACI</text>';

        $sutunGen = (int) floor(($cw - 40) / 4);
        foreach ($lejant as $idx => $l) {
            $col = $idx % 4;
            $row = intdiv($idx, 4);
            $lx = 20 + ($col * $sutunGen);
            $ry = $ly + 42 + ($row * 22);
            $out[] = '<rect x="'.$lx.'" y="'.($ry - 11).'" width="12" height="12" rx="2" fill="'.$esc($l['renk'] ?? '#334155').'" />';
            $out[] = '<text x="'.($lx + 18).'" y="'.$ry.'" fill="#1e293b" font-family="DejaVu Sans, Arial, sans-serif" font-size="12">'
                .$esc(($l['ad'] ?? '').' — '.((int) ($l['adet'] ?? 0)).' adet')
                .'</text>';
        }

        $onayY = $toplamH - 12;
        $out[] = '<text x="20" y="'.$onayY.'" fill="#475569" font-family="DejaVu Sans, Arial, sans-serif" font-size="11">'
            .$esc('Hazırlayan (İGU): '.($firma?->igu?->ad_soyad ?: '........................').'   |   Onaylayan (İşveren/Vekili): '.($firma?->isveren_vekili ?: ($firma?->isveren_ad ?: '........................')).'   |   Format: '.$boyut.' Vektör SVG')
            .'</text>';

        $out[] = '</svg>';

        return implode("\n", $out);
    }
}
