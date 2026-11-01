<x-filament-panels::page>
    @php
        $kullanici = $this->kullanici();
        $soluk = 'font-size:.8rem;color:rgb(107 114 128)';
    @endphp

    <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-start">
        <form wire:submit="kaydet" style="flex:2 1 420px;min-width:0">
            {{ $this->form }}
            <div style="margin-top:1rem;display:flex;justify-content:flex-end">
                <x-filament::button type="submit" color="success" icon="heroicon-o-check">Fatura Bilgilerini Kaydet</x-filament::button>
            </div>
        </form>

        <div style="flex:1 1 260px;border:1px solid rgb(107 114 128 / .22);border-radius:.75rem;padding:1.1rem 1.2rem;display:flex;flex-direction:column;gap:.6rem">
            <div style="font-weight:700;font-size:.95rem">Hesap</div>
            <div><div style="{{ $soluk }}">Ad soyad</div><div style="font-weight:600">{{ $kullanici->name }}</div></div>
            <div><div style="{{ $soluk }}">E-posta</div><div style="font-weight:600">{{ $kullanici->email }}</div></div>
            <div>
                <div style="{{ $soluk }}">Paket</div>
                <div style="font-weight:600">
                    {{ \App\Models\User::ABONELIK_PLANLARI[$kullanici->abonelik_plani] ?? ($kullanici->abonelik_plani ?: '—') }}
                    @if ($kullanici->abonelik_bitis)
                        · {{ $kullanici->abonelik_bitis->format('d.m.Y') }} tarihine kadar
                    @endif
                </div>
            </div>
            @if ($kullanici->faturaBilgisiTamMi())
                <p style="color:#047857;font-weight:600;font-size:.85rem">✓ Fatura bilgileriniz tamam.</p>
            @else
                <p style="color:#b45309;font-weight:600;font-size:.85rem">Fatura bilgileriniz eksik.</p>
            @endif
            <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.3rem">
                <x-filament::button tag="a" size="sm" color="gray" :href="filament()->getProfileUrl()" icon="heroicon-o-user-circle">Ad, e-posta, şifre</x-filament::button>
                <x-filament::button tag="a" size="sm" color="success" :href="route('filament.admin.paketler')" icon="heroicon-o-sparkles">Paketler</x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
