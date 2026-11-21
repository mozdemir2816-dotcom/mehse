<x-filament-panels::page>
    @php
        $oneriler = $this->oneriler();
        $durumlar = $this->durumlar();
    @endphp

    <style>
        .tmp-adim { display:flex; flex-direction:column; gap:.7rem; }
        .tmp-metin { display:block; width:100%; box-sizing:border-box; padding:.75rem .9rem; border:1px solid rgb(128 128 140 / .35); border-radius:.6rem; background:rgb(128 128 140 / .05); color:inherit; font-size:.9rem; line-height:1.5; resize:vertical; }
        .tmp-metin.kod { font-family:ui-monospace, Consolas, monospace; font-size:.82rem; }
        .tmp-satir { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
        .tmp-etiket { display:inline-flex; align-items:center; gap:.3rem; font-size:.78rem; font-weight:600; padding:.15rem .5rem; border-radius:.35rem; background:rgb(128 128 140 / .14); }
        .tmp-etiket.var { background:rgb(22 163 74 / .12); color:#166534; }
        .tmp-etiket.yok { background:rgb(245 158 11 / .16); color:#92400e; }
        .dark .tmp-etiket.var { color:#86efac; } .dark .tmp-etiket.yok { color:#fcd34d; }
        .tmp-tablo td, .tmp-tablo th { padding:.55rem .6rem; text-align:left; vertical-align:top; border-bottom:1px solid rgb(128 128 140 / .2); font-size:.88rem; }
        .tmp-tablo th { font-size:.78rem; font-weight:600; opacity:.75; }
    </style>

    <p class="mehse-metin-soluk" style="max-width:74ch;font-size:.9rem">
        Sistemde olmayan makineler için günlük kontrol formu, çalışma talimatı, risk maddeleri ve afiş yazısı
        Gemini sohbetinde hazırlanır, tek yapıştırmayla kurulur. Yapay zekâ kotası harcanmaz. Kurulan her şey
        "uzman onayı bekliyor" etiketiyle gelir; kontrol edip <strong>Paketi onayla</strong> deyin.
    </p>

    <x-filament::section>
        <x-slot name="heading">1. Makineleri yazın</x-slot>
        <x-slot name="description">Her satıra bir makine. Sistemde paketi olanlar ayrılır, istem yalnız yeniler için hazırlanır.</x-slot>
        <div class="tmp-adim">
            <textarea class="tmp-metin" rows="6" wire:model="makineler" aria-label="Makine adları, her satıra bir tane" placeholder="Asfalt kesme makinesi&#10;Hidrolik kırıcı ataşmanı&#10;Karot makinesi"></textarea>
            @if ($oneriler)
                <div class="tmp-satir" style="font-size:.88rem">
                    <span>Firmalara eklenmiş ama paketi olmayan {{ count($oneriler) }} makine var: {{ \Illuminate\Support\Str::limit(implode(', ', $oneriler), 160) }}</span>
                    <x-filament::button size="sm" color="gray" wire:click="onerileriEkle">Listeye ekle</x-filament::button>
                </div>
            @endif
            <div class="tmp-satir">
                <x-filament::button icon="heroicon-o-sparkles" wire:click="istemleriHazirla">İstemleri hazırla</x-filament::button>
            </div>
            @if ($mevcut)
                <p style="font-size:.88rem">Paketi zaten olan, atlanan: {{ collect($mevcut)->map(fn ($m) => $m['ad'].($m['ad'] !== $m['tip'] ? ' → '.$m['tip'] : ''))->implode(', ') }}</p>
            @endif
        </div>
    </x-filament::section>

    @if ($istemler)
        <x-filament::section>
            <x-slot name="heading">2. İstemleri Gemini'ye yapıştırın</x-slot>
            <x-slot name="description">{{ count($yeni) }} makine, {{ count($istemler) }} istem. Her istemin cevabını sırayla 3. adıma yapıştırıp kurun. Gemini'de Pro modeli önerilir.</x-slot>
            <div class="tmp-adim">
                @foreach ($istemler as $i => $istem)
                    <div x-data="{ k: false, kopyala() { navigator.clipboard.writeText(this.$refs.m.value).then(() => { this.k = true; setTimeout(() => this.k = false, 2000) }) } }" wire:key="istem-{{ $i }}-{{ md5($istem) }}">
                        <div class="tmp-satir" style="margin-bottom:.4rem">
                            <strong style="font-size:.9rem">İstem {{ $i + 1 }}</strong>
                            <x-filament::button size="sm" icon="heroicon-o-clipboard-document" x-on:click="kopyala()">
                                <span x-show="! k">Kopyala</span><span x-show="k" x-cloak>Kopyalandı</span>
                            </x-filament::button>
                            <x-filament::button size="sm" color="gray" tag="a" href="https://gemini.google.com/app" target="_blank" rel="noopener" icon="heroicon-o-arrow-top-right-on-square">Gemini'yi aç</x-filament::button>
                        </div>
                        <textarea x-ref="m" readonly rows="5" class="tmp-metin kod" aria-label="İstem {{ $i + 1 }}">{{ $istem }}</textarea>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">3. Gemini'nin cevabını yapıştırıp kurun</x-slot>
        <x-slot name="description">Köşeli parantezle başlayan JSON'un tamamını yapıştırın. Kontrol formu, talimat, risk maddeleri ve afiş yazısı kurulur; o makinesi olan firmalara talimat eklenir.</x-slot>
        <div class="tmp-adim">
            <textarea class="tmp-metin kod" rows="8" wire:model="cevap" aria-label="Gemini cevabı (JSON)" placeholder='[{"makine": "...", ...}]'></textarea>
            <div class="tmp-satir">
                <x-filament::button icon="heroicon-o-arrow-down-tray" wire:click="kur" wire:loading.attr="disabled" wire:target="kur">
                    <span wire:loading.remove wire:target="kur">Sisteme kur</span><span wire:loading wire:target="kur">Kuruluyor…</span>
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Kurulan makineler</x-slot>
        <x-slot name="description">Afiş resmi için "Resim istemi"ni Gemini'ye verin, resmi gösterilen adla kaydedip Ekipman Afişleri'nden yükleyin.</x-slot>
        @if ($durumlar === [])
            <p style="font-size:.9rem">Henüz paketle kurulan makine yok. Yukarıdaki adımlarla ilk makineyi kurun.</p>
        @else
            <div style="overflow-x:auto">
                <table class="tmp-tablo" style="width:100%;border-collapse:collapse">
                    <thead><tr><th scope="col">Makine</th><th scope="col">Paket</th><th scope="col">Afiş resmi</th><th scope="col">Durum</th></tr></thead>
                    <tbody>
                        @foreach ($durumlar as $d)
                            @php $t = $d['tip']; @endphp
                            <tr wire:key="tip-{{ $t->id }}">
                                <td>
                                    <div style="font-weight:600">{{ $t->ad }}</div>
                                    <div class="mehse-metin-soluk" style="font-size:.78rem">{{ $d['kategori'] }} · {{ $t->periyot_ay }} ay</div>
                                </td>
                                <td>
                                    <div class="tmp-satir">
                                        <span class="tmp-etiket {{ $d['form'] ? 'var' : 'yok' }}">Kontrol formu: {{ $d['form'] ?: 'yok' }}{{ $d['form'] ? ' soru' : '' }}</span>
                                        <span class="tmp-etiket {{ $d['talimat'] ? 'var' : 'yok' }}">Talimat: {{ $d['talimat'] ? 'var' : 'yok' }}</span>
                                        <span class="tmp-etiket {{ $d['risk'] ? 'var' : 'yok' }}">Risk: {{ $d['risk'] ?: 'yok' }}{{ $d['risk'] ? ' madde' : '' }}</span>
                                        <span class="tmp-etiket {{ $d['afis_yazi'] ? 'var' : 'yok' }}">Afiş yazısı: {{ $d['afis_yazi'] ? 'var' : 'yok' }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($d['afis_resim'])
                                        <span class="tmp-etiket var">Yüklü</span>
                                    @else
                                        <div x-data="{ k: false }" class="tmp-adim" style="gap:.35rem">
                                            <span class="tmp-etiket yok">Bekleniyor: {{ $t->anahtar }}.png</span>
                                            <x-filament::button size="sm" color="gray" icon="heroicon-o-clipboard-document"
                                                x-on:click="$wire.resimIstemi({{ $t->id }}).then(m => navigator.clipboard.writeText(m)).then(() => { k = true; setTimeout(() => k = false, 2000) })">
                                                <span x-show="! k">Resim istemi</span><span x-show="k" x-cloak>Kopyalandı</span>
                                            </x-filament::button>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if ($t->onayBekliyorMu())
                                        <div class="tmp-adim" style="gap:.35rem">
                                            <span class="tmp-etiket yok">Uzman onayı bekliyor</span>
                                            <x-filament::button size="sm" icon="heroicon-o-check" wire:click="onayla({{ $t->id }})">Paketi onayla</x-filament::button>
                                        </div>
                                    @else
                                        <span class="tmp-etiket var">Onaylı {{ $t->onaylandi_at?->format('d.m.Y') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
