<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Çalışma Talimatı — isgpratik 82-83.jpg.
 */
class Talimat extends Model
{
    use HasFactory;

    protected $table = 'talimatlar';

    protected $guarded = ['id'];

    protected $casts = [
        'kkdler' => 'array',
        'maddeler' => 'array',
        'bolumler' => 'array',
        'yayin_tarihi' => 'date',
        'revizyon_tarihi' => 'date',
        'boyut' => 'integer',
    ];

    /** Düz (numaralı) talimatların sonundaki tebliğ-tebellüğ metni (6331 SK m.16, m.17 ve m.19 uyumlu). */
    public const TAAHHUT_DUZ = "Yukarıda yer alan iş sağlığı ve güvenliği çalışma talimatını okudum, anladım ve bir nüshasını teslim aldım. 6331 sayılı İş Sağlığı ve Güvenliği Kanunu m.19 uyarınca işveren tarafından sağlanan iş ekipmanlarını, güvenlik donanımlarını ve kişisel koruyucu donanımları doğru kullanacağımı, iş sağlığı ve güvenliği kurallarına uyacağımı ve ciddi veya yakın bir tehlike ile karşılaştığımda derhal amirime ve işyerine bildireceğimi beyan ederim.";

    /** Bölümlü talimatların son bölümü (TAAHHÜT) — Kalıp/Demir talimatlarından birebir. */
    public const TAAHHUT_BOLUMLU = 'Bu talimatı okudum, anladım ve iş sağlığı ve güvenliği kurallarına uygun şekilde çalışacağımı kabul ederim.';

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** Amaç / Kapsam / KKD… başlıklı yapı mı (yoksa düz numaralı madde listesi mi). */
    public function bolumluMu(): bool
    {
        return ! empty($this->bolumler);
    }

    /** Çıktıya basılacak bölümler — başlığı ya da içeriği boş olanlar atlanır. */
    public function doluBolumler(): array
    {
        return array_values(array_filter($this->bolumler ?? [], fn ($b) => filled($b['baslik'] ?? null)
            && (filled($b['aciklama'] ?? null) || array_filter($b['maddeler'] ?? []))));
    }

    public static function varsayilanTaahhut(bool $bolumlu): string
    {
        return $bolumlu ? self::TAAHHUT_BOLUMLU : self::TAAHHUT_DUZ;
    }

    public function taahhutMetni(): string
    {
        return filled($this->taahhut) ? $this->taahhut : self::varsayilanTaahhut($this->bolumluMu());
    }

    /** Doküman no boşsa kayıttan türetilir: TLM-{firma}-{sıra}. */
    public function dokumanNoGoster(): string
    {
        return $this->dokuman_no ?: 'TLM-'.$this->firma_id.'-'.str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    /** Yayınlanma tarihi boşsa kayıt tarihi. */
    public function yayinTarihiGoster(): ?string
    {
        return ($this->yayin_tarihi ?? $this->created_at)?->format('d.m.Y');
    }

    public function kategoriEtiketi(): string
    {
        return config('isg.talimat.kategoriler.'.$this->kategori, (string) $this->kategori);
    }

    public function dosyaVarMi(): bool
    {
        return (bool) $this->dosya_yolu;
    }

    public function boyutEtiketi(): string
    {
        if (! $this->boyut) {
            return '—';
        }

        $kb = $this->boyut / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb, 0).' KB';
    }
}
