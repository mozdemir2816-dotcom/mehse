<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AcilDurumPlani;
use App\Filament\Pages\RiskSihirbazi;
use App\Filament\Pages\YillikPlan\YillikCalismaPlani;
use App\Filament\Resources\Calisans\CalisanResource;
use App\Filament\Resources\Firmas\FirmaResource;
use App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use App\Support\KullaniciAyarlari;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

/**
 * O12 (23.11.2026): ilk kullanım başlangıç listesi — "Şimdi ne yapmalıyım?" sorusuna 6 adımlık cevap.
 * Adımlar gerçek veriden işaretlenir (aktif firmalardan en az birinde kayıt var mı); hepsi tamamlanınca
 * ya da kullanıcı "Gizle" deyince görünmez (KullaniciAyarlari: 'baslangic_gizli').
 */
class BaslangicListesiWidget extends Widget
{
    protected static ?int $sort = -9;

    protected string $view = 'filament.widgets.baslangic-listesi-widget';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        $kullanici = Filament::auth()->user();

        return $kullanici instanceof User
            && ! (KullaniciAyarlari::hepsi($kullanici)['baslangic_gizli'] ?? false)
            && collect(static::adimlar($kullanici))->contains('tamam', false);
    }

    public function gizle(): void
    {
        /** @var User $kullanici */
        $kullanici = Filament::auth()->user();
        KullaniciAyarlari::kaydet($kullanici, ['baslangic_gizli' => true]);

        $this->redirect(request()->header('Referer') ?: url()->current(), navigate: true);
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $adimlar = static::adimlar(Filament::auth()->user());
        $biten = collect($adimlar)->where('tamam', true)->count();

        return [
            'adimlar' => $adimlar,
            'biten' => $biten,
            'toplam' => count($adimlar),
            // Sıradaki = ilk tamamlanmamış adım; kart yalnız onu birincil düğmeyle öne çıkarır.
            'siradaki' => collect($adimlar)->search(fn (array $a) => ! $a['tamam']),
        ];
    }

    /**
     * @return array<int, array{baslik: string, aciklama: string, tamam: bool, url: string, eylem: string}>
     */
    public static function adimlar(User $kullanici): array
    {
        $firmalar = Firma::query()->where('user_id', $kullanici->id)->where('aktif', true);
        $varMi = fn (string $iliski, ?\Closure $kosul = null): bool => (clone $firmalar)->whereHas($iliski, $kosul)->exists();

        return [
            [
                'baslik' => 'Firma ekleyin',
                'aciklama' => 'SGK sicil ya da NACE kodunu girin; tehlike sınıfı otomatik gelir.',
                'tamam' => (clone $firmalar)->exists(),
                'url' => FirmaResource::getUrl('index', ['action' => 'create']),
                'eylem' => 'Firma ekle',
            ],
            [
                'baslik' => 'Çalışan listesini yükleyin',
                'aciklama' => 'Excel, SGK işe giriş listesi ya da elle.',
                'tamam' => Calisan::query()->where('aktif', true)
                    ->whereIn('firma_id', (clone $firmalar)->select('id'))->exists(),
                'url' => CalisanResource::getUrl('index'),
                'eylem' => 'Çalışanlar',
            ],
            [
                'baslik' => 'İSG profesyonellerini atayın',
                'aciklama' => 'Firmaya iş güvenliği uzmanı ve işyeri hekimi seçin.',
                'tamam' => (clone $firmalar)->whereNotNull('igu_id')->exists(),
                'url' => IsgProfesyoneliResource::getUrl('index'),
                'eylem' => 'Profesyoneller',
            ],
            [
                'baslik' => 'Risk değerlendirmesi hazırlayın',
                'aciklama' => 'Sihirbaz sektör şablonuyla başlatır.',
                'tamam' => $varMi('riskDegerlendirmeleri'),
                'url' => RiskSihirbazi::getUrl(),
                'eylem' => 'Risk sihirbazı',
            ],
            [
                'baslik' => 'Acil durum planını oluşturun',
                'aciklama' => 'Konuları seçin, ekipleri ve krokiyi ekleyin.',
                'tamam' => $varMi('acilDurumPlani', fn ($q) => $q->whereNotNull('konular')->where('konular', '!=', '[]')),
                'url' => AcilDurumPlani::getUrl(),
                'eylem' => 'Acil durum planı',
            ],
            [
                'baslik' => 'Yıllık çalışma ve eğitim planını yapın',
                'aciklama' => now()->year.' yılı için plan oluşturun.',
                'tamam' => $varMi('yillikPlanlar', fn ($q) => $q->where('yil', now()->year)),
                'url' => YillikCalismaPlani::getUrl(),
                'eylem' => 'Yıllık planlar',
            ],
        ];
    }
}
