<?php

namespace App\Models;

use App\Support\EkipmanKatalogu;
use App\Support\KullaniciAyarlari;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * İş Ekipmanı — periyodik kontrol/muayene takip künyesi (isgpratik "Ekipman &
 * Periyodik Kontrol Motoru"). Son muayene tarihi girilince "sonraki vize"
 * periyottan otomatik hesaplanır; vize durumu (geçerli / yaklaşan / dolmuş)
 * buradan türetilir.
 */
class IsEkipmani extends Model
{
    use HasFactory;

    protected $table = 'is_ekipmanlari';

    protected $guarded = ['id'];

    protected $casts = [
        'muayene_periyodu_ay' => 'integer',
        'son_muayene_tarihi' => 'date',
        'sonraki_vize_tarihi' => 'date',
        'aktif' => 'boolean',
        'uretim_yili' => 'integer',
        'teblig_cikti_at' => 'datetime',
        'isverene_teblig_edildi' => 'boolean',
        'teblig_tarihi' => 'date',
    ];

    protected $hidden = ['qr_pin'];

    /** Periyodik kontrole kalan gün eşikleri (12.11.2026): 30 yeşil, 15 sarı, 7 kırmızı. */
    public const UYARI_ESIKLERI = ['yesil' => 30, 'sari' => 15, 'kirmizi' => 7];

    protected static function booted(): void
    {
        static::creating(function (IsEkipmani $e): void {
            $e->qr_anahtar ??= static::yeniQrAnahtari();
        });

        static::saving(function (IsEkipmani $e): void {
            $e->muayene_periyodu_ay = max(1, (int) ($e->muayene_periyodu_ay ?: 12));

            if (! array_key_exists($e->sonuc, config('isg.periyodik_kontrol.sonuclar'))) {
                $e->sonuc = 'bekliyor';
            }

            // Son muayene tarihi girilmiş; sonraki vize elle verilmemişse periyottan türet.
            if ($e->son_muayene_tarihi && ! $e->getOriginal('sonraki_vize_tarihi') && ! $e->isDirty('sonraki_vize_tarihi')) {
                $e->sonraki_vize_tarihi = Carbon::parse($e->son_muayene_tarihi)->addMonths($e->muayene_periyodu_ay);
            }

            // Periyodik muayenesi olmayan ekipmanda vize tarihi tutulmaz (takvim/uyarılara girmez).
            if (! $e->son_muayene_tarihi || ! EkipmanKatalogu::periyodikMi($e->kategori)) {
                $e->sonraki_vize_tarihi = null;
            }
        });

        // Son kontrol bilgisi geçmişe yazılır; aynı tarihli kayıt varsa güncellenir
        // (satır içi düzenleme / "Kontrol Gir" / yeni ekipman — hepsi aynı yol).
        static::saved(function (IsEkipmani $e): void {
            $izlenen = ['son_muayene_tarihi', 'sonraki_vize_tarihi', 'muayene_yapan', 'rapor_no', 'sonuc'];

            if (! $e->son_muayene_tarihi || (! $e->wasRecentlyCreated && ! $e->wasChanged($izlenen))) {
                return;
            }

            $tarih = $e->son_muayene_tarihi->toDateString();
            $degerler = [
                'sonraki_tarih' => $e->sonraki_vize_tarihi?->toDateString(),
                'kontrol_eden' => $e->muayene_yapan,
                'rapor_no' => $e->rapor_no,
                'sonuc' => $e->sonuc,
            ];

            // whereDate: sürücüden bağımsız eşleşme (sqlite tarihi saatle saklar).
            $mevcut = $e->kontroller()->whereDate('kontrol_tarihi', $tarih)->first();
            $mevcut
                ? $mevcut->update($degerler)
                : $e->kontroller()->create(['kontrol_tarihi' => $tarih, ...$degerler]);
        });

        // Katalogda talimatı olan ekipman eklenince talimat firmaya otomatik eklenir
        // (yasal evrak kaçmasın — EkipmanKatalogu::talimatlariEkle; firmada varsa eklemez).
        // 12.11.2026: tip sonradan değişirse ya da başka firmaya taşınırsa da.
        static::saved(function (IsEkipmani $e): void {
            if (($e->wasRecentlyCreated || $e->wasChanged(['tip', 'firma_id', 'aktif']))
                && $e->aktif !== false && $e->firma && EkipmanKatalogu::talimatSablonu($e->tip)) {
                EkipmanKatalogu::talimatlariEkle($e->firma);
            }
        });

        // Canlı DB'de FK yok — geçmişi (ve rapor dosyalarını) burada temizle.
        static::deleting(function (IsEkipmani $e): void {
            $e->kontroller()->get()->each->delete();
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** Kontrol geçmişi — en yeni önce. */
    public function kontroller(): HasMany
    {
        return $this->hasMany(IsEkipmaniKontrolu::class)->orderByDesc('kontrol_tarihi');
    }

    /** Operatör günlük kontrolleri — en yeni önce. */
    public function gunlukKontroller(): HasMany
    {
        return $this->hasMany(IsEkipmaniGunlukKontrolu::class)->orderByDesc('kontrol_zamani');
    }

    /** Bakım / arıza kayıtları — en yeni önce. */
    public function bakimlar(): HasMany
    {
        return $this->hasMany(IsEkipmaniBakimi::class)->orderByDesc('acilis_zamani');
    }

    public static function yeniQrAnahtari(): string
    {
        do {
            $a = Str::random(32);
        } while (static::query()->where('qr_anahtar', $a)->exists());

        return $a;
    }

    /** QR anahtarı; eski kayıtta yoksa üretip sessizce kaydeder. */
    public function qrAnahtari(): string
    {
        if (blank($this->qr_anahtar)) {
            $this->qr_anahtar = static::yeniQrAnahtari();
            $this->saveQuietly();
        }

        return $this->qr_anahtar;
    }

    /** Operatör ekranı (giriş gerektirmez; PIN tanımlıysa PIN sorar). */
    public function operatorAdresi(): string
    {
        return route('ekipman.operator', $this->qrAnahtari());
    }

    /** QR görseli (SVG data URI — dompdf'te de çalışır). */
    public function qrDataUri(): string
    {
        return (new QRCode(new QROptions(['outputBase64' => true, 'addQuietzone' => true])))
            ->render($this->operatorAdresi());
    }

    public function pinGerekliMi(): bool
    {
        return filled($this->qr_pin);
    }

    public function pinDogruMu(?string $pin): bool
    {
        return ! $this->pinGerekliMi() || (filled($pin) && Hash::check($pin, $this->qr_pin));
    }

    /**
     * Periyodik kontrol uyarı rengi: süresi dolmuş ya da ≤7 gün kırmızı, ≤15 sarı, ≤30 yeşil;
     * daha uzak / takipsiz null.
     *
     * @return 'kirmizi'|'sari'|'yesil'|null
     */
    public function uyariSeviyesi(): ?string
    {
        $kalan = EkipmanKatalogu::periyodikMi($this->kategori) ? $this->kalanGun() : null;
        if ($kalan === null) {
            return null;
        }

        return match (true) {
            $kalan <= self::UYARI_ESIKLERI['kirmizi'] => 'kirmizi',
            $kalan <= self::UYARI_ESIKLERI['sari'] => 'sari',
            $kalan <= self::UYARI_ESIKLERI['yesil'] => 'yesil',
            default => null,
        };
    }

    /** @return 'edildi'|'bekliyor'|'yok' — tebliğ paketi basıldı mı, işverene imzalatıldı mı */
    public function tebligDurumu(): string
    {
        return $this->isverene_teblig_edildi ? 'edildi' : ($this->teblig_cikti_at ? 'bekliyor' : 'yok');
    }

    public function kategoriAdi(): string
    {
        return $this->kategori_adi
            ?: config('isg.periyodik_kontrol.kategoriler.'.$this->kategori.'.ad', $this->kategori ?? '—');
    }

    public function kategoriMevzuati(): ?string
    {
        return config('isg.periyodik_kontrol.kategoriler.'.$this->kategori.'.mevzuat');
    }

    public function sonucEtiketi(): string
    {
        return config('isg.periyodik_kontrol.sonuclar.'.$this->sonuc, $this->sonuc ?? '—');
    }

    /**
     * Vize (periyodik muayene geçerlilik) durumu.
     *
     * @return 'muaf'|'bekliyor'|'gecerli'|'yaklasan'|'dolmus'
     */
    public function vizeDurumu(): string
    {
        // Periyodik muayeneye tabi olmayan kategori (ör. Üretim & Atölye Makineleri).
        if (! EkipmanKatalogu::periyodikMi($this->kategori)) {
            return 'muaf';
        }

        if (! $this->sonraki_vize_tarihi) {
            return 'bekliyor';
        }

        $bugun = Carbon::today();

        if ($this->sonraki_vize_tarihi->lt($bugun)) {
            return 'dolmus';
        }

        $esik = KullaniciAyarlari::esik('ekipman');

        return $this->sonraki_vize_tarihi->lte($bugun->copy()->addDays($esik)) ? 'yaklasan' : 'gecerli';
    }

    public function vizeDurumEtiketi(): string
    {
        return [
            'muaf' => 'Periyodik Muayene Yok',
            'bekliyor' => 'Muayene Bekliyor',
            'gecerli' => 'Vizesi Geçerli',
            'yaklasan' => 'Vize Yaklaşan',
            'dolmus' => 'Süresi Dolan / Yasak',
        ][$this->vizeDurumu()];
    }

    public function kalanGun(): ?int
    {
        return $this->sonraki_vize_tarihi
            ? (int) Carbon::today()->diffInDays($this->sonraki_vize_tarihi, false)
            : null;
    }
}
