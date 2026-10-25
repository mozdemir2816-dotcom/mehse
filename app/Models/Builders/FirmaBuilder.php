<?php

namespace App\Models\Builders;

use App\Models\Firma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** @extends Builder<Firma> */
class FirmaBuilder extends Builder
{
    /**
     * Firma seçim listeleri: id ⇒ etiket. Aynı unvanlı şube/şantiyeler kısa adla ayrılır.
     *
     * @return Collection<int, string>
     */
    public function secenekler(): Collection
    {
        return $this->get(['firmalar.id', 'firmalar.unvan', 'firmalar.kisa_ad'])
            ->mapWithKeys(fn (Firma $f) => [$f->id => $f->secimEtiketi()]);
    }
}
