@php
    $mavi = 'rgb(59 130 246)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma, çalışan(lar) ve teslim edilecek kişisel koruyucu donanımları seçip
        "PDF Oluştur" ile her çalışan için ayrı zimmet tutanağı üretin.
    </p>

    {{-- 1. FİRMA & TESLİM BİLGİLERİ --}}
    <x-filament::section icon="heroicon-o-shield-check" icon-color="info">
        <x-slot name="heading">1. Firma & Teslim Bilgileri</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Teslim Tarihi</label>
                <input type="date" wire:model="teslimTarihi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Periyodik Kontrol Tarihi</label>
                <input type="date" wire:model="periyodikKontrolTarihi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Teslim Eden (Ad Soyad / Ünvan)</label>
                <input type="text" wire:model="teslimEden" placeholder="İSG Uzmanı / Depo Sorumlusu vb."
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. ÇALIŞAN SEÇİMİ --}}
        <x-filament::section icon="heroicon-o-user-group" icon-color="info">
            <x-slot name="heading">
                2. Çalışan Seçimi
                <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ count($secilenCalisanIdler) + count($manuelCalisanlar) }} çalışan)</span>
            </x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.35rem;margin-bottom:.75rem">
                    @foreach ($this->calisanlar as $c)
                        @php $secili = in_array($c->id, $secilenCalisanIdler, true); @endphp
                        <button type="button" wire:click="calisanToggle({{ $c->id }})"
                            style="text-align:left;padding:.45rem .65rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                                border:1px solid {{ $secili ? $mavi : 'rgb(107 114 128 / .3)' }};
                                background:{{ $secili ? 'rgb(59 130 246 / .08)' : 'transparent' }}">
                            {{ $secili ? '☑' : '☐' }} {{ $c->ad_soyad }}
                        </button>
                    @endforeach
                </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.5rem;margin-bottom:.5rem">
                <input type="text" wire:model="manuelAdSoyad" placeholder="Ad Soyad"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <input type="text" wire:model="manuelTc" placeholder="T.C. Kimlik No"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <input type="text" wire:model="manuelDepartman" placeholder="Departman / Görev"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <x-filament::button size="sm" wire:click="manuelCalisanEkle">+ Ekle</x-filament::button>
            </div>

            @if ($manuelCalisanlar)
                <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                    @foreach ($manuelCalisanlar as $i => $c)
                        <tr>
                            <td style="padding:.25rem .5rem">{{ $c['ad_soyad'] }}</td>
                            <td style="padding:.25rem .5rem;text-align:right">
                                <button type="button" wire:click="manuelCalisanSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 3. KKD SEÇİMİ --}}
        <x-filament::section icon="heroicon-o-shield-exclamation" icon-color="info">
            <x-slot name="heading">
                3. KKD Seçimi
                <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ count($secilenKkdler) }} seçili)</span>
            </x-slot>

            <div style="margin-bottom:.75rem">
                <x-filament::button size="xs" color="gray" wire:click="kkdSecimiTemizle">Temizle</x-filament::button>
            </div>

            @foreach ($this->kategoriler as $anahtar => $kategori)
                <details style="margin-bottom:.6rem" open>
                    <summary style="cursor:pointer;font-weight:700;font-size:.85rem;color:{{ $mavi }};margin-bottom:.4rem">
                        {{ $kategori['ad'] }} ({{ count($kategori['maddeler']) }})
                    </summary>
                    <div style="margin-bottom:.4rem">
                        <x-filament::button size="xs" color="gray" wire:click="kategoriTumunuSec('{{ $anahtar }}')">+ Kategorinin Tümünü Seç</x-filament::button>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.35rem">
                        @foreach ($kategori['maddeler'] as $m)
                            @php $secili = in_array($anahtar.'|'.$m['ad'], $secilenKkdler, true); @endphp
                            <button type="button" wire:click="kkdToggle('{{ $anahtar }}', '{{ addslashes($m['ad']) }}')"
                                style="text-align:left;padding:.4rem .6rem;border-radius:.4rem;cursor:pointer;font-size:.78rem;
                                    border:1px solid {{ $secili ? $mavi : 'rgb(107 114 128 / .3)' }};
                                    background:{{ $secili ? 'rgb(59 130 246 / .08)' : 'transparent' }}">
                                {{ $secili ? '☑' : '☐' }} {{ $m['ad'] }}
                                <div style="font-size:.68rem;color:rgb(107 114 128)">{{ $m['standart'] }}</div>
                            </button>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </x-filament::section>

        {{-- 4. GEÇMİŞ FORMLAR --}}
        @if ($this->gecmisFormlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş KKD Formları</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisFormlar as $f)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $f->form_no }} — {{ $f->teslim_tarihi?->format('d.m.Y') }} ({{ count($f->calisanlar ?? []) }} çalışan, {{ count($f->kkdler ?? []) }} KKD)</td>
                            <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $f->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $f->id }})">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
