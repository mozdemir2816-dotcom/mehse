{{--
    Düzenlenebilir eğitim konusu kontrol listesi — EgitimKatilim ve
    SertifikaOlustur'da ortak kullanılır. $icerik (EgitimIcerikOlusturucu
    çıktısı) ve $wireModelKok (örn. "icerik") bekler; her wire:model bu
    kökün altına yazılır (örn. "icerik.genel_konular.0.dahil").
--}}
@php
    $kutu = $kutu ?? 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    // Host bileşen isyerineOzguMaddeEkle/Cikar sağlıyorsa işyerine özgü maddeler
    // metin olarak da düzenlenir + eklenir/çıkarılır (EgitimKatilim). Sağlamıyorsa
    // (SertifikaOlustur) yalnız dakika/dahil düzenlenir — eski davranış.
    $konularDuzenlenebilir = ($konularDuzenlenebilir ?? false) && method_exists($this, 'isyerineOzguMaddeEkle');

    $satir = function (string $yol, array $m, int $i) use ($wireModelKok) {
        $tamYol = $wireModelKok.'.'.$yol.'.'.$i;

        return [
            'dahil_model' => $tamYol.'.dahil',
            'dakika_model' => $tamYol.'.dakika',
            'madde' => $m['madde'],
            'dahil' => $m['dahil'],
        ];
    };
@endphp

@if (($icerik['tip'] ?? null) === 'genel')
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
        @foreach ([
            'genel_konular' => 'Genel Konular',
            'saglik_konulari' => 'Sağlık Konuları',
            'teknik_konular' => 'Teknik Konular',
        ] as $anahtar => $baslik)
            @php $sure = \App\Support\EgitimIcerikOlusturucu::bolumSuresi($icerik[$anahtar]); @endphp
            <div style="{{ $kutu }}">
                <div style="font-weight:700;font-size:.85rem;margin-bottom:.5rem">
                    {{ $baslik }}
                    <span style="font-weight:400;color:rgb(107 114 128);font-size:.75rem">({{ $sure['fiili'] }} dk · Din: {{ $sure['dinlenme'] }} dk)</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:.35rem">
                    @foreach ($icerik[$anahtar] as $i => $m)
                        @php $s = $satir($anahtar, $m, $i); @endphp
                        <div style="display:flex;align-items:center;gap:.4rem;font-size:.8rem">
                            <input type="checkbox" wire:model.live="{{ $s['dahil_model'] }}">
                            <span style="flex:1;{{ ! $s['dahil'] ? 'text-decoration:line-through;color:rgb(107 114 128)' : '' }}">{{ $s['madde'] }}</span>
                            <input type="number" min="0" wire:model.live="{{ $s['dakika_model'] }}"
                                style="width:3.5rem;padding:.15rem .3rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.75rem">
                            <span style="font-size:.7rem;color:rgb(107 114 128)">dk</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div style="{{ $kutu }}">
            <div style="font-weight:700;font-size:.85rem;margin-bottom:.5rem">
                İşyerine Özgü Riskler
                @if ($icerik['isyerine_ozgu'] ?? null)
                    @php $sure = \App\Support\EgitimIcerikOlusturucu::bolumSuresi($icerik['isyerine_ozgu']['maddeler']); @endphp
                    <span style="font-weight:400;color:rgb(107 114 128);font-size:.75rem">— {{ $icerik['isyerine_ozgu']['sektor'] }} ({{ $sure['fiili'] }} dk · Din: {{ $sure['dinlenme'] }} dk)</span>
                @endif
            </div>
            @if ($icerik['isyerine_ozgu'] ?? null)
                <div style="display:flex;flex-direction:column;gap:.35rem">
                    @foreach ($icerik['isyerine_ozgu']['maddeler'] as $i => $m)
                        @php $s = $satir('isyerine_ozgu.maddeler', $m, $i); @endphp
                        <div style="display:flex;align-items:center;gap:.4rem;font-size:.8rem">
                            <input type="checkbox" wire:model.live="{{ $s['dahil_model'] }}">
                            @if ($konularDuzenlenebilir)
                                <input type="text" wire:model.live.debounce.500ms="{{ $wireModelKok }}.isyerine_ozgu.maddeler.{{ $i }}.madde"
                                    placeholder="İşe özgü konu"
                                    style="flex:1;padding:.2rem .4rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.78rem">
                            @else
                                <span style="flex:1;{{ ! $s['dahil'] ? 'text-decoration:line-through;color:rgb(107 114 128)' : '' }}">{{ $s['madde'] }}</span>
                            @endif
                            <input type="number" min="0" wire:model.live="{{ $s['dakika_model'] }}"
                                style="width:3.5rem;padding:.15rem .3rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.75rem">
                            <span style="font-size:.7rem;color:rgb(107 114 128)">dk</span>
                            @if ($konularDuzenlenebilir)
                                <button type="button" wire:click="isyerineOzguMaddeCikar({{ $i }})"
                                    style="color:#ef4444;cursor:pointer;background:none;border:none;font-size:.85rem;line-height:1">✕</button>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if ($konularDuzenlenebilir)
                    <button type="button" wire:click="isyerineOzguMaddeEkle"
                        style="margin-top:.5rem;font-size:.75rem;color:rgb(16 185 129);background:none;border:1px dashed rgb(16 185 129 / .5);border-radius:.4rem;padding:.25rem .6rem;cursor:pointer">
                        + Konu Ekle
                    </button>
                @endif
            @else
                <p style="font-size:.78rem;color:#f59e0b;margin:0">Sektör seçilmedi.</p>
            @endif
        </div>
    </div>
@else
    @php $sure = \App\Support\EgitimIcerikOlusturucu::bolumSuresi($icerik['maddeler'] ?? []); @endphp
    <div style="{{ $kutu }}">
        <div style="font-weight:700;font-size:.85rem;margin-bottom:.5rem">
            {{ $icerik['ad'] ?? '' }}
            <span style="font-weight:400;color:rgb(107 114 128);font-size:.75rem">({{ $sure['fiili'] }} dk · Din: {{ $sure['dinlenme'] }} dk)</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:.35rem">
            @foreach (($icerik['maddeler'] ?? []) as $i => $m)
                @php $s = $satir('maddeler', $m, $i); @endphp
                <div style="display:flex;align-items:center;gap:.4rem;font-size:.8rem">
                    <input type="checkbox" wire:model.live="{{ $s['dahil_model'] }}">
                    <span style="flex:1;{{ ! $s['dahil'] ? 'text-decoration:line-through;color:rgb(107 114 128)' : '' }}">{{ $s['madde'] }}</span>
                    <input type="number" min="0" wire:model.live="{{ $s['dakika_model'] }}"
                        style="width:3.5rem;padding:.15rem .3rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.75rem">
                    <span style="font-size:.7rem;color:rgb(107 114 128)">dk</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
