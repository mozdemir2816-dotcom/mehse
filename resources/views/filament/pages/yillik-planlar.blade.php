@php
    $renkler = ['bos' => '#374151', 'planlandi' => '#f59e0b', 'tamamlandi' => '#10b981'];
    $etiketler = ['bos' => 'Boş', 'planlandi' => 'Planlandı', 'tamamlandi' => 'Tamamlandı'];
    $mor = 'rgb(139 92 246)';
    $p = $this->plan;
    $kilit = $this->kilitAyIndeksi;
    $egitimKategorileri = config('isg.yillik_plan.egitim_kategorileri');
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İSG mevzuatına uygun yıllık çalışma planı, eğitim planı ve değerlendirme raporu
        oluşturun; ay hücrelerine tıklayarak durumu Boş → Planlandı → Tamamlandı arasında
        değiştirin.
    </p>

    @php
        $yillikKriter = match ($sekme) {
            'egitim' => 'yillik_egitim_plani',
            'degerlendirme' => 'yillik_degerlendirme',
            default => 'yillik_calisma_plani',
        };
    @endphp
    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => $yillikKriter])

    @if ($p && $kilit > 0 && $kilit < 12)
        <div style="border:1px solid rgb(245 158 11 / .4);background:rgb(245 158 11 / .08);border-radius:.6rem;padding:.6rem .85rem;font-size:.8rem;color:#b45309">
            ⚠ Bu firmanın sözleşme başlangıcı (atanmış uzman tarihi) <strong>{{ \App\Filament\Pages\YillikPlanlar::AYLAR[$kilit] }}</strong> ayı — önceki aylar planda seçilemez ve otomatik doldurulmaz.
        </div>
    @elseif ($p && $kilit >= 12)
        <div style="border:1px solid rgb(239 68 68 / .4);background:rgb(239 68 68 / .08);border-radius:.6rem;padding:.6rem .85rem;font-size:.8rem;color:#b91c1c">
            ⚠ Bu firmanın sözleşme başlangıcı {{ $yil }} yılından sonra — bu yıl için plan ayları seçilemez.
        </div>
    @endif

    {{-- FİRMA & YIL --}}
    <x-filament::section icon="heroicon-o-calendar-days" icon-color="primary">
        <x-slot name="heading">Firma & Yıl</x-slot>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Yıl</label>
                <select wire:model.live="yil"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    @foreach (range(now()->year - 1, now()->year + 2) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    @if ($p)
        {{-- SEKMELER --}}
        <div style="display:flex;gap:.4rem;margin-bottom:1rem">
            @foreach (['calisma' => 'Yıllık Çalışma Planı', 'egitim' => 'Yıllık Eğitim Planı', 'degerlendirme' => 'Yıllık Değerlendirme Raporu'] as $anahtar => $etiket)
                @php $aktif = $sekme === $anahtar; @endphp
                <button type="button" wire:click="$set('sekme', '{{ $anahtar }}')"
                    style="padding:.5rem .9rem;border-radius:.5rem;cursor:pointer;font-size:.82rem;font-weight:600;
                        border:1px solid {{ $aktif ? $mor : 'rgb(107 114 128 / .3)' }};
                        background:{{ $aktif ? 'rgb(139 92 246 / .1)' : 'transparent' }};
                        color:{{ $aktif ? $mor : 'inherit' }}">
                    {{ $etiket }}
                </button>
            @endforeach
        </div>

        @if ($sekme === 'calisma')
            <x-filament::section icon="heroicon-o-table-cells" icon-color="primary">
                <x-slot name="heading">
                    Yıllık Çalışma Planı — {{ $yil }}
                    <span style="font-weight:400;font-size:.78rem;color:rgb(107 114 128)">
                        (<span style="color:{{ $renkler['bos'] }}">●</span> Boş
                        <span style="color:{{ $renkler['planlandi'] }}">●</span> Planlandı
                        <span style="color:{{ $renkler['tamamlandi'] }}">●</span> Tamamlandı — tıklayarak değiştirin)
                    </span>
                </x-slot>

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.75rem;min-width:1000px">
                        <tr>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Yasal Gereklilik / Faaliyet</th>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Sorumlu</th>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Frekans</th>
                            @foreach (\App\Filament\Pages\YillikPlanlar::AYLAR as $ay)
                                <th style="padding:.3rem .3rem;border-bottom:1px solid rgb(107 114 128 / .3);width:2.2rem">{{ $ay }}</th>
                            @endforeach
                            <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                        </tr>
                        @foreach (($p->faaliyetler ?? []) as $fi => $f)
                            <tr>
                                <td style="padding:.3rem .5rem">
                                    <div style="font-weight:600">{{ $f['faaliyet'] }}</div>
                                    @if (!empty($f['yasal_gereklilik']))
                                        <div style="font-size:.68rem;color:rgb(107 114 128)">{{ $f['yasal_gereklilik'] }}</div>
                                    @endif
                                </td>
                                <td style="padding:.3rem .5rem;color:rgb(107 114 128);font-size:.7rem">{{ $f['sorumlu'] ?? '—' }}</td>
                                <td style="padding:.3rem .5rem;color:rgb(107 114 128);font-size:.7rem">{{ $f['frekans'] ?? '—' }}</td>
                                @foreach (($f['aylar'] ?? array_fill(0, 12, 'bos')) as $ai => $durum)
                                    @php $kilitli = $ai < $kilit; @endphp
                                    <td style="padding:.15rem;text-align:center">
                                        <button type="button" @disabled($kilitli)
                                            @unless ($kilitli) wire:click="ayDurumDegistir('faaliyetler', {{ $fi }}, {{ $ai }})" @endunless
                                            title="{{ $kilitli ? 'Uzman atanmadan önce — seçilemez' : ($etiketler[$durum] ?? $durum) }}"
                                            style="width:1.4rem;height:1.4rem;border-radius:.25rem;border:none;background:{{ $kilitli ? '#111827' : ($renkler[$durum] ?? $renkler['bos']) }};{{ $kilitli ? 'opacity:.3;cursor:not-allowed' : 'cursor:pointer' }}"></button>
                                    </td>
                                @endforeach
                                <td style="padding:.3rem .3rem">
                                    <button type="button" wire:click="faaliyetSil({{ $fi }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div style="margin-top:1rem;display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.5rem">
                    <input type="text" wire:model="yeniFaaliyet" placeholder="Faaliyet"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <input type="text" wire:model="yeniSorumlu" placeholder="Sorumlu"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <input type="text" wire:model="yeniAciklama" placeholder="Açıklama"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <x-filament::button size="sm" wire:click="faaliyetEkle">+ Aktivite Ekle</x-filament::button>
                </div>

                <div style="margin-top:.75rem">
                    <x-filament::button size="xs" color="gray" wire:click="varsayilanaSifirla">Varsayılana Sıfırla</x-filament::button>
                </div>
            </x-filament::section>
        @endif

        @if ($sekme === 'egitim')
            <x-filament::section icon="heroicon-o-academic-cap" icon-color="primary">
                <x-slot name="heading">
                    Yıllık Eğitim Planı — {{ $yil }}
                    <span style="font-weight:400;font-size:.78rem;color:rgb(107 114 128)">(tıklayarak durumu değiştirin)</span>
                </x-slot>

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.75rem;min-width:1000px">
                        <tr>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Kategori</th>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Eğitim Konusu</th>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Süre</th>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Eğitici</th>
                            @foreach (\App\Filament\Pages\YillikPlanlar::AYLAR as $ay)
                                <th style="padding:.3rem .3rem;border-bottom:1px solid rgb(107 114 128 / .3);width:2.2rem">{{ $ay }}</th>
                            @endforeach
                            <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                        </tr>
                        @foreach (($p->egitimler ?? []) as $ei => $e)
                            <tr>
                                <td style="padding:.3rem .5rem;color:rgb(107 114 128);font-size:.7rem">{{ $egitimKategorileri[$e['kategori'] ?? ''] ?? '—' }}</td>
                                <td style="padding:.3rem .5rem;font-weight:600">{{ $e['konu'] }}</td>
                                <td style="padding:.3rem .5rem;color:rgb(107 114 128)">{{ $e['sure_saat'] ?? '—' }} saat</td>
                                <td style="padding:.3rem .5rem;color:rgb(107 114 128)">{{ $e['egitici'] ?? '—' }}</td>
                                @foreach (($e['aylar'] ?? array_fill(0, 12, 'bos')) as $ai => $durum)
                                    @php $kilitli = $ai < $kilit; @endphp
                                    <td style="padding:.15rem;text-align:center">
                                        <button type="button" @disabled($kilitli)
                                            @unless ($kilitli) wire:click="ayDurumDegistir('egitimler', {{ $ei }}, {{ $ai }})" @endunless
                                            title="{{ $kilitli ? 'Uzman atanmadan önce — seçilemez' : ($etiketler[$durum] ?? $durum) }}"
                                            style="width:1.4rem;height:1.4rem;border-radius:.25rem;border:none;background:{{ $kilitli ? '#111827' : ($renkler[$durum] ?? $renkler['bos']) }};{{ $kilitli ? 'opacity:.3;cursor:not-allowed' : 'cursor:pointer' }}"></button>
                                    </td>
                                @endforeach
                                <td style="padding:.3rem .3rem">
                                    <button type="button" wire:click="egitimSil({{ $ei }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div style="margin-top:1rem;display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:.5rem">
                    <input type="text" wire:model="yeniEgitimKonu" placeholder="Eğitim konusu"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <input type="number" wire:model="yeniEgitimSure" placeholder="Süre (saat)"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <input type="text" wire:model="yeniEgitimEgitici" placeholder="Eğitici"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <input type="text" wire:model="yeniEgitimHedefKitle" placeholder="Hedef kitle"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <x-filament::button size="sm" wire:click="egitimEkle">+ Ekle</x-filament::button>
                </div>

                <div style="margin-top:.75rem">
                    <x-filament::button size="xs" color="gray" wire:click="varsayilanaSifirla">Varsayılana Sıfırla</x-filament::button>
                </div>
            </x-filament::section>
        @endif

        @if ($sekme === 'degerlendirme')
            <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
                <x-slot name="heading">Yıllık Değerlendirme Raporu — {{ $yil }}</x-slot>

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.78rem;min-width:900px">
                        <tr>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">No</th>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Yapılan Çalışmalar</th>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tarih</th>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Yapan Kişi ve Unvanı</th>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tekrar Sayısı</th>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Kullanılan Yöntem</th>
                            <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Sonuç ve Yorum</th>
                            <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                        </tr>
                        @foreach (($p->degerlendirmeler ?? []) as $di => $d)
                            <tr>
                                <td style="padding:.3rem .5rem">{{ $di + 1 }}</td>
                                <td style="padding:.3rem .5rem;font-weight:600">{{ $d['calisma'] }}</td>
                                <td style="padding:.15rem">
                                    <input type="date" value="{{ $d['tarih'] ?? '' }}"
                                        x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'tarih', $event.target.value)"
                                        style="width:8.5rem;padding:.3rem .4rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.75rem">
                                </td>
                                <td style="padding:.15rem">
                                    <input type="text" value="{{ $d['yapan_kisi'] ?? '' }}"
                                        x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'yapan_kisi', $event.target.value)"
                                        style="width:9rem;padding:.3rem .4rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.75rem">
                                </td>
                                <td style="padding:.15rem">
                                    <input type="number" value="{{ $d['tekrar_sayisi'] ?? '' }}"
                                        x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'tekrar_sayisi', $event.target.value)"
                                        style="width:4rem;padding:.3rem .4rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.75rem">
                                </td>
                                <td style="padding:.15rem">
                                    <input type="text" value="{{ $d['yontem'] ?? '' }}"
                                        x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'yontem', $event.target.value)"
                                        style="width:9rem;padding:.3rem .4rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.75rem">
                                </td>
                                <td style="padding:.15rem">
                                    <input type="text" value="{{ $d['sonuc'] ?? '' }}"
                                        x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'sonuc', $event.target.value)"
                                        style="width:11rem;padding:.3rem .4rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.75rem">
                                </td>
                                <td style="padding:.3rem .3rem">
                                    <button type="button" wire:click="degerlendirmeSil({{ $di }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div style="margin-top:1rem;display:grid;grid-template-columns:1fr auto;gap:.5rem">
                    <input type="text" wire:model="yeniDegerlendirmeCalisma" placeholder="Yapılan çalışma"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <x-filament::button size="sm" wire:click="degerlendirmeEkle">+ Çalışma Ekle</x-filament::button>
                </div>

                <div style="margin-top:.75rem">
                    <x-filament::button size="xs" color="gray" wire:click="varsayilanaSifirla">Varsayılana Sıfırla</x-filament::button>
                </div>
            </x-filament::section>
        @endif
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
