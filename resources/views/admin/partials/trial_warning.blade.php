{{-- Panel en üstü (AdminPanelProvider BODY_START): deneme/abonelik bitişine ≤ 7 gün kalınca. --}}
@php
    $kullanici = auth()->user();
@endphp
@if ($kullanici?->shouldShowTrialWarning())
    @php
        $kalan = $kullanici->trialDaysRemaining();
        $ne = $kullanici->abonelik_plani === 'deneme' ? 'Deneme süreniz' : 'Abonelik süreniz';
        $metin = $kalan === 0
            ? "{$ne} bugün sona eriyor."
            : "{$ne} {$kullanici->abonelik_bitis->format('d.m.Y')} tarihinde bitiyor — son {$kalan} gün kaldı.";
    @endphp
    <div role="status" class="mehse-deneme-uyari" style="background:#fef3c7;border-bottom:1px solid #fcd34d;color:#92400e;font-size:.875rem;">
        <div style="padding:.6rem 1rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.5rem 1rem;text-align:center;">
            <span style="display:inline-flex;align-items:center;gap:.4rem;font-weight:600;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                {{ $metin }}
            </span>
            <span>Verileriniz korunur; kesintisiz devam etmek için paketinizi seçin.</span>
            <a href="{{ route('filament.admin.paketler') }}" style="display:inline-block;background:#92400e;color:#fff;font-weight:700;padding:.3rem .9rem;border-radius:.5rem;text-decoration:none;">Aboneliği Uzat</a>
        </div>
    </div>
@endif
