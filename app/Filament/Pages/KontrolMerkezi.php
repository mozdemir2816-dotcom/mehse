<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Support\PortfoyKarne;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * İSG Komuta Merkezi — isgpratik 135-136.jpg. Portföy genelinde yasal uyum
 * takibi. 3 sekme: Günlük Akış / Firma Asistanı / Çalışan Asistanı.
 */
class KontrolMerkezi extends Page
{
    protected string $view = 'filament.pages.kontrol-merkezi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'kontrol-merkezi';

    protected static ?string $title = 'İSG Komuta Merkezi';

    protected static ?string $navigationLabel = 'Kontrol Merkezi';

    public const SEKMELER = [
        'gunluk' => 'Günlük Akış',
        'firma' => 'Firma Asistanı',
        'calisan' => 'Çalışan Asistanı',
    ];

    public string $sekme = 'firma';

    public ?int $firmaId = null;

    /** Portföy kriter listesinde "eksik firmaları göster" için açık olan kriter anahtarı. */
    public ?string $acikKriter = null;

    public function sekmeSec(string $sekme): void
    {
        if (array_key_exists($sekme, self::SEKMELER)) {
            $this->sekme = $sekme;
        }
    }

    /** Bir kriter satırına tıklayınca eksik firma listesini aç/kapat. */
    public function kriterDetayAc(string $anahtar): void
    {
        $this->acikKriter = $this->acikKriter === $anahtar ? null : $anahtar;
    }

    /**
     * Açık kriteri henüz karşılamayan firmalar (id => unvan). Kullanıcı "işyeri
     * hekimi 1 firmada eksik" görünce hangi firma olduğunu tıkla-gör.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function acikKriterEksikFirmalar(): array
    {
        if ($this->acikKriter === null) {
            return [];
        }

        return PortfoyKarne::eksikFirmalar(Filament::auth()->id(), $this->acikKriter)
            ->pluck('unvan', 'id')
            ->all();
    }

    /** @return array<int, string> */
    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->pluck('unvan', 'id')
            ->all();
    }

    #[Computed]
    public function ozet(): array
    {
        return PortfoyKarne::ozet(Filament::auth()->id());
    }

    #[Computed]
    public function kriterler(): array
    {
        return PortfoyKarne::kriterler(Filament::auth()->id());
    }

    #[Computed]
    public function secilenFirma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    #[Computed]
    public function calisanKarne(): ?array
    {
        return $this->secilenFirma
            ? PortfoyKarne::calisanKarne($this->secilenFirma)
            : null;
    }

    /** Günlük Akış: geçerliliği geçmiş / 60 gün içinde dolan risk değerlendirmeleri. */
    #[Computed]
    public function yaklasanIsler(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->with(['riskDegerlendirmeleri' => fn ($q) => $q->latest('gecerlilik_tarihi')])
            ->get()
            ->flatMap(function (Firma $f) {
                $rd = $f->riskDegerlendirmeleri->first();

                if (! $rd) {
                    return [[
                        'firma' => $f->unvan,
                        'baslik' => 'Risk değerlendirmesi yok',
                        'durum' => 'gecikti',
                        'tarih' => null,
                    ]];
                }

                if (! $rd->gecerlilik_tarihi) {
                    return [];
                }

                $gun = (int) now()->startOfDay()->diffInDays($rd->gecerlilik_tarihi, false);

                if ($gun > 60) {
                    return [];
                }

                return [[
                    'firma' => $f->unvan,
                    'baslik' => $gun < 0 ? 'Risk değerlendirmesi geçerliliği doldu' : 'Risk değerlendirmesi yenilemesi yaklaşıyor',
                    'durum' => $gun < 0 ? 'gecikti' : 'yaklasiyor',
                    'tarih' => $rd->gecerlilik_tarihi->format('d.m.Y'),
                ]];
            })
            ->sortBy('tarih')
            ->values()
            ->all();
    }
}
