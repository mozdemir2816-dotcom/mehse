@php
    $cfg = config('isg.e_recetem');
    $yesil = 'rgb(34 197 94)';
    $mavi = 'rgb(37 99 235)';
    $grad = 'linear-gradient(135deg, rgb(37 99 235), rgb(14 165 233))';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    {{-- HERO --}}
    <div style="{{ $kutu }};background:{{ $grad }};color:#fff;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.5rem;padding:1.5rem">
        <div style="font-size:2rem">💊</div>
        <div style="font-size:1.05rem;font-weight:800;max-width:26rem">Çalışanlarınız İşyeri Hekiminden Ücretsiz e-Reçete Alabilir</div>
        <p style="font-size:.82rem;opacity:.9;max-width:28rem">
            Periyodik muayene sırasında tespit edilen basit rahatsızlıklar için, çalışanınız
            aile hekimine gitmeden e-Reçete ile eczaneden ilacını temin edebilir.
        </p>
        <x-filament::button size="sm" color="white" icon="heroicon-o-information-circle" wire:click="isyeriHekimiHatirlat">
            İşyeri Hekimi Atamamı Kontrol Et
        </x-filament::button>
    </div>

    {{-- NEDİR --}}
    <div style="{{ $kutu }}">
        <div style="display:flex;align-items:center;gap:.5rem;font-weight:700">
            <x-filament::icon icon="heroicon-o-light-bulb" style="width:1.15rem;height:1.15rem;color:{{ $mavi }}"/>
            Nedir?
        </div>
        <p style="font-size:.85rem;color:rgb(75 85 99);margin-top:.5rem;text-align:justify">{{ $cfg['nedir'] }}</p>
    </div>

    {{-- KİMLER FAYDALANABİLİR --}}
    <div style="{{ $kutu }}">
        <div style="display:flex;align-items:center;gap:.5rem;font-weight:700">
            <x-filament::icon icon="heroicon-o-user-group" style="width:1.15rem;height:1.15rem;color:{{ $mavi }}"/>
            Kimler Faydalanabilir?
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem;margin-top:.75rem">
            @foreach ($cfg['kimler_faydalanabilir'] as $k)
                <div style="display:flex;gap:.6rem">
                    <x-filament::icon :icon="$k['ikon']" style="width:1.1rem;height:1.1rem;flex-shrink:0;color:{{ $mavi }}"/>
                    <div>
                        <div style="font-weight:600;font-size:.88rem">{{ $k['baslik'] }}</div>
                        <div style="font-size:.8rem;color:rgb(107 114 128)">{{ $k['aciklama'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- NASIL ÇALIŞIR --}}
    <div style="{{ $kutu }}">
        <div style="display:flex;align-items:center;gap:.5rem;font-weight:700">
            <x-filament::icon icon="heroicon-o-arrow-path" style="width:1.15rem;height:1.15rem;color:{{ $mavi }}"/>
            Nasıl Çalışır?
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem;margin-top:.75rem">
            @foreach ($cfg['nasil_calisir'] as $i => $adim)
                <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.5rem;padding:.7rem">
                    <div style="display:flex;align-items:center;gap:.4rem;font-weight:600;font-size:.88rem">
                        <span style="width:1.4rem;height:1.4rem;border-radius:9999px;background:{{ $mavi }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:.78rem">{{ $i + 1 }}</span>
                        {{ $adim['baslik'] }}
                    </div>
                    <div style="font-size:.78rem;color:rgb(107 114 128);margin-top:.35rem">{{ $adim['aciklama'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- KAPSAM DIŞI + SSS --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
        <div style="{{ $kutu }};background:rgb(107 114 128 / .06)">
            <div style="display:flex;align-items:center;gap:.5rem;font-weight:700">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" style="width:1.15rem;height:1.15rem;color:rgb(107 114 128)"/>
                mehse Kapsamında Değildir
            </div>
            <ul style="margin:.5rem 0 0;padding-left:1.1rem;font-size:.8rem;color:rgb(75 85 99);display:flex;flex-direction:column;gap:.4rem">
                @foreach ($cfg['kapsam_disi'] as $m) <li>{{ $m }}</li> @endforeach
            </ul>
        </div>
        <div style="{{ $kutu }}">
            <div style="display:flex;align-items:center;gap:.5rem;font-weight:700">
                <x-filament::icon icon="heroicon-o-question-mark-circle" style="width:1.15rem;height:1.15rem;color:{{ $yesil }}"/>
                Sık Sorulan Sorular
            </div>
            <div style="display:flex;flex-direction:column;gap:.6rem;margin-top:.6rem">
                @foreach ($cfg['sss'] as $s)
                    <div>
                        <div style="font-weight:600;font-size:.82rem">{{ $s['soru'] }}</div>
                        <div style="font-size:.78rem;color:rgb(107 114 128)">{{ $s['cevap'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
