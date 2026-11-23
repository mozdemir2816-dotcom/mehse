<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Uzaktan Eğitim Paketi — sıralı video dersleri + final sınav havuzu. İSG uzmanı
 * oluşturur, firmadaki çalışanlara atar (EgitimAtamasi). Çalışan portalda izler.
 */
class EgitimPaketi extends Model
{
    use HasFactory;

    protected $table = 'egitim_paketleri';

    protected $guarded = ['id'];

    protected $casts = [
        'gecme_puani' => 'integer',
        'video_zorunlu_yuzde' => 'integer',
        'sinav_soru_sayisi' => 'integer',
        'aktif' => 'boolean',
        'paylasildi' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (EgitimPaketi $p): void {
            $p->kod ??= \App\Support\BelgeNumarasi::sonraki(static::class, 'kod', 'UE-'.now()->format('Y').'-', 3);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Çalışana gösterilen (videosu bağlı) dersler. Önce modülsüz (eski) dersler, sonra Modül 1→4;
     * her modülde kayıtlı sıra (1, 2, 3 …). Video bekleyen taslak konular burada YOKTUR.
     */
    public function dersler(): HasMany
    {
        return $this->tumDersler()->whereNotNull('video_url')->where('video_url', '!=', '');
    }

    /** Taslak (video bekleyen) konu başlıkları dahil tüm dersler — yönetim ekranı kullanır. */
    public function tumDersler(): HasMany
    {
        return $this->hasMany(EgitimDersi::class)->orderByRaw('COALESCE(modul, 0)')->orderBy('sira')->orderBy('id');
    }

    /** Video bağlanmayı bekleyen konu başlığı sayısı. */
    public function videoBekleyenSayisi(): int
    {
        return $this->tumDersler()->where(fn ($q) => $q->whereNull('video_url')->orWhere('video_url', ''))->count();
    }

    public function sorular(): HasMany
    {
        return $this->hasMany(EgitimPaketiSorusu::class);
    }

    public function atamalar(): HasMany
    {
        return $this->hasMany(EgitimAtamasi::class);
    }

    /** Kullanıcının kendi + paylaşılan paketleri. */
    public function scopeGorunur(Builder $q, int $userId): Builder
    {
        return $q->where(fn (Builder $b) => $b->where('user_id', $userId)->orWhere('paylasildi', true));
    }

    public function sektorEtiketi(): ?string
    {
        return $this->sektor ? config('isg.risk_ai.sektorler.'.$this->sektor.'.ad', $this->sektor) : null;
    }

    public function toplamSureDk(): int
    {
        return (int) ceil($this->dersler->sum('sure_sn') / 60);
    }

    /** Planlanan sınav süreleri (dk): ön test + final (config uzaktan_egitim.sinav_sure_dk). */
    public static function sinavSureDk(?string $tur = null): int
    {
        $sureler = (array) config('isg.uzaktan_egitim.sinav_sure_dk', []);

        return $tur ? (int) ($sureler[$tur] ?? 0) : (int) array_sum($sureler);
    }

    /** Online eğitimin toplam süresi: videolar + ön test + final (Md.13 karşılaştırmasında bu kullanılır). */
    public function onlineSureDk(): int
    {
        return $this->toplamSureDk() + static::sinavSureDk();
    }

    /**
     * Md.13 + 08.11.2026 / 23.11.2026 kararları: online kısım paket türüne göre `uzaktan_hedef_saat`
     * (Temel İSG Eğitimi 8 ders saati = 360 dk; Temel İSG Yenileme Eğitimi 4 ders saati = 180 dk).
     * Yüz yüze kısım: tehlikeli / çok tehlikelide 4. konu (`yuz_yuze_saat`) ve her durumda yasal
     * toplamın (`saat`) online ile karşılanmayan kalanı — 4 saatlik yenilemede her sınıfta 4 saat.
     * `$paketTuru` verilmezse eski davranış (tek 8 saatlik paket) korunur.
     *
     * @return array{saat: int, gereken_dk: int, dorduncu_yuz_yuze: bool, yuz_yuze_saat: int, online_saat: int}
     */
    public static function gerekenUzaktanSure(string $sinif, string $tur = 'ilk_defa', ?string $paketTuru = null): array
    {
        $saat = (int) ($tur === 'ilk_defa'
            ? config("isg.egitim.sureler.ilk.{$sinif}.saat", 8)
            : config('isg.egitim.sureler.tekrar.saat', 8));
        $dorduncuYuzYuze = in_array($sinif, config('isg.uzaktan_egitim.yuz_yuze_dorduncu_konu', []), true);
        $dorduncu = $tur === 'ilk_defa' ? (int) config("isg.uzaktan_egitim.dorduncu_konu_saat.{$sinif}", 0) : intdiv($saat, 4);
        $yuzYuze = $dorduncuYuzYuze
            ? max($dorduncu, (int) config('isg.uzaktan_egitim.yuz_yuze_saat.'.($tur === 'ilk_defa' ? 'ilk_defa' : 'yenileme').".{$sinif}", $dorduncu))
            : 0;
        $online = static::onlineHedefSaat($paketTuru ?? 'ilk_defa');
        // Online ile karşılanmayan yasal süre yüz yüze tamamlanır (4 saatlik yenilemede 8 − 4 = 4).
        if ($paketTuru !== null) {
            $yuzYuze = max($yuzYuze, $saat - $online);
        }

        return [
            'saat' => $saat,
            'gereken_dk' => $online * (int) config('isg.uzaktan_egitim.ders_saati_dk', 45),
            'dorduncu_yuz_yuze' => $dorduncuYuzYuze,
            'yuz_yuze_saat' => $yuzYuze,
            'online_saat' => $online,
        ];
    }

    /** Paket türünün online hedefi (ders saati). Eski tek değerli ayar da okunur. */
    public static function onlineHedefSaat(string $paketTuru = 'ilk_defa'): int
    {
        $ayar = config('isg.uzaktan_egitim.uzaktan_hedef_saat', 8);

        return (int) (is_array($ayar) ? ($ayar[$paketTuru] ?? $ayar['ilk_defa'] ?? 8) : $ayar);
    }

    /** Bu paketin Md.13 süre hesabı (paket türüyle). */
    public function gerekenSure(string $sinif, ?string $tur = null): array
    {
        return static::gerekenUzaktanSure($sinif, $tur ?? $this->turAnahtari(), $this->turAnahtari());
    }

    /** ilk_defa | yenileme */
    public function turAnahtari(): string
    {
        return $this->egitim_turu === 'yenileme' ? 'yenileme' : 'ilk_defa';
    }

    public function turEtiketi(): string
    {
        return (string) config('isg.uzaktan_egitim.paket_turleri.'.$this->turAnahtari());
    }

    /** Sınav için havuzdan rastgele soru seç (yetmezse hepsi). */
    /**
     * Rastgele sınav soruları. $tur: 'on_test' (seviye tespiti) ya da 'final'.
     * Soru, kullanımı 'ortak' ya da istenen türse havuza girer; o türe uygun
     * soru hiç yoksa tüm havuz kullanılır (eski paketler).
     */
    public function sinavSorulari(string $tur = 'final', array $haric = []): array
    {
        $adet = (int) config('isg.uzaktan_egitim.'.($tur === 'on_test' ? 'on_test_soru_sayisi' : 'final_soru_sayisi'), 10);
        $havuz = $this->sorular()->whereIn('kullanim', ['ortak', $tur])->get();

        if ($havuz->isEmpty()) {
            $havuz = $this->sorular()->get();
        }

        // Önceki denemelerde sorulanlar, havuzda yeterince yeni soru varsa tekrar sorulmaz.
        $yeni = $havuz->whereNotIn('id', $haric);
        if ($haric && $yeni->count() >= $adet) {
            $havuz = $yeni;
        }

        // Konu dağılımı (08.11.2026): Genel / Sağlık / Teknik gruplarından sırayla birer soru —
        // konusu girilmemiş sorular ayrı bir grup sayılır.
        $gruplar = $havuz->shuffle()->groupBy(fn (EgitimPaketiSorusu $s) => (int) $s->modul)->shuffle()->map(fn ($g) => $g->values())->values();
        $secilen = collect();
        for ($i = 0; $secilen->count() < $adet && $gruplar->contains(fn ($g) => $g->has($i)); $i++) {
            foreach ($gruplar as $g) {
                if ($secilen->count() < $adet && $g->has($i)) {
                    $secilen->push($g[$i]);
                }
            }
        }

        return $secilen->shuffle()->map(fn (EgitimPaketiSorusu $s) => [
            'id' => $s->id,
            'soru' => $s->soru,
            'secenekler' => $s->secenekler,
            'dogru_index' => $s->dogru_index,
        ])->all();
    }

    public function sinavaHazirMi(): bool
    {
        return $this->dersler()->exists() && $this->sorular()->count() >= min(5, $this->sinav_soru_sayisi);
    }

    public function paketOzeti(): string
    {
        return Str::of($this->ad)->limit(60).' · '.$this->dersler->count().' ders · '.$this->sorular()->count().' soru';
    }
}
