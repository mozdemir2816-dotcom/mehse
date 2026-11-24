<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Taşeron / alt işveren firması — bir işyerinin (firma) altında çalışır.
 * Çalışanları ana personel listesine eklenmez. Uygunluk: sözleşme süresi,
 * türe göre zorunlu belgeler, belge geçerliliği, aktif çalışan ve çalışan
 * eğitim / sağlık geçerliliği (bkz. eksikler()).
 */
class Taseron extends Model
{
    /** Taşeron onay / geçerlilik dönemi (ay): her 6 ayda bir yeniden onaylanır. */
    public const ONAY_SURESI_AY = 6;

    protected $table = 'taseronlar';

    protected $guarded = ['id'];

    protected $attributes = ['aktif' => true, 'tur' => 'alt_isveren'];

    protected $casts = [
        'sozlesme_baslangic' => 'date',
        'sozlesme_bitis' => 'date',
        'aktif' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Canlı DB'de FK yok — alt kayıtları burada temizle.
        static::deleting(function (Taseron $t): void {
            $t->calisanlar()->delete();
            $t->belgeler()->get()->each->delete();
            $t->isIzinleri()->detach();
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function calisanlar(): HasMany
    {
        return $this->hasMany(TaseronCalisani::class)->orderBy('ad_soyad');
    }

    public function belgeler(): HasMany
    {
        return $this->hasMany(TaseronBelgesi::class)->orderBy('tur');
    }

    public function isIzinleri(): BelongsToMany
    {
        return $this->belongsToMany(IsIzinFormu::class, 'taseron_is_izinleri', 'taseron_id', 'is_izin_formu_id')->withTimestamps();
    }

    public function turEtiketi(): string
    {
        return config('isg.taseron.turler.'.$this->tur, $this->tur);
    }

    /** Çalışan eğitim / sağlık periyodu için: taşeronun sınıfı, yoksa işyerinin. */
    public function etkinTehlikeSinifi(): ?string
    {
        return $this->tehlike_sinifi ?: $this->firma?->tehlike_sinifi;
    }

    /** gecerli | yaklasan | bitti | belirsiz */
    public function sozlesmeDurumu(): string
    {
        if (! $this->sozlesme_bitis) {
            return 'belirsiz';
        }

        $kalan = (int) now()->startOfDay()->diffInDays($this->sozlesme_bitis, false);

        return match (true) {
            $kalan < 0 => 'bitti',
            $kalan <= config('isg.taseron.yaklasan_gun', 30) => 'yaklasan',
            default => 'gecerli',
        };
    }

    /** 6 aylık taşeron onayı süresi doldu mu? */
    public function onaySuresiDolduMu(): bool
    {
        return $this->sozlesmeDurumu() === 'bitti';
    }

    /** Taşeronu bugünden itibaren 6 ay süreyle onaylar / yeniler. */
    public function altiAyOnayla(): void
    {
        $baslangic = now()->startOfDay();
        $this->update([
            'sozlesme_baslangic' => $baslangic->toDateString(),
            'sozlesme_bitis' => $baslangic->copy()->addMonthsNoOverflow(self::ONAY_SURESI_AY)->toDateString(),
            'aktif' => true,
        ]);
    }

    /** @return array<int, string> türüne göre zorunlu olup hiç kaydı olmayan belgelerin adları */
    public function eksikZorunluBelgeler(): array
    {
        $mevcut = $this->belgeler->pluck('tur')->unique()->all();

        return collect(config('isg.taseron.belge_turleri'))
            ->filter(fn (array $b, string $anahtar) => in_array($this->tur, $b['zorunlu'], true) && ! in_array($anahtar, $mevcut, true))
            ->map(fn (array $b) => $b['ad'])
            ->values()
            ->all();
    }

    /**
     * Uygunluk eksikleri — isgsuite "Eksik var" kutusu. Pasif taşeron için boş.
     *
     * @return array<int, string>
     */
    public function eksikler(): array
    {
        if (! $this->aktif) {
            return [];
        }

        $e = [];

        $ziyaretci = $this->tur === 'ziyaretci_firma';
        $sure = $ziyaretci ? 'Ziyaret süresi' : 'Sözleşme';

        $sozlesme = $this->sozlesmeDurumu();
        if ($sozlesme === 'bitti') {
            $e[] = $sure.' '.$this->sozlesme_bitis->format('d.m.Y').' tarihinde bitmiş';
        } elseif ($sozlesme === 'yaklasan') {
            $e[] = $sure.' '.$this->sozlesme_bitis->format('d.m.Y').' tarihinde bitiyor';
        } elseif ($sozlesme === 'belirsiz' && ! $ziyaretci) {
            // Ziyaretçi firmada bitiş tarihi zorunlu değil (tek seferlik ziyaret olabilir).
            $e[] = 'Sözleşme bitiş tarihi girilmemiş';
        }

        if ($this->belgeler->isEmpty()) {
            $e[] = 'Belge kaydı bulunmuyor';
        } elseif ($eksik = $this->eksikZorunluBelgeler()) {
            $e[] = 'Eksik zorunlu belge: '.implode(', ', $eksik);
        }

        $dolan = $this->belgeler->filter(fn (TaseronBelgesi $b) => $b->durum() === 'dolmus');
        if ($dolan->isNotEmpty()) {
            $e[] = 'Süresi dolmuş belge: '.$dolan->map->etiket()->implode(', ');
        }

        $aktifler = $this->calisanlar->where('aktif', true);
        if ($aktifler->isEmpty()) {
            if (! $ziyaretci) {
                $e[] = 'Aktif taşeron çalışanı bulunmuyor';
            }
        } else {
            $egitimsiz = $aktifler->reject->egitimGecerliMi();
            if ($egitimsiz->isNotEmpty()) {
                $e[] = $egitimsiz->count().' çalışanın İSG eğitimi yok / süresi dolmuş';
            }
            $raporsuz = $aktifler->reject->saglikGecerliMi();
            if ($raporsuz->isNotEmpty()) {
                $e[] = $raporsuz->count().' çalışanın sağlık raporu yok / süresi dolmuş';
            }
        }

        return $e;
    }
}
