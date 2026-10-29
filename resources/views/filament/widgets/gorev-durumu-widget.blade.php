<x-filament-widgets::widget class="mehse-mobilde-gizle">
    @php
        $o = $this->ozet;
        $sure = $this->sure;
        $gorevler = $this->gorevler;
        $kullanici = \Filament\Facades\Filament::auth()->user();
        $yaklasanGun = \App\Support\GorevDurumu::yaklasanGun();
        $sutunlar = [
            'gecikmis' => ['Günü geçenler', 'heroicon-o-exclamation-triangle', 'rgb(220 38 38)', 'rgb(220 38 38 / .06)'],
            'yaklasan' => ['Yaklaşanlar', 'heroicon-o-calendar-days', 'rgb(217 119 6)', 'rgb(217 119 6 / .07)'],
            'yapilmayan' => ['Yapılmayanlar', 'heroicon-o-clock', 'rgb(71 85 105)', 'rgb(100 116 139 / .06)'],
            'yapilan' => ['Yapılanlar', 'heroicon-o-check-circle', 'rgb(21 128 61)', 'rgb(21 128 61 / .06)'],
        ];
        $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.75rem 1rem';
        $saatDk = fn (int $dk) => \App\Support\GorevDurumu::saatDk($dk);
    @endphp

    <x-filament::section icon="heroicon-o-shield-check" icon-color="primary">
        <x-slot name="heading">Görev durumu</x-slot>
        <x-slot name="description">
            <span style="display:block;font-weight:600;font-size:.92em;line-height:1.35">
                {{ collect([$kullanici->name, config('isg.uzman_unvanlari.'.$kullanici->unvan), $sure['isyeri'].' işyeri'])->filter()->implode(' · ') }}
            </span>
            <span style="display:block;font-size:.8em;margin-top:.15rem">Faaliyetlerin durumu tek bakışta; kartta “İncele” ile ilgili kaydı açın.</span>
        </x-slot>
        {{-- Aylık görevlendirme süresi: tek parça kapasite kartı (İSG-KATİP) --}}
        @php
            $toplamDk = (int) config('isg.ana_sayfa.aylik_kapasite_saat') * 60;
            $asim = $sure['yuzde'] > 100;
            $doluRenk = $asim ? 'rgb(225 29 72)' : 'rgb(2 132 199)';
            $dolu = min(100, max(0, $sure['yuzde']));
        @endphp
        <div style="{{ $kart }};margin-bottom:1rem">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap">
                <div style="font-weight:700;font-size:.9rem">Aylık görevlendirme süresi · {{ now()->format('Y-m') }}</div>
                <span style="font-size:.72rem;font-weight:700;padding:.15rem .6rem;border-radius:9999px;background:color-mix(in srgb, {{ $doluRenk }} 12%, transparent);color:{{ $doluRenk }}">%{{ $sure['yuzde'] }} kapasite</span>
            </div>
            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-top:.5rem">
                <div>
                    <span style="font-size:1.6rem;font-weight:800;color:{{ $doluRenk }}">{{ $saatDk($sure['kullanilan_dk']) }}</span>
                    <span style="font-size:.95rem;color:rgb(107 114 128)"> / {{ $saatDk($toplamDk) }}</span>
                </div>
                <div style="font-size:.85rem;font-weight:600;color:{{ $asim ? 'rgb(225 29 72)' : 'rgb(5 150 105)' }}">
                    {{ $asim ? 'Aşım' : 'Kalan' }}: {{ $saatDk(abs($sure['kalan_dk'])) }}
                </div>
            </div>
            <div style="height:8px;border-radius:9999px;background:rgb(16 185 129 / .18);margin-top:.5rem;overflow:hidden">
                <div style="height:8px;border-radius:9999px;width:{{ $dolu }}%;background:{{ $doluRenk }}"></div>
            </div>
            <div style="font-size:.72rem;color:rgb(107 114 128);margin-top:.5rem">
                Kullanılan süre = aktif işyerlerinde çalışan sayısı × aylık süre (az tehlikeli {{ config('isg.igu_aylik_dk.az_tehlikeli') }}, tehlikeli {{ config('isg.igu_aylik_dk.tehlikeli') }}, çok tehlikeli {{ config('isg.igu_aylik_dk.cok_tehlikeli') }} dk).
                Kalan süre = {{ config('isg.ana_sayfa.aylik_kapasite_saat') }} saatlik normal aylık kapasite − kullanılan süre.
            </div>
            @if ($sure['sinif_uygunsuz'])
                <div style="font-size:.78rem;color:rgb(185 28 28);margin-top:.4rem">
                    ⚠ Belge sınıfınızın görev alamayacağı tehlike sınıfında işyeri: {{ implode(', ', $sure['sinif_uygunsuz']) }}
                </div>
            @endif
        </div>

        {{-- Hızlı çalışma akışları --}}
        <div style="{{ $kart }};margin-bottom:1rem">
            <div style="font-weight:700;font-size:.9rem;margin-bottom:.5rem">Hızlı çalışma akışları</div>
            <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                @foreach ($this->hizliAkislar() as $h)
                    <a href="{{ $h['url'] }}" style="font-size:.8rem;font-weight:600;padding:.4rem .75rem;border-radius:.5rem;background:rgb(124 58 237 / .08);color:rgb(91 33 182);text-decoration:none">{{ $h['ad'] }}</a>
                @endforeach
            </div>
        </div>

        {{-- Arama (solda) + Yenile / Durum raporu (sağa yaslı) --}}
        <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:.8rem">
            <div style="flex:1 1 260px;max-width:420px">
                <label style="font-size:.78rem;font-weight:600">Görev ara</label>
                <input type="search" wire:model.live.debounce.400ms="arama" placeholder="Firma, görev, açıklama veya modül ara…"
                       style="display:block;width:100%;margin-top:.25rem;padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem">
            </div>
            <div style="display:flex;gap:.5rem">
                <x-filament::button size="sm" color="gray" outlined icon="heroicon-o-arrow-path" wire:click="yenile">Yenile</x-filament::button>
                <x-filament::button size="sm" color="gray" outlined icon="heroicon-o-arrow-down-tray" wire:click="durumRaporu">İndir / Durum raporu</x-filament::button>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.6rem;margin-bottom:1rem">
            @foreach ([
                ['Günü geçen', $o['gecikmis'], 'rgb(220 38 38)'],
                ['Yaklaşan ('.$yaklasanGun.' gün)', $o['yaklasan'], 'rgb(217 119 6)'],
                ['Yapılmayan', $o['yapilmayan'], 'rgb(71 85 105)'],
                ['Yapılan', $o['yapilan'], 'rgb(21 128 61)'],
                ['Tamamlanma', '%'.$o['tamamlanma'], 'rgb(13 148 136)'],
            ] as [$ad, $sayi, $renk])
                <div style="border:1px solid rgb(107 114 128 / .2);border-top:3px solid {{ $renk }};border-radius:.6rem;padding:.55rem .8rem">
                    <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $ad }}</div>
                    <div style="font-size:1.4rem;font-weight:800;color:{{ $renk }}">{{ $sayi }}</div>
                </div>
            @endforeach
        </div>

        @if ($o['gecikmis'] > 0)
            <div style="border:1px solid rgb(220 38 38 / .35);background:rgb(220 38 38 / .06);border-radius:.6rem;padding:.6rem .9rem;margin-bottom:1rem;font-size:.82rem">
                <strong>{{ $o['gecikmis'] }} süresi geçen faaliyet — öncelikli.</strong>
                <span style="color:rgb(107 114 128)">Karttaki “İncele” ile ilgili sayfada kayıt oluşturun veya tamamlayın. Durum raporunu indirip işverene / OSGB'ye iletebilirsiniz.</span>
            </div>
        @endif

        {{-- Dört sütunlu pano --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.75rem">
            @foreach ($sutunlar as $durum => [$baslik, $ikon, $renk, $zemin])
                @php $liste = $gorevler->where('durum', $durum); @endphp
                <div style="border:1px solid color-mix(in srgb, {{ $renk }} 28%, transparent);border-radius:.75rem;background:{{ $zemin }};padding:.6rem;display:flex;flex-direction:column;max-height:520px">
                    <div style="display:flex;justify-content:space-between;align-items:center;font-weight:700;color:{{ $renk }};font-size:.88rem;padding:.1rem .2rem .5rem">
                        <span style="display:flex;align-items:center;gap:.3rem"><x-filament::icon :icon="$ikon" style="width:1rem;height:1rem" /> {{ $baslik }}</span>
                        <span style="font-size:.72rem;font-weight:700;padding:.05rem .5rem;border-radius:9999px;background:color-mix(in srgb, {{ $renk }} 14%, transparent)">{{ $liste->count() }}</span>
                    </div>
                    <div style="overflow-y:auto;display:flex;flex-direction:column;gap:.5rem;min-height:0">
                        @forelse ($liste->take(60) as $g)
                            <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.6rem .7rem;box-shadow:0 1px 2px rgb(15 23 42 / .05)">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.4rem">
                                    <div style="font-weight:700;font-size:.8rem;color:{{ $renk }}">{{ $g['baslik'] }}</div>
                                    @if ($g['termin'] && $durum === 'gecikmis')
                                        <span style="flex-shrink:0;font-size:.64rem;font-weight:700;padding:.1rem .45rem;border-radius:9999px;background:rgb(225 29 72 / .1);color:rgb(190 18 60)">{{ abs($g['kalan_gun']) }} gün gecikmiş</span>
                                    @elseif ($g['termin'] && $durum === 'yaklasan')
                                        <span style="flex-shrink:0;font-size:.64rem;font-weight:700;padding:.1rem .45rem;border-radius:9999px;background:rgb(217 119 6 / .12);color:rgb(180 83 9)">{{ $g['kalan_gun'] === 0 ? 'bugün' : $g['kalan_gun'].' gün kaldı' }}</span>
                                    @endif
                                </div>
                                <div style="font-size:.78rem;font-weight:600;margin-top:.2rem">{{ $g['firma'] }}</div>
                                @if ($g['termin'])
                                    <div style="font-size:.7rem;color:rgb(107 114 128)">Termin: {{ $g['termin']->format('d.m.Y') }}</div>
                                @endif
                                <div style="font-size:.74rem;margin-top:.25rem;line-height:1.4">{{ $g['aciklama'] }}</div>
                                @if ($g['dayanak'])<div style="font-size:.68rem;color:rgb(107 114 128);margin-top:.15rem">{{ $g['dayanak'] }}</div>@endif
                                @if ($g['url'])
                                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-top:.45rem">
                                        <span style="font-size:.66rem;color:rgb(107 114 128)">{{ $g['modul'] }}</span>
                                        <a href="{{ $g['url'] }}" style="display:inline-flex;align-items:center;font-size:.72rem;font-weight:600;padding:.2rem .65rem;border-radius:9999px;border:1px solid color-mix(in srgb, {{ $renk }} 40%, transparent);color:{{ $renk }};text-decoration:none">İncele →</a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div style="font-size:.78rem;color:rgb(107 114 128);padding:.3rem">Görev yok.</div>
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
