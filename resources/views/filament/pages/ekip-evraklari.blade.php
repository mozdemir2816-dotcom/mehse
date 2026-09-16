<?php /** @var \App\Filament\Pages\EkipEvraklari $this */ ?>
@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Yetki verdiğiniz kişilerin kendi hesaplarında oluşturduğu firmalardaki Risk
        Değerlendirmesi ve Acil Durum Planı kayıtları burada listelenir. Bir risk
        değerlendirmesini paylaşılan Risk Şablonu kütüphanesine ("havuz") eklemek
        için ilgili satırdaki butonu kullanın.
    </p>

    <x-filament::section icon="heroicon-o-users" icon-color="primary">
        <x-slot name="heading">Kişi Filtresi</x-slot>

        <div style="max-width:320px">
            <label style="font-weight:600;font-size:.82rem">Kişi</label>
            <select wire:model.live="kullaniciId" style="{{ $girdi }}">
                <option value="">— Tümü —</option>
                @foreach ($this->kullanicilar as $k)
                    <option value="{{ $k->id }}">{{ $k->name }}</option>
                @endforeach
            </select>
        </div>
    </x-filament::section>

    <x-filament::section :heading="'Risk Değerlendirmeleri ('.$this->riskDegerlendirmeleri->count().')'" icon="heroicon-o-shield-exclamation">
        @forelse ($this->riskDegerlendirmeleri as $rd)
            <div style="{{ $kutu }};display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:.5rem">
                <div style="font-size:.86rem">
                    <div style="font-weight:700">{{ $rd->firma_unvan ?? $rd->firma?->unvan ?? '—' }}</div>
                    <div style="color:rgb(107 114 128);margin-top:.15rem">
                        {{ $rd->firma?->user?->name ?? '—' }} ·
                        {{ $rd->rapor_tarihi?->format('d.m.Y') }} ·
                        {{ $rd->yontemEtiketi() }} ·
                        {{ $rd->maddeler_count }} madde
                    </div>
                </div>
                <div style="flex-shrink:0">
                    <x-filament::button size="xs" color="primary" icon="heroicon-o-plus-circle"
                        wire:click="havuzaEkle({{ $rd->id }})"
                        wire:confirm="Bu değerlendirmenin {{ $rd->maddeler_count }} maddesi paylaşılan Risk Şablonu havuzuna eklensin mi? Değerlendirmenin kendisi değişmez, kopyası eklenir.">
                        Havuza Ekle
                    </x-filament::button>
                </div>
            </div>
        @empty
            <div style="text-align:center;color:rgb(107 114 128);padding:1.5rem 0">
                Henüz görüntülenecek risk değerlendirmesi yok.
            </div>
        @endforelse
    </x-filament::section>

    <x-filament::section :heading="'Acil Durum Planları ('.$this->acilDurumPlanlari->count().')'" icon="heroicon-o-fire">
        @forelse ($this->acilDurumPlanlari as $p)
            <div style="{{ $kutu }};margin-bottom:.5rem">
                <div style="font-size:.86rem;font-weight:700">{{ $p->firma?->unvan ?? '—' }}</div>
                <div style="font-size:.86rem;color:rgb(107 114 128);margin-top:.15rem">
                    {{ $p->firma?->user?->name ?? '—' }} ·
                    {{ $p->updated_at?->format('d.m.Y') }} ·
                    {{ implode(', ', $p->konuAdlari()) ?: '—' }}
                </div>
            </div>
        @empty
            <div style="text-align:center;color:rgb(107 114 128);padding:1.5rem 0">
                Henüz görüntülenecek acil durum planı yok.
            </div>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
