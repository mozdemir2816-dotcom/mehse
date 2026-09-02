@php
    $yesil = 'rgb(16 185 129)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $icerik = $this->icerik;
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma, eğitim başlığı ve katılımcı listesini seçin; "Form PDF" ile 6331 Sayılı Kanun
        Madde 17 kapsamındaki eğitim katılım formunu oluşturun.
    </p>

    {{-- 1. FİRMA & EĞİTİM BİLGİLERİ --}}
    <x-filament::section icon="heroicon-o-academic-cap" icon-color="success">
        <x-slot name="heading">1. Firma & Eğitim Bilgileri</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
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
                <label style="font-weight:600;font-size:.82rem">Eğitim Başlığı <span style="color:#ef4444">*</span></label>
                <select wire:model.live="baslikAnahtari"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    @foreach ($this->basliklar as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Eğitim Yeri</label>
                <input type="text" wire:model="egitimYeri" placeholder="Örn: Şantiye şefliği toplantı odası"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Belge Düzenleme Tarihi <span style="color:#ef4444">*</span></label>
                <input type="date" wire:model="belgeTarihi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Eğitim Süresi (Gün)</label>
                <input type="number" min="1" wire:model="sureGun"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            @if ($baslikAnahtari === 'genel')
                <div>
                    <label style="font-weight:600;font-size:.82rem">İşyerine Özgü Risk Sektörü</label>
                    <select wire:model.live="sektorAnahtari"
                        style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <option value="">— Sektör seçin —</option>
                        @foreach ($this->sektorler as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        @if ($baslikAnahtari === 'genel' && ($icerik['saat'] ?? null))
            <div style="{{ $kutu }};margin-top:1rem;background:rgb(16 185 129 / .06);border-color:rgb(16 185 129 / .3);font-size:.82rem">
                <strong>{{ $this->firma?->tehlikeSinifiEtiketi() ?? 'Az Tehlikeli' }}</strong> sınıfı için toplam eğitim süresi:
                <strong>{{ $icerik['saat'] }} saat</strong>
                @if ($icerik['isyerine_ozgu'] ?? null)
                    (işyerine özgü riskler {{ $icerik['isyerine_ozgu']['dakika'] }} dk dahil)
                @endif
                · Dinlenme/ara: {{ $icerik['dinlenme_dk'] }} dk.
            </div>
        @endif

        <div style="margin-top:1rem">
            <div style="font-weight:600;font-size:.85rem;margin-bottom:.4rem">Eğitimciler</div>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer">
                    <input type="checkbox" wire:model="isgUzmaniVar"> İş Güvenliği Uzmanı
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer">
                    <input type="checkbox" wire:model.live="isyeriHekimiVar"> İşyeri Hekimi
                </label>
                @if ($isyeriHekimiVar)
                    <input type="text" wire:model="isyeriHekimiAdi" placeholder="İşyeri Hekimi Ad Soyad"
                        style="padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
                @endif
            </div>
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. KONU İÇERİĞİ --}}
        <x-filament::section icon="heroicon-o-book-open" icon-color="success">
            <x-slot name="heading">2. Eğitim Konuları</x-slot>

            @if (($icerik['tip'] ?? null) === 'genel')
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem">
                    <div style="{{ $kutu }}">
                        <div style="font-weight:700;font-size:.85rem;margin-bottom:.4rem">Genel Konular</div>
                        <ol style="margin:0;padding-left:1.1rem;font-size:.8rem;display:flex;flex-direction:column;gap:.25rem">
                            @foreach ($icerik['genel_konular'] as $m)
                                <li>{{ $m['madde'] }} <span style="color:rgb(107 114 128)">({{ $m['dakika'] }} dk)</span></li>
                            @endforeach
                        </ol>
                    </div>
                    <div style="{{ $kutu }}">
                        <div style="font-weight:700;font-size:.85rem;margin-bottom:.4rem">Sağlık Konuları</div>
                        <ol style="margin:0;padding-left:1.1rem;font-size:.8rem;display:flex;flex-direction:column;gap:.25rem">
                            @foreach ($icerik['saglik_konulari'] as $m)
                                <li>{{ $m['madde'] }} <span style="color:rgb(107 114 128)">({{ $m['dakika'] }} dk)</span></li>
                            @endforeach
                        </ol>
                    </div>
                    <div style="{{ $kutu }}">
                        <div style="font-weight:700;font-size:.85rem;margin-bottom:.4rem">Teknik Konular</div>
                        <ol style="margin:0;padding-left:1.1rem;font-size:.8rem;display:flex;flex-direction:column;gap:.25rem">
                            @foreach ($icerik['teknik_konular'] as $m)
                                <li>{{ $m['madde'] }} <span style="color:rgb(107 114 128)">({{ $m['dakika'] }} dk)</span></li>
                            @endforeach
                        </ol>
                    </div>
                    <div style="{{ $kutu }}">
                        <div style="font-weight:700;font-size:.85rem;margin-bottom:.4rem">
                            İşyerine Özgü Riskler
                            @if ($icerik['isyerine_ozgu'] ?? null)
                                <span style="font-weight:400;color:rgb(107 114 128)">— {{ $icerik['isyerine_ozgu']['sektor'] }}</span>
                            @endif
                        </div>
                        @if ($icerik['isyerine_ozgu'] ?? null)
                            <ol style="margin:0;padding-left:1.1rem;font-size:.8rem;display:flex;flex-direction:column;gap:.25rem">
                                @foreach ($icerik['isyerine_ozgu']['maddeler'] as $madde)
                                    <li>{{ $madde }}</li>
                                @endforeach
                            </ol>
                        @else
                            <p style="font-size:.78rem;color:#f59e0b;margin:0">Sektör seçilmedi — üstteki "İşyerine Özgü Risk Sektörü" alanından seçin.</p>
                        @endif
                    </div>
                </div>
            @else
                <div style="{{ $kutu }}">
                    <div style="font-weight:700;font-size:.85rem;margin-bottom:.4rem">{{ $icerik['ad'] ?? '' }}</div>
                    <ol style="margin:0;padding-left:1.1rem;font-size:.82rem;display:flex;flex-direction:column;gap:.3rem">
                        @foreach (($icerik['maddeler'] ?? []) as $madde)
                            <li>{{ $madde }}</li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </x-filament::section>

        {{-- 3. KATILIMCI LİSTESİ --}}
        <x-filament::section icon="heroicon-o-users" icon-color="success">
            <x-slot name="heading">
                3. Katılımcı Listesi
                <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ count($secilenCalisanIdler) + count($manuelKatilimcilar) }} kişi)</span>
            </x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="font-weight:600;font-size:.82rem;margin-bottom:.4rem">Firma Çalışanları</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.35rem;margin-bottom:.5rem">
                    @foreach ($this->calisanlar as $c)
                        @php $secili = in_array($c->id, $secilenCalisanIdler, true); @endphp
                        <button type="button" wire:click="calisanToggle({{ $c->id }})"
                            style="text-align:left;padding:.45rem .65rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                                border:1px solid {{ $secili ? $yesil : 'rgb(107 114 128 / .3)' }};
                                background:{{ $secili ? 'rgb(16 185 129 / .08)' : 'transparent' }}">
                            {{ $secili ? '☑' : '☐' }} {{ $c->ad_soyad }}
                            @if ($c->gorev) <span style="color:rgb(107 114 128)">— {{ $c->gorev }}</span> @endif
                        </button>
                    @endforeach
                </div>
                <div style="display:flex;gap:.5rem;margin-bottom:1rem">
                    <x-filament::button size="xs" color="gray" wire:click="tumCalisanlar(true)">Tümünü Seç</x-filament::button>
                    <x-filament::button size="xs" color="gray" wire:click="tumCalisanlar(false)">Tümünü Kaldır</x-filament::button>
                </div>
            @else
                <p style="font-size:.82rem;color:rgb(107 114 128)">Bu firmaya kayıtlı çalışan bulunamadı.</p>
            @endif

            <div style="font-weight:600;font-size:.82rem;margin-bottom:.4rem">Ek Katılımcı Ekle</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr)) auto;gap:.5rem;align-items:end;margin-bottom:.5rem">
                <div>
                    <input type="text" wire:model="yeniAdSoyad" placeholder="Ad Soyad"
                        style="width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <input type="text" wire:model="yeniTc" placeholder="T.C. Kimlik No"
                        style="width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <input type="text" wire:model="yeniGorev" placeholder="Görevi"
                        style="width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <x-filament::button size="sm" wire:click="manuelEkle">Ekle</x-filament::button>
            </div>

            @if ($manuelKatilimcilar)
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;margin-bottom:.75rem">
                    <tr>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Ad Soyad</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">T.C. No</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Görevi</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($manuelKatilimcilar as $i => $k)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $k['ad_soyad'] }}</td>
                            <td style="padding:.3rem .5rem">{{ $k['tc'] ?: '—' }}</td>
                            <td style="padding:.3rem .5rem">{{ $k['gorev'] ?: '—' }}</td>
                            <td style="padding:.3rem .5rem;text-align:right">
                                <button type="button" wire:click="manuelCikar({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>

                <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;cursor:pointer;margin-bottom:1rem">
                    <input type="checkbox" wire:model="elleEklenenleriFirmayaKaydet"> Elle eklenenleri firmaya da kaydet
                </label>
            @endif

            <div style="font-weight:600;font-size:.82rem;margin-bottom:.4rem">Excel ile Toplu Yükle</div>
            <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                <input type="file" wire:model="excelDosya" accept=".xlsx,.xls,.csv" style="font-size:.8rem">
                <x-filament::button size="sm" color="gray" wire:click="excelIceAktar">Excel Yükle</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="excelSablonIndir">Şablon İndir</x-filament::button>
            </div>
            @if ($excelHatalar)
                <div style="margin-top:.5rem;font-size:.78rem;color:#f59e0b">
                    @foreach ($excelHatalar as $h) <div>{{ $h }}</div> @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- 4. GEÇMİŞ KAYITLAR --}}
        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Eğitim Kayıtları</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Belge No</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Başlık</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tarih</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Katılımcı</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($this->gecmisKayitlar as $k)
                        <tr>
                            <td style="padding:.35rem .5rem">{{ $k->belge_no }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->basliklarEtiketi() }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->belge_tarihi?->format('d.m.Y') }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->katilimciSayisi() }}</td>
                            <td style="padding:.35rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $k->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $k->id }})">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif

    <div style="{{ $kutu }};background:rgb(107 114 128 / .05);font-size:.78rem;color:rgb(107 114 128)">
        6331 Sayılı İş Sağlığı ve Güvenliği Kanunu Madde 17 uyarınca çalışanlara verilen eğitimler
        belgelendirilerek özlük dosyasında saklanmalıdır.
    </div>
</x-filament-panels::page>
