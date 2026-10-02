<?php

namespace App\Models;

use App\Support\KullaniciAyarlari;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
    ];

    protected static function booted(): void
    {
        static::saving(function (IsEkipmani $e): void {
            $e->muayene_periyodu_ay = max(1, (int) ($e->muayene_periyodu_ay ?: 12));

            if (! array_key_exists($e->sonuc, config('isg.periyodik_kontrol.sonuclar'))) {
                $e->sonuc = 'bekliyor';
            }

            // Son muayene tarihi girilmiş; sonraki vize elle verilmemişse periyottan türet.
            if ($e->son_muayene_tarihi && ! $e->getOriginal('sonraki_vize_tarihi') && ! $e->isDirty('sonraki_vize_tarihi')) {
                $e->sonraki_vize_tarihi = Carbon::parse($e->son_muayene_tarihi)->addMonths($e->muayene_periyodu_ay);
            }

            if (! $e->son_muayene_tarihi) {
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
     * @return 'bekliyor'|'gecerli'|'yaklasan'|'dolmus'
     */
    public function vizeDurumu(): string
    {
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
