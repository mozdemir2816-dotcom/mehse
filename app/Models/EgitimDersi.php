<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uzaktan eğitim dersi — bir video. `video_url`'den sağlayıcı ve gömme URL'i çözülür.
 */
class EgitimDersi extends Model
{
    use HasFactory;

    protected $table = 'egitim_dersleri';

    protected $guarded = ['id'];

    /** Yönetmelik Ek-1 ana konu grupları. */
    public const MODULLER = [
        1 => 'Genel Konular',
        2 => 'Sağlık Konuları',
        3 => 'Teknik Konular',
        4 => 'İşyerine Özgü Riskler',
    ];

    protected $casts = [
        'modul' => 'integer',
        'sira' => 'integer',
        'sure_sn' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (EgitimDersi $d): void {
            $d->saglayici = static::saglayiciTespit($d->video_url);
        });
    }

    public function paket(): BelongsTo
    {
        return $this->belongsTo(EgitimPaketi::class, 'egitim_paketi_id');
    }

    /** "Modül 2 · 3. Ders — Başlık" — portalda ve listelerde numaralı gösterim (konunun 2. videosu: "3.2"). */
    public function numaraliBaslik(): string
    {
        return ($this->modul ? 'M'.$this->modul.' · ' : '').$this->siraEtiketi().'. '.$this->baslik;
    }

    /**
     * Bir konu başlığına birden fazla video eklenebilir (07.11.2026): aynı paket +
     * konu başlığı (modül) + sıra no'daki videolar aynı konunun parçalarıdır,
     * eklenme sırasına göre 1, 2, 3 … diye numaralanır.
     */
    public function konuVideolari(): Builder
    {
        return static::query()
            ->where('egitim_paketi_id', $this->egitim_paketi_id)
            ->where(fn (Builder $q) => $this->modul === null ? $q->whereNull('modul') : $q->where('modul', $this->modul))
            ->where('sira', $this->sira)
            ->orderBy('id');
    }

    /** Konudaki kaçıncı video (1 = konunun ilk videosu). */
    public function konuIciNo(): int
    {
        return $this->exists ? $this->konuVideolari()->where('id', '<', $this->id)->count() + 1 : 1;
    }

    /** "3" ya da konunun ek videosu için "3.2" (konuda tek video varsa yalnız "3"). */
    public function siraEtiketi(): string
    {
        $no = $this->konuIciNo();

        return (int) $this->sira.($no > 1 || ($this->exists && $this->konuVideolari()->count() > 1) ? '.'.$no : '');
    }

    public static function saglayiciTespit(?string $url): string
    {
        $url = (string) $url;

        return match (true) {
            str_contains($url, 'youtu') => 'youtube',
            str_contains($url, 'vimeo') => 'vimeo',
            default => 'diger',
        };
    }

    /** YouTube video kimliği (izleme ilerlemesini JS ile takip için gerekli). */
    public function youtubeId(): ?string
    {
        if ($this->saglayici !== 'youtube') {
            return null;
        }

        if (preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{6,})~', (string) $this->video_url, $m)) {
            return $m[1];
        }

        return null;
    }

    public function vimeoId(): ?string
    {
        if ($this->saglayici !== 'vimeo') {
            return null;
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', (string) $this->video_url, $m)) {
            return $m[1];
        }

        return null;
    }

    /** iframe src'si. */
    public function gommeUrl(): ?string
    {
        return match ($this->saglayici) {
            'youtube' => $this->youtubeId() ? 'https://www.youtube-nocookie.com/embed/'.$this->youtubeId().'?rel=0&modestbranding=1' : null,
            'vimeo' => $this->vimeoId() ? 'https://player.vimeo.com/video/'.$this->vimeoId() : null,
            default => $this->video_url,
        };
    }

    public function sureEtiketi(): ?string
    {
        if (! $this->sure_sn) {
            return null;
        }

        return sprintf('%d:%02d', intdiv($this->sure_sn, 60), $this->sure_sn % 60);
    }
}
