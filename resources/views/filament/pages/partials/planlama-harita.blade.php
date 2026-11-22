@php
    $rota = $this->haritaRotasi;
    $duraklar = $rota['duraklar'];
    $gmapsUrl = $rota['gmaps_url'];
@endphp

<div style="display:flex;flex-direction:column;gap:1rem">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;border:1px solid rgb(107 114 128/.25);border-radius:.85rem;padding:.75rem 1rem">
        <div style="display:flex;align-items:center;gap:.65rem;flex-wrap:wrap">
            <label style="font-size:.82rem;font-weight:700">Rota Tarihi:</label>
            <input type="date" wire:model.live="takvimSeciliTarih" style="padding:.4rem .65rem;border:1px solid rgb(107 114 128/.35);border-radius:.45rem;font-size:.84rem;background:transparent">
            <span style="font-size:.78rem;color:rgb(107 114 128)">
                {{ $rota['tarih_etiket'] }} · <strong>{{ count($duraklar) }} firma</strong>
            </span>
        </div>
        @if ($gmapsUrl)
            <a href="{{ $gmapsUrl }}" target="_blank" rel="noopener"
                style="display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;border-radius:.55rem;background:#16a34a;color:#fff;font-weight:700;font-size:.82rem;text-decoration:none">
                🧭 Google Maps Çoklu Rota Başlat ↗
            </a>
        @endif
    </div>

    <div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:1rem">
        <div style="border:1px solid rgb(107 114 128/.25);border-radius:.85rem;overflow:hidden;min-height:420px;position:relative;background:#e2e8f0"
            wire:ignore
            x-data="{
                duraklar: @js($duraklar),
                init() {
                    const yukle = () => {
                        if (!window.L) return;
                        const map = L.map(this.$refs.harita).setView([40.205, 29.02], 11);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 18,
                            attribution: '© OpenStreetMap'
                        }).addTo(map);

                        const pts = [];
                        this.duraklar.forEach((d, idx) => {
                            pts.push([d.lat, d.lng]);
                            L.marker([d.lat, d.lng]).addTo(map)
                                .bindPopup('<strong>' + (idx + 1) + '. ' + d.unvan + '</strong><br>' + (d.adres || d.bolge));
                        });
                        if (pts.length > 1) {
                            L.polyline(pts, { color: '#2563eb', weight: 3, dashArray: '6 6' }).addTo(map);
                            map.fitBounds(pts, { padding: [30, 30] });
                        } else if (pts.length === 1) {
                            map.setView(pts[0], 13);
                        }
                    };

                    if (window.L) { yukle(); return; }
                    const css = document.createElement('link');
                    css.rel = 'stylesheet';
                    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(css);
                    const sc = document.createElement('script');
                    sc.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    sc.onload = yukle;
                    document.head.appendChild(sc);
                }
            }">
            <div x-ref="harita" style="width:100%;height:420px"></div>
        </div>

        <div style="border:1px solid rgb(107 114 128/.25);border-radius:.85rem;padding:.85rem;max-height:420px;overflow-y:auto">
            <div style="font-weight:700;font-size:.88rem;margin-bottom:.6rem">Önerilen Ziyaret Sırası (En Yakın Rota)</div>
            @forelse ($duraklar as $i => $d)
                <div style="display:flex;gap:.6rem;padding:.55rem 0;border-bottom:1px solid rgb(107 114 128/.15);font-size:.8rem">
                    <span style="width:1.6rem;height:1.6rem;border-radius:999px;background:#2563eb;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:.74rem;flex-shrink:0">{{ $i + 1 }}</span>
                    <div style="min-width:0;flex:1">
                        <div style="font-weight:700;line-height:1.3">{{ $d['unvan'] }}</div>
                        <div style="font-size:.73rem;color:rgb(107 114 128)">{{ $d['bolge'] }}@if ($d['adres']) · {{ \Illuminate\Support\Str::limit($d['adres'], 48) }}@endif</div>
                        <div style="margin-top:.25rem;display:flex;gap:.5rem">
                            <a href="{{ \App\Filament\Pages\ZiyaretModu::getUrl(['firma' => $d['firma_id']]) }}" style="font-size:.73rem;font-weight:700;color:#0284c7;text-decoration:none">Ziyareti Başlat →</a>
                            <a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($d['adres'] ?: ($d['lat'].','.$d['lng'])) }}" target="_blank" rel="noopener" style="font-size:.73rem;color:#475569;text-decoration:none">Haritada Gör ↗</a>
                        </div>
                    </div>
                </div>
            @empty
                <p style="font-size:.82rem;color:rgb(107 114 128)">Haritada gösterilecek aktif firma bulunamadı.</p>
            @endforelse
        </div>
    </div>
</div>
