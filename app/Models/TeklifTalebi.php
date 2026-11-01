<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tanıtım sayfasındaki teklif formundan gelen talep (üye olmayan ziyaretçi). */
class TeklifTalebi extends Model
{
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
        $rakam = preg_replace('/\D/', '', (string) $this->telefon);

        $rakam = match (true) {
            str_starts_with($rakam, '0090') => substr($rakam, 2),
            str_starts_with($rakam, '90') && strlen($rakam) === 12 => $rakam,
            str_starts_with($rakam, '0') && strlen($rakam) === 11 => '9'.$rakam,
            strlen($rakam) === 10 => '90'.$rakam,
            default => $rakam,
        };

        return preg_match('/^905\d{9}$/', $rakam) ? $rakam : null;
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
        $no = $this->whatsappNumarasi();

        return $no ? 'https://wa.me/'.$no.'?text='.rawurlencode($this->whatsappMesaji()) : null;
    }

    /** Sol menü ve sekme rozeti: henüz ilgilenilmemiş talepler. */
    public static function yeniSayisi(): int
    {
        return static::where('durum', 'yeni')->count();
    }
}
