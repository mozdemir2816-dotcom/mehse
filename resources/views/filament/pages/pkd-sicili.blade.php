@php
    $inp = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $th = 'text-align:left;padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3);font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:rgb(107 114 128)';
    $td = 'padding:.45rem .5rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:.7rem 1rem';
    $rozet = fn (string $renk) => "font-size:.7rem;padding:.08rem .45rem;border-radius:999px;white-space:nowrap;border:1px solid {$renk}";
    $durumRenk = ['taslak' => 'rgb(107 114 128 / .45)', 'aktif' => 'rgb(22 163 74 / .55)', 'revizyon' => 'rgb(245 158 11 / .6)', 'arsiv' => 'rgb(107 114 128 / .3)'];
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İşyeri, bölüm ve proses bazında PKD künyelerini; zone sınıflarını, tutuşturucu kaynakları ve korunma
        önlemlerini tek yerde tutun. PKD teknik dosyası, revizyonu ve gözden geçirme tarihi aynı kayıttan izlenir.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;align-items:end">
        <div>
            <label style="font-weight:600;font-size:.82rem">İşyeri</label>
            <select wire:model.live="firmaId" style="{{ $inp }}">
                <option value="">Tüm işyerleri</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
        </div>
        <div style="text-align:right">
            <x-filament::button icon="heroicon-o-plus" color="warning" wire:click="mountAction('yeniPkd')">Yeni PKD Kaydı</x-filament::button>
        </div>
    </div>

    @php $o = $this->ozet; @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.6rem">
        @foreach ([
            ['Toplam PKD', $o['toplam'], 'inherit', null],
            ['Aktif doküman', $o['aktif'], '#16a34a', null],
            ['Dosyası mevcut', $o['dosyali'], 'inherit', $o['dosyasiz'].' eksik'],
            ['Takip gerekiyor', $o['takip'], '#d97706', null],
            ['Gecikmiş inceleme', $o['gecikmis'], '#dc2626', null],
        ] as [$ad, $sayi, $renk, $alt])
            <div style="{{ $kutu }}">
                <div style="font-size:1.4rem;font-weight:700;color:{{ $sayi > 0 ? $renk : 'inherit' }}">{{ $sayi }}</div>
                <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $ad }}@if ($alt) <span style="color:{{ $o['dosyasiz'] > 0 ? '#dc2626' : 'inherit' }}">· {{ $alt }}</span>@endif</div>
            </div>
        @endforeach
    </div>

    <x-filament::section icon="heroicon-o-fire" icon-color="warning">
        <x-slot name="heading">Patlamadan Korunma Dokümanı Sicili ({{ $this->kayitlar->count() }})</x-slot>

        <input type="search" wire:model.live.debounce.400ms="arama" placeholder="Doküman no, bölüm, proses veya madde ara…" style="{{ $inp }};margin:0 0 .75rem">

        @if ($this->kayitlar->isNotEmpty())
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:900px">
                    <tr>
                        @foreach (['Doküman', 'İşyeri', 'Bölüm / Proses', 'Ortam', 'Zone', 'Revizyon / İnceleme', 'Dosya', 'Durum', ''] as $b)
                            <th style="{{ $th }}">{{ $b }}</th>
                        @endforeach
                    </tr>
                    @foreach ($this->kayitlar as $p)
                        @php $inc = $p->incelemeDurumu(); @endphp
                        <tr>
                            <td style="{{ $td }}"><strong>{{ $p->dokuman_no }}</strong><div style="font-size:.72rem;color:rgb(107 114 128)">Rev. {{ $p->revizyon_no ?: '—' }}</div></td>
                            <td style="{{ $td }}">{{ $p->firma?->unvan }}</td>
                            <td style="{{ $td }}">{{ $p->bolum }}<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $p->proses ?: 'Proses belirtilmedi' }}</div></td>
                            <td style="{{ $td }}"><span style="{{ $rozet('rgb(107 114 128 / .4)') }}">{{ $p->ortamEtiketi() }}</span></td>
                            <td style="{{ $td }}">
                                @foreach ($p->etiketler('zonelar') as $z)
                                    <span style="{{ $rozet('rgb(234 88 12 / .55)') }}">{{ $z }}</span>
                                @endforeach
                                @if (empty($p->zonelar))—@endif
                            </td>
                            <td style="{{ $td }};white-space:nowrap">
                                {{ $p->sonraki_gozden_gecirme?->format('d.m.Y') ?? '—' }}
                                @if ($inc === 'gecikmis')<div><span style="{{ $rozet('rgb(220 38 38 / .6)') }};color:#dc2626">Gecikmiş</span></div>
                                @elseif ($inc === 'yaklasan')<div><span style="{{ $rozet('rgb(245 158 11 / .6)') }}">Yaklaşıyor</span></div>@endif
                            </td>
                            <td style="{{ $td }}">
                                @if ($p->dosyaVarMi())
                                    <button type="button" wire:click="dosyaIndir({{ $p->id }})" style="background:none;border:0;color:rgb(20 184 166);cursor:pointer;padding:0">İndir</button>
                                @else
                                    <span style="color:#dc2626;font-weight:600;font-size:.75rem">Eksik</span>
                                @endif
                            </td>
                            <td style="{{ $td }}"><span style="{{ $rozet($durumRenk[$p->durum] ?? 'rgb(107 114 128 / .45)') }}">{{ $p->durumEtiketi() }}</span></td>
                            <td style="{{ $td }};text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="primary" wire:click="mountAction('pkdDuzenle', { id: {{ $p->id }} })">Düzenle</x-filament::button>
                                <x-filament::button size="xs" color="gray" wire:click="dokumanOlustur({{ $p->id }})">Künye PDF</x-filament::button>
                                <x-filament::button size="xs" color="gray" wire:click="mountAction('dosyaYukle', { id: {{ $p->id }} })">Dosya yükle</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="sil({{ $p->id }})" wire:confirm="PKD kaydı ve dosyası silinsin mi?">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @else
            <div style="text-align:center;padding:1.5rem;color:rgb(107 114 128);font-size:.85rem">
                {{ $this->tumKayitlar->isEmpty() ? 'Henüz PKD kaydı bulunmuyor. "Yeni PKD Kaydı" ile başlayın.' : 'Aramaya uyan kayıt yok.' }}
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
