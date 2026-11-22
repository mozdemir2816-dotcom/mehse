<?php

namespace App\Filament\Support;

use App\Models\Firma;
use App\Models\TeftisPaylasimi;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View;
use Illuminate\Support\Str;

/**
 * Müfettiş / Denetçi için süreli, salt-okunur ve erişim loglu teftiş bağlantısı + QR üretir (C21).
 */
class MufettisYetkilendirAksiyonu
{
    /**
     * @param  Closure(): (?Firma)  $firmaSaglayici
     */
    public static function make(Closure $firmaSaglayici): Action
    {
        return Action::make('mufettisYetkilendir')
            ->label('Müfettiş Yetkilendir (QR / Link)')
            ->icon('heroicon-o-shield-exclamation')
            ->color('warning')
            ->outlined()
            ->modalHeading('Müfettiş / Denetçi Teftiş Erişimi')
            ->modalDescription('Çalışma Bakanlığı müfettişi veya işveren denetçisi için süreli, salt-okunur ve erişim loglu teftiş bağlantısı oluşturun.')
            ->modalWidth('2xl')
            ->modalSubmitActionLabel('Yeni Teftiş Linki Oluştur')
            ->schema(function () use ($firmaSaglayici): array {
                $firma = $firmaSaglayici();
                $mevcutlar = $firma
                    ? TeftisPaylasimi::query()
                        ->where('user_id', Filament::auth()->id())
                        ->where('firma_id', $firma->id)
                        ->latest('id')
                        ->limit(8)
                        ->get()
                    : collect();

                return [
                    TextInput::make('baslik')
                        ->label('Teftiş / Denetim Başlığı')
                        ->default('ÇSGB / İş Müfettişi Salt-Okunur Teftiş Dosyası')
                        ->required()
                        ->maxLength(200),
                    TextInput::make('mufettis_adi')
                        ->label('Denetçi / Müfettiş Adı (Opsiyonel)')
                        ->maxLength(160),
                    Select::make('sure_gun')
                        ->label('Geçerlilik Süresi')
                        ->options([
                            1 => '24 Saat (1 Gün)',
                            3 => '3 Gün',
                            7 => '7 Gün (Önerilen)',
                            30 => '30 Gün',
                        ])
                        ->default(7)
                        ->required(),
                    Textarea::make('notlar')
                        ->label('Denetçiye Kısa Not (Opsiyonel)')
                        ->rows(2),
                    View::make('filament.components.mufettis-paylasim-listesi')
                        ->viewData([
                            'firma' => $firma,
                            'paylasimlar' => $mevcutlar,
                        ]),
                ];
            })
            ->action(function (array $data) use ($firmaSaglayici): void {
                $firma = $firmaSaglayici();
                if (! $firma) {
                    Notification::make()->title('Önce firma seçin')->warning()->send();

                    return;
                }

                $kayit = TeftisPaylasimi::create([
                    'user_id' => Filament::auth()->id(),
                    'firma_id' => $firma->id,
                    'token' => Str::random(40),
                    'baslik' => $data['baslik'],
                    'mufettis_adi' => $data['mufettis_adi'] ?? null,
                    'bitis_tarihi' => now()->addDays(max(1, (int) ($data['sure_gun'] ?? 7))),
                    'aktif' => true,
                    'notlar' => $data['notlar'] ?? null,
                ]);

                $url = route('mufettis.teftis', ['token' => $kayit->token]);

                Notification::make()
                    ->title('Müfettiş Teftiş Linki Oluşturuldu')
                    ->body($url)
                    ->actions([
                        Action::make('teftisAc')
                            ->label('Teftiş Ekranını Önizle')
                            ->url($url, shouldOpenInNewTab: true)
                            ->button(),
                    ])
                    ->success()
                    ->persistent()
                    ->send();
            });
    }
}
