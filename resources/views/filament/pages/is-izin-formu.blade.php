@php
    $turuncu = 'rgb(245 158 11)';
    $yesil = 'rgb(16 185 129)';
    $mor = 'rgb(139 92 246)';
    $inp = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $lbl = 'font-weight:600;font-size:.82rem';

    $durumRenk = [
        'taslak' => '#6b7280', 'onay_bekliyor' => '#f59e0b', 'onaylandi' => '#10b981',
        'reddedildi' => '#ef4444', 'is_tamamlandi' => '#3b82f6', 'kapatildi' => '#6b7280', 'iptal' => '#ef4444',
    ];
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Yüksek riskli çalışmalar için iş izni (Permit to Work) düzenleyin. Kütüphaneden hazır bir
        izin türü seçip önlem/KKD listesini otomatik doldurabilir; düzenledikten sonra izni onaya
        gönderip <strong>onaylandı → iş tamamlandı → kapatıldı</strong> yaşam döngüsünü takip edebilirsiniz.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'calisma_izin_formu'])

    {{-- 0. KÜTÜPHANE --}}
    <x-filament::section icon="heroicon-o-book-open" icon-color="primary">
        <x-slot name="heading">İzin Kütüphanesi</x-slot>
        <x-slot name="description">Hazır bir izin türü seçin — türler, önlemler, KKD, geçerlilik ve uyarılar otomatik dolar.</x-slot>

        <select wire:model.live="sablonSecim" style="{{ $inp }};max-width:34rem">
            <option value="">— Kütüphaneden seç (opsiyonel) —</option>
            <optgroup label="Hazır Katalog">
                @foreach ($this->sablonlar as $s)
                    @if ($s['kaynak'] === 'hazir')
                        <option value="hazir:{{ $s['anahtar'] }}">{{ $s['ad'] }}</option>
                    @endif
                @endforeach
            </optgroup>
            @if (collect($this->sablonlar)->firstWhere('kaynak', 'ozel'))
                <optgroup label="Kendi Şablonlarım">
                    @foreach ($this->sablonlar as $s)
                        @if ($s['kaynak'] === 'ozel')
                            <option value="ozel:{{ $s['anahtar'] }}">{{ $s['ad'] }}</option>
                        @endif
                    @endforeach
                </optgroup>
            @endif
        </select>

        @if (collect($this->sablonlar)->firstWhere('kaynak', 'ozel'))
            <div style="margin-top:.7rem;display:flex;gap:.4rem;flex-wrap:wrap">
                @foreach ($this->sablonlar as $s)
                    @if ($s['kaynak'] === 'ozel')
                        <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;border:1px solid rgb(107 114 128 / .3);border-radius:.4rem;padding:.15rem .5rem">
                            {{ $s['ad'] }}
                            <button type="button" wire:click="sablonSil({{ $s['anahtar'] }})" style="color:#ef4444;background:none;border:none;cursor:pointer">✕</button>
                        </span>
                    @endif
                @endforeach
            </div>
        @endif
    </x-filament::section>

    {{-- 1. İŞ TANIMI & LOKASYON --}}
    <x-filament::section icon="heroicon-o-key" icon-color="warning">
        <x-slot name="heading">1. İş Tanımı ve Lokasyon</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
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
                <label style="{{ $lbl }}">Çalışma Alanı / Lokasyon</label>
                <input type="text" wire:model="calismaAlani" placeholder="Örn: Kazan Dairesi, Çatı Katı" style="{{ $inp }}">
            </div>
            <div>
                <label style="{{ $lbl }}">Başlangıç Zamanı</label>
                <input type="datetime-local" wire:model="baslangic" style="{{ $inp }}">
            </div>
            <div>
                <label style="{{ $lbl }}">Bitiş Zamanı</label>
                <input type="datetime-local" wire:model="bitis" style="{{ $inp }}">
            </div>
            <div>
                <label style="{{ $lbl }}">Geçerlilik (saat)</label>
                <input type="number" min="1" max="72" wire:model="gecerlilikSaat" placeholder="Örn: 8" style="{{ $inp }}">
                <p style="font-size:.72rem;color:rgb(107 114 128);margin-top:.2rem">Bitiş boşsa başlangıç + bu süre alınır.</p>
            </div>
        </div>
        <div style="margin-top:1rem">
            <label style="{{ $lbl }}">Yapılacak İşin Detayı</label>
            <textarea wire:model="isDetayi" rows="2" placeholder="Yapılacak işi detaylıca açıklayınız..." style="{{ $inp }};font-family:inherit;font-size:.85rem"></textarea>
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. İZİN TÜRÜ --}}
        <x-filament::section icon="heroicon-o-fire" icon-color="warning">
            <x-slot name="heading">2. İzin Türü (Birden Fazla Seçilebilir)</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.5rem">
                @foreach ($this->turler as $anahtar => $ad)
                    @php $secili = in_array($anahtar, $izinTurleri, true); @endphp
                    <button type="button" wire:click="izinTuruToggle('{{ $anahtar }}')"
                        style="padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.82rem;text-align:center;
                            border:2px solid {{ $secili ? $turuncu : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(245 158 11 / .1)' : 'transparent' }}">
                        {{ $ad }}
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 3. GÜVENLİK ÖNLEMLERİ --}}
        <x-filament::section icon="heroicon-o-shield-check" icon-color="success">
            <x-slot name="heading">3. Güvenlik Önlemleri Kontrol Listesi</x-slot>
            <x-slot name="description">Aşağıdaki önlemlerin alındığını doğrulayınız</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.4rem">
                @foreach ($this->onlemler as $madde)
                    @php $secili = in_array($madde, $secilenOnlemler, true); @endphp
                    <button type="button" wire:click="onlemToggle('{{ addslashes($madde) }}')"
                        style="text-align:left;padding:.5rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                            border:1px solid {{ $secili ? $yesil : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(16 185 129 / .08)' : 'transparent' }}">
                        {{ $secili ? '☑' : '☐' }} {{ $madde }}
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 4. KKD --}}
        <x-filament::section icon="heroicon-o-shield-exclamation" icon-color="primary">
            <x-slot name="heading">4. Gerekli Kişisel Koruyucu Donanımlar</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.5rem">
                @foreach ($this->kkdSecenekleri as $kkd)
                    @php $secili = in_array($kkd, $secilenKkdler, true); @endphp
                    <button type="button" wire:click="kkdToggle('{{ $kkd }}')"
                        style="padding:.5rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;text-align:center;
                            border:2px solid {{ $secili ? $mor : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(139 92 246 / .1)' : 'transparent' }}">
                        {{ $kkd }}
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 4b. UYARILAR & ÖZEL KOŞULLAR --}}
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
            <x-slot name="heading">Uyarılar ve Özel Koşullar</x-slot>

            @if ($uyarilar)
                <div style="display:flex;flex-direction:column;gap:.35rem;margin-bottom:.75rem">
                    @foreach ($uyarilar as $i => $u)
                        <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;background:rgb(239 68 68 / .06);border:1px solid rgb(239 68 68 / .25);border-radius:.4rem;padding:.4rem .6rem">
                            <span style="flex:1">⚠ {{ $u }}</span>
                            <button type="button" wire:click="uyariSil({{ $i }})" style="color:#ef4444;background:none;border:none;cursor:pointer">✕</button>
                        </div>
                    @endforeach
                </div>
            @endif

            <label style="{{ $lbl }}">Özel Koşullar / Ek Notlar</label>
            <textarea wire:model="ozelKosullar" rows="2" placeholder="Örn: Gaz ölçümü her 2 saatte tekrarlanacak; çalışma yalnızca gündüz vardiyasında." style="{{ $inp }};font-family:inherit;font-size:.85rem"></textarea>
        </x-filament::section>

        {{-- 5. ONAYCILAR --}}
        <x-filament::section icon="heroicon-o-pencil-square" icon-color="danger">
            <x-slot name="heading">5. Onaycılar</x-slot>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label style="{{ $lbl }}">1. Onaycı — Başlık</label>
                    <input type="text" wire:model="onay1Baslik" placeholder="Saha Sorumlusu / Formen" style="{{ $inp }};margin-bottom:.4rem">
                    <label style="{{ $lbl }}">Ad Soyad</label>
                    <input type="text" wire:model="onay1Ad" placeholder="Ad Soyad" style="{{ $inp }}">
                </div>
                <div>
                    <label style="{{ $lbl }}">2. Onaycı — Başlık</label>
                    <input type="text" wire:model="onay2Baslik" placeholder="İSG Uzmanı / İşveren Vekili" style="{{ $inp }};margin-bottom:.4rem">
                    <label style="{{ $lbl }}">Ad Soyad</label>
                    <input type="text" wire:model="onay2Ad" placeholder="Ad Soyad" style="{{ $inp }}">
                </div>
            </div>
            <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:.6rem">
                Form kaydedildikten sonra aşağıdaki "Geçmiş İş İzinleri" listesinden
                <strong>Onaya Gönder → 1. Onaycı Onayla / 2. Onaycı Onayla</strong> adımlarını yürütün.
                İki onay tamamlanınca izin <strong>Onaylandı</strong> olur ve çalışma yetkisi verilmiş sayılır.
            </p>
        </x-filament::section>

        {{-- 6. GEÇMİŞ İZİNLER --}}
        @if ($this->gecmisFormlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş İş İzinleri</x-slot>

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:760px">
                        <tr>
                            <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">İzin No / Alan</th>
                            <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Durum</th>
                            <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Onaylar</th>
                            <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Geçerlilik</th>
                            <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                        </tr>
                        @foreach ($this->gecmisFormlar as $f)
                            <tr style="border-bottom:1px solid rgb(107 114 128 / .12)">
                                <td style="padding:.4rem .5rem">
                                    <strong>{{ $f->izin_no }}</strong><br>
                                    <span style="color:rgb(107 114 128)">{{ $f->calisma_alani ?: 'Alan belirtilmedi' }}</span>
                                </td>
                                <td style="padding:.4rem .5rem;white-space:nowrap">
                                    <span style="display:inline-block;padding:.1rem .5rem;border-radius:.4rem;font-size:.72rem;color:#fff;background:{{ $durumRenk[$f->durum] ?? '#6b7280' }}">
                                        {{ $f->durumEtiketi() }}
                                    </span>
                                    @if ($f->suresiGectiMi())
                                        <br><span style="font-size:.7rem;color:#ef4444">süre doldu</span>
                                    @endif
                                </td>
                                <td style="padding:.4rem .5rem;font-size:.74rem">
                                    @if ($f->durum === 'taslak')
                                        —
                                    @else
                                        1: {{ $f->onay1Etiketi() }}{{ $f->onay1_tarih ? ' ('.$f->onay1_tarih->format('d.m H:i').')' : '' }}<br>
                                        2: {{ $f->onay2Etiketi() }}{{ $f->onay2_tarih ? ' ('.$f->onay2_tarih->format('d.m H:i').')' : '' }}
                                        @if ($f->red_gerekcesi)
                                            <br><span style="color:#ef4444">Ret: {{ $f->red_gerekcesi }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td style="padding:.4rem .5rem;font-size:.74rem;white-space:nowrap">
                                    {{ $f->baslangic?->format('d.m.Y H:i') ?: '—' }}<br>{{ $f->bitis?->format('d.m.Y H:i') ?: '—' }}
                                </td>
                                <td style="padding:.4rem .5rem;text-align:right;white-space:nowrap">
                                    @if ($f->durum === 'taslak')
                                        <x-filament::button size="xs" color="warning" wire:click="onayaGonder({{ $f->id }})">Onaya Gönder</x-filament::button>
                                    @elseif ($f->durum === 'onay_bekliyor')
                                        @if ($f->onay1_durum !== 'onayladi')
                                            <x-filament::button size="xs" color="success" wire:click="onayla({{ $f->id }}, 1)">1. Onaycı</x-filament::button>
                                        @endif
                                        @if ($f->onay2_durum !== 'onayladi')
                                            <x-filament::button size="xs" color="success" wire:click="onayla({{ $f->id }}, 2)">2. Onaycı</x-filament::button>
                                        @endif
                                        <x-filament::button size="xs" color="danger" wire:click="islemBaslat({{ $f->id }}, 'reddet')">Reddet</x-filament::button>
                                    @elseif ($f->durum === 'onaylandi')
                                        <x-filament::button size="xs" color="info" wire:click="isiTamamla({{ $f->id }})">İş Tamamlandı</x-filament::button>
                                        <x-filament::button size="xs" color="gray" wire:click="islemBaslat({{ $f->id }}, 'kapat')">Kapat</x-filament::button>
                                    @elseif ($f->durum === 'is_tamamlandi')
                                        <x-filament::button size="xs" color="gray" wire:click="islemBaslat({{ $f->id }}, 'kapat')">Kapat (Saha Teslim)</x-filament::button>
                                    @endif

                                    <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $f->id }})">PDF</x-filament::button>
                                    @if (! in_array($f->durum, ['kapatildi', 'iptal']))
                                        <x-filament::button size="xs" color="danger" wire:click="iptalEt({{ $f->id }})">İptal</x-filament::button>
                                    @endif
                                    <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $f->id }})">Sil</x-filament::button>
                                </td>
                            </tr>

                            @if ($islemId === $f->id)
                                <tr>
                                    <td colspan="5" style="padding:.6rem .5rem;background:rgb(107 114 128 / .06)">
                                        @if ($islemTuru === 'reddet')
                                            <label style="{{ $lbl }}">Ret Gerekçesi <span style="color:#ef4444">*</span></label>
                                            <textarea wire:model="islemNotu" rows="2" style="{{ $inp }};font-family:inherit;font-size:.82rem"></textarea>
                                        @else
                                            <label style="{{ $lbl }}">Kapanış Notu</label>
                                            <textarea wire:model="islemNotu" rows="2" style="{{ $inp }};font-family:inherit;font-size:.82rem"></textarea>
                                            <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer;margin-top:.5rem">
                                                <input type="checkbox" wire:model="islemSahaTeslim"> Saha temiz/güvenli teslim alındı
                                            </label>
                                        @endif
                                        <div style="margin-top:.6rem;display:flex;gap:.4rem">
                                            <x-filament::button size="xs" wire:click="islemiUygula">Uygula</x-filament::button>
                                            <x-filament::button size="xs" color="gray" wire:click="islemIptal">Vazgeç</x-filament::button>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
            </x-filament::section>
        @endif
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
