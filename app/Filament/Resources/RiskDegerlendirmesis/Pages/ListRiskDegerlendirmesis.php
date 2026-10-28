<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\Pages;

use App\Filament\Pages\RiskSihirbazi;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Support\HastaneRiskIceAktarici;
use App\Support\RiskDegerlendirmesiExcelOkuyucu;
use App\Support\RiskDegerlendirmesiOlusturucu;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListRiskDegerlendirmesis extends ListRecords
{
    protected static string $resource = RiskDegerlendirmesiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sihirbaz')
                ->label('Yeni (Sihirbaz)')
                ->icon('heroicon-o-sparkles')
                ->url(RiskSihirbazi::getUrl()),
            $this->excelYukleAksiyonu(),
            CreateAction::make()->label('Boş kayıt')->icon('heroicon-o-plus')->color('gray')
                ->modal()->modalWidth(Width::FourExtraLarge)->modalHeading('Yeni Risk Değerlendirmesi')
                // CreateRiskDegerlendirmesi::getRedirectUrl ile aynı: maddeler düzenleme sayfasında eklenir.
                ->createAnother(false)
                ->successRedirectUrl(fn (Model $record): string => RiskDegerlendirmesiResource::getUrl('edit', ['record' => $record])),
        ];
    }

    /**
     * Hazır risk analizi Excel'lerini yükler: hastane formu (Bursa Şehir Hastanesi) olduğu gibi —
     * puanlar ve biçim korunarak, dosya başına ayrı değerlendirme — alınır; bu sistemde hazırlanıp
     * indirilen / başka düzende hazırlanmış Excel'ler esnek başlık algılamayla standart değerlendirme olur.
     */
    private function excelYukleAksiyonu(): Action
    {
        return Action::make('excelYukle')
            ->label('Excel Yükle')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->modalWidth(Width::TwoExtraLarge)
            ->modalHeading('Risk Analizi Excel Yükle')
            ->modalDescription('Hastane risk formu olduğu gibi (puanlar ve biçim korunarak) alınır; her dosya ayrı bir değerlendirme olur. Bu sistemde hazırlanan veya başka düzende hazırlanmış Excel dosyaları standart risk değerlendirmesi olarak eklenir.')
            ->modalSubmitActionLabel('Yükle')
            ->schema([
                Select::make('firma_id')->label('Firma')->required()->searchable()
                    ->options(fn () => Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->pluck('unvan', 'id')->all()),
                FileUpload::make('dosyalar')->label('Excel dosyaları (.xlsx)')
                    ->multiple()->maxFiles(40)->required()
                    ->disk('local')->directory('risk-yukleme')->preserveFilenames()
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(20480),
            ])
            ->action(function (array $data): void {
                @set_time_limit(600);
                $firma = Firma::where('user_id', Filament::auth()->id())->findOrFail($data['firma_id']);
                $eklenen = $madde = 0;
                $notlar = [];

                foreach ((array) $data['dosyalar'] as $yol) {
                    $mutlak = Storage::disk('local')->path($yol);
                    $ad = basename($yol);
                    $birim = trim(preg_replace('/^\d+\s*-\s*/u', '', pathinfo($ad, PATHINFO_FILENAME)));

                    try {
                        $sayfalar = HastaneRiskIceAktarici::oku($mutlak);

                        if ($sayfalar) {
                            File::ensureDirectoryExists(storage_path('app/hastane-sablonlari'));
                            foreach ($sayfalar as $i => $sayfa) {
                                $adSayfa = count($sayfalar) > 1 ? $birim.' — '.$sayfa['sayfa'] : $birim;
                                if (RiskDegerlendirmesi::where('firma_id', $firma->id)->where('cikti_sablonu', 'hastane')->where('proje_adi', $adSayfa)->exists()) {
                                    $notlar[] = "{$adSayfa}: zaten var, atlandı";

                                    continue;
                                }
                                $sablon = Str::uuid().'.xlsx';
                                File::copy($mutlak, storage_path('app/hastane-sablonlari/'.$sablon));
                                HastaneRiskIceAktarici::kaydet($firma, $sayfa, $adSayfa, $sablon);
                                $eklenen++;
                                $madde += count($sayfa['maddeler']);
                            }

                            continue;
                        }

                        $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($mutlak);
                        if (! $sonuc['adaylar']) {
                            $notlar[] = "{$ad}: ".($sonuc['hatalar'][0] ?? 'risk maddesi bulunamadı');

                            continue;
                        }

                        // Sayfalar farklı yöntemdeyse (5x5 + eski Fine-Kinney sayfası) karışmasınlar: sayfa başına ayrı değerlendirme.
                        $sayfaGruplari = collect($sonuc['adaylar'])->groupBy(fn ($a) => $a['sayfa'] ?? '');
                        $yontemler = $sayfaGruplari->map(fn ($g) => $g->contains(fn ($a) => ($a['frekans'] ?? null) !== null))->unique();

                        if ($yontemler->count() > 1) {
                            foreach ($sayfaGruplari as $sayfaAdi => $grup) {
                                $fk = $grup->contains(fn ($a) => ($a['frekans'] ?? null) !== null);
                                $rd = RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($firma, $fk ? 'fine_kinney' : 'matris_5x5', $grup->all());
                                $rd->update(['proje_adi' => $birim.' — '.$sayfaAdi]);
                                $eklenen++;
                                $madde += $grup->count();
                            }

                            continue;
                        }

                        $fk = (bool) $yontemler->first();
                        $rd = RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($firma, $fk ? 'fine_kinney' : 'matris_5x5', $sonuc['adaylar']);
                        $rd->update(['proje_adi' => $birim]);
                        $eklenen++;
                        $madde += count($sonuc['adaylar']);
                    } catch (\Throwable $e) {
                        $notlar[] = "{$ad}: okunamadı ({$e->getMessage()})";
                    } finally {
                        Storage::disk('local')->delete($yol);
                    }
                }

                Notification::make()
                    ->title($eklenen ? "{$eklenen} değerlendirme, {$madde} madde yüklendi" : 'Hiçbir değerlendirme yüklenemedi')
                    ->body($notlar ? implode("\n", array_slice($notlar, 0, 8)) : null)
                    ->{$eklenen ? 'success' : 'danger'}()
                    ->persistent(! empty($notlar))
                    ->send();
            });
    }
}
