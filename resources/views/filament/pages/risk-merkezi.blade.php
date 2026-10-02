@php
    use App\Support\RiskMerkeziVerisi as RMV;
    $firma = $this->firma;
    $rd = $this->rd;
    $o = $this->ozet;
    $girdi = 'padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem';
    $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.75rem 1rem';
    $th = 'text-align:left;padding:.5rem .6rem;font-size:.7rem;letter-spacing:.05em;text-transform:uppercase;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .2)';
    $td = 'padding:.5rem .6rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $grupRenk = ['cok_yuksek' => '#7f1d1d', 'yuksek' => '#dc2626', 'orta' => '#f59e0b', 'dusuk' => '#16a34a'];
    $yontemAd = $rd ? config('isg.risk_yontemleri.'.$rd->yontem) : null;
@endphp

<x-filament-panels::page>
    <div style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:flex-end;justify-content:space-between">
        <div style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:flex-end">
            <div>
                <label style="display:block;font-size:.75rem;font-weight:600">İşyeri</label>
                <select wire:model.live="firmaId" style="{{ $girdi }};min-width:280px">
                    @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            @if ($this->degerlendirmeler->count() > 1)
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:600">Değerlendirme</label>
                    <select wire:model.live="rdId" style="{{ $girdi }}">
                        @foreach ($this->degerlendirmeler as $d)<option value="{{ $d->id }}">{{ $d->belge_no }} · {{ config('isg.risk_yontemleri.'.$d->yontem) }} · {{ $d->rapor_tarihi?->format('d.m.Y') ?? 'tarihsiz' }}</option>@endforeach
                    </select>
                </div>
            @endif
        </div>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
            <x-filament::button size="sm" color="gray" icon="heroicon-o-sparkles" tag="a" :href="\App\Filament\Pages\RiskSihirbazi::getUrl()">Risk analizi başlat</x-filament::button>
            @if ($rd)<x-filament::button size="sm" icon="heroicon-o-pencil-square" tag="a" :href="\App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource::getUrl('edit', ['record' => $rd])">Kayıtları aç</x-filament::button>@endif
        </div>
    </div>

    @if ($firma)
        <div style="font-size:.75rem;color:rgb(107 114 128)">
            {{ $firma->unvan }} · SGK Sicil {{ $firma->sgk_sicil_no ?: '—' }} · NACE {{ $firma->nace_kodu ?: '—' }} · {{ $firma->tehlikeSinifiEtiketi() }}@if ($yontemAd) · {{ $yontemAd }}@endif
        </div>

        <div style="display:flex;gap:.25rem;border-bottom:1px solid rgb(107 114 128 / .2)">
            @foreach (['merkez' => 'Merkez', 'aksiyon' => 'Aksiyon', 'nace' => 'NACE Yol Haritası', 'raporlar' => 'Raporlar'] as $k => $ad)
                <button type="button" wire:click="$set('sekme', '{{ $k }}')" style="padding:.5rem .9rem;font-size:.85rem;font-weight:600;border-bottom:2px solid {{ $sekme === $k ? 'rgb(13 148 136)' : 'transparent' }};color:{{ $sekme === $k ? 'rgb(13 148 136)' : 'inherit' }}">{{ $ad }}</button>
            @endforeach
        </div>

        @php $yen = RMV::yenileme($firma, $rd); $yRenk = ['yok' => 'rgb(220 38 38)', 'eksik' => 'rgb(37 99 235)', 'gecti' => 'rgb(220 38 38)', 'yakin' => 'rgb(217 119 6)', 'gecerli' => 'rgb(21 128 61)'][$yen['durum']]; @endphp
        <div style="border:1px solid {{ $yRenk }};background:color-mix(in srgb, {{ $yRenk }} 7%, transparent);border-radius:.6rem;padding:.55rem .8rem;font-size:.8rem">
            <strong style="color:{{ $yRenk }}">Risk değerlendirmesi yenileme takibi</strong> — {{ $yen['metin'] }}
        </div>

        {{-- ================= MERKEZ ================= --}}
        @if ($sekme === 'merkez')
            @if (! $o)
                <x-filament::section>
                    <div style="text-align:center;padding:1rem">
                        <div style="font-weight:700">Henüz risk değerlendirmesi yok</div>
                        <div style="font-size:.8rem;color:rgb(107 114 128);margin:.3rem 0 .8rem">Risk Değerlendirme sihirbazıyla ilk değerlendirmeyi başlatın.</div>
                        <x-filament::button tag="a" :href="\App\Filament\Pages\RiskSihirbazi::getUrl()">Risk analizi başlat</x-filament::button>
                    </div>
                </x-filament::section>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.6rem">
                    @foreach ([['Toplam risk', $o['toplam'], $o['acik'].' açık', 'inherit'], ['Çok yüksek', $o['cok_yuksek_acik'], 'Açık, acil öncelik', '#7f1d1d'], ['Yüksek', $o['yuksek_acik'], 'Açık', '#dc2626'], ['Açık önlem', $o['aksiyon_acik'], 'Önerisi olan açık madde', '#d97706'], ['Geciken önlem', $o['geciken'], 'Termini geçmiş', '#dc2626']] as [$ad, $sayi, $alt, $renk])
                        <div style="{{ $kart }};border-left:4px solid {{ $renk === 'inherit' ? 'rgb(107 114 128)' : $renk }}">
                            <div style="font-size:1.4rem;font-weight:800;color:{{ $renk }}">{{ $sayi }}</div>
                            <div style="font-size:.78rem;font-weight:600">{{ $ad }}</div>
                            <div style="font-size:.7rem;color:rgb(107 114 128)">{{ $alt }}</div>
                        </div>
                    @endforeach
                </div>

                <div style="display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:1rem;align-items:start">
                    <x-filament::section>
                        <x-slot name="heading">Öncelikli riskler</x-slot>
                        <x-slot name="description">Yüksek ve çok yüksek açık kayıtlar</x-slot>
                        @forelse ($o['oncelikli'] as $m)
                            @php $g = RMV::seviyeGrubu($rd->yontem, $m->puan); @endphp
                            <div style="display:flex;gap:.6rem;align-items:flex-start;padding:.45rem 0;border-bottom:1px solid rgb(107 114 128 / .12)">
                                <span style="min-width:2.4rem;text-align:center;font-weight:800;color:#fff;background:{{ $grupRenk[$g] ?? 'gray' }};border-radius:.4rem;padding:.15rem .3rem;font-size:.8rem">{{ rtrim(rtrim(number_format((float) $m->puan, 1, ',', ''), '0'), ',') }}</span>
                                <div style="font-size:.8rem;flex:1">
                                    <strong>{{ \Illuminate\Support\Str::limit($m->tehlike, 90) }}</strong>
                                    <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $m->bolum ?: 'Bölüm yok' }} · {{ $m->duzey }} · Termin {{ RMV::terminTarihi($m->termin)?->format('d.m.Y') ?? ($m->termin ?: '—') }}</div>
                                </div>
                            </div>
                        @empty
                            <div style="text-align:center;font-size:.82rem;color:rgb(107 114 128);padding:1rem">Öncelikli açık risk yok.</div>
                        @endforelse
                    </x-filament::section>

                    <x-filament::section>
                        <x-slot name="heading">{{ $yontemAd }}</x-slot>
                        <x-slot name="description">{{ $rd->yontem === 'matris_5x5' ? 'Olasılık × şiddet — hücredeki sayı kayıt adedidir' : 'Düzey dağılımı' }}</x-slot>
                        @if ($rd->yontem === 'matris_5x5')
                            <table style="width:100%;border-collapse:separate;border-spacing:3px;font-size:.72rem;text-align:center">
                                <tr><td style="color:rgb(107 114 128)" title="Satır: olasılık · Sütun: şiddet">O\Ş</td>@foreach (range(1, 5) as $s)<td style="color:rgb(107 114 128)">{{ $s }}</td>@endforeach</tr>
                                @foreach (range(1, 5) as $ol)
                                    <tr>
                                        <td style="color:rgb(107 114 128)">{{ $ol }}</td>
                                        @foreach (range(1, 5) as $s)
                                            @php $p = $ol * $s; $adet = $o['matris'][$ol][$s] ?? 0; $bg = $p >= 15 ? 'rgb(254 202 202)' : ($p >= 8 ? 'rgb(254 243 199)' : 'rgb(220 252 231)'); @endphp
                                            <td style="background:{{ $bg }};border-radius:.3rem;padding:.4rem 0;{{ $adet ? 'outline:2px solid #0f766e;font-weight:800' : 'color:rgb(107 114 128)' }}">{{ $adet ?: $p }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                        @php $max = max(1, max($o['dagilim'])); @endphp
                        <div style="margin-top:.6rem;display:flex;flex-direction:column;gap:.3rem">
                            @foreach ($o['dagilim'] as $g => $n)
                                <div style="display:grid;grid-template-columns:90px 1fr 30px;gap:.4rem;align-items:center;font-size:.75rem">
                                    <span>{{ RMV::GRUPLAR[$g] }}</span>
                                    <span style="height:7px;border-radius:9px;background:rgb(107 114 128 / .15)"><span style="display:block;height:7px;border-radius:9px;width:{{ round($n * 100 / $max) }}%;background:{{ $grupRenk[$g] }}"></span></span>
                                    <span style="text-align:right">{{ $n }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if ($o['puansiz'])<div style="font-size:.72rem;color:rgb(217 119 6);margin-top:.4rem">{{ $o['puansiz'] }} madde henüz puanlanmadı.</div>@endif
                    </x-filament::section>
                </div>

                <div style="display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:1rem;align-items:start">
                    <x-filament::section>
                        <x-slot name="heading">Son kayıtlar</x-slot>
                        <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                            @foreach ($o['son'] as $m)
                                <tr><td style="{{ $td }}">{{ \Illuminate\Support\Str::limit($m->tehlike, 70) }}<div style="font-size:.7rem;color:rgb(107 114 128)">{{ $m->bolum ?: '—' }}</div></td>
                                    <td style="{{ $td }};white-space:nowrap">{{ $m->duzey ?: 'Puansız' }}</td>
                                    <td style="{{ $td }};white-space:nowrap;color:rgb(107 114 128)">{{ $m->updated_at?->format('d.m.Y') }}</td></tr>
                            @endforeach
                        </table>
                    </x-filament::section>
                    <x-filament::section>
                        <x-slot name="heading">Bölüm yoğunluğu</x-slot>
                        @php $bmax = max(1, max($o['bolumler'] ?: [0])); @endphp
                        @forelse (array_slice($o['bolumler'], 0, 12, true) as $b => $n)
                            <div style="display:grid;grid-template-columns:1fr 2fr 30px;gap:.4rem;align-items:center;font-size:.75rem;margin-bottom:.3rem">
                                <span>{{ \Illuminate\Support\Str::limit($b, 28) }}</span>
                                <span style="height:7px;border-radius:9px;background:rgb(107 114 128 / .15)"><span style="display:block;height:7px;border-radius:9px;width:{{ round($n * 100 / $bmax) }}%;background:#0f766e"></span></span>
                                <span style="text-align:right">{{ $n }}</span>
                            </div>
                        @empty
                            <div style="font-size:.8rem;color:rgb(107 114 128)">Bölüm yok.</div>
                        @endforelse
                    </x-filament::section>
                </div>
            @endif

        {{-- ================= AKSİYON ================= --}}
        @elseif ($sekme === 'aksiyon')
            <x-filament::section>
                <x-slot name="heading">Önlem (aksiyon) takibi — {{ $this->aksiyonlar->count() }} kayıt</x-slot>
                <x-slot name="description">Risk maddelerindeki önerilen önlemler; sorumlu ve terminle. Önlem tamamlanınca durumu "Kapalı" yapıp rezidüel riski kayıtta güncelleyin.</x-slot>
                <x-slot name="afterHeader">
                    <select wire:model.live="aksiyonFiltre" style="{{ $girdi }}">
                        <option value="acik">Açık / devam eden</option><option value="geciken">Termini geçen</option><option value="kapali">Kapalı</option><option value="tumu">Tümü</option>
                    </select>
                </x-slot>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:860px">
                        <tr>@foreach (['Tehlike / bölüm', 'Puan', 'Önlem', 'Sorumlu', 'Termin', 'Durum'] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                        @forelse ($this->aksiyonlar->take(150) as $a)
                            @php $m = $a['madde']; @endphp
                            <tr wire:key="aks-{{ $m->id }}">
                                <td style="{{ $td }};max-width:280px"><strong>{{ \Illuminate\Support\Str::limit($m->tehlike, 70) }}</strong><div style="font-size:.7rem;color:rgb(107 114 128)">{{ $m->bolum ?: '—' }} · {{ $a['rd']->belge_no }}</div></td>
                                <td style="{{ $td }};white-space:nowrap;color:{{ $grupRenk[$a['grup']] ?? 'inherit' }};font-weight:700">{{ $m->puan ?: '—' }}</td>
                                <td style="{{ $td }};max-width:300px">{{ \Illuminate\Support\Str::limit($m->oneri, 120) }}</td>
                                <td style="{{ $td }}">{{ $m->sorumlu ?: '—' }}</td>
                                <td style="{{ $td }};white-space:nowrap;{{ $a['gecikti'] ? 'color:#dc2626;font-weight:700' : '' }}">{{ $a['termin']?->format('d.m.Y') ?? ($m->termin ?: '—') }}@if ($a['gecikti'])<div style="font-size:.7rem">gecikti</div>@endif</td>
                                <td style="{{ $td }}">
                                    <select wire:change="maddeDurumu({{ $m->id }}, $event.target.value)" style="{{ $girdi }};padding:.25rem .4rem;font-size:.75rem">
                                        @foreach (config('isg.risk_madde_durumlari') as $k => $v)<option value="{{ $k }}" @selected($m->durum === $k)>{{ $v }}</option>@endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="{{ $td }};text-align:center;color:rgb(107 114 128)">Kayıt yok.</td></tr>
                        @endforelse
                    </table>
                </div>
                @if ($this->aksiyonlar->count() > 150)<div style="font-size:.75rem;color:rgb(107 114 128);margin-top:.4rem">İlk 150 kayıt gösteriliyor — tamamı için Raporlar → Önlem Excel.</div>@endif
            </x-filament::section>

        {{-- ================= NACE YOL HARİTASI ================= --}}
        @elseif ($sekme === 'nace')
            @php $ng = RMV::naceGrubu($firma); $kontroller = $this->kontroller; @endphp
            <x-filament::section>
                <x-slot name="heading">NACE kimliği ve güvenli kullanım sınırı</x-slot>
                <x-slot name="description">Firma kartındaki NACE ve SGK bilgisi otomatik alınır; NACE yalnız başlangıç kapsamıdır, saha doğrulaması yapılmadan otomatik risk üretmez.</x-slot>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.5rem;font-size:.8rem">
                    @foreach (['Firma' => $firma->unvan, 'SGK sicil' => $firma->sgk_sicil_no, 'NACE kodu' => $firma->nace_kodu, 'Faaliyet' => $firma->nace_aciklama, 'Sektör grubu' => $ng ? $ng['ad'].' (bölüm '.$ng['bolum'].')' : 'Eşleşme yok', 'Tehlike sınıfı' => $firma->tehlikeSinifiEtiketi()] as $e => $v)
                        <div style="{{ $kart }};padding:.5rem .7rem"><div style="font-size:.68rem;color:rgb(107 114 128);text-transform:uppercase">{{ $e }}</div><div style="font-weight:600">{{ $v ?: '—' }}</div></div>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">NACE'ye göre kontrol edilecek risk alanları</x-slot>
                <x-slot name="description">Başlıklar öneri / kapsam listesidir; her biri bölüm ve gerçek faaliyet üzerinden uzman tarafından doğrulanır.</x-slot>
                @if ($ng)
                    <div style="font-size:.8rem;font-weight:700;margin-bottom:.3rem">Teknik risk başlıkları</div>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.7rem">@foreach ($ng['basliklar'] as $b)<span style="font-size:.75rem;padding:.25rem .55rem;border-radius:9999px;border:1px solid rgb(13 148 136 / .4);background:rgb(13 148 136 / .06)">{{ $b }}</span>@endforeach</div>
                    <div style="font-size:.8rem;font-weight:700;margin-bottom:.3rem">Özel risk senaryoları</div>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem">@foreach ($ng['senaryolar'] as $b)<span style="font-size:.75rem;padding:.25rem .55rem;border-radius:9999px;border:1px solid rgb(217 119 6 / .45);background:rgb(217 119 6 / .07)">{{ $b }}</span>@endforeach</div>
                @else
                    <div style="font-size:.82rem;color:rgb(217 119 6)">Firma kartında NACE kodu yok ya da tanımlı bir sektör grubuyla eşleşmedi. Firma kaydına NACE kodunu girin.</div>
                @endif
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Risk analizi raporunda bulunması gerekenler</x-slot>
                <x-slot name="description">Mevzuat başlıkları rapor kapsamını kontrol etmek içindir; resmî değerlendirme ekip ve işveren onayı ile tamamlanır.</x-slot>
                @foreach ($kontroller as $i => $k)
                    <div style="display:flex;gap:.6rem;align-items:flex-start;padding:.5rem .6rem;border:1px solid rgb(107 114 128 / .18);border-radius:.55rem;margin-bottom:.35rem">
                        <span style="font-weight:800;min-width:1.4rem;color:{{ $k['tamam'] === true ? 'rgb(21 128 61)' : ($k['tamam'] === null ? 'rgb(107 114 128)' : 'rgb(217 119 6)') }}">{{ $k['tamam'] === true ? '✓' : ($k['tamam'] === null ? '–' : '!') }}</span>
                        <div style="flex:1;font-size:.8rem">
                            <strong>{{ $i + 1 }}. {{ $k['baslik'] }}</strong>
                            <div style="font-size:.74rem;color:rgb(107 114 128)">{{ $k['aciklama'] }} <em>({{ $k['dayanak'] }})</em></div>
                            <div style="font-size:.74rem;margin-top:.1rem">{{ $k['sonuc'] }}@if ($k['url']) · <a href="{{ $k['url'] }}" style="color:rgb(13 148 136)">Modüle git →</a>@endif</div>
                        </div>
                    </div>
                @endforeach
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Uygulama yol haritası</x-slot>
                <x-slot name="description">Kapsamdan izlemeye kadar sıralı iş akışı.</x-slot>
                @foreach (RMV::yolHaritasi($kontroller, $o ?? ['toplam' => 0, 'geciken' => 0]) as $i => [$b, $a, $tamam])
                    <div style="display:flex;gap:.6rem;align-items:flex-start;margin-bottom:.5rem">
                        <span style="width:1.6rem;height:1.6rem;flex:none;border-radius:9999px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:#fff;background:{{ $tamam ? 'rgb(21 128 61)' : 'rgb(13 148 136)' }}">{{ $tamam ? '✓' : $i + 1 }}</span>
                        <div style="font-size:.8rem"><strong>{{ $b }}</strong><div style="font-size:.74rem;color:rgb(107 114 128)">{{ $a }}</div></div>
                    </div>
                @endforeach
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Mevzuat dayanak başlıkları</x-slot>
                <x-slot name="description">Mevzuat başlıkları yol göstericidir; güncel metin ve işyerine özgü uygulanabilirlik ekip tarafından doğrulanır.</x-slot>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;font-size:.8rem">
                    <div><strong>Ortak mevzuat</strong><ul style="margin:.3rem 0 0 1rem;list-style:disc">@foreach (config('risk_nace.ortak_mevzuat') as $m)<li>{{ $m }}</li>@endforeach</ul></div>
                    <div><strong>NACE / risk alanı ile ilişkili</strong><ul style="margin:.3rem 0 0 1rem;list-style:disc">@foreach ($ng['mevzuat'] ?? [] as $m)<li>{{ $m }}</li>@endforeach</ul></div>
                </div>
            </x-filament::section>

        {{-- ================= RAPORLAR ================= --}}
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.8rem">
                <div style="{{ $kart }}">
                    <div style="font-weight:700">Risk PDF</div>
                    <div style="font-size:.75rem;color:rgb(107 114 128);margin:.2rem 0 .6rem">{{ $yontemAd ?? 'Seçili yöntem' }} — kapak, ekip, skorlar ve onay bölümü (Kayıtlı Değerlendirmeler'den).</div>
                    @if ($rd)<x-filament::button size="sm" tag="a" icon="heroicon-o-document-arrow-down" :href="\App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource::getUrl('edit', ['record' => $rd])">Değerlendirmeyi aç</x-filament::button>@endif
                </div>
                <div style="{{ $kart }}">
                    <div style="font-weight:700">Risk Excel</div>
                    <div style="font-size:.75rem;color:rgb(107 114 128);margin:.2rem 0 .6rem">Tüm risk maddeleri + istatistik sayfası (düzey ve bölüm dağılımı).</div>
                    <x-filament::button size="sm" icon="heroicon-o-table-cells" wire:click="riskExcel" :disabled="! $rd">Excel indir</x-filament::button>
                </div>
                <div style="{{ $kart }}">
                    <div style="font-weight:700">Önlem (DÖF) Excel</div>
                    <div style="font-size:.75rem;color:rgb(107 114 128);margin:.2rem 0 .6rem">Önerilen önlemler, sorumlu, termin, gecikme ve durum.</div>
                    <x-filament::button size="sm" icon="heroicon-o-table-cells" wire:click="aksiyonExcel">Önlem Excel</x-filament::button>
                </div>
            </div>
        @endif
    @else
        <x-filament::section>Aktif işyeri bulunamadı.</x-filament::section>
    @endif
</x-filament-panels::page>
