<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Filament\Support\DosyaKabul;
use App\Models\Firma;
use App\Models\OnBilgilendirme;
use App\Models\TespitOneriDefteri;
use App\Support\BilgilendirmeYazisiOkuyucu;
use App\Support\OnBilgilendirmeUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Ön Bilgilendirme / Tebliğ — İGU'nun işe başlamadan önce hazırladığı bilgilendirme
 * yazısı (Word/PDF) değiştirilmeden saklanır ve firmaya tebliğ edilir: işveren
 * paneline (İşyeri Girişi) gönderilip "okudum, tebellüğ ettim" onayı alınır ve/veya
 * elden teslim kaydedilir; Tebliğ-Tebellüğ Tutanağı PDF'i üretilir. İsteğe bağlı
 * olarak Tespit Öneri Defteri'ne "Ön Bilgilendirme" maddesi düşer (kullanıcı 15.10.2026:
 * "ileride tüm işler için hazırladığım evrakları direkt firmaya tebliğ edip bilgilendirmek").
 */
class OnBilgilendirmeler extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.on-bilgilendirmeler';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    /** Menüde gizli — Saha sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 99;

    protected static ?string $slug = 'on-bilgilendirmeler';

    protected static ?string $title = 'Ön Bilgilendirme / Tebliğ';

    protected static ?string $navigationLabel = 'Ön Bilgilendirme / Tebliğ';

    public ?int $firmaId = null;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }
    }

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
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    /** @return Collection<int, OnBilgilendirme> */
    #[Computed]
    public function kayitlar(): Collection
    {
        return $this->firma
            ? OnBilgilendirme::where('firma_id', $this->firma->id)->latest('bildirim_tarihi')->latest('id')->get()
            : collect();
    }

    #[Computed]
    public function panelHesabiVar(): bool
    {
        return (bool) $this->firma?->isyeriHesabi;
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->kayitlar, $this->panelHesabiVar);
    }

    private function kayit(int $id): ?OnBilgilendirme
    {
        return $this->firma ? OnBilgilendirme::where('firma_id', $this->firma->id)->find($id) : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('yukle')
                ->label('Bilgilendirme Yazısı Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn () => $this->firma !== null)
                ->modalDescription('Hazırladığınız yazı değiştirilmeden saklanır. Word dosyasında konu ve tarih yazıdan otomatik okunur.')
                ->modalSubmitActionLabel('Kaydet')
                ->stickyModalHeader()
                ->stickyModalFooter()
                ->schema([
                    // MIME yerine uzantı — gerekçe DosyaKabul'de.
                    DosyaKabul::uygula(FileUpload::make('dosya')->storeFileNamesIn('dosya_adi')
                        ->label('Word (.docx) veya PDF dosyası')
                        ->disk('local')->directory(fn () => 'on-bilgilendirme/'.$this->firmaId), ['docx', 'pdf'])
                        ->maxSize(20480)
                        ->required(),
                    TextInput::make('konu')->label('Konu')->placeholder('Boşsa Word yazısının başlığı kullanılır'),
                    DatePicker::make('bildirim_tarihi')->label('Bildirim tarihi')->helperText('Boşsa yazıdaki tarih, o da yoksa bugün'),
                    Textarea::make('aciklama')->label('Açıklama (opsiyonel)')->rows(2),
                    Toggle::make('panele_gonder')->label('İşveren paneline gönder (okudum onayı istenir)')->default(true),
                    Toggle::make('deftere_ekle')->label('Tespit Öneri Defteri\'ne "Ön Bilgilendirme" olarak ekle')->default(true),
                ])
                ->action(fn (array $data) => $this->yukle($data)),
        ];
    }

    public function yukle(array $data): void
    {
        $firma = $this->firma;

        if (! $firma) {
            Notification::make()->title('Önce bir firma seçin')->danger()->send();

            return;
        }

        $yol = (string) $data['dosya'];
        $adi = (string) ($data['dosya_adi'] ?? basename($yol));
        $yazi = strtolower(pathinfo($adi, PATHINFO_EXTENSION)) === 'docx'
            ? rescue(fn () => BilgilendirmeYazisiOkuyucu::oku(Storage::disk('local')->path($yol)), null)
            : null;

        $konu = filled($data['konu'] ?? null) ? $data['konu'] : (strtok((string) ($yazi['tespit'] ?? ''), "\n") ?: pathinfo($adi, PATHINFO_FILENAME));
        $tarih = $data['bildirim_tarihi'] ?? null
            ?: (($yazi['tarih'] ?? null) ? Carbon::createFromFormat('d.m.Y', $yazi['tarih'])->toDateString() : now()->toDateString());
        $panele = (bool) ($data['panele_gonder'] ?? false);

        $kayit = OnBilgilendirme::create([
            'user_id' => Filament::auth()->id(),
            'firma_id' => $firma->id,
            'konu' => $konu,
            'bildirim_tarihi' => $tarih,
            'aciklama' => $data['aciklama'] ?? null,
            'dosya_adi' => $adi,
            'dosya_yolu' => $yol,
            'boyut' => Storage::disk('local')->size($yol),
            'isyerine_gonderildi_at' => $panele ? now() : null,
        ]);

        if ($data['deftere_ekle'] ?? false) {
            TespitOneriDefteri::firmaIcin($firma)->bilgilendirmeEkle($yazi ?? [
                'tespit' => $konu,
                'oneri' => 'Firmaya tebliğ edilen "'.$adi.'" yazısında belirtilen önlemler alınmalıdır.',
                'tarih' => Carbon::parse($tarih)->format('d.m.Y'),
            ], $kayit->id);
        }

        unset($this->kayitlar);

        Notification::make()
            ->title('Bilgilendirme yazısı kaydedildi')
            ->body($panele && ! $this->panelHesabiVar
                ? 'Firmanın İşyeri Girişi hesabı yok — panelde görünmesi için Firmalar sayfasından "İşyeri Girişi" oluşturun.'
                : ($panele ? 'İşveren panelinde görünüyor; onay verildiğinde burada işaretlenir.' : null))
            ->success()->send();
    }

    public function indir(int $id)
    {
        $kayit = $this->kayit($id);

        if (! $kayit || ! Storage::disk('local')->exists($kayit->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('local')->download($kayit->dosya_yolu, $kayit->dosya_adi);
    }

    public function tutanak(int $id)
    {
        $kayit = $this->kayit($id);

        return $kayit ? OnBilgilendirmeUretici::tutanak($kayit) : null;
    }

    public function paneleGonder(int $id): void
    {
        $this->kayit($id)?->update(['isyerine_gonderildi_at' => now()]);
        unset($this->kayitlar);
        Notification::make()->title('İşveren paneline gönderildi')->success()->send();
    }

    public function eldenTeslimAction(): Action
    {
        return Action::make('eldenTeslim')
            ->label('Elden Teslim')
            ->modalHeading('Elden teslim kaydı')
            ->schema([
                TextInput::make('elden_teslim_alan')->label('Teslim alan (adı soyadı / görevi)')->required(),
                DatePicker::make('elden_teslim_tarihi')->label('Teslim tarihi')->default(now())->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $this->kayit((int) $arguments['id'])?->update($data);
                unset($this->kayitlar);
                Notification::make()->title('Elden teslim kaydedildi')->success()->send();
            });
    }

    public function sil(int $id): void
    {
        $kayit = $this->kayit($id);

        if (! $kayit) {
            return;
        }

        // Deftere düşen "Ön Bilgilendirme" maddesi de kaldırılır.
        if ($defter = $this->firma->tespitOneriDefteri) {
            $kalan = collect($defter->maddeler ?? [])->reject(fn ($m) => ($m['on_bilgilendirme_id'] ?? null) === $kayit->id)->values()->all();

            if (count($kalan) !== count($defter->maddeler ?? [])) {
                $defter->update(['maddeler' => $kalan]);
            }
        }

        $kayit->delete();
        unset($this->kayitlar);
        Notification::make()->title('Bilgilendirme silindi')->success()->send();
    }
}
