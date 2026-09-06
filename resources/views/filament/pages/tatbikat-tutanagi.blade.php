@php
    $kirmizi = 'rgb(239 68 68)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $cevapRenk = ['evet' => '#10b981', 'hayir' => '#ef4444', 'kismen' => '#f59e0b'];
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Acil Durumlar Yönetmeliği md.13 — yılda en az bir tatbikat zorunlu, tutanakla
        denetime kanıttır. Senaryo seçin, tatbikat bilgilerini doldurup PDF alın.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'acil_durum_tatbikat'])

    {{-- 1. FİRMA & SENARYO --}}
    <x-filament::section icon="heroicon-o-fire" icon-color="danger">
        <x-slot name="heading">1. Firma & Tatbikat Senaryosu</x-slot>

        <div style="margin-bottom:1rem">
            <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
            <select wire:model.live="firmaId"
                style="margin-top:.3rem;width:100%;max-width:24rem;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                <option value="">— Firma seçin —</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.5rem">
            @foreach ($this->senaryolar as $anahtar => $s)
                @php $secili = $senaryoAnahtari === $anahtar; @endphp
                <button type="button" wire:click="senaryoSec('{{ $anahtar }}')"
                    style="text-align:center;padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;
                        border:2px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                        background:{{ $secili ? 'rgb(239 68 68 / .08)' : 'transparent' }}">
                    <div style="font-weight:700">{{ $s['ad'] }}</div>
                    <div style="font-size:.7rem;color:rgb(107 114 128)">≈{{ $s['sure_dk'] }} dk</div>
                </button>
            @endforeach
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. FİRMA & TATBİKAT BİLGİLERİ --}}
        <x-filament::section icon="heroicon-o-clipboard-document-list" icon-color="danger">
            <x-slot name="heading">2. Tatbikat Bilgileri</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Tatbikat Tarihi</label>
                    <input type="date" wire:model="tatbikatTarihi"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Tatbikat Yeri</label>
                    <input type="text" wire:model="tatbikatYeri" placeholder="Örn: Üretim sahası ve idari bina"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Başlama Saati</label>
                    <input type="time" wire:model="baslamaSaati"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Bitiş Saati</label>
                    <input type="time" wire:model="bitisSaati"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Tahliye Süresi (dk)</label>
                    <input type="number" wire:model="tahliyeDk" placeholder="Örn: 4"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">İşveren / Vekili</label>
                    <input type="text" wire:model="isverenVekili"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">İş Güvenliği Uzmanı</label>
                    <input type="text" wire:model="isGuvenligiUzmani"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Tatbikat Koordinatörü</label>
                    <input type="text" wire:model="tatbikatKoordinatoru"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Belge Tarihi</label>
                    <input type="date" wire:model="belgeTarihi"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
            </div>
            <div style="margin-top:.75rem;display:flex;gap:1.5rem;flex-wrap:wrap">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer">
                    <input type="checkbox" wire:model="haberliTatbikat"> Haberli tatbikat
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer">
                    <input type="checkbox" wire:model="yillikPlanDahilinde"> Yıllık plan dahilinde
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer">
                    <input type="checkbox" wire:model="isyeriHekimiImzasi"> İşyeri Hekimi imzası ekle
                </label>
            </div>
        </x-filament::section>

        {{-- 3. SENARYO METNİ --}}
        <x-filament::section icon="heroicon-o-document-text" icon-color="danger">
            <x-slot name="heading">3. Senaryo Metni</x-slot>
            <textarea wire:model="senaryoMetni" rows="3" placeholder="Yukarıdan bir senaryo seçin — hazır metin buraya gelir ve düzenlenebilir."
                style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.85rem"></textarea>
        </x-filament::section>

        {{-- 4. GÖREV ALAN EKİPLER --}}
        <x-filament::section icon="heroicon-o-user-group" icon-color="danger">
            <x-slot name="heading">4. Görev Alan Ekipler ({{ count($ekipler) }})</x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="margin-bottom:.75rem">
                    <div style="font-weight:600;font-size:.8rem;margin-bottom:.3rem">Firma Çalışanlarından Hızlı Ekle</div>
                    <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                        @foreach ($this->calisanlar as $c)
                            @foreach ($this->ekipSecenekleri as $ekip)
                                <x-filament::button size="xs" color="gray" wire:click="ekipHizliEkle({{ $c->id }}, '{{ $ekip }}')">+ {{ $c->ad_soyad }} ({{ $ekip }})</x-filament::button>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.5rem;margin-bottom:.5rem">
                <input type="text" wire:model="yeniEkipAd" placeholder="Ad Soyad"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <select wire:model="yeniEkipTipi"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <option value="">Ekip seçin</option>
                    @foreach ($this->ekipSecenekleri as $ekip)
                        <option value="{{ $ekip }}">{{ $ekip }}</option>
                    @endforeach
                </select>
                <x-filament::button size="sm" wire:click="ekipEkle">+ Ekle</x-filament::button>
            </div>

            @if ($ekipler)
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($ekipler as $i => $e)
                        <tr>
                            <td style="padding:.25rem .5rem">{{ $e['ad_soyad'] }} — {{ $e['ekip'] ?: '—' }}</td>
                            <td style="padding:.25rem .5rem;text-align:right">
                                <button type="button" wire:click="ekipSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 5. DEĞERLENDİRME KONTROL LİSTESİ --}}
        <x-filament::section icon="heroicon-o-check-circle" icon-color="danger">
            <x-slot name="heading">5. Değerlendirme Kontrol Listesi</x-slot>

            @foreach ($degerlendirmeler as $i => $d)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;{{ $kutu }};margin-bottom:.4rem;padding:.5rem .7rem">
                    <span style="font-size:.82rem;flex:1">{{ $d['soru'] }}</span>
                    <div style="display:flex;gap:.3rem;flex-shrink:0">
                        @foreach ($this->degerlendirmeSecenekleri as $anahtar => $etiket)
                            @php $secili = $d['cevap'] === $anahtar; @endphp
                            <button type="button" wire:click="degerlendirmeCevapla({{ $i }}, '{{ $anahtar }}')"
                                style="padding:.25rem .6rem;border-radius:.3rem;cursor:pointer;font-size:.75rem;color:#fff;
                                    background:{{ $secili ? $cevapRenk[$anahtar] : 'rgb(107 114 128 / .4)' }}">
                                {{ $etiket }}
                            </button>
                        @endforeach
                        <button type="button" wire:click="degerlendirmeSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                    </div>
                </div>
            @endforeach

            <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem;margin-top:.5rem">
                <input type="text" wire:model="yeniOzelSoru" placeholder="Özel değerlendirme sorusu ekle..."
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <x-filament::button size="sm" wire:click="ozelSoruEkle">+ Ekle</x-filament::button>
            </div>
        </x-filament::section>

        {{-- 6. GÖZLEM VE GENEL DEĞERLENDİRME --}}
        <x-filament::section icon="heroicon-o-eye" icon-color="danger">
            <x-slot name="heading">6. Gözlem ve Genel Değerlendirme</x-slot>
            <textarea wire:model="gozlem" rows="2" placeholder="Tatbikat sırasındaki gözlemler, genel değerlendirme..."
                style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.85rem"></textarea>
        </x-filament::section>

        {{-- 7. TESPİT EDİLEN EKSİKLİKLER --}}
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
            <x-slot name="heading">7. Tespit Edilen Eksiklikler ({{ count($eksiklikler) }})</x-slot>
            <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem;margin-bottom:.5rem">
                <input type="text" wire:model="yeniEksiklik" placeholder="Örn: Alarm sesi depo alanında zayıf duyuldu"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <x-filament::button size="sm" wire:click="eksiklikEkle">+ Ekle</x-filament::button>
            </div>
            @foreach ($eksiklikler as $i => $e)
                <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:.2rem .4rem">
                    <span>{{ $e }}</span>
                    <button type="button" wire:click="eksiklikSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                </div>
            @endforeach
        </x-filament::section>

        {{-- 8. YAPILACAK DÜZENLEMELER (DÖF) --}}
        <x-filament::section icon="heroicon-o-wrench" icon-color="danger">
            <x-slot name="heading">8. Yapılacak Düzenlemeler (DÖF) ({{ count($dofOnerileri) }})</x-slot>
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:.5rem;margin-bottom:.5rem">
                <input type="text" wire:model="yeniDofFaaliyet" placeholder="Faaliyet (örn: Depo alanına ek siren montajı)"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <input type="text" wire:model="yeniDofSorumlu" placeholder="Sorumlu"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <input type="date" wire:model="yeniDofTarih"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <x-filament::button size="sm" wire:click="dofOnerisiEkle">+ Ekle</x-filament::button>
            </div>
            @foreach ($dofOnerileri as $i => $d)
                <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:.2rem .4rem">
                    <span>{{ $d['faaliyet'] }} — {{ $d['sorumlu'] ?: '—' }} — {{ $d['tarih'] ?: '—' }}</span>
                    <button type="button" wire:click="dofOnerisiSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                </div>
            @endforeach
        </x-filament::section>

        {{-- 9. KATILIMCILAR --}}
        <x-filament::section icon="heroicon-o-users" icon-color="danger">
            <x-slot name="heading">9. Firma Çalışanları / Katılımcılar ({{ count($katilimcilar) }})</x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="margin-bottom:.75rem;display:flex;gap:.4rem;flex-wrap:wrap">
                    @foreach ($this->calisanlar as $c)
                        <x-filament::button size="xs" color="gray" wire:click="katilimciHizliEkle({{ $c->id }})">+ {{ $c->ad_soyad }}</x-filament::button>
                    @endforeach
                </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.5rem;margin-bottom:.5rem">
                <input type="text" wire:model="yeniKatilimciAd" placeholder="Ad Soyad"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <input type="text" wire:model="yeniKatilimciTc" placeholder="T.C. Kimlik No"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <input type="text" wire:model="yeniKatilimciGorev" placeholder="Görevi"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <x-filament::button size="sm" wire:click="katilimciEkle">+ Ekle</x-filament::button>
            </div>

            @if ($katilimcilar)
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($katilimcilar as $i => $k)
                        <tr>
                            <td style="padding:.25rem .5rem">{{ $k['ad_soyad'] }}</td>
                            <td style="padding:.25rem .5rem;text-align:right">
                                <button type="button" wire:click="katilimciSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 10. FOTOĞRAFLAR --}}
        <x-filament::section icon="heroicon-o-camera" icon-color="danger">
            <x-slot name="heading">10. Fotoğraflar ({{ count($yeniFotograflar) }})</x-slot>

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
            <p style="font-size:.75rem;color:rgb(107 114 128);margin-top:.3rem">
                Tatbikatın toplanma yeri, ekiplerin müdahalesi vb. fotoğrafları — PDF'in sonuna kanıt sayfası olarak eklenir.
            </p>
        </x-filament::section>

        {{-- 11. GEÇMİŞ TUTANAKLAR --}}
        @if ($this->gecmisTutanaklar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Tatbikat Tutanakları</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisTutanaklar as $t)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $t->senaryoEtiketi() }} — {{ $t->tatbikat_tarihi?->format('d.m.Y') }}</td>
                            <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $t->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $t->id }})">Sil</x-filament::button>
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
