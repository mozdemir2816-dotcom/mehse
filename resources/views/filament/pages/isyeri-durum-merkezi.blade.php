@php
    use App\Support\IsyeriDurumu;
    $firma = $this->firma;
    $girdi = 'padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem';
    $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.75rem 1rem';
    $th = 'text-align:left;padding:.5rem .6rem;font-size:.7rem;letter-spacing:.05em;text-transform:uppercase;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .2)';
    $td = 'padding:.55rem .6rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $renk = ['tamamlandi' => 'rgb(21 128 61)', 'eksik' => 'rgb(220 38 38)', 'gecikmis' => 'rgb(185 28 28)', 'yaklasan' => 'rgb(217 119 6)', 'bilgi' => 'rgb(37 99 235)'];
    $tRenk = ['gecikmis' => 'rgb(220 38 38)', 'cok_yakin' => 'rgb(234 88 12)', 'yaklasiyor' => 'rgb(217 119 6)', 'ileri' => 'rgb(107 114 128)', 'tamamlandi' => 'rgb(21 128 61)'];
@endphp

<x-filament-panels::page>
    <div style="{{ $kart }}" class="idm-yazdirma">
        <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.25rem">Firma / işyeri seçiniz</label>
        <select wire:model.live="firmaId" style="{{ $girdi }};width:100%;max-width:640px">
            @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
        </select>
    </div>

    @if (! $firma)
        <x-filament::section>Aktif işyeri bulunamadı.</x-filament::section>
    @else
        @php $o = $this->ozet; $g = $this->gostergeler; @endphp

        {{-- Başlık + dışa aktarım --}}
        <div style="{{ $kart }};display:flex;flex-wrap:wrap;gap:.75rem;justify-content:space-between;align-items:center">
            <div>
                <div style="font-weight:800;font-size:1.05rem">{{ $firma->unvan }}</div>
                <div style="font-size:.75rem;color:rgb(107 114 128)">
                    İşyeri Durum Merkezi · gerçek kayıtlardan birleşik görünüm
                    @if ($firma->sgk_sicil_no) · Sicil: {{ $firma->sgk_sicil_no }} @endif · {{ $firma->tehlikeSinifiEtiketi() }}
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:.4rem" class="idm-yazdirma">
                <x-filament::button size="sm" color="gray" icon="heroicon-o-document-text" wire:click="tamPdf">Tam PDF</x-filament::button>
                <x-filament::button size="sm" color="gray" icon="heroicon-o-table-cells" wire:click="detayliExcel">Detaylı Excel</x-filament::button>
                <x-filament::button size="sm" color="gray" icon="heroicon-o-printer" x-on:click="window.print()">Yazdır</x-filament::button>
                <x-filament::button size="sm" icon="heroicon-o-arrow-path" wire:click="yenile">Yenile</x-filament::button>
                <x-filament::button size="sm" color="gray" icon="heroicon-o-pencil-square" tag="a" :href="\App\Filament\Resources\Firmas\FirmaResource::getUrl('edit', ['record' => $firma])">Firma kaydı</x-filament::button>
            </div>
        </div>

        <div style="{{ $kart }};display:flex;justify-content:space-between;gap:1rem;background:rgb(124 58 237 / .05)">
            <div>
                <div style="font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;color:rgb(124 58 237);font-weight:700">Firma 360 · yönetici dosyası</div>
                <div style="font-weight:800;font-size:1.1rem">Tek ekranda firma resmi</div>
                <div style="font-size:.78rem;color:rgb(107 114 128)">İSG operasyonu, sözleşme ve yetkiniz dahilindeki cari veriler aynı rapor bütününde. Sağlık bilgileri yalnız toplu sayı olarak gösterilir.</div>
            </div>
            <div style="text-align:right;white-space:nowrap">
                <div style="font-size:.72rem;color:rgb(107 114 128)">Rapor tarihi</div>
                <div style="font-weight:800">{{ now()->format('d.m.Y') }}</div>
            </div>
        </div>

        {{-- Genel uyum --}}
        <div style="{{ $kart }};border-left:4px solid {{ $o['gecikmis'] + $o['eksik'] > 0 ? 'rgb(220 38 38)' : ($o['yaklasan'] ? 'rgb(217 119 6)' : 'rgb(21 128 61)') }}">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <div>
                    <div style="font-weight:800">Genel uyum durumu</div>
                    <div style="font-size:.82rem;color:rgb(107 114 128)">{{ $o['genel'] }}</div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:1.6rem;font-weight:800;color:rgb(124 58 237)">%{{ $o['yuzde'] }}</div>
                    <div style="font-size:.7rem;color:rgb(107 114 128)">ölçülebilir süreç tamamlanması</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.6rem;margin-top:.7rem">
                @foreach ([['Tamamlanan', 'tamamlandi'], ['Eksik', 'eksik'], ['Gecikmiş', 'gecikmis'], ['Yaklaşan', 'yaklasan']] as [$ad, $k])
                    <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.6rem;padding:.5rem .8rem">
                        <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $ad }}</div>
                        <div style="font-size:1.4rem;font-weight:800;color:{{ $renk[$k] }}">{{ $o[$k] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Süreçler --}}
        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
            <x-slot name="heading">Eksikler, tamamlanan süreçler ve sorumlular</x-slot>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem;min-width:820px">
                    <tr>@foreach (['Süreç', 'Durum', 'Gerçek veri sonucu', 'Sorumlu', 'Kaynak'] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                    @foreach ($this->surecler as $s)
                        <tr>
                            <td style="{{ $td }};font-weight:600">{{ $s['surec'] }}</td>
                            <td style="{{ $td }};white-space:nowrap;font-weight:700;color:{{ $renk[$s['durum']] }}">{{ IsyeriDurumu::DURUMLAR[$s['durum']] }}</td>
                            <td style="{{ $td }};line-height:1.45">{{ $s['sonuc'] }}</td>
                            <td style="{{ $td }};color:rgb(75 85 99)">{{ $s['sorumlu'] }}</td>
                            <td style="{{ $td }};white-space:nowrap">@if ($s['url'])<a href="{{ $s['url'] }}" class="idm-yazdirma" style="font-size:.75rem;font-weight:700;color:rgb(124 58 237)">Modüle git →</a>@endif</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </x-filament::section>

        {{-- Yükümlülük takvimi --}}
        @php
            $takvim = $this->takvim;
            $say = $takvim->countBy('durum');
            $kategoriler = $takvim->pluck('kategori')->unique()->sort()->values();
        @endphp
        <x-filament::section icon="heroicon-o-calendar-days" icon-color="primary">
            <x-slot name="heading">Yükümlülük takvimi — yaklaşan, gecikmiş ve tamamlanan kayıtlar</x-slot>
            <x-slot name="description">Gecikmişler önce gelir. Çok yakın 0–7 gün, yaklaşıyor 8–30 gün aralığıdır.</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.5rem;margin-bottom:.8rem">
                @foreach (['gecikmis', 'cok_yakin', 'yaklasiyor', 'tamamlandi'] as $k)
                    <button type="button" wire:click="$set('takvimDurum', '{{ $takvimDurum === $k ? '' : $k }}')"
                            style="text-align:left;border-radius:.6rem;padding:.55rem .8rem;border:1px solid {{ $tRenk[$k] }};{{ $takvimDurum === $k ? 'background:'.$tRenk[$k].';color:#fff' : '' }}">
                        <div style="font-size:.72rem">{{ IsyeriDurumu::TAKVIM_DURUMLARI[$k] }}</div>
                        <div style="font-size:1.3rem;font-weight:800;{{ $takvimDurum === $k ? '' : 'color:'.$tRenk[$k] }}">{{ $say[$k] ?? 0 }}</div>
                    </button>
                @endforeach
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.5rem;align-items:end" class="idm-yazdirma">
                <div><label style="font-size:.72rem;font-weight:600">Kategori</label>
                    <select wire:model.live="takvimKategori" style="{{ $girdi }};width:100%"><option value="">Tüm kategoriler</option>@foreach ($kategoriler as $k)<option value="{{ $k }}">{{ $k }}</option>@endforeach</select></div>
                <div><label style="font-size:.72rem;font-weight:600">Durum</label>
                    <select wire:model.live="takvimDurum" style="{{ $girdi }};width:100%"><option value="">Tüm durumlar</option>@foreach (IsyeriDurumu::TAKVIM_DURUMLARI as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div><label style="font-size:.72rem;font-weight:600">Başlangıç</label><input type="date" wire:model.live="takvimBaslangic" style="{{ $girdi }};width:100%"></div>
                <div><label style="font-size:.72rem;font-weight:600">Bitiş</label><input type="date" wire:model.live="takvimBitis" style="{{ $girdi }};width:100%"></div>
                <div><label style="font-size:.72rem;font-weight:600">Sayfa boyutu</label>
                    <select wire:model.live="sayfaBoyutu" style="{{ $girdi }};width:100%">@foreach ([10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} kayıt</option>@endforeach</select></div>
                <div><x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-uturn-left" wire:click="takvimSifirla">Sıfırla</x-filament::button></div>
            </div>

            <div style="font-size:.75rem;color:rgb(107 114 128);margin:.7rem 0 .3rem">{{ $this->filtreliTakvim->count() }} kayıt bulundu</div>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem;min-width:820px">
                    <tr>@foreach (['Durum', 'Kategori', 'Kayıt', 'Tarih', 'Süre', 'Sorumlu', 'İşlem'] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                    @forelse ($this->takvimSayfasi() as $t)
                        <tr>
                            <td style="{{ $td }};white-space:nowrap"><span style="font-size:.7rem;font-weight:700;color:#fff;background:{{ $tRenk[$t['durum']] }};border-radius:.35rem;padding:.15rem .45rem">{{ \Illuminate\Support\Str::before(IsyeriDurumu::TAKVIM_DURUMLARI[$t['durum']], ' (') }}</span></td>
                            <td style="{{ $td }}">{{ $t['kategori'] }}</td>
                            <td style="{{ $td }}"><strong>{{ $t['kayit'] }}</strong>@if ($t['alt'])<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $t['alt'] }}</div>@endif</td>
                            <td style="{{ $td }};white-space:nowrap">{{ $t['tarih']->format('d.m.Y') }}</td>
                            <td style="{{ $td }};white-space:nowrap">{{ $t['durum'] === 'tamamlandi' ? 'Tamamlandı' : IsyeriDurumu::kalanMetni($t['tarih']) }}</td>
                            <td style="{{ $td }};color:rgb(75 85 99)">{{ $t['sorumlu'] }}</td>
                            <td style="{{ $td }}">@if ($t['url'])<a href="{{ $t['url'] }}" class="idm-yazdirma" style="font-size:.75rem;font-weight:700;color:rgb(124 58 237)">Kaydı aç</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="{{ $td }};text-align:center;color:rgb(107 114 128)">Termini olan kayıt yok.</td></tr>
                    @endforelse
                </table>
            </div>
            @if ($this->sayfaSayisi() > 1)
                <div style="display:flex;gap:.5rem;justify-content:center;align-items:center;margin-top:.6rem;font-size:.8rem" class="idm-yazdirma">
                    <x-filament::button size="xs" color="gray" wire:click="sayfaDegistir(-1)">← Önceki</x-filament::button>
                    Sayfa {{ min($takvimSayfa, $this->sayfaSayisi()) }} / {{ $this->sayfaSayisi() }}
                    <x-filament::button size="xs" color="gray" wire:click="sayfaDegistir(1)">Sonraki →</x-filament::button>
                </div>
            @endif
        </x-filament::section>

        {{-- Göstergeler --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem">
            @foreach ([
                ['Personel', $g['personel']], ['Şube', $g['sube']], ['Görevlendirme', $g['gorevlendirme']], ['Evrak uyumu', '%'.$g['evrak_uyum']],
                ['Risk maddesi (son RD)', $g['risk_maddesi']], ['Açık DÖF', $g['acik_dof']], ['Gecikmiş muayene', $g['gecikmis_muayene']], ['Eğitim kaydı', $g['egitim_kaydi']],
            ] as [$ad, $deger])
                <div style="{{ $kart }}">
                    <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $ad }}</div>
                    <div style="font-size:1.35rem;font-weight:800">{{ $deger }}</div>
                </div>
            @endforeach
        </div>

        {{-- Profil + Görevlendirmeler --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem">
            <x-filament::section icon="heroicon-o-building-office" icon-color="gray">
                <x-slot name="heading">Profil</x-slot>
                <dl style="display:grid;grid-template-columns:auto 1fr;gap:.35rem .9rem;font-size:.82rem">
                    @foreach ([
                        'Yetkili / işveren' => $firma->isveren_vekili ?: $firma->isveren_ad,
                        'Telefon' => $firma->telefon, 'E-posta' => $firma->eposta, 'Vergi no' => $firma->vergi_no,
                        'NACE / tehlike' => trim($firma->nace_kodu.' · '.$firma->tehlikeSinifiEtiketi(), ' ·'),
                        'SGK sicil' => $firma->sgk_sicil_no, 'İSG-KATİP no' => $firma->katip_no,
                        'Adres' => trim($firma->adres.' '.$firma->ilce.' '.$firma->il),
                        'Durum' => $firma->aktif ? 'Aktif' : 'Pasif',
                    ] as $etiket => $deger)
                        <dt style="color:rgb(107 114 128)">{{ $etiket }}</dt><dd>{{ filled($deger) ? $deger : '—' }}</dd>
                    @endforeach
                </dl>
            </x-filament::section>

            <x-filament::section icon="heroicon-o-user-group" icon-color="gray">
                <x-slot name="heading">Görevlendirmeler</x-slot>
                @php $gorev = \App\Support\IsyeriDurumu::gorevlendirmeler($firma); @endphp
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>@foreach (['Profesyonel', 'Rol', 'Otomatik aylık süre'] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                    @forelse ($gorev as $r)
                        <tr>
                            <td style="{{ $td }}">{{ $r['ad'] }}@if ($r['sertifika'])<div style="font-size:.7rem;color:rgb(107 114 128)">Sertifika {{ $r['sertifika'] }}</div>@endif</td>
                            <td style="{{ $td }}">{{ $r['rol'] }}</td>
                            <td style="{{ $td }}"><strong>{{ $r['aylik_dk'] }} dk</strong><div style="font-size:.7rem;color:rgb(107 114 128)">{{ \App\Support\GorevDurumu::saatDk($r['aylik_dk']) }} / ay</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="{{ $td }};color:rgb(107 114 128)">Aktif görevlendirme yok.</td></tr>
                    @endforelse
                </table>
                <div style="font-size:.7rem;color:rgb(107 114 128);margin-top:.4rem">Süre = çalışan sayısı × İSG Hizmetleri Yönetmeliği aylık asgari süresi (İGU {{ config('isg.igu_aylik_dk.'.$firma->tehlike_sinifi) }}, hekim {{ config('isg.hekim_aylik_dk.'.$firma->tehlike_sinifi) }} dk).</div>
            </x-filament::section>
        </div>

        {{-- Son ziyaretler + sözleşme --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem">
            <x-filament::section icon="heroicon-o-map-pin" icon-color="gray">
                <x-slot name="heading">Son ziyaretler</x-slot>
                <x-slot name="afterHeader"><x-filament::button size="xs" color="gray" tag="a" :href="\App\Filament\Pages\ZiyaretProgrami::getUrl(['firma' => $firma->id])" class="idm-yazdirma">Ziyaret programı</x-filament::button></x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>@foreach (['Tarih', 'Konu', 'Süre', 'Durum'] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                    @forelse (\App\Support\IsyeriDurumu::sonZiyaretler($firma) as $z)
                        <tr>
                            <td style="{{ $td }};white-space:nowrap">{{ \Illuminate\Support\Carbon::parse($z['tarih'])->format('d.m.Y') }}</td>
                            <td style="{{ $td }}">{{ $z['amac'] ?: '—' }}</td>
                            <td style="{{ $td }}">{{ $z['sure_saat'] ? $z['sure_saat'].' sa' : '—' }}</td>
                            <td style="{{ $td }}">{{ ['tamamlandi' => 'Tamamlandı', 'planlandi' => 'Planlandı'][$z['durum']] ?? 'Boş' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="{{ $td }};text-align:center;color:rgb(107 114 128)">Kayıt yok.</td></tr>
                    @endforelse
                </table>
            </x-filament::section>

            <x-filament::section icon="heroicon-o-document-text" icon-color="gray">
                <x-slot name="heading">Hizmet sözleşmesi</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>@foreach (['Başlangıç', 'Bitiş', 'Kalan', 'Durum'] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                    @if ($firma->sozlesme_baslangic || $firma->sozlesme_bitis)
                        @php $bitis = $firma->sozlesme_bitis ? \Illuminate\Support\Carbon::parse($firma->sozlesme_bitis) : null; @endphp
                        <tr>
                            <td style="{{ $td }}">{{ $firma->sozlesme_baslangic ? \Illuminate\Support\Carbon::parse($firma->sozlesme_baslangic)->format('d.m.Y') : '—' }}</td>
                            <td style="{{ $td }}">{{ $bitis?->format('d.m.Y') ?? '—' }}</td>
                            <td style="{{ $td }}">{{ $bitis ? \App\Support\IsyeriDurumu::kalanMetni($bitis) : '—' }}</td>
                            <td style="{{ $td }};font-weight:700;color:{{ $bitis?->isPast() ? 'rgb(220 38 38)' : 'rgb(21 128 61)' }}">{{ $bitis?->isPast() ? 'Süresi doldu' : 'Yürürlükte' }}</td>
                        </tr>
                    @else
                        <tr><td colspan="4" style="{{ $td }};text-align:center;color:rgb(107 114 128)">Sözleşme tarihleri girilmemiş.</td></tr>
                    @endif
                </table>
            </x-filament::section>
        </div>

        {{-- Son olaylar --}}
        <x-filament::section icon="heroicon-o-exclamation-circle" icon-color="gray">
            <x-slot name="heading">Son olaylar</x-slot>
            <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                <tr>@foreach (['Form', 'Tür', 'Özet', 'Tarih'] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                @forelse (\App\Support\IsyeriDurumu::sonOlaylar($firma) as $olay)
                    <tr>
                        <td style="{{ $td }};white-space:nowrap">{{ $olay->belge_no ?? '—' }}</td>
                        <td style="{{ $td }}">{{ $olay->tipEtiketi() }}</td>
                        <td style="{{ $td }}">{{ \Illuminate\Support\Str::limit((string) $olay->olay_ozeti, 90) ?: '—' }}</td>
                        <td style="{{ $td }};white-space:nowrap">{{ $olay->olay_tarihi?->format('d.m.Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="{{ $td }};text-align:center;color:rgb(107 114 128)">Olay kaydı yok.</td></tr>
                @endforelse
            </table>
        </x-filament::section>
    @endif

    <style>
        @media print {
            .fi-sidebar, .fi-topbar, .idm-yazdirma, .fi-header { display: none !important; }
            .fi-main { padding: 0 !important; max-width: none !important; }
        }
    </style>
</x-filament-panels::page>
