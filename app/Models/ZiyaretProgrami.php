<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ziyaret Programı — firma + yıl başına tek kayıt, 12 aylık satır.
 */
class ZiyaretProgrami extends Model
{
    protected $table = 'ziyaret_programlari';

    protected $guarded = ['id'];

    protected $casts = [
        'yil' => 'integer',
        'ziyaretler' => 'array',
    ];

    public const DURUM_SIRASI = ['bos', 'planlandi', 'tamamlandi'];

    public const AYLAR = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

    /** Carbon dayOfWeekIso sırasıyla (Pazartesi=1). */
    public const HAFTA_GUNLERI = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function bosGirdi(): array
    {
        return ['tarih' => null, 'amac' => null, 'durum' => 'bos', 'sure_saat' => null, 'notlar' => null];
    }

    public static function firmaYilIcin(Firma $firma, int $yil): self
    {
        $p = static::firstOrNew(['firma_id' => $firma->id, 'yil' => $yil]);

        if (! $p->exists) {
            $p->ziyaretler = array_fill(0, 12, [static::bosGirdi()]);
            $p->save();
        }

        return $p;
    }

    /**
     * Bir ay hücresini her zaman ziyaret girdisi LİSTESİ olarak döndürür.
     * Eski (tek girdi düz dizi) ve yeni (girdi listesi) veri şekillerinin
     * ikisini de destekler, göç gerektirmez.
     *
     * @return array<int, array{tarih: ?string, amac: ?string, durum: string, sure_saat: mixed, notlar: ?string}>
     */
    public static function ayGirdileri(?array $ay): array
    {
        if (empty($ay)) {
            return [static::bosGirdi()];
        }

        if (array_key_exists('tarih', $ay)) {
            return [$ay];
        }

        return array_values($ay);
    }

    /** Bir ay/satır girdisinin durumunu Boş→Planlandı→Tamamlandı sırasıyla ilerletir ve kaydeder. */
    public function durumIlerlet(int $ayIndex, int $satirIndex): void
    {
        $aylar = $this->ziyaretler ?? [];

        if (! isset($aylar[$ayIndex])) {
            return;
        }

        $girdiler = static::ayGirdileri($aylar[$ayIndex]);

        if (! isset($girdiler[$satirIndex])) {
            return;
        }

        $mevcut = $girdiler[$satirIndex]['durum'] ?? 'bos';
        $siraIndex = array_search($mevcut, static::DURUM_SIRASI, true);
        $girdiler[$satirIndex]['durum'] = static::DURUM_SIRASI[($siraIndex + 1) % count(static::DURUM_SIRASI)];

        $aylar[$ayIndex] = $girdiler;
        $this->update(['ziyaretler' => $aylar]);
    }
}
