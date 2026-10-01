@php
    $mor = 'rgb(124 58 237)';
    $girdi = 'width:100%;padding:.4rem .5rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.78rem';
    $durumRenk = ['bos' => 'rgb(156 163 175)', 'planlandi' => 'rgb(180 83 9)', 'tamamlandi' => 'rgb(21 128 61)'];
    $durumEtiket = ['bos' => 'Boş', 'planlandi' => 'Planlandı', 'tamamlandi' => 'Tamamlandı'];
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma başına yıllık, 12 aylık saha ziyaret programı. Durum hücresine tıklayarak
        Boş→Planlandı→Tamamlandı arasında geçiş yapın; isterseniz AI'dan o ay için kısa
        bir amaç/kapsam önerisi alın. Bir ayda birden fazla ziyaret varsa "+" ile ek
        satır ekleyebilirsiniz. Her ayın "Yapılacaklar" listesi Yıllık Çalışma/Eğitim
        Planı'nda o ay planlanan maddelerden otomatik gelir.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
        <div>
            <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
            <select wire:model.live="firmaId" style="{{ $girdi }};margin-top:.3rem;padding:.55rem .75rem">
                <option value="">— Firma seçin —</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-weight:600;font-size:.82rem">Yıl</label>
            <input type="number" wire:model.live="yil" style="{{ $girdi }};margin-top:.3rem;padding:.55rem .75rem">
        </div>
    </div>

    @if ($this->firma)
        <x-filament::section icon="heroicon-o-calendar" icon-color="primary">
            <x-slot name="heading">{{ $yil }} Yılı Ziyaret Programı</x-slot>

            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:900px">
                    <tr>
                        <th style="text-align:left;padding:.4rem;width:70px">Ay</th>
                        <th style="text-align:left;padding:.4rem;width:130px">Tarih</th>
                        <th style="text-align:left;padding:.4rem">Amaç / Kapsam</th>
                        <th style="text-align:left;padding:.4rem;width:90px">Süre (sa.)</th>
                        <th style="text-align:left;padding:.4rem;width:110px">Durum</th>
                        <th style="text-align:left;padding:.4rem;width:160px">Notlar</th>
                        <th style="text-align:left;padding:.4rem;width:100px" title="Yıllık plandan o ay yapılacaklar (gerçekleşen/toplam)">Yapılacaklar</th>
                        <th style="text-align:left;padding:.4rem;width:60px"></th>
                    </tr>
                    @foreach (\App\Models\ZiyaretProgrami::AYLAR as $i => $ayAdi)
                        @php $girdiler = \App\Models\ZiyaretProgrami::ayGirdileri($this->program->ziyaretler[$i] ?? null); @endphp
                        @foreach ($girdiler as $s => $z)
                            <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                                <td style="padding:.4rem;font-weight:600">
                                    {{ $s === 0 ? $ayAdi : '' }}
                                </td>
                                <td style="padding:.4rem">
                                    <input type="date" value="{{ $z['tarih'] }}"
                                        wire:change="ayGuncelle({{ $i }}, {{ $s }}, 'tarih', $event.target.value)" style="{{ $girdi }}">
                                </td>
                                <td style="padding:.4rem">
                                    <div style="display:flex;gap:.3rem">
                                        <input type="text" list="amac-katalogu" value="{{ $z['amac'] }}"
                                            wire:change="ayGuncelle({{ $i }}, {{ $s }}, 'amac', $event.target.value)" style="{{ $girdi }}">
                                        <x-filament::icon-button icon="heroicon-o-sparkles" color="primary" size="sm"
                                            wire:click="aiAmacOner({{ $i }}, {{ $s }})" tooltip="AI'dan öneri al"/>
                                    </div>
                                </td>
                                <td style="padding:.4rem">
                                    <input type="number" step="0.5" min="0" value="{{ $z['sure_saat'] }}"
                                        wire:change="ayGuncelle({{ $i }}, {{ $s }}, 'sure_saat', $event.target.value)" style="{{ $girdi }}">
                                </td>
                                <td style="padding:.4rem">
                                    <button type="button" wire:click="durumDegistir({{ $i }}, {{ $s }})"
                                        style="width:100%;padding:.4rem;border-radius:.4rem;cursor:pointer;font-size:.75rem;font-weight:700;
                                            border:1px solid {{ $durumRenk[$z['durum']] }};color:{{ $durumRenk[$z['durum']] }};background:transparent">
                                        {{ $durumEtiket[$z['durum']] }}
                                    </button>
                                </td>
                                <td style="padding:.4rem">
                                    <input type="text" value="{{ $z['notlar'] }}"
                                        wire:change="ayGuncelle({{ $i }}, {{ $s }}, 'notlar', $event.target.value)" style="{{ $girdi }}">
                                </td>
                                <td style="padding:.4rem">
                                    @if ($s === 0)
                                        @php $say = $this->aylikYapilacakSayilari[$i] ?? ['toplam' => 0, 'yapilan' => 0]; @endphp
                                        <button type="button" wire:click="yapilacakAySec({{ $i }})" title="{{ $ayAdi }} ziyaretinde yapılacakları göster"
                                            style="padding:.3rem .5rem;border-radius:.4rem;cursor:pointer;font-size:.72rem;font-weight:700;background:transparent;
                                                border:1px solid {{ $i === $yapilacakAy ? $mor : 'rgb(107 114 128 / .3)' }};color:{{ $say['toplam'] && $say['yapilan'] === $say['toplam'] ? 'rgb(21 128 61)' : 'inherit' }}">
                                            📋 {{ $say['yapilan'] }}/{{ $say['toplam'] }}
                                        </button>
                                    @endif
                                </td>
                                <td style="padding:.4rem;white-space:nowrap">
                                    @if ($s === count($girdiler) - 1)
                                        <x-filament::icon-button icon="heroicon-o-plus" color="gray" size="sm"
                                            wire:click="ziyaretEkle({{ $i }})" tooltip="Bu aya ek ziyaret ekle"/>
                                    @endif
                                    @if (count($girdiler) > 1)
                                        <x-filament::icon-button icon="heroicon-o-x-mark" color="danger" size="sm"
                                            wire:click="ziyaretSil({{ $i }}, {{ $s }})" tooltip="Bu satırı sil"/>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </table>
                <datalist id="amac-katalogu">
                    @foreach ($this->amacKategorileri as $k)
                        <option value="{{ $k }}"></option>
                    @endforeach
                </datalist>
            </div>
        </x-filament::section>

        @php
            $yapilacakAyAdi = \App\Models\ZiyaretProgrami::AYLAR[$yapilacakAy];
            $yapilacakGruplari = collect($this->yapilacaklar)->groupBy('grup');
            $ayinSayisi = $this->aylikYapilacakSayilari[$yapilacakAy] ?? ['toplam' => 0, 'yapilan' => 0];
        @endphp
        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
            <x-slot name="heading">
                {{ $yapilacakAyAdi }} Ziyaretinde Yapılacaklar
                <span style="font-weight:400;font-size:.78rem;color:rgb(107 114 128)">
                    ({{ $ayinSayisi['yapilan'] }}/{{ $ayinSayisi['toplam'] }} gerçekleşti)
                </span>
            </x-slot>
            <x-slot name="afterHeader">
                <x-filament::link :href="\App\Filament\Pages\YillikPlan\YillikCalismaPlani::getUrl(['firma' => $this->firmaId, 'yil' => $yil])" size="sm" icon="heroicon-o-arrow-top-right-on-square">
                    Yıllık Plan'da aç
                </x-filament::link>
            </x-slot>

            <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:-.5rem;margin-bottom:.8rem">
                Liste {{ $yil }} Yıllık Çalışma ve Eğitim Planı'nda bu ay <strong>P</strong> (Planlandı) işaretli
                maddelerden otomatik gelir. Kutucuğu işaretlediğinizde plandaki o ayın <strong>G</strong>
                (Gerçekleşti) hücresi de işaretlenir.
            </p>

            <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:1rem">
                @foreach (\App\Models\ZiyaretProgrami::AYLAR as $ai => $ayAdi)
                    @php
                        $say = $this->aylikYapilacakSayilari[$ai] ?? ['toplam' => 0, 'yapilan' => 0];
                        $aktif = $ai === $yapilacakAy;
                    @endphp
                    <button type="button" wire:click="yapilacakAySec({{ $ai }})"
                        style="padding:.3rem .6rem;border-radius:.4rem;cursor:pointer;font-size:.74rem;font-weight:600;
                            border:1px solid {{ $aktif ? $mor : 'rgb(107 114 128 / .3)' }};
                            background:{{ $aktif ? 'rgb(139 92 246 / .1)' : 'transparent' }};
                            color:{{ $aktif ? $mor : 'inherit' }}">
                        {{ mb_substr($ayAdi, 0, 3) }}
                        <span style="font-weight:400;color:rgb(107 114 128)">{{ $say['yapilan'] }}/{{ $say['toplam'] }}</span>
                    </button>
                @endforeach
            </div>

            @forelse ($yapilacakGruplari as $grup => $maddeler)
                <div style="margin-bottom:.8rem">
                    <div style="font-size:.66rem;font-weight:700;letter-spacing:.04em;color:var(--primary);margin-bottom:.25rem">{{ $grup }}</div>
                    @foreach ($maddeler as $m)
                        <label style="display:flex;gap:.6rem;align-items:flex-start;padding:.35rem .2rem;border-top:1px solid rgb(107 114 128 / .12);cursor:pointer">
                            <input type="checkbox" @checked($m['gerceklesti'])
                                wire:click="yapilacakGerceklesti('{{ $m['alan'] }}', {{ $m['index'] }})"
                                style="margin-top:.2rem;width:1rem;height:1rem;accent-color:#15803d">
                            <span style="flex:1;font-size:.82rem;{{ $m['gerceklesti'] ? 'text-decoration:line-through;color:rgb(107 114 128)' : '' }}">
                                {{ $m['baslik'] }}
                                @if (filled($m['sorumlu']))
                                    <span style="display:block;font-size:.7rem;color:rgb(107 114 128);text-decoration:none">{{ $m['sorumlu'] }}</span>
                                @endif
                            </span>
                            <span style="font-size:.7rem;font-weight:700;color:{{ $m['gerceklesti'] ? '#15803d' : '#b45309' }}">
                                {{ $m['gerceklesti'] ? 'G · Gerçekleşti' : 'P · Planlandı' }}
                            </span>
                        </label>
                    @endforeach
                </div>
            @empty
                <div style="text-align:center;padding:1.5rem;color:rgb(107 114 128);font-size:.85rem">
                    {{ $yapilacakAyAdi }} ayı için yıllık planda planlanmış madde yok.
                </div>
            @endforelse
        </x-filament::section>

        @php
            $takvimAyBaslangic = \Illuminate\Support\Carbon::parse($this->takvimGosterilenAy.'-01');
            $takvimGunSayisi = $takvimAyBaslangic->daysInMonth;
            $takvimBosluk = $takvimAyBaslangic->dayOfWeekIso - 1;
            $takvimGunler = $this->takvimGunler;
            $seciliGunler = $takvimGunler[$takvimSeciliTarih] ?? [];
        @endphp
        <x-filament::section icon="heroicon-o-map" icon-color="primary">
            <x-slot name="heading">Nereye Gideceğim — Aylık Takvim</x-slot>
            <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:-.5rem;margin-bottom:.8rem">
                Yukarıdaki tabloya girdiğiniz tarihli ziyaretler burada işaretlenir — Profilim &gt;
                Firma Ziyaretleri'ndeki takvimle aynı kayıtlardan gelir, sadece bu firmaya süzülmüştür.
            </p>
            <div style="display:grid;grid-template-columns:20rem 1fr;gap:1rem">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
                        <button type="button" wire:click="takvimAyDegistir(-1)" style="border:none;background:none;cursor:pointer;font-size:1rem">‹</button>
                        <div style="font-weight:700">{{ $takvimAyBaslangic->translatedFormat('F Y') }}</div>
                        <button type="button" wire:click="takvimAyDegistir(1)" style="border:none;background:none;cursor:pointer;font-size:1rem">›</button>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;font-size:.7rem;text-align:center;color:rgb(107 114 128);margin-bottom:.3rem">
                        @foreach (['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'] as $g)
                            <div>{{ $g }}</div>
                        @endforeach
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px">
                        @for ($bosluk = 0; $bosluk < $takvimBosluk; $bosluk++)
                            <div></div>
                        @endfor
                        @for ($gun = 1; $gun <= $takvimGunSayisi; $gun++)
                            @php
                                $tarih = $takvimAyBaslangic->copy()->day($gun)->toDateString();
                                $doluMu = ! empty($takvimGunler[$tarih]);
                                $seciliMi = $tarih === $takvimSeciliTarih;
                            @endphp
                            <button type="button" wire:click="takvimGunSec('{{ $tarih }}')"
                                style="aspect-ratio:1;border-radius:.35rem;border:none;cursor:pointer;font-size:.78rem;
                                    background:{{ $seciliMi ? $mor : ($doluMu ? 'rgb(139 92 246 / .15)' : 'transparent') }};
                                    color:{{ $seciliMi ? '#fff' : 'inherit' }}">
                                {{ $gun }}
                            </button>
                        @endfor
                    </div>
                </div>
                <div>
                    <div style="font-weight:700;margin-bottom:.6rem">{{ \Illuminate\Support\Carbon::parse($takvimSeciliTarih)->translatedFormat('d F Y') }}</div>
                    @forelse ($seciliGunler as $z)
                        <div style="border-top:1px solid rgb(107 114 128 / .15);padding:.5rem 0">
                            <div style="font-weight:600;font-size:.85rem">{{ $z['amac'] ?: 'Amaç belirtilmemiş' }}</div>
                            <div style="font-size:.78rem;color:rgb(107 114 128)">
                                {{ $durumEtiket[$z['durum']] ?? $z['durum'] }}
                                @if ($z['sure_saat']) · {{ $z['sure_saat'] }} saat @endif
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center;padding:2rem;color:rgb(107 114 128)">Bu tarihte ziyaret kaydınız bulunmuyor.</div>
                    @endforelse
                </div>
            </div>
        </x-filament::section>
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
