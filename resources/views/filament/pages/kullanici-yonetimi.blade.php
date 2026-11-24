<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')

    {{-- OSGB Canlı Saha Ekibi Konumları (sol alt "📍 Konum Açık" ile paylaşılan konumlar) --}}
    @if (count($this->aktifKonumlar) > 0)
        <div style="border:1px solid rgb(34 197 94 / .45);background:rgb(34 197 94 / .07);border-radius:.75rem;padding:.85rem 1rem">
            <div style="font-weight:700;font-size:.86rem;color:#15803d;margin-bottom:.45rem">
                📍 Canlı Saha Konumu Paylaşan Ekip ({{ count($this->aktifKonumlar) }})
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem">
                @foreach ($this->aktifKonumlar as $k)
                    <div style="display:inline-flex;align-items:center;gap:.45rem;padding:.35rem .65rem;border-radius:.6rem;border:1px solid rgb(34 197 94 / .35);background:rgb(255 255 255 / .75);font-size:.78rem">
                        <strong>{{ $k['name'] }}</strong>
                        <span style="color:rgb(95 87 112)">· {{ $k['guncelleme'] ?: 'Az önce' }}</span>
                        @if ($k['harita_url'])
                            <a href="{{ $k['harita_url'] }}" target="_blank" rel="noopener noreferrer"
                                style="font-weight:700;color:#0369a1;text-decoration:underline">Haritada Gör ↗</a>
                        @else
                            <span style="color:rgb(180 83 9)">(koordinat bekleniyor)</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <p style="font-size:.85rem;color:rgb(95 87 112)">
        Kayıt olan kullanıcılar varsayılan olarak hiçbir sayfa görmez. Aşağıdan her kullanıcı
        için erişebileceği sayfaları, hazır rol paketlerini ve firmaları seçip kaydedin.
    </p>

    <div style="display:flex;flex-direction:column;gap:.75rem">
        @forelse ($this->kullanicilar as $kullanici)
            @php
                $acik = $this->acikKullaniciId === $kullanici->id;
                $kKonum = (array) (($kullanici->ayarlar ?? [])['canli_konum'] ?? []);
                $kKonumAktif = (bool) ($kKonum['aktif'] ?? false);
                $kEnlem = isset($kKonum['enlem']) && is_numeric($kKonum['enlem']) ? (float) $kKonum['enlem'] : null;
                $kBoylam = isset($kKonum['boylam']) && is_numeric($kKonum['boylam']) ? (float) $kKonum['boylam'] : null;
            @endphp
            <div style="border:1px solid rgb(128 116 148 / .3);border-radius:.75rem;overflow:hidden">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.85rem 1rem;flex-wrap:wrap">
                    <div style="cursor:pointer;flex:1;min-width:220px" wire:click="ac({{ $kullanici->id }})">
                        <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap">
                            <span style="font-weight:700">{{ $kullanici->name }}</span>
                            @if ($kKonumAktif)
                                <span style="font-size:.7rem;font-weight:700;padding:.1rem .45rem;border-radius:9999px;background:rgb(34 197 94 / .15);color:#15803d">
                                    📍 Konum Açık{{ ! empty($kKonum['guncelleme']) ? ' · '.$kKonum['guncelleme'] : '' }}
                                </span>
                                @if ($kEnlem !== null && $kBoylam !== null)
                                    <a href="{{ sprintf('https://www.google.com/maps?q=%.6f,%.6f', $kEnlem, $kBoylam) }}" target="_blank" rel="noopener noreferrer"
                                        x-on:click.stop
                                        style="font-size:.72rem;color:#0369a1;text-decoration:underline;font-weight:600">Harita ↗</a>
                                @endif
                            @endif
                        </div>
                        <div style="font-size:.8rem;color:rgb(95 87 112)">
                            {{ $kullanici->email }} · {{ $kullanici->telefon ?: '—' }} · {{ $kullanici->rol }}
                        </div>
                        @php
                            $kalan = $kullanici->abonelikKalanGun();
                        @endphp
                        <div style="font-size:.78rem;margin-top:.2rem;color:{{ $kalan === null ? 'rgb(95 87 112)' : ($kalan < 0 ? '#b91c1c' : ($kalan <= 7 ? '#b45309' : '#047857')) }}">
                            {{ \App\Models\User::ABONELIK_PLANLARI[$kullanici->abonelik_plani] ?? $kullanici->abonelik_plani }} ·
                            @if ($kalan === null)
                                süresiz
                            @elseif ($kalan < 0)
                                süresi doldu ({{ $kullanici->abonelik_bitis->format('d.m.Y') }}) — panele erişemiyor
                            @else
                                {{ $kullanici->abonelik_bitis->format('d.m.Y') }} tarihine kadar ({{ $kalan }} gün)
                            @endif
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.6rem">
                        <label style="display:flex;align-items:center;gap:.35rem;font-size:.8rem;cursor:pointer">
                            <input type="checkbox" wire:click="aktifDegistir({{ $kullanici->id }})" @checked($kullanici->aktif)>
                            Aktif
                        </label>
                        <x-filament::button size="sm" color="gray" wire:click="ac({{ $kullanici->id }})">
                            {{ $acik ? 'Kapat' : 'Yetkileri Düzenle' }}
                        </x-filament::button>
                    </div>
                </div>

                @if ($acik)
                    @php
                        $seciliSayfaSayisi = count(array_filter($this->sayfaSecim));
                        $toplamSayfaSayisi = count($this->sayfaKatalogu);
                        $seciliFirmaSayisi = count(array_filter($this->firmaSecim));
                        $toplamFirmaSayisi = $this->firmalar->count();
                    @endphp
                    <div style="padding:1rem;border-top:1px solid rgb(128 116 148 / .2);display:flex;flex-wrap:wrap;align-items:flex-end;gap:.75rem">
                        <label style="display:flex;flex-direction:column;gap:.25rem;font-size:.8rem;font-weight:600">
                            Abonelik planı
                            <select wire:model="abonelikPlani" style="border:1px solid rgb(128 116 148 / .4);border-radius:.5rem;padding:.35rem .5rem;font-weight:400;background:transparent">
                                @foreach (\App\Models\User::ABONELIK_PLANLARI as $k => $ad)
                                    <option value="{{ $k }}">{{ $ad }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label style="display:flex;flex-direction:column;gap:.25rem;font-size:.8rem;font-weight:600">
                            Bitiş tarihi (boş = süresiz)
                            <input type="date" wire:model="abonelikBitis" style="border:1px solid rgb(128 116 148 / .4);border-radius:.5rem;padding:.3rem .5rem;font-weight:400;background:transparent">
                        </label>
                        <x-filament::button size="sm" color="gray" wire:click="abonelikUzat(30)">+30 gün</x-filament::button>
                        <x-filament::button size="sm" color="gray" wire:click="abonelikUzat(365)">+1 yıl</x-filament::button>
                        <span style="font-size:.75rem;color:rgb(95 87 112)">Değişiklik "Kaydet" ile uygulanır.</span>
                    </div>

                    {{-- Hazır Rol Paketleri --}}
                    <div style="padding:.75rem 1rem;border-top:1px solid rgb(128 116 148 / .2);display:flex;flex-wrap:wrap;align-items:center;gap:.45rem">
                        <span style="font-size:.78rem;font-weight:700;color:rgb(95 87 112)">Hazır Rol Şablonu:</span>
                        @foreach (\App\Filament\Pages\KullaniciYonetimi::ROL_SABLONLARI as $rolKod => $rolBilgi)
                            <x-filament::button size="xs" color="gray" wire:click="rolSablonuUygula('{{ $rolKod }}')">
                                {{ $rolBilgi['ad'] }}
                            </x-filament::button>
                        @endforeach
                    </div>

                    <div style="padding:1rem;border-top:1px solid rgb(128 116 148 / .2);display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
                        <div>
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.45rem">
                                <div style="font-weight:600;font-size:.85rem">Sayfalar <span style="font-weight:400;color:rgb(95 87 112)">({{ $seciliSayfaSayisi }}/{{ $toplamSayfaSayisi }})</span></div>
                                <div style="display:flex;gap:.6rem">
                                    <button type="button" wire:click="tumSayfalariSec" style="font-size:.75rem;color:rgb(37 99 235);background:none;border:none;cursor:pointer;text-decoration:underline">Tümünü Seç</button>
                                    <button type="button" wire:click="tumSayfalariKaldir" style="font-size:.75rem;color:rgb(185 28 28);background:none;border:none;cursor:pointer;text-decoration:underline">Temizle</button>
                                </div>
                            </div>
                            <input
                                type="search"
                                wire:model.live.debounce.200ms="sayfaArama"
                                placeholder="Sayfa ara… (örn. risk, muayene, kkd)"
                                style="width:100%;margin-bottom:.5rem;padding:.35rem .55rem;border:1px solid rgb(128 116 148 / .35);border-radius:.45rem;font-size:.78rem;background:transparent"
                            >
                            <div style="display:flex;flex-direction:column;gap:.3rem;max-height:320px;overflow:auto">
                                @forelse ($this->filtrelenmisSayfalar as $anahtar => $ad)
                                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem">
                                        <input type="checkbox" wire:model="sayfaSecim.{{ $anahtar }}">
                                        {{ $ad }}
                                    </label>
                                @empty
                                    <span style="font-size:.78rem;color:rgb(95 87 112)">Eşleşen sayfa yok.</span>
                                @endforelse
                            </div>
                        </div>
                        <div>
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.45rem">
                                <div style="font-weight:600;font-size:.85rem">Firmalar <span style="font-weight:400;color:rgb(95 87 112)">({{ $seciliFirmaSayisi }}/{{ $toplamFirmaSayisi }})</span></div>
                                <div style="display:flex;gap:.6rem">
                                    <button type="button" wire:click="tumFirmalariSec" style="font-size:.75rem;color:rgb(37 99 235);background:none;border:none;cursor:pointer;text-decoration:underline">Tümünü Seç</button>
                                    <button type="button" wire:click="tumFirmalariKaldir" style="font-size:.75rem;color:rgb(185 28 28);background:none;border:none;cursor:pointer;text-decoration:underline">Temizle</button>
                                </div>
                            </div>
                            <input
                                type="search"
                                wire:model.live.debounce.200ms="firmaArama"
                                placeholder="Firma ara…"
                                style="width:100%;margin-bottom:.5rem;padding:.35rem .55rem;border:1px solid rgb(128 116 148 / .35);border-radius:.45rem;font-size:.78rem;background:transparent"
                            >
                            <div style="display:flex;flex-direction:column;gap:.3rem;max-height:320px;overflow:auto">
                                @forelse ($this->filtrelenmisFirmalar as $firma)
                                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem">
                                        <input type="checkbox" wire:model="firmaSecim.{{ $firma->id }}">
                                        {{ $firma->unvan }}
                                    </label>
                                @empty
                                    <span style="font-size:.78rem;color:rgb(95 87 112)">Eşleşen firma yok.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div style="padding:0 1rem 1rem">
                        <x-filament::button wire:click="kaydet">Kaydet</x-filament::button>
                    </div>
                @endif
            </div>
        @empty
            <p style="font-size:.85rem;color:rgb(95 87 112)">Henüz kayıt olan başka kullanıcı yok.</p>
        @endforelse
    </div>
</x-filament-panels::page>
