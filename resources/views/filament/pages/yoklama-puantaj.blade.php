@php
    $inp = 'margin-top:.25rem;width:100%;padding:.45rem .65rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.84rem';
    $lbl = 'font-weight:600;font-size:.78rem';
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <p class="mehse-bolum-aciklama">
        Şantiye ve üretim sahalarında günlük personel yoklamasını tek tıkla alın; hem aylık puantaj raporu üretin
        hem de acil durum / yangın tahliyesi için <strong>"Bugün Sahada Kimler Var?"</strong> mevcut listesini hazır tutun.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
        <div>
            <label style="{{ $lbl }}">İşyeri</label>
            <select wire:model.live="firmaId" style="{{ $inp }}">
                <option value="">— İşyeri seçin —</option>
                @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
            </select>
        </div>
        <div>
            <label style="{{ $lbl }}">Yoklama Tarihi</label>
            <input type="date" wire:model.live="tarih" style="{{ $inp }}">
        </div>
    </div>

    @if ($this->firma)
        @php $o = $this->ozet; @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(135px,1fr));gap:.65rem">
            @foreach ([
                ['Sahada (Mevcut)', $o['mevcut'], '#15803d'],
                ['İzinli', $o['izinli'], '#2563eb'],
                ['Raporlu', $o['raporlu'], '#d97706'],
                ['Görevli', $o['gorevli'], '#7c3aed'],
                ['Gelmedi', $o['gelmedi'], '#dc2626'],
                ['İşaretsiz', $o['isaretsiz'], '#64748b'],
            ] as [$ad, $sayi, $renk])
                <div style="border:1px solid rgb(107 114 128 / .25);border-left:4px solid {{ $renk }};border-radius:.65rem;padding:.6rem .85rem">
                    <div class="mehse-soluk">{{ $ad }}</div>
                    <div style="font-size:1.4rem;font-weight:800;color:{{ $sayi > 0 ? $renk : 'inherit' }}">{{ $sayi }}</div>
                </div>
            @endforeach
        </div>

        <x-filament::section icon="heroicon-o-user-group" icon-color="primary">
            <x-slot name="heading">{{ \Illuminate\Support\Carbon::parse($tarih)->format('d.m.Y') }} Günlük Yoklama Listesi ({{ $this->calisanlar->count() }} aktif çalışan)</x-slot>
            @if ($this->calisanlar->isEmpty())
                <p class="mehse-soluk">Bu işyerinde kayıtlı aktif çalışan bulunmuyor.</p>
            @else
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                        <thead>
                            <tr style="text-align:left;background:rgb(107 114 128 / .08)">
                                <th style="padding:.45rem">Çalışan</th>
                                <th style="padding:.45rem">Görev / Şube</th>
                                <th style="padding:.45rem">Durum Seçimi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->calisanlar as $c)
                                @php $mevcutDurum = $this->gunlukDurumlar[$c->id] ?? null; @endphp
                                <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                                    <td style="padding:.45rem;font-weight:600">{{ $c->ad_soyad }}</td>
                                    <td style="padding:.45rem;color:rgb(107 114 128)">{{ collect([$c->gorev, $c->sube])->filter()->implode(' · ') ?: '—' }}</td>
                                    <td style="padding:.45rem">
                                        <div style="display:flex;gap:.3rem;flex-wrap:wrap">
                                            @foreach (\App\Models\YoklamaKaydi::DURUMLAR as $kod => $etiket)
                                                @php
                                                    $secili = $mevcutDurum === $kod;
                                                    $renk = \App\Models\YoklamaKaydi::DURUM_RENK[$kod];
                                                @endphp
                                                <button type="button" wire:click="durumIsaretle({{ $c->id }}, '{{ $kod }}')"
                                                    style="font-size:.73rem;font-weight:700;padding:.24rem .55rem;border-radius:999px;cursor:pointer;
                                                        border:1px solid {{ $renk }};
                                                        background:{{ $secili ? $renk : 'transparent' }};
                                                        color:{{ $secili ? '#fff' : $renk }}">
                                                    {{ $etiket }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        @if (count($this->aylikPuantaj))
            <x-filament::section icon="heroicon-o-calendar-days" icon-color="gray" collapsible collapsed>
                <x-slot name="heading">{{ \Illuminate\Support\Carbon::parse($tarih)->translatedFormat('F Y') }} Aylık Puantaj Özeti</x-slot>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                        <thead>
                            <tr style="text-align:left;background:rgb(107 114 128 / .08)">
                                <th style="padding:.4rem">Çalışan</th>
                                <th style="padding:.4rem">Görev</th>
                                <th style="padding:.4rem">Mevcut</th>
                                <th style="padding:.4rem">İzinli</th>
                                <th style="padding:.4rem">Raporlu</th>
                                <th style="padding:.4rem">Görevli</th>
                                <th style="padding:.4rem">Gelmedi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->aylikPuantaj as $p)
                                <tr style="border-top:1px solid rgb(107 114 128 / .14)">
                                    <td style="padding:.4rem;font-weight:600">{{ $p['ad_soyad'] }}</td>
                                    <td style="padding:.4rem">{{ $p['gorev'] }}</td>
                                    <td style="padding:.4rem;font-weight:700;color:#15803d">{{ $p['mevcut'] }}</td>
                                    <td style="padding:.4rem;color:#2563eb">{{ $p['izinli'] }}</td>
                                    <td style="padding:.4rem;color:#d97706">{{ $p['raporlu'] }}</td>
                                    <td style="padding:.4rem;color:#7c3aed">{{ $p['gorevli'] }}</td>
                                    <td style="padding:.4rem;color:#dc2626">{{ $p['gelmedi'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
