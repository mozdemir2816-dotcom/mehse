@php
    $eksikSureler = $this->eksikSureTablosu;
    $kanitlar = $this->ziyaretKanitlari;
    $eksikFirmaSayisi = collect($eksikSureler)->where('eksik_dk', '>', 0)->count();
@endphp

<div style="display:flex;flex-direction:column;gap:1rem">
    @if ($eksikFirmaSayisi > 0)
        <div style="border-left:4px solid #dc2626;background:rgb(220 38 38/.06);border-radius:.6rem;padding:.7rem 1rem;font-size:.84rem;display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap">
            <span><strong>⚠ {{ $eksikFirmaSayisi }} işyerinde</strong> bu ay için kanıtlı saha süresi yasal dakikanın altında.</span>
            <span style="font-size:.78rem;color:#64748b">Aşağıdan QR/GPS giriş-çıkış kaydı başlatarak 4'lü yasal kanıt oluşturun.</span>
        </div>
    @endif

    {{-- HIZLI QR / GPS GİRİŞ BAŞLATMA --}}
    <x-filament::section icon="heroicon-o-shield-check" icon-color="success">
        <x-slot name="heading">📍 Yeni Saha Ziyaret Kanıtı Başlat (QR + GPS + Süre + Defter)</x-slot>
        <x-slot name="description">Sahaya vardığınızda GPS konumu ve isteğe bağlı işyeri QR doğrulamasıyla giriş yapın; çıkışta süre otomatik dakika olarak işlenir.</x-slot>

        <div
            x-data="{
                konumAl() {
                    if (!navigator.geolocation) { alert('Tarayıcınız GPS konum desteği vermiyor.'); return; }
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            $wire.set('kanitEnlem', Number(pos.coords.latitude.toFixed(6)));
                            $wire.set('kanitBoylam', Number(pos.coords.longitude.toFixed(6)));
                        },
                        () => alert('GPS konum izni alınamadı.')
                    );
                }
            }"
            style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.65rem;align-items:end"
        >
            <div>
                <label class="mehse-etiket">İşyeri *</label>
                <select wire:model="kanitFirmaId" style="width:100%;padding:.45rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128/.35);background:transparent;font-size:.82rem">
                    <option value="">— İşyeri seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mehse-etiket">Ziyaret Konusu</label>
                <input type="text" wire:model="kanitKonu" placeholder="Örn: Aylık Saha Gözetimi" style="width:100%;padding:.45rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128/.35);background:transparent;font-size:.82rem">
            </div>
            <div>
                <label class="mehse-etiket">İşyeri QR Kodu / Linki (Opsiyonel)</label>
                <input type="text" wire:model="kanitQrKod" placeholder="İşyeri QR posterinden okut/yapıştır" style="width:100%;padding:.45rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128/.35);background:transparent;font-size:.82rem">
            </div>
            <div style="display:flex;gap:.45rem;flex-wrap:wrap">
                <x-filament::button size="sm" color="gray" outlined x-on:click="konumAl()" icon="heroicon-o-map-pin">
                    {{ $kanitEnlem ? '✓ GPS Alındı' : 'GPS Al' }}
                </x-filament::button>
                <x-filament::button size="sm" color="success" wire:click="kanitGirisYap" icon="heroicon-o-play">
                    Giriş Yap
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    {{-- 4'LÜ KANIT ZİYARET KAYITLARI --}}
    <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
        <x-slot name="heading">4'lü Kanıt Ziyaret Defteri (QR · GPS · Süre · Defter · İmza)</x-slot>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:860px">
                <thead>
                    <tr style="text-align:left;border-bottom:1px solid rgb(107 114 128/.25);color:#64748b">
                        <th style="padding:.45rem">Tarih</th>
                        <th style="padding:.45rem">İşyeri</th>
                        <th style="padding:.45rem">Konu</th>
                        <th style="padding:.45rem">Süre (dk)</th>
                        <th style="padding:.45rem">GPS</th>
                        <th style="padding:.45rem">QR</th>
                        <th style="padding:.45rem">İmza</th>
                        <th style="padding:.45rem">Durum / İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kanitlar as $k)
                        <tr style="border-bottom:1px solid rgb(107 114 128/.12)">
                            <td style="padding:.45rem;white-space:nowrap">{{ $k->ziyaret_tarihi?->format('d.m.Y') }}</td>
                            <td style="padding:.45rem;font-weight:700">{{ $k->firma?->secimEtiketi() }}</td>
                            <td style="padding:.45rem">{{ $k->konu ?: '—' }}</td>
                            <td style="padding:.45rem;font-weight:700">{{ $k->sure_dk ? $k->sure_dk.' dk' : 'Sürüyor…' }}</td>
                            <td style="padding:.45rem">{!! $k->gpsVarMi() ? '<span style="color:#15803d;font-weight:700">✓ GPS</span>' : '<span style="color:#94a3b8">—</span>' !!}</td>
                            <td style="padding:.45rem">{!! $k->qr_dogrulandi ? '<span style="color:#15803d;font-weight:700">✓ QR</span>' : '<span style="color:#94a3b8">—</span>' !!}</td>
                            <td style="padding:.45rem">{!! $k->imza_onay ? '<span style="color:#15803d;font-weight:700">✓ Onaylı</span>' : '<span style="color:#94a3b8">—</span>' !!}</td>
                            <td style="padding:.45rem;white-space:nowrap">
                                @if ($k->durum !== 'tamamlandi')
                                    <x-filament::button size="xs" color="warning" wire:click="kanitCikisYap({{ $k->id }})">🏁 Çıkış Yap &amp; Tamamla</x-filament::button>
                                @else
                                    <span style="background:#dcfce7;color:#15803d;padding:.15rem .5rem;border-radius:999px;font-size:.72rem;font-weight:700">Tamamlandı ({{ $k->kanitPuani() }}/4)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:1rem;text-align:center;color:#64748b">Henüz QR/GPS ziyaret kanıtı kaydı yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- EKSİK SAHA SÜRESİ — PLAN ÖNERİSİ TABLOSU --}}
    <x-filament::section icon="heroicon-o-clock" icon-color="warning">
        <x-slot name="heading">Eksik Saha Süresi — Bu Ay Plan Önerisi ({{ now()->translatedFormat('F Y') }})</x-slot>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                <thead>
                    <tr style="text-align:left;border-bottom:1px solid rgb(107 114 128/.25);color:#64748b">
                        <th style="padding:.45rem">İşyeri</th>
                        <th style="padding:.45rem">İSG Uzmanı</th>
                        <th style="padding:.45rem;text-align:right">Yasal (dk)</th>
                        <th style="padding:.45rem;text-align:right">Kanıtlı (dk)</th>
                        <th style="padding:.45rem;text-align:right">Eksik (dk)</th>
                        <th style="padding:.45rem">Takvim Planı</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($eksikSureler as $s)
                        <tr style="border-bottom:1px solid rgb(107 114 128/.12)">
                            <td style="padding:.45rem;font-weight:700">{{ $s['unvan'] }}</td>
                            <td style="padding:.45rem">{{ $s['uzman'] }}</td>
                            <td style="padding:.45rem;text-align:right">{{ $s['yasal_dk'] }} dk</td>
                            <td style="padding:.45rem;text-align:right;color:#15803d;font-weight:700">{{ $s['gerceklesen_dk'] }} dk</td>
                            <td style="padding:.45rem;text-align:right;font-weight:800;color:{{ $s['eksik_dk'] > 0 ? '#dc2626' : '#15803d' }}">
                                {{ $s['eksik_dk'] > 0 ? $s['eksik_dk'].' dk' : 'Tamam ✓' }}
                            </td>
                            <td style="padding:.45rem">
                                @if ($s['planli_var'])
                                    <span style="color:#15803d;font-weight:700">Planlı</span>
                                @else
                                    <button type="button" wire:click="$set('firmaId', {{ $s['firma_id'] }}); gorunumSec('plan')" style="border:none;background:none;color:#dc2626;font-weight:700;cursor:pointer;padding:0">Yok — Planla →</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</div>
