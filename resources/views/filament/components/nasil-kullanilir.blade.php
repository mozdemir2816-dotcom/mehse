@php
    $rehber = \App\Support\SayfaRehberi::bul($scopes ?? []);
@endphp

@if ($rehber)
    <div x-data="{ acik: false }" style="display:inline-flex;align-items:center">
        <button type="button"
            x-on:click="acik = true"
            style="display:inline-flex;align-items:center;gap:.35rem;padding:.45rem .78rem;border-radius:.55rem;border:1px solid rgb(107 114 128 / .35);background:transparent;color:inherit;font-size:.8rem;font-weight:600;cursor:pointer;white-space:nowrap"
            title="Bu modülün 3 adımlı kullanım rehberini aç">
            <span>❓ Nasıl Kullanılır?</span>
        </button>

        <template x-teleport="body">
            <div x-show="acik"
                x-transition.opacity
                x-on:click.self="acik = false"
                x-on:keydown.escape.window="acik = false"
                style="position:fixed;inset:0;z-index:80;background:rgb(15 23 42 / .55);display:flex;align-items:center;justify-content:center;padding:1rem"
                role="dialog" aria-modal="true" aria-label="{{ $rehber['baslik'] }} — Nasıl Kullanılır?">
                <div style="background:#ffffff;color:#111827;border-radius:1rem;box-shadow:0 24px 60px rgb(0 0 0 / .35);width:min(100%,36rem);max-height:88vh;overflow-y:auto;border:1px solid #e5e7eb">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;padding:1rem 1.2rem;border-bottom:1px solid #e5e7eb;background:#f8fafc;border-radius:1rem 1rem 0 0">
                        <div>
                            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#1d4ed8">Hızlı Kullanım Rehberi</div>
                            <div style="font-size:1.02rem;font-weight:800;color:#0f172a;margin-top:.1rem">{{ $rehber['baslik'] }}</div>
                        </div>
                        <button type="button" x-on:click="acik = false" aria-label="Kapat"
                            style="min-width:2.2rem;min-height:2.2rem;border:none;background:transparent;font-size:1.15rem;cursor:pointer;color:#475569;border-radius:.4rem">✕</button>
                    </div>

                    <div style="padding:1.05rem 1.2rem;display:flex;flex-direction:column;gap:.9rem">
                        <p style="margin:0;font-size:.86rem;color:#334155;line-height:1.5">
                            {{ $rehber['ozet'] }}
                        </p>

                        <div style="display:flex;flex-direction:column;gap:.6rem">
                            @foreach ($rehber['adimlar'] as $i => $adim)
                                <div style="display:flex;gap:.7rem;align-items:flex-start;padding:.65rem .75rem;border-radius:.65rem;border:1px solid #e2e8f0;background:#f8fafc">
                                    <span style="flex-shrink:0;width:1.55rem;height:1.55rem;border-radius:999px;background:#2563eb;color:#ffffff;font-size:.78rem;font-weight:800;display:inline-flex;align-items:center;justify-content:center">
                                        {{ $i + 1 }}
                                    </span>
                                    <span style="font-size:.84rem;color:#1e293b;line-height:1.45">{{ $adim }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div style="padding:.7rem .85rem;border-radius:.65rem;background:#fffbeb;border:1px solid #fcd34d;font-size:.8rem;color:#92400e;line-height:1.45">
                            <strong>💡 Mevzuat / Pratik İpucu:</strong> {{ $rehber['ipucu'] }}
                        </div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;padding:.75rem 1.2rem;border-top:1px solid #e5e7eb;background:#f8fafc;border-radius:0 0 1rem 1rem">
                        <button type="button" x-on:click="acik = false"
                            style="padding:.48rem 1.1rem;border-radius:.55rem;border:none;background:#2563eb;color:#ffffff;font-size:.83rem;font-weight:700;cursor:pointer">
                            Anladım, Kapat
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
