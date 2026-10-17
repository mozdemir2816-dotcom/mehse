<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Sektörel risk şablonu. Bir risk seti sektöre etiketlenip saklanır; Risk
 * Sihirbazında aynı sektör seçilince tek tıkla uygulanır ("Şablonlar" yöntemi).
 */
class RiskSablonu extends Model
{
    use HasFactory;

    protected $table = 'risk_sablonlari';

    protected $guarded = ['id'];

    protected $casts = [
        'maddeler' => 'array',
        'paylasildi' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Kullanıcının kendi + paylaşılan şablonları. */
    public function scopeGorunur(Builder $q, int $userId): Builder
    {
        return $q->where(fn (Builder $b) => $b->where('user_id', $userId)->orWhere('paylasildi', true));
    }

    public function sektorEtiketi(): string
    {
        return config('isg.risk_ai.sektorler.'.$this->sektor.'.ad')
            ?? $this->sektor_adi
            ?? 'Sektörsüz';
    }

    public function maddeSayisi(): int
    {
        return count($this->maddeler ?? []);
    }

    /**
     * Gruplama ifadesi (SQL): sektör kodu varsa o, yoksa kırpılmış serbest sektör metni.
     * Baştaki/sondaki boşluk farkı tek başlıkta toplanır. Büyük/küçük harf farkı bilerek birleştirilmez:
     * SQL'in LOWER() fonksiyonu Türkçe harfleri (Ç, ı, İ) MySQL ve SQLite'ta tutarlı küçültmez.
     * Tablo gruplaması ve grupAnahtari() aynı kuralı kullanır.
     */
    public const GRUP_IFADESI = "COALESCE(NULLIF(sektor, ''), TRIM(COALESCE(sektor_adi, '')))";

    /** Gruplama anahtarı (PHP tarafı): GRUP_IFADESI ile aynı kural. */
    public function grupAnahtari(): string
    {
        return $this->sektor ?: trim((string) $this->sektor_adi);
    }

    /**
     * Kullanıcının kendi şablonlarını gruba göre toplar: anahtar => "Etiket (n şablon)".
     *
     * @return Collection<string, string>
     */
    public static function benimGruplari(int $userId): Collection
    {
        return static::query()->where('user_id', $userId)->get()
            ->groupBy(fn (self $s) => $s->grupAnahtari())
            ->map(fn (Collection $grup) => $grup->first()->sektorEtiketi().' ('.$grup->count().' şablon)');
    }

    /** Yalnız $userId'nin sahibi olduğu kayıtları siler; silinen sayısını döndürür (başkasının şablonuna dokunmaz). */
    public static function topluSil(iterable $kayitlar, int $userId): int
    {
        $silinecek = collect($kayitlar)->filter(fn (self $s) => $s->user_id === $userId);
        $silinecek->each->delete();

        return $silinecek->count();
    }

    /**
     * Şablon maddelerini sihirbaza aktarılacak biçimde (taze anahtarlarla) döndürür.
     *
     * @return array<int, array<string, mixed>>
     */
    public function maddeleriKopyala(): array
    {
        return collect($this->maddeler ?? [])->map(fn (array $m) => array_merge($m, [
            'anahtar' => 'sbl-'.substr(md5(uniqid('', true)), 0, 10),
            'kaynak' => 'sablon',
        ]))->all();
    }

    public function kullanildi(): void
    {
        $this->increment('kullanim_sayisi');
    }

    /**
     * @param  array<int, array<string, mixed>>  $maddeler  sihirbaz "secilenler" dizisi
     */
    public static function olustur(User $user, string $ad, ?string $sektor, ?string $sektorAdi, string $yontem, array $maddeler): self
    {
        return static::create([
            'user_id' => $user->id,
            'ad' => Str::limit(trim($ad) ?: 'Adsız şablon', 120, ''),
            'sektor' => $sektor,
            'sektor_adi' => $sektor ? null : $sektorAdi,
            'yontem' => $yontem,
            'maddeler' => array_values($maddeler),
        ]);
    }
}
