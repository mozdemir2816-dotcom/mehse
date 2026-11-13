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

    @if ($bilgi)
        <x-filament::section>
            <x-slot name="heading">Nasıl kullanılır</x-slot>
            <x-slot name="description">Çıktı: {{ $bilgi['cikti'] }}</x-slot>
            <ol style="font-size:.9rem;list-style:decimal;padding-left:1.25rem;display:flex;flex-direction:column;gap:.35rem">
                @foreach (\App\Support\HazirIstemler::adimlar($tur) as $adim)
                    <li>{{ $adim }}</li>
                @endforeach
            </ol>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Gemini'ye yapıştırılacak istem</x-slot>
            <x-slot name="description">{{ number_format(mb_strlen($istem), 0, ',', '.') }} karakter</x-slot>

            @if ($this->firmaEksik)
                <p role="alert" style="font-size:.9rem;margin-bottom:.75rem;padding:.6rem .8rem;border-radius:.5rem;background:rgb(254 243 199);color:rgb(120 53 15)">
                    Firma seçmediniz ve ek bilgi yazmadınız: Gemini hangi işyeri için hazırlayacağını bilemez.
                    Bir firma seçin ya da ek bilgiye işyerini anlatın.
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
