<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Support\ExcelBellek;
use App\Support\RiskMerkeziVerisi;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnitEnum;

/**
 * Risk Merkezi (isgsuite "Risk Değerlendirme" Merkez / Aksiyon / NACE Yol
 * Haritası / Raporlar sekmeleri): seçili işyerinin risk değerlendirmesi
 * panosu. Kayıt girişi Kayıtlı Değerlendirmeler'de kalır; burası özet,
 * önlem termin takibi, NACE kapsamı ve Excel çıktılarıdır.
 */
class RiskMerkezi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.risk-merkezi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Risk Değerlendirmesi';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'risk-merkezi';

    protected static ?string $title = 'Risk Merkezi';

    protected static ?string $navigationLabel = 'Risk Merkezi';

    public ?int $firmaId = null;

    public ?int $rdId = null;

    public string $sekme = 'merkez';

    public string $aksiyonFiltre = 'acik';

    public function mount(): void
    {
        $id = request()->integer('firma');
        $this->firmaId = $id && array_key_exists($id, $this->firmalar) ? $id : array_key_first($this->firmalar);
        $this->rdId = $this->degerlendirmeler->first()?->id;
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar) ? Firma::find($this->firmaId) : null;
    }

    /** @return Collection<int, RiskDegerlendirmesi> */
    #[Computed]
    public function degerlendirmeler(): Collection
    {
        return $this->firma
            ? RiskDegerlendirmesi::query()->where('firma_id', $this->firma->id)->orderByDesc('rapor_tarihi')->orderByDesc('id')->get()
            : collect();
    }

    #[Computed]
    public function rd(): ?RiskDegerlendirmesi
    {
        return $this->degerlendirmeler->firstWhere('id', $this->rdId) ?? $this->degerlendirmeler->first();
    }

    #[Computed]
    public function ozet(): ?array
    {
        return $this->rd ? RiskMerkeziVerisi::ozet($this->rd) : null;
    }

    #[Computed]
    public function aksiyonlar(): Collection
    {
        if (! $this->firma) {
            return collect();
        }

        $tumu = RiskMerkeziVerisi::aksiyonlar($this->firma);

        $secili = match ($this->aksiyonFiltre) {
            'acik' => $tumu->filter(fn ($a) => RiskMerkeziVerisi::acikMi($a['madde'])),
            'geciken' => $tumu->where('gecikti', true),
            'kapali' => $tumu->reject(fn ($a) => RiskMerkeziVerisi::acikMi($a['madde'])),
            default => $tumu,
        };

        return $secili->values();
    }

    #[Computed]
    public function kontroller(): array
    {
        return $this->firma ? RiskMerkeziVerisi::raporKontrolleri($this->firma, $this->rd) : [];
    }

    public function updated(string $alan): void
    {
        if ($alan === 'firmaId') {
            unset($this->firma, $this->degerlendirmeler);
            $this->rdId = $this->degerlendirmeler->first()?->id;
        }

        unset($this->rd, $this->ozet, $this->aksiyonlar, $this->kontroller);
    }

    /** Aksiyon sekmesi: önlemin durumunu değiştirir (Açık → Devam → Kapalı). */
    public function maddeDurumu(int $maddeId, string $durum): void
    {
        if (! array_key_exists($durum, config('isg.risk_madde_durumlari'))) {
            return;
        }

        $m = RiskMaddesi::query()
            ->whereHas('riskDegerlendirmesi', fn ($q) => $q->whereIn('firma_id', array_keys($this->firmalar)))
            ->find($maddeId);

        if ($m) {
            $m->update(['durum' => $durum]);
            unset($this->ozet, $this->aksiyonlar, $this->kontroller);
            Notification::make()->title('Önlem durumu: '.config('isg.risk_madde_durumlari.'.$durum))->success()->send();
        }
    }

    public function riskExcel()
    {
        $rd = $this->rd;

        if (! $rd) {
            return null;
        }

        $satirlar = $rd->maddeler()->orderBy('sira')->get()->map(fn (RiskMaddesi $m) => [
            $m->sira, $m->bolum, $m->faaliyet, $m->tehlike, $m->risk, $m->mevcut_onlem,
            $m->olasilik, $m->frekans, $m->siddet, $m->puan, $m->duzey,
            $m->oneri, $m->sorumlu, $m->termin, $m->son_puan, $m->son_duzey,
            config('isg.risk_madde_durumlari.'.$m->durum, $m->durum),
        ])->all();

        $o = $this->ozet;
        $istatistik = [
            ['Firma', $this->firma->unvan], ['Belge no', $rd->belge_no], ['Yöntem', config('isg.risk_yontemleri.'.$rd->yontem)],
            ['Rapor tarihi', $rd->rapor_tarihi?->format('d.m.Y')], ['Geçerlilik', $rd->gecerlilik_tarihi?->format('d.m.Y')],
            ['Toplam madde', $o['toplam']], ['Açık', $o['acik']], ['Kapalı', $o['kapali']],
            ['Çok yüksek (açık)', $o['cok_yuksek_acik']], ['Yüksek (açık)', $o['yuksek_acik']], ['Geciken önlem', $o['geciken']],
            ...collect($o['dagilim'])->map(fn ($n, $g) => ['Düzey: '.RiskMerkeziVerisi::GRUPLAR[$g], $n])->values()->all(),
            ...collect($o['bolumler'])->map(fn ($n, $b) => ['Bölüm: '.$b, $n])->values()->all(),
        ];

        return $this->excel([
            'Risk Maddeleri' => [['#', 'Bölüm', 'Faaliyet', 'Tehlike', 'Risk', 'Mevcut önlem', 'Olasılık', 'Frekans', 'Şiddet', 'Puan', 'Düzey', 'Önerilen önlem', 'Sorumlu', 'Termin', 'Rezidüel puan', 'Rezidüel düzey', 'Durum'], $satirlar],
            'İstatistik' => [['Gösterge', 'Değer'], $istatistik],
        ], 'risk-degerlendirmesi-'.Str::slug($this->firma->kisa_ad ?: $this->firma->unvan));
    }

    public function aksiyonExcel()
    {
        if (! $this->firma) {
            return null;
        }

        $satirlar = RiskMerkeziVerisi::aksiyonlar($this->firma)->map(fn ($a) => [
            $a['rd']->belge_no, $a['madde']->bolum, $a['madde']->tehlike, $a['madde']->puan, $a['madde']->duzey,
            $a['madde']->oneri, $a['madde']->sorumlu, $a['madde']->termin, $a['gecikti'] ? 'Gecikti' : '',
            config('isg.risk_madde_durumlari.'.$a['madde']->durum, $a['madde']->durum), $a['madde']->son_puan,
        ])->all();

        return $this->excel([
            'Önlem Takibi' => [['Belge no', 'Bölüm', 'Tehlike', 'Puan', 'Düzey', 'Önlem (DÖF)', 'Sorumlu', 'Termin', 'Gecikme', 'Durum', 'Rezidüel puan'], $satirlar],
        ], 'risk-onlem-takibi-'.Str::slug($this->firma->kisa_ad ?: $this->firma->unvan));
    }

    private function excel(array $sayfalar, string $ad)
    {
        ExcelBellek::artir();
        $kitap = new Spreadsheet;
        $i = 0;

        foreach ($sayfalar as $baslik => [$basliklar, $satirlar]) {
            $s = $i++ === 0 ? $kitap->getActiveSheet() : $kitap->createSheet();
            $s->setTitle($baslik);
            $son = Coordinate::stringFromColumnIndex(count($basliklar));
            $s->fromArray($basliklar, null, 'A1');
            $s->getStyle("A1:{$son}1")->getFont()->setBold(true);
            $s->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');
            foreach (array_values($satirlar) as $n => $satir) {
                $s->fromArray($satir, null, 'A'.($n + 2));
            }
            foreach (range(1, count($basliklar)) as $k) {
                $s->getColumnDimension(Coordinate::stringFromColumnIndex($k))->setAutoSize(true);
            }
            $s->freezePane('A2');
        }
        $kitap->setActiveSheetIndex(0);

        $tmp = tempnam(sys_get_temp_dir(), 'rsk').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, $ad.'.xlsx');
    }
}
