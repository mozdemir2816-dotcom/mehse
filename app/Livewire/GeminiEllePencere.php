<?php

namespace App\Livewire;

use App\Support\GeminiElleMod;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Gemini'de elle yap" penceresi (12.11.2026) — panelin her sayfasında (BODY_END). Yapay zekâ isteği başarısız olunca
 * GeminiElleMod 'gemini-elle-ac' olayıyla açar: istem kopyalanır, Gemini sohbetinin yanıtı yapıştırılır, kullanıcı
 * aynı düğmeye tekrar basınca bu yanıt kullanılır.
 */
class GeminiEllePencere extends Component
{
    public ?string $token = null;

    public string $istem = '';

    public bool $json = false;

    public bool $fotograf = false;

    public ?int $durum = null;

    public string $yanit = '';

    public bool $kaydedildi = false;

    #[On('gemini-elle-ac')]
    public function ac(string $token): void
    {
        $istek = GeminiElleMod::istek($token);
        if (! $istek) {
            return;
        }

        $this->token = $token;
        $this->istem = $istek['istem'];
        $this->json = $istek['json'];
        $this->fotograf = $istek['fotograf'];
        $this->durum = $istek['durum'];
        $this->yanit = '';
        $this->kaydedildi = false;
        $this->resetErrorBag();
        $this->dispatch('open-modal', id: 'gemini-elle');
    }

    public function kaydet(): void
    {
        if (! $this->token) {
            return;
        }
        $this->resetErrorBag();

        if ($hata = GeminiElleMod::yanitKaydet($this->token, $this->yanit)) {
            $this->addError('yanit', $hata);

            return;
        }

        $this->kaydedildi = true;
        Notification::make()->title('Yanıt kaydedildi')->body('Şimdi aynı yapay zekâ düğmesine tekrar basın; sistem bu yanıtı kullanacak.')->success()->send();
    }

    public function render()
    {
        return view('livewire.gemini-elle-pencere');
    }
}
