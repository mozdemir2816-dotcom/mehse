@php
    $teal = 'rgb(14 116 144)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        EK-2 İşe Giriş / Periyodik Muayene Formu'nu doldurun; sonuç ve kanaat ile PDF çıktısı üretilir.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'saglik_raporu'])

    {{-- 1. FİRMA & ÇALIŞAN --}}
    <x-filament::section icon="heroicon-o-heart" icon-color="info">
        <x-slot name="heading">1. Firma & Çalışan</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId" style="{{ $girdi }}">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            @if ($this->firma)
                <div>
                    <label style="font-weight:600;font-size:.82rem">Hızlı Çalışan Seç</label>
                    <select wire:model.live="calisanHizliSecId" style="{{ $girdi }}">
                        <option value="">Firmaya kayıtlı çalışan yok / manuel gir</option>
                        @foreach ($this->calisanlar as $c)
                            <option value="{{ $c->id }}">{{ $c->ad_soyad }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        @if ($this->firma)
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-top:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Ad Soyad <span style="color:#ef4444">*</span></label>
                    <input type="text" wire:model="calisanAdSoyad" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">T.C. Kimlik No</label>
                    <input type="text" wire:model="calisanTc" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Doğum Tarihi</label>
                    <input type="date" wire:model="calisanDogumTarihi" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Görevi</label>
                    <input type="text" wire:model="calisanGorevi" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">İşe Giriş Tarihi</label>
                    <input type="date" wire:model="iseGirisTarihi" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Muayene Tarihi</label>
                    <input type="date" wire:model="muayeneTarihi" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Muayene Türü</label>
                    <select wire:model="muayeneTuru" style="{{ $girdi }}">
                        @foreach ($this->muayeneTurleri as $anahtar => $etiket)
                            <option value="{{ $anahtar }}">{{ $etiket }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. MESLEK ÖYKÜSÜ VE MARUZİYET --}}
        <x-filament::section icon="heroicon-o-briefcase" icon-color="info">
            <x-slot name="heading">2. Meslek Öyküsü ve Maruziyet</x-slot>
            <label style="font-weight:600;font-size:.8rem">Meslek Öyküsü</label>
            <textarea wire:model="meslekOykusu" rows="2" placeholder="Daha önce çalıştığı işler, süreleri..."
                style="width:100%;margin-top:.2rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem;margin-bottom:.75rem"></textarea>
            <label style="font-weight:600;font-size:.8rem">Maruz Kalınan Riskler</label>
            <textarea wire:model="maruzKalinanRiskler" rows="2" placeholder="Gürültü, kimyasal, toz, titreşim..."
                style="width:100%;margin-top:.2rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem"></textarea>
        </x-filament::section>

        {{-- 3. ÖZGEÇMİŞ VE SOYGEÇMİŞ --}}
        <x-filament::section icon="heroicon-o-document-text" icon-color="info">
            <x-slot name="heading">3. Özgeçmiş ve Soygeçmiş</x-slot>
            <label style="font-weight:600;font-size:.8rem">Özgeçmiş</label>
            <textarea wire:model="ozgecmis" rows="2" placeholder="Kronik hastalık, kullandığı ilaçlar, sigara/alkol kullanımı, geçirdiği ameliyatlar, alerjiler..."
                style="width:100%;margin-top:.2rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem;margin-bottom:.75rem"></textarea>
            <label style="font-weight:600;font-size:.8rem">Soygeçmiş</label>
            <textarea wire:model="soygecmis" rows="2" placeholder="Ailede bilinen kronik hastalık öyküsü..."
                style="width:100%;margin-top:.2rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem"></textarea>
        </x-filament::section>

        {{-- 4. SİSTEMİK MUAYENE --}}
        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="info">
            <x-slot name="heading">4. Sistemik Muayene Bulguları</x-slot>
            <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                <tr><th style="text-align:left;padding:.3rem">Sistem</th><th style="width:140px">Sonuç</th><th>Not</th></tr>
                @foreach ($sistemikMuayene as $i => $s)
                    <tr>
                        <td style="padding:.3rem">{{ $s['baslik'] }}</td>
                        <td style="padding:.3rem">
                            <select wire:model="sistemikMuayene.{{ $i }}.sonuc"
                                style="width:100%;padding:.3rem .4rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.78rem">
                                <option value="normal">Normal</option>
                                <option value="anormal">Anormal</option>
                            </select>
                        </td>
                        <td style="padding:.3rem">
                            <input type="text" wire:model="sistemikMuayene.{{ $i }}.not" placeholder="Bulgu / not"
                                style="width:100%;padding:.3rem .4rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.78rem">
                        </td>
                    </tr>
                @endforeach
            </table>
        </x-filament::section>

        {{-- 5. TETKİKLER --}}
        <x-filament::section icon="heroicon-o-beaker" icon-color="info">
            <x-slot name="heading">5. Laboratuvar / Tetkik Sonuçları</x-slot>
            <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                <tr><th style="text-align:left;padding:.3rem">Tetkik</th><th style="width:80px">Yapıldı</th><th style="width:120px">Sonuç</th><th>Not</th></tr>
                @foreach ($tetkikler as $i => $t)
                    <tr>
                        <td style="padding:.3rem">{{ config('isg.muayene.tetkikler.'.$t['anahtar']) }}</td>
                        <td style="padding:.3rem;text-align:center">
                            <input type="checkbox" wire:model.live="tetkikler.{{ $i }}.yapildi">
                        </td>
                        <td style="padding:.3rem">
                            @if ($t['yapildi'])
                                <select wire:model="tetkikler.{{ $i }}.sonuc"
                                    style="width:100%;padding:.3rem .4rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.78rem">
                                    <option value="">—</option>
                                    <option value="normal">Normal</option>
                                    <option value="anormal">Anormal</option>
                                </select>
                            @else
                                <span style="color:rgb(107 114 128);font-size:.75rem">—</span>
                            @endif
                        </td>
                        <td style="padding:.3rem">
                            <input type="text" wire:model="tetkikler.{{ $i }}.not" placeholder="Not"
                                style="width:100%;padding:.3rem .4rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.78rem">
                        </td>
                    </tr>
                @endforeach
            </table>
        </x-filament::section>

        {{-- 6. SONUÇ VE KANAAT --}}
        <x-filament::section icon="heroicon-o-check-badge" icon-color="info">
            <x-slot name="heading">6. Sonuç ve Kanaat</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.5rem">
                @foreach ($this->sonucKanaatleri as $anahtar => $etiket)
                    @php $secili = $sonucKanaati === $anahtar; @endphp
                    <button type="button" wire:click="$set('sonucKanaati', '{{ $anahtar }}')"
                        style="text-align:left;padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.82rem;font-weight:600;
                            border:2px solid {{ $secili ? $teal : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(14 116 144 / .08)' : 'transparent' }}">
                        {{ $etiket }}
                    </button>
                @endforeach
            </div>

            @if ($sonucKanaati === 'sartli_uygun')
                <textarea wire:model="sartAciklamasi" rows="2" placeholder="Şart açıklaması (örn: gece vardiyasında çalışamaz)"
                    style="width:100%;margin-top:.75rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem"></textarea>
            @endif

            <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem;align-items:end;margin-top:1rem;max-width:420px">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Önerilen Bir Sonraki Kontrol Tarihi</label>
                    <input type="date" wire:model="onerilenKontrolTarihi" style="{{ $girdi }}">
                </div>
                <x-filament::button size="sm" color="gray" wire:click="kontrolTarihiOner">
                    Tehlike Sınıfına Göre Öner
                </x-filament::button>
            </div>

            <div style="margin-top:1rem;max-width:300px">
                <label style="font-weight:600;font-size:.8rem">Muayeneyi Yapan Hekim</label>
                <input type="text" wire:model="hekimAdi" style="{{ $girdi }}">
            </div>
        </x-filament::section>

        {{-- 7. GEÇMİŞ KAYITLAR --}}
        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Muayene Kayıtları</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisKayitlar as $m)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $m->belge_no }} — {{ $m->calisan_ad_soyad }} ({{ $m->muayene_tarihi?->format('d.m.Y') }})</td>
                            <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $m->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $m->id }})">Sil</x-filament::button>
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
