@php
    $sekmeler = \App\Filament\Pages\KontrolMerkezi::SEKMELER;
    $mor = 'rgb(139 92 246)';
    $yesil = 'rgb(34 197 94)';
    $kirmizi = 'rgb(239 68 68)';
    $sari = 'rgb(245 158 11)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $o = $this->ozet;
@endphp

<x-filament-panels::page>
    {{-- BAŞLIK --}}
    <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
        <span style="font-size:1.15rem;font-weight:700">İSG Komuta Merkezi</span>
        <span style="display:inline-flex;align-items:center;gap:.35rem;background:rgb(34 197 94 / .15);color:{{ $yesil }};font-size:.72rem;font-weight:600;padding:.2rem .6rem;border-radius:9999px">
            <span style="width:.5rem;height:.5rem;border-radius:9999px;background:{{ $yesil }}"></span> Canlı İSG Takibi
        </span>
    </div>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Tüm firmalarınızın yasal belgeleri, yaklaşan işleri ve çalışan eksiklerini tek kontrol merkezinde.
    </p>

    {{-- SEKME PILL'LERİ --}}
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;background:rgb(107 114 128 / .1);padding:.3rem;border-radius:.6rem">
        @foreach ($sekmeler as $anahtar => $ad)
            @php $aktif = $sekme === $anahtar; @endphp
            <button type="button" wire:click="sekmeSec('{{ $anahtar }}')"
                style="flex:1;min-width:120px;padding:.5rem .75rem;border:none;border-radius:.45rem;cursor:pointer;font-weight:600;font-size:.85rem;
                    background:{{ $aktif ? $mor : 'transparent' }};color:{{ $aktif ? '#fff' : 'inherit' }}">
                {{ $ad }}
            </button>
        @endforeach
    </div>

    {{-- FİRMA SEÇ (firma / calisan sekmeleri) --}}
    @if (in_array($sekme, ['firma', 'calisan'], true))
        <div>
            <label style="font-weight:600;font-size:.82rem">Firma Seçin</label>
            <select wire:model.live="firmaId"
                style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
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
                <p style="font-size:.85rem;color:rgb(107 114 128)">
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
                            <div style="font-size:.8rem;color:rgb(107 114 128)">{{ $is['firma'] }}</div>
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
                    <div style="font-size:.8rem;color:rgb(107 114 128)">{{ $o['firma'] }} Firma · {{ $o['calisan'] }} Çalışan — anlık yasal uyum tablosu</div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:1.4rem;font-weight:800;color:{{ $o['uyum_yuzde'] < 40 ? $kirmizi : ($o['uyum_yuzde'] < 75 ? $sari : $yesil) }}">%{{ $o['uyum_yuzde'] }}</div>
                    <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $o['tam_uyumlu'] }} firma tam uyumlu</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.6rem;margin-top:1rem">
                @foreach ([
                    ['Toplam Firma', $o['firma'], 'rgb(107 114 128)'],
                    ['Evrak Eksiği Olan', $o['evrak_eksigi'], $kirmizi],
                    ['Çalışan Uyarılı', 0, $sari],
                    ['Tam Uyumlu', $o['tam_uyumlu'], $yesil],
                ] as [$etiket, $deger, $renk])
                    <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.5rem;padding:.6rem .75rem">
                        <div style="font-size:.7rem;color:rgb(107 114 128);text-transform:uppercase">{{ $etiket }}</div>
                        <div style="font-size:1.3rem;font-weight:800;color:{{ $renk }}">{{ $deger }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Yasal Kriterlerin Portföy Tamamlanma Oranları --}}
        <div style="{{ $kutu }}">
            <div style="font-weight:700">Yasal Kriterlerin Portföy Tamamlanma Oranları</div>
            <div style="font-size:.8rem;color:rgb(107 114 128);margin-bottom:.75rem">
                Takip edilen {{ count($this->kriterler) }} kriterin firmalarınızdaki karşılanma yüzdeleri · {{ $o['firma'] }} firma tarandı
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:.5rem">
                @foreach ($this->kriterler as $k)
                    <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.5rem;padding:.6rem .75rem">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem">
                            <span style="display:flex;align-items:center;gap:.4rem;font-size:.83rem;font-weight:600">
                                <x-filament::icon :icon="$k['ikon']" style="width:1rem;height:1rem"/>
                                {{ $k['ad'] }}
                                @unless ($k['hazir']) <span style="font-size:.65rem;color:rgb(107 114 128)">(modül yakında)</span> @endunless
                            </span>
                            <span style="font-size:.78rem;color:rgb(107 114 128);white-space:nowrap">{{ $k['tamam'] }}/{{ $k['toplam'] }} · %{{ $k['yuzde'] }}</span>
                        </div>
                        <div style="height:6px;border-radius:9999px;background:rgb(107 114 128 / .2);margin-top:.4rem;overflow:hidden">
                            <div style="height:100%;width:{{ max($k['yuzde'], 1) }}%;background:{{ $k['yuzde'] < 40 ? $kirmizi : ($k['yuzde'] < 75 ? $sari : $yesil) }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
            {{-- Yenileme Süreleri --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Yasal Evrak Yenileme Süreleri</div>
                <div style="font-size:.78rem;color:rgb(107 114 128);margin-bottom:.6rem">6331 Sayılı İSG Kanunu uyarınca zorunlu periyotlar</div>
                @foreach (config('isg.tehlike_siniflari') as $anahtar => $ad)
                    <div style="display:flex;justify-content:space-between;padding:.35rem 0;border-top:1px solid rgb(107 114 128 / .15);font-size:.85rem">
                        <span>{{ $ad }} Sınıf</span>
                        <strong>{{ config('isg.risk_gecerlilik_yili.'.$anahtar) }} Yılda Bir</strong>
                    </div>
                @endforeach
            </div>

            {{-- Uzman Tavsiyeleri --}}
            <div style="{{ $kutu }}">
                <div style="font-weight:700">Uzman Tavsiyeleri & Aksiyonlar</div>
                <div style="font-size:.78rem;color:rgb(107 114 128);margin-bottom:.6rem">Denetim öncesi dikkat edilmesi gereken noktalar</div>
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
                <p style="font-size:.85rem;color:rgb(107 114 128)">
                    Muayene, İSG eğitimi, MYK ve genç çalışan eksiklerini listelemek için yukarıdan bir firma seçin.
                </p>
            </div>
        @else
            @php $k = $this->calisanKarne; @endphp
            <div style="{{ $kutu }}">
                <div style="font-weight:700">{{ $this->secilenFirma->unvan }}</div>
                <div style="font-size:.82rem;color:rgb(107 114 128)">{{ $k['toplam'] }} aktif çalışan</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;margin-top:.75rem">
                    <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.5rem;padding:.6rem">
                        <div style="font-size:.7rem;color:rgb(107 114 128);text-transform:uppercase">Genç Çalışan (&lt;18)</div>
                        <div style="font-size:1.3rem;font-weight:800;color:{{ $k['genc']->count() ? $sari : 'inherit' }}">{{ $k['genc']->count() }}</div>
                    </div>
                    <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.5rem;padding:.6rem">
                        <div style="font-size:.7rem;color:rgb(107 114 128);text-transform:uppercase">Ağır & Tehlikeli İş</div>
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

            <div style="{{ $kutu }};font-size:.82rem;color:rgb(107 114 128)">
                📋 Şu takipler ilgili modüller kurulunca burada listelenecek:
                <strong>{{ implode(', ', $k['moduller_bekliyor']) }}</strong>.
            </div>
        @endif
    @endif
</x-filament-panels::page>
