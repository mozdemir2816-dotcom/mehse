<?php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
?>
<x-filament-panels::page>
    <div>
        <label style="font-weight:600;font-size:.82rem">Firma</label>
        <select wire:model.live="firmaId" style="{{ $girdi }}">
            <option value="">Firma seçin...</option>
            @foreach ($this->firmalar as $id => $unvan)
                <option value="{{ $id }}">{{ $unvan }}</option>
            @endforeach
        </select>
    </div>

    @if ($this->firma)
        <div style="display:grid;grid-template-columns:220px 1fr;gap:1rem;margin-top:1rem;align-items:start">
            {{-- SOL: ARAÇLAR + PALET --}}
            <div style="display:flex;flex-direction:column;gap:.75rem">
                <div style="{{ $kutu }}">
                    <div style="font-weight:600;font-size:.82rem;margin-bottom:.5rem">Araç</div>
                    <div style="display:flex;flex-direction:column;gap:.35rem">
                        <x-filament::button size="sm" :color="$aktifArac === 'sembol' ? 'warning' : 'gray'" wire:click="aracSec('sembol')" icon="heroicon-o-map-pin">Sembol Ekle</x-filament::button>
                        <x-filament::button size="sm" :color="$aktifArac === 'duvar' ? 'warning' : 'gray'" wire:click="aracSec('duvar')" icon="heroicon-o-minus">Duvar Çiz</x-filament::button>
                    </div>
                    @if ($aktifArac === 'duvar')
                        <p style="font-size:.72rem;color:rgb(107 114 128);margin-top:.5rem">
                            {{ $bekleyenNokta ? 'Bitiş noktasına tıklayın (90°\'ye kenetlenir).' : 'Başlangıç noktasına tıklayın.' }}
                        </p>
                    @endif
                </div>

                <div style="{{ $kutu }}">
                    <div style="font-weight:600;font-size:.82rem;margin-bottom:.5rem">Sembol Paleti</div>
                    <div style="display:flex;flex-direction:column;gap:.3rem">
                        @foreach (config('isg.kroki.semboller') as $tip => $bilgi)
                            <button type="button" wire:click="semboSec('{{ $tip }}')"
                                style="display:flex;align-items:center;gap:.5rem;padding:.35rem .5rem;border-radius:.5rem;border:1px solid {{ $aktifSembol === $tip && $aktifArac === 'sembol' ? $bilgi['renk'] : 'transparent' }};background:{{ $aktifSembol === $tip && $aktifArac === 'sembol' ? 'rgb(107 114 128 / .1)' : 'transparent' }};cursor:pointer;text-align:left">
                                <span style="width:16px;height:16px;border-radius:4px;background:{{ $bilgi['renk'] }};flex:none"></span>
                                <span style="font-size:.78rem">{{ $bilgi['ad'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div style="{{ $kutu }}">
                    <label style="font-weight:600;font-size:.82rem">Hazırlanma Tarihi</label>
                    <input type="date" wire:model="hazirlanmaTarihi" style="{{ $girdi }}">
                </div>

                <div style="display:flex;flex-direction:column;gap:.4rem">
                    <x-filament::button color="success" wire:click="kaydet" icon="heroicon-o-check">Kaydet</x-filament::button>
                    <x-filament::button color="gray" wire:click="temizle" icon="heroicon-o-trash">Tuvali Temizle</x-filament::button>
                </div>
            </div>

            {{-- SAĞ: TUVAL --}}
            <div>
                <div style="{{ $kutu }};padding:0;overflow:hidden">
                    <svg viewBox="0 0 1000 700" style="width:100%;height:auto;display:block;background:#fafafa;cursor:crosshair"
                        x-on:click="
                            const r = $el.getBoundingClientRect();
                            const vb = $el.viewBox.baseVal;
                            const x = (($event.clientX - r.left) / r.width) * vb.width;
                            const y = (($event.clientY - r.top) / r.height) * vb.height;
                            $wire.nokta(Math.round(x), Math.round(y));
                        ">
                        @if ($this->arkaPlanUrl())
                            <image href="{{ $this->arkaPlanUrl() }}" x="0" y="0" width="1000" height="700" preserveAspectRatio="xMidYMid meet" opacity="0.5" />
                        @endif

                        @foreach ($duvarlar as $d)
                            <line x1="{{ $d['x1'] }}" y1="{{ $d['y1'] }}" x2="{{ $d['x2'] }}" y2="{{ $d['y2'] }}" stroke="#333" stroke-width="4" stroke-linecap="square" />
                        @endforeach

                        @if ($bekleyenNokta)
                            <circle cx="{{ $bekleyenNokta['x'] }}" cy="{{ $bekleyenNokta['y'] }}" r="5" fill="none" stroke="#d97706" stroke-width="2" />
                        @endif

                        @foreach ($semboller as $s)
                            @include('filament.pages.partials.kroki-sembol', ['tip' => $s['tip'], 'x' => $s['x'], 'y' => $s['y'], 'etiket' => $s['etiket'] ?? null])
                        @endforeach
                    </svg>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem">
                    <div style="{{ $kutu }}">
                        <div style="font-weight:600;font-size:.82rem;margin-bottom:.5rem">Duvarlar ({{ count($duvarlar) }})</div>
                        <div style="display:flex;flex-direction:column;gap:.25rem;max-height:150px;overflow-y:auto">
                            @forelse ($duvarlar as $i => $d)
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:.75rem">
                                    <span>{{ $i + 1 }}. ({{ $d['x1'] }},{{ $d['y1'] }}) → ({{ $d['x2'] }},{{ $d['y2'] }})</span>
                                    <x-filament::icon-button icon="heroicon-o-x-mark" size="sm" color="danger" wire:click="duvarSil({{ $i }})" />
                                </div>
                            @empty
                                <p style="font-size:.75rem;color:rgb(107 114 128)">Henüz duvar çizilmedi.</p>
                            @endforelse
                        </div>
                    </div>

                    <div style="{{ $kutu }}">
                        <div style="font-weight:600;font-size:.82rem;margin-bottom:.5rem">Semboller ({{ count($semboller) }})</div>
                        <div style="display:flex;flex-direction:column;gap:.25rem;max-height:150px;overflow-y:auto">
                            @forelse ($semboller as $i => $s)
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:.75rem">
                                    <span>{{ config('isg.kroki.semboller.'.$s['tip'].'.ad') }} — ({{ $s['x'] }},{{ $s['y'] }})</span>
                                    <x-filament::icon-button icon="heroicon-o-x-mark" size="sm" color="danger" wire:click="semboSil({{ $i }})" />
                                </div>
                            @empty
                                <p style="font-size:.75rem;color:rgb(107 114 128)">Henüz sembol yerleştirilmedi.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <p style="color:rgb(107 114 128);margin-top:1rem">Kroki çizmek için önce bir firma seçin.</p>
    @endif

    <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:1.5rem">
        Bu editör, isgpratik'in "Acil Durum & Tahliye Krokisi Düzenleyici"sinin küçültülmüş ilk sürümüdür — sürükleme
        yok (tıkla-yerleştir / listeden sil), 8 çekirdek sembol var, semboller resmi ISO 7010 sanatı değil basitleştirilmiş
        şematik işaretlerdir. Yönetmeliğe uygun gerçek acil durum işaretleri için Acil Durum Planı → "Acil Durum Afişleri"ndeki
        gerçek PDF dosyalarına bakınız.
    </p>
</x-filament-panels::page>
