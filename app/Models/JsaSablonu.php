<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * JSA (İşe Özgü Risk Değerlendirmesi / Job Safety Analysis) kütüphane kaydı.
 *
 * Bir kayıt = bir iş için TAM bir analiz: başlık + doküman künyesi + tüm iş
 * adımları (`adimlar`) + notlar + imza bloğu. Firmadan bağımsızdır; çıktı
 * alınırken firma yalnız künyeye eklenir, analiz gövdesine dokunulmaz.
 *
 * @property array<int, array{sira: string, is_adimi: string, tehlikeler: string, sonuclar: string, baslangic_risk: string, kontrol_tedbirleri: string, kalinti_risk: string, sorumlu: string}> $adimlar
 * @property array<int, string> $notlar
 * @property array<int, array{rol: string, ad: ?string, imza: ?string, tarih: ?string}> $imza_rolleri
 */
class JsaSablonu extends Model
{
    use HasFactory;

    protected $table = 'jsa_sablonlari';

    protected $guarded = ['id'];

    protected $casts = [
        'adimlar' => 'array',
        'notlar' => 'array',
        'imza_rolleri' => 'array',
    ];

    /** Örnek şablondaki (DUVAR ÖRME) imza bloğu — Excel'de bulunamazsa varsayılan. */
    public const VARSAYILAN_IMZA_ROLLERI = [
        ['rol' => 'Hazırlayan (İSG / HSE)', 'ad' => null, 'imza' => null, 'tarih' => null],
        ['rol' => 'Gözden Geçiren (Süpervizör / Saha Mühendisi)', 'ad' => null, 'imza' => null, 'tarih' => null],
        ['rol' => 'Onaylayan (Proje Müdürü / İSG Müdürü)', 'ad' => null, 'imza' => null, 'tarih' => null],
    ];

    public const ADIM_SUTUNLARI = [
        'sira' => 'Sıra No',
        'is_adimi' => 'İş Adımı / Faaliyet',
        'tehlikeler' => 'Olası Tehlikeler',
        'sonuclar' => 'Olası Sonuçlar / Riskler',
        'baslangic_risk' => 'Başlangıç Risk Seviyesi',
        'kontrol_tedbirleri' => "Kontrol Tedbirleri (Gerekli KKD'ler Dahil)",
        'kalinti_risk' => 'Kalıntı Risk Seviyesi',
        'sorumlu' => 'Sorumlu',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<JsaSablonu> $query */
    public function scopeSahip(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /** Bir risk seviyesi metnini renk anahtarına çevirir (yuksek/orta/dusuk/none). */
    public static function riskRengi(?string $seviye): string
    {
        $n = mb_strtolower(trim((string) $seviye));

        return match (true) {
            $n === '' => 'none',
            str_contains($n, 'çok yüksek') || str_contains($n, 'kritik') => 'kritik',
            str_contains($n, 'yüksek') => 'yuksek',
            str_contains($n, 'orta') => 'orta',
            str_contains($n, 'düşük') || str_contains($n, 'kabul') || str_contains($n, 'ihmal') => 'dusuk',
            default => 'none',
        };
    }
}
