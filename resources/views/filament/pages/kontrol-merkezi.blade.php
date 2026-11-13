@php
    $sekmeler = \App\Filament\Pages\KontrolMerkezi::SEKMELER;
    $mor = 'rgb(139 92 246)';
    $turkuaz = 'rgb(20 184 166)';
    $yesil = 'rgb(34 197 94)';
    $kirmizi = 'rgb(239 68 68)';
    $sari = 'rgb(245 158 11)';
    $gri = 'rgb(95 87 112)';
    $kutu = 'border:1px solid rgb(128 116 148 / .3);border-radius:.75rem;padding:1rem';
    $o = $this->ozet;

    // İSG Portföy Özeti kartları — Gemini referans görselindeki dolu-renk fayans stili.
    $ozetKartlari = [
        ['ad' => 'Toplam Firma', 'deger' => $o['firma'], 'ikon' => 'heroicon-o-building-office-2', 'renk' => $gri],
        ['ad' => 'Eksik Evrakı Olan Firma', 'deger' => $o['evrak_eksigi'], 'ikon' => 'heroicon-o-document-text', 'renk' => $kirmizi],
        ['ad' => 'Çalışan Kaydı Eksik Firma', 'deger' => $o['calisan_uyarili'], 'ikon' => 'heroicon-o-exclamation-triangle', 'renk' => $sari],
        ['ad' => 'Tam Uyumlu Firma', 'deger' => $o['tam_uyumlu'], 'ikon' => 'heroicon-o-check-circle', 'renk' => $yesil],
    ];
    $uyumRengi = $o['uyum_yuzde'] < 40 ? $kirmizi : ($o['uyum_yuzde'] < 75 ? $sari : $yesil);
@endphp

<x-filament-panels::page>
    {{-- BAŞLIK: sayfa başlığı zaten "İSG Komuta Merkezi" — burada yalnız canlı rozeti (13.10.2026). --}}
    <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
        <span style="display:inline-flex;align-items:center;gap:.35rem;background:rgb(34 197 94 / .15);color:{{ $yesil }};font-size:.75rem;font-weight:600;padding:.2rem .6rem;border-radius:9999px">
            <span style="width:.5rem;height:.5rem;border-radius:9999px;background:{{ $yesil }}"></span> Canlı İSG Takibi
        </span>
    </div>
    <p style="font-size:.85rem;color:rgb(95 87 112);margin-top:-.5rem">
        Tüm firmalarınızın yasal belgeleri, yaklaşan işleri ve çalışan eksiklerini tek kontrol merkezinde.
    </p>

    {{-- SEKME PILL'LERİ --}}
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;background:rgb(128 116 148 / .1);padding:.3rem;border-radius:.6rem">
        @foreach ($sekmeler as $anahtar => $ad)
            @php $aktif = $sekme === $anahtar; @endphp
            <button type="button" wire:click="sekmeSec('{{ $anahtar }}')"
                style="flex:1;min-width:120px;padding:.5rem .75rem;border:none;border-radius:.45rem;cursor:pointer;font-weight:600;font-size:.85rem;
                    background:{{ $aktif ? $turkuaz : 'transparent' }};color:{{ $aktif ? '#fff' : 'inherit' }}">
                {{ $ad }}
            </button>
        @endforeach
    </div>

    {{-- FİRMA SEÇ (firma / calisan sekmeleri) --}}
    @if (in_array($sekme, ['firma', 'calisan'], true))
        @include('filament.pages.partials.komuta-firma-cubugu', ['tumPortfoy' => true])
    @endif

    {{-- ==================== GÜNLÜK AKIŞ ==================== --}}
    {{-- 13.11.2026: tüm süreli yasal yükümlülükler (App\Support\YasalTakvim) — süresi geçenler + 90 gün içinde dolanlar. --}}
    @if ($sekme === 'gunluk')
        @php
            $isler = collect($this->yaklasanIsler);
            $turAdlari = ['risk' => 'Risk', 'acil' => 'Acil durum', 'kurul' => 'Kurul', 'egitim' => 'Eğitim', 'ekipman' => 'Periyodik kontrol', 'saglik' => 'Sağlık', 'olcum' => 'Ölçüm', 'kkd' => 'KKD', 'sozlesme' => 'Sözleşme'];
            $gruplar = [
                ['ad' => 'Süresi geçenler', 'renk' => $kirmizi, 'liste' => $isler->where('durum', 'gecikti')],
                ['ad' => 'Önümüzdeki '.\App\Filament\Pages\KontrolMerkezi::TAKVIM_GUN.' gün', 'renk' => $sari, 'liste' => $isler->where('durum', 'yaklasiyor')],
            ];
        @endphp
        @if ($isler->isEmpty())
            <div style="{{ $kutu }};text-align:center;padding:2.5rem 1rem">
                <div style="font-weight:700">Tüm yasal süreler güncel</div>
                <p style="font-size:.85rem;color:{{ $gri }}">
                    Süresi geçen ya da {{ \App\Filament\Pages\KontrolMerkezi::TAKVIM_GUN }} gün içinde dolan yükümlülük yok.
                </p>
            </div>
        @else
            <p style="font-size:.8rem;color:{{ $gri }};margin:0">
                Risk değerlendirmesi, acil durum planı, tatbikat, kurul, eğitim, periyodik kontrol, sağlık gözetimi, ortam ölçümü, KKD ve İSG-KATİP sözleşme süreleri — tüm aktif firmalar.
            </p>
            @foreach ($gruplar as $grup)
                @continue($grup['liste']->isEmpty())
                <div style="{{ $kutu }};padding:.75rem .9rem">
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem">
                        <span style="width:.6rem;height:.6rem;border-radius:9999px;background:{{ $grup['renk'] }}"></span>
                        <span style="font-weight:700;font-size:.9rem">{{ $grup['ad'] }}</span>
                        <span style="font-size:.75rem;font-weight:700;color:{{ $grup['renk'] }};background:color-mix(in srgb, {{ $grup['renk'] }} 14%, transparent);padding:.05rem .45rem;border-radius:9999px">{{ $grup['liste']->count() }}</span>
                    </div>
                    <div style="display:flex;flex-direction:column">
                        @foreach ($grup['liste'] as $is)
                            <div style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.25rem 1rem;align-items:center;padding:.5rem 0;border-top:1px solid rgb(128 116 148 / .14)">
                                <div style="min-width:0">
                                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.4rem">
                                        <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:{{ $gri }};border:1px solid rgb(128 116 148 / .3);border-radius:.3rem;padding:0 .3rem">{{ $turAdlari[$is['tur']] ?? $is['tur'] }}</span>
                                        <span style="font-weight:600;font-size:.86rem">{{ $is['baslik'] }}</span>
                                        <span style="font-size:.8rem;color:{{ $gri }}">· {{ $is['firma'] }}</span>
                                    </div>
                                    <div style="font-size:.78rem;color:{{ $gri }};margin-top:.15rem">{{ $is['aciklama'] }}</div>
                                </div>
                                <div style="display:flex;align-items:center;gap:.8rem;white-space:nowrap">
                                    <span style="font-size:.8rem;font-weight:600;color:{{ $grup['renk'] }};font-variant-numeric:tabular-nums;text-align:right">
                                        @if ($is['tarih'])
                                            {{ $is['tarih'] }}
                                            <span style="font-weight:400;color:{{ $gri }}">· {{ $is['kalan_gun'] < 0 ? abs($is['kalan_gun']).' gün geçti' : ($is['kalan_gun'] === 0 ? 'bugün' : $is['kalan_gun'].' gün kaldı') }}</span>
                                        @else
                                            Kayıt yok
                                        @endif
                                    </span>
                                    @if ($is['url'])
                                        <a href="{{ $is['url'] }}" style="font-size:.8rem;font-weight:600;color:{{ $mor }}">Aç →</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    @endif

    {{-- ==================== FİRMA ASİSTANI ==================== --}}
    @if ($sekme === 'firma')
        {{-- İSG Portföy Özeti --}}
        <div style="{{ $kutu }}">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <div>
                    <div style="font-weight:700">İSG Portföy Özeti</div>
                    <div style="font-size:.8rem;color:{{ $gri }}">{{ $o['firma'] }} Firma · {{ $o['calisan'] }} kayıtlı çalışan{{ $o['calisan_bildirilen'] !== $o['calisan'] ? ' (bildirilen '.$o['calisan_bildirilen'].')' : '' }} — anlık yasal uyum tablosu</div>
                </div>
                {{-- Uyum yüzdesi — dolgu halkası (conic-gradient + mask, tema-bağımsız) --}}
                <div style="display:flex;align-items:center;gap:.7rem">
                    <div style="position:relative;width:3.4rem;height:3.4rem;flex-shrink:0">
                        <div style="width:100%;height:100%;border-radius:9999px;
                                background:conic-gradient({{ $uyumRengi }} {{ $o['uyum_yuzde'] }}%, rgb(128 116 148 / .18) 0);
                                -webkit-mask:radial-gradient(farthest-side, transparent calc(100% - 7px), #000 calc(100% - 7px));
                                mask:radial-gradient(farthest-side, transparent calc(100% - 7px), #000 calc(100% - 7px))"></div>
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:{{ $uyumRengi }}">
                            %{{ $o['uyum_yuzde'] }}
                        </div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:1.3rem;font-weight:800;color:{{ $uyumRengi }}">%{{ $o['uyum_yuzde'] }}</div>
                        <div style="font-size:.75rem;color:{{ $gri }}">{{ $o['tam_uyumlu'] }} firma tam uyumlu</div>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;margin-top:1rem">
                @foreach ($ozetKartlari as $kart)
                    <div style="border-radius:.65rem;padding:.85rem;background:color-mix(in srgb, {{ $kart['renk'] }} 14%, transparent)">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
                            <span style="font-size:.75rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:{{ $gri }}">{{ $kart['ad'] }}</span>
                            <span style="width:1.8rem;height:1.8rem;border-radius:.5rem;background:rgb(128 116 148 / .12);
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <x-filament::icon :icon="$kart['ikon']" style="width:1rem;height:1rem;color:{{ $kart['renk'] }}"/>
                            </span>
                        </div>
                        <div style="font-size:1.55rem;font-weight:800;color:{{ $kart['renk'] }};margin-top:.35rem">{{ $kart['deger'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Çalışan kaydı eksikleri (13.11.2026): eğitim / muayene / temsilci takibi çalışan listesiyle hesaplanır. --}}
            @if ($this->calisanEksikleri)
                <details style="margin-top:.9rem;border:1px solid rgb(128 116 148 / .25);border-radius:.6rem;padding:.6rem .8rem">
                    <summary style="cursor:pointer;font-size:.85rem;font-weight:700">
                        Çalışan kaydı eksik firmalar ({{ count($this->calisanEksikleri) }})
                        <span style="font-weight:400;color:{{ $gri }}"> — eğitim, sağlık gözetimi ve temsilci takibi bu listeyle yapılır; Çalışanlar sayfasından Excel ile toplu eklenebilir.</span>
                    </summary>
                    <div style="overflow-x:auto;margin-top:.6rem">
                        <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                            <thead>
                                <tr style="text-align:left;color:{{ $gri }};border-bottom:1px solid rgb(128 116 148 / .25)">
                                    <th style="padding:.35rem .5rem">Firma</th>
                                    <th style="padding:.35rem .5rem;text-align:right">Kayıtlı / Bildirilen</th>
                                    <th style="padding:.35rem .5rem;text-align:right">TC boş</th>
                                    <th style="padding:.35rem .5rem;text-align:right">İşe giriş boş</th>
                                    <th style="padding:.35rem .5rem"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->calisanEksikleri as $s)
                                    <tr style="border-bottom:1px solid rgb(128 116 148 / .12)">
                                        <td style="padding:.35rem .5rem">{{ $s['unvan'] }}</td>
                                        <td style="padding:.35rem .5rem;text-align:right;font-variant-numeric:tabular-nums;{{ $s['kayitli'] < $s['bildirilen'] ? 'color:'.$kirmizi.';font-weight:700' : '' }}">{{ $s['kayitli'] }} / {{ $s['bildirilen'] }}</td>
                                        <td style="padding:.35rem .5rem;text-align:right;font-variant-numeric:tabular-nums">{{ $s['tc_bos'] ?: '—' }}</td>
                                        <td style="padding:.35rem .5rem;text-align:right;font-variant-numeric:tabular-nums">{{ $s['giris_bos'] ?: '—' }}</td>
                                        <td style="padding:.35rem .5rem;text-align:right"><a href="{{ $s['url'] }}" style="font-weight:600;color:{{ $mor }}">Çalışanlar →</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </div>

        {{-- Yasal Kriterlerin Portföy Tamamlanma Oranları --}}
        <div style="{{ $kutu }}">
            <div style="font-weight:700">Yasal Kriterlerin Portföy Tamamlanma Oranları</div>
            <div style="font-size:.8rem;color:{{ $gri }};margin-bottom:.75rem">
                Takip edilen {{ count($this->kriterler) }} kriterin firmalarınızdaki karşılanma yüzdeleri · {{ $o['firma'] }} firma tarandı
            </div>
            <div style="font-size:.75rem;color:{{ $gri }};margin-bottom:.5rem">
                Eksiği olan bir kritere tıklayın — hangi firmalarda eksik olduğu listelenir.
            </div>
            {{-- 3 mantıksal grup: Temel Yasal Belgeler / Saha & Kurul Süreçleri / Sağlık & Takip (App\Support\PortfoyKarne::grupla) --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,330px),1fr));gap:.75rem;align-items:start">
                @foreach ($this->kriterGruplari as $grup)
                    @php $gRenk = $grup['kapsam'] === 0 ? $gri : ($grup['yuzde'] < 40 ? $kirmizi : ($grup['yuzde'] < 75 ? $sari : $yesil)); @endphp
                    <div style="border:1px solid rgb(128 116 148 / .25);border-radius:.6rem;padding:.7rem .8rem">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.5rem">
                            <span style="display:flex;align-items:center;gap:.45rem;font-size:.88rem;font-weight:700">
                                <x-filament::icon :icon="$grup['ikon']" style="width:1.05rem;height:1.05rem;color:{{ $gRenk }}"/>
                                {{ $grup['ad'] }}
                            </span>
                            <span style="font-size:.75rem;font-weight:700;padding:.12rem .55rem;border-radius:9999px;color:{{ $gRenk }};background:color-mix(in srgb, {{ $gRenk }} 14%, transparent)">
                                %{{ $grup['yuzde'] }}
                            </span>
                        </div>
                        <div style="height:5px;border-radius:9999px;background:rgb(128 116 148 / .2);margin-bottom:.55rem;overflow:hidden">
                            <div style="height:100%;width:{{ max($grup['yuzde'], 1) }}%;background:{{ $gRenk }}"></div>
                        </div>

                        <div style="display:flex;flex-direction:column">
                            @foreach ($grup['kriterler'] as $k)
                                @php
                                    $kRenk = $k['takip'] ? $gri : ($k['yuzde'] < 40 ? $kirmizi : ($k['yuzde'] < 75 ? $sari : $yesil));
                                    $eksikSayi = max($k['toplam'] - $k['tamam'], 0);
                                    $tiklanir = $k['hazir'] && ! $k['takip'] && $eksikSayi > 0;
                                    $acik = $acikKriter === $k['anahtar'];
                                @endphp
                                <div style="border-top:1px solid rgb(128 116 148 / .15);padding:.4rem 0"
                                    @if ($tiklanir) wire:click="kriterDetayAc('{{ $k['anahtar'] }}')" role="button" tabindex="0" @endif>
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;{{ $tiklanir ? 'cursor:pointer' : '' }}">
                                        <span style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;font-weight:600;min-width:0">
                                            <x-filament::icon :icon="$k['ikon']" style="width:.85rem;height:.85rem;flex-shrink:0;color:{{ $kRenk }}"/>
                                            <span style="overflow:hidden;text-overflow:ellipsis" title="{{ $k['ad'] }}">{{ $k['ad'] }}</span>
                                            @unless ($k['hazir']) <span style="font-size:.68rem;color:{{ $gri }};white-space:nowrap">(yakında)</span> @endunless
                                            @if ($k['takip']) <span style="font-size:.68rem;color:{{ $gri }};white-space:nowrap" title="Olay bazlı kayıt — eksik sayılmaz, uyum yüzdesine girmez">(yalnız takip)</span> @endif
                                        </span>
                                        <span style="display:flex;align-items:center;gap:.3rem;white-space:nowrap">
                                            <span style="font-size:.75rem;font-weight:700;padding:.08rem .45rem;border-radius:9999px;color:{{ $kRenk }};background:color-mix(in srgb, {{ $kRenk }} 14%, transparent)">%{{ $k['yuzde'] }}</span>
                                            @if ($k['hazir'] && ! $k['takip'] && $eksikSayi > 0)
                                                <span style="font-size:.68rem;font-weight:700;padding:.08rem .45rem;border-radius:9999px;color:{{ $kirmizi }};background:color-mix(in srgb, {{ $kirmizi }} 12%, transparent)" title="Bu kriteri karşılamayan firma sayısı">{{ $eksikSayi }} eksik</span>
                                            @elseif ($k['hazir'] && ! $k['takip'])
                                                <span style="font-size:.68rem;color:{{ $yesil }}">✓</span>
                                            @endif
                                        </span>
                                    </div>

                                    @if ($acik)
                                        @php $eksikFirmalar = $this->acikKriterEksikFirmalar; @endphp
                                        <div style="margin-top:.5rem;padding-top:.45rem;border-top:1px dashed rgb(128 116 148 / .3)" onclick="event.stopPropagation()">
                                            <div style="font-size:.75rem;font-weight:700;color:{{ $kirmizi }};margin-bottom:.35rem">
                                                Bu kriterin eksik olduğu {{ count($eksikFirmalar) }} firma:
                                            </div>
                                            <div style="display:flex;flex-wrap:wrap;gap:.3rem">
                                                @forelse ($eksikFirmalar as $fId => $fUnvan)
                                                    <a href="{{ \App\Filament\Resources\Firmas\FirmaResource::getUrl('edit', ['record' => $fId]) }}"
                                                        style="display:inline-block;padding:.12rem .5rem;border-radius:9999px;font-size:.75rem;
                                                            border:1px solid {{ $kirmizi }};color:{{ $kirmizi }};text-decoration:none">
                                                        {{ $fUnvan }} ↗
                                                    </a>
                                                @empty
                                                    <span style="font-size:.75rem;color:{{ $gri }}">Eksik firma yok.</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
            {{-- Yenileme Süreleri --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Yasal Evrak Yenileme Süreleri</div>
                <div style="font-size:.78rem;color:rgb(95 87 112);margin-bottom:.6rem">6331 Sayılı İSG Kanunu uyarınca zorunlu periyotlar</div>
                @foreach (config('isg.tehlike_siniflari') as $anahtar => $ad)
                    <div style="display:flex;justify-content:space-between;padding:.35rem 0;border-top:1px solid rgb(128 116 148 / .15);font-size:.85rem">
                        <span>{{ $ad }} Sınıf</span>
                        <strong>{{ config('isg.risk_gecerlilik_yili.'.$anahtar) }} Yılda Bir</strong>
                    </div>
                @endforeach
            </div>

            {{-- Uzman Tavsiyeleri --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Uzman Tavsiyeleri & Aksiyonlar</div>
                <div style="font-size:.78rem;color:rgb(95 87 112);margin-bottom:.6rem">Denetim öncesi dikkat edilmesi gereken noktalar</div>
                <ul style="margin:0;padding-left:1.1rem;font-size:.82rem;display:flex;flex-direction:column;gap:.4rem">
                    @foreach (config('isg.kontrol_merkezi.uzman_tavsiyeleri') as $t)
                        <li><strong>{{ $t['baslik'] }}:</strong> {{ $t['metin'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ==================== ÇALIŞAN ASİSTANI ==================== --}}
    @if ($sekme === 'calisan')
        @if (! $this->secilenFirma)
            <div style="{{ $kutu }};text-align:center;padding:2.5rem 1rem">
                <div style="font-size:2rem">👥</div>
                <div style="font-weight:700;margin-top:.4rem">Çalışan İSG Asistanı</div>
                <p style="font-size:.85rem;color:rgb(95 87 112)">
                    Muayene, İSG eğitimi, MYK ve genç çalışan eksiklerini listelemek için yukarıdan bir firma seçin.
                </p>
            </div>
        @else
            @php $k = $this->calisanKarne; @endphp
            <div style="{{ $kutu }}">
                <div style="font-weight:700">{{ $this->secilenFirma->unvan }}</div>
                <div style="font-size:.82rem;color:rgb(95 87 112)">{{ $k['toplam'] }} aktif çalışan</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;margin-top:.75rem">
                    <div style="border:1px solid rgb(128 116 148 / .25);border-radius:.5rem;padding:.6rem">
                        <div style="font-size:.75rem;color:rgb(95 87 112);text-transform:uppercase">Genç Çalışan (&lt;18)</div>
                        <div style="font-size:1.3rem;font-weight:800;color:{{ $k['genc']->count() ? $sari : 'inherit' }}">{{ $k['genc']->count() }}</div>
                    </div>
                    <div style="border:1px solid rgb(128 116 148 / .25);border-radius:.5rem;padding:.6rem">
                        <div style="font-size:.75rem;color:rgb(95 87 112);text-transform:uppercase">Ağır & Tehlikeli İş</div>
                        <div style="font-size:1.3rem;font-weight:800">{{ $k['agir_tehlikeli'] }}</div>
                    </div>
                </div>
            </div>

            @if ($k['genc']->isNotEmpty())
                <div style="{{ $kutu }}">
                    <div style="font-weight:700;color:{{ $sari }}">⚠ Genç Çalışanlar — özel koruma gerektirir</div>
                    <ul style="margin:.4rem 0 0;padding-left:1.1rem;font-size:.85rem">
                        @foreach ($k['genc'] as $c)
                            <li>{{ $c->ad_soyad }} — {{ $c->gorev ?: 'görev belirtilmemiş' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="{{ $kutu }};font-size:.82rem;color:rgb(95 87 112)">
                📋 Şu takipler ilgili modüller kurulunca burada listelenecek:
                <strong>{{ implode(', ', $k['moduller_bekliyor']) }}</strong>.
            </div>
        @endif
    @endif
</x-filament-panels::page>
