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
            <span style="display:block;font-size:.8em;margin-top:.15rem">Faaliyetlerin durumu tek bakışta; kartta “İşleme git” ile kayıt açın.</span>
        </x-slot>
        {{-- Yenile / Durum raporu: kartın altında tam genişlik çubuk (telefonda başlığı sıkıştırmasın) --}}
        <x-slot name="footer">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
                <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="yenile" style="width:100%">Yenile</x-filament::button>
                <x-filament::button icon="heroicon-o-arrow-down-tray" wire:click="durumRaporu" style="width:100%">Durum raporu</x-filament::button>
            </div>
        </x-slot>

        {{-- Aylık görevlendirme süresi --}}
        <div style="{{ $kart }};margin-bottom:1rem">
            <div style="font-weight:700;font-size:.9rem">Aylık görevlendirme süresi · {{ now()->format('Y-m') }}</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem;margin-top:.6rem">
                @foreach ([
                    ['Kullanılan süre', $sure['kullanilan_dk'], min(100, $sure['yuzde']), $sure['yuzde'] > 100 ? 'rgb(220 38 38)' : 'rgb(124 58 237)'],
                    ['Kalan süre', $sure['kalan_dk'], max(0, 100 - $sure['yuzde']), 'rgb(21 128 61)'],
                ] as [$ad, $dk, $oran, $renk])
                    <div style="border:1px solid {{ $renk }};border-left-width:4px;border-radius:.6rem;padding:.6rem .8rem">
                        <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $ad }}</div>
                        <div style="font-size:1.35rem;font-weight:800;color:{{ $renk }}">{{ $saatDk($dk) }}</div>
                        <div style="font-size:.72rem;font-weight:700;color:{{ $renk }}">%{{ $oran }} kapasite</div>
                        <div style="height:5px;border-radius:9px;background:rgb(107 114 128 / .15);margin-top:.35rem"><div style="height:5px;border-radius:9px;width:{{ $oran }}%;background:{{ $renk }}"></div></div>
                    </div>
                @endforeach
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

        {{-- Arama + sayaçlar --}}
        <label style="font-size:.78rem;font-weight:600">Görev ara</label>
        <input type="search" wire:model.live.debounce.400ms="arama" placeholder="Firma, görev, açıklama veya modül ara…"
               style="display:block;width:100%;max-width:420px;margin:.25rem 0 .8rem;padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem">

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
                <span style="color:rgb(107 114 128)">Karttaki “İşleme git” ile ilgili sayfada kayıt oluşturun veya tamamlayın. Durum raporunu indirip işverene / OSGB'ye iletebilirsiniz.</span>
            </div>
        @endif

        {{-- Dört sütunlu pano --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.75rem">
            @foreach ($sutunlar as $durum => [$baslik, $ikon, $renk, $zemin])
                @php $liste = $gorevler->where('durum', $durum); @endphp
                <div style="border:1px solid {{ $renk }};border-radius:.75rem;background:{{ $zemin }};padding:.6rem;display:flex;flex-direction:column;max-height:520px">
                    <div style="display:flex;justify-content:space-between;align-items:center;font-weight:700;color:{{ $renk }};font-size:.88rem;padding:.1rem .2rem .5rem">
                        <span style="display:flex;align-items:center;gap:.3rem"><x-filament::icon :icon="$ikon" style="width:1rem;height:1rem" /> {{ $baslik }}</span>
                        <span style="font-size:.75rem">{{ $liste->count() }}</span>
                    </div>
                    <div style="overflow-y:auto;display:flex;flex-direction:column;gap:.5rem;min-height:0">
                        @forelse ($liste->take(60) as $g)
                            <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.6rem;padding:.55rem .65rem">
                                <div style="font-weight:700;font-size:.82rem;color:{{ $renk }}">{{ $g['baslik'] }}</div>
                                <div style="font-size:.74rem;font-weight:600;margin-top:.1rem">{{ $g['firma'] }}</div>
                                @if ($g['termin'])
                                    <div style="font-size:.72rem;color:rgb(107 114 128)">
                                        Termin: {{ $g['termin']->format('d.m.Y') }} ·
                                        {{ $g['kalan_gun'] < 0 ? abs($g['kalan_gun']).' gün gecikmiş' : ($g['kalan_gun'] === 0 ? 'bugün' : $g['kalan_gun'].' gün kaldı') }}
                                    </div>
                                @endif
                                <div style="font-size:.74rem;margin-top:.25rem;line-height:1.4">{{ $g['aciklama'] }}</div>
                                @if ($g['dayanak'])<div style="font-size:.68rem;color:rgb(107 114 128);margin-top:.15rem">{{ $g['dayanak'] }}</div>@endif
                                @if ($g['url'] && $durum !== 'yapilan')
                                    <a href="{{ $g['url'] }}" style="display:inline-block;margin-top:.4rem;font-size:.72rem;font-weight:700;padding:.3rem .6rem;border-radius:.4rem;background:{{ $renk }};color:#fff;text-decoration:none">İşleme git → {{ $g['modul'] }}</a>
                                @elseif ($g['url'])
                                    <a href="{{ $g['url'] }}" style="display:inline-block;margin-top:.3rem;font-size:.72rem;color:{{ $renk }}">Kaydı aç →</a>
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
