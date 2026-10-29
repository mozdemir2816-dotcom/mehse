@php
    $renkler = [
        'yesil' => 'background:#dcfce7;color:#166534',
        'sari' => 'background:#fef3c7;color:#92400e',
        'kirmizi' => 'background:#fee2e2;color:#991b1b',
    ];
@endphp
<div style="display:flex;flex-wrap:wrap;gap:.25rem;padding:.25rem 0">
    @foreach (\App\Support\CalisanIsgDurumu::rozetler($getRecord()) as $r)
        <span title="{{ $r['ipucu'] }}" style="font-size:.68rem;font-weight:700;padding:.1rem .5rem;border-radius:999px;white-space:nowrap;{{ $renkler[$r['renk']] }}">{{ $r['etiket'] }}</span>
    @endforeach
</div>
