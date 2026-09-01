@php
    $cfg = config('isg.isg_katip');
    $mor = 'rgb(139 92 246)';
    $yesil = 'rgb(34 197 94)';
    $grad = 'linear-gradient(135deg, rgb(139 92 246), rgb(99 102 241))';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    {{-- ÜST: gereksinimler + hero --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
        {{-- Teknik Gereksinimler --}}
        <div style="{{ $kutu }}">
            <div style="display:flex;align-items:center;gap:.5rem;font-weight:700">
                <x-filament::icon icon="heroicon-o-shield-check" style="width:1.15rem;height:1.15rem;color:{{ $mor }}"/>
                Teknik Gereksinimler
            </div>
            <div style="display:flex;flex-direction:column;gap:.75rem;margin-top:.75rem">
                @foreach ($cfg['gereksinimler'] as $g)
                    <div style="display:flex;gap:.6rem">
                        <x-filament::icon :icon="$g['ikon']" style="width:1.1rem;height:1.1rem;flex-shrink:0;color:{{ $mor }}"/>
                        <div>
                            <div style="font-weight:600;font-size:.88rem">{{ $g['baslik'] }}</div>
                            <div style="font-size:.8rem;color:rgb(107 114 128)">{{ $g['aciklama'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Hero: eklenti kurulum --}}
        <div style="{{ $kutu }};background:{{ $grad }};color:#fff;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.5rem">
            <div style="font-size:2rem">🤖</div>
            <div style="font-size:1.05rem;font-weight:800;max-width:22rem">Bot Özelliklerini Kullanmak İçin Eklentiyi Kurun</div>
            <p style="font-size:.82rem;opacity:.9;max-width:24rem">
                İSG-KATİP Bot henüz tarayıcınızda yüklü değil. Google Chrome Eklenti Mağazası’ndan tek tıkla yükleyebilirsiniz.
            </p>
            <div style="display:inline-flex;align-items:center;gap:.35rem;background:rgb(255 255 255 / .18);padding:.25rem .7rem;border-radius:9999px;font-size:.72rem">
                <span style="width:.5rem;height:.5rem;border-radius:9999px;background:{{ $eklentiBagli ? $yesil : '#f87171' }}"></span>
                {{ $eklentiBagli ? 'Eklenti bağlı' : 'Eklenti bağlı değil' }}
            </div>
        </div>
    </div>

    {{-- KURULUM REHBERİ --}}
    <div style="{{ $kutu }}">
        <div style="display:flex;align-items:center;gap:.5rem;font-weight:700">
            <x-filament::icon icon="heroicon-o-arrow-down-tray" style="width:1.15rem;height:1.15rem;color:{{ $mor }}"/>
            Kurulum Rehberi
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem;margin-top:.75rem">
            @foreach ($cfg['kurulum_adimlari'] as $i => $adim)
                <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.5rem;padding:.7rem">
                    <div style="display:flex;align-items:center;gap:.4rem;font-weight:600;font-size:.88rem">
                        <span style="width:1.4rem;height:1.4rem;border-radius:9999px;background:{{ $mor }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:.78rem">{{ $i + 1 }}</span>
                        {{ $adim['baslik'] }}
                    </div>
                    <div style="font-size:.78rem;color:rgb(107 114 128);margin-top:.35rem">{{ $adim['aciklama'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- KONTROL PANELİ — bot kataloğu --}}
    <div style="{{ $kutu }}">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
            <div>
                <div style="display:flex;align-items:center;gap:.5rem;font-weight:700">
                    <x-filament::icon icon="heroicon-o-squares-2x2" style="width:1.15rem;height:1.15rem;color:{{ $mor }}"/>
                    Kontrol Paneli
                </div>
                <div style="font-size:.8rem;color:rgb(107 114 128)">İSG-KATİP otomasyon araçlarını seçerek çalıştırın.</div>
            </div>
            <x-filament::badge color="gray">Bugün {{ $cfg['gunluk_hak'] }}/{{ $cfg['gunluk_hak'] }} bot hakkınız kaldı</x-filament::badge>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.75rem;margin-top:1rem">
            @foreach ($cfg['botlar'] as $bot)
                <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.6rem;padding:.85rem;display:flex;flex-direction:column;gap:.5rem">
                    <div style="display:flex;gap:.35rem;flex-wrap:wrap">
                        @if ($bot['osgb']) <x-filament::badge size="sm" color="gray">OSGB</x-filament::badge> @endif
                        <x-filament::badge size="sm" color="warning">Eklenti gerekli</x-filament::badge>
                        @if ($bot['yeni']) <x-filament::badge size="sm" color="success">YENİ</x-filament::badge> @endif
                    </div>
                    <div style="font-weight:700;font-size:.9rem">{{ $bot['ad'] }}</div>
                    <div style="font-size:.78rem;color:rgb(107 114 128);flex:1">{{ $bot['aciklama'] }}</div>
                    <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-right" icon-position="after"
                        wire:click="botKur(@js($bot['ad']))">
                        Kurmak için tıklayın
                    </x-filament::button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- NEDEN + GÜVENLİK --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
        <div style="{{ $kutu }}">
            <div style="display:flex;align-items:center;gap:.5rem;font-weight:700">
                <x-filament::icon icon="heroicon-o-check-badge" style="width:1.15rem;height:1.15rem;color:{{ $yesil }}"/>
                Neden Kullanmalıyım?
            </div>
            <ul style="margin:.5rem 0 0;padding-left:1.1rem;font-size:.82rem;display:flex;flex-direction:column;gap:.35rem">
                @foreach ($cfg['neden'] as $m) <li>{{ $m }}</li> @endforeach
            </ul>
        </div>
        <div style="{{ $kutu }}">
            <div style="display:flex;align-items:center;gap:.5rem;font-weight:700">
                <x-filament::icon icon="heroicon-o-lock-closed" style="width:1.15rem;height:1.15rem;color:{{ $mor }}"/>
                Güvende miyim?
            </div>
            <ul style="margin:.5rem 0 0;padding-left:1.1rem;font-size:.82rem;display:flex;flex-direction:column;gap:.35rem">
                @foreach ($cfg['guvenlik'] as $m) <li>{{ $m }}</li> @endforeach
            </ul>
        </div>
    </div>
</x-filament-panels::page>
