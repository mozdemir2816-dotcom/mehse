<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * İSG Kurulu toplantısı — isgpratik yardım/kurul-toplantisi rehberi.
 */
class KurulToplantisi extends Model
{
    use HasFactory;

    protected $table = 'kurul_toplantilari';

    protected $guarded = ['id'];

    protected $casts = [
        'tarih' => 'date',
        'sonraki_toplanti' => 'date',
        'katilimcilar' => 'array',
        'gundem' => 'array',
        'kararlar' => 'array',
    ];

    /** Karar durumları; "tamamlandi" dışındakiler kapanmamış sayılır ve sonraki toplantıya devreder. */
    public const KARAR_DURUMLARI = [
        'beklemede' => 'Beklemede',
        'devam_ediyor' => 'Devam ediyor',
        'tamamlandi' => 'Tamamlandı',
        'ertelendi' => 'Ertelendi',
    ];

    protected static function booted(): void
    {
        // Her karara tekil kimlik: devreden kararlar toplantılar arasında bununla anılır.
        static::saving(function (KurulToplantisi $t): void {
            if (is_array($t->kararlar) && $t->kararlar !== []) {
                $t->kararlar = static::kararlaraKimlikVer($t->kararlar);
            }
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $kararlar
     * @return array<int, array<string, mixed>>
     */
    public static function kararlaraKimlikVer(array $kararlar): array
    {
        return array_map(fn (array $k) => filled($k['id'] ?? null) ? $k : ['id' => Str::lower(Str::random(10))] + $k, $kararlar);
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function durumEtiketi(): string
    {
        return config("isg.kurul_toplantisi.durumlar.{$this->durum}", (string) $this->durum);
    }

    public function turEtiketi(): string
    {
        return config("isg.kurul_toplantisi.turler.{$this->tur}", (string) $this->tur);
    }

    /** "23:49 - 02:49" / "14:00" / null */
    public function saatAraligi(): ?string
    {
        return collect([$this->saat, $this->bitis_saati])->filter()->implode(' - ') ?: null;
    }

    public function katilanSayisi(): int
    {
        return collect($this->katilimcilar ?? [])->where('katildi', true)->count();
    }

    /**
     * Firma + yıl bazında sıradaki toplantı numarası — "2026/1", "2026/2" …
     * Yeni toplantı oluşturulurken atanır; kullanıcı sonradan düzeltebilir.
     */
    public static function sonrakiNo(Firma $firma, ?string $tarih = null): string
    {
        $yil = ($tarih ? Carbon::parse($tarih) : now())->year;

        $sayi = $firma->kurulToplantilari()->whereYear('tarih', $yil)->count() + 1;

        return $yil.'/'.$sayi;
    }

    /** Kimliksiz (eski) kararlara kimlik yazar. */
    public function kararKimlikleriniTamamla(): void
    {
        $kararlar = $this->kararlar ?? [];

        if (collect($kararlar)->contains(fn ($k) => blank($k['id'] ?? null))) {
            $this->kararlar = static::kararlaraKimlikVer($kararlar);
            $this->saveQuietly();
        }
    }

    /** @return Collection<int, KurulToplantisi> aynı firmanın bu toplantıdan önceki toplantıları (eskiden yeniye) */
    public function oncekiToplantilar(): Collection
    {
        if (! $this->tarih) {
            return collect();
        }

        return static::query()
            ->where('firma_id', $this->firma_id)
            ->whereNotNull('tarih')
            ->where(fn ($q) => $q->whereDate('tarih', '<', $this->tarih->toDateString())
                ->orWhere(fn ($q2) => $q2->whereDate('tarih', $this->tarih->toDateString())->where('id', '<', $this->id ?? 0)))
            ->orderBy('tarih')->orderBy('id')
            ->get();
    }

    /** Kararın verilen tarihteki durumu: o tarihten önceki son görüşme kaydı, yoksa kapanış tarihine göre. */
    public static function kararDurumuTarihte(array $karar, Carbon $tarih): string
    {
        $son = collect($karar['gorusmeler'] ?? [])
            ->filter(fn ($g) => filled($g['tarih'] ?? null) && $g['tarih'] < $tarih->toDateString())
            ->sortBy('tarih')->last();

        if ($son) {
            return (string) ($son['durum'] ?? 'beklemede');
        }

        $durum = (string) ($karar['durum'] ?? 'beklemede');

        // Bu toplantıdan sonra kapanmış bir karar o gün hâlâ açıktı.
        if ($durum === 'tamamlandi' && filled($karar['kapanis_tarihi'] ?? null) && $karar['kapanis_tarihi'] >= $tarih->toDateString()) {
            return 'beklemede';
        }

        return $durum;
    }

    /**
     * Önceki toplantılardan bu toplantıya devreden (o gün kapanmamış) kararlar.
     * "durum" / "not" bu toplantıda girilen görüşme; girilmediyse devreden durum.
     *
     * @return array<int, array<string, mixed>>
     */
    public function devredenKararlar(): array
    {
        if (! $this->tarih) {
            return [];
        }

        $devreden = [];

        foreach ($this->oncekiToplantilar() as $kaynak) {
            $kaynak->kararKimlikleriniTamamla();

            foreach ($kaynak->kararlar ?? [] as $k) {
                $once = static::kararDurumuTarihte($k, $this->tarih);

                if ($once === 'tamamlandi') {
                    continue;
                }

                $gorusme = collect($k['gorusmeler'] ?? [])->firstWhere('toplanti_id', $this->id);

                $devreden[] = [
                    'kaynak_id' => $kaynak->id,
                    'kaynak_no' => $kaynak->toplanti_no,
                    'kaynak_tarih' => $kaynak->tarih,
                    'id' => $k['id'] ?? null,
                    'gundem' => $k['gundem_maddesi'] ?? null,
                    'karar' => $k['karar_metni'] ?? '',
                    'sorumlu' => $k['sorumlu'] ?? null,
                    'termin' => $k['termin'] ?? null,
                    'gecikti' => filled($k['termin'] ?? null) && $k['termin'] < $this->tarih->toDateString(),
                    'onceki_durum' => $once,
                    'durum' => $gorusme['durum'] ?? $once,
                    'not' => $gorusme['not'] ?? null,
                ];
            }
        }

        return $devreden;
    }

    /**
     * Devreden bir kararın bu toplantıdaki durumunu / görüşme notunu kaydeder (kaynak toplantının
     * karar kaydına yazılır; durum + kapanış tarihi de güncellenir). Verilmeyen alan korunur.
     */
    public function devredenGuncelle(int $kaynakId, string $kararId, ?string $durum = null, ?string $not = null): bool
    {
        $kaynak = static::query()->where('firma_id', $this->firma_id)->find($kaynakId);

        if (! $kaynak || ($durum !== null && ! isset(self::KARAR_DURUMLARI[$durum]))) {
            return false;
        }

        $kararlar = $kaynak->kararlar ?? [];
        $i = collect($kararlar)->search(fn ($k) => ($k['id'] ?? null) === $kararId);

        if ($i === false) {
            return false;
        }

        $k = $kararlar[$i];
        $gorusmeler = collect($k['gorusmeler'] ?? []);
        $mevcut = $gorusmeler->firstWhere('toplanti_id', $this->id) ?? [];
        $yeni = [
            'toplanti_id' => $this->id,
            'tarih' => $this->tarih?->toDateString(),
            'durum' => $durum ?? ($mevcut['durum'] ?? static::kararDurumuTarihte($k, $this->tarih ?? now())),
            'not' => $not ?? ($mevcut['not'] ?? null),
        ];

        $k['gorusmeler'] = $gorusmeler->reject(fn ($g) => ($g['toplanti_id'] ?? null) === $this->id)->push($yeni)->values()->all();
        $k['durum'] = $yeni['durum'];
        $k['kapanis_tarihi'] = $yeni['durum'] === 'tamamlandi' ? $this->tarih?->toDateString() : null;
        $kararlar[$i] = $k;
        $kaynak->update(['kararlar' => $kararlar]);

        return true;
    }

    /**
     * Metin: devreden bir kararın bu toplantıdaki durumu + görüşme notu (tutanak çıktıları için).
     *
     * @param  array<string, mixed>  $d  devredenKararlar() satırı
     */
    public static function devredenOzet(array $d): string
    {
        return collect([self::KARAR_DURUMLARI[$d['durum']] ?? $d['durum'], $d['not'] ?? null])->filter()->implode(' — ');
    }
}
