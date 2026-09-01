<x-filament-panels::page>
    <div style="max-width:38rem;margin:2rem auto;text-align:center">
        <div style="font-size:2.25rem">🚧</div>
        <div style="font-size:1.15rem;font-weight:700;margin-top:.5rem">Bu modül hazırlanıyor</div>
        <p style="color:rgb(107 114 128);font-size:.9rem;margin-top:.5rem">
            {{ $this->getTitle() }} modülü sıradaki fazlarda kurulacak.
            @if ($aiModulu)
                <br><span style="color:rgb(139 92 246)">Yapay zeka destekli modül</span> — sağlayıcı entegrasyonu ileride.
            @endif
        </p>
        @if ($planNotu)
            <div style="margin-top:1rem;border:1px solid rgb(107 114 128 / .3);border-radius:.6rem;padding:.75rem 1rem;font-size:.82rem;color:rgb(107 114 128)">
                📋 Referans: {{ $planNotu }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
