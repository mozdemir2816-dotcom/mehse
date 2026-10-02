@php
    $inp = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $lbl = 'font-weight:600;font-size:.82rem';
    $th = 'text-align:left;padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3);font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:rgb(107 114 128)';
    $td = 'padding:.45rem .5rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $sekmeStil = fn (string $s) => 'padding:.45rem .85rem;border-radius:999px;cursor:pointer;font-weight:600;font-size:.82rem;border:1px solid '
        .($sekme === $s ? 'rgb(124 58 237);background:rgb(124 58 237);color:#fff' : 'rgb(107 114 128 / .35);background:transparent');
    $durumRenk = ['kayit_yok' => '#b91c1c', 'dolmus' => '#c2410c', 'yaklasan' => '#b45309', 'gecerli' => '#15803d'];
    $sinif = $this->firma?->tehlike_sinifi;
    $yil = $sinif ? config('isg.egitim_yenileme_yili.'.$sinif) : null;
@endphp

<x-filament-panels::page>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <button type="button" wire:click="$set('sekme', 'yenileme')" style="{{ $sekmeStil('yenileme') }}">Yenileme Takibi</button>
        <button type="button" wire:click="$set('sekme', 'kayitlar')" style="{{ $sekmeStil('kayitlar') }}">Yüz Yüze Kayıtlar</button>
        <a href="{{ \App\Filament\Pages\EgitimKatilim::getUrl(array_filter(['firma' => $firmaId])) }}" style="{{ $sekmeStil('-') }};text-decoration:none;color:inherit">+ Yeni Eğitim (Katılım Formu)</a>
        <a href="{{ \App\Filament\Pages\UzaktanEgitimAtama::getUrl() }}" style="{{ $sekmeStil('-') }};text-decoration:none;color:inherit">Uzaktan Eğitim</a>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
        <div>
            <label style="{{ $lbl }}">İşyeri</label>
            <select wire:model.live="firmaId" style="{{ $inp }}">
                <option value="">{{ $sekme === 'kayitlar' ? 'Tüm işyerleri' : 'İşyeri seçin' }}</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="{{ $lbl }}">Ara</label>
            <input type="search" wire:model.live.debounce.400ms="arama" placeholder="{{ $sekme === 'kayitlar' ? 'Eğitim, firma veya yer ara…' : 'Personel, görev veya bölüm ara…' }}" style="{{ $inp }}">
        </div>
    </div>

    @if ($sekme === 'yenileme')
        @if (! $this->firma)
            <p style="font-size:.85rem;color:#f59e0b">Çalışanların eğitim durumunu görmek için bir işyeri seçin.</p>
        @else
            @php $o = $this->ozet; @endphp
            <div style="border:1px solid rgb(124 58 237 / .3);background:rgb(124 58 237 / .05);border-radius:.75rem;padding:.7rem 1rem;font-size:.82rem">
                <strong>{{ $this->firma->tehlikeSinifiEtiketi() }}:</strong>
                ilk temel eğitim {{ config('isg.egitim.sureler.ilk.'.$sinif.'.saat', '—') }} ders saati,
                tekrar eğitimi {{ config('isg.egitim.sureler.tekrar.saat', '—') }} ders saati;
                temel İSG eğitimi {{ $yil ? $yil.' yılda bir' : '—' }} yenilenir.
                Son eğitim tarihi; eğitim kayıtları ve bu işyerinin yüz yüze katılım formlarından (TC, yoksa ad soyad eşleşmesiyle) bulunur.
                "Yaklaşan" sınırı {{ \App\Support\KullaniciAyarlari::esik('egitim') }} gün (Ayarlar'dan değiştirilebilir).
            </div>

            {{-- BUGÜN NE YAPMALIYIM --}}
            @php
                $isler = collect([
                    ['kayit_yok', $o['kayit_yok'], 'Temel eğitim kaydı yok', 'Bu çalışanlar için temel İSG eğitimi planlayın.', \App\Filament\Pages\EgitimKatilim::getUrl(['firma' => $firmaId]), 'rgb(220 38 38 / .07)', 'rgb(220 38 38 / .4)'],
                    ['dolmus', $o['dolmus'], 'Eğitim süresi dolmuş', 'Yenileme eğitimini öncelikle planlayın.', \App\Filament\Pages\EgitimKatilim::getUrl(['firma' => $firmaId]), 'rgb(234 88 12 / .07)', 'rgb(234 88 12 / .4)'],
                    ['isbasi_eksik', $o['isbasi_eksik'], 'İşbaşı eğitimi tutanağı yok', 'İşbaşı eğitim tutanağını hazırlayın.', \App\Filament\Pages\IsbasiEgitim::getUrl(['firma' => $firmaId]), 'rgb(245 158 11 / .08)', 'rgb(245 158 11 / .45)'],
                    ['yaklasan', $o['yaklasan'], 'Yenileme yaklaşıyor', 'Önümüzdeki dönem için eğitim tarihini planlayın.', \App\Filament\Pages\EgitimKatilim::getUrl(['firma' => $firmaId]), 'rgb(245 158 11 / .05)', 'rgb(245 158 11 / .35)'],
                ])->filter(fn ($i) => $i[1] > 0);
            @endphp
            <x-filament::section icon="heroicon-o-light-bulb" icon-color="warning">
                <x-slot name="heading">Bugün ne yapmalıyım?</x-slot>
                <x-slot name="description">Kırmızılar önce, sarılar sonra. Sistem hiçbir eğitimi sizin yerinize tamamlanmış saymaz.</x-slot>
                @if ($isler->isEmpty())
                    <p style="font-size:.85rem;color:#15803d">Öncelikli işlem yok — aktif çalışanların temel eğitimi geçerli.</p>
                @else
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.7rem">
                        @foreach ($isler as [$filtre, $sayi, $baslik, $aciklama, $url, $zemin, $cizgi])
                            <div style="background:{{ $zemin }};border:1px solid {{ $cizgi }};border-radius:.75rem;padding:.8rem 1rem">
                                <div style="font-size:1.4rem;font-weight:800">{{ $sayi }}</div>
                                <div style="font-weight:700">{{ $baslik }}</div>
                                <div style="font-size:.76rem;color:rgb(107 114 128);margin:.2rem 0 .5rem">{{ $aciklama }}</div>
                                <div style="display:flex;gap:.6rem;font-size:.78rem">
                                    <button type="button" wire:click="$set('durumFiltre', '{{ $filtre }}')" style="background:none;border:0;padding:0;color:rgb(124 58 237);cursor:pointer;text-decoration:underline">Kişileri göster</button>
                                    <a href="{{ $url }}" style="color:rgb(124 58 237)">→ Planla</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.6rem">
                @foreach ([['Aktif çalışan', $o['toplam'], 'inherit', null], ['Kayıt yok', $o['kayit_yok'], '#b91c1c', 'kayit_yok'], ['Süresi dolmuş', $o['dolmus'], '#c2410c', 'dolmus'], ['Yaklaşan', $o['yaklasan'], '#b45309', 'yaklasan'], ['Geçerli', $o['gecerli'], '#15803d', 'gecerli']] as [$ad, $sayi, $renk, $f])
                    <button type="button" wire:click="$set('durumFiltre', {{ $f ? "'".$f."'" : 'null' }})" style="text-align:left;border:1px solid {{ $durumFiltre === $f ? 'rgb(124 58 237)' : 'rgb(107 114 128 / .3)' }};border-radius:.75rem;padding:.6rem .9rem;background:transparent;cursor:pointer">
                        <div style="font-size:1.35rem;font-weight:700;color:{{ $sayi > 0 ? $renk : 'inherit' }}">{{ $sayi }}</div>
                        <div style="font-size:.74rem;color:rgb(107 114 128)">{{ $ad }}</div>
                    </button>
                @endforeach
            </div>

            <x-filament::section icon="heroicon-o-user-group" icon-color="gray">
                <x-slot name="heading">Kimin eğitimi dolmuş? ({{ $this->gosterilenSatirlar->count() }})</x-slot>
                <x-slot name="description">Uyumluluk: {{ $o['uyum'] !== null ? '%'.$o['uyum'] : '—' }} (geçerli + yaklaşan / aktif çalışan)</x-slot>
                @if ($this->gosterilenSatirlar->isNotEmpty())
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:760px">
                            <tr>@foreach (['Personel', 'Bölüm', 'Son eğitim', 'Yenileme', 'Durum', 'İşbaşı'] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                            @foreach ($this->gosterilenSatirlar as $s)
                                <tr>
                                    <td style="{{ $td }}"><strong>{{ $s['calisan']->ad_soyad }}</strong><div style="font-size:.72rem;color:rgb(107 114 128)">{{ $s['calisan']->gorev }}</div></td>
                                    <td style="{{ $td }}">{{ $s['calisan']->departman ?: '—' }}</td>
                                    <td style="{{ $td }};white-space:nowrap">{{ $s['son_egitim']?->format('d.m.Y') ?? '—' }}@if ($s['kaynak'])<div style="font-size:.7rem;color:rgb(107 114 128)">{{ $s['kaynak'] }}</div>@endif</td>
                                    <td style="{{ $td }};white-space:nowrap">{{ $s['yenileme']?->format('d.m.Y') ?? '—' }}@if ($s['kalan_gun'] !== null)<div style="font-size:.7rem;color:rgb(107 114 128)">{{ $s['kalan_gun'] < 0 ? abs($s['kalan_gun']).' gün geçti' : $s['kalan_gun'].' gün kaldı' }}</div>@endif</td>
                                    <td style="{{ $td }}"><span style="font-size:.72rem;font-weight:700;color:{{ $durumRenk[$s['durum']] }}">{{ \App\Filament\Pages\EgitimYenilemeTakibi::durumEtiketi($s['durum']) }}</span></td>
                                    <td style="{{ $td }}">{!! $s['isbasi'] ? '<span style="color:#15803d">Var</span>' : '<span style="color:#b45309">Eksik</span>' !!}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @else
                    <p style="font-size:.83rem;color:rgb(107 114 128)">{{ $this->satirlar->isEmpty() ? 'Bu işyerinde aktif çalışan yok.' : 'Filtreye uyan çalışan yok.' }}</p>
                @endif
            </x-filament::section>
        @endif
    @else
        <x-filament::section icon="heroicon-o-academic-cap" icon-color="primary">
            <x-slot name="heading">Yüz yüze eğitim kayıtları ({{ $this->oturumlar->count() }})</x-slot>
            <x-slot name="description">Eğitim Katılım formlarıyla kaydedilen oturumlar. Belgeler (katılım formu, sertifika) işyerinin Eğitim Katılım sayfasındaki geçmiş listesinden alınır.</x-slot>
            @if ($this->oturumlar->isNotEmpty())
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:760px">
                        <tr>@foreach (['Eğitim', 'Firma', 'Tarih', 'Tehlike', 'Gün', 'Katılımcı', 'Sonuç', ''] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                        @foreach ($this->oturumlar as $k)
                            <tr>
                                <td style="{{ $td }}">{{ $k->basliklarEtiketi() }}<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $k->belge_no }}</div></td>
                                <td style="{{ $td }}">{{ $k->firma?->unvan }}</td>
                                <td style="{{ $td }};white-space:nowrap">{{ \App\Support\EgitimTakibi::katilimTarihi($k)?->format('d.m.Y') ?? '—' }}</td>
                                <td style="{{ $td }}">{{ $k->firma?->tehlike_sinifi ? $k->firma->tehlikeSinifiEtiketi() : '—' }}</td>
                                <td style="{{ $td }}">{{ $k->sure_gun }}</td>
                                <td style="{{ $td }}">{{ $k->katilimciSayisi() }}</td>
                                <td style="{{ $td }};white-space:nowrap">
                                    @if ($k->katilimciSayisi() === 0)
                                        —
                                    @elseif ($k->sonuclandiMi())
                                        <span style="color:rgb(22 163 74);font-weight:600">Sonuçlandı</span> <span style="color:rgb(107 114 128)">({{ count($k->belgeAlacakKatilimcilar()) }}/{{ $k->katilimciSayisi() }} başarılı)</span>
                                    @else
                                        <span style="color:rgb(217 119 6);font-weight:600">Sonuç bekliyor</span>
                                    @endif
                                </td>
                                <td style="{{ $td }};text-align:right;white-space:nowrap">@if ($k->katilimciSayisi() > 0)<button type="button" wire:click="mountAction('sonucGir', { id: {{ $k->id }} })" style="color:rgb(22 163 74);font-size:.78rem;margin-right:.6rem">Katılım / Sonuç</button>@endif<a href="{{ \App\Filament\Pages\EgitimKatilim::getUrl(['firma' => $k->firma_id]) }}" style="color:rgb(124 58 237);font-size:.78rem">Belgeler →</a></td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @else
                <p style="font-size:.83rem;color:rgb(107 114 128)">Kayıtlı yüz yüze eğitim yok.</p>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
