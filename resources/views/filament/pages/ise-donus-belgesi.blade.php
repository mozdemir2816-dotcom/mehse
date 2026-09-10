@php
    $lbl = 'font-weight:600;font-size:.82rem;display:block;margin-bottom:.25rem';
    $inp = 'width:100%;padding:.45rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem';
    $kisitliMi = in_array($uygunluk, ['kisitli', 'gecici_gorev'], true);
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Uzun süreli rapor, iş kazası veya meslek hastalığı sonrası çalışanın işe dönüşünde
        işyeri hekiminin uygunluk değerlendirmesi ve geçici iş kısıtlamaları (6331 s.K. Md.15).
    </p>

    <x-filament::section icon="heroicon-o-arrow-uturn-left" icon-color="primary">
        <x-slot name="heading">Firma</x-slot>
        <select wire:model.live="firmaId" style="{{ $inp }};max-width:420px">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </x-filament::section>

    @if ($this->firma)
        <x-filament::section icon="heroicon-o-user" icon-color="primary">
            <x-slot name="heading">Çalışan ve Devamsızlık</x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="margin-bottom:.75rem">
                    <label style="{{ $lbl }}">Çalışan Hızlı Seç</label>
                    <select wire:model.live="calisanHizliSecId" style="{{ $inp }}">
                        <option value="">— manuel gir —</option>
                        @foreach ($this->calisanlar as $c)
                            <option value="{{ $c->id }}">{{ $c->ad_soyad }}@if ($c->gorev) — {{ $c->gorev }} @endif</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                <div><label style="{{ $lbl }}">Ad Soyad <span style="color:#ef4444">*</span></label><input type="text" wire:model="calisanAdSoyad" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">T.C. Kimlik No</label><input type="text" wire:model="calisanTc" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Görevi</label><input type="text" wire:model="gorev" style="{{ $inp }}"></div>
                <div>
                    <label style="{{ $lbl }}">İşe Dönüş Nedeni</label>
                    <select wire:model="neden" style="{{ $inp }}">
                        @foreach ($this->nedenler as $a => $ad) <option value="{{ $a }}">{{ $ad }}</option> @endforeach
                    </select>
                </div>
                <div><label style="{{ $lbl }}">Devamsızlık Başlangıç</label><input type="date" wire:model="devamsizlikBaslangic" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Devamsızlık Bitiş</label><input type="date" wire:model="devamsizlikBitis" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">İşe Dönüş Tarihi</label><input type="date" wire:model="iseDonusTarihi" style="{{ $inp }}"></div>
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-heart" icon-color="danger">
            <x-slot name="heading">İşyeri Hekimi Değerlendirmesi</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
                <div>
                    <label style="{{ $lbl }}">Uygunluk Durumu</label>
                    <select wire:model.live="uygunluk" style="{{ $inp }}">
                        @foreach ($this->uygunlukSecenekleri as $a => $ad) <option value="{{ $a }}">{{ $ad }}</option> @endforeach
                    </select>
                </div>
                <div><label style="{{ $lbl }}">Kontrol Muayenesi Tarihi</label><input type="date" wire:model="kontrolMuayeneTarihi" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">İşyeri Hekimi Ad Soyad</label><input type="text" wire:model="hekimAdi" style="{{ $inp }}"></div>
            </div>

            @if ($kisitliMi)
                <div style="margin-top:1rem">
                    <label style="{{ $lbl }}">Geçici İş Kısıtlamaları</label>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.35rem">
                        @foreach ($this->kisitlamaKutuphanesi as $k)
                            <label style="display:flex;align-items:center;gap:.35rem;font-size:.8rem;cursor:pointer">
                                <input type="checkbox" wire:model="kisitlamalar" value="{{ $k }}"> {{ $k }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div style="margin-top:1rem">
                <label style="{{ $lbl }}">Hekim Görüşü / Açıklama</label>
                <textarea wire:model="hekimGorusu" rows="3" placeholder="Ek açıklama, izlenecek yol, kısıtlama süresi vb." style="{{ $inp }}"></textarea>
            </div>
        </x-filament::section>

        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Belgeler</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisKayitlar as $k)
                        <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                            <td style="padding:.35rem .5rem">{{ $k->belge_no }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->calisan_ad_soyad }} — {{ $k->nedenEtiketi() }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->uygunlukEtiketi() }}</td>
                            <td style="padding:.35rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $k->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $k->id }})">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
