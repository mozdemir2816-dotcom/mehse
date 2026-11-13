<x-filament-widgets::widget class="mehse-mobilde-gizle" id="gorev-panosu">
    @php
        $o = $this->ozet;
        $sure = $this->sure;
        $gorevler = $this->gorevler;
        $kullanici = \Filament\Facades\Filament::auth()->user();
        $yaklasanGun = \App\Support\GorevDurumu::yaklasanGun();
        $sutunlar = [
            // Pano renkleri (ISO 7010): kırmızı #B3122E · sarı zemin + koyu sarı metin · mavi · yeşil #00704A
            'gecikmis' => ['Günü geçenler', 'heroicon-o-exclamation-triangle', '#B3122E', 'rgb(179 18 46 / .05)'],
            'yaklasan' => ['Yaklaşanlar', 'heroicon-o-calendar-days', '#7A5600', 'rgb(242 183 5 / .09)'],
            'yapilmayan' => ['Yapılmayanlar', 'heroicon-o-clock', '#0057A8', 'rgb(0 87 168 / .04)'],
            'yapilan' => ['Yapılanlar', 'heroicon-o-check-circle', '#00704A', 'rgb(0 112 74 / .05)'],
        ];
        $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.75rem 1rem';
        $saatDk = fn (int $dk) => \App\Support\GorevDurumu::saatDk($dk);
    @endphp

    {{-- 02.11.2026: "Görev durumu" başlık bandı kaldırıldı; kullanıcı / unvan satırı ve İSG-KATİP kapasite kartı
         ana sayfa kokpitine (HosGeldinWidget) taşındı. Pano sekmeli: varsayılan yalnız günü geçen + yaklaşan. --}}
    <x-filament::section>

        {{-- Portföy şeridi (Pano, 13.11.2026): firma başına çubuk; tıklayınca görevler o firmaya süzülür. --}}
        @php $serit = $this->portfoySeridi; $sayac = collect($serit)->countBy('durum'); @endphp
        @if ($serit)
            <div style="{{ $kart }};margin-bottom:1rem">
                <div style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:.4rem 1rem;margin-bottom:.6rem">
                    <div style="font-weight:700;font-size:.9rem">Portföy şeridi</div>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem 1rem;font-size:.8rem;color:var(--text-secondary)">
                        <span style="display:inline-flex;align-items:center;gap:.35rem"><x-mehse-durum-isareti durum="gecikti" />{{ $sayac['gecikti'] ?? 0 }} firmada geciken iş</span>
                        <span style="display:inline-flex;align-items:center;gap:.35rem"><x-mehse-durum-isareti durum="yaklasiyor" />{{ $sayac['yaklasiyor'] ?? 0 }} firmada yaklaşan iş</span>
                        <span style="display:inline-flex;align-items:center;gap:.35rem"><x-mehse-durum-isareti durum="tamam" />{{ $sayac['tamam'] ?? 0 }} firmada süreler güncel</span>
                    </div>
                </div>
                <div class="mehse-serit" role="group" aria-label="Firmalar, duruma göre">
                    @foreach ($serit as $f)
                        <button type="button" wire:click="firmaSec({{ $f['id'] }})"
                            @class(['mehse-serit-cubuk', 'mehse-serit-'.$f['durum'], 'fi-active' => $arama === $f['unvan']])
                            title="{{ $f['ad'] }}" aria-label="{{ $f['ad'] }}: {{ ['gecikti' => 'geciken iş var', 'yaklasiyor' => 'yaklaşan iş var', 'tamam' => 'süreler güncel'][$f['durum']] }}"
                            aria-pressed="{{ $arama === $f['unvan'] ? 'true' : 'false' }}"></button>
                    @endforeach
                </div>
                <div style="font-size:.78rem;color:var(--text-secondary);margin-top:.45rem">
                    Her çubuk bir firma. Tıklayınca aşağıdaki görevler o firmaya süzülür; tekrar tıklayınca tüm firmalar döner.
                </div>
            </div>
        @endif

        {{-- Hızlı çalışma akışları --}}
        <div style="{{ $kart }};margin-bottom:1rem;display:flex;flex-wrap:wrap;align-items:center;gap:.5rem .9rem">
            <div style="font-weight:700;font-size:.85rem">Hızlı çalışma akışları</div>
            <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                @foreach ($this->hizliAkislar() as $h)
                    <a href="{{ $h['url'] }}" class="mehse-akis-hap">
                        <x-filament::icon :icon="$h['ikon']" style="width:.95rem;height:.95rem" />{{ $h['ad'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Tek satır: arama (solda) · sayaç rozetleri · Yenile / Durum raporu (sağ uçta) --}}
        <div style="display:flex;align-items:center;gap:.6rem .9rem;flex-wrap:wrap;margin-bottom:.8rem">
            <input type="search" wire:model.live.debounce.400ms="arama" placeholder="Firma, görev veya modül ara…" aria-label="Görev ara"
                   style="flex:1 1 220px;max-width:300px;padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem">
            <div style="display:flex;flex-wrap:wrap;gap:.35rem;flex:1 1 auto">
                @foreach ([
                    ['Günü geçen', $o['gecikmis'], 'rgb(185 28 28)'],
                    ['Yaklaşan', $o['yaklasan'], 'rgb(180 83 9)'],
                    ['Yapılmayan', $o['yapilmayan'], 'rgb(71 85 105)'],
                    ['Yapılan', $o['yapilan'], 'rgb(21 128 61)'],
                    ['Tamamlanma', '%'.$o['tamamlanma'], 'rgb(15 118 110)'],
                ] as [$ad, $sayi, $renk])
                    <span @if ($ad === 'Yaklaşan') title="Termini {{ $yaklasanGun }} gün içinde" @endif
                          style="display:inline-flex;align-items:center;gap:.35rem;font-size:.75rem;padding:.25rem .65rem;border-radius:9999px;border:1px solid color-mix(in srgb, {{ $renk }} 35%, transparent);background:color-mix(in srgb, {{ $renk }} 8%, transparent);color:{{ $renk }}">
                        {{ $ad }}: <strong style="font-size:.82rem">{{ $sayi }}</strong>
                    </span>
                @endforeach
            </div>
            <div style="display:flex;gap:.5rem;margin-left:auto">
                <x-filament::button size="sm" color="gray" outlined icon="heroicon-o-arrow-path" wire:click="yenile">Yenile</x-filament::button>
                <x-filament::button size="sm" color="gray" outlined icon="heroicon-o-arrow-down-tray" wire:click="durumRaporu">İndir / Durum raporu</x-filament::button>
            </div>
        </div>

        {{-- 02.11.2026: "N süresi geçen faaliyet — öncelikli" açıklama kutusu kaldırıldı; sekmeler doğrudan gelir. --}}

        {{-- Pano sekmeleri --}}
        @php
            $sekmeler = [
                'kritik' => ['Öncelikli', $o['gecikmis'] + $o['yaklasan'], 'rgb(220 38 38)'],
                'yapilmayan' => ['Yapılmayanlar', $o['yapilmayan'], 'rgb(71 85 105)'],
                'yapilan' => ['Yapılanlar', $o['yapilan'], 'rgb(21 128 61)'],
            ];
            $gorunen = $pano === 'kritik' ? ['gecikmis', 'yaklasan'] : [$pano];
            $tekSutun = count($gorunen) === 1;
        @endphp
        <div role="tablist" style="display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:.7rem;border-bottom:1px solid rgb(107 114 128 / .2)">
            @foreach ($sekmeler as $anahtar => [$ad, $sayi, $renk])
                @php $aktif = $pano === $anahtar; @endphp
                <button type="button" role="tab" aria-selected="{{ $aktif ? 'true' : 'false' }}" wire:click="panoSec('{{ $anahtar }}')"
                        style="display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .8rem;margin-bottom:-1px;border:none;border-bottom:2px solid {{ $aktif ? $renk : 'transparent' }};background:transparent;cursor:pointer;font-size:.82rem;font-weight:{{ $aktif ? 700 : 600 }};color:{{ $aktif ? $renk : 'rgb(100 116 139)' }}">
                    {{ $ad }}
                    <span style="font-size:.75rem;font-weight:700;padding:.02rem .45rem;border-radius:9999px;background:color-mix(in srgb, {{ $renk }} 12%, transparent);color:{{ $renk }}">{{ $sayi }}</span>
                </button>
            @endforeach
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.75rem">
            @foreach (array_intersect_key($sutunlar, array_flip($gorunen)) as $durum => [$baslik, $ikon, $renk, $zemin])
                @php $liste = $gorevler->where('durum', $durum); @endphp
                <div style="border:1px solid color-mix(in srgb, {{ $renk }} 28%, transparent);border-radius:.75rem;background:{{ $zemin }};padding:.6rem;display:flex;flex-direction:column;max-height:{{ $tekSutun ? '560px' : '420px' }}">
                    <div style="display:flex;justify-content:space-between;align-items:center;font-weight:700;color:{{ $renk }};font-size:.88rem;padding:.1rem .2rem .5rem">
                        <span style="display:flex;align-items:center;gap:.4rem"><x-mehse-durum-isareti :durum="['gecikmis' => 'gecikti', 'yaklasan' => 'yaklasiyor', 'yapilmayan' => 'zorunlu', 'yapilan' => 'tamam'][$durum]" /> {{ $baslik }}</span>
                        <span style="font-size:.75rem;font-weight:700;padding:.05rem .5rem;border-radius:9999px;background:color-mix(in srgb, {{ $renk }} 14%, transparent)">{{ $liste->count() }}</span>
                    </div>
                    <div style="overflow-y:auto;min-height:0;gap:.5rem;{{ $tekSutun ? 'display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));align-items:start' : 'display:flex;flex-direction:column' }}">
                        @forelse ($liste->take(60) as $g)
                            <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.6rem .7rem;box-shadow:0 1px 2px rgb(15 23 42 / .05)">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.4rem">
                                    <div style="font-weight:700;font-size:.8rem;color:{{ $renk }}">{{ $g['baslik'] }}</div>
                                    @if ($g['termin'] && $durum === 'gecikmis')
                                        <span style="flex-shrink:0;font-size:.68rem;font-weight:700;padding:.1rem .45rem;border-radius:9999px;background:rgb(225 29 72 / .1);color:rgb(190 18 60)">{{ abs($g['kalan_gun']) }} gün gecikmiş</span>
                                    @elseif ($g['termin'] && $durum === 'yaklasan')
                                        <span style="flex-shrink:0;font-size:.68rem;font-weight:700;padding:.1rem .45rem;border-radius:9999px;background:rgb(217 119 6 / .12);color:rgb(180 83 9)">{{ $g['kalan_gun'] === 0 ? 'bugün' : $g['kalan_gun'].' gün kaldı' }}</span>
                                    @endif
                                </div>
                                <div style="font-size:.78rem;font-weight:600;margin-top:.2rem">{{ $g['firma'] }}</div>
                                @if ($g['termin'])
                                    <div class="mehse-soluk">Termin: {{ $g['termin']->format('d.m.Y') }}</div>
                                @endif
                                <div style="font-size:.75rem;margin-top:.25rem;line-height:1.4">{{ $g['aciklama'] }}</div>
                                @if ($g['dayanak'])<div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.15rem">{{ $g['dayanak'] }}</div>@endif
                                @if ($g['url'])
                                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-top:.45rem">
                                        <span style="font-size:.68rem;color:rgb(107 114 128)">{{ $g['modul'] }}</span>
                                        <a href="{{ $g['url'] }}" style="display:inline-flex;align-items:center;font-size:.75rem;font-weight:600;padding:.2rem .65rem;border-radius:9999px;border:1px solid color-mix(in srgb, {{ $renk }} 40%, transparent);color:{{ $renk }};text-decoration:none">İncele →</a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div style="font-size:.78rem;color:rgb(107 114 128);padding:.3rem">{{ $durum === 'gecikmis' ? 'Günü geçen görev yok.' : ($durum === 'yaklasan' ? 'Yaklaşan görev yok.' : 'Görev yok.') }}</div>
                        @endforelse
                        @if ($liste->count() > 60)
                            <div style="font-size:.75rem;color:rgb(107 114 128);padding:.3rem">… ve {{ $liste->count() - 60 }} görev daha — firma adıyla arayın ya da durum raporunu indirin.</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
