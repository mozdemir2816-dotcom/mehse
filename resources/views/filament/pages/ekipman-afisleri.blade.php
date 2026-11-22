<x-filament-panels::page>
    @php
        $s = $this->sayilar();
        $afisler = $this->afisler();
        $yonetir = $this->yonetebilirMi();
    @endphp

    <style>
        .afis-ozet { display:flex; flex-wrap:wrap; gap:.4rem 1.4rem; font-size:.92rem; }
        .afis-ozet strong { font-variant-numeric: tabular-nums; }
        .afis-suzgec { display:flex; flex-wrap:wrap; gap:.6rem; align-items:end; }
        .afis-suzgec label { display:flex; flex-direction:column; gap:.25rem; font-size:.8rem; font-weight:600; }
        .afis-suzgec input, .afis-suzgec select { min-height:44px; min-width:12rem; }
        .afis-izgara { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:1rem; }
        .afis-kart { display:flex; flex-direction:column; border:1px solid rgb(128 128 140 / .28); border-radius:.6rem; overflow:hidden; background:var(--mehse-yuzey, transparent); }
        .afis-gorsel { aspect-ratio:4/3; background:rgb(128 128 140 / .08); display:flex; align-items:center; justify-content:center; }
        .afis-gorsel img { width:100%; height:100%; object-fit:cover; display:block; }
        .afis-gorsel span { font-size:.85rem; opacity:.7; padding:1rem; text-align:center; }
        .afis-govde { padding:.7rem .8rem .8rem; display:flex; flex-direction:column; gap:.45rem; flex:1; }
        .afis-ad { font-weight:600; font-size:.95rem; line-height:1.3; }
        .afis-grup { font-size:.78rem; opacity:.7; }
        .afis-durum { display:inline-flex; align-self:flex-start; align-items:center; gap:.3rem; font-size:.78rem; font-weight:600; padding:.15rem .5rem; border-radius:.35rem; }
        .afis-durum.hazir { background:rgb(22 163 74 / .12); color:#166534; }
        .afis-durum.onay { background:rgb(245 158 11 / .16); color:#92400e; }
        .afis-durum.eksik { background:rgb(128 128 140 / .14); color:inherit; }
        .afis-dugmeler { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:auto; padding-top:.3rem; }
        .dark .afis-durum.hazir { color:#86efac; } .dark .afis-durum.onay { color:#fcd34d; }
    </style>

    <p class="mehse-metin-soluk" style="max-width:72ch;font-size:.9rem">
        Makinenin yanına asılacak A4 güvenlik afişleri. Görsel ve güvenlik kuralları şablon/AI çıktılarıyla toplu yüklenebilir.
        Firmanın logosu ve ekipmanın QR kodu basılı afiş için <strong>Periyodik Kontrol</strong>'de ekipman satırındaki "Afiş" düğmesini kullanın.
    </p>

    <div class="afis-ozet" aria-label="Afiş durumu">
        <span><strong>{{ $s['toplam'] }}</strong> makine</span>
        <span><strong>{{ $s['resim'] }}</strong> resim yüklü</span>
        <span><strong>{{ $s['yazi'] }}</strong> yazısı hazır</span>
        <span><strong>{{ $s['onay'] }}</strong> uzman onayı bekliyor</span>
    </div>

    @if ($yonetir && $s['resim'] === 0 && $s['yazi'] === 0)
        <div role="note" style="max-width:72ch;padding:.75rem 1rem;border-radius:.6rem;border:1px solid rgb(59 130 246 / .35);background:rgb(59 130 246 / .07);font-size:.9rem">
            Henüz afiş yok. Hazırladığınız makine görsellerini <strong>Resimleri yükle</strong> ile (tek tek ya da ZIP),
            YAPIN / YAPMAYIN tablosunu <strong>Yazıları yükle</strong> ile ekleyin. Resim ve yazısı olan makinenin afişi hemen basılabilir.
        </div>
    @endif

    <div class="afis-suzgec">
        <label>Makine ara
            <input type="search" class="mehse-girdi" wire:model.live.debounce.300ms="arama" placeholder="ör. vinç">
        </label>
        <label>Grup
            <select class="mehse-girdi" wire:model.live="grup">
                <option value="">Tüm gruplar</option>
                @foreach ($this->gruplar() as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach
            </select>
        </label>
        <label>Durum
            <select class="mehse-girdi" wire:model.live="durum">
                <option value="">Hepsi</option>
                <option value="resim_yok">Resmi eksik</option>
                <option value="yazi_yok">Yazısı eksik</option>
                <option value="onay">Onay bekleyen</option>
                <option value="hazir">Onaylı ve hazır</option>
            </select>
        </label>
    </div>

    @if ($afisler->isEmpty())
        <p style="font-size:.92rem">Bu süzgeçle eşleşen makine yok. Aramayı ya da durumu değiştirin.</p>
    @else
        <div class="afis-izgara">
            @foreach ($afisler as $a)
                @php
                    $durum = ! $a->gorselVarMi() && ! $a->yaziVarMi() ? ['eksik', 'Resim ve yazı bekleniyor']
                        : (! $a->gorselVarMi() ? ['eksik', 'Resim bekleniyor']
                        : (! $a->yaziVarMi() ? ['eksik', 'Yazı bekleniyor']
                        : ($a->onayBekliyorMu() ? ['onay', 'Uzman onayı bekliyor'] : ['hazir', 'Onaylı'])));
                @endphp
                <article class="afis-kart" wire:key="afis-{{ $a->id }}">
                    <div class="afis-gorsel">
                        @if ($src = $a->gorselAdresi())
                            <img src="{{ $src }}" alt="{{ $a->ad }} güvenli kullanım çizimi" loading="lazy">
                        @else
                            <span>Resim yok<br>{{ $a->anahtar }}.png</span>
                        @endif
                    </div>
                    <div class="afis-govde">
                        <div class="afis-ad">{{ $a->ad }}</div>
                        <div class="afis-grup">{{ $a->grup }}</div>
                        <span class="afis-durum {{ $durum[0] }}">{{ $durum[1] }}</span>
                        <div class="afis-dugmeler">
                            @if ($a->hazirMi())
                                <x-filament::button size="sm" color="gray" icon="heroicon-o-document-arrow-down" wire:click="onizle({{ $a->id }})">PDF</x-filament::button>
                            @endif
                            @if ($yonetir)
                                <x-filament::button size="sm" color="gray" icon="heroicon-o-pencil-square" wire:click="mountAction('duzenle', { id: {{ $a->id }} })">Düzenle</x-filament::button>
                                @if ($a->yaziVarMi() && $a->onayBekliyorMu())
                                    <x-filament::button size="sm" icon="heroicon-o-check" wire:click="onayla({{ $a->id }})">Onayla</x-filament::button>
                                @endif
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

</x-filament-panels::page>
