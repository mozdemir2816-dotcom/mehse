{{--
    Komuta Merkezi ortak firma seçimi (29.10.2026): Portföy Genel Bakış, Firma Denetim & Operasyon, Risk & Tehlike
    Analitiği (ve eski İşyeri Durumu / Tesis Uygunluğu / Uzman Raporları adresleri) bu tek çubuğu kullanır.
    Seçim oturumda saklanır (App\Support\KomutaFirma) — sekme değiştirince firma yeniden seçilmez.
    Sayfa sınıfı `$firmaId` ve `$this->firmalar` sağlar; değişkenler: $tumPortfoy (bool), $aciklama (string).
--}}
@php
    $tumPortfoy ??= true;
    $firmaSayisi = count($this->firmalar);
@endphp

<div class="mehse-komuta-cubuk idm-yazdirma" style="display:flex;flex-wrap:wrap;gap:.6rem 1rem;align-items:center;justify-content:space-between;border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.6rem 1rem">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.6rem">
        <label for="komuta-firma" style="font-size:.78rem;font-weight:700">Firma</label>
        <select id="komuta-firma" wire:model.live="firmaId"
                style="min-width:300px;max-width:100%;padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem">
            @if ($tumPortfoy)
                <option value="">★ Tüm Portföy ({{ $firmaSayisi }} Firma)</option>
            @else
                <option value="">— Firma seçin —</option>
            @endif
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </div>
    @isset($aciklama)
        <div style="font-size:.74rem;color:rgb(107 114 128)">{{ $aciklama }}</div>
    @endisset
</div>
