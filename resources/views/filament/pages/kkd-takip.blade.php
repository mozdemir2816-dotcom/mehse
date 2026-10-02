@php
    $inp = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $lbl = 'font-weight:600;font-size:.82rem';
    $th = 'text-align:left;padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3);font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:rgb(107 114 128)';
    $td = 'padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $rozet = fn (string $renk) => "font-size:.72rem;padding:.1rem .5rem;border-radius:999px;white-space:nowrap;border:1px solid {$renk};";
    $takipRenk = ['aktif' => 'rgb(22 163 74 / .55)', 'yaklasan' => 'rgb(245 158 11 / .6)', 'gecikmis' => 'rgb(220 38 38 / .6)'];
    $stokRenk = ['yeterli' => 'rgb(22 163 74 / .55)', 'yaklasan' => 'rgb(245 158 11 / .6)', 'dusuk' => 'rgb(245 158 11 / .6)', 'tukendi' => 'rgb(220 38 38 / .6)', 'suresi_gecmis' => 'rgb(220 38 38 / .6)'];
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Personel bazlı KKD zimmet sicili: kategori → tür, yenileme / SKT takibi, iade ve yenileme, zimmet formu ve Excel.
        Stok kartlarıyla giriş, zimmet çıkışı, iade ve fire izlenir. Toplu teslim tutanağı için
        <a href="{{ \App\Filament\Pages\KkdFormu::getUrl() }}" style="color:rgb(124 58 237);text-decoration:underline">KKD Zimmet Formu</a>
        da kullanılabilir.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
        <div>
            <label style="{{ $lbl }}">Firma <span style="color:#ef4444">*</span></label>
            <select wire:model.live="firmaId" style="{{ $inp }}">
                <option value="">— Firma seçin —</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($this->firma)
        @php $o = $this->ozet; @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem">
            @foreach ([
                ['Aktif zimmet', $o['aktif'], 'rgb(124 58 237)', 'aktif'],
                ['Yenileme yaklaşan', $o['yaklasan'], '#d97706', 'yaklasan'],
                ['Yenileme geciken', $o['gecikmis'], '#dc2626', 'gecikmis'],
                ['Düşük stok', $o['dusuk_stok'], '#d97706', null],
            ] as [$ad, $sayi, $renk, $filtre])
                <button type="button" @if ($filtre) wire:click="$set('durumFiltre', '{{ $filtre }}')" @endif
                    style="text-align:left;border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:.75rem 1rem;background:transparent;cursor:{{ $filtre ? 'pointer' : 'default' }}">
                    <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $ad }}</div>
                    <div style="font-size:1.5rem;font-weight:700;color:{{ $sayi > 0 ? $renk : 'inherit' }}">{{ $sayi }}</div>
                </button>
            @endforeach
        </div>

        {{-- ZİMMET SİCİLİ --}}
        <x-filament::section icon="heroicon-o-user-group" icon-color="primary">
            <x-slot name="heading">KKD Zimmet Kayıtları</x-slot>
            <x-slot name="afterHeader">
                <x-filament::button size="sm" icon="heroicon-o-plus" wire:click="mountAction('yeniZimmet')">Yeni Zimmet</x-filament::button>
            </x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-bottom:.75rem">
                <input type="search" wire:model.live.debounce.400ms="arama" placeholder="Personel, tür, marka, seri no ara…" style="{{ $inp }}">
                <select wire:model.live="durumFiltre" style="{{ $inp }}">
                    <option value="">Tüm durumlar</option>
                    <option value="aktif">Teslimde (tümü)</option>
                    <option value="yaklasan">Yenileme yaklaşan</option>
                    <option value="gecikmis">Yenileme geciken</option>
                    @foreach (collect(config('isg.kkd_takip.durumlar'))->except('teslim_edildi') as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>

            @if ($this->zimmetler->isNotEmpty())
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:900px">
                        <tr>
                            @foreach (['No', 'Teslim', 'Personel', 'KKD', 'Adet', 'Kalan', 'Marka / Model', 'Yenileme', 'Durum', ''] as $b)
                                <th style="{{ $th }}">{{ $b }}</th>
                            @endforeach
                        </tr>
                        @foreach ($this->zimmetler as $z)
                            @php $takip = $z->takipDurumu(); $kalan = $z->aktifMi() ? $z->kalanGun() : null; @endphp
                            <tr>
                                <td style="{{ $td }};white-space:nowrap">{{ $z->zimmet_no }}</td>
                                <td style="{{ $td }};white-space:nowrap">{{ $z->teslim_tarihi?->format('d.m.Y') }}</td>
                                <td style="{{ $td }}">
                                    {{ $z->personel_ad_soyad }}
                                    @if ($z->bolum)<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $z->bolum }}</div>@endif
                                </td>
                                <td style="{{ $td }}">
                                    {{ $z->tur }}@if ($z->beden)<span style="color:rgb(107 114 128)"> · {{ $z->beden }}</span>@endif
                                    @if ($z->seri_no)<div style="font-size:.72rem;color:rgb(107 114 128)">SN: {{ $z->seri_no }}</div>@endif
                                </td>
                                <td style="{{ $td }}">{{ $z->adet }}</td>
                                <td style="{{ $td }};white-space:nowrap;color:{{ $kalan !== null && $kalan < 0 ? '#dc2626' : 'inherit' }}">
                                    {{ $kalan === null ? '—' : ($kalan < 0 ? abs($kalan).' gün geçti' : $kalan.' gün') }}
                                </td>
                                <td style="{{ $td }}">{{ $z->markaModel() ?: '—' }}</td>
                                <td style="{{ $td }};white-space:nowrap">{{ $z->vade()?->format('d.m.Y') ?? '—' }}</td>
                                <td style="{{ $td }}"><span style="{{ $rozet($takipRenk[$takip] ?? 'rgb(107 114 128 / .45)') }}">{{ $z->takipEtiketi() }}</span></td>
                                <td style="{{ $td }};text-align:right;white-space:nowrap">
                                    <x-filament::button size="xs" color="gray" wire:click="zimmetFormu({{ $z->id }})">Zimmet Formu</x-filament::button>
                                    <x-filament::button size="xs" color="primary" wire:click="mountAction('zimmetDuzenle', { id: {{ $z->id }} })">Düzenle</x-filament::button>
                                    @if ($z->aktifMi())
                                        <x-filament::button size="xs" color="warning" wire:click="mountAction('zimmetYenile', { id: {{ $z->id }} })">Yenile</x-filament::button>
                                        <x-filament::button size="xs" color="success" wire:click="mountAction('durumDegistir', { id: {{ $z->id }} })">İade / Durum</x-filament::button>
                                    @endif
                                    <x-filament::button size="xs" color="danger" wire:click="zimmetSil({{ $z->id }})" wire:confirm="Zimmet kaydı silinsin mi? Stoktan düşüldüyse stoğa geri eklenir.">Sil</x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @else
                <p style="font-size:.83rem;color:rgb(107 114 128);text-align:center;padding:1rem">
                    {{ $this->tumZimmetler->isEmpty() ? 'KKD kaydı yok. Yeni zimmet ekleyin.' : 'Filtreye uyan kayıt yok.' }}
                </p>
            @endif
        </x-filament::section>

        {{-- STOK YÖNETİMİ --}}
        <x-filament::section icon="heroicon-o-archive-box" icon-color="primary">
            <x-slot name="heading">KKD Stok Yönetimi</x-slot>
            <x-slot name="description">Stok girişini, zimmet çıkışını, iadeyi, fireyi ve SKT / yenileme tarihlerini aynı kartta izleyin.</x-slot>
            <x-slot name="afterHeader">
                <x-filament::button size="sm" icon="heroicon-o-plus" wire:click="mountAction('yeniStokKarti')">Yeni Stok Kartı</x-filament::button>
            </x-slot>

            @if ($this->stokKartlari->isNotEmpty())
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:800px">
                        <tr>
                            @foreach (['KKD', 'Marka / Beden', 'Mevcut', 'Min.', 'Süre', 'Durum', ''] as $b)
                                <th style="{{ $th }}">{{ $b }}</th>
                            @endforeach
                        </tr>
                        @foreach ($this->stokKartlari as $k)
                            @php $durum = $k->durum(); @endphp
                            <tr>
                                <td style="{{ $td }}">
                                    {{ $k->tur }}
                                    @if ($k->kategori)<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $k->kategoriEtiketi() }}</div>@endif
                                </td>
                                <td style="{{ $td }}">{{ collect([$k->marka, $k->model, $k->beden ? 'Beden '.$k->beden : null])->filter()->implode(' · ') ?: '—' }}</td>
                                <td style="{{ $td }};font-weight:700">{{ $k->mevcut }}</td>
                                <td style="{{ $td }}">{{ $k->asgari }}</td>
                                <td style="{{ $td }};white-space:nowrap">{{ $k->vade()?->format('d.m.Y') ?? ($k->raf_omru ?: '—') }}</td>
                                <td style="{{ $td }}"><span style="{{ $rozet($stokRenk[$durum]) }}">{{ $k->durumEtiketi() }}</span></td>
                                <td style="{{ $td }};text-align:right;white-space:nowrap">
                                    <x-filament::button size="xs" color="success" wire:click="mountAction('stokHareket', { id: {{ $k->id }} })">Giriş / Fire</x-filament::button>
                                    <x-filament::button size="xs" color="gray" wire:click="mountAction('stokGecmisi', { id: {{ $k->id }} })">Geçmiş</x-filament::button>
                                    <x-filament::button size="xs" color="primary" wire:click="mountAction('stokDuzenle', { id: {{ $k->id }} })">Düzenle</x-filament::button>
                                    <x-filament::button size="xs" color="danger" wire:click="stokSil({{ $k->id }})" wire:confirm="Stok kartı ve hareket geçmişi silinsin mi? Zimmet kayıtları silinmez.">Sil</x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @else
                <p style="font-size:.83rem;color:rgb(107 114 128);text-align:center;padding:1rem">Stok kartı yok. İlk kartı oluşturarak başlayın.</p>
            @endif
        </x-filament::section>
    @else
        <p style="font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
