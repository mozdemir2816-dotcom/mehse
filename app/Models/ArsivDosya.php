<?php

namespace App\Models;

use App\Support\ArsivKurali;
use App\Support\KullaniciAyarlari;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Arşiv dosyası / doküman — Profilim > Arşiv (isgpratik 144.jpg), Doküman
 * Yönetimi (isgsuite) ve 04.10.2026'dan itibaren kategori kurallı Arşiv
 * (config/arsiv.php) aynı tabloyu kullanır.
 *
 * asama: 'dosyada' (resmî kayıt) | 'imza_bekliyor' (şablondan üretilmiş,
 * imzalanıp "Dosyaya ekle" ile resmîleşmeyi bekleyen belge).
 * Kural kategorilerinde gecerlilik_sonu kaydederken kuraldan hesaplanır.
 */
class ArsivDosya extends Model
{
    public const DOSYADA = 'dosyada';

    public const IMZA_BEKLIYOR = 'imza_bekliyor';

    protected $table = 'arsiv_dosyalari';

    protected $guarded = ['id'];

    protected $attributes = ['aktif' => true, 'asama' => self::DOSYADA];

    protected $casts = [
        'boyut' => 'integer',
        'baslangic_tarihi' => 'date',
        'gecerlilik_sonu' => 'date',
        'aktif' => 'boolean',
        'yil' => 'integer',
        'alanlar' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (ArsivDosya $d): void {
            if (ArsivKurali::takipliMi((string) $d->kategori)) {
                $d->gecerlilik_sonu = ArsivKurali::gecerlilikSonu($d);
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /**
     * Tarih bazlı uyarılara (Bildirim Merkezi, İşyeri Durum takvimi) giren
     * kayıtlar: aktif, dosyada ve "kayıt" türü kategoride. Kural kategorileri
     * ArsivKurali::durum ile beklenen belge üzerinden ayrıca uyarılır — ör.
     * geçen yılın planı 31 Aralık'ta "doldu" diye alarm vermesin.
     */
    public function scopeTarihTakipli(Builder $q): Builder
    {
        $kayitKategorileri = collect(config('arsiv.kategoriler'))->where('kural', 'kayit')->keys()->all();

        return $q->where('aktif', true)
            ->where(fn ($q) => $q->where('asama', self::DOSYADA)->orWhereNull('asama'))
            ->where(fn ($q) => $q->whereIn('kategori', $kayitKategorileri)->orWhereNull('kategori'));
    }

    public function boyutEtiketi(): string
    {
        $kb = $this->boyut / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb, 0).' KB';
    }

    /** Listede görünen ad: başlık, yoksa dosya adı. */
    public function etiket(): string
    {
        return $this->baslik ?: (string) $this->dosya_adi;
    }

    public function kategoriEtiketi(): string
    {
        return ArsivKurali::kategori($this->kategori)['ad'];
    }

    public function imzaBekliyorMu(): bool
    {
        return $this->asama === self::IMZA_BEKLIYOR;
    }

    /** @return 'suresiz'|'gecerli'|'yaklasan'|'dolmus' */
    public function gecerlilikDurumu(): string
    {
        if (! $this->gecerlilik_sonu) {
            return 'suresiz';
        }

        $kalan = (int) Carbon::today()->diffInDays($this->gecerlilik_sonu, false);

        return match (true) {
            $kalan < 0 => 'dolmus',
            $kalan <= KullaniciAyarlari::arsivYaklasanGun() => 'yaklasan',
            default => 'gecerli',
        };
    }

    public function durumEtiketi(): string
    {
        if (! $this->aktif) {
            return 'Pasif';
        }

        if ($this->imzaBekliyorMu()) {
            return 'İmza bekliyor';
        }

        return ['suresiz' => 'Aktif', 'gecerli' => 'Aktif', 'yaklasan' => 'Süresi yaklaşıyor', 'dolmus' => 'Süresi doldu'][$this->gecerlilikDurumu()];
    }
}
