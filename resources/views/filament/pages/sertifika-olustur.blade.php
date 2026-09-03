@php
    $altin = 'rgb(217 119 6)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $icerik = $this->icerik;
    $coklu = $this->cokluEgiticiMi;
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma, sertifika tipi ve katılımcı listesini seçin; "Sertifikayı Oluştur" ile
        katılımcı başına ayrı bir sayfa halinde eğitim sertifikası PDF'i indirin.
    </p>

    {{-- 1. FİRMA & SERTİFİKA TİPİ --}}
    <x-filament::section icon="heroicon-o-check-badge" icon-color="warning">
        <x-slot name="heading">1. Firma & Sertifika Tipi</x-slot>

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
        </div>

        <div style="margin-top:1rem;display:flex;gap:.4rem;flex-wrap:wrap">
            @foreach ($this->tipler as $anahtar => $t)
                @php $secili = $tip === $anahtar; @endphp
                <button type="button" wire:click="$set('tip','{{ $anahtar }}')"
                    style="padding:.4rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                        border:1px solid {{ $secili ? $altin : 'rgb(107 114 128 / .3)' }};
                        background:{{ $secili ? 'rgb(217 119 6 / .1)' : 'transparent' }}">
                    {{ $t['ad'] }}
                </button>
            @endforeach
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. EĞİTİM BİLGİLERİ --}}
        <x-filament::section icon="heroicon-o-calendar" icon-color="warning">
            <x-slot name="heading">2. Eğitim Bilgileri</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                @if ($coklu)
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
                <div>
                    <label style="font-weight:600;font-size:.82rem">Eğitim Gün Sayısı</label>
                    <input type="number" min="1" max="10" wire:model.live="gunSayisi"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Eğitim Süresi</label>
                    <select wire:model="sureMetni"
                        style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <option value="">— Seçin —</option>
                        @foreach (config('isg.sertifika.sureler') as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-top:1rem;display:flex;gap:1.5rem;flex-wrap:wrap">
                <div>
                    <label style="font-weight:600;font-size:.82rem">Eğitim Türü</label>
                    <div style="display:flex;gap:.4rem;margin-top:.3rem">
                        @foreach (config('isg.sertifika.turler') as $anahtar => $ad)
                            @php $secili = $tur === $anahtar; @endphp
                            <button type="button" wire:click="$set('tur','{{ $anahtar }}')"
                                style="padding:.4rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.78rem;
                                    border:1px solid {{ $secili ? $altin : 'rgb(107 114 128 / .3)' }};
                                    background:{{ $secili ? 'rgb(217 119 6 / .1)' : 'transparent' }}">
                                {{ $ad }}
                            </button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Eğitim Şekli</label>
                    <div style="display:flex;gap:.4rem;margin-top:.3rem">
                        @foreach (config('isg.sertifika.sekiller') as $anahtar => $ad)
                            @php $secili = $sekil === $anahtar; @endphp
                            <button type="button" wire:click="$set('sekil','{{ $anahtar }}')"
                                style="padding:.4rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.78rem;
                                    border:1px solid {{ $secili ? $altin : 'rgb(107 114 128 / .3)' }};
                                    background:{{ $secili ? 'rgb(217 119 6 / .1)' : 'transparent' }}">
                                {{ $ad }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div style="margin-top:1rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.6rem">
                @foreach ($egitimTarihleri as $i => $tarih)
                    <div>
                        <label style="font-weight:600;font-size:.78rem">Eğitim Tarihi {{ $i + 1 }}</label>
                        <input type="date" wire:model="egitimTarihleri.{{ $i }}"
                            style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                @endforeach
            </div>

            <div style="margin-top:1rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:end">
                <div>
                    <label style="font-weight:600;font-size:.82rem">Geçerlilik Tarihi</label>
                    <input type="date" wire:model="gecerlilikTarihi"
                        style="margin-top:.3rem;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <x-filament::button size="xs" color="gray" wire:click="gecerlilikOtomatik('az_tehlikeli')">Az Tehlikeli (+{{ config('isg.sertifika.gecerlilik_yili.az_tehlikeli') }} yıl)</x-filament::button>
                <x-filament::button size="xs" color="warning" wire:click="gecerlilikOtomatik('tehlikeli')">Tehlikeli (+{{ config('isg.sertifika.gecerlilik_yili.tehlikeli') }} yıl)</x-filament::button>
                <x-filament::button size="xs" color="danger" wire:click="gecerlilikOtomatik('cok_tehlikeli')">Çok Tehlikeli (+{{ config('isg.sertifika.gecerlilik_yili.cok_tehlikeli') }} yıl)</x-filament::button>
            </div>

            <div style="margin-top:1.2rem">
                <div style="font-weight:600;font-size:.85rem;margin-bottom:.4rem">Eğiticiler</div>
                @if ($coklu)
                    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center">
                        <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer">
                            <input type="checkbox" wire:model.live="egiticiIguDahil"> İş Güvenliği Uzmanı
                        </label>
                        @if ($egiticiIguDahil)
                            <input type="text" wire:model="egiticiIguAdi" placeholder="İGU Ad Soyad"
                                style="padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
                        @endif
                        <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer">
                            <input type="checkbox" wire:model.live="egiticiHekimDahil"> İşyeri Hekimi
                        </label>
                        @if ($egiticiHekimDahil)
                            <input type="text" wire:model="egiticiHekimAdi" placeholder="İşyeri Hekimi Ad Soyad"
                                style="padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
                        @endif
                    </div>
                @else
                    <input type="text" wire:model="egiticiIguAdi" placeholder="Eğitimi Veren Ad Soyad"
                        style="width:100%;max-width:24rem;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem">
                @endif

                <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:.5rem">
                    @if ($this->firma->igu?->kase_gorseli || $this->firma->isyeriHekimi?->kase_gorseli)
                        Kaşe: bu firmaya atanmış İSG Profesyoneli'nin kaşesi sertifikaya otomatik basılır.
                    @else
                        Kaşe yok — <a href="{{ \App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource::getUrl() }}" style="color:{{ $altin }};text-decoration:underline">İSG Profesyonelleri</a>
                        panelinden kaşe yükleyip Firma düzenleme sayfasından atayın.
                    @endif
                </p>
            </div>

            <div style="margin-top:1.2rem">
                <div style="font-weight:600;font-size:.85rem;margin-bottom:.4rem">Sertifika Logosu</div>
                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                    @foreach (['yok' => 'Logo Yok', 'sol' => 'Sol', 'sag' => 'Sağ', 'iki_taraf' => 'Her İki Taraf'] as $anahtar => $etiket)
                        @php $secili = $logoKonumu === $anahtar; @endphp
                        <button type="button" wire:click="$set('logoKonumu','{{ $anahtar }}')"
                            style="padding:.35rem .65rem;border-radius:.4rem;cursor:pointer;font-size:.78rem;
                                border:1px solid {{ $secili ? $altin : 'rgb(107 114 128 / .3)' }};
                                background:{{ $secili ? 'rgb(217 119 6 / .1)' : 'transparent' }}">
                            {{ $etiket }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div style="margin-top:1rem">
                <label style="font-weight:600;font-size:.82rem">Sertifika Çerçevesi</label>
                <select wire:model="cerceve"
                    style="margin-top:.3rem;width:100%;max-width:24rem;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;display:block">
                    @foreach (config('isg.sertifika.cerceveler') as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
        </x-filament::section>

        {{-- 3. KONU İÇERİĞİ --}}
        <x-filament::section icon="heroicon-o-book-open" icon-color="warning" collapsible>
            <x-slot name="heading">3. Eğitim Konuları</x-slot>
            <x-slot name="description">Her maddeyi işaretleyip dakikasını değiştirebilirsiniz.</x-slot>

            @php $wireModelKok = 'icerik'; @endphp
            @include('filament.pages.partials.egitim-konulari')
        </x-filament::section>

        {{-- 4. KATILIMCI LİSTESİ --}}
        <x-filament::section icon="heroicon-o-users" icon-color="warning">
            <x-slot name="heading">
                4. Katılımcı Listesi
                <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ count($secilenCalisanIdler) + count($manuelKatilimcilar) }} kişi)</span>
            </x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="font-weight:600;font-size:.82rem;margin-bottom:.4rem">Firma Çalışanları</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.35rem;margin-bottom:.5rem">
                    @foreach ($this->calisanlar as $c)
                        @php $secili = in_array($c->id, $secilenCalisanIdler, true); @endphp
                        <button type="button" wire:click="calisanToggle({{ $c->id }})"
                            style="text-align:left;padding:.45rem .65rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                                border:1px solid {{ $secili ? $altin : 'rgb(107 114 128 / .3)' }};
                                background:{{ $secili ? 'rgb(217 119 6 / .08)' : 'transparent' }}">
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

        {{-- 5. GEÇMİŞ KAYITLAR --}}
        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Sertifikalar</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Belge No</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tip</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Geçerlilik</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Katılımcı</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($this->gecmisKayitlar as $k)
                        <tr>
                            <td style="padding:.35rem .5rem">{{ $k->belge_no }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->tipEtiketi() }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->gecerlilik_tarihi?->format('d.m.Y') ?: '—' }}</td>
                            <td style="padding:.35rem .5rem">{{ count($k->katilimcilar ?? []) }}</td>
                            <td style="padding:.35rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $k->id }})">PDF</x-filament::button>
                                @if ($k->tip === 'isg')
                                    <x-filament::button size="xs" color="success" wire:click="gecmisYildizGrup({{ $k->id }})">Yıldız Grup</x-filament::button>
                                @endif
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
</x-filament-panels::page>
