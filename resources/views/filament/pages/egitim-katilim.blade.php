@php
    $yesil = 'rgb(16 185 129)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma, eğitim başlığı ve katılımcı listesini seçin; "Form PDF" ile 6331 Sayılı Kanun
        Madde 17 kapsamındaki eğitim katılım formunu oluşturun.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'egitim_katilim_formu'])

    {{-- 0. BOŞ İMZA FORMU — firma/katılımcı seçmeden, tek tıkla --}}
    <x-filament::section icon="heroicon-o-pencil-square" icon-color="gray" collapsible collapsed>
        <x-slot name="heading">Boş İmza Formu İndir</x-slot>
        <x-slot name="description">Firma/katılımcı seçmeden, yalnız konu içeriğiyle — eğitime gelenlerin kendi el yazısıyla ad/T.C./imza atması için (en az 10 satır).</x-slot>

        <div style="{{ $kutu }};margin-bottom:.75rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:end">
            <div>
                <label style="font-weight:600;font-size:.78rem">Tehlike Sınıfı (Genel başlığı için)</label>
                <select wire:model="bosFormTehlikeSinifi" style="margin-top:.3rem;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem">
                    <option value="az_tehlikeli">Az Tehlikeli</option>
                    <option value="tehlikeli">Tehlikeli</option>
                    <option value="cok_tehlikeli">Çok Tehlikeli</option>
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.78rem">Eğitim Türü</label>
                <select wire:model="bosFormTuru" style="margin-top:.3rem;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem">
                    <option value="ilk">İlk Defa</option>
                    <option value="tekrar">Tekrar</option>
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.78rem">İşyerine Özgü Sektör (opsiyonel)</label>
                <select wire:model="bosFormSektor" style="margin-top:.3rem;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem">
                    <option value="">— Seçilmedi —</option>
                    @foreach ($this->sektorler as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.5rem">
            @foreach ($this->basliklar as $anahtar => $ad)
                <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="bosFormIndir('{{ $anahtar }}')">
                    {{ $ad }}
                </x-filament::button>
            @endforeach
        </div>
    </x-filament::section>

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
                @php $toplamDk = \App\Support\EgitimIcerikOlusturucu::toplamDakika($icerik); @endphp
                <p style="font-size:.72rem;color:rgb(107 114 128);margin-top:.25rem">
                    Toplam süre ≈ {{ intdiv($toplamDk, 60) }}s {{ $toplamDk % 60 }}dk.
                    @if ($toplamDk > \App\Support\EgitimIcerikOlusturucu::IKI_GUN_ESIGI_DK)
                        11 saati aştığı için <strong>2 gün</strong> planlandı; imzalar 1. ve 2. gün ayrı alınır. Elle değiştirebilirsiniz.
                    @else
                        11 saati aşınca otomatik 2 güne çıkar.
                    @endif
                </p>
            </div>
            @if ($baslikAnahtari === 'genel')
                <div>
                    <label style="font-weight:600;font-size:.82rem">Eğitim Türü</label>
                    <select wire:model.live="egitimTuru"
                        style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <option value="ilk">İlk Defa</option>
                        <option value="tekrar">Tekrar (her zaman 8 saat)</option>
                    </select>
                </div>
            @endif
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
                @if ($egitimTuru === 'tekrar')
                    <strong>Tekrar eğitimi</strong> — tehlike sınıfından bağımsız her zaman <strong>8 saat</strong>
                    (4 blok × 2 saat).
                @else
                    <strong>{{ $this->firma?->tehlikeSinifiEtiketi() ?? 'Az Tehlikeli' }}</strong> sınıfı için ilk defa verilecek
                    eğitimin toplam süresi: <strong>{{ $icerik['saat'] }} saat</strong> (4 blok × {{ $icerik['saat'] / 4 }} saat).
                @endif
                Aşağıdaki "Eğitim Konuları" bölümünden maddeleri işaretleyip/kaldırıp dakikalarını değiştirerek gerçek süreyi kendiniz belirleyebilirsiniz.
                <div style="margin-top:.4rem;color:rgb(107 114 128)">
                    Tekrar (periyodik yenileme) eğitiminin yapılması gereken periyot —
                    Az Tehlikeli: {{ config('isg.egitim.tekrar_periyodu_yil.az_tehlikeli') }} yılda 1,
                    Tehlikeli: {{ config('isg.egitim.tekrar_periyodu_yil.tehlikeli') }} yılda 1,
                    Çok Tehlikeli: {{ config('isg.egitim.tekrar_periyodu_yil.cok_tehlikeli') }} yılda 1.
                </div>
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
            @if ($this->firma)
                <div style="margin-top:.5rem;font-size:.78rem;color:rgb(107 114 128)">
                    İş Güvenliği Uzmanı:
                    <strong>{{ $this->firma->igu?->ad_soyad ?? 'firmaya atanmamış' }}</strong>
                    @if ($this->firma->igu && ! $this->firma->igu->kase_gorseli) (kaşe görseli yok) @endif
                    &nbsp;•&nbsp; İşyeri Hekimi:
                    <strong>{{ $this->firma->isyeriHekimi?->ad_soyad ?? 'firmaya atanmamış' }}</strong>
                    @if ($this->firma->isyeriHekimi && ! $this->firma->isyeriHekimi->kase_gorseli) (kaşe görseli yok) @endif
                    <br>Ad ve kaşe, belge oluşturulduğunda firma kaydından (İSG Profesyonelleri) alınır.
                </div>
            @endif
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. KONU İÇERİĞİ --}}
        <x-filament::section icon="heroicon-o-book-open" icon-color="success">
            <x-slot name="heading">2. Eğitim Konuları</x-slot>
            <x-slot name="description">Her maddeyi işaretleyip dakikasını değiştirebilirsiniz. İşyerine özgü konuları ayrıca ekleyip metnini düzenleyebilirsiniz.</x-slot>

            @php $wireModelKok = 'icerik'; $konularDuzenlenebilir = true; @endphp
            @include('filament.pages.partials.egitim-konulari')
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
                                <x-filament::button size="xs" color="gray" wire:click="gecmisExcel({{ $k->id }})">Excel</x-filament::button>
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
