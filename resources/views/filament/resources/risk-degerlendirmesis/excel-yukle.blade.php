@php
    $girdi = 'padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem';
    $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.75rem 1rem';
    $soluk = 'color:rgb(107 114 128)';
    $th = 'text-align:left;padding:.4rem .5rem;font-size:.72rem;border-bottom:1px solid rgb(107 114 128 / .2);vertical-align:top;min-width:150px;max-width:210px';
    $td = 'padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .1);font-size:.75rem;vertical-align:top;'.$soluk;
    $alanlar = $this->alanSecenekleri();
@endphp

<x-filament-panels::page>
    <div style="font-size:.85rem;{{ $soluk }};margin-top:-.75rem">
        Excel dosyanızı seçin. Sistem sayfaları ve sütunları algılar; <strong>hangi sütunu ne olarak anladığını</strong> aşağıda görür,
        yanlışsa açılır listeden düzeltirsiniz.
        @if ($this->degerlendirme_id)
            <strong>Maddeler mevcut değerlendirmenin sonuna eklenir; eski maddeler olduğu gibi korunur</strong>, zaten var olanlar tekrar yazılmaz.
        @else
            Onayladığınızda değerlendirme oluşturulur. Hastane formu önizlemesiz, olduğu gibi alınır.
        @endif
    </div>

    <div style="{{ $kart }}">
        <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end">
            <div>
                <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.35rem">Firma</label>
                <select wire:model="firma_id" style="{{ $girdi }};min-width:280px" @disabled($this->degerlendirme_id)>
                    <option value="">Seçin…</option>
                    @foreach ($this->firmaSecenekleri() as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
                @error('firma_id') <div style="font-size:.78rem;color:rgb(220 38 38);margin-top:.25rem">{{ $message }}</div> @enderror
            </div>
            <div>
                <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.35rem">Excel dosyaları (birden çok seçebilirsiniz)</label>
                <input type="file" wire:model="dosyalar" multiple accept=".xlsx,.xls,.xlsm" style="{{ $girdi }};max-width:420px">
            </div>
        </div>
        <div wire:loading wire:target="dosyalar" style="font-size:.8rem;{{ $soluk }};margin-top:.4rem">Dosyalar okunuyor…</div>
        @error('dosyalar.*') <div style="font-size:.8rem;color:rgb(220 38 38);margin-top:.35rem">{{ $message }}</div> @enderror
    </div>

    @if ($hatalar)
        <div style="{{ $kart }};border-color:rgb(220 38 38 / .4);background:rgb(220 38 38 / .05)">
            @foreach ($hatalar as $h)
                <div style="font-size:.82rem;color:rgb(185 28 28)">⚠ {{ $h }}</div>
            @endforeach
        </div>
    @endif

    @foreach ($girdiler as $i => $g)
        <div style="{{ $kart }}" wire:key="girdi-{{ $i }}-{{ md5($g['yol'] ?? '') }}">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem">
                <div>
                    <div style="font-weight:600">{{ $g['dosya'] }}</div>
                    <div style="font-size:.78rem;{{ $soluk }}">
                        @if ($g['tur'] === 'hastane')
                            Hastane risk formu · {{ $g['sayfa_sayisi'] }} sayfa · {{ $g['madde'] }} madde — puanlar ve biçim korunarak olduğu gibi alınır.
                        @else
                            Değerlendirme adı: {{ $g['birim'] }}
                        @endif
                    </div>
                </div>
                <button type="button" wire:click="girdiKaldir({{ $i }})" style="font-size:.78rem;color:rgb(220 38 38);cursor:pointer">Kaldır</button>
            </div>

            @if ($g['tur'] === 'standart')
                <div style="margin:.6rem 0">
                    <label style="font-size:.75rem;font-weight:600;margin-right:.5rem">Yöntem</label>
                    <select wire:model="girdiler.{{ $i }}.yontem" style="{{ $girdi }}">
                        @foreach (\App\Support\RiskExcelYukleyici::YONTEMLER as $kod => $ad)
                            <option value="{{ $kod }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                    <label style="display:inline-flex;align-items:center;gap:.4rem;margin-left:1.25rem;font-size:.8rem;cursor:pointer">
                        <input type="checkbox" wire:model="girdiler.{{ $i }}.mukerrer_sil"> Mükerrer maddeleri yazma (bölüm, faaliyet, tehlike ve risk aynıysa)
                    </label>
                </div>

                @foreach ($g['sayfalar'] as $j => $s)
                    @php
                        $sutunlar = $this->gorunenSutunlar($s);
                        $tekrar = $this->tekrarlayanAlanlar($s);
                        $tehlikeVar = in_array('tehlike', (array) $s['sutunlar'], true);
                    @endphp
                    <div style="border-top:1px solid rgb(107 114 128 / .2);padding-top:.6rem;margin-top:.6rem" wire:key="sayfa-{{ $i }}-{{ $j }}">
                        <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center">
                            <label style="display:flex;align-items:center;gap:.4rem;font-weight:600;font-size:.88rem;cursor:pointer">
                                <input type="checkbox" wire:model.live="girdiler.{{ $i }}.sayfalar.{{ $j }}.kullan"> Sayfa: {{ $s['ad'] }}
                            </label>
                            @if ($s['taninmadi'])
                                <span style="font-size:.75rem;color:rgb(217 119 6)">Başlık satırı otomatik tanınmadı — sütunları kendiniz eşleyin.</span>
                            @endif
                            <label style="font-size:.75rem;{{ $soluk }}">İlk veri satırı
                                <input type="number" min="1" wire:model.blur="girdiler.{{ $i }}.sayfalar.{{ $j }}.veri_baslangic" style="{{ $girdi }};width:5rem;margin-left:.3rem">
                            </label>
                        </div>

                        @if ($s['kullan'])
                            @if (! $tehlikeVar)
                                <div style="font-size:.78rem;color:rgb(185 28 28);margin-top:.4rem">⚠ Hiçbir sütun "Tehlike" olarak seçilmedi.</div>
                            @endif
                            @foreach ($tekrar as $alan)
                                <div style="font-size:.78rem;color:rgb(185 28 28);margin-top:.4rem">⚠ "{{ $alanlar[$alan] }}" birden çok sütuna atanmış.</div>
                            @endforeach

                            <div style="overflow-x:auto;margin-top:.5rem">
                                <table style="border-collapse:collapse;font-size:.8rem">
                                    <thead>
                                        <tr>
                                            @foreach ($sutunlar as $c => $harf)
                                                <th style="{{ $th }}">
                                                    <div style="font-weight:600;color:rgb(107 114 128)">{{ $harf }} · {{ $s['etiketler'][$c] ?? '—' }}</div>
                                                    <select wire:model.live="girdiler.{{ $i }}.sayfalar.{{ $j }}.sutunlar.{{ $c }}" style="{{ $girdi }};width:100%;margin-top:.25rem;{{ ($s['sutunlar'][$c] ?? '') !== '' ? 'border-color:var(--primary-600);font-weight:600' : '' }}">
                                                        <option value="">(kullanma)</option>
                                                        @foreach ($alanlar as $kod => $ad)
                                                            <option value="{{ $kod }}">{{ $ad }}</option>
                                                        @endforeach
                                                    </select>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($s['ornek'] as $satir)
                                            <tr>
                                                @foreach ($sutunlar as $c => $harf)
                                                    <td style="{{ $td }}">{{ $satir[$c] ?? '' }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    @endforeach

    @if ($girdiler)
        <div style="display:flex;gap:.6rem">
            <x-filament::button wire:click="kaydet" wire:loading.attr="disabled" wire:target="kaydet">Onayla ve Yükle</x-filament::button>
            <x-filament::button color="gray" wire:click="temizle">Temizle</x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
