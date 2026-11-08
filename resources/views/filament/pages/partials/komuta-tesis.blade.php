{{-- Operasyon skoru + "Bugün neye müdahale etmeliyim?" (Tesis Uygunluğu) — Concerns\TesisUygunlukVerisi --}}
@php
    $s = $this->sonuc;
    $firma = $this->firma;
    $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.75rem 1rem';
    $renk = ['kritik' => 'rgb(220 38 38)', 'uyari' => 'rgb(217 119 6)', 'bilgi' => 'rgb(37 99 235)'];
    $skorRenk = fn (int $skor) => $skor >= 90 ? 'rgb(21 128 61)' : ($skor >= 75 ? 'rgb(13 148 136)' : ($skor >= 50 ? 'rgb(217 119 6)' : 'rgb(220 38 38)'));
@endphp

<p style="font-size:.82rem;color:rgb(107 114 128);line-height:1.5;margin:0">
    Bu bölüm kayıt değiştirmez; mevcut saha modüllerinin durumunu tek bakışta toplar ve “bugün neye müdahale etmeliyim?” önceliği ile
    açıklanabilir 0–100 operasyon skoru üretir. Sağlık / klinik veri puana dahil edilmez.
</p>

@if (! $s)
    <x-filament::section>Aktif işyeri bulunamadı.</x-filament::section>
@else
    <div style="{{ $kart }};display:flex;flex-wrap:wrap;justify-content:space-between;gap:1rem;align-items:center">
        <div>
            <div style="font-size:.75rem;color:rgb(107 114 128)">İşyeri İSG operasyon skoru</div>
            <div style="font-size:2.2rem;font-weight:800;color:{{ $skorRenk($s['skor']) }}">{{ $s['skor'] }}<span style="font-size:1rem;color:rgb(107 114 128)">/100</span></div>
            <div style="font-size:.75rem;color:rgb(107 114 128)">Taşeron + PTW + periyodik kontrol + saha denetimi + birleşik DÖF / aksiyon + profesyonel kapasite</div>
        </div>
        <div style="text-align:right">
            <span style="display:inline-block;font-weight:800;font-size:.95rem;padding:.2rem .8rem;border-radius:9999px;color:{{ $skorRenk($s['skor']) }};background:color-mix(in srgb, {{ $skorRenk($s['skor']) }} 12%, transparent)">{{ $s['seviye'] }}</span>
            <div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.3rem">Açıklanabilir · salt okunur</div>
        </div>
    </div>

    @php $n = $s['sayilar']; @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.5rem">
        @foreach ([
            ['Toplam dikkat', $n['toplam_dikkat'], 'uyari'], ['Aktif taşeron', $n['aktif_taseron'], null], ['Taşeron belge eksik', $n['taseron_belge'], 'uyari'],
            ['PTW dikkat', $n['ptw'], 'kritik'], ['Periyodik gecikmiş', $n['periyodik'], 'kritik'], ['Denetim aksiyonu', $n['denetim'], 'uyari'],
            ['Açık birleşik aksiyon', $n['acik_aksiyon'], null], ['Gecikmiş aksiyon', $n['gecikmis_aksiyon'], 'kritik'],
            ['Kapasite kritik', $n['kapasite_kritik'], 'kritik'], ['Kapasite uyarı', $n['kapasite_uyari'], 'uyari'], ['Kapasite aşımı', $n['kapasite_asimi'], 'uyari'],
        ] as [$ad, $deger, $tur])
            <div style="{{ $kart }};padding:.55rem .75rem">
                <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $ad }}</div>
                <div style="font-size:1.3rem;font-weight:800;color:{{ $deger > 0 && $tur ? $renk[$tur] : 'inherit' }}">{{ $deger }}</div>
            </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem;align-items:start">
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
            <x-slot name="heading">Bugün neye müdahale etmeliyim?</x-slot>
            <x-slot name="description">{{ count($s['oneriler']) }} kayıt gözden geçirme gerektiriyor.</x-slot>
            <div style="display:flex;flex-direction:column;gap:.5rem">
                @forelse ($s['oneriler'] as $o)
                    <div style="border:1px solid rgb(107 114 128 / .2);border-left:4px solid {{ $renk[$o['seviye']] }};border-radius:.6rem;padding:.6rem .8rem;display:flex;flex-wrap:wrap;justify-content:space-between;gap:.5rem">
                        <div style="min-width:220px;flex:1">
                            <div style="font-weight:700;font-size:.85rem">{{ $o['baslik'] }}</div>
                            <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $o['adet'] }} kayıt · {{ $o['aciklama'] }}</div>
                        </div>
                        <div style="display:flex;gap:.35rem;align-items:center">
                            @if ($o['url'])<x-filament::button size="xs" tag="a" :href="$o['url']">{{ $o['url_ad'] }} →</x-filament::button>@endif
                        </div>
                    </div>
                @empty
                    <div style="font-size:.85rem;color:rgb(21 128 61)">Müdahale gerektiren kayıt yok.</div>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-calculator" icon-color="gray">
            <x-slot name="heading">Skor dökümü</x-slot>
            <x-slot name="description">100 puandan başlanır; her kalemin düşüşü üst sınırla sınırlıdır.</x-slot>
            <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                <tr><td style="padding:.35rem 0">Başlangıç</td><td style="text-align:right;font-weight:700">100</td></tr>
                @foreach ($s['dokum'] as $d)
                    <tr style="border-top:1px solid rgb(107 114 128 / .15)"><td style="padding:.35rem 0">{{ $d['neden'] }}</td><td style="text-align:right;font-weight:700;color:rgb(220 38 38)">−{{ $d['puan'] }}</td></tr>
                @endforeach
                <tr style="border-top:2px solid rgb(107 114 128 / .3)"><td style="padding:.4rem 0;font-weight:700">Operasyon skoru</td><td style="text-align:right;font-weight:800;color:{{ $skorRenk($s['skor']) }}">{{ $s['skor'] }}</td></tr>
            </table>
            @php $k = $s['kapasite']; @endphp
            <div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.6rem;line-height:1.5">
                Hizmet süresi (bu ay): gerekli {{ $k['gerekli'] }} dk · yapılan {{ $k['yapilan'] }} dk · planlı {{ $k['planli'] }} dk.
                Gerekli süre = çalışan × {{ config('isg.igu_aylik_dk.'.$firma->tehlike_sinifi) }} dk (İSG Hizmetleri Yönetmeliği); yapılan / planlı süre Ziyaret Programı'ndaki "saat" alanından.
            </div>
        </x-filament::section>
    </div>
@endif
