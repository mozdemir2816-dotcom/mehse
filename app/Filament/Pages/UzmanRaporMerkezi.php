<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Support\UzmanRaporu;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Uzman Rapor Merkezi (isgsuite): aktif görevlendirmeli işyerinin sağlık
 * dışı İSG görünümü. Firma seçilmeden rapor verisi yüklenmez. Göstergeler,
 * işyeri uygunluk özeti (yasal dayanak bağlantılarıyla), kalem bazında
 * öncelikli aksiyonlar ve TXT çıktısı. Klinik sağlık kayıtları dahil değildir.
 */
class UzmanRaporMerkezi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.uzman-rapor-merkezi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'uzman-rapor-merkezi';

    protected static ?string $title = 'Uzman Rapor Merkezi';

    protected static ?string $navigationLabel = 'Uzman Rapor Merkezi';

    public ?int $firmaId = null;

    public int $gosterilen = 20;

    public function mount(): void
    {
        $id = request()->integer('firma');
        $this->firmaId = $id && array_key_exists($id, $this->firmalar) ? $id : null;
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar) ? Firma::find($this->firmaId) : null;
    }

    #[Computed]
    public function rapor(): ?array
    {
        return $this->firma ? UzmanRaporu::rapor($this->firma) : null;
    }

    public function updatedFirmaId(): void
    {
        $this->gosterilen = 20;
        $this->yenile();
    }

    public function dahaFazla(): void
    {
        $this->gosterilen += 20;
    }

    public function yenile(): void
    {
        unset($this->firma, $this->rapor);
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
