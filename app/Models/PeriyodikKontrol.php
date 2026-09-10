<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Periyodik Kontrol künyesi — firma başına bir kayıt. Ekipmanlar artık ayrı
 * `IsEkipmani` kayıtlarında tutulur (isgpratik "Ekipman & Periyodik Kontrol
 * Motoru"); bu model yalnız genel notu + belge kaydı (Profilim > Raporlar,
 * Firma Evrak Paketi) için bir kapsayıcıdır ve özet/durum hesaplarını
 * `firma->isEkipmanlari`'ndan türetir.
 */
class PeriyodikKontrol extends Model
{
    use HasFactory;

    protected $table = 'periyodik_kontroller';

    protected $guarded = ['id'];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaIcin(Firma $firma): self
    {
        $kayit = static::firstOrNew(['firma_id' => $firma->id]);

        if (! $kayit->exists) {
            $kayit->save();
        }

        return $kayit;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, IsEkipmani> */
    public function ekipmanlar()
    {
        return $this->firma
            ? $this->firma->isEkipmanlari()->where('aktif', true)->orderBy('kategori')->orderBy('ekipman_adi')->get()
            : IsEkipmani::query()->whereRaw('1 = 0')->get();
    }

    /** En az bir ekipmana muayene tarihi girilmiş mi (Kontrol Merkezi kriteri). */
    public function baslatilmisMi(): bool
    {
        return (bool) $this->firma?->isEkipmanlari()->whereNotNull('son_muayene_tarihi')->exists();
    }

    /** Vizesi geçmiş / eşik gün içinde dolacak ekipmanlar. */
    public function yaklasanlar(): array
    {
        return $this->ekipmanlar()
            ->filter(fn (IsEkipmani $e) => in_array($e->vizeDurumu(), ['yaklasan', 'dolmus'], true))
            ->values()
            ->all();
    }

    public function ozet(): array
    {
        $ekipmanlar = $this->ekipmanlar();

        return [
            'toplam' => $ekipmanlar->count(),
            'gecerli' => $ekipmanlar->filter(fn (IsEkipmani $e) => $e->vizeDurumu() === 'gecerli')->count(),
            'yaklasan' => $ekipmanlar->filter(fn (IsEkipmani $e) => $e->vizeDurumu() === 'yaklasan')->count(),
            'dolmus' => $ekipmanlar->filter(fn (IsEkipmani $e) => $e->vizeDurumu() === 'dolmus')->count(),
            'bekleyen' => $ekipmanlar->filter(fn (IsEkipmani $e) => $e->vizeDurumu() === 'bekliyor')->count(),
        ];
    }
}
