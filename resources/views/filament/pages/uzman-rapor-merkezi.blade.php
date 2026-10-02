@php
    $firma = $this->firma;
    $r = $this->rapor;
    $girdi = 'padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem';
    $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.75rem 1rem';
    $th = 'text-align:left;padding:.5rem .6rem;font-size:.7rem;letter-spacing:.05em;text-transform:uppercase;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .2)';
    $td = 'padding:.6rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $renk = ['gecikmis' => 'rgb(220 38 38)', 'cok_yakin' => 'rgb(234 88 12)', 'yaklasiyor' => 'rgb(217 119 6)', 'eksik' => 'rgb(71 85 105)'];
    $durumAd = ['gecikmis' => 'Gecikmiş', 'cok_yakin' => 'Çok yakın', 'yaklasiyor' => 'Yaklaşıyor', 'eksik' => 'Eksik'];
@endphp

<x-filament-panels::page>
    <div style="display:flex;justify-content:space-between;gap:.6rem;flex-wrap:wrap;align-items:center">
        <p style="font-size:.82rem;color:rgb(107 114 128);margin:0">
            Yalnız aktif görevlendirmeli işyerlerinizin sağlık dışı İSG görünümüdür. Klinik sağlık kayıtları bu rapora ve uzman rolüne dahil edilmez.
        </p>
        <div style="display:flex;gap:.4rem">
            <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-path" wire:click="yenile">Yenile</x-filament::button>
            <x-filament::button size="sm" icon="heroicon-o-arrow-down-tray" wire:click="txtIndir" :disabled="! $firma">TXT indir</x-filament::button>
        </div>
    </div>

    <div style="{{ $kart }};display:flex;flex-wrap:wrap;gap:1rem;justify-content:space-between;align-items:flex-start">
        <div style="min-width:260px">
            <div style="font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;color:rgb(124 58 237);font-weight:700">Aktif işyeri kapsamı</div>
            <div style="font-weight:800">Firma / işyeri seçiniz</div>
            <div style="font-size:.75rem;color:rgb(107 114 128)">Rapor merkezi yalnız seçtiğiniz işyerinin verilerini gösterir. Firma seçilmeden hiçbir rapor verisi yüklenmez.</div>
            @if ($firma)
                <div style="font-size:.78rem;margin-top:.4rem"><strong>Seçili firma:</strong> {{ $firma->unvan }} · <strong>NACE:</strong> {{ $firma->nace_kodu ?: '—' }} · <strong>Tehlike:</strong> {{ $firma->tehlikeSinifiEtiketi() }}</div>
            @endif
        </div>
        <div style="flex:1;min-width:260px;max-width:560px">
            <label style="display:block;font-size:.75rem;font-weight:600">Firma / işyeri</label>
            <select wire:model.live="firmaId" style="{{ $girdi }};width:100%">
                <option value="">— Firma seçin —</option>
                @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
            </select>
        </div>
    </div>

    @if ($r)
        @php $g = $r['gostergeler']; $u = $r['uygunluk']; @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem">
            @foreach ([
                ['İşyeri', $g['isyeri'], 'rgb(13 148 136)'], ['Aktif çalışan', $g['aktif_calisan'], 'rgb(13 148 136)'], ['Açık risk', $g['acik_risk'], 'rgb(220 38 38)'],
                ['Açık olay', $g['acik_olay'], 'rgb(217 119 6)'], ['KKD zimmet', $g['kkd_zimmet'], 'rgb(124 58 237)'], ['Yaklaşan / geciken', $g['yaklasan_geciken'], 'rgb(220 38 38)'],
            ] as [$ad, $deger, $c])
                <div style="{{ $kart }};border-top:3px solid {{ $c }}">
                    <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $ad }}</div>
                    <div style="font-size:1.4rem;font-weight:800">{{ $deger }}</div>
                </div>
            @endforeach
        </div>

        <x-filament::section>
            <x-slot name="heading">İşyeri uygunluk özeti</x-slot>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem;min-width:860px">
                    <tr>@foreach (['İşyeri', 'NACE / Tehlike', 'Çalışan', 'Açık risk', 'Kontrol', 'Mevzuat', 'İşlem'] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                    <tr>
                        <td style="{{ $td }}"><strong>{{ $firma->unvan }}</strong><div style="font-size:.72rem;color:{{ $u['durum'] === 'Uygun' ? 'rgb(21 128 61)' : 'rgb(220 38 38)' }}">{{ $u['durum'] }}</div></td>
                        <td style="{{ $td }}">{{ $firma->nace_kodu ?: '—' }}<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $firma->tehlikeSinifiEtiketi() }}</div></td>
                        <td style="{{ $td }}">{{ $g['aktif_calisan'] }}</td>
                        <td style="{{ $td }}">{{ $g['acik_risk'] }}</td>
                        <td style="{{ $td }};white-space:nowrap">{{ $u['basarisiz'] }} başarısız<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $u['yaklasan_geciken'] }} yaklaşan / geciken</div></td>
                        <td style="{{ $td }};font-size:.75rem;line-height:1.45">
                            @foreach (array_slice($u['dayanaklar'], 0, 3) as $d)
                                <div>{{ $d['ad'] }}</div>
                                <a href="{{ $d['url'] }}" target="{{ $d['resmi'] ? '_blank' : '_self' }}" rel="noopener" style="color:rgb(37 99 235);font-size:.72rem">{{ $d['resmi'] ? 'Resmî kaynağı aç ↗' : 'Mevzuat sayfasında aç' }}</a>
                            @endforeach
                            @if (count($u['dayanaklar']) > 3)
                                <details style="margin-top:.2rem"><summary style="cursor:pointer;color:rgb(107 114 128)">+{{ count($u['dayanaklar']) - 3 }} dayanak</summary>
                                    @foreach (array_slice($u['dayanaklar'], 3) as $d)
                                        <div>{{ $d['ad'] }} <a href="{{ $d['url'] }}" target="{{ $d['resmi'] ? '_blank' : '_self' }}" rel="noopener" style="color:rgb(37 99 235)">{{ $d['resmi'] ? '↗' : '→' }}</a></div>
                                    @endforeach
                                </details>
                            @endif
                            @if (! $u['dayanaklar'])<span style="color:rgb(107 114 128)">—</span>@endif
                        </td>
                        <td style="{{ $td }};white-space:nowrap">
                            <x-filament::button size="xs" color="gray" tag="a" :href="\App\Filament\Pages\IsyeriDurumMerkezi::getUrl(['firma' => $firma->id])">İşyeri</x-filament::button>
                            <x-filament::button size="xs" tag="a" :href="\App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource::getUrl('index')">Risk</x-filament::button>
                            <x-filament::button size="xs" color="gray" tag="a" :href="\App\Filament\Pages\EgitimYenilemeTakibi::getUrl(['firma' => $firma->id])">Eğitim</x-filament::button>
                        </td>
                    </tr>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Öncelikli aksiyonlar ({{ $r['aksiyonlar']->count() }})</x-slot>
            <div style="display:flex;flex-direction:column;gap:.5rem">
                @forelse ($r['aksiyonlar']->take($gosterilen) as $a)
                    <div style="border:1px solid rgb(107 114 128 / .2);border-left:4px solid {{ $renk[$a['durum']] ?? 'gray' }};border-radius:.6rem;padding:.6rem .8rem;display:flex;flex-wrap:wrap;justify-content:space-between;gap:.5rem;align-items:center">
                        <div style="flex:1;min-width:260px">
                            <div style="font-weight:700;font-size:.85rem">{{ $a['baslik'] }} <span style="font-size:.7rem;font-weight:700;color:{{ $renk[$a['durum']] ?? 'gray' }}">· {{ $durumAd[$a['durum']] ?? '' }}</span></div>
                            <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $a['alt'] }}</div>
                            <a href="{{ $a['dayanak']['url'] }}" target="{{ $a['dayanak']['resmi'] ? '_blank' : '_self' }}" rel="noopener" style="font-size:.72rem;color:rgb(37 99 235)" title="{{ $a['dayanak']['ad'] }}">{{ $a['dayanak']['resmi'] ? 'Resmî kaynağı aç ↗' : 'Dayanak: '.$a['dayanak']['ad'] }}</a>
                        </div>
                        <div style="display:flex;gap:.35rem">
                            <x-filament::button size="xs" color="gray" tag="a" :href="\App\Filament\Pages\IsyeriDurumMerkezi::getUrl(['firma' => $firma->id])">İşyeri</x-filament::button>
                            @if ($a['url'])<x-filament::button size="xs" tag="a" :href="$a['url']">Aç: {{ $a['modul'] }}</x-filament::button>@endif
                        </div>
                    </div>
                @empty
                    <div style="font-size:.85rem;color:rgb(21 128 61)">Öncelikli aksiyon yok.</div>
                @endforelse
                @if ($r['aksiyonlar']->count() > $gosterilen)
                    <x-filament::button size="sm" color="gray" wire:click="dahaFazla">Daha fazla göster ({{ $r['aksiyonlar']->count() - $gosterilen }} kaldı)</x-filament::button>
                @endif
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div style="font-size:.85rem;color:rgb(107 114 128)">Rapor verisi için yukarıdan bir firma / işyeri seçin.</div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
