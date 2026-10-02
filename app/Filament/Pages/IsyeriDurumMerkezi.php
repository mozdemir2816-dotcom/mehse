<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Support\ExcelBellek;
use App\Support\IsyeriDurumu;
use Barryvdh\DomPDF\Facade\Pdf;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnitEnum;

/**
 * İşyeri Durum Merkezi (isgsuite "İşyeri Durum Merkezi / Firma 360"): seçili
 * işyerinin genel uyum durumu, süreç tablosu, yükümlülük takvimi,
 * göstergeler, profil, görevlendirmeler, son ziyaret ve olaylar — tek
 * ekranda, salt okunur. Tam Firma Dosyası (PDF), Detaylı Excel, Yazdır.
 */
class IsyeriDurumMerkezi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.isyeri-durum-merkezi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'isyeri-durum-merkezi';

    protected static ?string $title = 'İşyeri Durum Merkezi';

    protected static ?string $navigationLabel = 'İşyeri Durum Merkezi';

    public ?int $firmaId = null;

    public string $takvimKategori = '';

    public string $takvimDurum = '';

    public ?string $takvimBaslangic = null;

    public ?string $takvimBitis = null;

    public int $sayfaBoyutu = 25;

    public int $takvimSayfa = 1;

    public function mount(): void
    {
        $this->firmaId = request()->integer('firma') ?: array_key_first($this->firmalar);
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar)
            ? Firma::with(['igu', 'isyeriHekimi', 'dsp'])->find($this->firmaId)
            : null;
    }

    #[Computed]
    public function surecler(): array
    {
        return $this->firma ? IsyeriDurumu::surecler($this->firma) : [];
    }

    #[Computed]
    public function ozet(): array
    {
        return IsyeriDurumu::ozet($this->surecler);
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function takvim(): Collection
    {
        return $this->firma ? IsyeriDurumu::takvim($this->firma) : collect();
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function filtreliTakvim(): Collection
    {
        $bas = $this->takvimBaslangic ? Carbon::parse($this->takvimBaslangic)->startOfDay() : null;
        $bit = $this->takvimBitis ? Carbon::parse($this->takvimBitis)->endOfDay() : null;

        return $this->takvim
            ->when($this->takvimKategori !== '', fn ($c) => $c->where('kategori', $this->takvimKategori))
            ->when($this->takvimDurum !== '', fn ($c) => $c->where('durum', $this->takvimDurum))
            ->when($bas, fn ($c) => $c->filter(fn ($t) => $t['tarih']->gte($bas)))
            ->when($bit, fn ($c) => $c->filter(fn ($t) => $t['tarih']->lte($bit)))
            ->values();
    }

    public function sayfaSayisi(): int
    {
        return max(1, (int) ceil($this->filtreliTakvim->count() / max(1, $this->sayfaBoyutu)));
    }

    /** @return Collection<int, array<string, mixed>> */
    public function takvimSayfasi(): Collection
    {
        return $this->filtreliTakvim->forPage(min($this->takvimSayfa, $this->sayfaSayisi()), max(1, $this->sayfaBoyutu))->values();
    }

    #[Computed]
    public function gostergeler(): array
    {
        return $this->firma ? IsyeriDurumu::gostergeler($this->firma) : [];
    }

    public function updated(string $alan): void
    {
        if ($alan === 'firmaId') {
            $this->takvimSifirla();
            $this->yenile();
        }

        if (in_array($alan, ['takvimKategori', 'takvimDurum', 'takvimBaslangic', 'takvimBitis', 'sayfaBoyutu'], true)) {
            $this->takvimSayfa = 1;
            unset($this->filtreliTakvim);
        }
    }

    public function takvimSifirla(): void
    {
        $this->takvimKategori = $this->takvimDurum = '';
        $this->takvimBaslangic = $this->takvimBitis = null;
        $this->takvimSayfa = 1;
        unset($this->filtreliTakvim);
    }

    public function sayfaDegistir(int $fark): void
    {
        $this->takvimSayfa = max(1, min($this->sayfaSayisi(), $this->takvimSayfa + $fark));
    }

    public function yenile(): void
    {
        unset($this->firma, $this->surecler, $this->ozet, $this->takvim, $this->filtreliTakvim, $this->gostergeler);
    }

    /** Tam Firma Dosyası (PDF). */
    public function tamPdf()
    {
        $firma = $this->firma;

        if (! $firma) {
            return null;
        }

        $pdf = Pdf::loadView('pdf.isyeri-durum-dosyasi', [
            'firma' => $firma,
            'surecler' => $this->surecler,
            'ozet' => $this->ozet,
            'takvim' => $this->takvim->where('durum', '!=', 'tamamlandi')->take(80),
            'gostergeler' => $this->gostergeler,
            'gorevlendirmeler' => IsyeriDurumu::gorevlendirmeler($firma),
            'uzman' => Filament::auth()->user(),
        ])->setPaper('a4');

        // Alt bilgi + sayfa no tek render'da (bkz. dompdf çift render yasağı).
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $dompdf->getCanvas()->page_text(34, 815, 'mehse · Tam Firma Dosyası · Yetkili kullanım', $font, 7, [0.4, 0.4, 0.4]);
        $dompdf->getCanvas()->page_text(510, 815, 'Sayfa {PAGE_NUM} / {PAGE_COUNT}', $font, 7, [0.4, 0.4, 0.4]);

        return response()->streamDownload(fn () => print ($dompdf->output()), 'firma-dosyasi-'.Str::slug($firma->kisa_ad ?: $firma->unvan).'.pdf');
    }

    /** Detaylı Excel: Özet, Süreçler, Termin Takvimi, Görevlendirmeler. */
    public function detayliExcel()
    {
        $firma = $this->firma;

        if (! $firma) {
            return null;
        }

        ExcelBellek::artir();
        $kitap = new Spreadsheet;
        $o = $this->ozet;
        $g = $this->gostergeler;

        $sayfalar = [
            'Özet' => [['Gösterge', 'Değer'], [
                ['Firma', $firma->unvan], ['SGK sicil no', $firma->sgk_sicil_no], ['NACE', trim($firma->nace_kodu.' '.$firma->nace_aciklama)],
                ['Tehlike sınıfı', $firma->tehlikeSinifiEtiketi()], ['Rapor tarihi', now()->format('d.m.Y')], ['Genel durum', $o['genel']],
                ['Tamamlanma', '%'.$o['yuzde']], ['Tamamlanan', $o['tamamlandi']], ['Eksik', $o['eksik']], ['Gecikmiş', $o['gecikmis']], ['Yaklaşan', $o['yaklasan']],
                ['Personel', $g['personel']], ['Şube', $g['sube']], ['Görevlendirme', $g['gorevlendirme']], ['Evrak uyumu', '%'.$g['evrak_uyum']],
                ['Açık DÖF', $g['acik_dof']], ['Gecikmiş muayene', $g['gecikmis_muayene']], ['Eğitim kaydı', $g['egitim_kaydi']],
            ]],
            'Süreçler' => [['Süreç', 'Durum', 'Gerçek veri sonucu', 'Sorumlu'], collect($this->surecler)->map(fn ($s) => [
                $s['surec'], IsyeriDurumu::DURUMLAR[$s['durum']], $s['sonuc'], $s['sorumlu'],
            ])->all()],
            'Termin Takvimi' => [['Durum', 'Kategori', 'Kayıt', 'Ayrıntı', 'Tarih', 'Kalan gün', 'Sorumlu'], $this->takvim->map(fn ($t) => [
                IsyeriDurumu::TAKVIM_DURUMLARI[$t['durum']], $t['kategori'], $t['kayit'], $t['alt'], $t['tarih']->format('d.m.Y'), $t['kalan'], $t['sorumlu'],
            ])->all()],
            'Görevlendirmeler' => [['Profesyonel', 'Rol', 'Sertifika no', 'Otomatik aylık süre (dk)'], collect(IsyeriDurumu::gorevlendirmeler($firma))->map(fn ($r) => [
                $r['ad'], $r['rol'], $r['sertifika'], $r['aylik_dk'],
            ])->all()],
        ];

        $i = 0;
        foreach ($sayfalar as $ad => [$basliklar, $satirlar]) {
            $s = $i++ === 0 ? $kitap->getActiveSheet() : $kitap->createSheet();
            $s->setTitle($ad);
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

        $tmp = tempnam(sys_get_temp_dir(), 'idm').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'firma-dosyasi-'.Str::slug($firma->kisa_ad ?: $firma->unvan).'.xlsx');
    }
}
