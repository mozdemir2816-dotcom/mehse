<?php

namespace App\Filament\Resources\Firmas\Pages;

use App\Filament\Concerns\KaydetUstte;
use App\Filament\Pages\DokumanYonetimi;
use App\Filament\Pages\FirmaDenetimOperasyon;
use App\Filament\Resources\Firmas\EvrakPaketiAksiyonu;
use App\Filament\Resources\Firmas\FirmaResource;
use App\Filament\Resources\Firmas\IsyeriGirisAksiyonu;
use App\Filament\Resources\Firmas\Tables\FirmasTable;
use App\Filament\Support\ImzaSecenegi;
use App\Models\Firma;
use App\Support\FirmaDosyaFihristiUretici;
use App\Support\IsKalemiEvrakHazirlayici;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;

class EditFirma extends EditRecord
{
    use KaydetUstte;

    protected static string $resource = FirmaResource::class;

    /**
     * Kayıt sekmeleri yan yana değil, alt alta tıklayınca açılan bölümler olarak gösterilir (05.11.2026,
     * kullanıcı isteği): başlıkta kayıt sayısı, içerik açılınca (tembel) yüklenir.
     */
    public function getRelationManagersContentComponent(): Component
    {
        $firma = $this->getRecord();
        $veri = $this->getRelationManagerLivewireData();

        $bolumler = collect($this->getCachedRelationManagers())
            ->map(function ($yonetici) use ($firma, $veri): Section {
                $sinif = $this->normalizeRelationManagerClass($yonetici);
                $adet = $firma->{$sinif::getRelationshipName()}()->count();

                return Section::make($sinif::getTitle($firma, static::class).' ('.$adet.')')
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed()
                    ->key('bolum-'.class_basename($sinif))
                    ->schema([
                        Livewire::make($sinif, [...$veri, ...$sinif::getDefaultProperties()])
                            ->lazy()
                            ->key($sinif),
                    ]);
            })
            ->values()
            ->all();

        return Group::make($bolumler);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->ustKaydet(),
            Action::make('durumMerkezi')
                ->label('Durum Merkezi')
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->url(fn (Firma $record) => FirmaDenetimOperasyon::getUrl(['firma' => $record->id, 'bolum' => 'firma360'])),

            Action::make('isgDosyasi')
                ->label('📁 İSG Dosyası & Evrak Takip')
                ->color('gray')
                ->url(fn (Firma $record) => \App\Filament\Pages\FirmaIsgDosyasi::getUrl(['firma' => $record->id])),

            Action::make('teftisPaketi')
                ->label('📦 Teftiş Paketi (ZIP)')
                ->color('success')
                ->action(function (Firma $record) {
                    $yanit = \App\Support\FirmaIsgDosyasi::teftisPaketi($record);

                    if (! $yanit) {
                        Notification::make()->title('Pakete eklenecek evrak yok')->warning()->send();
                    }

                    return $yanit;
                }),

            Action::make('arsivDosyasi')
                ->label('Arşiv Dosyası')
                ->icon('heroicon-o-folder-open')
                ->color('gray')
                ->url(fn (Firma $record) => DokumanYonetimi::getUrl(['firma' => $record->id])),

            IsyeriGirisAksiyonu::make(),

            Action::make('dosyaFihristi')
                ->label('Dosya Fihristi (İçindekiler)')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->schema([ImzaSecenegi::alan()])
                ->action(fn (Firma $record) => FirmaDosyaFihristiUretici::pdf($record)),

            EvrakPaketiAksiyonu::make('tumEvrakIndir')->label('Tüm Evrakları İndir (ZIP)'),

            Action::make('isKalemiEvraklariniHazirla')
                ->label('Evrakları Hazırla')->tooltip('İş Kalemlerine Göre Evrakları Hazırla')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Seçili iş kalemlerine göre eksik KKD satırları, çalışma talimatları ve (varsa) bir risk değerlendirmesi taslağı eklenir. Var olan kayıtların üstüne yazılmaz.')
                ->visible(fn (Firma $record): bool => filled($record->is_kalemleri))
                ->action(function (Firma $record) {
                    $ozet = IsKalemiEvrakHazirlayici::hazirla($record);

                    $satirlar = [
                        $ozet['kkd_eklenen'].' KKD satırı eklendi',
                        $ozet['talimat_eklenen'].' talimat eklendi',
                        $ozet['risk_olusturuldu']
                            ? 'yeni risk değerlendirmesi taslağı oluşturuldu'
                            : ($ozet['risk_atlandi_neden'] ?? 'risk değerlendirmesi için eşleşen tehlike bulunamadı'),
                    ];

                    if ($ozet['egitim_konu_sayisi'] > 0) {
                        $satirlar[] = $ozet['egitim_konu_sayisi'].' eğitim konusu, eğitim kayıtlarında önerilecek';
                    }

                    Notification::make()
                        ->title('Evraklar hazırlandı')
                        ->body(implode(' · ', $satirlar))
                        ->success()->send();
                }),

            FirmasTable::silAksiyonu(DeleteAction::make()),
        ];
    }
}
