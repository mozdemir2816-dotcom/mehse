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
        @switch ($sekme)
            @case('egitim')
                Firmanın yıllık eğitim planı. Ay hücrelerine tıklayarak durumu Boş → Planlandı → Tamamlandı
                arasında değiştirin; o ay planlanan eğitimler Ziyaret Programı'ndaki yapılacaklar listesine gelir.
                @break
            @case('degerlendirme')
                İSG yıllık değerlendirme raporu — 36 çalışma satırı sistemdeki kayıtlardan otomatik dolar; eksikleri tamamlayıp çıktı alın.
                @break
            @default
                Her ayın P (Planlandı) ve G (Gerçekleşti) hücresine tıklayarak işaretleyin; o ay P olan
                maddeler Ziyaret Programı'nda o ayın ziyaret yapılacaklar listesine otomatik gelir.
        @endswitch
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
        {{-- Diğer yıllık plan sayfaları (aynı firma + yıl ile) --}}
        <div style="display:flex;gap:.4rem;margin-bottom:1rem;flex-wrap:wrap;font-size:.78rem;color:rgb(107 114 128);align-items:center">
            Aynı firmanın diğer planları:
            @foreach ([
                'calisma' => [\App\Filament\Pages\YillikPlan\YillikCalismaPlani::class, 'Yıllık Çalışma Planı'],
                'egitim' => [\App\Filament\Pages\YillikPlan\YillikEgitimPlani::class, 'Yıllık Eğitim Planı'],
                'degerlendirme' => [\App\Filament\Pages\YillikPlan\YillikDegerlendirmeRaporu::class, 'Yıllık Değerlendirme Raporu'],
            ] as $anahtar => [$sinif, $etiket])
                @continue($anahtar === $sekme)
                <x-filament::link :href="$sinif::getUrl(['firma' => $firmaId, 'yil' => $yil])" size="sm" icon="heroicon-o-arrow-top-right-on-square">
                    {{ $etiket }}
                </x-filament::link>
            @endforeach
        </div>

        @if ($sekme === 'calisma')
            @php
                $cizgi = 'border:1px solid rgb(107 114 128 / .25)';
                $th = $cizgi.';padding:.3rem .4rem;font-size:.66rem;font-weight:700;text-align:center;background:rgb(107 114 128 / .08)';
                $td = $cizgi.';padding:.3rem .4rem;vertical-align:top';
                $pRenk = '#b45309';
                $gRenk = '#15803d';
            @endphp
            <x-filament::section icon="heroicon-o-table-cells" icon-color="primary">
                <x-slot name="heading">
                    İş Sağlığı ve Güvenliği Yıllık Çalışma Planı – {{ $yil }}
                    <span style="font-weight:400;font-size:.78rem;color:rgb(107 114 128)">
                        (<strong style="color:{{ $pRenk }}">P</strong> Planlandı ·
                        <strong style="color:{{ $gRenk }}">G</strong> Gerçekleşti — hücreye tıklayarak işaretleyin)
                    </span>
                </x-slot>

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.72rem;min-width:1500px">
                        <tr>
                            <th rowspan="2" style="{{ $th }};width:2rem">No</th>
                            <th rowspan="2" style="{{ $th }};width:8rem">Ana Konu</th>
                            <th rowspan="2" style="{{ $th }};min-width:14rem">Planlanan Faaliyet</th>
                            <th rowspan="2" style="{{ $th }};width:7rem">Periyot</th>
                            <th rowspan="2" style="{{ $th }};width:8rem">Sorumlu</th>
                            @foreach (['OCAK', 'ŞUBAT', 'MART', 'NİSAN', 'MAYIS', 'HAZİRAN', 'TEMMUZ', 'AĞUSTOS', 'EYLÜL', 'EKİM', 'KASIM', 'ARALIK'] as $ay)
                                <th colspan="2" style="{{ $th }}">{{ $ay }}</th>
                            @endforeach
                            <th rowspan="2" style="{{ $th }};min-width:14rem">Mevzuat Dayanağı / Kayıt-Kanıt / Açıklama</th>
                            <th rowspan="2" style="{{ $th }};width:5.5rem">Durum</th>
                            <th rowspan="2" style="{{ $th }};width:1.5rem"></th>
                        </tr>
                        <tr>
                            @foreach (range(0, 11) as $_)
                                <th style="{{ $th }};width:1.4rem;color:{{ $pRenk }}">P</th>
                                <th style="{{ $th }};width:1.4rem;color:{{ $gRenk }}">G</th>
                            @endforeach
                        </tr>
                        @foreach (($p->faaliyetler ?? []) as $fi => $f)
                            <tr>
                                <td style="{{ $td }};text-align:center">{{ $fi + 1 }}</td>
                                <td style="{{ $td }};font-size:.64rem;font-weight:700;letter-spacing:.03em">{{ $f['ana_konu'] ?? '' }}</td>
                                <td style="{{ $td }};font-weight:600">{{ $f['faaliyet'] }}</td>
                                <td style="{{ $td }};font-size:.68rem">{{ $f['frekans'] ?? '' }}</td>
                                <td style="{{ $td }};font-size:.68rem">{{ $f['sorumlu'] ?? '' }}</td>
                                @foreach (($f['aylar'] ?? array_fill(0, 12, 'bos')) as $ai => $durum)
                                    @php
                                        $kilitli = $ai < $kilit;
                                        $hucreler = [
                                            'P' => [$durum !== 'bos', $pRenk, 'Planlandı'],
                                            'G' => [$durum === 'tamamlandi', $gRenk, 'Gerçekleşti'],
                                        ];
                                    @endphp
                                    @foreach ($hucreler as $hucre => [$dolu, $renk, $ad])
                                        <td style="{{ $cizgi }};padding:0;text-align:center;{{ $kilitli ? 'background:rgb(107 114 128 / .15)' : '' }}">
                                            <button type="button" @disabled($kilitli)
                                                @unless ($kilitli) wire:click="ayHucresiDegistir('faaliyetler', {{ $fi }}, {{ $ai }}, '{{ $hucre }}')" @endunless
                                                title="{{ $kilitli ? 'Uzman atanmadan önce — seçilemez' : \App\Models\ZiyaretProgrami::AYLAR[$ai].' — '.$ad.($dolu ? ' (kaldırmak için tıklayın)' : '') }}"
                                                style="width:100%;min-height:1.6rem;border:none;background:{{ $dolu ? $renk.'22' : 'transparent' }};color:{{ $renk }};font-weight:800;font-size:.72rem;{{ $kilitli ? 'cursor:not-allowed' : 'cursor:pointer' }}">{{ $dolu ? $hucre : '' }}</button>
                                        </td>
                                    @endforeach
                                @endforeach
                                <td style="{{ $td }};font-size:.66rem;color:rgb(107 114 128)">
                                    {{ $f['yasal_gereklilik'] ?? '' }}
                                    @if (filled($f['kayit_notu'] ?? null))
                                        <div style="margin-top:.2rem"><strong>Kayıt/Not:</strong> {{ $f['kayit_notu'] }}</div>
                                    @endif
                                </td>
                                @php
                                    $genelDurum = \App\Models\YillikPlan::faaliyetDurumu($f['aylar'] ?? []);
                                    $durumRenk = match ($genelDurum) {
                                        'Gerçekleşti' => $gRenk,
                                        'Devam Ediyor' => '#2563eb',
                                        'Planlandı' => $pRenk,
                                        default => 'rgb(107 114 128)',
                                    };
                                @endphp
                                <td style="{{ $td }};text-align:center;font-size:.66rem;font-weight:700;color:{{ $durumRenk }}">{{ $genelDurum }}</td>
                                <td style="{{ $td }};text-align:center">
                                    <button type="button" wire:click="faaliyetSil({{ $fi }})" wire:confirm="Bu faaliyet plandan silinsin mi?" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div style="margin-top:1rem;display:grid;grid-template-columns:1fr 2fr 1fr 1fr 1.5fr auto;gap:.5rem">
                    <input type="text" wire:model="yeniAnaKonu" placeholder="Ana konu" list="ana-konu-listesi"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <datalist id="ana-konu-listesi">
                        @foreach (collect($p->faaliyetler ?? [])->pluck('ana_konu')->filter()->unique() as $konu)
                            <option value="{{ $konu }}"></option>
                        @endforeach
                    </datalist>
                    <input type="text" wire:model="yeniFaaliyet" placeholder="Planlanan faaliyet"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <input type="text" wire:model="yeniPeriyot" placeholder="Periyot"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <input type="text" wire:model="yeniSorumlu" placeholder="Sorumlu"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <input type="text" wire:model="yeniAciklama" placeholder="Mevzuat dayanağı / açıklama"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <x-filament::button size="sm" wire:click="faaliyetEkle">+ Faaliyet Ekle</x-filament::button>
                </div>

                <div style="margin-top:.75rem;display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap">
                    <x-filament::button size="xs" color="gray" wire:click="varsayilanaSifirla" wire:confirm="Çalışma planı standart şablona sıfırlansın mı? İşaretlenen P/G'ler silinir.">Varsayılana Sıfırla</x-filament::button>
                    <x-filament::button color="success" icon="heroicon-o-arrow-down-tray" wire:click="mountAction('calismaExcel')">
                        Çıktı Al (Excel — A4 yatay)
                    </x-filament::button>
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
                                <td style="padding:.3rem .3rem;white-space:nowrap">
                                    @if (($e['kategori'] ?? null) === 'ise_ozgu' && blank($e['kutuphane_anahtari'] ?? null))<button type="button" wire:click="mountAction('kutuphaneyeKaydet', { index: {{ $ei }} })" title="Kütüphaneye kaydet" style="color:#f59e0b;cursor:pointer;background:none;border:none">★</button>@endif
                                    <button type="button" wire:click="egitimSil({{ $ei }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div style="margin-top:1rem;display:grid;grid-template-columns:1.2fr 2fr 1fr 1fr 1fr auto;gap:.5rem">
                    <select wire:model="yeniEgitimKategori" title="Eğitim planındaki bölüm"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                        @foreach ($egitimKategorileri as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
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

                <div style="margin-top:.75rem;display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
                    <x-filament::button size="xs" color="primary" icon="heroicon-o-queue-list" wire:click="mountAction('iseOzguSec')">İşe Özgü Konuları Seç</x-filament::button>
                    <x-filament::button size="xs" color="gray" tag="a" :href="\App\Filament\Pages\IseOzguEgitimKonulari::getUrl(['firmaId' => $this->firmaId])">Konu kütüphanesi</x-filament::button>
                    <x-filament::button size="xs" color="gray" wire:click="varsayilanaSifirla">Varsayılana Sıfırla</x-filament::button>
                    <span style="font-size:.72rem;color:rgb(107 114 128)">4. bölüm firmanın iş kalemine / NACE koduna göre otomatik gelir; ★ ile plandaki konuyu kütüphaneye kaydedebilirsiniz.</span>
                </div>
            </x-filament::section>
        @endif

        @if ($sekme === 'degerlendirme')
            @php
                $ydKunye = \App\Support\YillikDegerlendirmeVerisi::kunye($this->firma, $yil);
                $ydSatirlar = collect($p->degerlendirmeler ?? []);
                $ydGirdi = 'width:100%;padding:.3rem .4rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.74rem;font-family:inherit';
                $ydTh = 'text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3);font-size:.7rem';
            @endphp
            <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
                <x-slot name="heading">İSG Yıllık Değerlendirme Raporu — {{ $yil }}</x-slot>
                <x-slot name="description">Dönem: {{ $ydKunye['donem'] }} (atandığınız tarih → yıl sonu). Satırlar sayfa açılınca sistem kayıtlarından yenilenir; elle düzelttiğiniz satır korunur. Çıktıda görevli adı yazılmaz, imza alanına kaşe basılır.</x-slot>

                <div style="font-size:.75rem;color:rgb(107 114 128);margin-bottom:.6rem">
                    NACE / Faaliyet: {{ $ydKunye['nace'] ?: '—' }} · Çalışan: Erkek {{ $ydKunye['erkek'] }} / Kadın {{ $ydKunye['kadin'] }} / Toplam {{ $ydKunye['toplam'] }} | Genç {{ $ydKunye['genc'] }} / Çocuk {{ $ydKunye['cocuk'] }}
                    · Sistemden dolan: <strong>{{ $ydSatirlar->where('otomatik', true)->count() }}</strong> / {{ $ydSatirlar->count() }} satır
                </div>

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.76rem;min-width:1000px">
                        <tr>
                            <th style="{{ $ydTh }};width:2.5rem">No</th>
                            <th style="{{ $ydTh }};width:9rem">Tarih / Dönem</th>
                            <th style="{{ $ydTh }};width:12rem">Yapılan çalışma</th>
                            <th style="{{ $ydTh }};width:10rem">Yapan / Ünvan</th>
                            <th style="{{ $ydTh }};width:11rem">Yöntem / Kanıt</th>
                            <th style="{{ $ydTh }}">Sonuç ve yorum</th>
                            <th style="{{ $ydTh }};width:3.5rem"></th>
                        </tr>
                        @php $no = 0; @endphp
                        @foreach (($p->degerlendirmeler ?? []) as $di => $d)
                            @continue(($d['tur'] ?? 'calisma') === 'genel')
                            @php $no++; @endphp
                            <tr wire:key="yd-{{ $di }}-{{ md5(json_encode($d)) }}" style="border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top">
                                <td style="padding:.3rem .5rem">{{ $no }}</td>
                                <td style="padding:.15rem"><input type="text" value="{{ $d['tarih'] ?? '' }}" placeholder="… / … / …" x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'tarih', $event.target.value)" style="{{ $ydGirdi }}"></td>
                                <td style="padding:.3rem .5rem;font-weight:600">{{ $d['calisma'] }}
                                    @if ($d['otomatik'] ?? false)<div style="font-size:.66rem;color:rgb(21 128 61);font-weight:500">● sistemden</div>@elseif ($d['elle'] ?? false)<div style="font-size:.66rem;color:rgb(217 119 6);font-weight:500">✎ elle</div>@else<div style="font-size:.66rem;color:rgb(107 114 128);font-weight:500">kayıt yok — şablon</div>@endif
                                </td>
                                <td style="padding:.15rem"><textarea rows="2" x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'yapan_kisi', $event.target.value)" style="{{ $ydGirdi }}">{{ $d['yapan_kisi'] ?? '' }}</textarea></td>
                                <td style="padding:.15rem"><textarea rows="2" x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'yontem', $event.target.value)" style="{{ $ydGirdi }}">{{ $d['yontem'] ?? '' }}</textarea></td>
                                <td style="padding:.15rem"><textarea rows="3" x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'sonuc', $event.target.value)" style="{{ $ydGirdi }}">{{ $d['sonuc'] ?? '' }}</textarea></td>
                                <td style="padding:.3rem .2rem;white-space:nowrap">
                                    @if (($d['elle'] ?? false) && ! str_starts_with((string) ($d['anahtar'] ?? ''), 'ek_'))
                                        <button type="button" wire:click="degerlendirmeOtomatigeDondur({{ $di }})" title="Sistem kaydına dön" style="color:rgb(13 148 136);cursor:pointer;background:none;border:none">↺</button>
                                    @endif
                                    <button type="button" wire:click="degerlendirmeSil({{ $di }})" wire:confirm="Satır silinsin mi?" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div style="margin-top:1rem;display:grid;grid-template-columns:1fr auto;gap:.5rem">
                    <input type="text" wire:model="yeniDegerlendirmeCalisma" placeholder="Ek çalışma (şablon dışı)"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <x-filament::button size="sm" wire:click="degerlendirmeEkle">+ Çalışma Ekle</x-filament::button>
                </div>

                @if ($ydSatirlar->where('tur', 'genel')->isNotEmpty())
                    <div style="margin-top:1.2rem;font-weight:700;font-size:.85rem">Genel sonuç, iyileştirme kararları ve gelecek yıl önerileri</div>
                    <table style="width:100%;border-collapse:collapse;font-size:.76rem;margin-top:.4rem">
                        @foreach (($p->degerlendirmeler ?? []) as $di => $d)
                            @continue(($d['tur'] ?? 'calisma') !== 'genel')
                            <tr wire:key="ydg-{{ $di }}-{{ md5(json_encode($d)) }}" style="border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top">
                                <td style="padding:.35rem .5rem;font-weight:600;width:14rem">{{ $d['calisma'] }}@if ($d['otomatik'] ?? false)<div style="font-size:.66rem;color:rgb(21 128 61);font-weight:500">● sistemden</div>@endif</td>
                                <td style="padding:.15rem"><textarea rows="2" x-on:change="$wire.degerlendirmeGuncelle({{ $di }}, 'sonuc', $event.target.value)" style="{{ $ydGirdi }}">{{ $d['sonuc'] ?? '' }}</textarea></td>
                            </tr>
                        @endforeach
                    </table>
                @endif

                <div style="margin-top:.75rem">
                    <x-filament::button size="xs" color="gray" wire:click="varsayilanaSifirla" wire:confirm="Değerlendirme şablona sıfırlanıp sistem kayıtlarından yeniden doldurulsun mu? Elle yazdıklarınız silinir.">Şablona Sıfırla</x-filament::button>
                </div>
            </x-filament::section>
        @endif
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
