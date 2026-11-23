<?php

namespace App\Filament\Resources\EgitimPaketis\Pages;

use App\Filament\Resources\EgitimPaketis\EgitimPaketiResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\CreateRecord;

class CreateEgitimPaketi extends CreateRecord
{
    use \App\Filament\Concerns\KaydetUstte;

    protected function getHeaderActions(): array
    {
        return [$this->ustKaydet(), $this->standartPaketAction()];
    }

    /**
     * Yönetmelik standart paketi: 4 modülün ders BAŞLIKLARINI Ek-1 konularından hazırlar; video linklerini
     * kullanıcı ekler (link boşken kayıt edilemez). 4. modül tehlikeli / çok tehlikeli sınıfta yüz yüze verilir.
     */
    protected function standartPaketAction(): Action
    {
        return Action::make('standartPaket')
            ->label('⚡ Standart Paket')->tooltip('⚡ Yönetmelik Standart Paketini Yükle')
            ->color('warning')
            ->modalHeading('Yönetmelik standart paketi')
            ->modalDescription('Modül başlıkları hazır gelir; her derse video linkini siz eklersiniz. İstemediğiniz dersi silebilirsiniz.')
            ->schema([
                Select::make('tur')->label('Paket')->required()->native(false)->default('ilk_defa')->live()
                    ->options([
                        'ilk_defa' => 'Temel İSG Eğitimi — 8 saat online (hiç eğitim kaydı olmayanlar)',
                        'yenileme' => 'Temel İSG Yenileme Eğitimi — 4 saat online + 4 saat yüz yüze',
                    ]),
                Select::make('sinif')->label('Tehlike sınıfı')->required()->native(false)->default('az_tehlikeli')
                    ->visible(fn ($get) => $get('tur') !== 'yenileme')
                    ->options([
                        'az_tehlikeli' => 'Az Tehlikeli — 8 saat, tamamı online verilebilir',
                        'tehlikeli' => 'Tehlikeli — 12 saat: 8 saat online + 4 saat yüz yüze (4. modül dahil)',
                        'cok_tehlikeli' => 'Çok Tehlikeli — 16 saat: 8 saat online + 8 saat yüz yüze (4. modül dahil)',
                    ]),
            ])
            ->action(function (array $data): void {
                if (($data['tur'] ?? 'ilk_defa') === 'yenileme') {
                    $this->form->fill([
                        'ad' => 'Temel İş Sağlığı ve Güvenliği Yenileme Eğitimi (4 Saat)',
                        'egitim_turu' => 'yenileme',
                        'aciklama' => 'Temel İSG eğitimi yenilemesi: Ek-1 konularının 4 ders saati online tekrarı; toplam 8 saatin kalan 4 saati yüz yüze verilir.',
                        'gecme_puani' => 60,
                        'video_zorunlu_yuzde' => 90,
                        'sinav_soru_sayisi' => 20,
                        'aktif' => true,
                        'modul1_dersler' => [],
                        'modul2_dersler' => [],
                        'modul3_dersler' => [],
                        'modul4_dersler' => [],
                    ]);

                    return;
                }
                $data['sinif'] ??= 'az_tehlikeli';
                $saat = ['az_tehlikeli' => 8, 'tehlikeli' => 12, 'cok_tehlikeli' => 16][$data['sinif']];
                $konular = array_values(config('isg.uzaktan_egitim.ek1_konulari'));
                $ders = fn (array $liste) => collect($liste)
                    ->map(fn ($b) => ['baslik' => preg_replace('/^\p{L}\)\s*/u', '', $b), 'video_url' => null])
                    ->all();

                $yuzYuze = $data['sinif'] !== 'az_tehlikeli';

                $this->form->fill([
                    'egitim_turu' => 'ilk_defa',
                    'ad' => 'Temel İSG Eğitimi — '.config('isg.tehlike_siniflari.'.$data['sinif'])." ({$saat} Saat)",
                    'aciklama' => 'Çalışanların İSG Eğitimleri Yönetmeliği (RG 02.04.2026) Ek-1 ana konuları.'
                        .($yuzYuze ? ' 4. modül yüz yüze verilir; sınav sonrası İGU onayı gerekir.' : ''),
                    'gecme_puani' => 60,
                    'video_zorunlu_yuzde' => 90,
                    'sinav_soru_sayisi' => 20,
                    'aktif' => true,
                    'modul1_dersler' => $ders($konular[0] ?? []),
                    'modul2_dersler' => $ders($konular[1] ?? []),
                    'modul3_dersler' => $ders($konular[2] ?? []),
                    'modul4_dersler' => $yuzYuze ? [] : [['baslik' => 'Faaliyetin genel tehlike ve riskleri', 'video_url' => null]],
                ]);
            });
    }

    protected static string $resource = EgitimPaketiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Filament::auth()->id();

        return $data;
    }
}
