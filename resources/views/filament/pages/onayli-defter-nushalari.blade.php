@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Tespit ve Öneri Defteri'ni "İndir" ile alıp imzalatın/onaylatın, taranmış nüshayı
        buraya yükleyin. Nüsha numarası firma ve defter türü başına otomatik artar (1, 2, 3…).
        İSG-KATİP'e yüklenen resmi nüshaların kaydı burada tutulur.
    </p>

    <x-filament::section icon="heroicon-o-document-check" icon-color="primary">
        <x-slot name="heading">Firma & Defter Türü</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId" style="{{ $girdi }}">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Defter Türü</label>
                <select wire:model.live="defterTuru" style="{{ $girdi }}">
                    @foreach (config('isg.onayli_defter.turleri') as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($this->firma)
            <p style="margin-top:.75rem;font-size:.82rem;color:rgb(107 114 128)">
                Sıradaki nüsha: <strong>{{ $this->sonrakiNo }}. Nüsha</strong> —
                yüklemek için sağ üstteki <strong>“Onaylı Nüsha Yükle”</strong> butonunu kullanın.
            </p>
        @endif
    </x-filament::section>

    @if ($this->firma)
        @forelse ($this->nushalar as $turEtiketi => $liste)
            <x-filament::section :heading="$turEtiketi" icon="heroicon-o-book-open">
                <div style="display:flex;flex-direction:column;gap:.5rem">
                    @foreach ($liste as $n)
                        <div style="{{ $kutu }};display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">
                            <div style="font-size:.86rem">
                                <div style="font-weight:700">{{ $n->nusha_no }}. Nüsha</div>
                                <div style="color:rgb(107 114 128);margin-top:.15rem">
                                    @if ($n->onay_tarihi) Onay: {{ $n->onay_tarihi->format('d.m.Y') }} @endif
                                    @if ($n->donem) · {{ $n->donem }} @endif
                                    · {{ $n->dosya_adi }} ({{ $n->boyutEtiketi() }})
                                </div>
                                @if ($n->aciklama)
                                    <div style="margin-top:.3rem">{{ $n->aciklama }}</div>
                                @endif
                            </div>
                            <div style="display:flex;gap:.4rem;flex-shrink:0">
                                <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-down-tray"
                                    wire:click="nushaIndir({{ $n->id }})">İndir</x-filament::button>
                                <x-filament::button size="xs" color="danger" icon="heroicon-o-trash"
                                    wire:click="nushaSil({{ $n->id }})"
                                    wire:confirm="{{ $n->nusha_no }}. nüsha silinsin mi? Dosya da kaldırılır.">Sil</x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <div style="text-align:center;color:rgb(107 114 128);padding:1.5rem 0">
                    Bu firma için henüz onaylı nüsha yüklenmemiş.
                </div>
            </x-filament::section>
        @endforelse
    @endif
</x-filament-panels::page>
