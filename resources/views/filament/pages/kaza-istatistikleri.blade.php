@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'width:100%;padding:.4rem .55rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem';
    $ozet = $this->ozet;
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İş Kazası Raporları ve Olay Kayıtları (iş kazası tipi) seçilen yıl için otomatik hesaba
        katılır. Aylık çalışan/çalışma saati verisini girin; Kaza Sıklık Oranı ve Kaza Ağırlık
        Oranı hesaplansın. Rapor, yıllık değerlendirmenin ekidir.
    </p>

    <x-filament::section icon="heroicon-o-chart-bar-square" icon-color="primary">
        <x-slot name="heading">Firma & Yıl</x-slot>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:end">
            <div style="flex:1;min-width:240px">
                <label style="font-size:.78rem;font-weight:600">Firma</label>
                <select wire:model.live="firmaId" style="{{ $girdi }}">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div style="width:110px">
                <label style="font-size:.78rem;font-weight:600">Yıl</label>
                <input type="number" min="2000" max="2100" wire:model.live.debounce.500ms="yil" style="{{ $girdi }}">
            </div>
            <div style="min-width:200px">
                <label style="font-size:.78rem;font-weight:600">Hesaplama Standardı</label>
                <select wire:model.live="standart" style="{{ $girdi }}">
                    @foreach ($this->standartlar as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- ÖZET KARTLARI --}}
        <x-filament::section icon="heroicon-o-calculator" icon-color="primary">
            <x-slot name="heading">{{ $yil }} Yılı Özeti</x-slot>
            <x-slot name="description">Değerler formdaki güncel verilere göre anlık hesaplanır; kalıcı olması için “Kaydet”.</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem">
                @foreach ([
                    ['Hesaba Dahil Kaza', $ozet['kaza_sayisi'] ?? 0],
                    ['Kayıp Zamanlı Kaza', $ozet['kayip_zamanli'] ?? 0],
                    ['Ölümlü Kaza', $ozet['olumlu'] ?? 0],
                    ['Toplam Çalışma Saati', number_format($ozet['toplam_saat'] ?? 0, 0, ',', '.')],
                    ['Toplam Kayıp Gün', $ozet['toplam_kayip_gun'] ?? 0],
                    ['Kaza Sıklık Oranı', ($ozet['siklik'] ?? null) !== null ? $ozet['siklik'] : '—'],
                    ['Kaza Ağırlık Oranı', ($ozet['agirlik'] ?? null) !== null ? $ozet['agirlik'] : '—'],
                ] as [$etiket, $deger])
                    <div style="{{ $kutu }};text-align:center">
                        <div style="font-size:1.35rem;font-weight:700">{{ $deger }}</div>
                        <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $etiket }}</div>
                    </div>
                @endforeach
            </div>

            @if (($ozet['toplam_saat'] ?? 0) === 0)
                <p style="margin-top:.6rem;font-size:.78rem;color:#f59e0b">
                    Sıklık/Ağırlık oranı için aşağıdaki aylık tabloya çalışma saati girin.
                </p>
            @endif
        </x-filament::section>

        {{-- AYLIK ÇALIŞMA VERİLERİ --}}
        <x-filament::section icon="heroicon-o-calendar-days" icon-color="gray">
            <x-slot name="heading">Aylık Çalışma Verileri</x-slot>
            <x-slot name="description">Çalışan sayısını girince çalışma saati önerilir (kişi başı ~{{ config('isg.kaza_istatistik.aylik_kisi_saat') }} saat/ay); elle değiştirebilirsiniz.</x-slot>

            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:520px">
                    <thead>
                        <tr style="text-align:left;background:rgb(107 114 128 / .08)">
                            <th style="padding:.4rem">Ay</th>
                            <th style="padding:.4rem;width:150px">Ort. Çalışan</th>
                            <th style="padding:.4rem;width:180px">Çalışma Saati</th>
                            <th style="padding:.4rem;width:60px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->aylar as $ay => $ad)
                            <tr style="border-top:1px solid rgb(107 114 128 / .18)">
                                <td style="padding:.35rem;font-weight:600">{{ $ad }}</td>
                                <td style="padding:.35rem"><input type="number" min="0" wire:model.blur="aylikVeriler.{{ $ay }}.ort_calisan" style="{{ $girdi }}"></td>
                                <td style="padding:.35rem"><input type="number" min="0" wire:model.blur="aylikVeriler.{{ $ay }}.calisma_saati" style="{{ $girdi }}"></td>
                                <td style="padding:.35rem">
                                    <x-filament::button size="xs" color="gray" wire:click="calismaSaatiOner({{ $ay }})" title="Çalışan × {{ config('isg.kaza_istatistik.aylik_kisi_saat') }}">öner</x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- KAZALAR --}}
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="gray">
            <x-slot name="heading">Hesaba Dahil Kazalar ({{ count($ozet['kazalar'] ?? []) }})</x-slot>
            <x-slot name="description">İş Kazası Raporları ve Olay Kayıtları otomatik gelir. Tutanağı sisteme girilmemiş kazaları “Harici Kaza” olarak ekleyin.</x-slot>

            @if (count($ozet['kazalar'] ?? []))
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;margin-bottom:.75rem">
                    <thead>
                        <tr style="text-align:left;background:rgb(107 114 128 / .08)">
                            <th style="padding:.35rem">Tarih</th><th style="padding:.35rem">Kaynak</th>
                            <th style="padding:.35rem">Kayıp Gün</th><th style="padding:.35rem">Ölümlü</th><th style="padding:.35rem">Açıklama</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ozet['kazalar'] as $k)
                            <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                                <td style="padding:.3rem">{{ $k['tarih'] ? \Illuminate\Support\Carbon::parse($k['tarih'])->format('d.m.Y') : '—' }}</td>
                                <td style="padding:.3rem">{{ $k['kaynak'] }}</td>
                                <td style="padding:.3rem">{{ $k['kayip_gunu'] }}</td>
                                <td style="padding:.3rem">{{ $k['olumlu'] ? 'Evet' : '' }}</td>
                                <td style="padding:.3rem">{{ $k['aciklama'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="font-size:.82rem;color:rgb(107 114 128)">Bu yıl için kaza kaydı yok.</p>
            @endif

            <div style="font-weight:600;font-size:.82rem;margin:.5rem 0 .35rem">Harici Kaza Ekle</div>
            @foreach ($hariciKazalar as $i => $h)
                <div style="display:grid;grid-template-columns:140px 1fr 110px auto auto;gap:.5rem;align-items:center;margin-bottom:.35rem">
                    <input type="date" wire:model.blur="hariciKazalar.{{ $i }}.tarih" style="{{ $girdi }}">
                    <input type="text" wire:model.blur="hariciKazalar.{{ $i }}.aciklama" placeholder="Açıklama" style="{{ $girdi }}">
                    <input type="number" min="0" wire:model.blur="hariciKazalar.{{ $i }}.kayip_gunu" placeholder="Kayıp gün" style="{{ $girdi }}">
                    <label style="display:flex;align-items:center;gap:.3rem;font-size:.78rem"><input type="checkbox" wire:model.blur="hariciKazalar.{{ $i }}.olumlu"> Ölümlü</label>
                    <button type="button" wire:click="hariciKazaSil({{ $i }})" style="color:#ef4444;background:none;border:none;cursor:pointer">✕</button>
                </div>
            @endforeach
            <x-filament::button size="xs" color="gray" wire:click="hariciKazaEkle" icon="heroicon-o-plus">Harici kaza satırı</x-filament::button>

            <div style="margin-top:1rem">
                <label style="font-size:.8rem;font-weight:600">Not / Değerlendirme</label>
                <input type="text" wire:model.blur="not" style="{{ $girdi }}" placeholder="örn. önceki yıla göre sıklık oranındaki değişim ve alınan önlemler">
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
