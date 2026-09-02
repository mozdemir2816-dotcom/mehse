@php
    $mor = 'rgb(139 92 246)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $derece = fn ($d) => match ((int) $d) {
        1 => ['#dc2626', 'rgb(220 38 38 / .1)'],
        2 => ['#ea580c', 'rgb(234 88 12 / .1)'],
        3 => ['#d97706', 'rgb(217 119 6 / .1)'],
        default => ['#16a34a', 'rgb(22 163 74 / .1)'],
    };
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Saha fotoğraflarını yükleyin; yapay zeka eksik KKD ve tehlikeleri tespit etsin, siz
        onaylayıp "Rapor Oluştur" ile İSG Saha Gözetim Raporu PDF'ini indirin.
    </p>

    @if (! $this->aiAktif)
        <div style="{{ $kutu }};background:rgb(245 158 11 / .08);border-color:rgb(245 158 11 / .4);font-size:.82rem;color:#b45309">
            Gemini API anahtarı tanımlı değil — fotoğraf analizi şu an kullanılamıyor. Bulguları elle de ekleyemezsiniz;
            yalnızca API anahtarı tanımlandığında bu modül çalışır.
        </div>
    @endif

    {{-- 1. FİRMA & RAPOR BİLGİLERİ --}}
    <x-filament::section icon="heroicon-o-clipboard-document-list" icon-color="primary">
        <x-slot name="heading">1. Firma & Rapor Bilgileri</x-slot>

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
                <label style="font-weight:600;font-size:.82rem">Alan / Bölge</label>
                <input type="text" wire:model="alanBolge" placeholder="Örn: Üretim Sahası"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Gözetim Tarih Aralığı</label>
                <input type="text" wire:model="gozetimTarihAraligi" placeholder="Örn: 01.09.2026 - 02.09.2026"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Rapor Tarihi</label>
                <input type="date" wire:model="raporTarihi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Gözetim Yapan (İSG Uzmanı)</label>
                <input type="text" wire:model="gozetimYapan"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">İSG Sertifika No</label>
                <input type="text" wire:model="gozetimYapanSertifikaNo"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Sorumlu Kişi</label>
                <input type="text" wire:model="sorumluKisi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">İşveren / Vekili Adı</label>
                <input type="text" wire:model="isverenVekiliAdi"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
        </div>

        @if ($this->firma && ! $this->firma->igu)
            <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:.75rem">
                Kaşe yok — <a href="{{ \App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource::getUrl() }}" style="color:{{ $mor }};text-decoration:underline">İSG Profesyonelleri</a>
                panelinden İGU ekleyip Firma düzenleme sayfasından atayın.
            </p>
        @endif
    </x-filament::section>

    {{-- 2. FOTOĞRAF ANALİZİ (firma seçilmeden de kullanılabilir — isgpratik'te de böyle) --}}
    <x-filament::section icon="heroicon-o-camera" icon-color="primary">
        <x-slot name="heading">2. Fotoğraf Analizi</x-slot>

        <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-bottom:.75rem">
            @foreach ($yuklenenFotograflar as $yol)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($yol) }}" style="width:70px;height:70px;object-fit:cover;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3)">
            @endforeach
            @foreach ($yeniFotograflar as $dosya)
                <img src="{{ $dosya->temporaryUrl() }}" style="width:70px;height:70px;object-fit:cover;border-radius:.4rem;border:2px solid {{ $mor }}">
            @endforeach
        </div>

        <input type="file" wire:model="yeniFotograflar" multiple accept="image/*" style="font-size:.82rem">
        <p style="font-size:.75rem;color:rgb(107 114 128);margin-top:.3rem">
            En fazla {{ \App\Filament\Pages\AiSahaAnalizi::MAX_FOTOGRAF }} fotoğraf · yalnızca yeni eklenen fotoğraflar analiz edilir.
        </p>

        <div style="margin-top:.75rem">
            <label style="font-weight:600;font-size:.82rem">Opsiyonel Bağlam Notu</label>
            <input type="text" wire:model="baglamNotu" placeholder="Örn: inşaat sahası, kalıp katı"
                style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
        </div>

        <x-filament::button color="primary" wire:click="fotograflariAnalizEt" wire:loading.attr="disabled" style="margin-top:1rem">
            <span wire:loading.remove wire:target="fotograflariAnalizEt">Fotoğrafları AI ile Analiz Et</span>
            <span wire:loading wire:target="fotograflariAnalizEt">Analiz ediliyor…</span>
        </x-filament::button>
    </x-filament::section>

    {{-- 3. BULGULAR --}}
    @if ($bulgular)
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
            <x-slot name="heading">
                3. Bulgular
                <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ collect($bulgular)->where('secili', true)->count() }} / {{ count($bulgular) }} seçili)</span>
            </x-slot>
            <x-slot name="afterHeader">
                @if (collect($bulgular)->contains('secili', true))
                    <x-filament::button size="sm" color="success" icon="heroicon-o-arrow-right-circle" wire:click="secilenleriDofeAktar">
                        Seçilenleri DÖF'e Aktar ({{ collect($bulgular)->where('secili', true)->count() }})
                    </x-filament::button>
                @endif
            </x-slot>

            <div style="display:flex;flex-direction:column;gap:.75rem">
                @foreach ($bulgular as $i => $b)
                    @php [$renk, $bgRenk] = $derece($b['risk_derecesi']); @endphp
                    <div style="{{ $kutu }};border-left:4px solid {{ $renk }}">
                        <div style="display:flex;gap:.75rem;align-items:flex-start">
                            <input type="checkbox" wire:model="bulgular.{{ $i }}.secili" style="margin-top:.3rem">
                            @if ($b['foto_yolu'])
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($b['foto_yolu']) }}" style="width:64px;height:64px;object-fit:cover;border-radius:.4rem;flex-shrink:0">
                            @endif
                            <div style="flex:1;min-width:0">
                                <div style="display:flex;gap:.4rem;align-items:center;margin-bottom:.4rem;flex-wrap:wrap">
                                    <span style="font-size:.72rem;font-weight:700;padding:.15rem .5rem;border-radius:.3rem;color:{{ $renk }};background:{{ $bgRenk }}">{{ $b['risk_derecesi'] }}. Derece Risk</span>
                                    @if ($b['kategori'])
                                        <span style="font-size:.72rem;padding:.15rem .5rem;border-radius:.3rem;background:rgb(107 114 128 / .1);color:rgb(107 114 128)">{{ $b['kategori'] }}</span>
                                    @endif
                                    <button type="button" wire:click="bulguSil({{ $i }})" style="margin-left:auto;color:#ef4444;cursor:pointer;background:none;border:none;font-size:.8rem">✕ Sil</button>
                                </div>
                                <label style="font-size:.72rem;font-weight:600;color:rgb(107 114 128)">Bina/Bölge</label>
                                <input type="text" wire:model="bulgular.{{ $i }}.bina_bolge" placeholder="Örn: Ana Kat, Depo"
                                    style="width:100%;padding:.35rem .5rem;border-radius:.35rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.8rem;margin-bottom:.4rem">
                                <label style="font-size:.72rem;font-weight:600;color:rgb(107 114 128)">Tespit</label>
                                <textarea wire:model="bulgular.{{ $i }}.tespit" rows="2"
                                    style="width:100%;padding:.35rem .5rem;border-radius:.35rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.8rem;margin-bottom:.4rem"></textarea>
                                <label style="font-size:.72rem;font-weight:600;color:rgb(107 114 128)">Öneriler (her satır bir madde)</label>
                                <textarea wire:model="bulgular.{{ $i }}.oneriler_metni" rows="3"
                                    style="width:100%;padding:.35rem .5rem;border-radius:.35rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.8rem;margin-bottom:.4rem"></textarea>
                                <label style="font-size:.72rem;font-weight:600;color:rgb(107 114 128)">İlgili Yasal Gerekçe</label>
                                <input type="text" wire:model="bulgular.{{ $i }}.yasal_gerekce"
                                    style="width:100%;padding:.35rem .5rem;border-radius:.35rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.8rem;margin-bottom:.4rem">
                                <label style="font-size:.72rem;font-weight:600;color:rgb(107 114 128)">Risk Derecesi</label>
                                <select wire:model="bulgular.{{ $i }}.risk_derecesi"
                                    style="padding:.35rem .5rem;border-radius:.35rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.8rem">
                                    @foreach ($this->riskDereceleri as $d => $etiket)
                                        <option value="{{ $d }}">{{ $d }}. Derece ({{ $etiket }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    {{-- 4. GEÇMİŞ KAYITLAR --}}
    @if ($this->firma)
        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Saha Gözetim Raporları</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Belge No</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Alan/Bölge</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tarih</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Bulgu</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($this->gecmisKayitlar as $k)
                        <tr>
                            <td style="padding:.35rem .5rem">{{ $k->belge_no }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->alan_bolge ?: '—' }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->rapor_tarihi?->format('d.m.Y') }}</td>
                            <td style="padding:.35rem .5rem">{{ count($k->bulgular ?? []) }}</td>
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
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">PDF rapor oluşturmak için bir firma seçin (fotoğraf analizi firma seçmeden de yapılabilir).</p>
    @endif

    <div style="{{ $kutu }};background:rgb(107 114 128 / .05);font-size:.78rem;color:rgb(107 114 128)">
        Bu analiz yapay zeka destekli bir ön değerlendirmedir; saha koşullarının İSG profesyoneli tarafından
        doğrulanması olmadan resmi tespit, ölçüm veya uygunluk beyanı yerine geçmez. Nihai değerlendirme
        sorumluluğu kullanıcıya aittir.
    </div>
</x-filament-panels::page>
