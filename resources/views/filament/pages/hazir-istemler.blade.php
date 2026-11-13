<x-filament-panels::page>
    @php
        $tur = $this->data['tur'] ?? 'risk_analizi';
        $bilgi = \App\Support\HazirIstemler::TURLER[$tur] ?? null;
        $istem = $this->istem;
    @endphp

    <p style="max-width:70ch;font-size:.9rem;opacity:.8">
        Yapay zekâ kotası dolduğunda ya da elle çalışmak istediğinizde: istemi Gemini sohbetine yapıştırın,
        gelen cevabı sistemin bildiği biçimde içe aktarın. Mevcut önlem hiçbir istemde doldurtulmaz.
    </p>

    {{ $this->form }}

    @if ($benzerKlasorler = $this->benzerKlasorler)
        <div role="status" style="padding:.75rem 1rem;border-radius:.6rem;border:1px solid rgb(245 158 11 / .45);background:rgb(254 243 199 / .55)">
            <p style="font-size:.9rem;font-weight:600;margin-bottom:.4rem">
                @if ($benzerKlasorler[0]['ayni'])
                    Bu klasör kütüphanede var — istem, içindeki maddeleri Gemini'ye "tekrar yazma" diye bildirir.
                @else
                    Kütüphanede benzer klasör var. Aynı ekipman / iş ise mevcut klasörü kullanın; yeni klasör açılırsa maddeler mükerrer olur.
                @endif
            </p>
            <ul style="display:flex;flex-direction:column;gap:.35rem;font-size:.88rem">
                @foreach ($benzerKlasorler as $k)
                    <li style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
                        <span><strong>{{ $k['ad'] }}</strong> · {{ $k['madde'] }} madde</span>
                        @if ($k['ayni'])
                            <span style="font-size:.78rem;padding:.1rem .45rem;border-radius:.3rem;background:rgb(22 163 74 / .12);color:#15803d">seçili</span>
                        @else
                            <x-filament::button size="xs" color="gray" wire:click="klasorSec({{ $k['id'] }})">Bunu kullan</x-filament::button>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($bilgi)
        <x-filament::section>
            <x-slot name="heading">Nasıl kullanılır</x-slot>
            <x-slot name="description">Çıktı: {{ $bilgi['cikti'] }}</x-slot>
            <ol style="font-size:.9rem;list-style:decimal;padding-left:1.25rem;display:flex;flex-direction:column;gap:.35rem">
                @foreach (\App\Support\HazirIstemler::adimlar($tur, ! empty($this->data['parca'])) as $adim)
                    <li>{{ $adim }}</li>
                @endforeach
            </ol>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Gemini'ye yapıştırılacak istem</x-slot>
            <x-slot name="description">{{ number_format(mb_strlen($istem), 0, ',', '.') }} karakter</x-slot>

            @if ($uyari = $this->uyari)
                <p role="alert" style="font-size:.9rem;margin-bottom:.75rem;padding:.6rem .8rem;border-radius:.5rem;background:rgb(254 243 199);color:rgb(120 53 15)">
                    {{ $uyari }}
                </p>
            @endif

            <div x-data="{ kopyalandi: false, kopyala() { navigator.clipboard.writeText(this.$refs.metin.value).then(() => { this.kopyalandi = true; setTimeout(() => this.kopyalandi = false, 2000) }) } }">
                <div style="display:flex;gap:.5rem;align-items:center;margin-bottom:.6rem;flex-wrap:wrap">
                    <x-filament::button icon="heroicon-o-clipboard-document" x-on:click="kopyala()">
                        <span x-show="! kopyalandi">İstemi kopyala</span>
                        <span x-show="kopyalandi" x-cloak>Kopyalandı</span>
                    </x-filament::button>
                    <x-filament::button tag="a" href="https://gemini.google.com/app" target="_blank" rel="noopener" color="gray" icon="heroicon-o-arrow-top-right-on-square">
                        Gemini'yi aç
                    </x-filament::button>
                    <span aria-live="polite" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap" x-text="kopyalandi ? 'İstem panoya kopyalandı' : ''"></span>
                </div>
                <textarea wire:key="istem-{{ md5($istem) }}" x-ref="metin" readonly rows="18" aria-label="Hazır istem metni"
                    class="fi-input-wrp"
                    style="display:block;width:100%;box-sizing:border-box;padding:.85rem 1rem;border:1px solid rgb(128 128 140 / .35);border-radius:.6rem;background:rgb(128 128 140 / .06);color:inherit;font-family:ui-monospace,Consolas,monospace;font-size:.85rem;line-height:1.55;resize:vertical">{{ $istem }}</textarea>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
