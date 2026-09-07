@php
    $mor = 'rgb(124 58 237)';
    $riskRozet = fn ($seviye) => match (\App\Models\JsaSablonu::riskRengi($seviye)) {
        'kritik' => '#7f1d1d', 'yuksek' => '#dc2626', 'orta' => '#f59e0b', 'dusuk' => '#16a34a', default => '#9ca3af',
    };
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        “İş Güvenliği Analizi (JSA)” formatındaki Excel dosyalarınızı yükleyip bir
        <strong>kütüphanede</strong> biriktirin. Bir firmaya lazım olduğunda aşağıdan firmayı
        seçin ve ilgili JSA’nın <strong>PDF</strong> veya <strong>Word</strong> çıktısını alın —
        şablon bölünmez, tüm iş adımları/notlar/imza bloğu bir arada gelir. Firmada birden
        çok iş varsa (örn. <em>kazı</em> + <em>elektrik tesisatı</em>) kütüphaneden birden
        fazla JSA’yı <strong>işaretleyip tek belgede</strong> birleştirebilirsiniz.
    </p>

    {{-- FİRMA (opsiyonel — çıktı künyesi) --}}
    <x-filament::section icon="heroicon-o-building-office-2" icon-color="primary">
        <x-slot name="heading">Firmaya Uygula (Opsiyonel)</x-slot>
        <x-slot name="description">Seçerseniz PDF/Word künyesine firma adı ve logosu eklenir. Boş bırakırsanız çıktı firmasız (genel kütüphane belgesi) üretilir.</x-slot>

        <select wire:model.live="firmaId"
            style="width:100%;max-width:28rem;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            <option value="">— Firmasız (genel) —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>

        @if ($this->firma)
            <p style="margin-top:.7rem;font-size:.78rem;color:rgb(107 114 128)">
                <strong>Onay ve İmza</strong> bloğundaki <strong>“Hazırlayan”</strong> satırı, firmaya
                atanmış İş Güvenliği Uzmanı
                <strong>{{ $this->firma->igu?->ad_soyad ?? '— firmaya uzman atanmamış —' }}</strong>
                @if ($this->firma->igu && ! $this->firma->igu->kase_gorseli)
                    <span style="color:rgb(239 68 68)">(kaşe görseli yüklenmemiş)</span>
                @endif
                bilgisiyle ve kaşesiyle otomatik doldurulur. Diğer satırlar boş kalır.
            </p>
        @endif
    </x-filament::section>

    {{-- KÜTÜPHANE --}}
    <x-filament::section icon="heroicon-o-clipboard-document-list" icon-color="primary">
        <x-slot name="heading">JSA Kütüphanem ({{ $this->sablonlar->count() }})</x-slot>

        <input type="text" wire:model.live.debounce.400ms="arama" placeholder="Başlıkta ara (örn: duvar örme, iskele)…"
            style="width:100%;max-width:24rem;padding:.5rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem;margin-bottom:1rem">

        {{-- TOPLU SEÇİM ÇUBUĞU --}}
        @if (count($secili) > 0)
            <div style="display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;padding:.6rem .85rem;margin-bottom:.9rem;border:1px solid {{ $mor }};border-radius:.5rem;background:rgb(124 58 237 / .07)">
                <span style="font-weight:700;font-size:.82rem">{{ count($secili) }} JSA seçili</span>
                <x-filament::button size="xs" color="danger" icon="heroicon-o-document-arrow-down" wire:click="topluPdf" wire:loading.attr="disabled">
                    Seçilenleri PDF (tek belge)
                </x-filament::button>
                <x-filament::button size="xs" color="info" icon="heroicon-o-document-text" wire:click="topluWord" wire:loading.attr="disabled">
                    Seçilenleri Word
                </x-filament::button>
                <x-filament::button size="xs" color="gray" wire:click="tumunuSec">Tümünü Seç</x-filament::button>
                <x-filament::button size="xs" color="gray" wire:click="secimiTemizle">Temizle</x-filament::button>
                @unless ($this->firma)
                    <span style="font-size:.74rem;color:rgb(239 68 68)">Firma seçilmedi — çıktı firmasız (genel) gelir</span>
                @endunless
            </div>
        @endif

        @forelse ($this->sablonlar as $s)
            <div style="border:1px solid {{ in_array((string) $s->id, $secili, true) ? $mor : 'rgb(107 114 128 / .25)' }};border-radius:.6rem;padding:.85rem 1rem;margin-bottom:.6rem">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:start;flex-wrap:wrap">
                    <div style="flex:1;min-width:220px;display:flex;gap:.65rem;align-items:start">
                        <input type="checkbox" wire:model.live="secili" value="{{ $s->id }}" title="Toplu çıktı için işaretle"
                            style="width:1.05rem;height:1.05rem;margin-top:.15rem;flex-shrink:0;cursor:pointer">
                        <div style="flex:1">
                            <div style="font-weight:700;font-size:.9rem">{{ $s->baslik }}</div>
                            <div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.2rem">
                                {{ count($s->adimlar ?? []) }} iş adımı
                                @if ($s->dokuman_ref) &nbsp;•&nbsp; Ref: {{ $s->dokuman_ref }} @endif
                                @if ($s->revizyon) &nbsp;•&nbsp; Rev: {{ $s->revizyon }} @endif
                                @if ($s->belge_tarihi) &nbsp;•&nbsp; {{ $s->belge_tarihi }} @endif
                            </div>
                            @php
                                $seviyeler = collect($s->adimlar ?? [])->pluck('baslangic_risk')->filter()->countBy();
                            @endphp
                            @if ($seviyeler->isNotEmpty())
                                <div style="display:flex;gap:.3rem;flex-wrap:wrap;margin-top:.4rem">
                                    @foreach ($seviyeler as $seviye => $adet)
                                        <span style="display:inline-block;color:#fff;padding:1px 6px;border-radius:3px;font-size:.68rem;background:{{ $riskRozet($seviye) }}">{{ $seviye }}: {{ $adet }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    <div style="display:flex;gap:.35rem;flex-wrap:wrap;align-items:center">
                        <x-filament::button size="xs" color="danger" icon="heroicon-o-document-arrow-down" wire:click="pdf({{ $s->id }})">PDF</x-filament::button>
                        <x-filament::button size="xs" color="info" icon="heroicon-o-document-text" wire:click="word({{ $s->id }})">Word</x-filament::button>
                        <x-filament::button size="xs" color="gray" wire:click="sil({{ $s->id }})" wire:confirm="Bu JSA kütüphaneden silinsin mi?">Sil</x-filament::button>
                    </div>
                </div>
            </div>
        @empty
            <p style="font-size:.85rem;color:rgb(107 114 128)">
                Kütüphane boş. Yukarıdaki <strong>“Excel’den Yükle”</strong> ile ilk JSA’nızı ekleyin
                (biçimden emin değilseniz önce <strong>“Boş Şablon İndir”</strong>).
            </p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
