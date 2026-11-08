<?php /** @var \App\Filament\Pages\EkipEvraklari $this */ ?>
@php
    $kart = 'background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.1rem';
    $girdi = 'width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid #e2e8f0;background:#fff;color:#0f172a;font-size:.85rem';
    $rozet = 'display:inline-block;font-size:.75rem;font-weight:700;padding:.12rem .55rem;border-radius:999px;';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:#64748b;margin-top:-.5rem">
        Yetki verdiğiniz kişilerin kendi hesaplarında hazırladığı risk değerlendirmeleri ve acil durum planları burada toplanır.
        Beğendiğiniz bir değerlendirmeyi kendi Risk Şablonu havuzunuza kopyalayabilirsiniz; orijinali değişmez.
    </p>

    <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:end;justify-content:space-between">
        <div style="min-width:240px;max-width:320px;flex:1">
            <label style="font-weight:600;font-size:.78rem;color:#475569">Hazırlayan kişi</label>
            <select wire:model.live="kullaniciId" style="{{ $girdi }};margin-top:.25rem">
                <option value="">Tüm ekip</option>
                @foreach ($this->kullanicilar as $k)
                    <option value="{{ $k->id }}">{{ $k->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:.5rem;font-size:.78rem;color:#475569">
            <span style="{{ $rozet }}background:#e0e7ff;color:#3730a3">{{ $this->riskDegerlendirmeleri->count() }} risk değerlendirmesi</span>
            <span style="{{ $rozet }}background:#fee2e2;color:#991b1b">{{ $this->acilDurumPlanlari->count() }} acil durum planı</span>
        </div>
    </div>

    <div>
        <h3 style="font-weight:700;font-size:.95rem;margin-bottom:.6rem">Paylaşılan Risk Değerlendirmeleri</h3>
        @if ($this->riskDegerlendirmeleri->isEmpty())
            <div style="{{ $kart }};text-align:center;color:#64748b;padding:1.8rem 1rem">Henüz görüntülenecek risk değerlendirmesi yok.</div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:.75rem">
                @foreach ($this->riskDegerlendirmeleri as $rd)
                    <div style="{{ $kart }};display:flex;flex-direction:column;gap:.6rem;justify-content:space-between">
                        <div>
                            <div style="font-weight:700;font-size:.9rem;color:#0f172a">{{ $rd->firma_unvan ?? $rd->firma?->unvan ?? '—' }}</div>
                            <div style="font-size:.78rem;color:#64748b;margin-top:.2rem">
                                Hazırlayan: <strong style="color:#334155">{{ $rd->firma?->user?->name ?? '—' }}</strong>
                                · {{ $rd->rapor_tarihi?->format('d.m.Y') }}
                            </div>
                            <div style="margin-top:.5rem;display:flex;flex-wrap:wrap;gap:.3rem">
                                <span style="{{ $rozet }}background:#f1f5f9;color:#475569">{{ $rd->yontemEtiketi() }}</span>
                                <span style="{{ $rozet }}background:#fef3c7;color:#92400e">{{ $rd->maddeler_count }} madde</span>
                            </div>
                        </div>
                        <x-filament::button size="sm" color="primary" icon="heroicon-o-document-duplicate"
                            wire:click="havuzaEkle({{ $rd->id }})"
                            wire:confirm="Bu değerlendirmenin {{ $rd->maddeler_count }} maddesi Risk Şablonu havuzunuza kopyalansın mı? Değerlendirmenin kendisi değişmez, kopyası eklenir.">
                            + Havuzuma Kopyala
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <h3 style="font-weight:700;font-size:.95rem;margin-bottom:.6rem">Acil Durum Planları</h3>
        @if ($this->acilDurumPlanlari->isEmpty())
            <div style="{{ $kart }};text-align:center;color:#64748b;padding:1.8rem 1rem">Henüz görüntülenecek acil durum planı yok.</div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:.75rem">
                @foreach ($this->acilDurumPlanlari as $p)
                    <div style="{{ $kart }}">
                        <div style="font-weight:700;font-size:.9rem;color:#0f172a">{{ $p->firma?->unvan ?? '—' }}</div>
                        <div style="font-size:.78rem;color:#64748b;margin-top:.2rem">
                            Hazırlayan: <strong style="color:#334155">{{ $p->firma?->user?->name ?? '—' }}</strong>
                            · {{ $p->updated_at?->format('d.m.Y') }}
                        </div>
                        <div style="margin-top:.5rem;font-size:.78rem;color:#475569">{{ implode(', ', $p->konuAdlari()) ?: '—' }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
