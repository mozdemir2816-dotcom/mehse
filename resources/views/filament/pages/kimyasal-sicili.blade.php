@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;max-width:420px;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İşyerindeki tehlikeli kimyasalları SDS (Malzeme Güvenlik Bilgi Formu) ve GHS/CLP sınıflarıyla listeleyin;
        envanter PDF'ini alın. Afiş / pano kütüphanesine GHS levhaları, uyarı posterleri vb. yükleyebilirsiniz.
    </p>

    <x-filament::section icon="heroicon-o-beaker" icon-color="primary">
        <x-slot name="heading">Firma</x-slot>
        <select wire:model.live="firmaId" style="{{ $girdi }}">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
        </select>
    </x-filament::section>

    @if ($this->firma)
        @php $o = $this->ozet; @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:1px;background:rgb(107 114 128 / .2);border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;overflow:hidden">
            @foreach (['toplam' => 'Ürün', 'sds_var' => 'SDS Var', 'sds_yok' => 'SDS Eksik', 'yaklasan' => 'Gözden Geçirme Yakın', 'gecikmis' => 'Gecikmiş'] as $k => $ad)
                <div style="background:var(--fi-color-white,#fff);padding:.8rem 1rem">
                    <div style="font-size:1.4rem;font-weight:700;font-variant-numeric:tabular-nums;color:{{ $k === 'sds_yok' || $k === 'gecikmis' ? '#b91c1c' : ($k === 'yaklasan' ? '#b45309' : 'inherit') }}">{{ $o[$k] }}</div>
                    <div style="font-size:.78rem;color:rgb(107 114 128)">{{ $ad }}</div>
                </div>
            @endforeach
        </div>

        {{-- KİMYASAL LİSTESİ --}}
        <x-filament::section icon="heroicon-o-list-bullet" icon-color="primary">
            <x-slot name="heading">Kimyasal Envanteri ({{ $this->urunler->count() }})</x-slot>
            @if ($this->urunler->isEmpty())
                <p style="font-size:.85rem;color:rgb(107 114 128)">Sağ üstteki "Kimyasal Ekle" ile başlayın.</p>
            @else
                <div style="overflow-x:auto">
                    <table style="width:100%;font-size:.82rem;border-collapse:collapse;min-width:760px">
                        <thead><tr style="text-align:left;background:rgb(107 114 128 / .08)">
                            <th style="padding:.5rem">Ürün</th><th style="padding:.5rem">CAS</th>
                            <th style="padding:.5rem">GHS</th><th style="padding:.5rem">SDS</th>
                            <th style="padding:.5rem">Gözden Geçirme</th><th style="padding:.5rem"></th>
                        </tr></thead>
                        <tbody>
                        @foreach ($this->urunler as $u)
                            @php $d = $u->gozdenGecirmeDurumu(); @endphp
                            <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                                <td style="padding:.5rem">
                                    <div style="font-weight:600">{{ $u->urun_adi }}</div>
                                    <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $u->tedarikci }} · {{ config('isg.kimyasal.fiziksel_hal.'.$u->fiziksel_hal) }} · {{ $u->kullanim_alani }}</div>
                                </td>
                                <td style="padding:.5rem;font-family:monospace">{{ $u->cas_no ?: '—' }}</td>
                                <td style="padding:.5rem;font-size:.72rem">{{ implode(', ', array_map(fn ($e) => explode(' — ', $e)[0], $u->ghsEtiketleri())) ?: '—' }}</td>
                                <td style="padding:.5rem">
                                    @if ($u->sdsVarMi())
                                        <button wire:click="sdsIndir({{ $u->id }})" style="background:none;border:0;color:rgb(20 184 166);cursor:pointer">İndir</button>
                                    @else
                                        <span style="color:#b91c1c;font-weight:600">EKSİK</span>
                                    @endif
                                </td>
                                <td style="padding:.5rem;color:{{ $d === 'gecikmis' ? '#b91c1c' : ($d === 'yaklasan' ? '#b45309' : 'inherit') }}">
                                    {{ $u->sonraki_gozden_gecirme?->format('d.m.Y') ?: '—' }}
                                </td>
                                <td style="padding:.5rem">
                                    <button wire:click="kimyasalSil({{ $u->id }})" wire:confirm="'{{ $u->urun_adi }}' silinsin mi?" style="background:none;border:0;color:#ef4444;cursor:pointer">Sil</button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- AFİŞLER --}}
    <x-filament::section icon="heroicon-o-photo" icon-color="gray" collapsible :collapsed="$this->firma !== null">
        <x-slot name="heading">Afiş / Pano Kütüphanesi</x-slot>
        @if ($this->afisler->isEmpty())
            <p style="font-size:.85rem;color:rgb(107 114 128)">"Afiş / Pano Ekle" ile GHS levhaları, uyarı posterleri vb. yükleyin.</p>
        @else
            @foreach ($this->afisler as $kategori => $liste)
                <div style="margin-bottom:1rem">
                    <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:rgb(107 114 128);margin-bottom:.4rem">{{ $kategori }}</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.6rem">
                        @foreach ($liste as $a)
                            <div style="{{ $kutu }};font-size:.82rem">
                                <div style="font-weight:600">{{ $a->baslik }}</div>
                                <div style="font-size:.72rem;color:rgb(107 114 128)">
                                    {{ $a->firma_id ? $a->firma?->unvan : 'Genel' }} · {{ $a->dosya_adi }} ({{ $a->boyutEtiketi() }})
                                </div>
                                <div style="margin-top:.5rem;display:flex;gap:.4rem">
                                    <x-filament::button size="xs" color="gray" wire:click="afisIndir({{ $a->id }})">İndir</x-filament::button>
                                    <x-filament::button size="xs" color="danger" wire:click="afisSil({{ $a->id }})" wire:confirm="Afiş silinsin mi?">Sil</x-filament::button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </x-filament::section>
</x-filament-panels::page>
