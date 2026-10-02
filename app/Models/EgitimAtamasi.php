<?php

namespace App\Models;

use App\Support\EgitimTakibi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bir çalışana atanmış uzaktan eğitim. İlerleme (izlenen dersler) + sınav
 * sonuçları buna bağlı; `durum` bunlardan türetilir.
 */
class EgitimAtamasi extends Model
{
    use HasFactory;

    protected $table = 'egitim_atamalari';

    protected $guarded = ['id'];

    protected $casts = [
        'atandi_at' => 'datetime',
        'son_tarih' => 'date',
        'tamamlandi_at' => 'datetime',
    ];

    public function paket(): BelongsTo
    {
        return $this->belongsTo(EgitimPaketi::class, 'egitim_paketi_id');
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function atayan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atayan_user_id');
    }

    protected static function booted(): void
    {
        static::deleting(fn (EgitimAtamasi $a) => $a->girisler()->delete());
    }

    /** Bu eğitimin açıldığı tarihler (giriş günlüğü). */
    public function girisler(): HasMany
    {
        return $this->hasMany(EgitimGirisi::class)->orderBy('giris_at');
    }

    public function ilerlemeler(): HasMany
    {
        return $this->hasMany(EgitimDersIlerlemesi::class);
    }

    public function sinavSonuclari(): HasMany
    {
        return $this->hasMany(EgitimSinavSonucu::class)->orderByDesc('deneme_no');
    }

    /** İzlenmiş ders sayısı / toplam ders. */
    public function izlenenDersSayisi(): int
    {
        return $this->ilerlemeler()->where('izlendi', true)->count();
    }

    public function toplamDersSayisi(): int
    {
        return $this->paket->dersler()->count();
    }

    public function ilerlemeYuzdesi(): int
    {
        $toplam = $this->toplamDersSayisi();

        return $toplam > 0 ? (int) round($this->izlenenDersSayisi() / $toplam * 100) : 0;
    }

    public function tumDerslerIzlendiMi(): bool
    {
        $toplam = $this->toplamDersSayisi();

        return $toplam > 0 && $this->izlenenDersSayisi() >= $toplam;
    }

    public function sonSinav(): ?EgitimSinavSonucu
    {
        return $this->sinavSonuclari()->first();
    }

    public function basariliMi(): bool
    {
        return $this->sinavSonuclari()->where('gecti', true)->exists();
    }

    public function sonrakiDeneme(): int
    {
        return (int) $this->sinavSonuclari()->max('deneme_no') + 1;
    }

    /** İlerleme/sınav durumuna göre `durum` alanını günceller. */
    public function durumuTazele(): void
    {
        $yeni = match (true) {
            $this->basariliMi() => 'tamamlandi',
            $this->sinavSonuclari()->exists() => 'basarisiz',
            $this->izlenenDersSayisi() > 0 => 'devam',
            default => 'atandi',
        };

        if ($yeni === 'tamamlandi' && ! $this->tamamlandi_at) {
            $this->tamamlandi_at = now();
        }

        $yeniTamamlandi = $yeni === 'tamamlandi' && $this->durum !== 'tamamlandi';

        if ($this->durum !== $yeni || $this->isDirty('tamamlandi_at')) {
            $this->durum = $yeni;
            $this->save();
        }

        if ($yeniTamamlandi) {
            $this->egitimKaydinaIsle();
        }
    }

    /**
     * Temel İSG eğitimi (ilk defa / yenileme) tamamlanınca çalışanın Eğitim
     * Kayıtları'na tamamlanma tarihiyle işlenir — personel dosyası, eğitim
     * matrisi ve Yenileme Takibi bunu görür. Daha yeni bir kayıt varsa
     * dokunulmaz. İşbaşı eğitimi bu listede ayrı tür olmadığından işlenmez.
     */
    public function egitimKaydinaIsle(): ?EgitimKaydi
    {
        if (! $this->tamamlandi_at || ! in_array($this->egitim_turu, ['ilk_defa', 'yenileme'], true)) {
            return null;
        }

        $kayit = EgitimKaydi::firstOrNew(['calisan_id' => $this->calisan_id, 'tur' => EgitimTakibi::TEMEL_TUR]);

        if ($kayit->exists && $kayit->tarih && $kayit->tarih->gte($this->tamamlandi_at->copy()->startOfDay())) {
            return $kayit;
        }

        $kayit->fill([
            'tarih' => $this->tamamlandi_at->toDateString(),
            'notlar' => 'Uzaktan eğitim: '.($this->paket?->ad ?? '').' (otomatik)',
        ])->save();

        return $kayit;
    }

    public function durumEtiketi(): string
    {
        return config('isg.uzaktan_egitim.durumlar.'.$this->durum, $this->durum);
    }

    public function gecikti(): bool
    {
        return $this->son_tarih && $this->son_tarih->isPast() && ! $this->basariliMi();
    }
}
