@php
    $sekmeler = \App\Filament\Pages\KontrolMerkezi::SEKMELER;
    $mor = 'rgb(139 92 246)';
    $turkuaz = 'rgb(20 184 166)';
    $yesil = 'rgb(34 197 94)';
    $kirmizi = 'rgb(239 68 68)';
    $sari = 'rgb(245 158 11)';
    $gri = 'rgb(128 116 148)';
    $kutu = 'border:1px solid rgb(128 116 148 / .3);border-radius:.75rem;padding:1rem';
    $o = $this->ozet;

    // İSG Portföy Özeti kartları — Gemini referans görselindeki dolu-renk fayans stili.
    $ozetKartlari = [
        ['ad' => 'Toplam Firma', 'deger' => $o['firma'], 'ikon' => 'heroicon-o-building-office-2', 'renk' => $gri],
        ['ad' => 'Evrak Eksiği Olan', 'deger' => $o['evrak_eksigi'], 'ikon' => 'heroicon-o-document-text', 'renk' => $kirmizi],
        ['ad' => 'Çalışan Uyarılı', 'deger' => 0, 'ikon' => 'heroicon-o-exclamation-triangle', 'renk' => $sari],
        ['ad' => 'Tam Uyumlu', 'deger' => $o['tam_uyumlu'], 'ikon' => 'heroicon-o-check-circle', 'renk' => $yesil],
    ];
    $uyumRengi = $o['uyum_yuzde'] < 40 ? $kirmizi : ($o['uyum_yuzde'] < 75 ? $sari : $yesil);
@endphp

<x-filament-panels::page>
    {{-- BAŞLIK --}}
    <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
        <span style="font-size:1.15rem;font-weight:700">İSG Komuta Merkezi</span>
        <span style="display:inline-flex;align-items:center;gap:.35rem;background:rgb(34 197 94 / .15);color:{{ $yesil }};font-size:.72rem;font-weight:600;padding:.2rem .6rem;border-radius:9999px">
            <span style="width:.5rem;height:.5rem;border-radius:9999px;background:{{ $yesil }}"></span> Canlı İSG Takibi
        </span>
    </div>
    <p style="font-size:.85rem;color:rgb(128 116 148);margin-top:-.5rem">
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
        <div>
            <label style="font-weight:600;font-size:.82rem">Firma Seçin</label>
            <select wire:model.live="firmaId"
                style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(128 116 148 / .35);background:transparent">
                <option value="">★ Tüm Firmalar (portföy geneli)</option>
                @foreach ($this->firmalar as $id => $unvan)
                    <option value="{{ $id }}">{{ $unvan }}</option>
                @endforeach
            </select>
        </div>
    @endif

    {{-- ==================== GÜNLÜK AKIŞ ==================== --}}
    @if ($sekme === 'gunluk')
        @php $isler = $this->yaklasanIsler; @endphp
        @if (empty($isler))
            <div style="{{ $kutu }};text-align:center;padding:2.5rem 1rem">
                <div style="font-size:2rem">🎉</div>
                <div style="font-weight:700;margin-top:.4rem">Harika! Tüm Görevler Güncel</div>
                <p style="font-size:.85rem;color:rgb(128 116 148)">
                    Yaklaşan risk değerlendirmesi yenilemesi veya eksik kayıt bulunmuyor.
                </p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:.5rem">
                @foreach ($isler as $is)
                    <div style="{{ $kutu }};display:flex;align-items:center;justify-content:space-between;gap:1rem;
                        border-left:4px solid {{ $is['durum'] === 'gecikti' ? $kirmizi : $sari }}">
                        <div>
                            <div style="font-weight:600;font-size:.88rem">{{ $is['baslik'] }}</div>
                            <div style="font-size:.8rem;color:rgb(128 116 148)">{{ $is['firma'] }}</div>
                        </div>
                        <div style="font-size:.8rem;color:{{ $is['durum'] === 'gecikti' ? $kirmizi : $sari }};font-weight:600;white-space:nowrap">
                            {{ $is['durum'] === 'gecikti' ? 'Gecikti' : 'Yaklaşıyor' }}
                            @if ($is['tarih']) · {{ $is['tarih'] }} @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- ==================== FİRMA ASİSTANI ==================== --}}
    @if ($sekme === 'firma')
        {{-- İSG Portföy Özeti --}}
        <div style="{{ $kutu }}">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <div>
                    <div style="font-weight:700">İSG Portföy Özeti</div>
                    <div style="font-size:.8rem;color:{{ $gri }}">{{ $o['firma'] }} Firma · {{ $o['calisan'] }} Çalışan — anlık yasal uyum tablosu</div>
                </div>
                {{-- Uyum yüzdesi — dolgu halkası (conic-gradient + mask, tema-bağımsız) --}}
                <div style="display:flex;align-items:center;gap:.7rem">
                    <div style="position:relative;width:3.4rem;height:3.4rem;flex-shrink:0">
                        <div style="width:100%;height:100%;border-radius:9999px;
                                background:conic-gradient({{ $uyumRengi }} {{ $o['uyum_yuzde'] }}%, rgb(128 116 148 / .18) 0);
                                -webkit-mask:radial-gradient(farthest-side, transparent calc(100% - 7px), #000 calc(100% - 7px));
                                mask:radial-gradient(farthest-side, transparent calc(100% - 7px), #000 calc(100% - 7px))"></div>
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:800;color:{{ $uyumRengi }}">
                            %{{ $o['uyum_yuzde'] }}
                        </div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:1.3rem;font-weight:800;color:{{ $uyumRengi }}">%{{ $o['uyum_yuzde'] }}</div>
                        <div style="font-size:.72rem;color:{{ $gri }}">{{ $o['tam_uyumlu'] }} firma tam uyumlu</div>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;margin-top:1rem">
                @foreach ($ozetKartlari as $kart)
                    <div style="border-radius:.65rem;padding:.85rem;background:color-mix(in srgb, {{ $kart['renk'] }} 14%, transparent)">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
                            <span style="font-size:.68rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:{{ $gri }}">{{ $kart['ad'] }}</span>
                            <span style="width:1.8rem;height:1.8rem;border-radius:.5rem;background:rgb(128 116 148 / .12);
                                    display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <x-filament::icon :icon="$kart['ikon']" style="width:1rem;height:1rem;color:{{ $kart['renk'] }}"/>
                            </span>
                        </div>
                        <div style="font-size:1.55rem;font-weight:800;color:{{ $kart['renk'] }};margin-top:.35rem">{{ $kart['deger'] }}</div>
                    </div>
                @endforeach
            </div>
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
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:.5rem">
                @foreach ($this->kriterler as $k)
                    @php
                        $kRenk = $k['yuzde'] < 40 ? $kirmizi : ($k['yuzde'] < 75 ? $sari : $yesil);
                        $eksikSayi = max($k['toplam'] - $k['tamam'], 0);
                        $tiklanir = $k['hazir'] && $eksikSayi > 0;
                        $acik = $acikKriter === $k['anahtar'];
                    @endphp
                    <div style="border:1px solid {{ $acik ? $kRenk : 'rgb(128 116 148 / .2)' }};border-radius:.5rem;padding:.6rem .75rem;{{ $tiklanir ? 'cursor:pointer' : '' }}"
                        @if ($tiklanir) wire:click="kriterDetayAc('{{ $k['anahtar'] }}')" role="button" tabindex="0" @endif>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem">
                            <span style="display:flex;align-items:center;gap:.5rem;font-size:.83rem;font-weight:600">
                                <span style="width:1.5rem;height:1.5rem;border-radius:9999px;flex-shrink:0;
                                        background:color-mix(in srgb, {{ $kRenk }} 18%, transparent);
                                        display:flex;align-items:center;justify-content:center">
                                    <x-filament::icon :icon="$k['ikon']" style="width:.85rem;height:.85rem;color:{{ $kRenk }}"/>
                                </span>
                                {{ $k['ad'] }}
                                @unless ($k['hazir']) <span style="font-size:.65rem;color:{{ $gri }}">(modül yakında)</span> @endunless
                            </span>
                            <span style="display:flex;align-items:center;gap:.3rem;font-size:.78rem;color:{{ $gri }};white-space:nowrap">
                                {{ $k['tamam'] }}/{{ $k['toplam'] }} · %{{ $k['yuzde'] }}
                                @if ($tiklanir)
                                    <x-filament::icon icon="{{ $acik ? 'heroicon-m-chevron-up' : 'heroicon-m-chevron-down' }}" style="width:.85rem;height:.85rem"/>
                                @endif
                            </span>
                        </div>
                        <div style="height:6px;border-radius:9999px;background:rgb(128 116 148 / .2);margin-top:.4rem;overflow:hidden">
                            <div style="height:100%;width:{{ max($k['yuzde'], 1) }}%;background:{{ $kRenk }}"></div>
                        </div>

                        @if ($acik)
                            @php $eksikFirmalar = $this->acikKriterEksikFirmalar; @endphp
                            <div style="margin-top:.6rem;padding-top:.55rem;border-top:1px dashed rgb(128 116 148 / .3)" onclick="event.stopPropagation()">
                                <div style="font-size:.72rem;font-weight:700;color:{{ $kirmizi }};margin-bottom:.4rem">
                                    Bu kriterin eksik olduğu {{ count($eksikFirmalar) }} firma:
                                </div>
                                <div style="display:flex;flex-wrap:wrap;gap:.35rem">
                                    @forelse ($eksikFirmalar as $fId => $fUnvan)
                                        <a href="{{ \App\Filament\Resources\Firmas\FirmaResource::getUrl('edit', ['record' => $fId]) }}"
                                            style="display:inline-block;padding:.15rem .55rem;border-radius:9999px;font-size:.72rem;
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

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
            {{-- Yenileme Süreleri --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Yasal Evrak Yenileme Süreleri</div>
                <div style="font-size:.78rem;color:rgb(128 116 148);margin-bottom:.6rem">6331 Sayılı İSG Kanunu uyarınca zorunlu periyotlar</div>
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
                <div style="font-size:.78rem;color:rgb(128 116 148);margin-bottom:.6rem">Denetim öncesi dikkat edilmesi gereken noktalar</div>
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
                <p style="font-size:.85rem;color:rgb(128 116 148)">
                    Muayene, İSG eğitimi, MYK ve genç çalışan eksiklerini listelemek için yukarıdan bir firma seçin.
                </p>
            </div>
        @else
            @php $k = $this->calisanKarne; @endphp
            <div style="{{ $kutu }}">
                <div style="font-weight:700">{{ $this->secilenFirma->unvan }}</div>
                <div style="font-size:.82rem;color:rgb(128 116 148)">{{ $k['toplam'] }} aktif çalışan</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;margin-top:.75rem">
                    <div style="border:1px solid rgb(128 116 148 / .25);border-radius:.5rem;padding:.6rem">
                        <div style="font-size:.7rem;color:rgb(128 116 148);text-transform:uppercase">Genç Çalışan (&lt;18)</div>
                        <div style="font-size:1.3rem;font-weight:800;color:{{ $k['genc']->count() ? $sari : 'inherit' }}">{{ $k['genc']->count() }}</div>
                    </div>
                    <div style="border:1px solid rgb(128 116 148 / .25);border-radius:.5rem;padding:.6rem">
                        <div style="font-size:.7rem;color:rgb(128 116 148);text-transform:uppercase">Ağır & Tehlikeli İş</div>
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

            <div style="{{ $kutu }};font-size:.82rem;color:rgb(128 116 148)">
                📋 Şu takipler ilgili modüller kurulunca burada listelenecek:
                <strong>{{ implode(', ', $k['moduller_bekliyor']) }}</strong>.
            </div>
        @endif
    @endif
</x-filament-panels::page>
