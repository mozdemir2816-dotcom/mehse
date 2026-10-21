<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tabela / Güvenlik Levhası Tasarım Atölyesi — firma başına birden çok kayıt.
 * `tasarim` JSON kolonu editörün tüm durumunu (renkler, piktogram slotları,
 * metinler, ebat) tutar; `gorsel_yolu` son kaydedilen görünümün PNG'sidir.
 */
class TabelaTasarimi extends Model
{
    protected $table = 'tabela_tasarimlari';

    protected $guarded = ['id'];

    protected $casts = [
        'tasarim' => 'array',
    ];

    public const STANDART_TIPLERI = ['ansi', 'yonetmelik_pure'];

    public const FORM_TIPLERI = ['single', 'flag', 'floor'];

    public const PIKTOGRAM_KONUMLARI = ['top', 'left', 'right', 'bottom', 'none'];

    public const VIDA_SECENEKLERI = ['4_corners', '2_sides', 'none'];

    public const PIKTOGRAM_CERCEVELERI = ['none', 'warning_triangle', 'mandatory_circle', 'prohibition_circle'];

    public const FONT_AILELERI = ['font-roboto', 'font-impact', 'font-sans', 'font-montserrat', 'font-oswald', 'font-arial'];

    public const METIN_HIZALARI = ['text-center', 'text-left', 'text-right'];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** Editöre gönderilecek varsayılan tasarım durumu (yeni tasarım). */
    public static function varsayilanTasarim(): array
    {
        return [
            'standardType' => 'ansi',
            'formType' => 'single',
            'headerBg' => '#D02C2F',
            'headerTextCol' => '#ffffff',
            'bodyBg' => '#ffffff',
            'bodyTextCol' => '#000000',
            'borderColor' => '#000000',
            'borderWidth' => 4,
            'borderRadius' => 8,
            'doubleBorder' => true,
            'screwOption' => '4_corners',
            'signalTr' => 'TEHLİKE',
            'signalEn' => 'DANGER',
            'hasTriangle' => true,
            'picPosition' => 'top',
            'picCount' => 1,
            'activeSlotIndex' => 0,
            'slots' => ['warn_electric', 'cmd_helmet', 'cmd_boots', 'no_entry'],
            'slotCustomImages' => [null, null, null, null],
            'picWrapper' => 'none',
            'picScale' => 100,
            'mainTitle' => 'YÜKSEK GERİLİM - 380 VOLT',
            'descText' => 'Panolara ve elektrik hücrelerine yetkisiz personelin müdahalesi kesinlikle yasaktır.',
            'showEn' => false,
            'enTitle' => '',
            'enDesc' => '',
            'specNote' => '0.50mm Eloksallı Alüminyum / UV Baskı / -30°C +80°C Dayanımlı',
            'fontFamily' => 'font-roboto',
            'textAlign' => 'text-center',
            'titleSize' => 22,
            'descSize' => 12,
            'width' => 480,
            'height' => 680,
        ];
    }

    /**
     * Tarayıcıdan gelen tasarım verisini doğrular / sınırlar (bilinen tipler,
     * sayı aralıkları, kısa metinler, boyutu sınırlı özel piktogram resimleri).
     */
    public static function temizle(array $veri): array
    {
        $metin = fn ($v, int $uz) => mb_substr(trim(strip_tags((string) ($v ?? ''))), 0, $uz);
        $renk = fn ($v, string $varsayilan) => is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $varsayilan;
        $sayi = fn ($v, int $min, int $max, int $varsayilan) => is_numeric($v) ? (int) max($min, min($max, (float) $v)) : $varsayilan;
        $secenek = fn ($v, array $gecerli, string $varsayilan) => in_array($v, $gecerli, true) ? $v : $varsayilan;

        $slotlar = collect($veri['slots'] ?? [])->take(4)
            ->map(fn ($s) => is_string($s) && $s !== '' ? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $s), 0, 40) : null)
            ->values()->all();
        while (count($slotlar) < 4) {
            $slotlar[] = null;
        }

        $ozelResimler = collect($veri['slotCustomImages'] ?? [])->take(4)
            ->map(function ($img) {
                if (! is_string($img) || ! preg_match('/^data:image\/(png|jpe?g|svg\+xml);base64,/', $img)) {
                    return null;
                }

                return strlen($img) > 2_000_000 ? null : $img;
            })->values()->all();
        while (count($ozelResimler) < 4) {
            $ozelResimler[] = null;
        }

        return [
            'standardType' => $secenek($veri['standardType'] ?? null, self::STANDART_TIPLERI, 'ansi'),
            'formType' => $secenek($veri['formType'] ?? null, self::FORM_TIPLERI, 'single'),
            'headerBg' => $renk($veri['headerBg'] ?? null, '#D02C2F'),
            'headerTextCol' => $renk($veri['headerTextCol'] ?? null, '#ffffff'),
            'bodyBg' => $renk($veri['bodyBg'] ?? null, '#ffffff'),
            'bodyTextCol' => $renk($veri['bodyTextCol'] ?? null, '#000000'),
            'borderColor' => $renk($veri['borderColor'] ?? null, '#000000'),
            'borderWidth' => $sayi($veri['borderWidth'] ?? null, 0, 8, 4),
            'borderRadius' => $sayi($veri['borderRadius'] ?? null, 0, 32, 8),
            'doubleBorder' => (bool) ($veri['doubleBorder'] ?? true),
            'screwOption' => $secenek($veri['screwOption'] ?? null, self::VIDA_SECENEKLERI, '4_corners'),
            'signalTr' => $metin($veri['signalTr'] ?? 'TEHLİKE', 40) ?: 'TEHLİKE',
            'signalEn' => $metin($veri['signalEn'] ?? 'DANGER', 40),
            'hasTriangle' => (bool) ($veri['hasTriangle'] ?? true),
            'picPosition' => $secenek($veri['picPosition'] ?? null, self::PIKTOGRAM_KONUMLARI, 'top'),
            'picCount' => $sayi($veri['picCount'] ?? null, 1, 4, 1),
            'activeSlotIndex' => $sayi($veri['activeSlotIndex'] ?? null, 0, 3, 0),
            'slots' => $slotlar,
            'slotCustomImages' => $ozelResimler,
            'picWrapper' => $secenek($veri['picWrapper'] ?? null, self::PIKTOGRAM_CERCEVELERI, 'none'),
            'picScale' => $sayi($veri['picScale'] ?? null, 50, 150, 100),
            'mainTitle' => $metin($veri['mainTitle'] ?? '', 120),
            'descText' => $metin($veri['descText'] ?? '', 500),
            'showEn' => (bool) ($veri['showEn'] ?? false),
            'enTitle' => $metin($veri['enTitle'] ?? '', 120),
            'enDesc' => $metin($veri['enDesc'] ?? '', 500),
            'specNote' => $metin($veri['specNote'] ?? '', 160),
            'fontFamily' => $secenek($veri['fontFamily'] ?? null, self::FONT_AILELERI, 'font-roboto'),
            'textAlign' => $secenek($veri['textAlign'] ?? null, self::METIN_HIZALARI, 'text-center'),
            'titleSize' => $sayi($veri['titleSize'] ?? null, 14, 44, 22),
            'descSize' => $sayi($veri['descSize'] ?? null, 9, 22, 12),
            'width' => $sayi($veri['width'] ?? null, 360, 720, 480),
            'height' => $sayi($veri['height'] ?? null, 360, 850, 680),
        ];
    }

    public static function basligiTemizle(?string $baslik): string
    {
        $temiz = mb_substr(trim(strip_tags((string) $baslik)), 0, 120);

        return $temiz !== '' ? $temiz : 'İsimsiz Tabela';
    }
}
