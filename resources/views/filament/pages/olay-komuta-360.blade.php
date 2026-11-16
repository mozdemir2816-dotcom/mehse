@php
    use App\Filament\Pages\KazaIstatistikleri;
    use App\Filament\Pages\OlayKayitlari;

    $o = $this->ozet;
    $aylar = \App\Support\OlayKomuta::AYLAR;
    $bolgeVerisi = $this->bolgeVerisi;
    $bos = $this->firmaOlaylari->isEmpty();
    $sayi = fn ($n) => number_format((int) $n, 0, ',', '.');

    // Şiddet renkleri: yalnız renge bağlı kalınmaz — her yerde etiket / sayı da yazılır.
    $renkSonuc = [
        'yaralanmasiz' => '#0f766e', 'ilk_yardim' => '#0891b2', 'tibbi_mudahale' => '#d97706',
        'is_gunu_kaybi' => '#ea580c', 'kalici_hasar' => '#dc2626', 'olum' => '#7f1d1d',
    ];
    $renkGrup = ['hafif' => '#0f766e', 'tibbi' => '#d97706', 'kayip' => '#ea580c', 'agir' => '#dc2626'];
    $donutRenk = ['#4f46e5', '#0891b2', '#d97706', '#be185d', '#0f766e', '#7c3aed', '#64748b'];
@endphp

<x-filament-panels::page>
    <style>
        .okm { --okm-kenar: rgb(107 114 128 / .28); --okm-zemin: rgb(107 114 128 / .07); --okm-vurgu: var(--primary-600, #4f46e5); font-size: .88rem; }
        .okm * { box-sizing: border-box; }
        .okm-filtre { display: flex; flex-wrap: wrap; gap: .75rem; align-items: end; margin-bottom: 1rem; }
        .okm-filtre label { display: block; font-size: .75rem; font-weight: 600; margin-bottom: .2rem; }
        .okm-girdi { padding: .4rem .55rem; border-radius: .45rem; border: 1px solid var(--okm-kenar); background: transparent; font-size: .84rem; min-height: 2.4rem; color: inherit; }
        .okm-sekmeler { display: flex; gap: .25rem; flex-wrap: wrap; border-bottom: 1px solid var(--okm-kenar); margin-bottom: 1rem; }
        .okm-sekme { padding: .6rem .95rem; font-weight: 600; font-size: .85rem; border: 0; background: none; color: inherit; opacity: .7; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -1px; min-height: 2.75rem; }
        .okm-sekme:hover { opacity: 1; }
        .okm-sekme[aria-selected="true"] { opacity: 1; border-bottom-color: var(--okm-vurgu); color: var(--okm-vurgu); }
        .okm button:focus-visible, .okm select:focus-visible, .okm input:focus-visible, .okm a:focus-visible { outline: 2px solid var(--okm-vurgu); outline-offset: 2px; }
        .okm-kutu { border: 1px solid var(--okm-kenar); border-radius: .75rem; padding: 1rem; background: transparent; }
        .okm-kutu h3 { font-size: .95rem; font-weight: 700; margin: 0 0 .15rem; }
        .okm-alt { font-size: .76rem; opacity: .7; margin: 0 0 .75rem; }
        .okm-izgara { display: grid; gap: .85rem; }
        .okm-kpi { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
        .okm-iki { grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr)); }
        .okm-sayi { font-size: 1.9rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .okm-etiket { font-size: .78rem; font-weight: 600; opacity: .75; }
        .okm-not { font-size: .72rem; opacity: .65; margin-top: .15rem; }
        .okm-kpi .okm-kutu { border-left: 4px solid var(--kpi, var(--okm-kenar)); }
        .okm table { width: 100%; border-collapse: collapse; font-size: .82rem; }
        .okm th { text-align: left; font-weight: 700; padding: .45rem .5rem; border-bottom: 2px solid var(--okm-kenar); white-space: nowrap; }
        .okm td { padding: .45rem .5rem; border-bottom: 1px solid var(--okm-kenar); vertical-align: top; }
        .okm td.sayi, .okm th.sayi { text-align: right; font-variant-numeric: tabular-nums; }
        .okm-kaydir { overflow-x: auto; }
        .okm-rozet { display: inline-block; padding: .08rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid currentColor; white-space: nowrap; }
        .okm-cubuk { height: .6rem; border-radius: 999px; background: var(--okm-zemin); overflow: hidden; }
        .okm-cubuk > i { display: block; height: 100%; border-radius: 999px; background: var(--okm-vurgu); }
        .okm-satir { display: grid; grid-template-columns: minmax(110px, 38%) 1fr 2.2rem; gap: .5rem; align-items: center; margin-bottom: .45rem; font-size: .8rem; }
        .okm-satir span:last-child { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }
        .okm-bos { text-align: center; padding: 2.5rem 1rem; }
        .okm-bolge-btn { display: grid; grid-template-columns: 1fr auto auto; gap: .6rem; width: 100%; text-align: left; padding: .45rem .6rem; border: 1px solid transparent; border-radius: .5rem; background: none; color: inherit; cursor: pointer; font-size: .82rem; min-height: 2.5rem; align-items: center; }
        .okm-bolge-btn:hover { background: var(--okm-zemin); }
        .okm-bolge-btn[aria-pressed="true"] { border-color: var(--okm-vurgu); background: var(--okm-zemin); font-weight: 700; }
        .okm-vucut { display: grid; grid-template-columns: minmax(300px, 1.35fr) minmax(260px, 1fr); gap: 1.25rem; align-items: start; }
        @media (max-width: 720px) { .okm-vucut { grid-template-columns: 1fr; } .okm-vucut svg { max-height: 420px; margin: 0 auto; } }
        .okm-vucut-svg g.okm-nokta { cursor: pointer; }
        .okm-vucut-svg g.okm-nokta:focus-visible circle { stroke: var(--okm-vurgu); stroke-width: 1.4; }
        .okm-kilcik { display: grid; gap: .7rem; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); }
        .okm-kilcik > div { border: 1px solid var(--okm-kenar); border-radius: .6rem; padding: .65rem .75rem; }
        .okm-kilcik h4 { margin: 0 0 .35rem; font-size: .8rem; font-weight: 700; }
        .okm-kilcik ul { margin: 0; padding-left: 1.05rem; font-size: .8rem; }
        .okm-liste-btn { display: block; width: 100%; text-align: left; padding: .5rem .6rem; border-radius: .5rem; border: 1px solid var(--okm-kenar); background: none; color: inherit; cursor: pointer; margin-bottom: .4rem; min-height: 2.75rem; font-size: .8rem; }
        .okm-liste-btn[aria-pressed="true"] { border-color: var(--okm-vurgu); background: var(--okm-zemin); }
        .okm-ayrim { display: inline-flex; border: 1px solid var(--okm-kenar); border-radius: .5rem; overflow: hidden; }
        .okm-ayrim button { padding: .4rem .9rem; border: 0; background: none; color: inherit; font-weight: 600; font-size: .82rem; cursor: pointer; min-height: 2.4rem; }
        .okm-ayrim button[aria-pressed="true"] { background: var(--okm-vurgu); color: #fff; }
        .okm-uyari { border-left: 4px solid #d97706; padding: .6rem .8rem; background: rgb(217 119 6 / .08); border-radius: .4rem; font-size: .8rem; }
        .okm-link { color: var(--okm-vurgu); font-weight: 600; text-decoration: underline; }
        @media (prefers-reduced-motion: no-preference) { .okm-cubuk > i { transition: width .35s ease-out; } }
        @media print {
            .okm-filtre, .okm-sekmeler, .fi-header-actions, .fi-sidebar, .fi-topbar { display: none !important; }
            .okm-kutu { break-inside: avoid; }
        }
    </style>

    <div class="okm">
        <div class="okm-filtre">
            <div style="min-width:220px;flex:1">
                <label for="okm-firma">Firma</label>
                <select id="okm-firma" class="okm-girdi" style="width:100%" wire:model.live="firmaId">
                    <option value="">Tüm firmalar</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="okm-yil">Yıl</label>
                <select id="okm-yil" class="okm-girdi" wire:model.live="yil">
                    <option value="0">Tüm yıllar</option>
                    @foreach ($this->yillar as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="okm-ay">Ay</label>
                <select id="okm-ay" class="okm-girdi" wire:model.live="ay">
                    <option value="0">Tüm aylar</option>
                    @foreach ($aylar as $i => $ad)
                        <option value="{{ $i + 1 }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="okm-bolum">Bölüm</label>
                <select id="okm-bolum" class="okm-girdi" wire:model.live="bolum">
                    <option value="">Tüm bölümler</option>
                    @foreach ($this->bolumler as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="okm-girdi" style="cursor:pointer;font-weight:600" wire:click="filtreleriTemizle">Filtreleri temizle</button>
        </div>

        @if ($bos)
            <div class="okm-kutu okm-bos">
                <h3>Henüz olay kaydı yok</h3>
                <p class="okm-alt" style="max-width:46ch;margin:.3rem auto 1rem">Panolar, Olay Kayıtları'na girdiğiniz olaylardan oluşur. İlk olayı kaydedin; sayılar ve grafikler burada kendiliğinden görünür.</p>
                <a class="okm-link" href="{{ OlayKayitlari::getUrl() }}">Olay Kayıtları'na git</a>
            </div>
        @else
            <div class="okm-sekmeler" role="tablist" aria-label="Komuta bölümleri">
                @foreach (\App\Filament\Pages\OlayKomuta360::SEKMELER as $anahtar => $ad)
                    <button type="button" role="tab" class="okm-sekme" aria-selected="{{ $this->sekme === $anahtar ? 'true' : 'false' }}" wire:click="sekmeAc('{{ $anahtar }}')">{{ $ad }}</button>
                @endforeach
            </div>

            {{-- ===================== YÖNETİCİ PANOSU ===================== --}}
            @if ($this->sekme === 'pano')
                <div class="okm-izgara okm-kpi" style="margin-bottom:.85rem">
                    @foreach ([
                        ['Toplam olay', $o['toplam'], 'Seçili dönemdeki tüm kayıtlar', '#4f46e5'],
                        ['Kayıtlı yaralanma', $o['yaralanma'], 'Tıbbi müdahale ve üstü', '#d97706'],
                        ['İş günü kayıplı', $o['kayipli'], $sayi($o['kayip_gun']).' gün kayıp', '#ea580c'],
                        ['Ölümlü kaza', $o['olumlu'], $o['olumlu'] ? 'Acil inceleme gerekir' : 'Kayıt yok', '#7f1d1d'],
                        ['İlk yardım', $o['ilk_yardim'], 'Kayıtsız müdahaleler dahil', '#0891b2'],
                        ['Ramak kala', $o['ramak_kala'], 'Önleyici fırsat', '#0f766e'],
                        ['Açık kayıt', $o['acik'], 'Henüz kapanmamış inceleme', '#be185d'],
                        ['İnceleme kapanışı', '%'.$o['kapanis_orani'], 'Kapanan / toplam', '#4f46e5'],
                    ] as [$etiket, $deger, $not, $renk])
                        <div class="okm-kutu" style="--kpi: {{ $renk }}">
                            <div class="okm-etiket">{{ $etiket }}</div>
                            <div class="okm-sayi">{{ $deger }}</div>
                            <div class="okm-not">{{ $not }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="okm-izgara okm-iki">
                    <div class="okm-kutu">
                        <h3>Olay eğilimi</h3>
                        <p class="okm-alt">Aylara göre olaylar ve kayıtlı yaralanmalar{{ $this->yil ? ' · '.$this->yil : ' · tüm yıllar' }}</p>
                        @include('filament.pages.partials.olay-komuta-aylik-grafik', ['aylik' => $this->aylik])
                    </div>

                    <div class="okm-kutu">
                        <h3>Olay sınıflandırması</h3>
                        <p class="okm-alt">Olaya yol açan tehlike kaynağı</p>
                        @if (empty($this->siniflandirma))
                            <p class="okm-alt">Seçili dönemde kayıt yok.</p>
                        @else
                            @php
                                $r = 15.9; $cevre = 2 * M_PI * $r; $ofset = 0;
                            @endphp
                            <div style="display:flex;gap:1.2rem;flex-wrap:wrap;align-items:center">
                                <svg viewBox="0 0 42 42" width="170" height="170" role="img" aria-label="Olay sınıflandırması: {{ collect($this->siniflandirma)->map(fn ($s) => $s['etiket'].' %'.$s['yuzde'])->implode(', ') }}">
                                    <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="rgb(107 114 128 / .15)" stroke-width="5.5"/>
                                    @foreach ($this->siniflandirma as $i => $s)
                                        @php $uzun = $cevre * $s['adet'] / max(1, $o['toplam']); @endphp
                                        <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="{{ $donutRenk[$i % count($donutRenk)] }}" stroke-width="5.5"
                                                stroke-dasharray="{{ round($uzun, 3) }} {{ round($cevre - $uzun, 3) }}" stroke-dashoffset="{{ round(-$ofset, 3) }}" transform="rotate(-90 21 21)"/>
                                        @php $ofset += $uzun; @endphp
                                    @endforeach
                                    <text x="21" y="21.5" text-anchor="middle" font-size="7" font-weight="800" fill="currentColor">{{ $o['toplam'] }}</text>
                                    <text x="21" y="27" text-anchor="middle" font-size="2.8" fill="currentColor" opacity=".7">toplam olay</text>
                                </svg>
                                <ul style="list-style:none;margin:0;padding:0;flex:1;min-width:160px;font-size:.8rem">
                                    @foreach ($this->siniflandirma as $i => $s)
                                        <li style="display:flex;gap:.5rem;align-items:center;margin-bottom:.35rem">
                                            <i style="width:.7rem;height:.7rem;border-radius:2px;background:{{ $donutRenk[$i % count($donutRenk)] }};flex:none"></i>
                                            <span style="flex:1">{{ $s['etiket'] }}</span>
                                            <b>%{{ $s['yuzde'] }}</b>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="okm-kutu">
                        <h3>Sonuç şiddeti</h3>
                        <p class="okm-alt">Olayların insana etkisi</p>
                        @php $enBuyuk = max(1, collect($this->siddetler)->max('adet')); @endphp
                        @foreach ($this->siddetler as $s)
                            <div class="okm-satir">
                                <span>{{ $s['etiket'] }}</span>
                                <div class="okm-cubuk"><i style="width:{{ $s['adet'] / $enBuyuk * 100 }}%;background:{{ $renkSonuc[$s['anahtar']] ?? '#64748b' }}"></i></div>
                                <span>{{ $s['adet'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="okm-kutu">
                        <h3>Yaralanan bölgeler</h3>
                        <p class="okm-alt">En çok etkilenen 5 bölge — ayrıntı için Vücut Haritası sekmesi</p>
                        @php $ilkBolgeler = collect($bolgeVerisi['bolgeler'])->where('adet', '>', 0)->sortByDesc('adet')->take(5); @endphp
                        @forelse ($ilkBolgeler as $b)
                            <div class="okm-satir">
                                <span>{{ $b['ad'] }}</span>
                                <div class="okm-cubuk"><i style="width:{{ $b['yuzde'] }}%"></i></div>
                                <span>{{ $b['adet'] }}</span>
                            </div>
                        @empty
                            <p class="okm-alt">Bölge girilmiş yaralanma yok. Olay Kayıtları'nda "Yaralanan Vücut Bölgesi" alanını doldurun.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- ===================== OLAY ANALİTİĞİ ===================== --}}
            @if ($this->sekme === 'analitik')
                <div class="okm-kutu">
                    <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:end;justify-content:space-between;margin-bottom:.75rem">
                        <div>
                            <h3>Olay kayıt listesi</h3>
                            <p class="okm-alt" style="margin:0">{{ $this->listelenenOlaylar->count() }} kayıt
                                @if ($this->bolge !== '') · bölge: {{ config('isg.olay.vucut_bolgeleri.'.$this->bolge.'.ad') }} <button type="button" class="okm-link" style="background:none;border:0;cursor:pointer" wire:click="bolgeSec('{{ $this->bolge }}')">kaldır</button> @endif
                            </p>
                        </div>
                        <div>
                            <label for="okm-ara" style="font-size:.75rem;font-weight:600;display:block">Ara</label>
                            <input id="okm-ara" type="search" class="okm-girdi" placeholder="Belge no, yer, özet…" wire:model.live.debounce.300ms="arama">
                        </div>
                    </div>
                    <div class="okm-kaydir">
                        <table>
                            <thead><tr><th>Belge no</th><th>Tarih</th><th>Firma / bölüm</th><th>Tür</th><th>Sonuç</th><th>Bölge</th><th class="sayi">Kayıp gün</th><th>Durum</th><th><span class="sr-only">İşlem</span></th></tr></thead>
                            <tbody>
                                @forelse ($this->listelenenOlaylar->take(200) as $k)
                                    <tr>
                                        <td>{{ $k->belge_no }}</td>
                                        <td style="white-space:nowrap">{{ $k->olay_tarihi?->format('d.m.Y') ?? '—' }}</td>
                                        <td>{{ $k->firma?->unvan }}<br><span class="okm-not">{{ $k->bolum ?: '—' }}</span></td>
                                        <td>{{ config('isg.olay.tipler.'.$k->olay_tipi, '—') }}</td>
                                        <td>
                                            @if ($k->sonuc_turu)
                                                <span class="okm-rozet" style="color:{{ $renkSonuc[$k->sonuc_turu] ?? '#64748b' }}">{{ config('isg.olay.sonuc_turleri.'.$k->sonuc_turu, $k->sonuc_turu) }}</span>
                                            @else — @endif
                                        </td>
                                        <td>{{ config('isg.olay.vucut_bolgeleri.'.$k->yaralanan_bolge.'.ad', '—') }}</td>
                                        <td class="sayi">{{ (int) $k->kayip_gun_sayisi }}</td>
                                        <td>{{ config('isg.olay.durumlar.'.$k->durum, $k->durum) }}</td>
                                        <td><button type="button" class="okm-link" style="background:none;border:0;cursor:pointer;white-space:nowrap" wire:click="olayInceleyin({{ $k->id }})">Kök neden</button></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" style="text-align:center;padding:1.5rem;opacity:.7">Bu filtrelerle eşleşen olay yok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($this->listelenenOlaylar->count() > 200)
                        <p class="okm-not">İlk 200 kayıt gösteriliyor; tamamı için Excel'i kullanın.</p>
                    @endif
                </div>
            @endif

            {{-- ===================== VÜCUT HARİTASI ===================== --}}
            @if ($this->sekme === 'vucut')
                <div class="okm-kutu">
                    <h3>Yaralanan vücut bölgeleri</h3>
                    <p class="okm-alt">Oklar en çok yaralanan 8 bölgeyi gösterir; ok rengi o bölgedeki en ağır sonucu belirtir. Bir bölgeye tıklayınca Olay Analitiği listesi o bölgeye göre süzülür.</p>

                    @if ($bolgeVerisi['toplam'] === 0)
                        <p class="okm-uyari">Seçili dönemde vücut bölgesi girilmiş yaralanma yok. Olay Kayıtları'nda yaralanmalı olaylara "Yaralanan Vücut Bölgesi" seçin.</p>
                    @else
                        <div class="okm-vucut">
                            @php
                                // Vücut haritası: en çok vakalı 8 bölge, ok + kartla. Kartlar y'ye göre sıralı dağıtılır (oklar çaprazlanmasın).
                                $S = 1.95; $OX = 202.5; $OY = 14;
                                $anahtarlar = collect($bolgeVerisi['bolgeler'])->filter(fn ($b) => $b['adet'] > 0 && $b['x'] > 0)->sortByDesc('adet')->take(8)->sortBy('y')->keys()->values()->all();
                                $sol = []; $sag = [];
                                foreach ($anahtarlar as $i => $k) { if ($i % 2 === 0) { $sol[] = $k; } else { $sag[] = $k; } }
                                $yanlar = ['sol' => $sol, 'sag' => $sag];
                                $yanSimetrik = ['omuz', 'kol', 'el_bilek', 'parmak'];
                                $baskin = function (array $b) {
                                    foreach (['agir', 'kayip', 'tibbi', 'hafif'] as $g) { if (($b['kirilim'][$g] ?? 0) > 0) { return $g; } }
                                    return 'hafif';
                                };
                            @endphp
                            <svg class="okm-vucut-svg" viewBox="0 0 600 460" role="img" aria-label="Vücut haritası: toplam {{ $bolgeVerisi['toplam'] }} yaralanma vakası" style="width:100%;height:auto;max-height:560px">
                                <defs>
                                    @foreach ($renkGrup as $g => $renk)
                                        <marker id="ok-{{ $g }}" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto"><polygon points="0 0, 8 4, 0 8" fill="{{ $renk }}"/></marker>
                                    @endforeach
                                    <linearGradient id="okm-govde" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0" stop-color="rgb(148 163 184 / .38)"/><stop offset="1" stop-color="rgb(148 163 184 / .16)"/>
                                    </linearGradient>
                                </defs>

                                {{-- İnsan figürü (önden) --}}
                                <g transform="translate({{ $OX }} {{ $OY }}) scale({{ $S }})" fill="url(#okm-govde)" stroke="rgb(100 116 139 / .75)" stroke-width=".7" stroke-linejoin="round" stroke-linecap="round">
                                    <ellipse cx="50" cy="12" rx="9.2" ry="11"/>
                                    <path d="M45.5 21 L45 29 Q40 31 33 33 Q27 35 26 42 L22 66 Q21 72 20 76 L15 96 Q13 101 16 103 Q19 105 21 101 L27 82 L30 62 L31 52 L33 86 Q33 92 36 96 L35 118 L36 158 L34 205 Q31 213 29 216 Q30 220 38 220 L47 219 Q49 214 48 207 L50 150 L52 207 Q51 214 53 219 L62 220 Q70 220 71 216 Q69 213 66 205 L64 158 L65 118 L64 96 Q67 92 67 86 L69 52 L70 62 L73 82 L79 101 Q81 105 84 103 Q87 101 85 96 L80 76 Q79 72 78 66 L74 42 Q73 35 67 33 Q60 31 55 29 L54.5 21 Z"/>
                                    <path d="M31 52 Q50 58 69 52" fill="none" stroke-opacity=".25"/>
                                    <path d="M37 96 Q50 104 63 96" fill="none" stroke-opacity=".3"/>
                                </g>

                                @foreach ($yanlar as $yan => $liste)
                                    @foreach ($liste as $i => $anahtar)
                                        @php
                                            $b = $bolgeVerisi['bolgeler'][$anahtar];
                                            $g = $baskin($b);
                                            $renk = $renkGrup[$g];
                                            $secili = $this->bolge === $anahtar;
                                            $adetSayisi = count($liste);
                                            $kartY = $adetSayisi > 1 ? 14 + $i * ((460 - 28 - 52) / ($adetSayisi - 1)) : 200;
                                            $kartX = $yan === 'sol' ? 4 : 456;
                                            $hx = $b['x']; if ($yan === 'sag' && in_array($anahtar, $yanSimetrik, true)) { $hx = 100 - $hx; }
                                            $tx = $OX + $S * $hx; $ty = $OY + $S * $b['y'];
                                            $ox = $yan === 'sol' ? $kartX + 140 : $kartX;
                                            $oy = $kartY + 26;
                                        @endphp
                                        <g class="okm-nokta" tabindex="0" role="button" aria-pressed="{{ $secili ? 'true' : 'false' }}" aria-label="{{ $b['ad'] }}: {{ $b['adet'] }} vaka"
                                           wire:click="bolgeSec('{{ $anahtar }}')" wire:keydown.enter="bolgeSec('{{ $anahtar }}')" wire:keydown.space.prevent="bolgeSec('{{ $anahtar }}')">
                                            <line x1="{{ $ox }}" y1="{{ $oy }}" x2="{{ round($tx, 1) }}" y2="{{ round($ty, 1) }}" stroke="{{ $renk }}" stroke-width="2" marker-end="url(#ok-{{ $g }})"/>
                                            <circle cx="{{ round($tx, 1) }}" cy="{{ round($ty, 1) }}" r="5" fill="{{ $renk }}" fill-opacity=".25" stroke="{{ $renk }}" stroke-width="1.2"/>
                                            <rect x="{{ $kartX }}" y="{{ $kartY }}" width="140" height="52" rx="10" fill="{{ $secili ? 'rgb(79 70 229 / .14)' : 'rgb(148 163 184 / .10)' }}" stroke="{{ $secili ? '#4f46e5' : $renk }}" stroke-width="{{ $secili ? 2.2 : 1.4 }}"/>
                                            <circle cx="{{ $kartX + 22 }}" cy="{{ $kartY + 26 }}" r="14" fill="{{ $renk }}"/>
                                            <text x="{{ $kartX + 22 }}" y="{{ $kartY + 31 }}" text-anchor="middle" font-size="14" font-weight="800" fill="#fff" pointer-events="none">{{ $b['adet'] }}</text>
                                            <text x="{{ $kartX + 44 }}" y="{{ $kartY + 23 }}" font-size="12.5" font-weight="700" fill="currentColor" pointer-events="none">{{ \Illuminate\Support\Str::limit($b['ad'], 14, '') }}</text>
                                            <text x="{{ $kartX + 44 }}" y="{{ $kartY + 40 }}" font-size="11" fill="currentColor" opacity=".75" pointer-events="none">%{{ rtrim(rtrim(number_format($b['yuzde'], 1, ',', ''), '0'), ',') }} · {{ $b['adet'] }} vaka</text>
                                        </g>
                                    @endforeach
                                @endforeach
                            </svg>

                            <div>
                                <div class="okm-kaydir">
                                    <table>
                                        <caption class="sr-only">Vücut bölgesine göre şiddet tablosu</caption>
                                        <thead>
                                            <tr>
                                                <th>Bölge</th><th class="sayi">Vaka</th><th class="sayi">%</th>
                                                @foreach (\App\Support\OlayKomuta::SIDDET_GRUPLARI as $g => [$kisa, $ad])
                                                    <th class="sayi" title="{{ $ad }}" style="color:{{ $renkGrup[$g] }}">{{ $kisa }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach (collect($bolgeVerisi['bolgeler'])->where('adet', '>', 0)->sortByDesc('adet') as $anahtar => $b)
                                                <tr>
                                                    <td style="padding:0"><button type="button" class="okm-bolge-btn" style="grid-template-columns:1fr" aria-pressed="{{ $this->bolge === $anahtar ? 'true' : 'false' }}" wire:click="bolgeSec('{{ $anahtar }}')">{{ $b['ad'] }}</button></td>
                                                    <td class="sayi"><b>{{ $b['adet'] }}</b></td>
                                                    <td class="sayi">%{{ rtrim(rtrim(number_format($b['yuzde'], 1, ',', ''), '0'), ',') }}</td>
                                                    @foreach ($b['kirilim'] as $g => $adet)
                                                        <td class="sayi" style="{{ $adet ? 'font-weight:700;color:'.$renkGrup[$g] : 'opacity:.35' }}">{{ $adet }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot><tr><td><b>Toplam yaralanma vakası</b></td><td class="sayi"><b>{{ $bolgeVerisi['toplam'] }}</b></td><td colspan="5"></td></tr></tfoot>
                                    </table>
                                </div>
                                <p class="okm-not" style="margin-top:.6rem">
                                    @foreach (\App\Support\OlayKomuta::SIDDET_GRUPLARI as $g => [$kisa, $ad])
                                        <b style="color:{{ $renkGrup[$g] }}">{{ $kisa }}</b> = {{ $ad }}@if (! $loop->last) · @endif
                                    @endforeach
                                </p>
                                @if (($bolgeVerisi['bolgeler']['genel']['adet'] ?? 0) > 0)
                                    <p class="okm-not">"Birden çok bölge / sistemik" kayıtları haritada gösterilmez; yalnız tabloda sayılır.</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ===================== KÖK NEDEN ===================== --}}
            @if ($this->sekme === 'kok_neden')
                <div class="okm-izgara okm-iki">
                    <div class="okm-kutu">
                        <h3>En sık kök neden kategorileri</h3>
                        <p class="okm-alt">Olay Kayıtları'ndaki kök neden seçimlerinden</p>
                        @php $enBuyukKok = max(1, collect($this->kokNedenler)->max('adet')); @endphp
                        @forelse ($this->kokNedenler as $kn)
                            <div class="okm-satir">
                                <span>{{ $kn['etiket'] }}</span>
                                <div class="okm-cubuk"><i style="width:{{ $kn['adet'] / $enBuyukKok * 100 }}%"></i></div>
                                <span>{{ $kn['adet'] }}</span>
                            </div>
                        @empty
                            <p class="okm-alt">Kök neden kategorisi girilmiş olay yok.</p>
                        @endforelse
                    </div>

                    <div class="okm-kutu">
                        <h3>Olay seçin</h3>
                        <p class="okm-alt">Balık kılçığı ve 5 Neden zinciri seçilen olay için gösterilir</p>
                        <div style="max-height:19rem;overflow-y:auto">
                            @forelse ($this->olaylar->take(60) as $k)
                                <button type="button" class="okm-liste-btn" aria-pressed="{{ $this->incelenenOlayId === $k->id ? 'true' : 'false' }}" wire:click="$set('incelenenOlayId', {{ $k->id }})">
                                    <b>{{ $k->belge_no }}</b> · {{ $k->olay_tarihi?->format('d.m.Y') }}
                                    @if ($k->balikKilcigiDoluMu()) <span class="okm-rozet" style="color:#0f766e;margin-left:.3rem">Kılçık dolu</span> @endif
                                    <br><span class="okm-not">{{ \Illuminate\Support\Str::limit($k->olay_ozeti, 90) }}</span>
                                </button>
                            @empty
                                <p class="okm-alt">Seçili dönemde olay yok.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                @if ($k = $this->incelenenOlay)
                    <div class="okm-kutu" style="margin-top:.85rem">
                        <h3>{{ $k->belge_no }} — balık kılçığı (Ishikawa)</h3>
                        <p class="okm-alt">{{ config('isg.olay.tipler.'.$k->olay_tipi) }} · {{ $k->olay_tarihi?->format('d.m.Y') }} · {{ $k->olay_yeri ?: 'yer belirtilmemiş' }}</p>
                        <p style="margin:0 0 .8rem"><b>Olay:</b> {{ $k->olay_ozeti ?: '—' }}</p>

                        @if ($k->balikKilcigiDoluMu())
                            <div class="okm-kilcik">
                                @foreach ($k->balikKilcigiKategorileri() as $kat)
                                    <div>
                                        <h4>{{ $kat['etiket'] }}</h4>
                                        @if ($kat['nedenler'])
                                            <ul>@foreach ($kat['nedenler'] as $n)<li>{{ $n }}</li>@endforeach</ul>
                                        @else
                                            <span class="okm-not">Neden girilmemiş</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="okm-uyari">Bu olay için balık kılçığı doldurulmamış. Olay Kayıtları'nda 5 Neden'den otomatik oluşturabilirsiniz.</p>
                        @endif

                        @if ($zincir = $k->nedenZinciri())
                            <h3 style="margin-top:1rem">5 Neden zinciri</h3>
                            <ol style="margin:.3rem 0 0;padding-left:1.2rem;font-size:.84rem">
                                @foreach ($zincir as $z)<li style="margin-bottom:.2rem">{{ is_array($z) ? ($z['neden'] ?? json_encode($z, JSON_UNESCAPED_UNICODE)) : $z }}</li>@endforeach
                            </ol>
                        @endif
                        @if (filled($k->kok_neden))
                            <p style="margin:.8rem 0 0"><b>Kök neden:</b> {{ $k->kok_neden }}</p>
                        @endif
                    </div>
                @endif
            @endif

            {{-- ===================== DÖF TAKİBİ ===================== --}}
            @if ($this->sekme === 'dof')
                @php $d = $this->dofOzeti; @endphp
                <div class="okm-izgara okm-kpi" style="margin-bottom:.85rem">
                    @foreach ([
                        ['DÖF maddesi', $d['toplam'], 'Tüm DÖF raporlarında', '#4f46e5'],
                        ['Açık madde', $d['acik'], 'Tamamlanmamış', '#d97706'],
                        ['Süresi geçen', $d['geciken'], $d['geciken'] ? 'Termini aşıldı' : 'Geciken yok', '#dc2626'],
                        ['Kapanış oranı', '%'.$d['oran'], $d['kapanan'].' madde tamamlandı', '#0f766e'],
                    ] as [$etiket, $deger, $not, $renk])
                        <div class="okm-kutu" style="--kpi: {{ $renk }}">
                            <div class="okm-etiket">{{ $etiket }}</div>
                            <div class="okm-sayi">{{ $deger }}</div>
                            <div class="okm-not">{{ $not }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="okm-kutu">
                    <h3>Açık DÖF maddeleri</h3>
                    <p class="okm-alt">Süresi geçenler önce, sonra öncelik sırasıyla. Madde kapatma DÖF Oluştur sayfasındadır. (Olaya bağlı DÖF: {{ $this->olaylar->whereNotNull('dof_raporu_id')->count() }} olay)</p>
                    @php
                        $sira = ['kritik' => 0, 'yuksek' => 1, 'orta' => 2, 'dusuk' => 3];
                        $acikMaddeler = $this->dofMaddeleri->where('acik', true)
                            ->sortBy(fn ($m) => [$m['geciken'] ? 0 : 1, $sira[$m['oncelik']] ?? 9])->take(40);
                    @endphp
                    <div class="okm-kaydir">
                        <table>
                            <thead><tr><th>Belge</th><th>Firma</th><th>Tespit</th><th>Öncelik</th><th>Sorumlu</th><th>Termin</th></tr></thead>
                            <tbody>
                                @forelse ($acikMaddeler as $m)
                                    <tr>
                                        <td>{{ $m['belge_no'] }}</td>
                                        <td>{{ $m['firma'] }}</td>
                                        <td style="max-width:34rem">{{ \Illuminate\Support\Str::limit($m['baslik'], 140) }}</td>
                                        <td><span class="okm-rozet" style="color:{{ ['kritik' => '#dc2626', 'yuksek' => '#ea580c', 'orta' => '#d97706', 'dusuk' => '#0f766e'][$m['oncelik']] ?? '#64748b' }}">{{ config('isg.dof.oncelikler.'.$m['oncelik'], ucfirst($m['oncelik'])) }}</span></td>
                                        <td>{{ $m['sorumlu'] }}</td>
                                        <td style="white-space:nowrap">
                                            @if ($m['termin']) {{ $m['termin']->format('d.m.Y') }} @else Derhal / belirsiz @endif
                                            @if ($m['geciken']) <span class="okm-rozet" style="color:#dc2626;margin-left:.3rem">Gecikti</span> @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" style="text-align:center;padding:1.5rem;opacity:.7">Açık DÖF maddesi yok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ===================== AYLIK & YILLIK RAPOR ===================== --}}
            @if ($this->sekme === 'rapor')
                @php $or = $this->oranlar; $cal = $this->calisma; @endphp
                <div style="margin-bottom:.85rem">
                    <div class="okm-ayrim" role="group" aria-label="Rapor türü">
                        <button type="button" aria-pressed="{{ $this->raporModu === 'aylik' ? 'true' : 'false' }}" wire:click="$set('raporModu','aylik')">Aylık pano</button>
                        <button type="button" aria-pressed="{{ $this->raporModu === 'yillik' ? 'true' : 'false' }}" wire:click="$set('raporModu','yillik')">Yıllık pano</button>
                    </div>
                </div>

                <div class="okm-izgara okm-kpi" style="margin-bottom:.85rem">
                    @foreach ([
                        ['TRIR (200 bin saat)', $or['trir'] === null ? '—' : number_format($or['trir'], 2, ',', '.'), 'Kayıtlı yaralanma × 200.000 / saat', '#4f46e5'],
                        ['LTIFR (1 milyon saat)', $or['ltifr'] === null ? '—' : number_format($or['ltifr'], 2, ',', '.'), 'İş günü kayıplı × 1.000.000 / saat', '#ea580c'],
                        ['Ortalama çalışan', $cal['calisan'] ?: '—', 'Kaza İstatistikleri verisinden', '#0891b2'],
                        ['Çalışma saati', $cal['saat'] ? $sayi($cal['saat']) : '—', 'Kaza İstatistikleri verisinden', '#0f766e'],
                    ] as [$etiket, $deger, $not, $renk])
                        <div class="okm-kutu" style="--kpi: {{ $renk }}">
                            <div class="okm-etiket">{{ $etiket }}</div>
                            <div class="okm-sayi">{{ $deger }}</div>
                            <div class="okm-not">{{ $not }}</div>
                        </div>
                    @endforeach
                </div>
                @if ($cal['saat'] === 0)
                    <p class="okm-uyari" style="margin-bottom:.85rem">TRIR ve LTIFR için çalışma saati gerekir. <a class="okm-link" href="{{ KazaIstatistikleri::getUrl() }}">Kaza İstatistikleri</a> sayfasında aylık çalışan ve çalışma saatini girin.</p>
                @endif

                @if ($this->raporModu === 'aylik')
                    <div class="okm-izgara okm-iki">
                        <div class="okm-kutu">
                            <h3>Aylık olay ve yaralanma dağılımı</h3>
                            <p class="okm-alt">{{ $this->yil ?: 'Tüm yıllar' }}</p>
                            @include('filament.pages.partials.olay-komuta-aylik-grafik', ['aylik' => $this->aylik])
                        </div>
                        <div class="okm-kutu">
                            <h3>Aylık tablo</h3>
                            <div class="okm-kaydir">
                                <table>
                                    <thead><tr><th>Ay</th><th class="sayi">Olay</th><th class="sayi">Yaralanma</th><th class="sayi">Kayıp gün</th></tr></thead>
                                    <tbody>
                                        @foreach ($this->aylik as $a)
                                            <tr><td>{{ $a['ay'] }}</td><td class="sayi">{{ $a['olay'] }}</td><td class="sayi">{{ $a['yaralanma'] }}</td><td class="sayi">{{ $a['kayip_gun'] }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="okm-kutu">
                        <h3>Yıllık karşılaştırma</h3>
                        <p class="okm-alt">Firma ve bölüm filtresine göre, tüm yıllar</p>
                        <div class="okm-kaydir">
                            <table>
                                <thead><tr><th>Yıl</th><th class="sayi">Olay</th><th class="sayi">Yaralanma</th><th class="sayi">İş günü kayıplı</th><th class="sayi">Kayıp gün</th><th class="sayi">Ramak kala</th></tr></thead>
                                <tbody>
                                    @foreach ($this->yillikTablo as $y)
                                        <tr><td><b>{{ $y['yil'] }}</b></td><td class="sayi">{{ $y['olay'] }}</td><td class="sayi">{{ $y['yaralanma'] }}</td><td class="sayi">{{ $y['kayipli'] }}</td><td class="sayi">{{ $y['kayip_gun'] }}</td><td class="sayi">{{ $y['ramak_kala'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="okm-kutu" style="margin-top:.85rem">
                    <h3>Bölüm güvenlik karnesi</h3>
                    <p class="okm-alt">İş günü kayıplı olay varsa "Riskli"; kapanış %70'in altındaysa "İzleniyor"</p>
                    <div class="okm-kaydir">
                        <table>
                            <thead><tr><th>Bölüm</th><th class="sayi">Olay</th><th class="sayi">Yaralanma</th><th class="sayi">Kapanış</th><th>Durum</th></tr></thead>
                            <tbody>
                                @foreach ($this->karne as $b)
                                    <tr>
                                        <td>{{ $b['bolum'] }}</td><td class="sayi">{{ $b['olay'] }}</td><td class="sayi">{{ $b['yaralanma'] }}</td><td class="sayi">%{{ $b['kapanis'] }}</td>
                                        <td><span class="okm-rozet" style="color:{{ ['Riskli' => '#dc2626', 'İzleniyor' => '#d97706', 'Uyumlu' => '#0f766e'][$b['durum']] }}">{{ $b['durum'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
