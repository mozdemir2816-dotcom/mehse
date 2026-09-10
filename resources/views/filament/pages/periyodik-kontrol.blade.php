@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'width:100%;padding:.4rem .55rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem';
    $durumRenk = ['gecerli' => '#16a34a', 'yaklasan' => '#d97706', 'dolmus' => '#dc2626', 'bekliyor' => '#6b7280'];
    $durumEtiket = ['gecerli' => 'Vizesi Geçerli', 'yaklasan' => 'Vize Yaklaşan', 'dolmus' => 'Süresi Dolan / Yasak', 'bekliyor' => 'Muayene Bekliyor'];
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        6331 sayılı Kanun & EKİPNET doğrulamalı muayene takip sistemi. Her iş ekipmanını kategori
        ve tipiyle tanımlayın (tip seçilince yasal standart, deney/test ve önerilen periyot
        otomatik gelir). Son muayene tarihi girilince <strong>sonraki vize</strong> hesaplanır.
    </p>

    <x-filament::section icon="heroicon-o-wrench-screwdriver" icon-color="primary">
        <x-slot name="heading">Firma</x-slot>
        <select wire:model.live="firmaId" style="{{ $girdi }};max-width:460px">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </x-filament::section>

    @if ($this->firma)
        {{-- KPI KARTLARI --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.6rem">
            @foreach ([
                ['TOPLAM EKİPMAN', $this->kpi['toplam'], 'Aktif takibe alınan iş ekipmanları', '#111827'],
                ['VİZESİ GEÇERLİ', $this->kpi['gecerli'], 'Yasal muayenesi uygun ve onaylı', '#16a34a'],
                ['VİZE YAKLAŞAN', $this->kpi['yaklasan'], '<30 gün kalanlar / randevu bekleyen', '#d97706'],
                ['SÜRESİ DOLAN / YASAK', $this->kpi['dolmus'], 'Kullanımı kanunen durdurulmalıdır', '#dc2626'],
            ] as [$baslik, $deger, $aciklama, $renk])
                <div style="{{ $kutu }}">
                    <div style="font-size:.7rem;font-weight:700;color:rgb(107 114 128);letter-spacing:.02em">{{ $baslik }}</div>
                    <div style="font-size:1.7rem;font-weight:800;color:{{ $renk }};line-height:1.1">{{ $deger }}</div>
                    <div style="font-size:.68rem;color:rgb(107 114 128)">{{ $aciklama }}</div>
                </div>
            @endforeach
        </div>

        {{-- KATEGORİ SEKMELERİ + FİLTRE --}}
        <x-filament::section>
            <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.75rem">
                <button type="button" wire:click="$set('kategoriFiltre', '')"
                    style="padding:.35rem .7rem;border-radius:999px;font-size:.78rem;cursor:pointer;
                        border:1px solid {{ $kategoriFiltre === '' ? 'rgb(16 185 129)' : 'rgb(107 114 128 / .3)' }};
                        background:{{ $kategoriFiltre === '' ? 'rgb(16 185 129 / .1)' : 'transparent' }}">
                    Tüm Kategoriler ({{ $this->kpi['toplam'] }})
                </button>
                @foreach ($this->kategoriler as $anahtar => $k)
                    @php $say = $this->kategoriSayaclari[$anahtar] ?? 0; @endphp
                    <button type="button" wire:click="$set('kategoriFiltre', '{{ $anahtar }}')"
                        title="{{ $k['mevzuat'] }}"
                        style="padding:.35rem .7rem;border-radius:999px;font-size:.78rem;cursor:pointer;
                            border:1px solid {{ $kategoriFiltre === $anahtar ? 'rgb(16 185 129)' : 'rgb(107 114 128 / .3)' }};
                            background:{{ $kategoriFiltre === $anahtar ? 'rgb(16 185 129 / .1)' : 'transparent' }}">
                        {{ $k['ad'] }} ({{ $say }})
                    </button>
                @endforeach
            </div>

            <div style="display:flex;gap:.6rem;flex-wrap:wrap">
                <input type="text" wire:model.live.debounce.400ms="arama" placeholder="Ekipman adı, seri no veya marka ara…"
                    style="{{ $girdi }};max-width:340px">
                <select wire:model.live="durumFiltre" style="{{ $girdi }};max-width:200px">
                    <option value="">Tüm Durumlar</option>
                    <option value="gecerli">Vizesi Geçerli</option>
                    <option value="yaklasan">Vize Yaklaşan</option>
                    <option value="dolmus">Süresi Dolan</option>
                    <option value="bekliyor">Muayene Bekliyor</option>
                </select>
            </div>
        </x-filament::section>

        {{-- EKİPMAN LİSTESİ --}}
        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
            <x-slot name="heading">İş Ekipmanları ({{ count($satirlar) }})</x-slot>
            <x-slot name="description">Muayene tarihi / sonuç / rapor no alanlarını satır içinde girip sağ üstteki “Kaydet” ile yazın. Yeni ekipman için “Yeni Ekipman Tanımla”.</x-slot>

            @if (count($satirlar) === 0)
                <div style="text-align:center;padding:2rem 1rem;color:rgb(107 114 128)">
                    <p style="font-weight:600">Kayıtlı İş Ekipmanı Bulunamadı</p>
                    <p style="font-size:.82rem">Filtre kriterlerinize uygun kayıt yok. “Yeni Ekipman Tanımla” ile ekleyin.</p>
                </div>
            @else
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.76rem;min-width:1100px">
                        <thead>
                            <tr style="text-align:left;background:rgb(107 114 128 / .08)">
                                <th style="padding:.4rem">Ekipman</th>
                                <th style="padding:.4rem;width:90px">Seri / Marka</th>
                                <th style="padding:.4rem;width:64px">Periyot (Ay)</th>
                                <th style="padding:.4rem;width:130px">Son Muayene</th>
                                <th style="padding:.4rem;width:110px">Sonraki Vize</th>
                                <th style="padding:.4rem;width:150px">Muayene Yapan / Rapor No</th>
                                <th style="padding:.4rem;width:150px">Sonuç</th>
                                <th style="padding:.4rem;width:110px">Vize Durumu</th>
                                <th style="padding:.4rem;width:36px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sonKat = null; @endphp
                            @foreach ($satirlar as $i => $s)
                                @if ($s['kategori_adi'] !== $sonKat)
                                    <tr><td colspan="9" style="background:rgb(107 114 128 / .06);font-weight:700;font-size:.72rem;padding:.3rem .4rem">{{ $s['kategori_adi'] }}</td></tr>
                                    @php $sonKat = $s['kategori_adi']; @endphp
                                @endif
                                <tr style="border-top:1px solid rgb(107 114 128 / .18)">
                                    <td style="padding:.35rem">
                                        <div style="font-weight:600">{{ $s['ekipman_adi'] }}</div>
                                        <div style="font-size:.66rem;color:rgb(107 114 128)">
                                            {{ $s['konum'] ?: '—' }}@if ($s['kapasite']) · {{ $s['kapasite'] }} @endif
                                            @if ($s['yasal_standart']) · {{ $s['yasal_standart'] }} @endif
                                        </div>
                                    </td>
                                    <td style="padding:.35rem;font-size:.7rem">{{ $s['seri_no'] ?: '—' }}<br><span style="color:rgb(107 114 128)">{{ $s['marka_model'] ?: '' }}</span></td>
                                    <td style="padding:.35rem"><input type="number" min="1" wire:model.blur="satirlar.{{ $i }}.muayene_periyodu_ay" style="{{ $girdi }}"></td>
                                    <td style="padding:.35rem"><input type="date" wire:model.blur="satirlar.{{ $i }}.son_muayene_tarihi" style="{{ $girdi }}"></td>
                                    <td style="padding:.35rem;font-size:.72rem;color:rgb(107 114 128)">{{ $s['sonraki_vize_tarihi'] ? \Illuminate\Support\Carbon::parse($s['sonraki_vize_tarihi'])->format('d.m.Y') : '—' }}</td>
                                    <td style="padding:.35rem">
                                        <input type="text" wire:model.blur="satirlar.{{ $i }}.muayene_yapan" placeholder="A tipi muayene kuruluşu" style="{{ $girdi }};margin-bottom:.2rem">
                                        <input type="text" wire:model.blur="satirlar.{{ $i }}.rapor_no" placeholder="Rapor no" style="{{ $girdi }}">
                                    </td>
                                    <td style="padding:.35rem">
                                        <select wire:model.blur="satirlar.{{ $i }}.sonuc" style="{{ $girdi }}">
                                            @foreach ($this->sonuclar as $anahtar => $etiket)
                                                <option value="{{ $anahtar }}">{{ $etiket }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td style="padding:.35rem">
                                        <span style="display:inline-block;background:{{ $durumRenk[$s['vize_durumu']] }};color:#fff;font-size:.68rem;font-weight:600;border-radius:.3rem;padding:.15rem .45rem">
                                            {{ $durumEtiket[$s['vize_durumu']] }}
                                        </span>
                                        @if ($s['kalan_gun'] !== null && $s['vize_durumu'] !== 'bekliyor')
                                            <div style="font-size:.66rem;color:rgb(107 114 128);margin-top:.15rem">{{ $s['kalan_gun'] }} gün</div>
                                        @endif
                                    </td>
                                    <td style="padding:.35rem;text-align:center">
                                        <button type="button" wire:click="ekipmanSil({{ $s['id'] }})"
                                            wire:confirm="Bu ekipman silinsin mi?"
                                            style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem">✕</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div style="margin-top:1rem">
                <label style="font-size:.8rem;font-weight:600">Genel Not</label>
                <input type="text" wire:model.blur="genelNot" style="{{ $girdi }}" placeholder="örn. Kapasite raporu tarih/no; muayeneleri yapan A tipi muayene kuruluşu">
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
