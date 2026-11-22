@php
    /** @var ?\App\Models\Firma $firma */
    /** @var \Illuminate\Support\Collection<int, \App\Models\CanliImza> $imzalar */
@endphp
<div style="margin-top:.5rem">
    @if (! $firma)
        <p style="font-size:.82rem;color:#b45309">Önce sayfadan bir firma seçin, ardından imza oturumu oluşturun.</p>
    @elseif ($imzalar->isEmpty())
        <p style="font-size:.82rem;color:#64748b">Bu firma ve belge türü için henüz canlı imza oturumu yok. Yukarıdaki düğmeyle ilk QR / tablet imza oturumunu oluşturabilirsiniz.</p>
    @else
        <div style="font-size:.78rem;font-weight:700;color:#475569;margin-bottom:.45rem">Son Canlı İmza Oturumları ({{ $imzalar->count() }})</div>
        <div style="display:flex;flex-direction:column;gap:.5rem;max-height:260px;overflow-y:auto">
            @foreach ($imzalar as $im)
                @php
                    $url = \App\Support\CanliImzaYonetici::imzaAdresi($im);
                    $qr = ! $im->imzalandiMi() ? \App\Support\CanliImzaYonetici::qrDataUri($url) : null;
                @endphp
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;border:1px solid #cbd5e1;border-radius:10px;padding:.55rem .75rem;font-size:.8rem">
                    <div style="min-width:0;flex:1">
                        <div style="font-weight:700">{{ $im->calisan_adi ?: 'İsimsiz / Çalışan Dolduracak' }}</div>
                        <div style="font-size:.74rem;color:#64748b">{{ $im->belge_baslik }}</div>
                        @if ($im->imzalandiMi())
                            <span style="display:inline-block;margin-top:.2rem;font-size:.7rem;font-weight:700;color:#15803d;background:#dcfce7;padding:.1rem .45rem;border-radius:999px">
                                ✓ İmzalandı · {{ $im->imzalandi_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') }} · {{ $im->dogrulama_kodu }}
                            </span>
                        @else
                            <span style="display:inline-block;margin-top:.2rem;font-size:.7rem;font-weight:700;color:#b45309;background:#fef3c7;padding:.1rem .45rem;border-radius:999px">
                                ⏳ İmza Bekliyor
                            </span>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:.45rem;flex-shrink:0">
                        @if ($qr)
                            <img src="{{ $qr }}" alt="QR" style="width:52px;height:52px;border:1px solid #e2e8f0;border-radius:6px">
                            <a href="{{ $url }}" target="_blank" rel="noopener" style="font-size:.74rem;font-weight:700;color:#1d4ed8;text-decoration:none;padding:.3rem .55rem;border:1px solid #93c5fd;border-radius:8px">
                                Tablette Aç ↗
                            </a>
                        @else
                            <a href="{{ route('canli-imza.pdf', ['id' => $im->id]) }}" style="font-size:.74rem;font-weight:700;color:#15803d;text-decoration:none;padding:.3rem .55rem;border:1px solid #86efac;border-radius:8px">
                                Mühürlü PDF
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
