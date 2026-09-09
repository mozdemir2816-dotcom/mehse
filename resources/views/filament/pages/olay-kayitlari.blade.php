@php
    $mor = 'rgb(124 58 237)';
    $inp = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $lbl = 'font-weight:600;font-size:.82rem';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İş Kazası Raporu'ndan ayrı olay defteri: yaralanma olmasa da (ramak kala, tehlikeli
        durum/davranış, maddi hasar, çevre) her olayı kaydedin. Her kayıtta sınıflandırma,
        potansiyel risk (olasılık × şiddet) ve <strong>5 Neden (5N)</strong> kök neden analizi yapılır.
    </p>

    <div style="{{ $kutu }};background:rgb(124 58 237 / .06);border-color:rgb(124 58 237 / .3);font-size:.78rem;color:#5b21b6">
        6331 sayılı Kanun m.14 — işveren, iş kazaları ve meslek hastalıklarının yanı sıra
        <strong>ramak kala olaylarını da kayıt altına alır</strong> ve gerekli incelemeleri yaparak raporlar.
    </div>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'olay_ramak_kala_kaydi'])

    {{-- 1. FİRMA & OLAY TİPİ --}}
    <x-filament::section icon="heroicon-o-bell-alert" icon-color="warning">
        <x-slot name="heading">1. Firma & Olay Tipi</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
            <div>
                <label style="{{ $lbl }}">Firma Seçin <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId" style="{{ $inp }}">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">Olay Tipi <span style="color:#ef4444">*</span></label>
                <select wire:model.live="olayTipi" style="{{ $inp }}">
                    @foreach ($this->tipler as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. OLAY BİLGİLERİ --}}
        <x-filament::section icon="heroicon-o-calendar" icon-color="warning">
            <x-slot name="heading">2. Olay Bilgileri</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                <div>
                    <label style="{{ $lbl }}">Olay Tarihi <span style="color:#ef4444">*</span></label>
                    <input type="date" wire:model="olayTarihi" style="{{ $inp }}">
                </div>
                <div>
                    <label style="{{ $lbl }}">Olay Saati</label>
                    <input type="time" wire:model="olaySaati" style="{{ $inp }}">
                </div>
                <div>
                    <label style="{{ $lbl }}">Olay Yeri</label>
                    <input type="text" wire:model="olayYeri" placeholder="Örn: Montaj hattı, 2. kat merdiven" style="{{ $inp }}">
                </div>
                <div>
                    <label style="{{ $lbl }}">Olayı Bildiren</label>
                    <input type="text" wire:model="bildirenAdSoyad" style="{{ $inp }}">
                </div>
                <div>
                    <label style="{{ $lbl }}">Bildirim Tarihi</label>
                    <input type="date" wire:model="bildirimTarihi" style="{{ $inp }}">
                </div>
                <div>
                    <label style="{{ $lbl }}">Etkilenen Çalışan (Hızlı Seç)</label>
                    <select wire:model.live="etkilenenHizliSecId" style="{{ $inp }}">
                        <option value="">Yok / manuel gir</option>
                        @foreach ($this->calisanlar as $c)
                            <option value="{{ $c->id }}">{{ $c->ad_soyad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="{{ $lbl }}">Etkilenen Kişi (Ad Soyad)</label>
                    <input type="text" wire:model="etkilenenAdSoyad" style="{{ $inp }}">
                </div>
                <div>
                    <label style="{{ $lbl }}">Etkilenen Kişi Görevi</label>
                    <input type="text" wire:model="etkilenenGorev" style="{{ $inp }}">
                </div>
            </div>

            <div style="margin-top:1rem">
                <label style="{{ $lbl }}">Olay Özeti (Ne oldu? Nasıl fark edildi?) <span style="color:#ef4444">*</span></label>
                <textarea wire:model="olayOzeti" rows="3" style="{{ $inp }}"></textarea>
            </div>
        </x-filament::section>

        {{-- 3. SINIFLANDIRMA & POTANSİYEL RİSK --}}
        <x-filament::section icon="heroicon-o-adjustments-horizontal" icon-color="warning">
            <x-slot name="heading">3. Sınıflandırma & Potansiyel Risk</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
                <div>
                    <label style="{{ $lbl }}">Sonuç Türü</label>
                    <select wire:model="sonucTuru" style="{{ $inp }}">
                        @foreach ($this->sonucTurleri as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="{{ $lbl }}">Olabilecek En Kötü Sonuç — Olasılık</label>
                    <select wire:model.live="olasilik" style="{{ $inp }}">
                        <option value="">— Seçin —</option>
                        @foreach ($this->olasiliklar as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="{{ $lbl }}">Olabilecek En Kötü Sonuç — Şiddet</label>
                    <select wire:model.live="siddet" style="{{ $inp }}">
                        <option value="">— Seçin —</option>
                        @foreach ($this->siddetler as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($this->potansiyelOnizleme)
                <div style="margin-top:1rem;display:inline-block;{{ $kutu }};padding:.55rem .9rem;background:rgb(124 58 237 / .08);border-color:rgb(124 58 237 / .35);font-size:.85rem">
                    Potansiyel Risk Skoru: <strong>{{ $this->potansiyelOnizleme['skor'] }}</strong> / 25 —
                    <strong>{{ $this->potansiyelOnizleme['seviye'] }}</strong>
                </div>
            @endif

            <div style="{{ $lbl }};margin:1rem 0 .4rem">Etkilenen Unsurlar (birden çok seçilebilir)</div>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap">
                @foreach ($this->etkilenenSecenekleri as $anahtar => $ad)
                    <label style="display:flex;align-items:center;gap:.3rem;font-size:.8rem;cursor:pointer">
                        <input type="checkbox" wire:model="etkilenenKategorileri" value="{{ $anahtar }}"> {{ $ad }}
                    </label>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 4. 5N KÖK NEDEN ANALİZİ --}}
        <x-filament::section icon="heroicon-o-magnifying-glass" icon-color="warning">
            <x-slot name="heading">4. Kök Neden Analizi — 5 Neden (5N)</x-slot>

            <p style="font-size:.8rem;color:rgb(107 114 128);margin-bottom:.75rem">
                Her adımda bir önceki cevaba "neden?" diye sorun. Kök nedene ulaşana kadar
                doldurun (5 adımın tamamı zorunlu değildir).
            </p>

            @foreach (range(0, 4) as $i)
                <div style="margin-bottom:.6rem">
                    <label style="{{ $lbl }}">{{ $i + 1 }}. Neden{{ $i === 0 ? ' (Olay neden oldu?)' : '' }}</label>
                    <input type="text" wire:model="besNeden.{{ $i }}" style="{{ $inp }}">
                </div>
            @endforeach

            <div style="{{ $lbl }};margin:1rem 0 .4rem">Kök Neden Kategorileri (birden çok seçilebilir)</div>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem">
                @foreach ($this->kokNedenSecenekleri as $anahtar => $ad)
                    <label style="display:flex;align-items:center;gap:.3rem;font-size:.8rem;cursor:pointer">
                        <input type="checkbox" wire:model="kokNedenKategorileri" value="{{ $anahtar }}"> {{ $ad }}
                    </label>
                @endforeach
            </div>

            <label style="{{ $lbl }}">Belirlenen Kök Neden (Özet)</label>
            <textarea wire:model="kokNeden" rows="2" style="{{ $inp }}"></textarea>
        </x-filament::section>

        {{-- 5. DÜZELTİCİ FAALİYET --}}
        <x-filament::section icon="heroicon-o-wrench" icon-color="warning">
            <x-slot name="heading">5. Düzeltici / Önleyici Faaliyet</x-slot>

            <textarea wire:model="duzelticiFaaliyet" rows="3" placeholder="Tekrarını önlemek için yapılacaklar…" style="{{ $inp }}"></textarea>
            <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:.4rem">
                Kaydı oluşturduktan sonra geçmiş listeden <strong>"DÖF'e Aktar"</strong> ile bu faaliyeti
                DÖF Oluştur ekranına taşıyabilirsiniz.
            </p>
        </x-filament::section>

        {{-- 6. İŞ KAZASI EK ALANLARI --}}
        @if (in_array($olayTipi, ['is_kazasi', 'meslek_hastaligi_supheli']))
            <x-filament::section icon="heroicon-o-exclamation-circle" icon-color="danger">
                <x-slot name="heading">6. İş Kazası / Meslek Hastalığı Bildirimi</x-slot>

                <div style="{{ $kutu }};background:rgb(220 38 38 / .06);border-color:rgb(220 38 38 / .3);font-size:.78rem;color:#991b1b;margin-bottom:1rem">
                    İş kazası, kazadan sonraki <strong>3 iş günü içinde</strong> SGK'ya bildirilmelidir (6331 m.14).
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                    <div>
                        <label style="{{ $lbl }}">Kayıp Gün Sayısı</label>
                        <input type="number" min="0" wire:model="kayipGunSayisi" style="{{ $inp }}">
                    </div>
                </div>

                <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer;margin:.9rem 0 .5rem">
                    <input type="checkbox" wire:model.live="sgkBildirimiYapildi"> SGK bildirimi yapıldı
                </label>
                @if ($sgkBildirimiYapildi)
                    <div style="max-width:16rem">
                        <label style="{{ $lbl }}">SGK Bildirim Tarihi</label>
                        <input type="date" wire:model="sgkBildirimTarihi" style="{{ $inp }}">
                    </div>
                @endif

                <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer;margin-top:.75rem">
                    <input type="checkbox" wire:model="kollukBildirimiYapildi"> Kolluğa (ölümlü/ağır kaza) bildirim yapıldı
                </label>
            </x-filament::section>
        @endif

        {{-- 7. TANIKLAR --}}
        <x-filament::section icon="heroicon-o-users" icon-color="gray">
            <x-slot name="heading">7. Tanıklar</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr)) auto;gap:.5rem;align-items:end;margin-bottom:.5rem">
                <input type="text" wire:model="yeniTanikAd" placeholder="Ad Soyad"
                    style="width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <input type="text" wire:model="yeniTanikGorev" placeholder="Görevi"
                    style="width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <x-filament::button size="sm" wire:click="tanikEkle">Ekle</x-filament::button>
            </div>

            @if ($taniklar)
                <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                    @foreach ($taniklar as $i => $t)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $t['ad_soyad'] }}</td>
                            <td style="padding:.3rem .5rem">{{ $t['gorev'] ?: '—' }}</td>
                            <td style="padding:.3rem .5rem;text-align:right">
                                <button type="button" wire:click="tanikSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 8. FOTOĞRAFLAR & HAZIRLAYAN --}}
        <x-filament::section icon="heroicon-o-camera" icon-color="gray">
            <x-slot name="heading">8. Fotoğraflar ({{ count($yeniFotograflar) }}) & Hazırlayan</x-slot>

            @if ($yeniFotograflar)
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem">
                    @foreach ($yeniFotograflar as $i => $dosya)
                        <div style="position:relative">
                            <img src="{{ $dosya->temporaryUrl() }}" style="width:70px;height:70px;object-fit:cover;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3)">
                            <button type="button" wire:click="fotoSil({{ $i }})"
                                style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:18px;height:18px;font-size:.7rem;cursor:pointer;line-height:1">✕</button>
                        </div>
                    @endforeach
                </div>
            @endif

            <input type="file" wire:model="yeniFotograflar" multiple accept="image/*" style="font-size:.82rem">

            <label style="{{ $lbl }};margin-top:1rem;display:block">Kaydı Hazırlayan (İSG Uzmanı)</label>
            <input type="text" wire:model="raporHazirlayan" style="{{ $inp }};max-width:24rem">
        </x-filament::section>

        {{-- GEÇMİŞ KAYITLAR --}}
        <x-filament::section icon="heroicon-o-clock" icon-color="gray">
            <x-slot name="heading">Olay Kayıt Defteri</x-slot>

            <div style="margin-bottom:.75rem;max-width:18rem">
                <label style="{{ $lbl }}">Tipe Göre Filtrele</label>
                <select wire:model.live="tipFiltre" style="{{ $inp }}">
                    <option value="">Tümü</option>
                    @foreach ($this->tipler as $anahtar => $ad)
                        <option value="{{ $anahtar }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>

            @if ($this->gecmisKayitlar->isNotEmpty())
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:720px">
                        <tr>
                            <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Belge No</th>
                            <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tip</th>
                            <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tarih</th>
                            <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Potansiyel</th>
                            <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">DÖF</th>
                            <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                        </tr>
                        @foreach ($this->gecmisKayitlar as $k)
                            <tr>
                                <td style="padding:.35rem .5rem">{{ $k->belge_no }}</td>
                                <td style="padding:.35rem .5rem">{{ $k->tipEtiketi() }}</td>
                                <td style="padding:.35rem .5rem">{{ $k->olay_tarihi?->format('d.m.Y') }}</td>
                                <td style="padding:.35rem .5rem">{{ $k->potansiyel_skor ?? '—' }} / {{ $k->potansiyelSeviye() }}</td>
                                <td style="padding:.35rem .5rem">{{ $k->dofRaporu?->belge_no ?? '—' }}</td>
                                <td style="padding:.35rem .5rem;text-align:right;white-space:nowrap">
                                    <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $k->id }})">PDF</x-filament::button>
                                    <x-filament::button size="xs" color="warning" wire:click="dofeAktar({{ $k->id }})">DÖF'e Aktar</x-filament::button>
                                    <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $k->id }})">Sil</x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @else
                <p style="font-size:.83rem;color:rgb(107 114 128)">Bu firmada henüz olay kaydı yok.</p>
            @endif
        </x-filament::section>
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
