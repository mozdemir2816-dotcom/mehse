@php
    $girdi = 'width:100%;min-width:0;padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem';
    $th = 'text-align:left;padding:.55rem .6rem;font-size:.7rem;letter-spacing:.05em;text-transform:uppercase;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .2)';
    $td = 'padding:.55rem .6rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $o = $this->ozet;
    $durumRenk = ['Aktif' => 'rgb(21 128 61)', 'Pasif' => 'rgb(107 114 128)', 'Süresi yaklaşıyor' => 'rgb(217 119 6)', 'Süresi doldu' => 'rgb(220 38 38)'];
@endphp

<x-filament-panels::page>
    <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:-.5rem">
        <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" wire:click="excelRapor">Excel Rapor</x-filament::button>
        {{ $this->yeniAction }}
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem">
        @foreach ([['Aktif doküman', $o['aktif'], 'rgb(21 128 61)', 'aktif'], ['Süresi doldu', $o['dolmus'], 'rgb(220 38 38)', 'dolmus'], ['30 gün içinde doluyor', $o['yaklasan'], 'rgb(217 119 6)', 'yaklasan'], ['Pasif', $o['pasif'], 'rgb(107 114 128)', 'pasif']] as [$ad, $sayi, $renk, $f])
            <button type="button" wire:click="$set('durum', '{{ $durum === $f ? '' : $f }}')"
                    style="text-align:left;border:1px solid {{ $durum === $f ? $renk : 'rgb(107 114 128 / .2)' }};border-top:3px solid {{ $renk }};border-radius:.6rem;padding:.55rem .8rem;background:{{ $durum === $f ? 'rgb(107 114 128 / .06)' : 'transparent' }}">
                <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $ad }}</div>
                <div style="font-size:1.4rem;font-weight:800;color:{{ $renk }}">{{ $sayi }}</div>
            </button>
        @endforeach
    </div>

    <x-filament::section>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.5rem;margin-bottom:.8rem">
            <input type="search" wire:model.live.debounce.400ms="arama" placeholder="Ara… (başlık, dosya, açıklama, firma)" style="{{ $girdi }}">
            <select wire:model.live="firmaId" style="{{ $girdi }}">
                <option value="">Tüm firmalar</option>
                @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
            </select>
            <select wire:model.live="kategori" style="{{ $girdi }}">
                <option value="">Tüm kategoriler</option>
                @foreach (config('isg.dokuman.kategoriler') as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select>
            <select wire:model.live="durum" style="{{ $girdi }}">
                <option value="">Tüm durumlar</option>
                <option value="aktif">Aktif</option>
                <option value="yaklasan">Süresi yaklaşan</option>
                <option value="dolmus">Süresi dolan</option>
                <option value="pasif">Pasif</option>
            </select>
        </div>

        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.82rem;min-width:820px">
                <tr>@foreach (['Doküman', 'Kategori', 'Dosya adı', 'Versiyon', 'Geçerlilik sonu', 'Durum', ''] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                @forelse ($this->dokumanlar as $d)
                    <tr wire:key="dok-{{ $d->id }}" style="{{ $d->aktif ? '' : 'opacity:.6' }}">
                        <td style="{{ $td }};max-width:320px">
                            <strong>{{ $d->etiket() }}</strong>
                            <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $d->firma?->unvan }}@if ($d->aciklama) · {{ \Illuminate\Support\Str::limit($d->aciklama, 70) }}@endif</div>
                        </td>
                        <td style="{{ $td }}">{{ $d->kategoriEtiketi() }}</td>
                        <td style="{{ $td }}">{{ \Illuminate\Support\Str::limit($d->dosya_adi, 40) }}<div style="font-size:.7rem;color:rgb(107 114 128)">{{ $d->boyutEtiketi() }}</div></td>
                        <td style="{{ $td }}">{{ $d->versiyon ?: '—' }}</td>
                        <td style="{{ $td }};white-space:nowrap">
                            {{ $d->gecerlilik_sonu?->format('d.m.Y') ?? 'Süresiz' }}
                            @if ($d->baslangic_tarihi)<div style="font-size:.7rem;color:rgb(107 114 128)">Başlangıç {{ $d->baslangic_tarihi->format('d.m.Y') }}</div>@endif
                        </td>
                        <td style="{{ $td }};white-space:nowrap;font-weight:700;color:{{ $durumRenk[$d->durumEtiketi()] ?? 'inherit' }}">{{ $d->durumEtiketi() }}</td>
                        <td style="{{ $td }};width:1%"><div style="display:grid;grid-template-columns:1fr 1fr;gap:.25rem;min-width:170px">
                            <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="indir({{ $d->id }})">İndir</x-filament::button>
                            <x-filament::button size="xs" color="gray" wire:click="mountAction('duzenle', { id: {{ $d->id }} })">Düzenle</x-filament::button>
                            <x-filament::button size="xs" color="gray" wire:click="durumDegistir({{ $d->id }})">{{ $d->aktif ? 'Pasife Al' : 'Aktifleştir' }}</x-filament::button>
                            <x-filament::button size="xs" color="danger" wire:click="sil({{ $d->id }})" wire:confirm="Doküman ve dosyası silinsin mi?">Sil</x-filament::button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="{{ $td }};text-align:center;color:rgb(107 114 128);padding:1.4rem">Doküman bulunamadı. "Yeni Doküman" ile ekleyin.</td></tr>
                @endforelse
            </table>
        </div>
        <p style="font-size:.72rem;color:rgb(107 114 128);margin-top:.6rem">
            Profilim → Arşiv'e yüklenen dosyalar da burada "Diğer" kategorisinde görünür. Geçerlilik sonu girilen dokümanlar süresi yaklaşınca / dolunca Bildirim Merkezi'ne düşer.
        </p>
    </x-filament::section>

</x-filament-panels::page>
