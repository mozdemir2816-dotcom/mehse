@php
    $yesil = 'rgb(16 185 129)';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İşe yeni başlayan her çalışan için ayrı, tek sayfalık işbaşı/oryantasyon
        eğitim tutanağı oluşturun. Firma seçmeden, üstteki <strong>"Boş Katılım Formu"</strong>
        ile boş imza satırlı formu indirip işyerinde elle doldurabilirsiniz.
    </p>

    {{-- 1. FİRMA & EĞİTİM BİLGİSİ --}}
    <x-filament::section icon="heroicon-o-identification" icon-color="success">
        <x-slot name="heading">1. Firma & Eğitim Bilgisi</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
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
                <label style="font-weight:600;font-size:.82rem">Süre (Saat)</label>
                <input type="number" wire:model="sureSaat" min="1"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Eğitim Yeri</label>
                <input type="text" wire:model="egitimYeri" placeholder="Örn: Üretim sahası"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Eğitimi Veren</label>
                <input type="text" wire:model="egitimiVeren" placeholder="Ad Soyad"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Eğitim Yöntemi</label>
                <select wire:model="egitimYontemi"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    @foreach ($this->egitimYontemleri as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Eğitim Tarihi</label>
                <input type="date" wire:model="egitimTarihi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Belge Tarihi</label>
                <input type="date" wire:model="belgeTarihi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
        </div>

        <div style="margin-top:1rem;display:flex;gap:1.5rem;flex-wrap:wrap">
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer">
                <input type="checkbox" wire:model="iguImzasi"> İGU imzası ekle
            </label>
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer">
                <input type="checkbox" wire:model="isyeriHekimiImzasi"> İşyeri Hekimi imzası ekle
            </label>
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer">
                <input type="checkbox" wire:model="tcGizli"> T.C. No gizle
            </label>
        </div>
    </x-filament::section>

    {{-- 2. EĞİTİM KONULARI — firma seçilmeden de düzenlenebilir (boş katılım formu için) --}}
    <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="success">
        <x-slot name="heading">2. Eğitim Konuları ({{ count($secilenKonular) }}/{{ $this->toplamMaddeSayisi }})</x-slot>

        <div style="margin-bottom:.75rem;display:flex;gap:.5rem">
            <x-filament::button size="xs" color="gray" wire:click="tumKonular(true)">Tümünü Seç</x-filament::button>
            <x-filament::button size="xs" color="gray" wire:click="tumKonular(false)">Tümünü Kaldır</x-filament::button>
        </div>

        @foreach ($this->konuKategorileri as $kategori => $maddeler)
            <div style="margin-bottom:.75rem">
                <div style="font-weight:700;font-size:.78rem;color:{{ $yesil }};margin-bottom:.3rem;text-transform:uppercase">{{ $kategori }}</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:.35rem">
                    @foreach ($maddeler as $madde)
                        @php $secili = in_array($madde, $secilenKonular, true); @endphp
                        <button type="button" wire:click="konuToggle('{{ addslashes($madde) }}')"
                            style="text-align:left;padding:.4rem .6rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                                border:1px solid {{ $secili ? $yesil : 'rgb(107 114 128 / .3)' }};
                                background:{{ $secili ? 'rgb(16 185 129 / .08)' : 'transparent' }}">
                            {{ $secili ? '☑' : '☐' }} {{ $madde }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </x-filament::section>

    @if ($this->firma)
        {{-- 3. ÇALIŞAN --}}
        <x-filament::section icon="heroicon-o-user" icon-color="success">
            <x-slot name="heading">3. Çalışan Bilgisi</x-slot>

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

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Ad Soyad <span style="color:#ef4444">*</span></label>
                    <input type="text" wire:model="calisanAdSoyad"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">T.C. Kimlik No</label>
                    <input type="text" wire:model="calisanTc"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
            </div>
        </x-filament::section>

        {{-- 4. GEÇMİŞ TUTANAKLAR --}}
        @if ($this->gecmisTutanaklar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Tutanaklar</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisTutanaklar as $t)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $t->calisan_ad_soyad }} — {{ $t->egitim_tarihi?->format('d.m.Y') }}</td>
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
