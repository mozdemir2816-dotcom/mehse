@php
    $girdi = 'margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $o = $this->ozet;
    $kart = fn (bool $yeter) => 'border:1px solid '.($yeter ? 'rgb(21 128 61 / .45)' : 'rgb(217 119 6 / .6)').';border-radius:.75rem;padding:.8rem 1rem;'.($yeter ? '' : 'background:rgb(217 119 6 / .06)');
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem;line-height:1.5">
        Paketin sınav soruları havuzu. Çalışan eğitime başlamadan önce <strong>sınav öncesi (ön test)</strong> için
        {{ $o['on_test_gereken'] }}, tüm videoları izledikten sonra <strong>sınav sonrası (final)</strong> için
        {{ $o['final_gereken'] }} soruyu havuzdan rastgele çeker. Havuz ne kadar büyükse her çalışana farklı sorular gelir.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;align-items:end">
        <div>
            <label class="mehse-etiket">Eğitim Paketi</label>
            <select wire:model.live="paketId" style="{{ $girdi }}">
                @forelse ($this->paketler as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @empty
                    <option value="">— Önce Paketler sekmesinden paket oluşturun —</option>
                @endforelse
            </select>
        </div>
        <div style="{{ $kart($o['on_test'] >= $o['on_test_gereken']) }}">
            <div class="mehse-soluk">Sınav öncesi (ön test) havuzu</div>
            <div style="font-weight:700;font-size:1.1rem">{{ $o['on_test'] }} soru <span style="font-weight:400;font-size:.8rem">→ {{ $o['on_test_gereken'] }} tanesi sorulur</span></div>
            @if ($o['on_test'] < $o['on_test_gereken'])<div style="font-size:.75rem;color:#b45309">{{ $o['on_test_gereken'] - $o['on_test'] }} soru daha ekleyin</div>@endif
        </div>
        <div style="{{ $kart($o['final'] >= $o['final_gereken']) }}">
            <div class="mehse-soluk">Sınav sonrası (final) havuzu</div>
            <div style="font-weight:700;font-size:1.1rem">{{ $o['final'] }} soru <span style="font-weight:400;font-size:.8rem">→ {{ $o['final_gereken'] }} tanesi sorulur</span></div>
            @if ($o['final'] < $o['final_gereken'])<div style="font-size:.75rem;color:#b45309">{{ $o['final_gereken'] - $o['final'] }} soru daha ekleyin</div>@endif
        </div>
    </div>

    @if ($o['final'] > 0)
        <div style="font-size:.8rem;color:rgb(107 114 128)">
            Final havuzunun konu dağılımı:
            @foreach (\App\Models\EgitimPaketiSorusu::KONULAR as $k => $ad)
                {{ $ad }} <strong>{{ $o['konular'][$k] ?? 0 }}</strong> ·
            @endforeach
            Konusu girilmemiş <strong>{{ $o['konular'][0] ?? 0 }}</strong>.
            Final sınavında sorular konulara sırayla dağıtılır; önceki denemelerde sorulan sorular (havuz yetiyorsa) tekrar gelmez.
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
