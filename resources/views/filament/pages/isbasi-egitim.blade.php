@php
    $yesil = 'rgb(4 120 87)';
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <p class="mehse-bolum-aciklama">
        İşe yeni başlayan her çalışan için ayrı, tek sayfalık işbaşı/oryantasyon
        eğitim tutanağı oluşturun. Firma seçmeden, üstteki <strong>"Boş Katılım Formu"</strong>
        ile boş imza satırlı formu indirip işyerinde elle doldurabilirsiniz.
    </p>

    {{-- 1. FİRMA & EĞİTİM BİLGİSİ --}}
    <x-filament::section icon="heroicon-o-identification" icon-color="success">
        <x-slot name="heading">1. Firma & Eğitim Bilgisi</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
            <div>
                <label class="mehse-etiket">Firma Seçin <span class="mehse-hata">*</span></label>
                <select wire:model.live="firmaId"
                    class="mehse-girdi">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mehse-etiket">Süre (Saat)</label>
                <input type="number" wire:model="sureSaat" min="1"
                    class="mehse-girdi">
            </div>
            <div>
                <label class="mehse-etiket">Eğitim Yeri</label>
                <input type="text" wire:model="egitimYeri" placeholder="Örn: Üretim sahası"
                    class="mehse-girdi">
            </div>
            <div>
                <label class="mehse-etiket">Eğitimi Veren</label>
                <input type="text" wire:model="egitimiVeren" placeholder="Ad Soyad"
                    class="mehse-girdi">
            </div>
            <div>
                <label class="mehse-etiket">Eğitim Yöntemi</label>
                <select wire:model="egitimYontemi"
                    class="mehse-girdi">
                    @foreach ($this->egitimYontemleri as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mehse-etiket">Eğitim Tarihi</label>
                <input type="date" wire:model="egitimTarihi"
                    class="mehse-girdi">
            </div>
            <div>
                <label class="mehse-etiket">Belge Tarihi</label>
                <input type="date" wire:model="belgeTarihi"
                    class="mehse-girdi">
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

    {{-- İŞE ÖZGÜ EĞİTİM SUNUMLARI (Kütüphanem) — firmadan bağımsız, kendi arşivim --}}
    <x-filament::section icon="heroicon-o-presentation-chart-bar" icon-color="success">
        <x-slot name="heading">İşe Özgü Eğitim Sunumları (Kütüphanem, {{ $this->sunumlar->count() }})</x-slot>
        <x-slot name="description">Sağ üstteki "Eğitim Sunumu Yükle" ile görev/iş bazlı PowerPoint/PDF slaytlarınızı buraya kaydedin — o göreve yeni biri başladığında aşağıda otomatik önerilir.</x-slot>

        @if ($this->sunumlar->isNotEmpty())
            <table class="mehse-tablo">
                @foreach ($this->sunumlar as $s)
                    <tr>
                        <td style="padding:.3rem .5rem"><strong>{{ $s->gorev }}</strong> — {{ $s->baslik }}</td>
                        <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                            <x-filament::button size="xs" color="gray" wire:click="sunumIndir({{ $s->id }})">İndir</x-filament::button>
                            <x-filament::button size="xs" color="danger" wire:click="sunumSil({{ $s->id }})">Sil</x-filament::button>
                        </td>
                    </tr>
                @endforeach
            </table>
        @else
            <p style="font-size:.82rem;color:rgb(107 114 128)">Henüz sunum yüklenmedi.</p>
        @endif
    </x-filament::section>

    @if ($this->firma)
        {{-- 3. ÇALIŞAN --}}
        <x-filament::section icon="heroicon-o-user" icon-color="success">
            <x-slot name="heading">3. Çalışan Bilgisi</x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="margin-bottom:.75rem">
                    <label class="mehse-etiket">Hızlı Çalışan Seç</label>
                    <select wire:model.live="calisanHizliSecId"
                        class="mehse-girdi">
                        <option value="">Firmaya kayıtlı çalışan yok / manuel gir</option>
                        @foreach ($this->calisanlar as $c)
                            <option value="{{ $c->id }}">{{ $c->ad_soyad }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="mehse-izgara">
                <div>
                    <label class="mehse-etiket">Ad Soyad <span class="mehse-hata">*</span></label>
                    <input type="text" wire:model="calisanAdSoyad"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">T.C. Kimlik No</label>
                    <input type="text" wire:model="calisanTc"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
                <div>
                    <label class="mehse-etiket">Görev / İş</label>
                    <input type="text" wire:model.live="calisanGorev" placeholder="Örn: Kaynakçı"
                        class="mehse-girdi-kompakt mehse-tam">
                </div>
            </div>

            @if ($this->eslesenSunumlar->isNotEmpty())
                <div style="margin-top:.75rem;padding:.6rem .8rem;border-radius:.5rem;background:rgb(16 185 129 / .08);border:1px solid {{ $yesil }}">
                    <div style="font-weight:700;font-size:.8rem;color:{{ $yesil }};margin-bottom:.3rem">Bu göreve uygun eğitim sunumu bulundu</div>
                    @foreach ($this->eslesenSunumlar as $s)
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:.82rem;padding:.2rem 0">
                            <span>{{ $s->gorev }} — {{ $s->baslik }}</span>
                            <x-filament::button size="xs" color="success" wire:click="sunumIndir({{ $s->id }})">İndir</x-filament::button>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- 4. GEÇMİŞ TUTANAKLAR --}}
        @if ($this->gecmisTutanaklar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Tutanaklar</x-slot>
                <table class="mehse-tablo">
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
        <p class="mehse-uyari-not">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
