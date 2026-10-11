<?php

namespace App\Models;

use App\Support\AcilDurumKonuSecici;
use App\Support\AcilEkipDurumu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Acil Durum Eylem Planı — firma başına bir kayıt. Konu sayfaları, destek
 * ekipleri ve kapak çerçevesi seçilir; `AcilDurumPlaniUretici` PDF üretir.
 */
class AcilDurumPlani extends Model
{
    use HasFactory;

    protected $table = 'acil_durum_planlari';

    protected $guarded = ['id'];

    protected $casts = [
        'rapor_tarihi' => 'date',
        'gecerlilik_tarihi' => 'date',
        'konular' => 'array',
        'ekipler' => 'array',
        'uygulama' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (AcilDurumPlani $p): void {
            $p->dokuman_no ??= 'AD-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 2, '0', STR_PAD_LEFT);
            $p->kapak_cercevesi ??= 'klasik';

            if (empty($p->konular)) {
                $p->konular = AcilDurumKonuSecici::firmaIcin($p->firma);
            }

            if ($p->rapor_tarihi && ! $p->gecerlilik_tarihi) {
                $sinif = $p->firma?->tehlike_sinifi ?? 'az_tehlikeli';
                $yil = config('isg.acil_durum.gecerlilik_yili.'.$sinif, 6);
                $p->gecerlilik_tarihi = Carbon::parse($p->rapor_tarihi)->addYears($yil);
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** Seçili konuların ad listesi (config sırasında). */
    public function konuAdlari(): array
    {
        return collect(config('isg.acil_durum.konular'))
            ->whereIn('anahtar', $this->konular ?? [])
            ->pluck('ad')
            ->all();
    }

    /**
     * Ekip anahtarı => isimler. Acil Durum Ekipleri sayfasında üyesi olan
     * ekip, plandaki elle yazılmış isimlerin yerine geçer.
     *
     * @return array<string, array<int, string>>
     */
    public function ekipListesi(): array
    {
        $kayitli = $this->firma ? AcilEkipDurumu::planListesi($this->firma) : [];
        $ekipler = array_replace($this->ekipler ?? [], $kayitli);

        return collect(config('isg.acil_durum.ekipler'))
            ->mapWithKeys(fn ($ad, $anahtar) => [$anahtar => array_values(array_filter($ekipler[$anahtar] ?? []))])
            ->all();
    }

    /**
     * Plan PDF'indeki görevli listesi (Acil Durumlar Yön. Md.12/1-f-4 ve
     * Md.11/9: adı soyadı, unvanı, sorumluluk alanı, iletişim; varsa yedek).
     * Acil Durum Ekipleri sayfasında üyesi olan ekip ayrıntılı gelir; yoksa
     * plandaki elle yazılmış isimler yalnız adla listelenir.
     *
     * @return array<string, array<int, array{ad: string, gorev: ?string, bolum: ?string, telefon: ?string, yedek: bool, lider: bool, vardiya: ?string}>>
     */
    public function ekipDetaylari(): array
    {
        $kayitli = [];

        if ($this->firma) {
            foreach (AcilEkipDurumu::ekipler($this->firma) as $ekip) {
                $anahtar = $ekip->turBilgisi()['plan'] ?? null;

                foreach ($anahtar ? $ekip->uyeler : [] as $u) {
                    $kayitli[$anahtar][] = [
                        'ad' => $u->ad_soyad, 'gorev' => $u->gorev, 'bolum' => $u->bolum, 'telefon' => $u->telefon,
                        'yedek' => $u->uyelik === 'yedek', 'lider' => (bool) $u->lider, 'vardiya' => $u->vardiya,
                    ];
                }
            }
        }

        return collect(config('isg.acil_durum.ekipler'))->mapWithKeys(fn ($ad, $anahtar) => [
            $anahtar => $kayitli[$anahtar] ?? collect($this->ekipler[$anahtar] ?? [])->filter()->map(fn ($isim) => [
                'ad' => $isim, 'gorev' => null, 'bolum' => null, 'telefon' => null, 'yedek' => false, 'lider' => false, 'vardiya' => null,
            ])->values()->all(),
        ])->all();
    }

    /** Planın tahliye planı görseli: yüklenen görsel, yoksa kaydedilmiş kroki görüntüsü (tam yol). */
    public function tahliyeGorselYolu(): ?string
    {
        if ($this->tahliye_plani_gorseli && is_file($yol = public_path('storage/'.$this->tahliye_plani_gorseli))) {
            return $yol;
        }

        $kroki = AcilDurumKrokisi::query()->where('firma_id', $this->firma_id)->first();
        $yol = $kroki?->gorsel_yolu ? storage_path('app/public/'.$kroki->gorsel_yolu) : null;

        return $yol && is_file($yol) ? $yol : null;
    }

    public static function firmaIcin(Firma $firma): self
    {
        $plan = static::firstOrNew(['firma_id' => $firma->id]);

        if (! $plan->exists) {
            $plan->rapor_tarihi = now();
            $plan->save();
        }

        return $plan;
    }
}
