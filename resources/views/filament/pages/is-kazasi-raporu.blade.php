@php
    $kirmizi = '#dc2626';
    $lbl = 'font-weight:600;font-size:.82rem;display:block;margin-bottom:.25rem';
    $inp = 'width:100%;padding:.45rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem';
    $ozet = $this->onizleme;
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İş Kazası İnceleme ve Kök Neden Analiz Raporu — 6331 s.K. Madde 14. Bölümleri sırayla
        doldurun; "Taslak Kaydet" ile ara kayıt alın, "Raporu Tamamla ve İndir" ile PDF üretin.
        @if ($duzenlenenId) <strong style="color:{{ $kirmizi }}">(Geçmiş bir kayıt düzenleniyor.)</strong> @endif
    </p>

    <x-filament::section icon="heroicon-o-exclamation-circle" icon-color="danger">
        <x-slot name="heading">Firma</x-slot>
        <select wire:model.live="firmaId" style="{{ $inp }};max-width:460px">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </x-filament::section>

    @if ($this->firma)
        {{-- 1. GENEL BİLGİLER --}}
        <x-filament::section icon="heroicon-o-clipboard-document" icon-color="danger">
            <x-slot name="heading">1. Genel Bilgiler ve Kaza Özeti</x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="margin-bottom:.75rem">
                    <label style="{{ $lbl }}">Kazazede Hızlı Seç</label>
                    <select wire:model.live="kazazedeHizliSecId" style="{{ $inp }}">
                        <option value="">— manuel gir —</option>
                        @foreach ($this->calisanlar as $c)
                            <option value="{{ $c->id }}">{{ $c->ad_soyad }}@if ($c->gorev) — {{ $c->gorev }} @endif</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                <div><label style="{{ $lbl }}">Kaza Tarihi <span style="color:{{ $kirmizi }}">*</span></label><input type="date" wire:model="kazaTarihi" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Kaza Saati</label><input type="time" wire:model="kazaSaati" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Kaza Yeri / Bölümü <span style="color:{{ $kirmizi }}">*</span></label><input type="text" wire:model="kazaYeri" placeholder="Örn: Pres bölümü" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Kazazede Ad Soyad <span style="color:{{ $kirmizi }}">*</span></label><input type="text" wire:model="kazazedeAdSoyad" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">T.C. Kimlik No</label><input type="text" wire:model="kazazedeTc" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Görev</label><input type="text" wire:model="kazazedeGorev" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Kıdem</label><input type="text" wire:model="kazazedeKidem" placeholder="Örn: 4 yıl" style="{{ $inp }}"></div>
                <div>
                    <label style="{{ $lbl }}">Kaza Türü</label>
                    <select wire:model="kazaTuru" style="{{ $inp }}">
                        <option value="">— seçin —</option>
                        @foreach ($this->kazaTurleri as $a => $ad) <option value="{{ $a }}">{{ $ad }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label style="{{ $lbl }}">Ağırlık Derecesi</label>
                    <select wire:model="agirlikDerecesi" style="{{ $inp }}">
                        <option value="">— seçin —</option>
                        @foreach ($this->agirlikDereceleri as $a => $ad) <option value="{{ $a }}">{{ $ad }}</option> @endforeach
                    </select>
                </div>
                <div><label style="{{ $lbl }}">Kayıp Gün Sayısı</label><input type="number" min="0" wire:model="kayipGunSayisi" style="{{ $inp }}"></div>
            </div>

            <div style="margin-top:1rem">
                <label style="{{ $lbl }}">Kaza Özeti <span style="color:{{ $kirmizi }}">*</span></label>
                <p style="font-size:.75rem;color:rgb(107 114 128);margin-bottom:.3rem">Olayın oluş şeklini teknik terimlerle, yorum katmadan, olduğu gibi ifade edin.</p>
                <textarea wire:model="kazaTanimi" rows="4" style="{{ $inp }}"></textarea>
            </div>

            <div style="margin-top:1rem;display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem"><input type="checkbox" wire:model.live="sgkBildirimiYapildi"> SGK bildirimi yapıldı</label>
                @if ($sgkBildirimiYapildi)
                    <input type="date" wire:model="sgkBildirimTarihi" style="{{ $inp }};max-width:180px">
                @endif
            </div>
        </x-filament::section>

        {{-- 2. 5 NEDEN --}}
        <x-filament::section icon="heroicon-o-magnifying-glass" icon-color="warning">
            <x-slot name="heading">2. Kök Neden Analizi (5 Neden)</x-slot>
            <x-slot name="description">Her soruyu yanıtlayarak kök nedene ulaşın. Görünür neden "tedbirsizlik" gibi durabilir; kök neden sistemdedir.</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:.9rem">
                @foreach ($this->besNedenSorulari as $i => $soru)
                    <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.5rem;padding:.7rem">
                        <div style="font-weight:700;font-size:.82rem;margin-bottom:.35rem"><span style="color:{{ $kirmizi }}">{{ $i + 1 }}.</span> {{ $soru }}</div>
                        <textarea wire:model.blur="besNeden.{{ $i }}" rows="2" placeholder="Yanıtı yazın…" style="{{ $inp }}"></textarea>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:1rem">
                <label style="{{ $lbl }}">Kök Neden Kategorileri (balık kılçığına otomatik dağıtılır)</label>
                <div style="display:flex;gap:.6rem;flex-wrap:wrap">
                    @foreach ($this->kokNedenSecenekleri as $a => $ad)
                        <label style="display:flex;align-items:center;gap:.3rem;font-size:.8rem;cursor:pointer">
                            <input type="checkbox" wire:model="kokNedenKategorileri" value="{{ $a }}"> {{ $ad }}
                        </label>
                    @endforeach
                </div>
            </div>
        </x-filament::section>

        {{-- 3. BALIK KILÇIĞI --}}
        <x-filament::section icon="heroicon-o-share" icon-color="info">
            <x-slot name="heading">3. Balık Kılçığı (Ishikawa) — 6M Modeli</x-slot>
            <x-slot name="description">Kazanın olası tüm nedenlerini 6 ana kategoride analiz edin. Örnek bulgulara tıklayarak ekleyebilirsiniz.</x-slot>

            <x-filament::button size="xs" color="gray" wire:click="balikKilcigiOtomatik" style="margin-bottom:.75rem">
                5N ve Kök Nedenlerden Doldur
            </x-filament::button>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:.75rem">
                @foreach ($this->balikKategorileri as $anahtar => $tanim)
                    <div style="border:1px solid rgb(107 114 128 / .3);border-radius:.5rem;padding:.6rem">
                        <div style="font-weight:700;font-size:.82rem">{{ $tanim['ad'] }}
                            <span style="font-weight:400;color:rgb(107 114 128)">({{ count($balikKilcigi[$anahtar] ?? []) }})</span>
                        </div>
                        <div style="font-size:.7rem;color:rgb(107 114 128);margin-bottom:.4rem">{{ $tanim['aciklama'] }}</div>

                        <div style="display:flex;flex-wrap:wrap;gap:.25rem;margin-bottom:.4rem">
                            @foreach ($tanim['ornekler'] as $ornek)
                                <button type="button" wire:click="balikNedenEkle('{{ $anahtar }}', @js($ornek))"
                                    style="font-size:.68rem;border:1px dashed rgb(107 114 128 / .5);border-radius:999px;padding:.1rem .45rem;cursor:pointer;background:transparent">
                                    + {{ $ornek }}
                                </button>
                            @endforeach
                        </div>

                        @foreach ($balikKilcigi[$anahtar] ?? [] as $i => $neden)
                            <div style="display:flex;gap:.3rem;margin-bottom:.3rem">
                                <input type="text" wire:model="balikKilcigi.{{ $anahtar }}.{{ $i }}" placeholder="Neden…" style="{{ $inp }};font-size:.78rem">
                                <button type="button" wire:click="balikNedenSil('{{ $anahtar }}', {{ $i }})" style="color:#ef4444;background:none;border:none;cursor:pointer">✕</button>
                            </div>
                        @endforeach
                        <x-filament::button size="xs" color="gray" wire:click="balikNedenEkle('{{ $anahtar }}')" icon="heroicon-o-plus">Neden</x-filament::button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 4. DÖF --}}
        <x-filament::section icon="heroicon-o-wrench" icon-color="success">
            <x-slot name="heading">4. Düzeltici ve Önleyici Faaliyetler (DÖF)</x-slot>
            <x-slot name="description">Her kök neden için en az bir düzeltici faaliyet tanımlayın. Sorumlu kişi ve hedef tarih belirlemeyi unutmayın.</x-slot>

            @foreach ($dofMaddeleri as $i => $d)
                <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.5rem;padding:.7rem;margin-bottom:.6rem">
                    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.6rem;align-items:end;margin-bottom:.5rem">
                        <div>
                            <label style="{{ $lbl }}">Önlem Tipi</label>
                            <select wire:model.blur="dofMaddeleri.{{ $i }}.tip" style="{{ $inp }}">
                                @foreach ($this->dofTipleri as $a => $ad) <option value="{{ $a }}">{{ $ad }}</option> @endforeach
                            </select>
                        </div>
                        <div><label style="{{ $lbl }}">Sorumlu</label><input type="text" wire:model.blur="dofMaddeleri.{{ $i }}.sorumlu" placeholder="Örn: Bakım Md." style="{{ $inp }}"></div>
                        <button type="button" wire:click="dofSil({{ $i }})" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem;padding-bottom:.4rem">✕</button>
                    </div>
                    <div style="margin-bottom:.5rem">
                        <label style="{{ $lbl }}">Açıklama <span style="color:{{ $kirmizi }}">*</span></label>
                        <textarea wire:model.blur="dofMaddeleri.{{ $i }}.aciklama" rows="2" style="{{ $inp }}"></textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem">
                        <div><label style="{{ $lbl }}">Hedef Tarih</label><input type="date" wire:model.blur="dofMaddeleri.{{ $i }}.hedef_tarih" style="{{ $inp }}"></div>
                        <div>
                            <label style="{{ $lbl }}">Durum</label>
                            <select wire:model.blur="dofMaddeleri.{{ $i }}.durum" style="{{ $inp }}">
                                @foreach ($this->dofDurumlari as $a => $ad) <option value="{{ $a }}">{{ $ad }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endforeach
            <x-filament::button size="sm" color="gray" wire:click="dofEkle" icon="heroicon-o-plus">Yeni DÖF Ekle</x-filament::button>
        </x-filament::section>

        {{-- 5. FOTO & NOTLAR --}}
        <x-filament::section icon="heroicon-o-camera" icon-color="primary">
            <x-slot name="heading">5. Fotoğraf ve Kritik Notlar</x-slot>
            <x-slot name="description">Olay yerini "kaza olduğu haliyle" her açıdan fotoğraflayın; tanık ifadelerini ayrı ayrı alın.</x-slot>

            <div style="margin-bottom:.75rem">
                <label style="{{ $lbl }}">Kaza Yeri Fotoğrafları</label>
                <input type="file" wire:model="yeniFotograflar" multiple accept="image/*" style="font-size:.8rem">
                @if ($yeniFotograflar)
                    <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.4rem">
                        @foreach ($yeniFotograflar as $i => $f)
                            <span style="font-size:.72rem;background:rgb(107 114 128 / .1);padding:.15rem .5rem;border-radius:.3rem">
                                {{ $f->getClientOriginalName() }} <button type="button" wire:click="fotoSil({{ $i }})" style="color:#ef4444;border:none;background:none;cursor:pointer">✕</button>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <label style="{{ $lbl }}">Kritik Notlar ve Gözlemler</label>
            <textarea wire:model="kritikNotlar" rows="3" placeholder="Olay yeri gözlemleri, tanık ifade özetleri, ramak kala kayıtları kontrolü, varsa ek notlar…" style="{{ $inp }}"></textarea>

            <div style="margin-top:.75rem">
                <label style="{{ $lbl }}">Tanıklar</label>
                <div style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:end;margin-bottom:.4rem">
                    <input type="text" wire:model="yeniTanikAd" placeholder="Ad Soyad" style="{{ $inp }};max-width:220px">
                    <input type="text" wire:model="yeniTanikGorev" placeholder="Görevi" style="{{ $inp }};max-width:180px">
                    <x-filament::button size="sm" wire:click="tanikEkle">Ekle</x-filament::button>
                </div>
                @foreach ($taniklar as $i => $t)
                    <div style="font-size:.8rem">• {{ $t['ad_soyad'] }} @if ($t['gorev']) ({{ $t['gorev'] }}) @endif
                        <button type="button" wire:click="tanikSil({{ $i }})" style="color:#ef4444;border:none;background:none;cursor:pointer">✕</button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 6. ÖNİZLEME --}}
        <x-filament::section icon="heroicon-o-check-badge" icon-color="gray">
            <x-slot name="heading">6. Önizleme ve Son Kontrol</x-slot>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem;margin-bottom:.75rem">
                <div style="border:1px solid rgb(107 114 128 / .3);border-radius:.5rem;padding:.7rem;text-align:center">
                    <div style="font-size:1.4rem;font-weight:800">{{ $ozet['bes_neden'] }}/5</div>
                    <div style="font-size:.72rem;color:rgb(107 114 128)">Kök Neden</div>
                </div>
                <div style="border:1px solid rgb(107 114 128 / .3);border-radius:.5rem;padding:.7rem;text-align:center">
                    <div style="font-size:1.4rem;font-weight:800">{{ $ozet['ishikawa'] }}</div>
                    <div style="font-size:.72rem;color:rgb(107 114 128)">Ishikawa Bulgu</div>
                </div>
                <div style="border:1px solid rgb(107 114 128 / .3);border-radius:.5rem;padding:.7rem;text-align:center">
                    <div style="font-size:1.4rem;font-weight:800">{{ $ozet['dof'] }}</div>
                    <div style="font-size:.72rem;color:rgb(107 114 128)">DÖF Aksiyonu</div>
                </div>
            </div>

            @if ($ozet['eksik'])
                <div style="border:1px solid #f59e0b;background:rgb(245 158 11 / .1);border-radius:.5rem;padding:.6rem .85rem;font-size:.8rem">
                    <strong style="color:#b45309">Eksik Alanlar:</strong> {{ implode(', ', $ozet['eksik']) }}
                </div>
            @endif

            <div style="margin-top:1rem;display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem"><input type="checkbox" wire:model.live="isyeriHekimiDahil"> İndirilen PDF'e İşyeri Hekimi kaşesi ekle</label>
                @if ($isyeriHekimiDahil)
                    <input type="text" wire:model="isyeriHekimiAdi" placeholder="İşyeri Hekimi Ad Soyad" style="{{ $inp }};max-width:240px">
                @endif
            </div>
            <p style="font-size:.75rem;color:rgb(107 114 128);margin-top:.4rem">
                İSG Uzmanı kaşesi firma kaydından (İGU) otomatik alınır. "Raporu Tamamla ve İndir" ile durum
                "Tamamlandı" olur ve PDF üretilir.
            </p>
        </x-filament::section>

        {{-- GEÇMİŞ --}}
        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Raporlar</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisKayitlar as $k)
                        <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                            <td style="padding:.35rem .5rem">{{ $k->belge_no }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->kazazede_ad_soyad }} — {{ $k->kaza_tarihi?->format('d.m.Y') }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->durumEtiketi() }}</td>
                            <td style="padding:.35rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="duzenle({{ $k->id }})">Düzenle</x-filament::button>
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $k->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $k->id }})">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
