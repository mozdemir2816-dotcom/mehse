@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'width:100%;padding:.35rem .5rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.78rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Hangi işte hangi KKD'nin gerektiğini belirleyin. Sektörel katalogdan iş kalemi ekleyin
        (varsayılan KKD önerileriyle gelir) veya serbest satır girin; her hücreyi elle düzenleyin
        (“✔”, “Gerekirse”, standart no ya da serbest metin). Matris, risk değerlendirmesinin ekidir.
    </p>

    <x-filament::section icon="heroicon-o-shield-exclamation" icon-color="primary">
        <x-slot name="heading">Firma</x-slot>
        <select wire:model.live="firmaId" style="{{ $girdi }};max-width:420px">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </x-filament::section>

    @if ($this->firma)
        <x-filament::section icon="heroicon-o-plus-circle" icon-color="gray" collapsible collapsed>
            <x-slot name="heading">İş Kalemi Ekle</x-slot>

            <div style="display:flex;flex-direction:column;gap:.5rem">
                @foreach ($this->katalog as $grup => $maddeler)
                    <div style="{{ $kutu }}">
                        <div style="font-weight:600;font-size:.85rem;margin-bottom:.5rem">{{ $grup }}</div>
                        <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                            @foreach ($maddeler as $m)
                                <x-filament::button size="xs" color="gray" icon="heroicon-o-plus"
                                    wire:click="katalogdanEkle('{{ addslashes($grup) }}', '{{ addslashes($m['ad']) }}')">{{ $m['ad'] }}</x-filament::button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="{{ $kutu }};margin-top:.75rem;display:flex;gap:.5rem;align-items:end">
                <div style="flex:1">
                    <label style="font-size:.78rem;font-weight:600">İş kalemi / görev (serbest)</label>
                    <input type="text" wire:model="yeniIsKalemi" style="{{ $girdi }}" placeholder="örn. Vinç ile yük kaldırma">
                </div>
                <x-filament::button size="sm" wire:click="serbestEkle">Ekle</x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-table-cells" icon-color="primary">
            <x-slot name="heading">KKD Matrisi ({{ count($satirlar) }} iş kalemi)</x-slot>
            <x-slot name="description">Kaydetmek için sağ üstteki “Kaydet”. Hücreye “✔”, “Gerekirse” veya standart yazın; boş bırakılırsa gerekli değil sayılır.</x-slot>

            @if (count($satirlar) === 0)
                <p style="color:rgb(107 114 128);font-size:.85rem">Yukarıdan iş kalemi ekleyin.</p>
            @else
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.75rem;min-width:1100px">
                        <thead>
                            <tr style="text-align:left;background:rgb(107 114 128 / .08)">
                                <th style="padding:.4rem;min-width:150px">İş Kalemi</th>
                                @foreach ($this->sutunlar as $anahtar => $tam)
                                    <th style="padding:.4rem;font-size:.68rem" title="{{ $tam }}">{{ trim(explode('(', $tam)[0]) }}</th>
                                @endforeach
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($satirlar as $i => $s)
                                <tr style="border-top:1px solid rgb(107 114 128 / .18)">
                                    <td style="padding:.3rem">
                                        <input type="text" wire:model.blur="satirlar.{{ $i }}.is_kalemi" style="{{ $girdi }};font-weight:600">
                                        <div style="font-size:.66rem;color:rgb(107 114 128)">{{ $s['grup'] ?? '' }}</div>
                                    </td>
                                    @foreach (array_keys($this->sutunlar) as $anahtar)
                                        <td style="padding:.3rem"><input type="text" wire:model.blur="satirlar.{{ $i }}.{{ $anahtar }}" style="{{ $girdi }};text-align:center" placeholder="–"></td>
                                    @endforeach
                                    <td style="padding:.3rem;text-align:center">
                                        <button type="button" wire:click="satirSil({{ $i }})" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem">✕</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div style="margin-top:1rem">
                <label style="font-size:.8rem;font-weight:600">Genel Not</label>
                <input type="text" wire:model.blur="genelNot" style="{{ $girdi }}" placeholder="örn. Tüm KKD'ler CE işaretli ve zimmetle teslim edilir; risk değerlendirmesi ref. no">
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-book-open" icon-color="gray" collapsible collapsed>
            <x-slot name="heading">KKD Standartları ve Teknik Özellikleri Rehberi</x-slot>
            <x-slot name="description">Salt-okunur referans (Şantiye KKD Teknik Standartlar Matrisi) — yukarıdaki tabloya hiçbir etkisi yoktur.</x-slot>

            <div style="display:flex;flex-direction:column;gap:1rem">
                <div style="{{ $kutu }}">
                    <div style="font-weight:600;font-size:.85rem;margin-bottom:.4rem">Bu firma için geçerli faaliyet bölümleri</div>
                    <p style="font-size:.72rem;color:rgb(107 114 128);margin:0 0 .5rem">
                        Firma her inşaat aşamasını yapmıyor olabilir (örn. sadece kazı, veya sadece dış cephe/iskele/sıva/boya).
                        Hiçbiri seçilmezse tüm bölümler gösterilir. Seçim bu firma için kaydedilir.
                    </p>
                    <div style="display:flex;flex-wrap:wrap;gap:.9rem">
                        @foreach ($this->faaliyetBolumleri as $bolum)
                            <label style="display:flex;align-items:center;gap:.35rem;font-size:.78rem;cursor:pointer">
                                <input type="checkbox" wire:click="bolumToggle('{{ addslashes($bolum) }}')" @checked(in_array($bolum, $gecerliBolumler, true))>
                                {{ $bolum }}
                            </label>
                        @endforeach
                    </div>
                </div>

                @foreach ($this->teknikStandartlar as $bolum => $bolumSatirlari)
                    <div style="{{ $kutu }}">
                        <div style="font-weight:600;font-size:.85rem;margin-bottom:.5rem">{{ $bolum }}</div>
                        <div style="overflow-x:auto">
                            <table style="width:100%;border-collapse:collapse;font-size:.72rem;min-width:900px">
                                <thead>
                                    <tr style="text-align:left;background:rgb(107 114 128 / .08)">
                                        <th style="padding:.3rem">İş Kolu</th>
                                        <th style="padding:.3rem">Vücut Bölgesi</th>
                                        <th style="padding:.3rem">KKD Tanımı</th>
                                        <th style="padding:.3rem">Standart</th>
                                        <th style="padding:.3rem">Performans / Kod Detayı</th>
                                        <th style="padding:.3rem">Koruma Amacı</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bolumSatirlari as $s)
                                        <tr style="border-top:1px solid rgb(107 114 128 / .18)">
                                            <td style="padding:.3rem">{{ $s['is_kolu'] }}</td>
                                            <td style="padding:.3rem">{{ $s['vucut_bolgesi'] }}</td>
                                            <td style="padding:.3rem;font-weight:600">{{ $s['kkd'] }}</td>
                                            <td style="padding:.3rem">{{ $s['standart'] }}</td>
                                            <td style="padding:.3rem">{{ $s['performans'] }}</td>
                                            <td style="padding:.3rem;color:rgb(107 114 128)">{{ $s['koruma_amaci'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                <div style="{{ $kutu }}">
                    <div style="font-weight:600;font-size:.85rem;margin-bottom:.5rem">Standart Kod Okuma Rehberi</div>
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse;font-size:.72rem;min-width:900px">
                            <thead>
                                <tr style="text-align:left;background:rgb(107 114 128 / .08)">
                                    <th style="padding:.3rem">Standart</th>
                                    <th style="padding:.3rem">Temel Tanım</th>
                                    <th style="padding:.3rem">Kodlama / Performans Basamakları</th>
                                    <th style="padding:.3rem">Sahadaki Pratik Karşılığı</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->standartRehberi as $r)
                                    <tr style="border-top:1px solid rgb(107 114 128 / .18)">
                                        <td style="padding:.3rem;font-weight:600">{{ $r['standart'] }}</td>
                                        <td style="padding:.3rem">{{ $r['tanim'] }}</td>
                                        <td style="padding:.3rem">{{ $r['kodlama'] }}</td>
                                        <td style="padding:.3rem;color:rgb(107 114 128)">{{ $r['pratik'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
