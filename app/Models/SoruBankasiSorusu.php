<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Soru Bankası sorusu — kalıcı, kaynaklı, onaylı İSG sınav sorusu. Eğitim
 * Soruları ve Uzaktan Eğitim sınavları yalnızca `durum = onaylandi` soruları
 * havuzdan çeker.
 */
class SoruBankasiSorusu extends Model
{
    protected $table = 'soru_bankasi_sorulari';

    protected $guarded = ['id'];

    protected $casts = [
        'secenekler' => 'array',
        'kaynaklar' => 'array',
        'dogru_index' => 'integer',
        'surum' => 'integer',
        'onay_tarihi' => 'datetime',
    ];

    /** "41, 43.21" → ",41,4321," (rakam ön ekleri; boşsa null). */
    public static function naceOnekleriniHazirla(?string $girdi): ?string
    {
        $onekler = collect(preg_split('/[\s,;]+/', (string) $girdi, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($p) => preg_replace('/\D/', '', $p))
            ->filter(fn ($p) => strlen($p) >= 2 && strlen($p) <= 6)
            ->unique()->values();

        return $onekler->isEmpty() ? null : ','.$onekler->implode(',').',';
    }

    /** ",41,4321," → ["41", "43.21"] (görüntü için noktalı). */
    public function naceKapsami(): array
    {
        return collect(explode(',', trim((string) $this->nace_onekleri, ',')))
            ->filter()
            ->map(fn ($p) => implode('.', str_split($p, 2)))
            ->values()->all();
    }

    /** Firma NACE'sinin ön ekleri: "43.21.01" → ["43", "432", "4321", "43210", "432101"]. */
    public static function naceOnEkleri(?string $nace): array
    {
        $r = preg_replace('/\D/', '', (string) $nace);

        return strlen($r) < 2 ? [] : array_map(fn ($u) => substr($r, 0, $u), range(2, min(6, strlen($r))));
    }

    /**
     * Sınava uygun kapsam: ortak (sektör ve NACE boş) + seçili sektör +
     * NACE ön eki firmanın koduyla eşleşen sorular.
     */
    public function scopeKapsamaUygun(Builder $q, ?string $sektor, ?string $nace = null): Builder
    {
        $onekler = static::naceOnEkleri($nace);

        return $q->where(function (Builder $w) use ($sektor, $onekler) {
            $w->where(fn (Builder $o) => $o->whereNull('sektor_anahtari')->whereNull('nace_onekleri'));
            if ($sektor) {
                $w->orWhere('sektor_anahtari', $sektor);
            }
            foreach ($onekler as $p) {
                $w->orWhere('nace_onekleri', 'like', '%,'.$p.',%');
            }
        });
    }

    /**
     * Doğrulanabilir kaynaklar: [{ad, url, madde, tarih}]. Eski tek satırlık
     * `kaynak` alanı varsa tek kaynak olarak döner.
     *
     * @return array<int, array{ad: string, url: ?string, madde: ?string, tarih: ?string}>
     */
    public function kaynakListesi(): array
    {
        $liste = collect($this->kaynaklar ?? [])->filter(fn ($k) => filled($k['ad'] ?? null))
            ->map(fn ($k) => ['ad' => $k['ad'], 'url' => $k['url'] ?? null, 'madde' => $k['madde'] ?? null, 'tarih' => $k['tarih'] ?? null])
            ->values()->all();

        return $liste ?: ($this->kaynak ? [['ad' => $this->kaynak, 'url' => null, 'madde' => null, 'tarih' => null]] : []);
    }

    public function kodEtiketi(): string
    {
        return ($this->soru_kodu ?: 'SB-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT)).' v'.($this->surum ?: 1);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Sistem havuzu (user_id NULL) + verilen uzmanın kendi soruları. */
    public function scopeErisilebilir(Builder $q, ?int $userId): Builder
    {
        return $q->where(fn (Builder $s) => $s->whereNull('user_id')->orWhere('user_id', $userId));
    }

    public function scopeOnayli(Builder $q): Builder
    {
        return $q->where('durum', 'onaylandi');
    }

    public function dogruSecenek(): ?string
    {
        return $this->secenekler[$this->dogru_index] ?? null;
    }

    public function sektorEtiketi(): string
    {
        return $this->sektor_anahtari
            ? (config('isg.risk_ai.sektorler.'.$this->sektor_anahtari.'.ad') ?? $this->sektor_anahtari)
            : 'Genel';
    }

    public function konuEtiketi(): string
    {
        return config('isg.soru_bankasi.konular.'.$this->konu, $this->konu ?? '—');
    }

    public function zorlukEtiketi(): string
    {
        return config('isg.soru_bankasi.zorluklar.'.$this->zorluk, $this->zorluk ?? '—');
    }

    public function durumEtiketi(): string
    {
        return config('isg.soru_bankasi.durumlar.'.$this->durum, $this->durum ?? '—');
    }

    /**
     * Eğitim Soruları / LMS sınav biçimine indirger.
     *
     * @return array{soru: string, secenekler: array<int, string>, dogru_index: int, kaynak: ?string, aciklama: ?string}
     */
    public function sinavBicimi(): array
    {
        return [
            'soru' => $this->soru,
            'secenekler' => $this->secenekler,
            'dogru_index' => $this->dogru_index,
            'kaynak' => collect($this->kaynakListesi())->map(fn ($k) => trim($k['ad'].' '.($k['madde'] ?? '')))->implode('; ') ?: null,
            'aciklama' => $this->aciklama,
        ];
    }
}
