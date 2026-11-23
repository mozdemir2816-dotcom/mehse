<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bir firma için uzaktan eğitim ayarları. İçerik onayı (paket + içerik sürümü) verilmeden
 * o firmaya gönderim yapılmaz; paket içeriği (ders / video / süre) değişirse onay yeniden istenir.
 */
class UzaktanEgitimFirmaAyari extends Model
{
    protected $table = 'uzaktan_egitim_firma_ayarlari';

    protected $guarded = ['id'];

    protected $casts = [
        'kase_bas' => 'boolean',
        'calisan_imza_alani' => 'boolean',
        'icerik_onay_at' => 'datetime',
        'icerik_onaylari' => 'array',
    ];

    public const LOGO_KONUMLARI = [
        'yok' => 'Logo yok',
        'sol' => 'Firma logosu solda',
        'sag' => 'Firma logosu sağda',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaIcin(int $firmaId): ?self
    {
        return static::query()->where('firma_id', $firmaId)->first();
    }

    /** Paket içeriğinin parmak izi: ders sırası/başlığı/video/süre değişirse değişir. */
    public static function icerikSurumu(EgitimPaketi $paket): string
    {
        $satirlar = $paket->dersler()->orderBy('sira')->get()
            ->map(fn ($d) => [$d->id, $d->modul, $d->sira, $d->baslik, $d->video_url, $d->sure_sn])->all();

        return substr(sha1(json_encode($satirlar, JSON_UNESCAPED_UNICODE)), 0, 12);
    }

    /**
     * Verilen paket için içerik onayı geçerli mi (aynı paket + aynı içerik sürümü). 23.11.2026'dan
     * beri onay paket başına `icerik_onaylari` içinde tutulur; eski tek paketli alanlar da okunur.
     */
    public function icerikOnayliMi(EgitimPaketi $paket): bool
    {
        $surum = static::icerikSurumu($paket);
        $kayit = $this->icerik_onaylari[(string) $paket->id] ?? null;

        if ($kayit) {
            return ($kayit['surum'] ?? null) === $surum;
        }

        return $this->icerik_onay_at !== null
            && (int) $this->icerik_paket_id === (int) $paket->id
            && $this->icerik_surumu === $surum;
    }

    /** Paketin onay bilgisi (onaylayan, tarih) — yoksa null. @return array{onaylayan: ?string, at: ?\Illuminate\Support\Carbon}|null */
    public function onayBilgisi(EgitimPaketi $paket): ?array
    {
        if ($kayit = $this->icerik_onaylari[(string) $paket->id] ?? null) {
            return ['onaylayan' => $kayit['onaylayan'] ?? null, 'at' => isset($kayit['at']) ? \Illuminate\Support\Carbon::parse($kayit['at']) : null];
        }

        return $this->icerik_onay_at && (int) $this->icerik_paket_id === (int) $paket->id
            ? ['onaylayan' => $this->icerik_onaylayan, 'at' => $this->icerik_onay_at]
            : null;
    }

    /** Paket için içerik onayını ver ya da kaldır. */
    public function onayYaz(EgitimPaketi $paket, ?string $onaylayan): void
    {
        $onaylar = $this->icerik_onaylari ?? [];
        $onaylar[(string) $paket->id] = ['surum' => static::icerikSurumu($paket), 'at' => now()->toDateTimeString(), 'onaylayan' => $onaylayan];
        $this->icerik_onaylari = $onaylar;
        // Eski alanlar son onaylanan paketi gösterir (geriye dönük okuma).
        $this->icerik_paket_id = $paket->id;
        $this->icerik_surumu = $onaylar[(string) $paket->id]['surum'];
        $this->icerik_onay_at = now();
        $this->icerik_onaylayan = $onaylayan;
    }

    public function onayKaldir(EgitimPaketi $paket): void
    {
        $onaylar = $this->icerik_onaylari ?? [];
        unset($onaylar[(string) $paket->id]);
        $this->icerik_onaylari = $onaylar ?: null;

        if ((int) $this->icerik_paket_id === (int) $paket->id) {
            $this->icerik_onay_at = null;
            $this->icerik_surumu = null;
            $this->icerik_onaylayan = null;
        }
    }

    /** Gönderime hazır mı: ayar kaydedilmiş ve içerik onaylı. */
    public static function gonderimeHazirMi(Firma $firma, EgitimPaketi $paket): bool
    {
        return (bool) static::firmaIcin($firma->id)?->icerikOnayliMi($paket);
    }

    /** Belge için görseli data URI olarak döndürür (dompdf), yoksa null. */
    public static function gorselVeri(?string $yol): ?string
    {
        if (! $yol) {
            return null;
        }
        $tam = storage_path('app/public/'.$yol);
        if (! is_file($tam)) {
            return null;
        }
        $mime = mime_content_type($tam) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($tam));
    }
}
