@php
    /** @var ?\App\Models\Firma $firma */
    /** @var \Illuminate\Support\Collection<int, \App\Models\TeftisPaylasimi> $paylasimlar */
@endphp
<div style="margin-top:.5rem">
    @if (! $firma)
        <p style="font-size:.82rem;color:#b45309">Önce firma seçin.</p>
    @elseif ($paylasimlar->isEmpty())
        <p style="font-size:.82rem;color:#64748b">Bu firma için oluşturulmuş teftiş bağlantısı yok.</p>
    @else
        <div style="font-size:.78rem;font-weight:700;color:#475569;margin-bottom:.45rem">Oluşturulan Teftiş Bağlantıları ({{ $paylasimlar->count() }})</div>
        <div style="display:flex;flex-direction:column;gap:.5rem;max-height:240px;overflow-y:auto">
            @foreach ($paylasimlar as $p)
                @php
                    $url = route('mufettis.teftis', ['token' => $p->token]);
                    $qr = $p->gecerliMi() ? \App\Support\CanliImzaYonetici::qrDataUri($url) : null;
                @endphp
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;border:1px solid #cbd5e1;border-radius:10px;padding:.55rem .75rem;font-size:.8rem">
                    <div style="min-width:0;flex:1">
                        <div style="font-weight:700">{{ $p->baslik }}</div>
                        <div style="font-size:.73rem;color:#64748b">
                            Bitiş: {{ $p->bitis_tarihi->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}
                            · Erişim: <strong>{{ $p->erisim_sayisi }}</strong>
                            @if ($p->son_erisim_at) · Son: {{ $p->son_erisim_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}@endif
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.45rem;flex-shrink:0">
                        @if ($qr)
                            <img src="{{ $qr }}" alt="Teftiş QR" style="width:48px;height:48px;border:1px solid #e2e8f0;border-radius:6px">
                            <a href="{{ $url }}" target="_blank" rel="noopener" style="font-size:.74rem;font-weight:700;color:#1d4ed8;text-decoration:none;padding:.3rem .55rem;border:1px solid #93c5fd;border-radius:8px">Aç ↗</a>
                        @else
                            <span style="font-size:.72rem;color:#dc2626;font-weight:700">Süresi Doldu / İptal</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
