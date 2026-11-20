@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'width:100%;padding:.4rem .55rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem';
    $bugun = \Illuminate\Support\Carbon::today();
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    @include('filament.pages.partials.kvkk-rozeti')
    <p class="mehse-bolum-aciklama">
        Çalışanların işe giriş / periyodik muayene ve işe özgü tetkiklerini (odyometri, SFT,
        portör, psikoteknik vb.) takip edin. Tetkik tarihi girince sonraki tetkik tarihi
        periyoda / tehlike sınıfına göre otomatik hesaplanır. Muayene formunu (EK-2) “Muayene
        Formu” ekranından üretebilirsiniz.
    </p>

    <x-filament::section icon="heroicon-o-heart" icon-color="primary">
        <x-slot name="heading">Firma</x-slot>
        <select wire:model.live="firmaId" style="{{ $girdi }};max-width:420px">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </x-filament::section>

    @if ($this->firma)
        @php
            $ozetGuncel = 0;
            $ozetYaklasan = 0;
            $ozetGecmis = 0;
            $ozetKisitli = 0;
            foreach ($satirlar as $sOzet) {
                if (in_array($sOzet['sonuc'] ?? '', ['sartli', 'uygun_degil'], true)) {
                    $ozetKisitli++;
                }
                if (! empty($sOzet['sonraki_tarih'])) {
                    $sTarih = \Illuminate\Support\Carbon::parse($sOzet['sonraki_tarih'])->startOfDay();
                    $fark = (int) $bugun->diffInDays($sTarih, false);
                    if ($fark < 0) {
                        $ozetGecmis++;
                    } elseif ($fark <= 30) {
                        $ozetYaklasan++;
                    } else {
                        $ozetGuncel++;
                    }
                }
            }
        @endphp

        @if (count($satirlar))
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:.75rem">
                @foreach ([
                    ['Toplam takip', count($satirlar), 'inherit'],
                    ['Güncel', $ozetGuncel, '#15803d'],
                    ['Yaklaşan (30 gün)', $ozetYaklasan, '#b45309'],
                    ['Süresi geçmiş', $ozetGecmis, '#dc2626'],
                    ['Kısıtlı / Uygun değil', $ozetKisitli, '#dc2626'],
                ] as [$ad, $sayi, $renk])
                    <div style="{{ $kutu }};padding:.7rem 1rem">
                        <div class="mehse-soluk">{{ $ad }}</div>
                        <div style="font-size:1.4rem;font-weight:700;color:{{ $sayi > 0 ? $renk : 'inherit' }}">{{ $sayi }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <x-filament::section icon="heroicon-o-plus-circle" icon-color="gray">
            <x-slot name="heading">Tetkik Satırı Ekle</x-slot>

            @if ($this->calisanlar->isEmpty())
                <p style="font-size:.82rem;color:#b45309">Bu firmaya kayıtlı aktif çalışan yok — önce Çalışanlar ekranından ekleyin.</p>
            @else
                <div style="{{ $kutu }};display:grid;grid-template-columns:1.4fr 1.6fr auto auto;gap:.5rem;align-items:end">
                    <div>
                        <label style="font-size:.78rem;font-weight:600">Çalışan</label>
                        <select wire:model="yeniCalisanId" style="{{ $girdi }}">
                            <option value="">— seçin —</option>
                            @foreach ($this->calisanlar as $c)
                                <option value="{{ $c->id }}">{{ $c->ad_soyad }}@if ($c->gorev) — {{ $c->gorev }} @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.78rem;font-weight:600">Tetkik Türü</label>
                        <select wire:model="yeniTur" style="{{ $girdi }}">
                            @foreach ($this->turler as $anahtar => $tanim)
                                <option value="{{ $anahtar }}">{{ $tanim['ad'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-filament::button size="sm" wire:click="satirEkle">Ekle</x-filament::button>
                    <x-filament::button size="sm" color="gray" wire:click="tumCalisanlaraEkle" tooltip="Seçili tetkik türünü tüm aktif çalışanlara ekle">Tüm çalışanlara</x-filament::button>
                </div>
                <p style="font-size:.75rem;color:rgb(107 114 128);margin-top:.4rem">
                    {{ $this->turler[$yeniTur]['aciklama'] ?? '' }}
                </p>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
            <x-slot name="heading">Sağlık Gözetimi Listesi ({{ count($satirlar) }})</x-slot>
            <x-slot name="description">Kaydetmek için sağ üstteki “Kaydet”. Kırmızı sonraki tetkik tarihi = süresi geçmiş.</x-slot>

            @if (count($satirlar) === 0)
                <p style="color:rgb(107 114 128);font-size:.85rem">Yukarıdan tetkik satırı ekleyin.</p>
            @else
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.78rem;min-width:900px">
                        <thead>
                            <tr style="text-align:left;background:rgb(107 114 128 / .08)">
                                <th style="padding:.4rem">Çalışan</th>
                                <th style="padding:.4rem">Tetkik</th>
                                <th style="padding:.4rem;width:130px">Tetkik Tarihi</th>
                                <th style="padding:.4rem;width:110px">Sonraki</th>
                                <th style="padding:.4rem;width:140px">Sonuç</th>
                                <th style="padding:.4rem;width:120px">Rapor No</th>
                                <th style="padding:.4rem;width:110px">Kurşun (µg/dL)</th>
                                <th style="padding:.4rem"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($satirlar as $i => $s)
                                @php
                                    $sonraki = !empty($s['sonraki_tarih']) ? \Illuminate\Support\Carbon::parse($s['sonraki_tarih']) : null;
                                    $gecmis = $sonraki && $sonraki->lt($bugun);
                                    $kalanGun = $sonraki ? (int) $bugun->diffInDays($sonraki->copy()->startOfDay(), false) : null;
                                    $yaklasan = $kalanGun !== null && $kalanGun >= 0 && $kalanGun <= 30;
                                @endphp
                                <tr style="border-top:1px solid rgb(107 114 128 / .18)">
                                    <td style="padding:.35rem">
                                        <div style="font-weight:600">{{ $s['calisan_adi'] ?? '' }}</div>
                                        <div class="mehse-soluk">{{ $s['gorev'] ?? '' }}</div>
                                    </td>
                                    <td style="padding:.35rem">{{ $this->turler[$s['tetkik_turu']]['ad'] ?? $s['tetkik_turu'] }}</td>
                                    <td style="padding:.35rem"><input type="date" wire:model.blur="satirlar.{{ $i }}.tarih" style="{{ $girdi }}"></td>
                                    <td style="padding:.35rem;{{ $gecmis ? 'color:#b91c1c;font-weight:600' : ($yaklasan ? 'color:#b45309;font-weight:600' : 'color:rgb(107 114 128)') }}">
                                        {{ $sonraki ? $sonraki->format('d.m.Y') : '—' }}
                                        @if ($kalanGun !== null)
                                            <div style="font-size:.7rem;font-weight:600;color:{{ $kalanGun < 0 ? '#b91c1c' : ($yaklasan ? '#b45309' : 'rgb(107 114 128)') }}">
                                                {{ $kalanGun < 0 ? abs($kalanGun).' gün geçti' : ($kalanGun === 0 ? 'Bugün' : $kalanGun.' gün kaldı') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="padding:.35rem">
                                        <select wire:model.blur="satirlar.{{ $i }}.sonuc" style="{{ $girdi }}">
                                            @foreach ($this->sonuclar as $anahtar => $etiket)
                                                <option value="{{ $anahtar }}">{{ $etiket }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td style="padding:.35rem"><input type="text" wire:model.blur="satirlar.{{ $i }}.rapor_no" style="{{ $girdi }}"></td>
                                    <td style="padding:.35rem">
                                        @if (($s['tetkik_turu'] ?? null) === 'kan_kursunu')
                                            <input type="number" step="0.1" min="0" wire:model.blur="satirlar.{{ $i }}.kursun_deger" style="{{ $girdi }}" placeholder="µg/dL">
                                        @else
                                            <span class="mehse-soluk-metin">—</span>
                                        @endif
                                    </td>
                                    <td style="padding:.35rem;text-align:center;white-space:nowrap">
                                        @if (!empty($s['calisan_id']))
                                            <a href="{{ \App\Filament\Pages\MuayeneFormu::getUrl(['firma' => $this->firmaId, 'calisan' => $s['calisan_id']]) }}"
                                                title="Bu çalışan için EK-2 Muayene Formu oluştur"
                                                style="font-size:.72rem;font-weight:700;color:#2563eb;text-decoration:none;margin-right:.45rem">EK-2 →</a>
                                            @if (in_array($s['sonuc'] ?? '', ['sartli', 'uygun_degil'], true) || ($s['tetkik_turu'] ?? '') === 'ise_donus')
                                                <a href="{{ \App\Filament\Pages\IseDonusBelgesi::getUrl(['firma' => $this->firmaId, 'calisan' => $s['calisan_id']]) }}"
                                                    title="Bu çalışan için İşe Dönüş / Kısıtlama Belgesi hazırla"
                                                    style="font-size:.72rem;font-weight:700;color:#b45309;text-decoration:none;margin-right:.45rem">İşe Dönüş →</a>
                                            @endif
                                        @endif
                                        <button type="button" wire:click="satirSil({{ $i }})" style="color:#b91c1c;background:none;border:none;cursor:pointer;font-size:1rem">✕</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div style="margin-top:1rem">
                <label style="font-size:.8rem;font-weight:600">Genel Not</label>
                <input type="text" wire:model.blur="genelNot" style="{{ $girdi }}" placeholder="örn. Muayeneleri yapan işyeri hekimi; OSGB sözleşme no">
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
