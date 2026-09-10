<?php

namespace App\Filament\Pages;

use App\Models\TehlikeCakismasi;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Risk Kütüphanesi'ne Excel'den toplu yüklerken bulunan olası mükerrer
 * maddelerin gözden geçirilmesi. `TehlikeExcelIceAktarici` benzerliği yüksek
 * (≥80%) ama birebir aynı olmayan bir madde bulunca burada biriktirir;
 * kullanıcı her biri için mevcudu koru / yenisini kullan / ikisini de tut seçer.
 */
class TehlikeCakismalari extends Page
{
    protected string $view = 'filament.pages.tehlike-cakismalari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static string|UnitEnum|null $navigationGroup = 'Risk Değerlendirmesi';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'tehlike-cakismalari';

    protected static ?string $title = 'Risk Kütüphanesi Çakışmaları';

    protected static ?string $navigationLabel = 'Kütüphane Çakışmaları';

    public static function shouldRegisterNavigation(): bool
    {
        return TehlikeCakismasi::query()->exists();
    }

    public static function getNavigationBadge(): ?string
    {
        $sayi = TehlikeCakismasi::query()->count();

        return $sayi > 0 ? (string) $sayi : null;
    }

    public function cakismalar()
    {
        return TehlikeCakismasi::with(['kategori', 'mevcutTehlike'])->latest()->get();
    }

    public function mevcuduKoru(int $id): void
    {
        $cakisma = TehlikeCakismasi::findOrFail($id);
        $cakisma->mevcuduKoru();

        Notification::make()->title('Mevcut madde korundu')->success()->send();
    }

    public function yenisiniKullan(int $id): void
    {
        $cakisma = TehlikeCakismasi::findOrFail($id);
        $cakisma->yenisiniKullan();

        Notification::make()->title('Mevcut madde güncellendi')->success()->send();
    }

    public function ikisiniDeTut(int $id): void
    {
        $cakisma = TehlikeCakismasi::findOrFail($id);
        $cakisma->ikisiniDeTut();

        Notification::make()->title('Yeni ayrı madde olarak eklendi')->success()->send();
    }
}
