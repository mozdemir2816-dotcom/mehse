{{--
    Tek dokunuşla arşiv yükleme düğmesi (HizliArsivYukleme trait'i).
    Parametre: $kategori. 📷 kamerayı açar (telefonda arka kamera); 📎 galeri / PDF.
    Çekilen sayfalar tepside birikir; "Kaydet" ile tek belge olarak arşive girer.
--}}
@php
    $sayfalar = $this->hizliSayfalar[$kategori] ?? [];
    $hedef = 'hizliYeni.'.$kategori;
    $dugme = 'display:inline-flex;align-items:center;justify-content:center;gap:.3rem;min-height:2.4rem;padding:0 .75rem;border-radius:.55rem;font-size:.85rem;font-weight:600;cursor:pointer;white-space:nowrap';
@endphp
<div wire:key="hizli-{{ $kategori }}" style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
    @if ($sayfalar)
        <span style="font-size:.8rem;font-weight:600;color:#15803d">{{ count($sayfalar) }} sayfa</span>
        @foreach ($sayfalar as $i => $s)
            <button type="button" wire:click="hizliSayfaSil('{{ $kategori }}', {{ $i }})" title="Sayfayı çıkar"
                style="font-size:.7rem;padding:.1rem .35rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;cursor:pointer;color:inherit">{{ $i + 1 }} ✕</button>
        @endforeach
        <label style="{{ $dugme }};border:1px solid rgb(37 99 235 / .4);color:rgb(37 99 235)">
            ＋ Sayfa
            <input type="file" wire:model="{{ $hedef }}" accept="image/*" capture="environment" style="display:none">
        </label>
        <button type="button" wire:click="hizliKaydet('{{ $kategori }}')" wire:loading.attr="disabled" wire:target="hizliKaydet"
            style="{{ $dugme }};border:none;background:#15803d;color:#fff">✓ Kaydet</button>
        <button type="button" wire:click="hizliIptal('{{ $kategori }}')" style="{{ $dugme }};border:none;background:transparent;color:#ef4444;padding:0 .4rem">✕</button>
    @else
        <label title="Fotoğraf çek" style="{{ $dugme }};border:none;background:rgb(37 99 235);color:#fff">
            📷 Çek
            <input type="file" wire:model="{{ $hedef }}" accept="image/*" capture="environment" style="display:none">
        </label>
        <label title="Galeriden ya da PDF seç" style="{{ $dugme }};border:1px solid rgb(107 114 128 / .35);color:inherit">
            📎
            <input type="file" wire:model="{{ $hedef }}" accept="image/*,application/pdf" multiple style="display:none">
        </label>
    @endif
    <span wire:loading wire:target="{{ $hedef }}" style="font-size:.78rem;color:rgb(107 114 128)">Yükleniyor…</span>
</div>
