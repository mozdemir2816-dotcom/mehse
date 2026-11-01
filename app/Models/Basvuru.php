<?php

namespace App\Models;

use App\Support\WhatsApp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kayıt / başvuru kaydı — bireysel İGU kaydı (anında hesap) veya OSGB
 * başvurusu (sahip onayı ile hesap). Bkz. App\Support\BasvuruIslemleri.
 */
class Basvuru extends Model
{
    use Prunable;

    protected $table = 'basvurular';

    protected $guarded = ['id'];

    protected $casts = [
        'onaylar' => 'array',
        'incelendi_at' => 'datetime',
    ];

    public const TIPLER = [
        'uzman' => 'İş Güvenliği Uzmanı (bireysel)',
        'osgb' => 'OSGB',
    ];

    public const DURUMLAR = [
        'beklemede' => 'Beklemede',
        'onaylandi' => 'Onaylandı',
        'reddedildi' => 'Reddedildi',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inceleyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inceleyen_id');
    }

    public function tipEtiketi(): string
    {
        return self::TIPLER[$this->tip] ?? $this->tip;
    }

    public function durumEtiketi(): string
    {
        return self::DURUMLAR[$this->durum] ?? $this->durum;
    }

    /** KVKK md.5: reddedilen başvurular ret tarihinden 1 yıl sonra otomatik silinir. */
    public function prunable(): Builder
    {
        return static::where('durum', 'reddedildi')->where('incelendi_at', '<', now()->subYear());
    }

    /** Başvuru ekranındaki başlık: OSGB adı ya da kişi adı. */
    public function baslik(): string
    {
        return $this->osgb_adi ?: $this->ad_soyad;
    }

    /** Onaylanan OSGB'ye "hesabınız açıldı" haberi — şifre yazılmaz (yalnız e-postada). */
    public function whatsappMesaji(): string
    {
        return "Merhaba Sayın {$this->ad_soyad}, {$this->baslik()} adına yaptığınız Mehse İSG başvurusu onaylandı ve "
            .config('isg.kayit.osgb_deneme_gun', 90).' günlük ücretsiz deneme hesabınız açıldı. '
            ."Giriş bilgileriniz {$this->eposta} adresine e-postayla gönderildi (gelen kutusunda yoksa gereksiz/spam klasörüne bakabilirsiniz). "
            .'Giriş adresi: '.url('/admin/login').' — İlk girişte şifrenizi değiştirmenizi öneririz. Kolay gelsin.';
    }

    /** Yalnız onaylanmış OSGB başvurusunda ve Türk cep numarası varsa. */
    public function whatsappUrl(): ?string
    {
        return $this->tip === 'osgb' && $this->durum === 'onaylandi'
            ? WhatsApp::url($this->telefon, $this->whatsappMesaji())
            : null;
    }
}
