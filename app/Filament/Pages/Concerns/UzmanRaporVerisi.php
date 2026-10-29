<?php

namespace App\Filament\Pages\Concerns;

use App\Support\UzmanRaporu;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/** Uzman raporu (sağlık dışı İSG görünümü) — hem eski sayfa hem Firma Denetim & Operasyon kullanır. */
trait UzmanRaporVerisi
{
    public int $gosterilen = 20;

    #[Computed]
    public function rapor(): ?array
    {
        return $this->firma ? UzmanRaporu::rapor($this->firma) : null;
    }

    public function dahaFazla(): void
    {
        $this->gosterilen += 20;
    }

    public function txtIndir()
    {
        if (! $this->firma) {
            return null;
        }

        $metin = UzmanRaporu::txt($this->firma);

        return response()->streamDownload(fn () => print ("\xEF\xBB\xBF".$metin), 'uzman-raporu-'.Str::slug($this->firma->kisa_ad ?: $this->firma->unvan).'.txt', ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
