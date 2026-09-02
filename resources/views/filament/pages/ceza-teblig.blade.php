@php
    $kirmizi = 'rgb(239 68 68)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Çalışana yönelik ceza/ihtar tutanağını ve işverene idari para cezası bilgilendirme
        tebliğini — resmi PDF çıktısı ile — hazırlayın.
    </p>

    {{-- 1. FİRMA & ÇALIŞAN --}}
    <x-filament::section icon="heroicon-o-scale" icon-color="danger">
        <x-slot name="heading">1. Firma ve Çalışan Bilgileri</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1rem">
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
                <label style="font-weight:600;font-size:.82rem">Tutanak Tarihi</label>
                <input type="date" wire:model="tutanakTarihi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
        </div>

        @if ($this->firma)
            @if ($this->calisanlar->isNotEmpty())
                <div style="margin-bottom:.75rem">
                    <label style="font-weight:600;font-size:.82rem">Hızlı Çalışan Seç</label>
                    <select wire:model.live="calisanHizliSecId"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <option value="">Firmaya kayıtlı çalışan yok / manuel gir</option>
                        @foreach ($this->calisanlar as $c)
                            <option value="{{ $c->id }}">{{ $c->ad_soyad }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Adı Soyadı <span style="color:#ef4444">*</span></label>
                    <input type="text" wire:model="calisanAdSoyad"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">T.C. Kimlik No</label>
                    <input type="text" wire:model="calisanTc"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Görevi</label>
                    <input type="text" wire:model="calisanGorev"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Bölümü</label>
                    <input type="text" wire:model="calisanBolum"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">İşe Giriş Tarihi</label>
                    <input type="date" wire:model="iseGirisTarihi"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
            </div>

            <div style="margin-top:.75rem">
                <label style="font-weight:600;font-size:.8rem">İstihdam Şekli</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.5rem;margin-top:.3rem">
                    @foreach (['kadrolu' => 'Kadrolu Çalışan', 'alt_isveren' => 'Alt İşveren (Taşeron) Çalışan', 'gecici_gorevli' => 'Geçici Görevli Çalışan'] as $anahtar => $etiket)
                        @php $secili = $istihdamSekli === $anahtar; @endphp
                        <button type="button" wire:click="$set('istihdamSekli', '{{ $anahtar }}')"
                            style="padding:.5rem;border-radius:.4rem;cursor:pointer;font-size:.78rem;text-align:center;
                                border:1px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                                background:{{ $secili ? 'rgb(239 68 68 / .08)' : 'transparent' }}">
                            {{ $etiket }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. OLAY BİLGİLERİ --}}
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
            <x-slot name="heading">2. Olay Bilgileri</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Olay Tarihi</label>
                    <input type="date" wire:model="olayTarihi"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Olay Saati</label>
                    <input type="time" wire:model="olaySaati"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Olay Yeri</label>
                    <input type="text" wire:model="olayYeri" placeholder="Örn: Üretim sahası"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
            </div>
            <div style="margin-top:.75rem">
                <label style="font-weight:600;font-size:.8rem">Olay Açıklaması</label>
                <textarea wire:model="olayAciklamasi" rows="2" placeholder="Olayın gelişimi, yapılan uyarılar ve çalışanın davranışı..."
                    style="width:100%;margin-top:.2rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem"></textarea>
            </div>

            <div style="margin-top:.75rem">
                <label style="font-weight:600;font-size:.8rem">Tanıklar</label>
                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.5rem;margin-top:.3rem;margin-bottom:.5rem">
                    <input type="text" wire:model="yeniTanikAd" placeholder="Adı Soyadı"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <input type="text" wire:model="yeniTanikGorev" placeholder="Görevi (örn. Vardiya Amiri)"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <x-filament::button size="sm" wire:click="tanikEkle">+ Ekle</x-filament::button>
                </div>
                @foreach ($taniklar as $i => $tk)
                    <div style="display:flex;justify-content:space-between;font-size:.8rem;padding:.2rem .4rem">
                        <span>{{ $tk['ad_soyad'] }} — {{ $tk['gorev'] ?: '—' }}</span>
                        <button type="button" wire:click="tanikSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 3. İHLAL EDİLEN KURALLAR --}}
        <x-filament::section icon="heroicon-o-x-circle" icon-color="danger">
            <x-slot name="heading">3. İhlal Edilen Kurallar ({{ count($ihlaller) }} seçili)</x-slot>
            <x-slot name="description">Katalogdan seçin; listede olmayan durumlar için serbest madde ekleyin</x-slot>

            @foreach ($this->ihlalKatalogu as $kategori => $maddeler)
                <div style="margin-bottom:.6rem">
                    <div style="font-weight:700;font-size:.78rem;color:{{ $kirmizi }};margin-bottom:.3rem;text-transform:uppercase">{{ $kategori }}</div>
                    <div style="display:flex;flex-direction:column;gap:.3rem">
                        @foreach ($maddeler as $m)
                            @php $secili = $this->ihlalSeciliMi($m['madde']); @endphp
                            <button type="button" wire:click="ihlalToggle('{{ addslashes($m['madde']) }}', '{{ addslashes($m['dayanak']) }}')"
                                style="text-align:left;padding:.4rem .6rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                                    border:1px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                                    background:{{ $secili ? 'rgb(239 68 68 / .08)' : 'transparent' }}">
                                {{ $secili ? '☑' : '☐' }} {{ $m['madde'] }}
                                <div style="font-size:.68rem;color:rgb(107 114 128)">{{ $m['dayanak'] }}</div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div style="{{ $kutu }};margin-top:.5rem">
                <div style="font-weight:600;font-size:.8rem;margin-bottom:.4rem">Serbest Madde Ekle</div>
                <textarea wire:model="serbestIhlalMetni" rows="2" placeholder="İhlal açıklaması (tutanağa yazılacak cümle)"
                    style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem;margin-bottom:.4rem"></textarea>
                <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem">
                    <input type="text" wire:model="serbestIhlalDayanak" placeholder="Yasal dayanak (boşsa: işyeri iç yönetmeliği)"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <x-filament::button size="sm" wire:click="serbestIhlalEkle">+ Ekle</x-filament::button>
                </div>
            </div>

            @if ($ihlaller)
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;margin-top:.75rem">
                    @foreach ($ihlaller as $i => $ih)
                        <tr>
                            <td style="padding:.25rem .5rem">{{ $ih['madde'] }}</td>
                            <td style="padding:.25rem .5rem;text-align:right">
                                <button type="button" wire:click="ihlalSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 4. YAPTIRIM --}}
        <x-filament::section icon="heroicon-o-shield-exclamation" icon-color="danger">
            <x-slot name="heading">4. Uygulanan Yaptırım</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.5rem">
                @foreach ($this->yaptirimlar as $anahtar => $y)
                    @php $secili = $yaptirim === $anahtar; @endphp
                    <button type="button" wire:click="$set('yaptirim', '{{ $anahtar }}')"
                        style="text-align:left;padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.82rem;
                            border:2px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(239 68 68 / .08)' : 'transparent' }}">
                        <div style="font-weight:700">{{ $y['ad'] }}</div>
                        <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $y['aciklama'] }}</div>
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 5. TEBLİĞ / TEBELLÜĞ --}}
        <x-filament::section icon="heroicon-o-calendar" icon-color="danger">
            <x-slot name="heading">5. Tebliğ / Tebellüğ</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Tebliğ Tarihi</label>
                    <input type="date" wire:model="tebligTarihi"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Çalışanın İmza Durumu</label>
                    <div style="display:flex;gap:.5rem;margin-top:.3rem">
                        @foreach (['imzaladi' => 'İmzaladı / Teslim Aldı', 'imtina_etti' => 'İmzadan İmtina Etti'] as $anahtar => $etiket)
                            @php $secili = $imzaDurumu === $anahtar; @endphp
                            <button type="button" wire:click="$set('imzaDurumu', '{{ $anahtar }}')"
                                style="flex:1;padding:.45rem;border-radius:.4rem;cursor:pointer;font-size:.78rem;
                                    border:1px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                                    background:{{ $secili ? 'rgb(239 68 68 / .08)' : 'transparent' }}">
                                {{ $etiket }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- 6. GEÇMİŞ TUTANAKLAR --}}
        @if ($this->gecmisTutanaklar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Tutanaklar</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisTutanaklar as $t)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $t->tutanak_no }} — {{ $t->calisan_ad_soyad }}</td>
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
