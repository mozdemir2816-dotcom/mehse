<?php

namespace App\Filament\Portal\Pages;

use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\KkdTalebi;
use App\Models\SahaBulgusu;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Çalışan portalı ana sayfa — kendisine atanmış uzaktan eğitimler ve durumları.
 */
class Egitimlerim extends Page
{
    protected string $view = 'filament.portal.pages.egitimlerim';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $title = 'Eğitimlerim';

    protected static ?string $slug = '/';

    public static function getNavigationLabel(): string
    {
        return 'Eğitimlerim';
    }

    /** KVKK aydınlatma + taahhüt onaylanmadan eğitimler açılmaz. */
    public function mount()
    {
        if (Filament::auth()->user()?->kvkk_onay_at === null) {
            return redirect(KvkkOnay::getUrl());
        }
    }

    /** KKD Talep formu (K5). */
    public string $kkdTuru = 'İş Ayakkabısı';

    public string $kkdBeden = '';

    public string $kkdNeden = 'yipranma';

    public string $kkdAciklama = '';

    /** @return Collection<int, EgitimAtamasi> */
    #[Computed]
    public function atamalar(): Collection
    {
        return EgitimAtamasi::query()
            ->where('calisan_id', Filament::auth()->id())
            ->with(['paket.dersler', 'sinavSonuclari'])
            ->latest()
            ->get();
    }

    /** Çalışanın KKD talepleri (K5). */
    #[Computed]
    public function kkdTalepleri(): Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('kkd_talepleri')) {
            return collect();
        }

        return KkdTalebi::query()
            ->where('calisan_id', Filament::auth()->id())
            ->latest('id')
            ->limit(15)
            ->get();
    }

    public function kkdTalepGonder(): void
    {
        /** @var ?Calisan $calisan */
        $calisan = Filament::auth()->user();
        if (! $calisan || trim($this->kkdTuru) === '') {
            return;
        }

        KkdTalebi::create([
            'firma_id' => $calisan->firma_id,
            'calisan_id' => $calisan->id,
            'calisan_ad_soyad' => (string) $calisan->ad_soyad,
            'kkd_turu' => trim($this->kkdTuru),
            'beden' => trim($this->kkdBeden) ?: null,
            'neden' => array_key_exists($this->kkdNeden, KkdTalebi::NEDENLER) ? $this->kkdNeden : 'yipranma',
            'aciklama' => trim($this->kkdAciklama) ?: null,
            'durum' => 'bekliyor',
        ]);

        $this->reset('kkdBeden', 'kkdAciklama');
        unset($this->kkdTalepleri);
        Notification::make()->title('KKD talebiniz iletildi')->body('İSG biriminiz onayladığında buradan takip edebilirsiniz.')->success()->send();
    }

    /** Çalışanın adına açılmış (sorumlu alanında adı geçen) açık DÖF / saha bulguları (K5). */
    #[Computed]
    public function atananDofler(): Collection
    {
        /** @var ?Calisan $calisan */
        $calisan = Filament::auth()->user();
        if (! $calisan || blank($calisan->ad_soyad)) {
            return collect();
        }

        return SahaBulgusu::query()
            ->where('firma_id', $calisan->firma_id)
            ->where('durum', '!=', 'kapandi')
            ->where('sorumlu', 'like', '%'.trim((string) $calisan->ad_soyad).'%')
            ->orderByRaw('termin IS NULL, termin ASC')
            ->limit(15)
            ->get();
    }

    /** Çalışan kendi sorumlu olduğu DÖF için düzeltme fotoğrafı/kanıtı yükler (K5). */
    public function calisanDofKanitAction(): Action
    {
        return Action::make('calisanDofKanit')
            ->modalHeading('DÖF Düzeltme Kanıtı Gönder')
            ->modalDescription('Yaptığınız düzeltmenin fotoğrafını ve açıklamasını gönderin; İSG uzmanınız inceleyip onayladıktan sonra kapatacaktır.')
            ->modalSubmitActionLabel('Uzman Onayına Gönder')
            ->schema([
                Textarea::make('not')->label('Yapılan Düzeltme')->rows(3)->required(),
                FileUpload::make('dosya')
                    ->label('Düzeltme Fotoğrafı / Belge')
                    ->disk('public')
                    ->directory('dof-kanit')
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->maxSize(10240),
            ])
            ->action(function (array $data, array $arguments): void {
                /** @var ?Calisan $calisan */
                $calisan = Filament::auth()->user();
                $b = SahaBulgusu::query()
                    ->where('firma_id', $calisan?->firma_id)
                    ->where('durum', '!=', 'kapandi')
                    ->find((int) ($arguments['id'] ?? 0));

                if (! $b || ! $calisan) {
                    return;
                }

                $b->update([
                    'isveren_kapanis_talebi_at' => now(),
                    'isveren_kapanis_gonderen' => $calisan->ad_soyad.' (Çalışan Portalı)',
                    'isveren_kapanis_notu' => trim($data['not']),
                    'isveren_kapanis_dosya' => $data['dosya'] ?? $b->isveren_kapanis_dosya,
                    'durum' => $b->durum === 'acik' ? 'devam_ediyor' : $b->durum,
                ]);

                unset($this->atananDofler);
                Notification::make()->title('Düzeltme kanıtınız İSG uzmanına gönderildi')->success()->send();
            });
    }
}
