<?php

namespace App\Filament\Pages\Concerns;

use App\Models\User;
use App\Support\TesisUygunlugu;
use Filament\Facades\Filament;
use Livewire\Attributes\Computed;

/** Tesis uygunluk skoru ("bugün neye müdahale etmeliyim?") — hem eski sayfa hem Firma Denetim & Operasyon kullanır. */
trait TesisUygunlukVerisi
{
    #[Computed]
    public function sonuc(): ?array
    {
        /** @var User $kullanici */
        $kullanici = Filament::auth()->user();

        return $this->firma ? TesisUygunlugu::hesapla($this->firma, $kullanici) : null;
    }
}
