<?php

namespace App\Models;

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

    protected $casts = [
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
