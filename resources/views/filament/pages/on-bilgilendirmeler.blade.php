@php
    $girdi = 'margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $durumRenk = ['teblig_edildi' => ['rgb(16 185 129 / .12)', '#047857'], 'panelde_bekliyor' => ['rgb(37 99 235 / .1)', '#1d4ed8'], 'teblig_bekliyor' => ['rgb(245 158 11 / .12)', '#92400e']];
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İşe başlamadan önce firmaya bildirdiğiniz yazıları (Word/PDF) buraya yükleyin; dosya değiştirilmeden saklanır.
        Firmaya tebliğ işveren panelindeki "okudum, tebellüğ ettim" onayıyla ya da elden teslim kaydıyla belgelenir;
        her kayıt için Tebliğ-Tebellüğ Tutanağı alınabilir.
    </p>

    <x-filament::section icon="heroicon-o-megaphone" icon-color="primary">
        <x-slot name="heading">Firma</x-slot>
        <select wire:model.live="firmaId" style="{{ $girdi }};max-width:28rem">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>

        @if ($this->firma && ! $this->panelHesabiVar)
            <p style="margin-top:.6rem;font-size:.8rem;color:#92400e">
                Bu firmanın İşyeri Girişi hesabı yok — yazıların işveren panelinde görünmesi için Firmalar sayfasından "İşyeri Girişi" oluşturun.
            </p>
        @endif
    </x-filament::section>

    @if ($this->firma)
        <x-filament::section icon="heroicon-o-document-text" icon-color="gray">
            <x-slot name="heading">Bilgilendirme Yazıları <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ $this->kayitlar->count() }})</span></x-slot>

            @forelse ($this->kayitlar as $k)
                @php [$bg, $renk] = $durumRenk[$k->durum()]; @endphp
                <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.6rem;padding:.7rem .85rem;margin-bottom:.6rem">
                    <div style="display:flex;gap:.6rem;align-items:flex-start;flex-wrap:wrap">
                        <div style="flex:1;min-width:14rem">
                            <div style="font-weight:600;font-size:.9rem">{{ $k->konu }}</div>
                            <div style="font-size:.76rem;color:rgb(107 114 128);margin-top:.15rem">
                                {{ $k->bildirim_tarihi?->format('d.m.Y') }} · {{ $k->dosya_adi }}
                            </div>
                            @if ($k->aciklama)
                                <div style="font-size:.78rem;margin-top:.25rem">{{ $k->aciklama }}</div>
                            @endif
                            <div style="font-size:.76rem;margin-top:.35rem">
                                <span style="display:inline-block;background:{{ $bg }};color:{{ $renk }};border-radius:999px;padding:.05rem .55rem;font-weight:600">{{ $k->durumEtiketi() }}</span>
                                @if ($k->isyeri_okundu_at)
                                    <span style="margin-left:.35rem">Panelde onaylandı: {{ $k->isyeri_okundu_at->format('d.m.Y H:i') }} — {{ $k->isyeri_onaylayan }}</span>
                                @endif
                                @if ($k->elden_teslim_tarihi)
                                    <span style="margin-left:.35rem">Elden teslim: {{ $k->elden_teslim_tarihi->format('d.m.Y') }} — {{ $k->elden_teslim_alan }}</span>
                                @endif
                            </div>
                        </div>
                        <div style="display:flex;gap:.3rem;flex-wrap:wrap;justify-content:flex-end">
                            <x-filament::button size="xs" color="info" wire:click="indir({{ $k->id }})">Dosya</x-filament::button>
                            <x-filament::button size="xs" color="success" wire:click="tutanak({{ $k->id }})">Tebliğ Tutanağı</x-filament::button>
                            @if (! $k->isyerine_gonderildi_at)
                                <x-filament::button size="xs" color="primary" wire:click="paneleGonder({{ $k->id }})">Panele Gönder</x-filament::button>
                            @endif
                            <x-filament::button size="xs" color="gray" wire:click="mountAction('eldenTeslim', { id: {{ $k->id }} })">Elden Teslim</x-filament::button>
                            <x-filament::button size="xs" color="danger" wire:click="sil({{ $k->id }})" wire:confirm="Bu bilgilendirme yazısı silinsin mi? Deftere düşen maddesi de kaldırılır.">Sil</x-filament::button>
                        </div>
                    </div>
                </div>
            @empty
                <p style="font-size:.82rem;color:rgb(107 114 128)">Henüz bilgilendirme yazısı yok — sağ üstteki "Bilgilendirme Yazısı Yükle" ile ekleyin.</p>
            @endforelse
        </x-filament::section>
    @endif

</x-filament-panels::page>
