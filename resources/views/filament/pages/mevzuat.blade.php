<?php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
?>
<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İSG pratiğinde en sık başvurulan mevzuatın kısa dizini — tam metin değil, yönlendirme amaçlıdır.
        Madde numarası/tarih gibi ayrıntılar için resmi kaynağa (mevzuat.gov.tr, resmigazete.gov.tr) bakınız.
    </p>

    <div style="margin-top:1rem">
        <input type="text" placeholder="Mevzuat ara (ör. risk değerlendirmesi, KKD, gürültü...)" wire:model.live.debounce.300ms="arama" style="{{ $girdi }}">
    </div>

    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem">
        <x-filament::button size="sm" :color="$kategori === 'tumu' ? 'warning' : 'gray'" wire:click="kategoriSec('tumu')">Tümü</x-filament::button>
        @foreach (config('isg.mevzuat.kategoriler') as $anahtar => $etiket)
            <x-filament::button size="sm" :color="$kategori === $anahtar ? 'warning' : 'gray'" wire:click="kategoriSec('{{ $anahtar }}')">{{ $etiket }}</x-filament::button>
        @endforeach
    </div>

    @php $sonuclar = $this->sonuclar(); @endphp

    <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:1rem">{{ count($sonuclar) }} kayıt</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;margin-top:.5rem">
        @forelse ($sonuclar as $m)
            <div style="{{ $kutu }}">
                <x-filament::badge color="gray" size="sm">{{ config('isg.mevzuat.kategoriler')[$m['kategori']] }}</x-filament::badge>
                <div style="font-weight:600;margin-top:.5rem">{{ $m['baslik'] }}</div>
                <div style="font-size:.82rem;color:rgb(107 114 128);margin-top:.4rem">{{ $m['aciklama'] }}</div>
            </div>
        @empty
            <p style="color:rgb(107 114 128);font-size:.85rem">Eşleşen mevzuat bulunamadı.</p>
        @endforelse
    </div>

    <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:1.5rem">
        Bu dizin, mehse'nin kendi modüllerinde (Risk Değerlendirme, DÖF, Ceza-Tebliğ, KKD Formu vb.) dayanak
        olarak kullanılan mevzuatla sınırlı bir seçkidir — resmi mevzuat.gov.tr'nin tam kütüphanesi değildir.
        Resmi ve güncel metin için
        <a href="https://www.mevzuat.gov.tr" target="_blank" style="color:rgb(217 119 6)">mevzuat.gov.tr</a>
        veya
        <a href="https://www.resmigazete.gov.tr" target="_blank" style="color:rgb(217 119 6)">resmigazete.gov.tr</a>
        kullanılmalıdır.
    </p>
</x-filament-panels::page>
