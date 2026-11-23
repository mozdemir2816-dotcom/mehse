<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\OrtamOlcumu as OrtamOlcumuModel;
use App\Models\TespitOneriDefteri as TespitOneriDefteriModel;
use App\Filament\Support\ImzaSecenegi;
use App\Support\OrtamOlcumuUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * İş Hijyeni / Ortam Ölçümleri Takibi — işyerinde yapılması gereken ölçümler
 * (gürültü, toz, aydınlatma, termal konfor, kimyasal maruziyet vb.) listelenir;
 * her ölçüm için tarih, laboratuvar, ölçülen/sınır değer ve sonuç girilir.
 * Bir sonraki ölçüm tarihi periyoda göre otomatik hesaplanır.
 */
class OrtamOlcumleri extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.ortam-olcumleri';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|UnitEnum|null $navigationGroup = 'Saha Dışı Kayıtlar';

    /** Menüde gizli — Tesis Güvenliği, Ekipman & Dış Paydaş Merkezi sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'ortam-olcumleri';

    protected static ?string $title = 'Ortam Ölçümleri Takibi';

    protected static ?string $navigationLabel = 'Ortam Ölçümleri';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    /** @var array<int, array<string, mixed>> */
    public array $satirlar = [];

    public ?string $genelNot = null;

    // Serbest ölçüm ekleme
    public ?string $yeniParametre = null;

    public ?string $yeniGrup = null;

    public int $yeniPeriyot = 24;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->secenekler()
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    #[Computed]
    public function olcum(): ?OrtamOlcumuModel
    {
        return $this->firma ? OrtamOlcumuModel::firmaIcin($this->firma) : null;
    }

    #[Computed]
    public function katalog(): array
    {
        return config('isg.ortam_olcum.katalog', []);
    }

    #[Computed]
    public function sonuclar(): array
    {
        return config('isg.ortam_olcum.sonuclar', []);
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->olcum);

        $olcum = $this->olcum();
        $this->satirlar = $olcum?->olcumler ?? [];
        $this->genelNot = $olcum?->genel_not;
    }

    public function katalogdanEkle(string $grup, string $ad): void
    {
        $madde = collect(config('isg.ortam_olcum.katalog.'.$grup, []))->firstWhere('ad', $ad);

        $this->satirlar[] = [
            'parametre' => $ad,
            'grup' => $grup,
            'bolge' => null,
            'periyot_ay' => $madde['periyot_ay'] ?? 24,
            'olcum_tarihi' => null,
            'sonraki_olcum_tarihi' => null,
            'laboratuvar' => null,
            'rapor_no' => null,
            'olculen_deger' => null,
            'sinir_deger' => $madde['sinir_deger'] ?? null,
            'birim' => $madde['birim'] ?? null,
            'sonuc' => 'bekliyor',
            'not' => null,
        ];
    }

    public function serbestOlcumEkle(): void
    {
        if (blank($this->yeniParametre)) {
            Notification::make()->title('Ölçüm parametresi gerekli')->danger()->send();

            return;
        }

        $this->satirlar[] = [
            'parametre' => $this->yeniParametre,
            'grup' => $this->yeniGrup ?: 'Diğer',
            'bolge' => null,
            'periyot_ay' => max(1, $this->yeniPeriyot),
            'olcum_tarihi' => null,
            'sonraki_olcum_tarihi' => null,
            'laboratuvar' => null,
            'rapor_no' => null,
            'olculen_deger' => null,
            'sinir_deger' => null,
            'birim' => null,
            'sonuc' => 'bekliyor',
            'not' => null,
        ];

        $this->reset('yeniParametre', 'yeniGrup', 'yeniPeriyot');
        $this->yeniPeriyot = 24;
    }

    /** Ölçülen / sınır değer değişince sonuç sayısal karşılaştırmadan otomatik güncellenir. */
    public function updatedSatirlar(mixed $deger, string $anahtar): void
    {
        [$i, $alan] = array_pad(explode('.', $anahtar, 2), 2, null);

        if (! in_array($alan, ['olculen_deger', 'sinir_deger'], true) || ! isset($this->satirlar[$i])) {
            return;
        }

        $oto = OrtamOlcumuModel::otomatikSonuc(
            $this->satirlar[$i]['olculen_deger'] ?? null,
            $this->satirlar[$i]['sinir_deger'] ?? null,
            $this->satirlar[$i]['birim'] ?? null,
            $this->satirlar[$i]['parametre'] ?? null,
        );

        if ($oto) {
            $this->satirlar[$i]['sonuc'] = $oto;
        }
    }

    /**
     * Periyodik yeni ölçüm: mevcut sonuç satırın geçmişine taşınır, ölçüm
     * alanları boşaltılır (parametre, nokta, sınır, birim, laboratuvar kalır).
     */
    public function yeniOlcum(int $index): void
    {
        $s = $this->satirlar[$index] ?? null;

        if (! $s || blank($s['olcum_tarihi'] ?? null)) {
            Notification::make()->title('Bu satırda henüz ölçüm yok — tarih ve değeri doğrudan girin')->warning()->send();

            return;
        }

        $s['gecmis'] = [
            ...($s['gecmis'] ?? []),
            collect($s)->only(['olcum_tarihi', 'laboratuvar', 'rapor_no', 'olculen_deger', 'sinir_deger', 'birim', 'sonuc'])->all(),
        ];
        $s['olcum_tarihi'] = null;
        $s['sonraki_olcum_tarihi'] = null;
        $s['rapor_no'] = null;
        $s['olculen_deger'] = null;
        $s['sonuc'] = 'bekliyor';

        $this->satirlar[$index] = $s;

        Notification::make()
            ->title('Önceki ölçüm geçmişe alındı')
            ->body('Yeni ölçüm tarihini ve değerini girip Kaydet\'e basın.')
            ->success()
            ->send();
    }

    public function olcumSil(int $index): void
    {
        unset($this->satirlar[$index]);
        $this->satirlar = array_values($this->satirlar);
        $this->kaydet(sessiz: true);
    }

    public function kaydet(bool $sessiz = false): void
    {
        $olcum = $this->olcum();

        if (! $olcum) {
            Notification::make()->title('Önce bir firma seçin')->danger()->send();

            return;
        }

        // Ölçüm tarihi temizlendiyse sıfırla; tarih/periyot doluysa sonraki ölçüm tarihini güncelle.
        $satirlar = array_map(function (array $s): array {
            if (empty($s['olcum_tarihi'])) {
                $s['sonraki_olcum_tarihi'] = null;
            } elseif (! empty($s['periyot_ay']) && (int) $s['periyot_ay'] > 0) {
                $s['sonraki_olcum_tarihi'] = \Illuminate\Support\Carbon::parse($s['olcum_tarihi'])
                    ->addMonthsNoOverflow((int) $s['periyot_ay'])
                    ->toDateString();
            }

            return $s;
        }, $this->satirlar);

        $olcum->update(['olcumler' => $satirlar, 'genel_not' => $this->genelNot]);

        $this->satirlar = $olcum->fresh()->olcumler ?? [];

        if (! $sessiz) {
            Notification::make()->title('Ortam ölçümleri listesi kaydedildi')->success()->send();
        }
    }

    /**
     * Sınır değer aşımı bulunan ölçüm satırlarını tek tıkla DÖF Oluştur sayfasına aktarır.
     * $index verilirse yalnızca ilgili satır, null ise tüm aşım satırları aktarılır.
     */
    public function asimlariDofaAktar(?int $index = null): void
    {
        if (! $this->firma) {
            Notification::make()->title('Önce bir firma seçin')->danger()->send();

            return;
        }

        $this->kaydet(sessiz: true);

        $hedef = $this->asimSatirlari($index);

        if ($hedef === []) {
            Notification::make()->title('Aktarılacak sınır değer aşımı bulunamadı')->warning()->send();

            return;
        }

        $maddeler = array_map(function (array $s): array {
            $hazir = $this->asimMaddesiHazirla($s);

            return [
                'tespit' => $hazir['tespit'],
                'oncelik' => 'yuksek',
                'oneri' => $hazir['oneri'],
                'sorumlu' => null,
                'termin' => now()->addDays(15)->toDateString(),
                'durum' => 'acik',
            ];
        }, $hedef);

        session([
            'dof_aktarim' => [
                'firma_id' => $this->firmaId,
                'kaynak' => 'Ortam Ölçümleri',
                'maddeler' => $maddeler,
            ],
        ]);

        $this->redirect(DofOlustur::getUrl());
    }

    /**
     * Sınır değer aşımı bulunan ölçüm satırlarını firmanın Onaylı Defterine
     * (Tespit ve Öneri Defteri) mevzuat dayanağı ile birlikte işler.
     */
    public function asimlariDeftereAktar(?int $index = null): void
    {
        if (! $this->firma) {
            Notification::make()->title('Önce bir firma seçin')->danger()->send();

            return;
        }

        $this->kaydet(sessiz: true);

        $hedef = $this->asimSatirlari($index);

        if ($hedef === []) {
            Notification::make()->title('Deftere işlenecek sınır değer aşımı bulunamadı')->warning()->send();

            return;
        }

        $defter = TespitOneriDefteriModel::firmaIcin($this->firma);
        $mevcut = $defter->maddeler ?? [];

        foreach ($hedef as $s) {
            $hazir = $this->asimMaddesiHazirla($s);
            $mevcut[] = [
                'tespit' => $hazir['tespit'],
                'oneri' => $hazir['oneri'],
                'dayanak' => $hazir['dayanak'],
                'oncelik' => 'yuksek',
                'foto_yolu' => null,
            ];
        }

        $defter->update(['maddeler' => $mevcut]);

        Notification::make()
            ->title(count($hedef).' sınır değer aşımı Onaylı Deftere işlendi')
            ->success()
            ->send();

        $this->redirect(TespitOneriDefteri::getUrl(['firma' => $this->firmaId]));
    }

    /** @return array<int, array<string, mixed>> */
    private function asimSatirlari(?int $index): array
    {
        if ($index !== null) {
            return isset($this->satirlar[$index]) ? [$this->satirlar[$index]] : [];
        }

        return array_values(array_filter(
            $this->satirlar,
            fn (array $s): bool => ($s['sonuc'] ?? '') === 'asim',
        ));
    }

    /** @return array{tespit: string, oneri: string, dayanak: string} */
    private function asimMaddesiHazirla(array $s): array
    {
        $parametre = trim((string) ($s['parametre'] ?? 'Ortam Ölçümü'));
        $bolge = trim((string) ($s['bolge'] ?? ''));
        $birim = trim((string) ($s['birim'] ?? ''));
        $olculen = trim((string) ($s['olculen_deger'] ?? ''));
        $sinir = trim((string) ($s['sinir_deger'] ?? ''));
        $raporNo = trim((string) ($s['rapor_no'] ?? ''));
        $tarih = filled($s['olcum_tarihi'] ?? null)
            ? \Illuminate\Support\Carbon::parse($s['olcum_tarihi'])->format('d.m.Y')
            : '';

        $detay = [];
        if ($olculen !== '') {
            $detay[] = 'ölçülen: '.trim($olculen.' '.$birim);
        }
        if ($sinir !== '') {
            $detay[] = 'sınır değer: '.trim($sinir.' '.$birim);
        }
        if ($tarih !== '') {
            $detay[] = 'tarih: '.$tarih;
        }
        if ($raporNo !== '') {
            $detay[] = 'rapor: '.$raporNo;
        }

        $tespit = 'Ortam ölçümü sınır değer aşımı — '.$parametre
            .($bolge !== '' ? ' ('.$bolge.')' : '')
            .($detay !== [] ? ': '.implode(', ', $detay).'.' : '.');

        $oneri = 'Aşımın kaynağı incelenerek mühendislik ve toplu koruma önlemleri (havalandırma, yalıtım, bakım, kaynak kontrolü) uygulanmalı; önlem tamamlanıncaya kadar maruz kalan çalışanlara uygun KKD kullandırılmalı ve akredite laboratuvar ile kontrol ölçümü tekrarlanmalıdır.';

        return [
            'tespit' => $tespit,
            'oneri' => $oneri,
            'dayanak' => '6331 sayılı Kanun m.10 · İş Hijyeni Ölçüm, Test ve Analizleri Hakkında Yönetmelik',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kaydet')
                ->label('Kaydet')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => $this->kaydet()),

            Action::make('pdf')
                ->label('PDF (Takip Listesi)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => filled($this->olcum()?->olcumler))
                ->action(function () {
                    $this->kaydet(sessiz: true);

                    return OrtamOlcumuUretici::pdf($this->olcum());
                }),

            Action::make('excel')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => filled($this->olcum()?->olcumler))
                ->action(function () {
                    $this->kaydet(sessiz: true);

                    return OrtamOlcumuUretici::excel($this->olcum());
                }),
        ];
    }
}
