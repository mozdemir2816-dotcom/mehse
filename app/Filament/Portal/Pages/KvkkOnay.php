<?php

namespace App\Filament\Portal\Pages;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Çalışanın portala ilk girişinde KVKK aydınlatma metnini ve eğitim taahhüdünü onaylaması (07.11.2026).
 * Onaylanmadan eğitimler açılmaz; onay zamanı ve IP'si çalışan kaydına yazılır (denetim kanıtı).
 */
class KvkkOnay extends Page
{
    protected string $view = 'filament.portal.pages.kvkk-onay';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'kvkk-onay';

    protected static ?string $title = 'Aydınlatma ve Taahhüt';

    public bool $aydinlatma = false;

    public bool $taahhut = false;

    public function mount()
    {
        if (Filament::auth()->user()?->kvkk_onay_at !== null) {
            return redirect(Egitimlerim::getUrl());
        }
    }

    public function onayla()
    {
        if (! $this->aydinlatma || ! $this->taahhut) {
            Notification::make()->title('Devam etmek için iki kutuyu da işaretleyin')->warning()->send();

            return null;
        }

        Filament::auth()->user()->forceFill([
            'kvkk_onay_at' => now(),
            'kvkk_onay_ip' => request()->ip(),
        ])->save();

        return redirect(Egitimlerim::getUrl());
    }
}
