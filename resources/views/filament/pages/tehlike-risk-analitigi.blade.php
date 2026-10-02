@php
    use App\Support\RiskAnalitigi as RA;
    $firma = $this->firma;
    $rd = $this->rd;
    $a = $this->analiz;
    $turler = RA::turler();
    $girdi = 'padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem';
    $kucukGirdi = 'padding:.25rem .4rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem';
    $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.75rem 1rem';
    $th = 'text-align:left;padding:.5rem .6rem;font-size:.7rem;letter-spacing:.05em;text-transform:uppercase;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .2)';
    $td = 'padding:.5rem .6rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $sayi = fn ($n) => number_format((float) $n, 0, ',', '.');
@endphp

<x-filament-panels::page>
    <div style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:flex-end;justify-content:space-between">
        <div style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:flex-end">
            <div>
                <label style="display:block;font-size:.75rem;font-weight:600">İşyeri</label>
                <select wire:model.live="firmaId" style="{{ $girdi }};min-width:280px">
                    @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            @if ($this->degerlendirmeler->count() > 1)
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:600">Değerlendirme</label>
                    <select wire:model.live="rdId" style="{{ $girdi }}">
                        @foreach ($this->degerlendirmeler as $d)<option value="{{ $d->id }}">{{ $d->belge_no }} · {{ config('isg.risk_yontemleri.'.$d->yontem) }} · {{ $d->rapor_tarihi?->format('d.m.Y') ?? 'tarihsiz' }}</option>@endforeach
                    </select>
                </div>
            @endif
        </div>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
            @if ($firma)<x-filament::button size="sm" color="gray" icon="heroicon-o-presentation-chart-bar" tag="a" :href="\App\Filament\Pages\RiskMerkezi::getUrl(['firma' => $firma->id])">Risk Merkezi</x-filament::button>@endif
            @if ($rd)<x-filament::button size="sm" icon="heroicon-o-pencil-square" tag="a" :href="\App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource::getUrl('edit', ['record' => $rd])">Kayıtları aç</x-filament::button>@endif
        </div>
    </div>

    @if (! $firma)
        <x-filament::section><div style="text-align:center;padding:1rem;color:rgb(107 114 128)">Aktif işyeri yok.</div></x-filament::section>
    @else
        {{-- NACE kaynaklı tehlike kapsamı --}}
        <div style="{{ $kart }};display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem">
            <div><div style="font-size:.7rem;color:rgb(107 114 128)">NACE kodu</div><div style="font-weight:700">{{ $a['kapsam']['nace'] ?: '—' }}</div></div>
            <div><div style="font-size:.7rem;color:rgb(107 114 128)">Tehlike sınıfı</div><div style="font-weight:700">{{ $a['kapsam']['tehlike_sinifi'] }}</div></div>
            <div><div style="font-size:.7rem;color:rgb(107 114 128)">Çalışan</div><div style="font-weight:700">{{ $a['kapsam']['calisan'] ?: '—' }}</div></div>
            <div><div style="font-size:.7rem;color:rgb(107 114 128)">Risk kaydı</div><div style="font-weight:700">{{ $a['kapsam']['risk_kaydi'] }}</div></div>
        </div>

        <div style="border:1px solid rgb(217 119 6);background:rgb(217 119 6 / .07);border-radius:.6rem;padding:.55rem .8rem;font-size:.8rem">
            <strong style="color:rgb(217 119 6)">Bilgi</strong> — NACE'ye göre listelenen tehlike kaynakları başlangıç kapsamıdır; otomatik risk üretmez. Her kaynak sahada doğrulanmalı ve risk değerlendirmesine işlenmelidir. Dominans skoru = <strong>risk puanı × max(maruz kalan kişi sayısı, 1)</strong>; maruz kişi girilmeyen kayıtlar 1 kişi sayılır.
        </div>

        @php $baskin = $a['baskin'] ? $a['dagilim'][$a['baskin']] : null; @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.6rem">
            <div style="{{ $kart }}">
                <div style="font-size:.75rem;color:rgb(107 114 128)">Baskın tehlike türü</div>
                <div style="font-size:1.4rem;font-weight:800;color:{{ $baskin['renk'] ?? 'inherit' }}">{{ $baskin['ad'] ?? '—' }}</div>
                <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $baskin ? '%'.$baskin['pay'].' dominans payı' : 'Puanlı risk kaydı yok' }}</div>
            </div>
            <div style="{{ $kart }}">
                <div style="font-size:.75rem;color:rgb(107 114 128)">Aday tehlike kaynağı</div>
                <div style="font-size:1.4rem;font-weight:800">{{ count($a['kaynaklar']) }}</div>
                <div style="font-size:.72rem;color:rgb(107 114 128)">NACE listesinden · {{ collect($a['kaynaklar'])->where('eslesen', 0)->count() }} doğrulama bekliyor</div>
            </div>
            <div style="{{ $kart }}">
                <div style="font-size:.75rem;color:rgb(107 114 128)">Bildirilen maruz kişi</div>
                <div style="font-size:1.4rem;font-weight:800">{{ $sayi($a['maruz_toplam']) }}</div>
                <div style="font-size:.72rem;color:{{ $a['maruz_eksik'] ? 'rgb(217 119 6)' : 'rgb(107 114 128)' }}">{{ $a['maruz_eksik'] ? $a['maruz_eksik'].' kayıtta kişi sayısı eksik' : 'Tüm kayıtlarda girilmiş' }}</div>
            </div>
            <div style="{{ $kart }}">
                <div style="font-size:.75rem;color:rgb(107 114 128)">Risk değerlendirmesi</div>
                <div style="font-size:1.4rem;font-weight:800">{{ $a['kapsam']['risk_kaydi'] }}</div>
                <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $rd ? ($rd->belge_no.' · '.config('isg.risk_yontemleri.'.$rd->yontem)) : 'Değerlendirme yok' }}</div>
            </div>
        </div>

        {{-- Dominant risk görünümü --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:.8rem">
            <x-filament::section heading="Tehlike türleri dağılımı" description="Dominans skoruna göre ağırlıklı pay">
                @foreach ($a['dagilim'] as $d)
                    <div style="margin-bottom:.55rem">
                        <div style="display:flex;justify-content:space-between;font-size:.8rem"><span style="font-weight:600">{{ $d['ad'] }}</span><span>%{{ $d['pay'] }} · {{ $d['kayit'] }} kayıt</span></div>
                        <div style="height:.5rem;border-radius:9999px;background:rgb(107 114 128 / .15);overflow:hidden"><div style="height:100%;width:{{ $d['pay'] }}%;background:{{ $d['renk'] }}"></div></div>
                    </div>
                @endforeach
            </x-filament::section>

            <x-filament::section heading="En yüksek dominant riskler" description="Puan × maruz kişi sıralaması">
                @forelse ($a['en_yuksek'] as $i => $s)
                    <div style="display:flex;gap:.6rem;align-items:flex-start;padding:.35rem 0;border-bottom:1px solid rgb(107 114 128 / .12);font-size:.8rem">
                        <span style="font-weight:800;color:rgb(107 114 128);min-width:1.2rem">{{ $i + 1 }}</span>
                        <div style="flex:1">
                            <div style="font-weight:600">{{ \Illuminate\Support\Str::limit($s['madde']->tehlike, 90) }}</div>
                            <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $s['madde']->bolum ?: 'Bölüm yok' }} · <span style="color:{{ $turler[$s['tur']]['renk'] }}">{{ $turler[$s['tur']]['ad'] }}</span> · puan {{ $sayi($s['madde']->puan) }} × {{ max((int) $s['madde']->maruz_kisi, 1) }} kişi</div>
                        </div>
                        <span style="font-weight:800">{{ $sayi($s['dominans']) }}</span>
                    </div>
                @empty
                    <div style="font-size:.8rem;color:rgb(107 114 128)">Puanlı risk kaydı yok.</div>
                @endforelse
            </x-filament::section>
        </div>

        {{-- NACE kaynakları --}}
        <x-filament::section heading="NACE'ye göre olası tehlike kaynakları" :description="$a['kaynaklar'] ? 'Risk kayıtlarında karşılığı aranır; karşılığı olmayan kaynak sahada doğrulanmalıdır.' : null">
            @if (! $a['kaynaklar'])
                <div style="font-size:.8rem;color:rgb(107 114 128)">NACE kodu girilmemiş ya da bu NACE bölümü için kaynak listesi yok. Firma kaydında NACE kodunu kontrol edin.</div>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:.6rem">
                    @foreach ($a['kaynaklar'] as $k)
                        @php $ok = $k['eslesen'] > 0; @endphp
                        <div style="{{ $kart }};border-left:4px solid {{ $ok ? 'rgb(21 128 61)' : 'rgb(217 119 6)' }}">
                            <div style="font-weight:600;font-size:.85rem">{{ $k['baslik'] }}</div>
                            <div style="font-size:.72rem;margin-top:.2rem;color:{{ $ok ? 'rgb(21 128 61)' : 'rgb(217 119 6)' }}">{{ $ok ? 'Risk kaydında karşılığı var ('.$k['eslesen'].' kayıt)' : 'Saha doğrulaması bekliyor' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- Özet tablo --}}
        <x-filament::section heading="İşyeri risk değerlendirmesi özeti">
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <thead><tr><th style="{{ $th }}">Tehlike türü</th><th style="{{ $th }}">Risk kaydı</th><th style="{{ $th }}">Maruz kişi</th><th style="{{ $th }}">Dominans skoru</th><th style="{{ $th }}">Pay</th></tr></thead>
                    <tbody>
                        @foreach ($a['dagilim'] as $d)
                            <tr>
                                <td style="{{ $td }}"><span style="display:inline-block;width:.6rem;height:.6rem;border-radius:9999px;background:{{ $d['renk'] }};margin-right:.4rem"></span>{{ $d['ad'] }}</td>
                                <td style="{{ $td }}">{{ $d['kayit'] }}</td>
                                <td style="{{ $td }}">{{ $sayi($d['maruz']) }}</td>
                                <td style="{{ $td }}">{{ $sayi($d['skor']) }}</td>
                                <td style="{{ $td }}">%{{ $d['pay'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Tür ve maruz kişi girişi --}}
        @if ($rd && $a['satirlar']->isNotEmpty())
            <x-filament::section heading="Tehlike türü ve maruz kişi" description="Tür seçilmemiş kayıtlar metinden tahmin edilir (italik). Değişiklik anında kaydedilir.">
                <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;margin-bottom:.6rem">
                    <label style="font-size:.8rem;display:flex;gap:.3rem;align-items:center"><input type="checkbox" wire:model.live="sadeceEksik"> Yalnız türü tahmin edilen / kişi sayısı eksik olanlar</label>
                    @if ($a['tur_tahmin'])
                        <x-filament::button size="xs" color="gray" wire:click="tahminleriKaydet" wire:confirm="Tahmin edilen {{ $a['tur_tahmin'] }} türü maddelere kaydedilsin mi?">Tahminleri kaydet ({{ $a['tur_tahmin'] }})</x-filament::button>
                    @endif
                </div>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                        <thead><tr><th style="{{ $th }}">#</th><th style="{{ $th }}">Bölüm</th><th style="{{ $th }}">Tehlike</th><th style="{{ $th }}">Puan</th><th style="{{ $th }}">Tür</th><th style="{{ $th }}">Maruz kişi</th></tr></thead>
                        <tbody>
                            @foreach ($a['satirlar'] as $s)
                                @php $m = $s['madde']; @endphp
                                @continue($sadeceEksik && ! $s['tahmin'] && filled($m->maruz_kisi))
                                <tr wire:key="m-{{ $m->id }}">
                                    <td style="{{ $td }}">{{ $m->sira }}</td>
                                    <td style="{{ $td }}">{{ $m->bolum ?: '—' }}</td>
                                    <td style="{{ $td }}">{{ \Illuminate\Support\Str::limit($m->tehlike, 110) }}</td>
                                    <td style="{{ $td }}">{{ $m->puan ? $sayi($m->puan) : '—' }}</td>
                                    <td style="{{ $td }}">
                                        <select style="{{ $kucukGirdi }};{{ $s['tahmin'] ? 'font-style:italic;color:rgb(107 114 128)' : '' }}" wire:change="maddeGuncelle({{ $m->id }}, 'tehlike_turu', $event.target.value)">
                                            <option value="" @selected($s['tahmin'])>Tahmin: {{ $turler[$s['tur']]['ad'] }}</option>
                                            @foreach ($turler as $k => $t)<option value="{{ $k }}" @selected(! $s['tahmin'] && $s['tur'] === $k)>{{ $t['ad'] }}</option>@endforeach
                                        </select>
                                    </td>
                                    <td style="{{ $td }}"><input type="number" min="0" value="{{ $m->maruz_kisi }}" placeholder="—" style="{{ $kucukGirdi }};width:5.5rem" wire:change="maddeGuncelle({{ $m->id }}, 'maruz_kisi', $event.target.value)"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @elseif (! $rd)
            <x-filament::section><div style="text-align:center;padding:1rem;font-size:.85rem;color:rgb(107 114 128)">Bu işyeri için risk değerlendirmesi yok — analitik, risk kayıtları girildikçe oluşur.</div></x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
