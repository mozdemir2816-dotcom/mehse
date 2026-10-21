<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Models\Firma;
use App\Models\TabelaTasarimi as TabelaTasarimiModel;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Tabela / Güvenlik Levhası Tasarım Atölyesi (ANSI Z535 + ISO 7010 + T.C.
 * Sağlık ve Güvenlik İşaretleri Yönetmeliği Ek-2 piktogram kütüphanesi).
 * Çizim tamamen tarayıcıda (iframe, yalıtılmış HTML/JS — kendi Tailwind CDN
 * teması); kaydet butonu postMessage ile tasarım JSON'unu ve o anki
 * görünümün PNG'sini gönderir. Firma başına birden çok tasarım saklanır.
 * Acil durum senaryosuna bağlı olmadığından (ofis/büro aracı) kendi menü
 * girdisiyle "Saha Dışı Kayıtlar" grubunda, firma seçimi sayfa içinde yapılır.
 */
class TabelaTasarimi extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.tabela-tasarimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static string|UnitEnum|null $navigationGroup = 'Saha Dışı Kayıtlar';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'tabela-tasarimi';

    protected static ?string $title = 'Tabela Tasarımı';

    protected static ?string $navigationLabel = 'Tabela Tasarımı';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    #[Url(as: 'id')]
    public ?int $tasarimId = null;

    public function mount(): void
    {
        $id = request()->integer('firma');
        $this->firmaId = $id && array_key_exists($id, $this->firmalar) ? $id : null;

        $tasarimId = request()->integer('id');
        $this->tasarimId = $tasarimId && $this->tasarimlar->has($tasarimId) ? $tasarimId : null;
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId) : null;
    }

    /** @return \Illuminate\Support\Collection<int, TabelaTasarimiModel> */
    #[Computed]
    public function tasarimlar(): \Illuminate\Support\Collection
    {
        if (! $this->firma) {
            return collect();
        }

        return TabelaTasarimiModel::where('firma_id', $this->firma->id)->orderByDesc('updated_at')->get()->keyBy('id');
    }

    public function aktifTasarim(): ?TabelaTasarimiModel
    {
        return $this->tasarimId ? $this->tasarimlar->get($this->tasarimId) : null;
    }

    /** Editöre gönderilecek tasarım durumu: kayıtlı tasarım ya da varsayılanlar. */
    public function editorVerisi(): array
    {
        $aktif = $this->aktifTasarim();

        return [
            'baslik' => $aktif?->baslik ?? '',
            ...($aktif?->tasarim ?? TabelaTasarimiModel::varsayilanTasarim()),
        ];
    }

    #[Computed]
    public function editorHtml(): string
    {
        return view('tabela.editor', ['veri' => $this->editorVerisi()])->render();
    }

    public function updatedFirmaId(): void
    {
        $this->tasarimId = null;
        $this->redirect(static::getUrl($this->firmaId ? ['firma' => $this->firmaId] : []));
    }

    public function yeni(): void
    {
        $this->redirect(static::getUrl($this->firmaId ? ['firma' => $this->firmaId] : []));
    }

    public function ac(int $id): void
    {
        if (! $this->tasarimlar->has($id)) {
            return;
        }

        $this->redirect(static::getUrl(['firma' => $this->firmaId, 'id' => $id]));
    }

    public function sil(int $id): void
    {
        $tasarim = $this->tasarimlar->get($id);

        if (! $tasarim) {
            return;
        }

        if ($tasarim->gorsel_yolu) {
            Storage::disk('public')->delete($tasarim->gorsel_yolu);
        }

        $tasarim->delete();

        Notification::make()->title('Tasarım silindi')->success()->send();

        $this->redirect(static::getUrl($this->firmaId ? ['firma' => $this->firmaId] : []));
    }

    /**
     * Tarayıcıdan: başlık + tasarım verisi + o anki görünümün PNG'si (data URL).
     */
    public function kaydet(?string $baslik, array $veri, ?string $png = null): void
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        $temiz = TabelaTasarimiModel::temizle($veri);
        $aktif = $this->aktifTasarim();

        if (! $aktif) {
            $aktif = new TabelaTasarimiModel(['firma_id' => $this->firma->id]);
        }

        $aktif->forceFill([
            'baslik' => TabelaTasarimiModel::basligiTemizle($baslik),
            'tasarim' => $temiz,
        ]);

        $this->pngYaz($aktif, $png);

        $aktif->save();

        $this->tasarimId = $aktif->id;

        Notification::make()->title('Tabela tasarımı kaydedildi')->success()->send();

        $this->redirect(static::getUrl(['firma' => $this->firmaId, 'id' => $aktif->id]));
    }

    /** O anki görünümün PNG'sini (data URL, en fazla ~8 MB) diske yazar. */
    private function pngYaz(TabelaTasarimiModel $tasarim, ?string $png): void
    {
        if (! $png || ! str_starts_with($png, 'data:image/png;base64,') || strlen($png) >= 11_000_000) {
            return;
        }

        $ikili = base64_decode(substr($png, 22), true);

        if ($ikili === false || ! str_starts_with($ikili, "\x89PNG")) {
            return;
        }

        $eski = $tasarim->gorsel_yolu;
        $yol = 'tabela-tasarim/'.($tasarim->firma_id ?? $this->firmaId).'-'.now()->format('YmdHis').'.png';
        Storage::disk('public')->put($yol, $ikili);
        $tasarim->gorsel_yolu = $yol;

        if ($eski && $eski !== $yol) {
            Storage::disk('public')->delete($eski);
        }
    }

    public function gorselUrl(TabelaTasarimiModel $tasarim): ?string
    {
        return $tasarim->gorsel_yolu ? Storage::disk('public')->url($tasarim->gorsel_yolu) : null;
    }
}
