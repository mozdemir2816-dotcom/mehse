@php
    $sekmeler = array_diff_key(\App\Filament\Pages\Profilim::SEKMELER, array_flip(\App\Filament\Pages\Profilim::GIZLI_SEKMELER));
    $u = $this->kullanici;
    $o = $this->ozet;
    $gorsel = $this->gorselDurumu();
    $mor = 'rgb(139 92 246)';
    $indigo = 'rgb(99 102 241)';
    $turkuaz = 'rgb(20 184 166)';
    $yesil = 'rgb(34 197 94)';
    $sari = 'rgb(245 158 11)';
    $kirmizi = 'rgb(239 68 68)';
    $gri = 'rgb(95 87 112)';
    $kutu = 'border:1px solid rgb(128 116 148 / .3);border-radius:.75rem;padding:1rem';
    // Kartlar: yuvarlak köşe + yumuşak gölge (Profilim yeniden tasarımı, 29.10.2026).
    $golge = 'border:1px solid rgb(128 116 148 / .2);border-radius:1rem;padding:1rem;box-shadow:0 1px 3px rgb(15 23 42 / .08)';
    $bas = strtoupper(mb_substr($u->name ?? '?', 0, 1));
    $say = fn ($n) => number_format((int) $n, 0, ',', '.');
    $onemliEsik = (int) config('isg.onemli_risk_esigi', 140);
@endphp

<x-filament-panels::page>
    {{-- ==================== HERO: PROFİL & YASAL YETKİ ==================== --}}
    <div style="{{ $golge }};display:flex;gap:1.25rem 2rem;flex-wrap:wrap;align-items:center;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:1rem;flex:1 1 340px;min-width:260px">
            <div style="width:4rem;height:4rem;border-radius:9999px;background:linear-gradient(135deg,#0F172A,#0284C7);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;flex-shrink:0">{{ $bas }}</div>
            <div style="min-width:0">
                <div style="font-size:1.2rem;font-weight:800;line-height:1.2">{{ $u->name }}</div>
                <div style="font-size:.82rem;font-weight:600;color:#0369a1;margin-top:.1rem">{{ $u->unvanEtiketi() }}</div>
                <div style="display:flex;flex-wrap:wrap;gap:.2rem .9rem;font-size:.78rem;color:{{ $gri }};margin-top:.3rem">
                    <span style="display:inline-flex;align-items:center;gap:.3rem"><x-filament::icon icon="heroicon-o-envelope" style="width:.9rem;height:.9rem"/>{{ $u->email }}</span>
                    @if ($u->telefon)<span style="display:inline-flex;align-items:center;gap:.3rem"><x-filament::icon icon="heroicon-o-phone" style="width:.9rem;height:.9rem"/>{{ $u->telefon }}</span>@endif
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.45rem">
                    <x-filament::badge color="success">● İSG-KATİP: Aktif</x-filament::badge>
                    <x-filament::badge :color="match ($gorsel['kase']) { 'yuklu' => 'success', default => 'warning' }">
                        {{ match ($gorsel['kase']) { 'yuklu' => 'Kaşe Yüklü', 'dosya_yok' => 'Kaşe dosyası bulunamadı', default => 'Kaşe eksik' } }}
                    </x-filament::badge>
                </div>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:.9rem 1rem;flex-wrap:wrap">
            {{-- Kaşe / imza önizleme avatarları --}}
            <div style="display:flex;gap:.6rem">
                @foreach ([['kase', 'Kaşe'], ['imza', 'İmza']] as [$tur, $ad])
                    <div style="text-align:center">
                        <div style="width:4rem;height:4rem;border-radius:.75rem;border:1px dashed {{ $gorsel[$tur] === 'yuklu' ? 'rgb(34 197 94 / .6)' : 'rgb(128 116 148 / .45)' }};background:rgb(128 116 148 / .07);display:flex;align-items:center;justify-content:center;overflow:hidden">
                            @if ($gorsel[$tur] === 'yuklu')
                                <img src="{{ route('mehse.uzman-gorsel', ['tur' => $tur]) }}?v={{ md5((string) $u->{$tur.'_gorseli'}) }}" alt="{{ $ad }}" style="max-width:100%;max-height:100%;object-fit:contain">
                            @else
                                <x-filament::icon :icon="$tur === 'kase' ? 'heroicon-o-finger-print' : 'heroicon-o-pencil'" style="width:1.4rem;height:1.4rem;color:{{ $gri }}"/>
                            @endif
                        </div>
                        <div style="font-size:.68rem;margin-top:.2rem;color:{{ $gorsel[$tur] === 'yuklu' ? 'rgb(21 128 61)' : $gri }}">
                            {{ $ad }}{{ $gorsel[$tur] === 'yuklu' ? ' ✓' : ($gorsel[$tur] === 'dosya_yok' ? ' (dosya yok)' : '') }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                {{ $this->kaseBilgisiAction }}
                {{ $this->unvanAyariAction }}
                {{ $this->excelPanosuAction }}
            </div>
        </div>
    </div>

    {{-- ==================== KPI ŞERİDİ (mükerrer sayaçlar tek satırda) ==================== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.7rem">
        @foreach ([
            ['Aktif Firma', $o['firma'], 'heroicon-o-building-office-2', $indigo, null],
            ['Çalışan', $o['calisan'], 'heroicon-o-user-group', $yesil, null],
            ['Risk Analizi', $o['risk_degerlendirmesi'], 'heroicon-o-sparkles', $sari, null],
            ['Sektör Şablonu', $o['risk_sablonu'], 'heroicon-o-rectangle-stack', $mor, null],
            ['Kritik Risk Maddesi', $o['onemli_risk'], 'heroicon-o-exclamation-triangle', $kirmizi, 'Skor > '.$onemliEsik],
        ] as [$etiket, $deger, $ikon, $renk, $alt])
            <div style="{{ $golge }};padding:.8rem 1rem;{{ $alt ? 'background:rgb(244 63 94 / .06);border-color:rgb(244 63 94 / .35)' : '' }}">
                <div style="display:flex;align-items:center;gap:.5rem">
                    <div style="width:1.8rem;height:1.8rem;border-radius:9999px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb, {{ $renk }} 15%, transparent)">
                        <x-filament::icon :icon="$ikon" style="width:1rem;height:1rem;color:{{ $renk }}"/>
                    </div>
                    <div style="font-size:.75rem;color:{{ $gri }};font-weight:600">{{ $etiket }}</div>
                </div>
                <div style="font-size:1.6rem;font-weight:800;color:{{ $renk }};margin-top:.3rem;line-height:1.1">{{ $say($deger) }}</div>
                @if ($alt)<div style="font-size:.75rem;color:{{ $gri }}">{{ $alt }}</div>@endif
            </div>
        @endforeach
    </div>

    @if ($o['calisansiz_firma'] > 0 || $o['evrak_eksigi'] > 0)
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;font-size:.76rem">
            @if ($o['calisansiz_firma'] > 0)
                <span style="padding:.2rem .7rem;border-radius:9999px;background:rgb(245 158 11 / .12);color:rgb(180 83 9)">⚠ {{ $o['calisansiz_firma'] }} firmada aktif çalışan eklenmemiş</span>
            @endif
            @if ($o['evrak_eksigi'] > 0)
                <span style="padding:.2rem .7rem;border-radius:9999px;background:rgb(245 158 11 / .12);color:rgb(180 83 9)">⚠ {{ $o['evrak_eksigi'] }} firmada risk değerlendirmesi yok</span>
            @endif
        </div>
    @endif

    {{-- SEKME PILL'LERİ --}}
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;background:rgb(128 116 148 / .1);padding:.3rem;border-radius:.6rem">
        @foreach ($sekmeler as $anahtar => $ad)
            @php $aktif = $sekme === $anahtar; @endphp
            <button type="button" wire:click="sekmeSec('{{ $anahtar }}')"
                style="padding:.5rem .85rem;border:none;border-radius:.45rem;cursor:pointer;font-weight:600;font-size:.83rem;
                    background:{{ $aktif ? $turkuaz : 'transparent' }};color:{{ $aktif ? '#fff' : 'inherit' }}">
                {{ $ad }}
            </button>
        @endforeach
    </div>

    {{-- ==================== GENEL BAKIŞ ==================== --}}
    @if ($sekme === 'genel')
        @php
            // ---- Aktivite: son 30 gün yumuşak eğri ----
            $akt = $this->aktivite;
            $son30 = array_values(array_slice($akt, -30, null, true));
            $enYuksek = max(1, max($son30 ?: [0]));
            $n30 = count($son30);
            $pts = [];
            foreach ($son30 as $i => $deger) {
                $pts[] = [$n30 > 1 ? round($i / ($n30 - 1) * 100, 2) : 0, round(34 - ($deger / $enYuksek) * 30, 2)];
            }
            $egri = $pts ? 'M'.$pts[0][0].','.$pts[0][1] : '';
            for ($i = 1; $i < $n30; $i++) {
                $cx = round(($pts[$i - 1][0] + $pts[$i][0]) / 2, 2);
                $egri .= " C{$cx},{$pts[$i - 1][1]} {$cx},{$pts[$i][1]} {$pts[$i][0]},{$pts[$i][1]}";
            }
            $alan = $egri ? $egri.' L100,40 L0,40 Z' : '';

            // ---- Heatmap: hafta sütunlu (GitHub tarzı), Pzt başlangıç ----
            $enYuksekAkt = max(1, max($akt ?: [0]));
            $ilkGun = $akt ? \Illuminate\Support\Carbon::parse(array_key_first($akt)) : null;
            $bosluk = $ilkGun ? $ilkGun->dayOfWeekIso - 1 : 0;
            $hRenk = ['rgb(128 116 148 / .18)', 'rgb(99 102 241 / .3)', 'rgb(99 102 241 / .55)', 'rgb(99 102 241 / .8)', 'rgb(99 102 241)'];
            $toplam90 = array_sum($akt);

            // ---- Radar ----
            $eksenler = $this->performansEksenleri;
            $n = count($eksenler);
            $merkez = 50; $yaricap = 40;
            $noktaHesapla = fn (float $yuzde, int $i) => [
                $merkez + $yaricap * ($yuzde / 100) * cos((-90 + $i * 360 / $n) * M_PI / 180),
                $merkez + $yaricap * ($yuzde / 100) * sin((-90 + $i * 360 / $n) * M_PI / 180),
            ];
            $poligon = collect($eksenler)->values()->map(fn ($y, $i) => implode(',', $noktaHesapla($y, $i)))->implode(' ');
            $izgara = fn (float $yuzde) => collect(range(0, $n - 1))->map(fn ($i) => implode(',', $noktaHesapla($yuzde, $i)))->implode(' ');

            $uyum = $o['uyum_yuzde'];
            $uyumRenk = $uyum < 40 ? $kirmizi : ($uyum < 75 ? $sari : $yesil);
            $renkler = [$yesil, $sari, $kirmizi];
        @endphp

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,440px),1fr));gap:1rem;align-items:start">
            {{-- SOL: Yasal Uyum & Risk Dağılımı --}}
            <div style="{{ $golge }};display:flex;flex-direction:column;gap:1.1rem">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem">
                    <div style="font-weight:800">Yasal Uyum &amp; Risk Dağılımı</div>
                    <span style="font-size:.75rem;font-weight:700;padding:.15rem .65rem;border-radius:9999px;color:{{ $uyumRenk }};background:color-mix(in srgb, {{ $uyumRenk }} 14%, transparent)" title="Takip edilen yasal kriterlerin portföy geneli ortalama karşılanma oranı">Portföy uyumu %{{ $uyum }}</span>
                </div>

                <div>
                    <div style="font-size:.78rem;font-weight:700;margin-bottom:.4rem">Tehlike Sınıfı Dağılımı <span style="font-weight:400;color:{{ $gri }}">· {{ $o['firma'] }} firma</span></div>
                    @php $ri = 0; @endphp
                    @foreach ($o['tehlike_dagilimi'] as $ad => $sayi)
                        @php $yuzde = $o['firma'] > 0 ? round($sayi / $o['firma'] * 100) : 0; $c = $renkler[$ri++ % 3]; @endphp
                        <div style="margin-bottom:.45rem">
                            <div style="display:flex;justify-content:space-between;font-size:.8rem">
                                <span>{{ $ad }}</span><strong>{{ $sayi }} · %{{ $yuzde }}</strong>
                            </div>
                            <div style="height:7px;border-radius:9999px;background:rgb(128 116 148 / .18);margin-top:.2rem;overflow:hidden">
                                <div style="height:100%;width:{{ max($yuzde, 1) }}%;background:{{ $c }};border-radius:9999px"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;border:1px solid rgb(16 185 129 / .35);background:rgb(16 185 129 / .07);border-radius:.8rem;padding:.7rem .9rem">
                    <div>
                        <div style="font-size:.78rem;font-weight:700">İlk Yardımcı İhtiyacı</div>
                        <div style="font-size:.75rem;color:{{ $gri }};line-height:1.35;margin-top:.15rem">
                            İlkyardım Yönetmeliği md.19 · çok tehlikeli her 10, tehlikeli her 15, az tehlikeli her 20 çalışana 1 kişi
                        </div>
                    </div>
                    <span style="flex-shrink:0;font-size:1.3rem;font-weight:800;color:rgb(4 120 87);padding:.15rem .8rem;border-radius:9999px;background:rgb(16 185 129 / .16)">{{ $this->ilkyardimciIhtiyaci }} kişi</span>
                </div>

                <div>
                    <div style="font-size:.78rem;font-weight:700;margin-bottom:.2rem">Performans Profili <span style="font-weight:400;color:{{ $gri }}">· kurulu modüllerin portföy ortalaması</span></div>
                    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                        <svg viewBox="0 0 100 100" style="width:190px;max-width:100%;flex-shrink:0">
                            @foreach ([25, 50, 75, 100] as $g)
                                <polygon points="{{ $izgara($g) }}" fill="none" stroke="rgb(128 116 148 / .22)" stroke-width="0.5"/>
                            @endforeach
                            @foreach (range(0, $n - 1) as $i)
                                @php [$x, $y] = $noktaHesapla(100, $i); @endphp
                                <line x1="{{ $merkez }}" y1="{{ $merkez }}" x2="{{ $x }}" y2="{{ $y }}" stroke="rgb(128 116 148 / .22)" stroke-width="0.5"/>
                            @endforeach
                            <polygon points="{{ $poligon }}" fill="{{ $turkuaz }}" fill-opacity="0.25" stroke="{{ $turkuaz }}" stroke-width="1.5"/>
                        </svg>
                        <div style="display:flex;flex-direction:column;gap:.3rem;flex:1;min-width:150px">
                            @foreach ($eksenler as $ad => $yuzde)
                                <div style="display:flex;justify-content:space-between;font-size:.76rem">
                                    <span style="color:{{ $gri }}">{{ $ad }}</span><strong>%{{ $yuzde }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- SAĞ: Saha & Denetim Aktivite Yoğunluğu --}}
            <div style="{{ $golge }};display:flex;flex-direction:column;gap:1.1rem">
                <div>
                    <div style="font-weight:800">Saha &amp; Denetim Aktivite Yoğunluğu</div>
                    <div style="font-size:.75rem;color:{{ $gri }}">Risk / şablon / firma / çalışan eklemeleri</div>
                </div>

                <div>
                    <div style="display:flex;justify-content:space-between;font-size:.78rem;font-weight:700;margin-bottom:.2rem">
                        <span>Dönemsel Trend <span style="font-weight:400;color:{{ $gri }}">· son 30 gün</span></span>
                        <span style="font-weight:400;color:{{ $gri }}">toplam {{ array_sum($son30) }} · en yoğun gün {{ max($son30 ?: [0]) }}</span>
                    </div>
                    <svg viewBox="0 0 100 40" preserveAspectRatio="none" style="width:100%;height:110px;display:block">
                        <defs>
                            <linearGradient id="trendDolgu" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="{{ $indigo }}" stop-opacity=".3"/>
                                <stop offset="100%" stop-color="{{ $indigo }}" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        @if ($alan)
                            <path d="{{ $alan }}" fill="url(#trendDolgu)"/>
                            <path d="{{ $egri }}" fill="none" stroke="{{ $indigo }}" stroke-width="1.6" vector-effect="non-scaling-stroke" stroke-linecap="round"/>
                        @endif
                    </svg>
                </div>

                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-bottom:.45rem">
                        <div style="font-size:.78rem;font-weight:700">Son 90 Gün Aktivite <span style="font-weight:400;color:{{ $gri }}">· {{ $toplam90 }} işlem</span></div>
                        <div style="display:flex;align-items:center;gap:.25rem;font-size:.75rem;color:{{ $gri }}">
                            Az
                            @foreach ($hRenk as $c)<span style="width:.7rem;height:.7rem;border-radius:2px;background:{{ $c }}"></span>@endforeach
                            Çok
                        </div>
                    </div>
                    <div style="display:flex;gap:.4rem;justify-content:center;overflow-x:auto;padding-bottom:.2rem">
                        <div style="display:grid;grid-template-rows:repeat(7,15px);gap:4px;font-size:.68rem;color:{{ $gri }};line-height:15px;text-align:right">
                            @foreach (['Pzt', '', 'Çar', '', 'Cum', '', ''] as $etiket)<span>{{ $etiket }}</span>@endforeach
                        </div>
                        <div style="display:grid;grid-template-rows:repeat(7,15px);grid-auto-flow:column;grid-auto-columns:15px;gap:4px">
                            @for ($b = 0; $b < $bosluk; $b++)<span></span>@endfor
                            @foreach ($akt as $gun => $sayi)
                                @php $seviye = $sayi === 0 ? 0 : (int) ceil($sayi / $enYuksekAkt * 4); @endphp
                                <span title="{{ \Illuminate\Support\Carbon::parse($gun)->format('d.m.Y') }} — {{ $sayi }} işlem"
                                    style="border-radius:3px;background:{{ $hRenk[$seviye] }}"></span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== EN YÜKSEK ÇALIŞANLI VE RİSKLİ İLK 5 FİRMA ==================== --}}
        <div style="{{ $golge }}">
            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem">
                <div style="font-weight:800">En Yüksek Çalışanlı ve Riskli İlk 5 Firma</div>
                <div style="font-size:.75rem;color:{{ $gri }}">Sıralama: çalışan sayısı × kritik risk maddesi (skor &gt; {{ $onemliEsik }})</div>
            </div>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem;min-width:480px">
                    <thead>
                        <tr style="text-align:left;color:{{ $gri }};font-size:.75rem;text-transform:uppercase;letter-spacing:.04em">
                            <th style="padding:.4rem">Firma</th>
                            <th style="padding:.4rem;text-align:right">Çalışan</th>
                            <th style="padding:.4rem;text-align:right">Kritik risk</th>
                            <th style="padding:.4rem;text-align:right">Hızlı git</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->oncelikliFirmalar as $f)
                            <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                                <td style="padding:.5rem .4rem;font-weight:600">{{ \Illuminate\Support\Str::limit($f['unvan'], 48) }}</td>
                                <td style="padding:.5rem .4rem;text-align:right">{{ $say($f['calisan']) }}</td>
                                <td style="padding:.5rem .4rem;text-align:right">
                                    <span style="font-size:.75rem;font-weight:700;padding:.1rem .55rem;border-radius:9999px;{{ $f['kritik_risk'] > 0 ? 'color:rgb(190 18 60);background:rgb(244 63 94 / .1)' : 'color:'.$gri.';background:rgb(128 116 148 / .12)' }}">{{ $say($f['kritik_risk']) }}</span>
                                </td>
                                <td style="padding:.5rem .4rem;text-align:right;white-space:nowrap">
                                    <a href="{{ \App\Filament\Pages\RiskMerkezi::getUrl(['firma' => $f['id']]) }}" style="font-size:.75rem;font-weight:600;padding:.2rem .65rem;border-radius:9999px;border:1px solid rgb(2 132 199 / .4);color:#0369A1;text-decoration:none">Risk →</a>
                                    <a href="{{ \App\Filament\Pages\FirmaDenetimOperasyon::getUrl(['firma' => $f['id']]) }}" style="font-size:.75rem;font-weight:600;padding:.2rem .65rem;border-radius:9999px;border:1px solid rgb(128 116 148 / .4);color:inherit;text-decoration:none">Denetim →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:1rem;text-align:center;color:{{ $gri }}">Aktif firma yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ==================== FİRMALAR ==================== --}}
    @if ($sekme === 'firmalar')
        <div style="{{ $kutu }};overflow-x:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
                <div style="font-weight:700">Firmalarım ({{ $this->firmalar->count() }})</div>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Resources\Firmas\FirmaResource::getUrl()">Firmalar sayfası →</x-filament::button>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:.83rem">
                <thead><tr style="text-align:left;color:rgb(95 87 112)">
                    <th style="padding:.4rem">Firma</th><th style="padding:.4rem">SGK Sicil</th>
                    <th style="padding:.4rem">Çalışan</th><th style="padding:.4rem">Tehlike Sınıfı</th>
                </tr></thead>
                <tbody>
                @forelse ($this->firmalar as $f)
                    <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                        <td style="padding:.4rem;font-weight:600">{{ $f->unvan }}</td>
                        <td style="padding:.4rem">{{ $f->sgk_sicil_no ?: '—' }}</td>
                        <td style="padding:.4rem">{{ $f->calisanlar_count ?: $f->calisan_sayisi }}</td>
                        <td style="padding:.4rem"><x-filament::badge>{{ $f->tehlikeSinifiEtiketi() }}</x-filament::badge></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="padding:1rem;text-align:center;color:rgb(95 87 112)">Henüz firma eklenmemiş.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- ==================== ÇALIŞANLAR ==================== --}}
    @if ($sekme === 'calisanlar')
        <div style="{{ $kutu }};overflow-x:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
                <div style="font-weight:700">Çalışanlarım ({{ $this->calisanlar->count() }})</div>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Resources\Calisans\CalisanResource::getUrl()">Çalışanlar sayfası →</x-filament::button>
            </div>
            @if ($this->calisanlar->isEmpty())
                <div style="text-align:center;padding:2rem;color:rgb(95 87 112)">
                    <div style="font-size:1.75rem">👥</div>Henüz çalışan eklenmemiş.
                </div>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:.83rem">
                    <thead><tr style="text-align:left;color:rgb(95 87 112)">
                        <th style="padding:.4rem">Ad Soyad</th><th style="padding:.4rem">Firma</th>
                        <th style="padding:.4rem">Görev</th><th style="padding:.4rem">Durum</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($this->calisanlar as $c)
                        <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                            <td style="padding:.4rem;font-weight:600">{{ $c->ad_soyad }}</td>
                            <td style="padding:.4rem">{{ $c->firma?->unvan ?: '—' }}</td>
                            <td style="padding:.4rem">{{ $c->gorev ?: '—' }}</td>
                            <td style="padding:.4rem">
                                <x-filament::badge :color="$c->aktif ? 'success' : 'gray'">{{ $c->aktif ? 'Aktif' : 'Ayrıldı' }}</x-filament::badge>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    {{-- ==================== EĞİTİMLER ==================== --}}
    @if ($sekme === 'egitimler')
        @php
            $durumRenk = ['gecerli' => $yesil, 'yakinda' => $sari, 'dolmus' => $kirmizi];
            $durumEtiket = ['gecerli' => 'Geçerli', 'yakinda' => 'Yakında', 'dolmus' => 'Dolmuş', 'eksik' => 'Eksik'];
            $ozet = $this->egitimOzet;
        @endphp
        <div style="{{ $kutu }};overflow-x:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem;flex-wrap:wrap;gap:.5rem">
                <div>
                    <div style="font-weight:700">Eğitim Kayıtları ({{ $ozet['toplam'] }} çalışan · {{ $ozet['eksik'] }} eksik)</div>
                    <div style="font-size:.78rem;color:rgb(95 87 112)">Çalışan × eğitim türü tamamlanma tarihi. Yeşil = geçerli, sarı = 60 gün içinde dolacak, kırmızı = dolmuş/eksik. Varsayılan olarak yalnız Temel İSG Eğitimi takip edilir — "Konu Ekle" ile yeni sütun eklenir.</div>
                </div>
                <div style="display:flex;gap:.4rem">
                    {{ $this->egitimSablonIndirAction }}
                    {{ $this->egitimYukleAction }}
                    {{ $this->egitimTuruEkleAction }}
                </div>
            </div>

            {{-- AKTİF / İŞTEN AYRILAN + ARAMA + FİRMA --}}
            <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin-bottom:.6rem">
                <div style="display:flex;background:rgb(128 116 148 / .08);border-radius:9999px;padding:.2rem">
                    <button type="button" wire:click="$set('egitimDurumTab','aktif')"
                        style="padding:.3rem .8rem;border-radius:9999px;border:none;cursor:pointer;font-size:.78rem;font-weight:600;
                            background:{{ $egitimDurumTab === 'aktif' ? $mor : 'transparent' }};color:{{ $egitimDurumTab === 'aktif' ? '#fff' : 'inherit' }}">Aktif Personel</button>
                    <button type="button" wire:click="$set('egitimDurumTab','ayrilan')"
                        style="padding:.3rem .8rem;border-radius:9999px;border:none;cursor:pointer;font-size:.78rem;font-weight:600;
                            background:{{ $egitimDurumTab === 'ayrilan' ? $mor : 'transparent' }};color:{{ $egitimDurumTab === 'ayrilan' ? '#fff' : 'inherit' }}">İşten Ayrılanlar</button>
                </div>
                <input type="text" wire:model.live.debounce.400ms="egitimArama" placeholder="Çalışan ara..."
                    style="padding:.35rem .7rem;border-radius:.5rem;border:1px solid rgb(128 116 148 / .35);background:transparent;font-size:.8rem;min-width:12rem">
                <select wire:model.live="egitimFirmaId"
                    style="padding:.35rem .6rem;border-radius:.5rem;border:1px solid rgb(128 116 148 / .35);background:transparent;font-size:.8rem">
                    <option value="0">Tüm Firmalar</option>
                    @foreach ($this->egitimFirmalari as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>

            {{-- KATEGORİ FİLTRESİ --}}
            @php $kullanilanKategoriler = $this->egitimTurleri->map(fn ($t) => $t->kategori() ?? 'ozel')->unique(); @endphp
            @if ($kullanilanKategoriler->count() > 1)
                <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:.4rem">
                    <span style="font-size:.75rem;color:rgb(95 87 112);align-self:center;font-weight:700">KATEGORİ</span>
                    <button type="button" wire:click="$set('egitimKategoriFiltre','')"
                        style="padding:.15rem .6rem;border-radius:9999px;font-size:.75rem;cursor:pointer;
                            border:1px solid {{ $egitimKategoriFiltre === '' ? $mor : 'rgb(128 116 148 / .3)' }};
                            background:{{ $egitimKategoriFiltre === '' ? 'rgb(139 92 246 / .1)' : 'transparent' }}">Tümü</button>
                    @foreach ($kullanilanKategoriler as $kat)
                        <button type="button" wire:click="$set('egitimKategoriFiltre','{{ $kat }}')"
                            style="padding:.15rem .6rem;border-radius:9999px;font-size:.75rem;cursor:pointer;
                                border:1px solid {{ $egitimKategoriFiltre === $kat ? $mor : 'rgb(128 116 148 / .3)' }};
                                background:{{ $egitimKategoriFiltre === $kat ? 'rgb(139 92 246 / .1)' : 'transparent' }}">{{ \App\Filament\Pages\Profilim::EGITIM_KATEGORI_ETIKETLERI[$kat] ?? $kat }}</button>
                    @endforeach
                </div>
            @endif

            {{-- DURUM FİLTRESİ --}}
            <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:.7rem">
                <span style="font-size:.75rem;color:rgb(95 87 112);align-self:center;font-weight:700">DURUM</span>
                <button type="button" wire:click="$set('egitimDurumFiltre','')"
                    style="padding:.15rem .6rem;border-radius:9999px;font-size:.75rem;cursor:pointer;
                        border:1px solid {{ $egitimDurumFiltre === '' ? $mor : 'rgb(128 116 148 / .3)' }};
                        background:{{ $egitimDurumFiltre === '' ? 'rgb(139 92 246 / .1)' : 'transparent' }}">Tümü</button>
                @foreach ($durumEtiket as $anahtar => $etiket)
                    <button type="button" wire:click="$set('egitimDurumFiltre','{{ $anahtar }}')"
                        style="padding:.15rem .6rem;border-radius:9999px;font-size:.75rem;cursor:pointer;
                            border:1px solid {{ $egitimDurumFiltre === $anahtar ? ($durumRenk[$anahtar] ?? $mor) : 'rgb(128 116 148 / .3)' }};
                            color:{{ $egitimDurumFiltre === $anahtar ? ($durumRenk[$anahtar] ?? $mor) : 'inherit' }};
                            background:{{ $egitimDurumFiltre === $anahtar ? 'rgb(139 92 246 / .08)' : 'transparent' }}">{{ $etiket }}</button>
                @endforeach
            </div>

            @if (empty($this->egitimMatrisi))
                <div style="text-align:center;padding:2rem;color:rgb(95 87 112)">
                    {{ $egitimDurumTab === 'ayrilan' ? 'İşten ayrılan çalışan bulunamadı.' : 'Filtrelere uyan aktif çalışan bulunamadı.' }}
                </div>
            @else
                <table style="border-collapse:collapse;font-size:.76rem;white-space:nowrap">
                    <thead>
                        <tr style="color:rgb(95 87 112)">
                            <th style="padding:.35rem;text-align:left;position:sticky;left:0;background:inherit">Çalışan</th>
                            @foreach ($this->egitimTurleriGorunen as $t)
                                <th style="padding:.35rem;text-align:center;max-width:5.5rem;white-space:normal">
                                    {{ $t->ad }}
                                    <button type="button" title="Takipten kaldır" wire:click="egitimTuruKaldir('{{ $t->anahtar }}')"
                                        wire:confirm="'{{ $t->ad }}' takipten kaldırılsın mı? Geçmiş eğitim kayıtları silinmez."
                                        style="border:none;background:none;color:{{ $kirmizi }};cursor:pointer;font-weight:700;margin-left:.2rem">×</button>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->egitimMatrisi as $satir)
                            <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                                <td style="padding:.35rem;font-weight:600;position:sticky;left:0;background:inherit">
                                    {{ $satir['calisan']->ad_soyad }}
                                    <div style="font-weight:400;font-size:.75rem;color:rgb(95 87 112)">{{ $satir['calisan']->firma?->unvan }}</div>
                                </td>
                                @foreach ($this->egitimTurleriGorunen as $t)
                                    @php $h = $satir['hucreler'][$t->anahtar]; @endphp
                                    <td style="padding:.35rem;text-align:center;color:{{ $h['durum'] ? $durumRenk[$h['durum']] : $kirmizi }}">
                                        {{ $h['tarih'] ? $h['tarih']->format('d.m.Y') : 'Eksik' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    {{-- ==================== FİRMA TAKİP (evrak matrisi) ==================== --}}
    @if ($sekme === 'firma_takip')
        <div style="{{ $kutu }};overflow-x:auto">
            <div style="margin-bottom:.6rem">
                <div style="font-weight:700">Firma × Yasal Kriter Matrisi</div>
                <div style="font-size:.8rem;color:rgb(95 87 112)">
                    Her firmanın {{ count($this->firmaTakipKriterleri) }} kriteri karşılama durumu.
                    Koyu ✓/✗ = mehse'de gerçek modül var; açık ✗ = modül henüz kurulmadı. Oran yalnız gerçek modüllere göre hesaplanır.
                </div>
            </div>
            <table style="border-collapse:collapse;font-size:.78rem;white-space:nowrap">
                <thead><tr style="color:rgb(95 87 112)">
                    <th style="padding:.35rem;text-align:left;position:sticky;left:0;background:inherit">Firma</th>
                    @foreach ($this->firmaTakipKriterleri as $k)
                        <th style="padding:.35rem;text-align:center;max-width:5rem;white-space:normal">{{ $k['ad'] }}</th>
                    @endforeach
                    <th style="padding:.35rem;text-align:center">Oran</th>
                </tr></thead>
                <tbody>
                @forelse ($this->firmaMatrisi as $satir)
                    <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                        <td style="padding:.35rem;font-weight:600;position:sticky;left:0;background:inherit">{{ $satir['firma']->unvan }}</td>
                        @foreach ($this->firmaTakipKriterleri as $k)
                            @php $var = $satir['hucreler'][$k['anahtar']]; $renk = $var ? $yesil : ($k['takip'] ? 'rgb(95 87 112)' : ($k['hazir'] ? $kirmizi : 'rgb(239 68 68 / .55)')); @endphp
                            <td style="padding:.35rem;text-align:center;color:{{ $renk }}" title="{{ $k['hazir'] ? 'Gerçek modül' : 'Henüz modül yok' }}">
                                {{ $var ? '✓' : ($k['takip'] ? '–' : '✗') }}
                            </td>
                        @endforeach
                        <td style="padding:.35rem;text-align:center;font-weight:700">%{{ $satir['oran'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($this->firmaTakipKriterleri) + 2 }}" style="padding:1rem;text-align:center;color:rgb(95 87 112)">Firma yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- FİRMA CHECKLİST (tek firma, vade tarihli detay) --}}
        <div style="{{ $kutu }};margin-top:1rem">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;margin-bottom:.6rem">
                <div>
                    <div style="font-weight:700">Firma Checklist</div>
                    <div style="font-size:.78rem;color:rgb(95 87 112)">
                        Tek firma seçip her kriter için vade tarihi girin — vadesi 30 gün içinde olan "Yakın",
                        geçmiş veya girilmemiş "Eksik" olarak işaretlenir. Modül gerçekten tamamlanmışsa vadeden
                        bağımsız her zaman "Tamamlandı" gösterilir.
                    </div>
                </div>
                <select wire:model.live="firmaTakipSeciliId"
                    style="padding:.35rem .6rem;border-radius:.5rem;border:1px solid rgb(128 116 148 / .35);background:transparent;font-size:.8rem">
                    <option value="0">— Firma seçin —</option>
                    @foreach ($this->firmalar as $f)
                        <option value="{{ $f->id }}">{{ $f->unvan }}</option>
                    @endforeach
                </select>
            </div>

            @if ($this->firmaTakipSecili)
                @php
                    $seciliOran = collect($this->firmaMatrisi)->firstWhere('firma.id', $this->firmaTakipSecili->id)['oran'] ?? 0;
                    $checklistRenk = ['tamamlandi' => $yesil, 'yakin' => $sari, 'eksik' => $kirmizi, 'takip' => '#808080'];
                    $checklistEtiket = ['tamamlandi' => 'Tamamlandı', 'yakin' => 'Yakın', 'eksik' => 'Eksik', 'takip' => 'Takip (kayıt yok)'];
                @endphp
                <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:.7rem">
                    <div style="{{ $kutu }};flex:1;min-width:10rem">
                        <div style="font-size:.75rem;color:rgb(95 87 112)">Uyum Skoru</div>
                        <div style="font-size:1.4rem;font-weight:800;color:{{ $mor }}">%{{ $seciliOran }}</div>
                    </div>
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                    <thead><tr style="color:rgb(95 87 112)">
                        <th style="padding:.35rem;text-align:left">Kriter</th>
                        <th style="padding:.35rem;text-align:center">Durum</th>
                        <th style="padding:.35rem;text-align:center">Vade Tarihi</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($this->firmaChecklistDetay as $satir)
                            <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                                <td style="padding:.4rem">{{ $satir['ad'] }}</td>
                                <td style="padding:.4rem;text-align:center">
                                    <span style="padding:.15rem .6rem;border-radius:9999px;font-size:.75rem;font-weight:600;
                                        color:{{ $checklistRenk[$satir['durum']] }};background:{{ $checklistRenk[$satir['durum']] }}1a">
                                        {{ $checklistEtiket[$satir['durum']] }}
                                    </span>
                                </td>
                                <td style="padding:.4rem;text-align:center">
                                    <input type="date" value="{{ $satir['vade_tarihi']?->toDateString() }}"
                                        @disabled($satir['tamam'])
                                        wire:change="firmaChecklistVadeGuncelle('{{ $satir['anahtar'] }}', $event.target.value)"
                                        style="padding:.25rem .4rem;border-radius:.4rem;border:1px solid rgb(128 116 148 / .35);background:transparent;font-size:.75rem">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="text-align:center;padding:1.5rem;color:rgb(95 87 112);font-size:.85rem">Detay için yukarıdan bir firma seçin.</p>
            @endif
        </div>
    @endif

    {{-- ==================== RİSKLERİM ==================== --}}
    @if ($sekme === 'risklerim')
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem">
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Sektör Şablonları</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $mor }}">{{ $o['risk_sablonu'] }}</div>
                <p style="font-size:.8rem;color:rgb(95 87 112)">Sektöre etiketli risk setleri; sihirbazda tek tıkla uygulanır.</p>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Resources\RiskSablonus\RiskSablonuResource::getUrl()">Şablonları yönet →</x-filament::button>
            </div>
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Risk Kütüphanesi</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $yesil }}">{{ \App\Models\Tehlike::count() }}</div>
                <p style="font-size:.8rem;color:rgb(95 87 112)">Kategoriye göre gruplu tehlike/önlem maddeleri.</p>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Resources\Tehlikes\TehlikeResource::getUrl()">Kütüphaneye git →</x-filament::button>
            </div>
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Risk Değerlendirmeleri</div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $sari }}">{{ $o['risk_degerlendirmesi'] }}</div>
                <p style="font-size:.8rem;color:rgb(95 87 112)">Kayıtlı firma risk raporları.</p>
                <x-filament::button size="xs" tag="a" :href="\App\Filament\Pages\RiskSihirbazi::getUrl()">Yeni sihirbaz →</x-filament::button>
            </div>
        </div>
    @endif

    {{-- ==================== ARŞİV ==================== --}}
    @if ($sekme === 'arsiv')
        <div style="{{ $kutu }}">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                <div>
                    <div style="font-weight:700">Dosya Arşivi</div>
                    <div style="font-size:.78rem;color:rgb(95 87 112)">{{ $this->arsivKullanilanMb }} MB / {{ config('isg.arsiv.kota_mb') }} MB kullanılıyor</div>
                </div>
                {{ $this->arsivYukleAction }}
            </div>
            <div style="height:6px;border-radius:9999px;background:rgb(128 116 148 / .2);margin-top:.5rem;overflow:hidden">
                <div style="height:100%;width:{{ min(100, round($this->arsivKullanilanMb / max(1, config('isg.arsiv.kota_mb')) * 100)) }}%;background:{{ $mor }}"></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:16rem 1fr;gap:1rem">
            <div style="{{ $kutu }};max-height:28rem;overflow-y:auto">
                <div style="font-weight:700;margin-bottom:.5rem">Firmalar</div>
                @forelse ($this->arsivFirmalar as $f)
                    <button type="button" wire:click="arsivFirmaSec({{ $f->id }})"
                        style="display:block;width:100%;text-align:left;padding:.4rem .5rem;border:none;border-radius:.4rem;cursor:pointer;font-size:.8rem;margin-bottom:.15rem;
                            background:{{ $this->arsivSeciliFirmaId === $f->id ? $mor : 'transparent' }};color:{{ $this->arsivSeciliFirmaId === $f->id ? '#fff' : 'inherit' }}">
                        {{ $f->unvan }}
                    </button>
                @empty
                    <div style="font-size:.8rem;color:rgb(95 87 112)">Firma yok.</div>
                @endforelse
            </div>

            <div style="{{ $kutu }}">
                <div style="font-weight:700;margin-bottom:.6rem">{{ $this->arsivSeciliFirma?->unvan ?? 'Firma seçin' }}</div>
                @if ($this->arsivDosyalar->isEmpty())
                    <div style="text-align:center;padding:2rem;color:rgb(95 87 112)">Bu firmada henüz dosya yok.</div>
                @else
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.6rem">
                        @foreach ($this->arsivDosyalar as $dosya)
                            <div style="border:1px solid rgb(128 116 148 / .2);border-radius:.5rem;padding:.6rem;text-align:center">
                                <div style="font-size:1.5rem">📄</div>
                                <div style="font-size:.75rem;font-weight:600;word-break:break-word">{{ $dosya->dosya_adi }}</div>
                                <div style="font-size:.75rem;color:rgb(95 87 112);margin-bottom:.4rem">{{ $dosya->boyutEtiketi() }}</div>
                                <div style="display:flex;justify-content:center;gap:.3rem">
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($dosya->dosya_yolu) }}" target="_blank"
                                        style="font-size:.75rem;color:{{ $mor }}">İndir</a>
                                    <button type="button" wire:click="arsivDosyaSil({{ $dosya->id }})" wire:confirm="Bu dosya silinsin mi?"
                                        style="font-size:.75rem;color:{{ $kirmizi }};border:none;background:none;cursor:pointer">Sil</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ==================== RAPORLAR ==================== --}}
    @if ($sekme === 'raporlar')
        <div style="{{ $kutu }};overflow-x:auto">
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.6rem">
                <input type="text" wire:model.live.debounce.400ms="raporArama" placeholder="Ara..."
                    style="padding:.4rem .6rem;border:1px solid rgb(128 116 148 / .3);border-radius:.4rem;font-size:.82rem;min-width:14rem">
                <select wire:model.live="raporTipFiltre" style="padding:.4rem .6rem;border:1px solid rgb(128 116 148 / .3);border-radius:.4rem;font-size:.82rem">
                    <option value="">Tüm tipler</option>
                    @foreach ($this->raporTipSecenekleri as $tip)
                        <option value="{{ $tip }}">{{ $tip }}</option>
                    @endforeach
                </select>
            </div>

            @if (empty($this->raporlar))
                <div style="text-align:center;padding:2rem;color:rgb(95 87 112)">Bu filtrelerde rapor bulunamadı.</div>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:.83rem">
                    <thead><tr style="text-align:left;color:rgb(95 87 112)">
                        <th style="padding:.4rem">Rapor</th><th style="padding:.4rem">Tip</th>
                        <th style="padding:.4rem">Tarih</th><th style="padding:.4rem">İşlemler</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($this->raporlar as $satir)
                        <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                            <td style="padding:.4rem;font-weight:600">
                                {{ $satir['baslik'] }}
                                <div style="font-weight:400;font-size:.75rem;color:rgb(95 87 112)">Firma: {{ $satir['firma'] ?? '—' }}</div>
                            </td>
                            <td style="padding:.4rem"><x-filament::badge>{{ $satir['tip'] }}</x-filament::badge></td>
                            <td style="padding:.4rem">{{ $satir['tarih']?->format('d.m.Y H:i') }}</td>
                            <td style="padding:.4rem;white-space:nowrap">
                                <x-filament::button size="xs" color="gray"
                                    wire:click="mountAction('raporIndir', { model: '{{ addslashes($satir['kaynak']['model']) }}', id: {{ $satir['kayit']->id }}, format: 'birincil' })">
                                    İndir
                                </x-filament::button>
                                @if (\App\Support\RaporKayitlari::ikincilUygunMu($satir['kaynak'], $satir['kayit']))
                                    <x-filament::button size="xs" color="gray"
                                        wire:click="mountAction('raporIndir', { model: '{{ addslashes($satir['kaynak']['model']) }}', id: {{ $satir['kayit']->id }}, format: 'ikincil' })">
                                        {{ $satir['kaynak']['ikincil_etiket'] }}
                                    </x-filament::button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        {{ $this->raporIndirAction }}
    @endif

</x-filament-panels::page>
