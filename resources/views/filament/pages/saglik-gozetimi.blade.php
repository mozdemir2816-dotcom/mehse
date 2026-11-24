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
        {{-- K1 SEKMELER: Tetkik & Muayene | Revir & Protokol Defteri | Ecza Dolabı & SKT | Aşı Takibi --}}
        <div style="display:flex;gap:.4rem;flex-wrap:wrap;background:rgb(128 116 148 / .1);padding:.3rem;border-radius:.6rem">
            @foreach (\App\Filament\Pages\SaglikGozetimi::SEKMELER as $anahtar => $etiket)
                @php $aktif = $sekme === $anahtar; @endphp
                <button type="button" wire:click="sekmeSec('{{ $anahtar }}')"
                    style="flex:1;min-width:130px;padding:.48rem .75rem;border:none;border-radius:.45rem;cursor:pointer;font-weight:600;font-size:.83rem;
                        background:{{ $aktif ? '#2563EB' : 'transparent' }};color:{{ $aktif ? '#fff' : 'inherit' }}">
                    {{ $etiket }}
                </button>
            @endforeach
        </div>

        @if ($sekme === 'tetkik')
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
        @elseif ($sekme === 'revir')
            <x-filament::section icon="heroicon-o-clipboard-document-list" icon-color="primary">
                <x-slot name="heading">Revir &amp; Poliklinik Protokol Defteri ({{ $this->revirToplamSayisi }})</x-slot>
                <x-slot name="afterHeader">
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-document-arrow-down" wire:click="protokolDefteriPdf">Protokol Defteri (PDF)</x-filament::button>
                </x-slot>
                @unless ($this->tibbiVeriGorebilirMi)
                    <div style="margin-bottom:.75rem;padding:.55rem .8rem;border-radius:.5rem;border:1px solid rgb(217 119 6 / .5);background:rgb(217 119 6 / .08);color:#92400e;font-size:.78rem">
                        🔒 <strong>6331 m.15/5 &amp; KVKK m.6 Gizlilik Koruması:</strong> Çalışanların tıbbi tanı ve klinik işlem detayları yalnızca İşyeri Hekimi / DSP yetkisinde görünür; bu ekranda maskelenmiştir.
                    </div>
                @endunless
                <div style="{{ $kutu }};display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:.55rem;margin-bottom:1rem;align-items:end">
                    <div>
                        <label style="font-size:.75rem;font-weight:600">Çalışan (Kayıtlı)</label>
                        <select wire:model="revirCalisanId" style="{{ $girdi }}">
                            <option value="">— Liste dışıysa sağa yaz —</option>
                            @foreach ($this->calisanlar as $c)<option value="{{ $c->id }}">{{ $c->ad_soyad }}</option>@endforeach
                        </select>
                    </div>
                    <div><label style="font-size:.75rem;font-weight:600">Ad Soyad (Alternatif)</label><input type="text" wire:model="revirAdSoyad" style="{{ $girdi }}" placeholder="Çalışan adı"></div>
                    <div><label style="font-size:.75rem;font-weight:600">Başvuru Nedeni *</label><input type="text" wire:model="revirNeden" style="{{ $girdi }}" placeholder="Örn: Baş ağrısı / kesik"></div>
                    <div><label style="font-size:.75rem;font-weight:600">Bulgu / Tanı</label><input type="text" wire:model="revirTani" style="{{ $girdi }}" placeholder="Tansiyon, pansuman vb."></div>
                    <div><label style="font-size:.75rem;font-weight:600">Yapılan İşlem</label><input type="text" wire:model="revirIslem" style="{{ $girdi }}" placeholder="Pansuman / reçete / sevk"></div>
                    <div>
                        <label style="font-size:.75rem;font-weight:600">Sonuç</label>
                        <select wire:model.live="revirSonuc" style="{{ $girdi }}">
                            @foreach (\App\Models\RevirKaydi::SONUCLAR as $k => $ad)<option value="{{ $k }}">{{ $ad }}</option>@endforeach
                        </select>
                    </div>
                    @if ($revirSonuc === 'istirahat')
                        <div><label style="font-size:.75rem;font-weight:600">İstirahat (Gün)</label><input type="number" min="1" max="180" wire:model="revirIstirahatGun" style="{{ $girdi }}" placeholder="1"></div>
                    @endif
                    <div><x-filament::button size="sm" wire:click="revirEkle" wire:loading.attr="disabled">Protokole Ekle</x-filament::button></div>
                </div>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                        <thead><tr style="text-align:left;background:rgb(107 114 128 / .08)"><th style="padding:.4rem">Protokol</th><th style="padding:.4rem">Tarih</th><th style="padding:.4rem">Çalışan</th><th style="padding:.4rem">Neden &amp; İşlem</th><th style="padding:.4rem">Sonuç</th><th style="padding:.4rem"></th></tr></thead>
                        <tbody>
                            @forelse ($this->revirKayitlari as $r)
                                <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                                    <td style="padding:.4rem;font-weight:700;white-space:nowrap">{{ $r->protokol_no }}</td>
                                    <td style="padding:.4rem;white-space:nowrap">{{ $r->tarih?->format('d.m.Y H:i') }}</td>
                                    <td style="padding:.4rem;font-weight:600">{{ $r->calisan_ad_soyad }}</td>
                                    <td style="padding:.4rem">
                                        @if ($this->tibbiVeriGorebilirMi)
                                            {{ $r->basvuru_nedeni }}@if ($r->bulgu_tani) <span style="font-size:.73rem;color:#6b7280">({{ $r->bulgu_tani }})</span>@endif
                                            @if ($r->yapilan_islem)<div style="font-size:.73rem;color:rgb(107 114 128)">{{ $r->yapilan_islem }}</div>@endif
                                        @else
                                            <span style="font-size:.75rem;color:#6b7280;font-style:italic">[Gizli — 6331 m.15/5]</span>
                                        @endif
                                    </td>
                                    <td style="padding:.4rem">{{ $r->sonucEtiketi() }}</td>
                                    <td style="padding:.4rem;text-align:right"><button type="button" wire:click="revirSil({{ $r->id }})" wire:confirm="Bu revir kaydı silinsin mi?" style="color:#b91c1c;background:none;border:none;cursor:pointer">✕</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="padding:1rem;text-align:center;color:rgb(107 114 128)">Henüz revir başvurusu yok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($this->revirToplamSayisi > $this->revirKayitlari->count())
                    <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:.6rem">
                        Son {{ $this->revirKayitlari->count() }} kayıt gösteriliyor (+{{ $this->revirToplamSayisi - $this->revirKayitlari->count() }} eski kayıt daha — tümü <strong>Protokol Defteri (PDF)</strong> çıktısında yer alır).
                    </p>
                @endif
            </x-filament::section>
        @elseif ($sekme === 'ecza')
            <x-filament::section icon="heroicon-o-beaker" icon-color="primary">
                <x-slot name="heading">Ecza Dolabı &amp; İlk Yardım Çantası Stoğu ({{ $this->eczaKalemleri->count() }})</x-slot>
                <div style="{{ $kutu }};display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.55rem;margin-bottom:1rem;align-items:end">
                    <div><label style="font-size:.75rem;font-weight:600">Malzeme / İlaç Adı *</label><input type="text" wire:model="eczaAd" style="{{ $girdi }}" placeholder="Örn: Steril gazlı bez"></div>
                    <div>
                        <label style="font-size:.75rem;font-weight:600">Kategori</label>
                        <select wire:model="eczaKategori" style="{{ $girdi }}">
                            @foreach (\App\Models\EczaDolabiKalemi::KATEGORILER as $k => $ad)<option value="{{ $k }}">{{ $ad }}</option>@endforeach
                        </select>
                    </div>
                    <div><label style="font-size:.75rem;font-weight:600">Miktar</label><input type="number" min="0" wire:model="eczaMiktar" style="{{ $girdi }}"></div>
                    <div><label style="font-size:.75rem;font-weight:600">Kritik Eşik</label><input type="number" min="0" wire:model="eczaKritik" style="{{ $girdi }}"></div>
                    <div><label style="font-size:.75rem;font-weight:600">SKT</label><input type="date" wire:model="eczaSkt" style="{{ $girdi }}"></div>
                    <div><label style="font-size:.75rem;font-weight:600">Konum</label><input type="text" wire:model="eczaKonum" style="{{ $girdi }}"></div>
                    <div><x-filament::button size="sm" wire:click="eczaEkle">Stoğa Ekle</x-filament::button></div>
                </div>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                        <thead><tr style="text-align:left;background:rgb(107 114 128 / .08)"><th style="padding:.4rem">Malzeme</th><th style="padding:.4rem">Konum</th><th style="padding:.4rem">Stok</th><th style="padding:.4rem">SKT</th><th style="padding:.4rem">Durum</th><th style="padding:.4rem"></th></tr></thead>
                        <tbody>
                            @forelse ($this->eczaKalemleri as $ek)
                                @php
                                    $dr = $ek->durum();
                                    $drRenk = match ($dr) { 'suresi_gecmis' => '#dc2626', 'skt_yaklasan', 'kritik_stok' => '#d97706', default => '#15803d' };
                                @endphp
                                <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                                    <td style="padding:.4rem;font-weight:600">{{ $ek->malzeme_adi }}<div style="font-size:.72rem;color:rgb(107 114 128)">{{ \App\Models\EczaDolabiKalemi::KATEGORILER[$ek->kategori] ?? $ek->kategori }}</div></td>
                                    <td style="padding:.4rem">{{ $ek->konum ?: '—' }}</td>
                                    <td style="padding:.4rem;white-space:nowrap">
                                        <button type="button" wire:click="eczaMiktarDegistir({{ $ek->id }}, -1)" style="padding:.1rem .4rem;border:1px solid #cbd5e1;border-radius:4px;cursor:pointer">−</button>
                                        <strong style="margin:0 .4rem">{{ $ek->miktar }} {{ $ek->birim }}</strong>
                                        <button type="button" wire:click="eczaMiktarDegistir({{ $ek->id }}, 1)" style="padding:.1rem .4rem;border:1px solid #cbd5e1;border-radius:4px;cursor:pointer">+</button>
                                    </td>
                                    <td style="padding:.4rem;white-space:nowrap">{{ $ek->skt?->format('d.m.Y') ?: '—' }}</td>
                                    <td style="padding:.4rem"><span style="font-size:.73rem;font-weight:700;color:{{ $drRenk }}">{{ $ek->durumEtiketi() }}</span></td>
                                    <td style="padding:.4rem;text-align:right"><button type="button" wire:click="eczaSil({{ $ek->id }})" style="color:#b91c1c;background:none;border:none;cursor:pointer">✕</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="padding:1rem;text-align:center;color:rgb(107 114 128)">Ecza dolabı kalemi tanımlanmamış.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @else
            <x-filament::section icon="heroicon-o-shield-check" icon-color="primary">
                <x-slot name="heading">Çalışan Aşı Takibi ({{ $this->asiKayitlari->count() }})</x-slot>
                <div style="{{ $kutu }};display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.55rem;margin-bottom:1rem;align-items:end">
                    <div>
                        <label style="font-size:.75rem;font-weight:600">Çalışan</label>
                        <select wire:model="asiCalisanId" style="{{ $girdi }}">
                            <option value="">— Liste dışıysa sağa yaz —</option>
                            @foreach ($this->calisanlar as $c)<option value="{{ $c->id }}">{{ $c->ad_soyad }}</option>@endforeach
                        </select>
                    </div>
                    <div><label style="font-size:.75rem;font-weight:600">Ad Soyad (Alternatif)</label><input type="text" wire:model="asiAdSoyad" style="{{ $girdi }}"></div>
                    <div>
                        <label style="font-size:.75rem;font-weight:600">Aşı Türü</label>
                        <select wire:model="asiTur" style="{{ $girdi }}">
                            @foreach (\App\Models\AsiKaydi::TURLER as $k => $ad)<option value="{{ $k }}">{{ $ad }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:600">Doz</label>
                        <select wire:model="asiDoz" style="{{ $girdi }}">
                            @foreach (\App\Models\AsiKaydi::DOZLAR as $k => $ad)<option value="{{ $k }}">{{ $ad }}</option>@endforeach
                        </select>
                    </div>
                    <div><label style="font-size:.75rem;font-weight:600">Yapılma Tarihi</label><input type="date" wire:model="asiTarih" style="{{ $girdi }}"></div>
                    <div><label style="font-size:.75rem;font-weight:600">Sonraki Doz / Rapel</label><input type="date" wire:model="asiSonrakiTarih" style="{{ $girdi }}"></div>
                    <div><x-filament::button size="sm" wire:click="asiEkle">Aşı Ekle</x-filament::button></div>
                </div>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                        <thead><tr style="text-align:left;background:rgb(107 114 128 / .08)"><th style="padding:.4rem">Çalışan</th><th style="padding:.4rem">Aşı / Doz</th><th style="padding:.4rem">Yapılma</th><th style="padding:.4rem">Sonraki Doz</th><th style="padding:.4rem"></th></tr></thead>
                        <tbody>
                            @forelse ($this->asiKayitlari as $a)
                                @php $sd = $a->sonrakiDurum(); @endphp
                                <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                                    <td style="padding:.4rem;font-weight:600">{{ $a->calisan_ad_soyad }}</td>
                                    <td style="padding:.4rem">{{ $a->asi_turu }} · <strong>{{ $a->doz }}</strong></td>
                                    <td style="padding:.4rem;white-space:nowrap">{{ $a->yapilma_tarihi?->format('d.m.Y') }}</td>
                                    <td style="padding:.4rem;white-space:nowrap;color:{{ $sd === 'gecikti' ? '#dc2626' : ($sd === 'yaklasan' ? '#d97706' : 'inherit') }};font-weight:{{ in_array($sd, ['gecikti', 'yaklasan'], true) ? '700' : '400' }}">
                                        {{ $a->sonraki_doz_tarihi?->format('d.m.Y') ?: 'Tamamlandı' }}
                                    </td>
                                    <td style="padding:.4rem;text-align:right"><button type="button" wire:click="asiSil({{ $a->id }})" style="color:#b91c1c;background:none;border:none;cursor:pointer">✕</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" style="padding:1rem;text-align:center;color:rgb(107 114 128)">Aşı kaydı bulunmuyor.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
