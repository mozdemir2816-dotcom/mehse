<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

/**
 * Kayıt (Create/Edit) sayfalarında formun Kaydet düğmesi başlıkta da durur —
 * uzun formda aşağı inmeden kaydedilir (kullanıcı isteği 06.10.2026).
 * Düğme `form` kimlikli sayfa formunu gönderir; alttaki düğmeyle aynı işi yapar.
 */
trait KaydetUstte
{
    protected function ustKaydet(): Action
    {
        $aksiyon = $this instanceof CreateRecord
            ? $this->getCreateFormAction()
            : $this->getSaveFormAction();

        return $aksiyon->formId('form')->icon('heroicon-o-check');
    }

    /** Düzenleme sayfasında kaydetme sonrası: bağlı evraklar güncellendiyse bildir. */
    protected function afterSave(): void
    {
        \App\Support\BagliKayitGuncelleyici::bildir();
    }
}
