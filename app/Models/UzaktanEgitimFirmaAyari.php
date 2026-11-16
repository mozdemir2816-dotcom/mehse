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

    /** Verilen paket için içerik onayı geçerli mi (aynı paket + aynı içerik sürümü). */
    public function icerikOnayliMi(EgitimPaketi $paket): bool
    {
        return $this->icerik_onay_at !== null
            && (int) $this->icerik_paket_id === (int) $paket->id
            && $this->icerik_surumu === static::icerikSurumu($paket);
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
