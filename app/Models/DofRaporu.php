<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DÖF Oluştur (Çoklu DÖF) raporu — isgpratik 158.jpg.
 */
class DofRaporu extends Model
{
    protected $table = 'dof_raporlari';

    protected $guarded = ['id'];

    protected $casts = [
        'rapor_tarihi' => 'date',
        'maddeler' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (DofRaporu $d): void {
            $d->belge_no ??= 'DOF-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
        });

        // Maddeler ortak saha bulgularına bağlanır, madde durumu bulguya geçer.
        static::saved(fn (DofRaporu $d) => \App\Support\BulguHavuzu::esle($d));
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function oncelikEtiketi(?string $anahtar): string
    {
        return config('isg.dof.oncelikler.'.$anahtar, $anahtar ?? '—');
    }

    public static function durumEtiketi(?string $anahtar): string
    {
        return config('isg.dof.durumlar.'.$anahtar, $anahtar ?? '—');
    }

    /*
    |--------------------------------------------------------------------------
    | PDF künye / imza / tablo varsayılanları (02.11.2026)
    |--------------------------------------------------------------------------
    | Boş alanlar firmadaki tanımlı kişilerden ve akıllı varsayılanlardan dolar;
    | kayıttaki değer her zaman önceliklidir.
    */

    public const VARSAYILAN_ALAN = 'Şantiye / İşletme Geneli';

    public const VARSAYILAN_SORUMLU = 'İşveren / Saha Sorumlusu';

    /** Öncelik → rapor tarihine eklenecek gün (null = Derhal). */
    public const TERMIN_GUN = ['kritik' => null, 'yuksek' => 3, 'orta' => 7, 'dusuk' => 30];

    /** Gözetim yapan İGU: ad, tam unvan, kısa unvan ("A Sınıfı İGU"), belge no. */
    public function gozetimYapanBilgisi(): array
    {
        $igu = $this->firma?->igu;
        $sahip = $this->firma?->user;
        $ham = (string) ($igu?->unvan ?: ($sahip?->unvan ? $sahip->unvanEtiketi() : ''));

        // "A SINIFI İŞ GÜVENLİĞİ UZMANI" / "A Sınıfı …" → sınıf harfinden standart unvan.
        $sinif = preg_match('/\b([ABC])\s*SINIFI/u', mb_strtoupper(strtr($ham, ['ı' => 'I', 'i' => 'İ']), 'UTF-8'), $e) ? $e[1] : null;
        $unvan = $sinif ? $sinif.' Sınıfı İş Güvenliği Uzmanı' : ($ham !== '' ? $ham : 'İş Güvenliği Uzmanı');

        return [
            'ad' => $this->gozetim_yapan ?: ($igu?->ad_soyad ?: $sahip?->name),
            'unvan' => $unvan,
            'kisa_unvan' => $sinif ? $sinif.' Sınıfı İGU' : 'İGU',
            'belge_no' => $this->gozetim_yapan_sertifika_no ?: ($igu?->sertifika_no ?: $sahip?->sertifika_no),
        ];
    }

    /** İşveren / işveren vekili adı (imza kutusu). */
    public function isverenAdi(): ?string
    {
        return $this->isveren_vekili_adi ?: ($this->firma?->isveren_vekili ?: $this->firma?->isveren_ad) ?: null;
    }

    /** Künyedeki "Sorumlu Kişi": kayıttaki ad, yoksa firma yetkilisi. */
    public function sorumluKisiAdi(): ?string
    {
        $genel = ['işveren', 'işveren vekili', 'işveren / işveren vekili', 'işveren/işveren vekili'];
        $ad = trim((string) $this->sorumlu_kisi);

        // Kayıtta isim yerine genel ifade varsa firma yetkilisinin adı basılır.
        // Türkçe "İ" mb_strtolower'da "i̇" (i + nokta) olur; önce düz "i"ye çevrilir.
        $kucuk = mb_strtolower(strtr($ad, ['İ' => 'i', 'I' => 'ı']), 'UTF-8');

        return ($ad !== '' && ! in_array(preg_replace('/\s+/u', ' ', $kucuk), $genel, true)) ? $ad : $this->isverenAdi();
    }

    public function alanBolgeMetni(): string
    {
        return trim((string) $this->alan_bolge) ?: self::VARSAYILAN_ALAN;
    }

    public function gozetimAraligiMetni(): string
    {
        return trim((string) $this->gozetim_tarih_araligi) ?: ($this->rapor_tarihi ?? now())->format('d.m.Y');
    }

    /** Madde sorumlusu: boşsa "İşveren / Saha Sorumlusu". */
    public static function maddeSorumlusu(array $m): string
    {
        return trim((string) ($m['sorumlu'] ?? '')) ?: self::VARSAYILAN_SORUMLU;
    }

    /** Madde termini: girilmişse tarih; boşsa önceliğe göre Derhal / rapor tarihi + 3 / 7 / 30 gün. */
    public function maddeTermini(array $m): string
    {
        if (filled($m['termin'] ?? null)) {
            return \Illuminate\Support\Carbon::parse($m['termin'])->format('d.m.Y');
        }

        $oncelik = $m['oncelik'] ?? 'orta';
        $gun = array_key_exists($oncelik, self::TERMIN_GUN) ? self::TERMIN_GUN[$oncelik] : 7;   // kritik → null = Derhal

        return $gun === null ? 'Derhal' : ($this->rapor_tarihi ?? now())->copy()->addDays($gun)->format('d.m.Y');
    }
}
