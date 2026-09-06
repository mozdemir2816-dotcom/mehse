<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Eğitim Türü — kullanıcının Profilim > Eğitimler'de aktif takip ettiği
 * eğitim konusu. Kullanıcı hiç eklememişse yalnız "Temel İş Sağlığı ve
 * Güvenliği Eğitimi" ile başlar (config isg.egitim_kayit_turleri'nin ilk
 * maddesi); "Konu Ekle" ile katalogdan veya özel isimle yenisi eklenir.
 */
class EgitimTuru extends Model
{
    protected $table = 'egitim_turleri';

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Katalogdaki (isg.egitim_kayit_turleri) kategori — özel/kullanıcı tanımlı konularda null döner. */
    public function kategori(): ?string
    {
        return collect(config('isg.egitim_kayit_turleri', []))
            ->firstWhere('anahtar', $this->anahtar)['kategori'] ?? null;
    }

    /** @return Collection<int, EgitimTuru> */
    public static function aktifListe(int $userId): Collection
    {
        $mevcut = static::where('user_id', $userId)->orderBy('sira')->orderBy('id')->get();

        if ($mevcut->isNotEmpty()) {
            return $mevcut;
        }

        $varsayilan = collect(config('isg.egitim_kayit_turleri', []))->first();

        $olusturulan = static::create([
            'user_id' => $userId,
            'anahtar' => $varsayilan['anahtar'] ?? 'is_sagligi_guvenligi_egitimi',
            'ad' => $varsayilan['ad'] ?? 'Temel İş Sağlığı ve Güvenliği Eğitimi',
            'gecerlilik_ay' => $varsayilan['gecerlilik_ay'] ?? null,
            'sira' => 0,
        ]);

        return collect([$olusturulan]);
    }
}
