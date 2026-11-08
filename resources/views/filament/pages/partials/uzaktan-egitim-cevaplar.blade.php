{{-- Soru bazlı cevap listesi (ön test / final). Değişken: $cevaplar (soru, secenekler, dogru_index, verilen_index). --}}
<div style="margin-top:.5rem;font-size:.78rem;line-height:1.5">
    @foreach ($cevaplar ?? [] as $i => $c)
        @php
            $dogruMu = isset($c['verilen_index'], $c['dogru_index']) && (int) $c['verilen_index'] === (int) $c['dogru_index'];
            $sec = $c['secenekler'] ?? [];
            $verilen = (int) ($c['verilen_index'] ?? -1);
        @endphp
        <div style="margin-bottom:.4rem;padding-left:.5rem;border-left:3px solid {{ $dogruMu ? 'rgb(34 197 94)' : 'rgb(239 68 68)' }}">
            <div><strong>{{ $i + 1 }}.</strong> {{ $c['soru'] ?? '' }}</div>
            <div class="mehse-soluk-metin">Verdiği cevap:
                <span style="color:{{ $dogruMu ? 'rgb(21 128 61)' : 'rgb(185 28 28)' }}">
                    {{ $verilen >= 0 ? chr(65 + $verilen).') '.($sec[$verilen] ?? '') : 'boş' }}
                </span>
                @unless ($dogruMu)
                    · Doğru: <span style="color:rgb(21 128 61)">{{ isset($c['dogru_index']) ? chr(65 + (int) $c['dogru_index']).') '.($sec[$c['dogru_index']] ?? '') : '—' }}</span>
                @endunless
            </div>
        </div>
    @endforeach
</div>
