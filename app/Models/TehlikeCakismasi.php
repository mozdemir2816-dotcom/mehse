<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Excel'den Risk Kütüphanesi'ne toplu yüklerken bulunan olası mükerrer
 * (mevcut bir Tehlike'ye metin olarak çok benzeyen ama birebir aynı olmayan)
 * bir aday satır. Kullanıcı gözden geçirip karar verene kadar bekler.
 */
class TehlikeCakismasi extends Model
{
    protected $table = 'tehlike_cakismalari';

    protected $guarded = ['id'];

    protected $casts = [
        'yeni_veri' => 'array',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(TehlikeKategorisi::class, 'tehlike_kategorisi_id');
    }

    public function mevcutTehlike(): BelongsTo
    {
        return $this->belongsTo(Tehlike::class, 'mevcut_tehlike_id');
    }

    public function mevcuduKoru(): void
    {
        $this->delete();
    }

    public function yenisiniKullan(): void
    {
        $this->mevcutTehlike->update(collect($this->yeni_veri)->only([
            'kod', 'bolum', 'faaliyet', 'tehlike', 'risk', 'mevcut_onlem', 'mevzuat',
        ])->all());

        $this->delete();
    }

    public function ikisiniDeTut(): Tehlike
    {
        $tehlike = Tehlike::create(array_merge(
            ['tehlike_kategorisi_id' => $this->tehlike_kategorisi_id],
            collect($this->yeni_veri)->only([
                'kod', 'bolum', 'faaliyet', 'tehlike', 'risk', 'mevcut_onlem', 'mevzuat',
            ])->all(),
        ));

        $this->delete();

        return $tehlike;
    }
}
