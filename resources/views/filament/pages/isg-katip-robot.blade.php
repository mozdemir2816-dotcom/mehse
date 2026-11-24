@php
    $mor = 'rgb(37 99 235)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')

    {{-- ÇSGB İBYS UYUMLU AKTARIM PAKETİ (XML / JSON) --}}
    <div style="{{ $kutu }};border-left:4px solid {{ $mor }}">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap">
            <div>
                <div style="font-weight:700;font-size:.95rem">🏛️ ÇSGB İBYS Uyumlu Veri Aktarım Paketi (XML / JSON)</div>
                <div style="font-size:.79rem;color:rgb(107 114 128);margin-top:.15rem">
                    Seçili işyerinin eğitim bildirimleri, sağlık muayene kayıtları ve İSG sözleşme künyesini standart İBYS şemasında dışa aktarın.
                    Canlı Bakanlık API gönderimi resmî ÇSGB İBYS Entegratör Yetkilendirmesi &amp; e-İmza ile devreye alınır.
                </div>
            </div>
        </div>
        <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;margin-top:.75rem">
            <select wire:model="ibysFirmaId" style="flex:1;min-width:240px;max-width:420px;padding:.45rem .65rem;border:1px solid rgb(107 114 128 / .35);border-radius:.5rem;background:transparent;font-size:.83rem">
                <option value="">— Aktarılacak işyerini seçin —</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
            <x-filament::button size="sm" icon="heroicon-o-code-bracket" wire:click="ibysIndir('xml')">İBYS XML İndir</x-filament::button>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-document-text" wire:click="ibysIndir('json')">İBYS JSON İndir</x-filament::button>
        </div>
    </div>

    {{-- İSG-KATİP RESMÎ EXCEL SENKRONİZASYON GEÇİŞİ --}}
    <div style="{{ $kutu }};display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div>
            <div style="font-weight:700;font-size:.92rem">📊 İSG-KATİP Sözleşme &amp; İşyeri Senkronizasyonu</div>
            <div style="font-size:.8rem;color:rgb(107 114 128);margin-top:.2rem">
                İSG-KATİP portalındaki <strong>"Tümünü Dışa Aktar"</strong> Excel dosyasını tek seferde yükleyerek tüm uzman/hekim sözleşmelerini ve firma otomatik doldurma listesini güncelleyebilirsiniz.
            </div>
        </div>
        <x-filament::button tag="a" :href="\App\Filament\Pages\IsgKatipSozlesmeleri::getUrl()" icon="heroicon-o-arrow-up-tray" color="primary" size="sm">
            İSG-KATİP Sözleşmeleri'ne Git
        </x-filament::button>
    </div>
</x-filament-panels::page>
