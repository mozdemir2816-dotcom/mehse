@php
    $kirmizi = 'rgb(220 38 38)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Kazazede ve kaza bilgilerini girin, kök neden analizini ve alınan önlemleri kaydedin;
        "Raporu Oluştur" ile 6331 sayılı Kanun kapsamındaki kaza inceleme raporu PDF'ini indirin.
    </p>

    <div style="{{ $kutu }};background:rgb(220 38 38 / .06);border-color:rgb(220 38 38 / .3);font-size:.78rem;color:#991b1b">
        6331 Sayılı Kanun m.14 uyarınca iş kazası, kazadan sonraki <strong>3 iş günü içinde</strong> SGK'ya bildirilmelidir.
    </div>

    {{-- 1. FİRMA & KAZAZEDE --}}
    <x-filament::section icon="heroicon-o-user" icon-color="danger">
        <x-slot name="heading">1. Firma & Kazazede</x-slot>

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
            @if ($this->firma)
                <div>
                    <label style="font-weight:600;font-size:.82rem">Hızlı Çalışan Seç</label>
                    <select wire:model.live="kazazedeHizliSecId"
                        style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <option value="">Firmaya kayıtlı çalışan yok / manuel gir</option>
                        @foreach ($this->calisanlar as $c)
                            <option value="{{ $c->id }}">{{ $c->ad_soyad }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label style="font-weight:600;font-size:.82rem">Kazazede Ad Soyad <span style="color:#ef4444">*</span></label>
                <input type="text" wire:model="kazazedeAdSoyad"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">T.C. Kimlik No</label>
                <input type="text" wire:model="kazazedeTc"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Görevi</label>
                <input type="text" wire:model="kazazedeGorev"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. KAZA BİLGİLERİ --}}
        <x-filament::section icon="heroicon-o-calendar" icon-color="danger">
            <x-slot name="heading">2. Kaza Bilgileri</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.82rem">Kaza Tarihi <span style="color:#ef4444">*</span></label>
                    <input type="date" wire:model="kazaTarihi"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Kaza Saati</label>
                    <input type="time" wire:model="kazaSaati"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Kaza Yeri</label>
                    <input type="text" wire:model="kazaYeri" placeholder="Örn: Üretim sahası, Depo"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Kaza Türü</label>
                    <select wire:model="kazaTuru"
                        style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <option value="">— Seçin —</option>
                        @foreach ($this->kazaTurleri as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Ağırlık Derecesi</label>
                    <select wire:model="agirlikDerecesi"
                        style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <option value="">— Seçin —</option>
                        @foreach ($this->agirlikDereceleri as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;font-size:.82rem">Kayıp Gün Sayısı</label>
                    <input type="number" min="0" wire:model="kayipGunSayisi"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
            </div>

            <div style="margin-top:1rem">
                <label style="font-weight:600;font-size:.82rem">Kaza Tanımı (Ne oldu?) <span style="color:#ef4444">*</span></label>
                <textarea wire:model="kazaTanimi" rows="2"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent"></textarea>
            </div>
            <div style="margin-top:1rem">
                <label style="font-weight:600;font-size:.82rem">Kaza Nasıl Oldu?</label>
                <textarea wire:model="kazaNasilOldu" rows="2"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent"></textarea>
            </div>
        </x-filament::section>

        {{-- 3. KÖK NEDEN ANALİZİ --}}
        <x-filament::section icon="heroicon-o-magnifying-glass" icon-color="danger">
            <x-slot name="heading">3. Kök Neden Analizi</x-slot>

            <div style="font-weight:600;font-size:.82rem;margin-bottom:.4rem">Kök Neden Kategorileri (birden çok seçilebilir)</div>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem">
                @foreach ($this->kokNedenSecenekleri as $anahtar => $ad)
                    <label style="display:flex;align-items:center;gap:.3rem;font-size:.8rem;cursor:pointer">
                        <input type="checkbox" wire:model="kokNedenKategorileri" value="{{ $anahtar }}"> {{ $ad }}
                    </label>
                @endforeach
            </div>

            <label style="font-weight:600;font-size:.82rem">Kök Neden Açıklaması</label>
            <textarea wire:model="kazaNedeni" rows="2" placeholder="Neden oldu? (kök neden detayı)"
                style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent"></textarea>

            <label style="font-weight:600;font-size:.82rem;margin-top:1rem;display:block">Alınan / Alınacak Önlemler</label>
            <textarea wire:model="alinanOnlemler" rows="2"
                style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent"></textarea>
        </x-filament::section>

        {{-- 4. TANIKLAR --}}
        <x-filament::section icon="heroicon-o-users" icon-color="danger">
            <x-slot name="heading">4. Tanıklar</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr)) auto;gap:.5rem;align-items:end;margin-bottom:.5rem">
                <div>
                    <input type="text" wire:model="yeniTanikAd" placeholder="Ad Soyad"
                        style="width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <input type="text" wire:model="yeniTanikGorev" placeholder="Görevi"
                        style="width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
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

        {{-- 5. SGK BİLDİRİMİ & HAZIRLAYAN --}}
        <x-filament::section icon="heroicon-o-document-check" icon-color="danger">
            <x-slot name="heading">5. SGK Bildirimi & Rapor Hazırlayan</x-slot>

            <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer;margin-bottom:.75rem">
                <input type="checkbox" wire:model.live="sgkBildirimiYapildi"> SGK bildirimi yapıldı
            </label>

            @if ($sgkBildirimiYapildi)
                <div style="margin-bottom:.75rem">
                    <label style="font-weight:600;font-size:.82rem">SGK Bildirim Tarihi</label>
                    <input type="date" wire:model="sgkBildirimTarihi"
                        style="margin-top:.3rem;width:100%;max-width:16rem;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                </div>
            @endif

            <label style="font-weight:600;font-size:.82rem">Raporu Hazırlayan (İSG Uzmanı)</label>
            <input type="text" wire:model="raporHazirlayan"
                style="margin-top:.3rem;width:100%;max-width:24rem;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">

            @if (! $this->firma->igu)
                <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:.75rem">
                    Kaşe yok — <a href="{{ \App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource::getUrl() }}" style="color:{{ $kirmizi }};text-decoration:underline">İSG Profesyonelleri</a>
                    panelinden İGU ekleyip Firma düzenleme sayfasından atayın.
                </p>
            @endif
        </x-filament::section>

        {{-- 6. GEÇMİŞ KAYITLAR --}}
        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Kaza Raporları</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Belge No</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Kazazede</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tarih</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Ağırlık</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($this->gecmisKayitlar as $k)
                        <tr>
                            <td style="padding:.35rem .5rem">{{ $k->belge_no }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->kazazede_ad_soyad }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->kaza_tarihi?->format('d.m.Y') }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->agirlikDerecesiEtiketi() }}</td>
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
</x-filament-panels::page>
