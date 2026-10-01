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

    {{-- ÖZET KARTLARI — seçili firma, yoksa tüm firmalar --}}
    @php $oz = $this->ozet; @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem">
        @foreach ([
            ['Toplam izin', $oz['toplam'], '#0f766e'],
            ['Aktif izin', $oz['aktif'], '#10b981'],
            ['Onay bekleyen', $oz['onay_bekleyen'], '#f59e0b'],
            ['Süresi geçen', $oz['suresi_gecen'], '#ef4444'],
        ] as [$etiket, $sayi, $renk])
            <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.75rem;padding:.8rem 1rem;border-left:4px solid {{ $renk }}">
                <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $etiket }}</div>
                <div style="font-size:1.6rem;font-weight:700;color:{{ $renk }}">{{ $sayi }}</div>
            </div>
        @endforeach
    </div>
    <p style="font-size:.72rem;color:rgb(107 114 128);margin-top:-.6rem">
        {{ $this->firma ? $this->firma->unvan.' için' : 'Tüm firmalarınız için' }} — aktif izin: onaylanmış ve geçerlilik süresi içinde.
    </p>

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

        {{-- 2b. ÇALIŞANLAR, TAŞERON, SAHA DENETİMİ --}}
        <x-filament::section icon="heroicon-o-users" icon-color="info">
            <x-slot name="heading">İzinde Çalışacak Personel</x-slot>
            <x-slot name="description">Firmanın aktif çalışanlarından seçin — ad ve görev izne kaydedilir.</x-slot>

            @if ($this->firmaCalisanlari->isEmpty())
                <p style="font-size:.82rem;color:rgb(107 114 128)">
                    Bu firmada kayıtlı aktif çalışan yok. Firma → Çalışanlar sekmesinden ekleyebilirsiniz.
                </p>
            @else
                <div x-data="{ ara: '' }">
                    <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap">
                        <input type="search" x-model="ara" placeholder="Çalışan ara..." style="{{ $inp }};max-width:18rem;margin-top:0">
                        <span style="font-size:.8rem;color:rgb(107 114 128)">{{ count($secilenCalisanlar) }} kişi seçildi</span>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.4rem;margin-top:.6rem;max-height:16rem;overflow-y:auto">
                        @foreach ($this->firmaCalisanlari as $c)
                            @php $secili = in_array($c->id, $secilenCalisanlar, true); @endphp
                            <button type="button" wire:click="calisanToggle({{ $c->id }})"
                                x-show="ara === '' || '{{ addslashes(mb_strtolower(strtr($c->ad_soyad.' '.$c->gorev, ['İ' => 'i', 'I' => 'ı']))) }}'.includes(ara.toLocaleLowerCase('tr'))"
                                style="text-align:left;padding:.45rem .65rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                                    border:1px solid {{ $secili ? '#0ea5e9' : 'rgb(107 114 128 / .3)' }};
                                    background:{{ $secili ? 'rgb(14 165 233 / .08)' : 'transparent' }}">
                                {{ $secili ? '☑' : '☐' }} {{ $c->ad_soyad }}
                                @if ($c->gorev)
                                    <span style="color:rgb(107 114 128)">· {{ $c->gorev }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-top:1rem">
                <div>
                    <label style="{{ $lbl }}">Taşeron (varsa)</label>
                    <input type="text" wire:model="taseron" placeholder="Taşeron firma adı — yoksa boş bırakın" style="{{ $inp }}">
                </div>
                <div>
                    <label style="{{ $lbl }}">Bağlı Saha Denetimi</label>
                    <select wire:model="sahaDenetimiId" style="{{ $inp }}">
                        <option value="">Bağlantı yok</option>
                        @foreach ($this->sahaDenetimleri as $id => $ad)
                            <option value="{{ $id }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
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

        {{-- 4a. PTW OPERASYON KONTROLLERİ --}}
        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="success">
            <x-slot name="heading">PTW Operasyon Kontrolleri</x-slot>
            <x-slot name="description">İzin türüne göre ilgisiz kontroller "Gerekli değil" gelir. Bekleyen veya uygun olmayan kontrol varken izin tam onaylanamaz.</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.8rem">
                @foreach (config('isg.is_izin.operasyon_kontrolleri') as $anahtar => $k)
                    <div>
                        <label style="{{ $lbl }}">{{ $k['ad'] }}</label>
                        <select wire:model="kontroller.{{ $anahtar }}" style="{{ $inp }}">
                            @foreach (config('isg.is_izin.kontrol_durumlari') as $d => $etiket)
                                <option value="{{ $d }}">{{ $etiket }}</option>
                            @endforeach
                        </select>
                    </div>
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
                                    @if ($f->calisanlar || $f->taseron)
                                        <br><span style="font-size:.72rem;color:rgb(107 114 128)">
                                            {{ $f->calisanlar ? count($f->calisanlar).' çalışan' : '' }}{{ $f->calisanlar && $f->taseron ? ' · ' : '' }}{{ $f->taseron ? 'Taşeron: '.$f->taseron : '' }}
                                        </span>
                                    @endif
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
                                    @if ($f->kontroller !== null)
                                        @php
                                            $uygunDegil = $f->kontrolAdlari('uygun_degil');
                                            $bekleyen = $f->kontrolAdlari('bekliyor');
                                        @endphp
                                        <br>
                                        @if ($uygunDegil)
                                            <span style="color:#ef4444">✕ Uygun değil: {{ implode(', ', $uygunDegil) }}</span>
                                        @elseif ($bekleyen)
                                            <span style="color:#f59e0b">⏳ {{ count($bekleyen) }} kontrol bekliyor</span>
                                        @else
                                            <span style="color:#10b981">✓ Kontroller tamam</span>
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

                                    @if (! in_array($f->durum, ['kapatildi', 'iptal']))
                                        <x-filament::button size="xs" color="success" outlined wire:click="islemBaslat({{ $f->id }}, 'kontroller')">Kontroller</x-filament::button>
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
                                        @if ($islemTuru === 'kontroller')
                                            <strong style="font-size:.82rem">PTW operasyon kontrolleri: {{ $f->izin_no }}</strong>
                                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.6rem;margin-top:.5rem">
                                                @foreach (config('isg.is_izin.operasyon_kontrolleri') as $anahtar => $k)
                                                    <div>
                                                        <label style="{{ $lbl }}">{{ $k['ad'] }}</label>
                                                        <select wire:model="islemKontroller.{{ $anahtar }}" style="{{ $inp }}">
                                                            @foreach (config('isg.is_izin.kontrol_durumlari') as $d => $etiket)
                                                                <option value="{{ $d }}">{{ $etiket }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif ($islemTuru === 'reddet')
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
                                            <x-filament::button size="xs" wire:click="islemiUygula">{{ $islemTuru === 'kontroller' ? 'Kontrolleri Kaydet' : 'Uygula' }}</x-filament::button>
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
