<?php

namespace App\Models;

use App\Support\WhatsApp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/** Tanıtım sayfasındaki teklif formundan gelen talep (üye olmayan ziyaretçi). */
class TeklifTalebi extends Model
{
    use Prunable;

    protected $table = 'teklif_talepleri';

    protected $guarded = ['id'];

    public const DURUMLAR = [
        'yeni' => 'Yeni',
        'arandi' => 'Arandı',
        'teklif_verildi' => 'Teklif verildi',
        'kapandi' => 'Kapandı',
    ];

    public const TEHLIKE_SINIFLARI = ['Az Tehlikeli', 'Tehlikeli', 'Çok Tehlikeli', 'Bilmiyorum'];

    /**
     * Formdaki serbest yazılmış telefonu wa.me'nin istediği uluslararası biçime çevirir
     * (0532 111 22 33 / +90 532... / 532... → 905321112233). Türk cep numarası
     * çıkarılamazsa null — WhatsApp düğmesi o zaman gizlenir.
     */
    public function whatsappNumarasi(): ?string
    {
        return WhatsApp::numara($this->telefon);
    }

    /** Müşteriye hazır şablonla WhatsApp mesajı. */
    public function whatsappMesaji(): string
    {
        return "Merhaba Sayın {$this->kisi}, mehse.com üzerinden {$this->firma_adi} adına yaptığınız İSG keşif/teklif "
            .'başvurunuz hakkında yazıyorum. Ücretsiz ön keşif için işyerinizi ziyaret etmek isteriz; '
            .'size uygun gün ve saati paylaşabilir misiniz?';
    }

    public function whatsappUrl(): ?string
    {
        return WhatsApp::url($this->telefon, $this->whatsappMesaji());
    }

    /** KVKK md.3A: son işlemden 1 yıl sonra otomatik silinir (routes/console.php model:prune). */
    public function prunable(): Builder
    {
        return static::where('updated_at', '<', now()->subYear());
    }

    /** Sol menü ve sekme rozeti: henüz ilgilenilmemiş talepler. */
    public static function yeniSayisi(): int
    {
        return static::where('durum', 'yeni')->count();
    }
}
