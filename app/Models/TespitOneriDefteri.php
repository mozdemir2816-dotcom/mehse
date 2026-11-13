<?php

namespace App\Models;

use App\Support\BulguHavuzu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tespit ve Öneri Defteri — isgpratik 65-66.jpg. Firma başına bir kayıt;
 * madde listesi zamanla biriktirilir.
 */
class TespitOneriDefteri extends Model
{
    use HasFactory;

    protected $table = 'tespit_oneri_defterleri';

    protected $guarded = ['id'];

    /** Önerisi boş maddede PDF ve canlı önizlemenin gösterdiği metin (02.11.2026). */
    public const VARSAYILAN_ONERI = 'Mevzuata uygun koruyucu tedbirler ivedilikle alınmalı ve uygunsuzluk giderilmelidir.';

    protected $casts = [
        'maddeler' => 'array',
    ];

    protected static function booted(): void
    {
        // Maddeler ortak saha bulgularına bağlanır (BulguHavuzu).
        static::saved(fn (TespitOneriDefteri $d) => BulguHavuzu::esle($d));
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /**
     * Firmaya önceden bildirilen tedbirleri "Ön Bilgilendirme" maddesi olarak ekler
     * (BilgilendirmeYazisiOkuyucu çıktısı). Saha bulgusu havuzuna düşmez (BulguHavuzu).
     *
     * @param  array{tespit: string, oneri: string, dayanak?: ?string, tarih?: ?string}  $yazi
     */
    public function bilgilendirmeEkle(array $yazi, ?int $onBilgilendirmeId = null): void
    {
        $this->update(['maddeler' => [...($this->maddeler ?? []), [
            'tur' => 'bilgilendirme',
            'tarih' => $yazi['tarih'] ?? null,
            'tespit' => $yazi['tespit'],
            'oneri' => $yazi['oneri'],
            'dayanak' => $yazi['dayanak'] ?? null,
            'oncelik' => 'orta',
            'foto_yolu' => null,
            'on_bilgilendirme_id' => $onBilgilendirmeId,
        ]]]);
    }

    /**
     * Bugün açılan ve deftere henüz bağlanmamış saha bulgularını resmî dille deftere ekler.
     * Hem Tespit ve Öneri Defteri sayfası hem Ziyaret Modu "Ziyareti bitir" kullanır (13.11.2026).
     *
     * @return int eklenen madde sayısı
     */
    public static function bugununBulgulariniEkle(Firma $firma): int
    {
        $defter = static::firmaIcin($firma);
        $bugun = BulguHavuzu::eklenebilirler($firma->id, $defter->maddeler ?? [])
            ->filter(fn (SahaBulgusu $b) => $b->created_at?->isToday())
            ->sortBy('id')->values();

        if ($bugun->isNotEmpty()) {
            $defter->update(['maddeler' => [...($defter->maddeler ?? []), ...$bugun->map(fn (SahaBulgusu $b) => \App\Support\BulguDonusturucu::tespitOneriResmiMaddesi($b))->all()]]);
        }

        return $bugun->count();
    }

    public static function firmaIcin(Firma $firma): self
    {
        $defter = static::firstOrNew(['firma_id' => $firma->id]);

        if (! $defter->exists) {
            $defter->maddeler = [];
            $defter->save();
        }

        return $defter;
    }
}
