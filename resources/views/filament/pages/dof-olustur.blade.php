@php
    $kirmizi = 'rgb(220 38 38)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma ve gözetim/rapor bilgilerini girin, tespit ettiğiniz maddeleri tek tek ekleyin;
        "DÖF Raporu" ile Çoklu Düzeltici Önleyici Faaliyet raporu PDF'ini indirin.
    </p>

    {{-- 1. FİRMA & RAPOR BİLGİLERİ --}}
    <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="danger">
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
        </div>

        <div style="margin-top:1rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
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
                Kaşe yok — <a href="{{ \App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource::getUrl() }}" style="color:{{ $kirmizi }};text-decoration:underline">İSG Profesyonelleri</a>
                panelinden İGU ekleyip Firma düzenleme sayfasından atayın.
            </p>
        @endif
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. DÖF MADDELERİ --}}
        <x-filament::section icon="heroicon-o-list-bullet" icon-color="danger">
            <x-slot name="heading">
                2. DÖF Maddeleri
                <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ count($maddeler) }} madde)</span>
            </x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.6rem">
                <div style="grid-column:1/-1">
                    <label style="font-weight:600;font-size:.8rem">Tespit <span style="color:#ef4444">*</span></label>
                    <textarea wire:model="yeniTespit" rows="2" placeholder="Sahada tespit edilen uygunsuzluğu yazın"
                        style="margin-top:.2rem;width:100%;padding:.5rem .65rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem"></textarea>
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Öncelik</label>
                    <select wire:model="yeniOncelik"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                        @foreach ($this->oncelikler as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Sorumlu</label>
                    <input type="text" wire:model="yeniSorumlu" placeholder="Örn: Bakım Sorumlusu"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Termin Tarihi</label>
                    <input type="date" wire:model="yeniTermin"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div style="grid-column:1/-1">
                    <label style="font-weight:600;font-size:.8rem">Öneri / Düzeltici Faaliyet</label>
                    <textarea wire:model="yeniOneri" rows="2" placeholder="Alınacak düzeltici/önleyici faaliyet"
                        style="margin-top:.2rem;width:100%;padding:.5rem .65rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem"></textarea>
                    @if ($this->aiAktif)
                        <x-filament::button size="xs" color="gray" icon="heroicon-o-sparkles" wire:click="aiOnerisiAl" style="margin-top:.35rem">
                            Yapay Zekadan Öneri Al
                        </x-filament::button>
                    @endif
                </div>
            </div>

            <x-filament::button size="sm" color="danger" wire:click="maddeEkle" style="margin-top:.75rem">Madde Ekle</x-filament::button>

            @if ($maddeler)
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;margin-top:1rem">
                    <tr>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tespit</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Öncelik</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Öneri</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Sorumlu</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Termin</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Durum</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($maddeler as $i => $m)
                        <tr>
                            <td style="padding:.3rem .5rem;max-width:16rem">{{ $m['tespit'] }}</td>
                            <td style="padding:.3rem .5rem">{{ $this->oncelikler[$m['oncelik']] ?? $m['oncelik'] }}</td>
                            <td style="padding:.3rem .5rem;max-width:16rem">{{ $m['oneri'] ?: '—' }}</td>
                            <td style="padding:.3rem .5rem">{{ $m['sorumlu'] ?: '—' }}</td>
                            <td style="padding:.3rem .5rem">{{ $m['termin'] ? \Illuminate\Support\Carbon::parse($m['termin'])->format('d.m.Y') : '—' }}</td>
                            <td style="padding:.3rem .5rem">
                                <select wire:change="durumGuncelle({{ $i }}, $event.target.value)"
                                    style="font-size:.75rem;padding:.15rem .3rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                                    @foreach ($this->durumlar as $anahtar => $ad)
                                        <option value="{{ $anahtar }}" @selected($m['durum'] === $anahtar)>{{ $ad }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="padding:.3rem .5rem;text-align:right">
                                <button type="button" wire:click="maddeSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 3. GEÇMİŞ KAYITLAR --}}
        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş DÖF Raporları</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Belge No</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Alan/Bölge</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tarih</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Madde</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($this->gecmisKayitlar as $k)
                        <tr>
                            <td style="padding:.35rem .5rem">{{ $k->belge_no }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->alan_bolge ?: '—' }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->rapor_tarihi?->format('d.m.Y') }}</td>
                            <td style="padding:.35rem .5rem">{{ count($k->maddeler ?? []) }}</td>
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
