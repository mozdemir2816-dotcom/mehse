<div>
    <x-filament::modal id="gemini-elle" width="3xl" :close-by-clicking-away="false">
        <x-slot name="heading">Yapay zekâ yanıt vermedi — Gemini'de elle yapın</x-slot>
        <x-slot name="description">
            {{ match (true) { $durum === 429 => 'Günlük yapay zekâ kotası doldu.', in_array($durum, [500, 503], true) => 'Gemini şu an çok yoğun.', default => 'Gemini\'ye ulaşılamadı.' } }}
            İsteği Gemini sohbetinde ücretsiz yapıp yanıtı buraya getirebilirsiniz.
        </x-slot>

        <div class="mehse-elle" x-data="{ kopyalandi: false }">
            <style>
                .mehse-elle { display:grid; gap:1rem; font-size:.875rem }
                .mehse-elle ol.adim { margin:0; padding-left:1.2rem; display:grid; gap:.25rem }
                .mehse-elle textarea { width:100%; font:inherit; font-size:.8rem; line-height:1.45; border:1px solid rgb(107 114 128 / .35);
                    border-radius:.6rem; padding:.6rem .7rem; background:transparent; color:inherit; resize:vertical }
                .mehse-elle textarea:focus { outline:2px solid rgb(var(--primary-500)); outline-offset:1px }
                .mehse-elle .istem { min-height:9rem; max-height:16rem; font-family:ui-monospace,monospace; font-size:.74rem }
                .mehse-elle .satir { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center }
                .mehse-elle .uyari { border-radius:.6rem; padding:.55rem .75rem; background:rgb(161 98 7 / .1); color:#a16207 }
                .dark .mehse-elle .uyari { color:#facc15 }
                .mehse-elle .tamam { border-radius:.6rem; padding:.55rem .75rem; background:rgb(21 128 61 / .1); color:#15803d; font-weight:600 }
                .dark .mehse-elle .tamam { color:#4ade80 }
                .mehse-elle .hata { color:#b91c1c; font-weight:600 } .dark .mehse-elle .hata { color:#f87171 }
            </style>

            <ol class="adim">
                <li><strong>İstemi kopyalayın</strong> ve Gemini'yi açıp yapıştırın.</li>
                <li>Gemini'nin <strong>yanıtının tamamını</strong> kopyalayıp aşağıdaki kutuya yapıştırın, "Kaydet"e basın.</li>
                <li>Pencereyi kapatıp <strong>aynı yapay zekâ düğmesine tekrar basın</strong> — sistem bu yanıtı kullanır.</li>
            </ol>

            @if ($fotograf)
                <div class="uyari">Bu istemde fotoğraf var: fotoğrafı da Gemini'ye ayrıca yükleyin.</div>
            @endif

            <div>
                <label class="fi-fo-field-wrp-label" for="gemini-elle-istem" style="font-weight:600;display:block;margin-bottom:.35rem">İstem</label>
                <textarea id="gemini-elle-istem" class="istem" readonly x-ref="istem">{{ $istem }}</textarea>
                <div class="satir" style="margin-top:.5rem">
                    <x-filament::button size="sm" icon="heroicon-m-clipboard-document"
                        x-on:click="navigator.clipboard.writeText($refs.istem.value).then(() => { kopyalandi = true; setTimeout(() => kopyalandi = false, 2500) })">
                        <span x-show="! kopyalandi">İstemi kopyala</span>
                        <span x-show="kopyalandi" x-cloak>Kopyalandı ✓</span>
                    </x-filament::button>
                    <x-filament::button size="sm" color="gray" tag="a" href="https://gemini.google.com/app" target="_blank" rel="noopener" icon="heroicon-m-arrow-top-right-on-square">
                        Gemini'yi aç
                    </x-filament::button>
                </div>
            </div>

            <div>
                <label for="gemini-elle-yanit" style="font-weight:600;display:block;margin-bottom:.35rem">Gemini'nin yanıtı</label>
                <textarea id="gemini-elle-yanit" rows="7" wire:model="yanit" placeholder="{{ $json ? 'Gemini\'nin verdiği JSON yanıtı buraya yapıştırın ( [ … ] ya da { … } )' : 'Gemini\'nin yanıtını buraya yapıştırın' }}"></textarea>
                @error('yanit')<p class="hata" role="alert">{{ $message }}</p>@enderror
            </div>

            @if ($kaydedildi)
                <div class="tamam" role="status">Kaydedildi. Pencereyi kapatıp aynı yapay zekâ düğmesine tekrar basın.</div>
            @endif
        </div>

        <x-slot name="footerActions">
            <x-filament::button wire:click="kaydet" wire:loading.attr="disabled" :disabled="$kaydedildi">Kaydet</x-filament::button>
            <x-filament::button color="gray" x-on:click="close">Kapat</x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
