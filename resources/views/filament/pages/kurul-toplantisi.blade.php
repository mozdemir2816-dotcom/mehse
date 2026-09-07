@php
    $mor = 'rgb(139 92 246)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $t = $this->toplanti;
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma seçip yeni bir kurul toplantısı oluşturun veya geçmiş bir toplantıyı seçin;
        katılımcı/gündem/karar bilgilerini doldurup <strong>"PDF İndir"</strong> veya
        <strong>"Excel İndir"</strong> ile resmi tutanağı alın. Her toplantıya firma+yıl
        bazlı bir <strong>toplantı numarası</strong> atanır (elle düzeltilebilir).
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'isg_kurulu'])

    {{-- 1. FİRMA & TOPLANTI SEÇİMİ --}}
    <x-filament::section icon="heroicon-o-users" icon-color="primary">
        <x-slot name="heading">1. Firma & Toplantı</x-slot>

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
        </div>

        @if ($this->firma)
            @if ($this->toplantilar->isNotEmpty())
                <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem">
                    @foreach ($this->toplantilar as $tp)
                        @php $secili = $toplantiId === $tp->id; @endphp
                        <button type="button" wire:click="toplantiSec({{ $tp->id }})"
                            style="padding:.4rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                                border:1px solid {{ $secili ? $mor : 'rgb(107 114 128 / .3)' }};
                                background:{{ $secili ? 'rgb(139 92 246 / .1)' : 'transparent' }}">
                            @if ($tp->toplanti_no)<strong>No {{ $tp->toplanti_no }}</strong> · @endif{{ $tp->tarih?->format('d.m.Y') }} {{ $tp->saat }}
                        </button>
                    @endforeach
                </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr)) auto;gap:.75rem;align-items:end">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Toplantı No</label>
                    <input type="text" wire:model="toplantiNo" placeholder="Örn: 2026/1"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Tarih</label>
                    <input type="date" wire:model="tarih"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Saat</label>
                    <input type="time" wire:model="saat"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Toplantı Yeri</label>
                    <input type="text" wire:model="yer" placeholder="Örn: Toplantı Salonu"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Toplantı Başkanı</label>
                    <input type="text" wire:model="baskan" placeholder="Genellikle işveren/vekili"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent">
                </div>
                @if ($t)
                    <x-filament::button size="sm" wire:click="toplantiBilgileriniKaydet">Kaydet</x-filament::button>
                @else
                    <x-filament::button size="sm" color="success" wire:click="yeniToplanti">+ Yeni Toplantı</x-filament::button>
                @endif
            </div>
        @else
            <p style="font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
        @endif
    </x-filament::section>

    @if ($t)
        {{-- 2. KATILIMCILAR --}}
        <x-filament::section icon="heroicon-o-user-group" icon-color="primary">
            <x-slot name="heading">
                2. Katılımcılar
                <span style="font-weight:400;font-size:.8rem;color:rgb(107 114 128)">({{ $t->katilanSayisi() }} / {{ count($t->katilimcilar ?? []) }} katıldı)</span>
            </x-slot>

            @if ($this->calisanlar->isNotEmpty())
                <div style="font-weight:600;font-size:.8rem;margin-bottom:.3rem">Firma Çalışanlarından Hızlı Ekle</div>
                <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.75rem">
                    @foreach ($this->calisanlar as $c)
                        <x-filament::button size="xs" color="gray" wire:click="katilimHizliEkle({{ $c->id }})">+ {{ $c->ad_soyad }}</x-filament::button>
                    @endforeach
                </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.5rem;margin-bottom:.75rem">
                <input type="text" wire:model="yeniKatilimciAd" placeholder="Ad Soyad"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <select wire:model="yeniKatilimciGorev"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <option value="">Görev seçin</option>
                    @foreach ($this->katilimciGorevleri as $g)
                        <option value="{{ $g }}">{{ $g }}</option>
                    @endforeach
                </select>
                <x-filament::button size="sm" wire:click="katilimciEkle">Ekle</x-filament::button>
            </div>

            @if ($t->katilimcilar)
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Ad Soyad</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Görev</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)">Katılım</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($t->katilimcilar as $i => $k)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $k['ad_soyad'] }}</td>
                            <td style="padding:.3rem .5rem">{{ $k['gorev'] ?: '—' }}</td>
                            <td style="padding:.3rem .5rem;text-align:center">
                                <button type="button" wire:click="katilimToggle({{ $i }})"
                                    style="border:none;cursor:pointer;padding:.15rem .5rem;border-radius:.3rem;font-size:.75rem;color:#fff;
                                        background:{{ ($k['katildi'] ?? false) ? '#10b981' : '#ef4444' }}">
                                    {{ ($k['katildi'] ?? false) ? 'Katıldı' : 'Katılmadı' }}
                                </button>
                            </td>
                            <td style="padding:.3rem .5rem;text-align:right">
                                <button type="button" wire:click="katilimciSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 3. GÜNDEM --}}
        <x-filament::section icon="heroicon-o-clipboard-document-list" icon-color="primary">
            <x-slot name="heading">3. Gündem</x-slot>

            <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem;margin-bottom:.75rem">
                <input type="text" wire:model="yeniGundemMaddesi" placeholder="Gündem maddesi yazın"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <x-filament::button size="sm" wire:click="gundemEkle">+ Ekle</x-filament::button>
            </div>

            <details style="margin-bottom:.75rem">
                <summary style="cursor:pointer;font-size:.82rem;font-weight:600;color:rgb(107 114 128)">Hazır Gündem Maddeleri</summary>
                <div style="margin-top:.5rem;display:flex;flex-direction:column;gap:.6rem">
                    @foreach ($this->hazirGundemMaddeleri as $kategori => $maddeler)
                        <div>
                            <div style="font-size:.75rem;font-weight:700;color:{{ $mor }};margin-bottom:.25rem">{{ $kategori }}</div>
                            <div style="display:flex;flex-wrap:wrap;gap:.3rem">
                                @foreach ($maddeler as $madde)
                                    <button type="button" wire:click="hazirGundemEkle('{{ addslashes($madde) }}')"
                                        style="text-align:left;padding:.3rem .5rem;border-radius:.35rem;cursor:pointer;font-size:.75rem;
                                            border:1px solid rgb(107 114 128 / .3);background:transparent">
                                        + {{ $madde }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </details>

            @if ($t->gundem)
                <div style="display:flex;flex-direction:column;gap:.4rem">
                    @foreach ($t->gundem as $i => $madde)
                        <div style="display:flex;align-items:center;gap:.5rem;{{ $kutu }};padding:.5rem .7rem">
                            <span style="flex:1;font-size:.82rem">{{ $i + 1 }}. {{ $madde }}</span>
                            <x-filament::button size="xs" color="gray" wire:click="kararFormuAc({{ $i }})">Karar Yaz</x-filament::button>
                            <button type="button" wire:click="gundemSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="font-size:.82rem;color:rgb(107 114 128)">Henüz gündem maddesi eklenmedi.</p>
            @endif

            @if ($kararGundemIndex !== null && isset($t->gundem[$kararGundemIndex]))
                <div style="{{ $kutu }};margin-top:.75rem;background:rgb(139 92 246 / .05);border-color:{{ $mor }}">
                    <div style="font-weight:600;font-size:.82rem;margin-bottom:.5rem">Karar: {{ $t->gundem[$kararGundemIndex] }}</div>
                    <textarea wire:model="yeniKararMetni" rows="2" placeholder="Karar metni"
                        style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem;font-family:inherit"></textarea>
                    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.5rem;margin-top:.5rem">
                        <input type="text" wire:model="yeniKararSorumlu" placeholder="Sorumlu"
                            style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                        <input type="date" wire:model="yeniKararTermin"
                            style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                        @if ($this->aiAktif)
                            <x-filament::button size="sm" color="gray" wire:click="kararAiOner">✨ AI Öner</x-filament::button>
                        @endif
                    </div>
                    <div style="margin-top:.5rem;display:flex;gap:.5rem">
                        <x-filament::button size="sm" wire:click="kararEkle">Karar Ekle</x-filament::button>
                        <x-filament::button size="sm" color="gray" wire:click="$set('kararGundemIndex', null)">Vazgeç</x-filament::button>
                    </div>
                </div>
            @endif
        </x-filament::section>

        {{-- 4. KARARLAR --}}
        <x-filament::section icon="heroicon-o-check-circle" icon-color="primary">
            <x-slot name="heading">4. Alınan Kararlar</x-slot>

            @if ($t->kararlar)
                <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                    <tr>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Gündem</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Karar</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Sorumlu</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Termin</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)">Durum</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($t->kararlar as $i => $k)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $k['gundem_maddesi'] }}</td>
                            <td style="padding:.3rem .5rem">{{ $k['karar_metni'] }}</td>
                            <td style="padding:.3rem .5rem">{{ $k['sorumlu'] ?: '—' }}</td>
                            <td style="padding:.3rem .5rem">{{ $k['termin'] ?: '—' }}</td>
                            <td style="padding:.3rem .5rem">
                                <select x-on:change="$wire.kararDurumGuncelle({{ $i }}, $event.target.value)"
                                    style="padding:.2rem .4rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.75rem">
                                    <option value="beklemede" @selected(($k['durum'] ?? '') === 'beklemede')>Beklemede</option>
                                    <option value="devam_ediyor" @selected(($k['durum'] ?? '') === 'devam_ediyor')>Devam Ediyor</option>
                                    <option value="tamamlandi" @selected(($k['durum'] ?? '') === 'tamamlandi')>Tamamlandı</option>
                                </select>
                            </td>
                            <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                                <button type="button" wire:click="kararDuzenle({{ $i }})" style="color:{{ $mor }};cursor:pointer;background:none;border:none;font-size:.78rem">Düzenle</button>
                                <button type="button" wire:click="kararSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                        @if ($duzenlenenKararIndex === $i)
                            <tr>
                                <td colspan="6" style="padding:.5rem">
                                    <div style="{{ $kutu }};background:rgb(139 92 246 / .05);border-color:{{ $mor }}">
                                        <div style="font-weight:600;font-size:.8rem;margin-bottom:.4rem">Kararı düzenle — {{ $k['gundem_maddesi'] }}</div>
                                        <textarea wire:model="yeniKararMetni" rows="2" placeholder="Karar metni"
                                            style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem;font-family:inherit"></textarea>
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.5rem">
                                            <input type="text" wire:model="yeniKararSorumlu" placeholder="Sorumlu"
                                                style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                                            <input type="date" wire:model="yeniKararTermin"
                                                style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                                        </div>
                                        <div style="margin-top:.5rem;display:flex;gap:.5rem">
                                            <x-filament::button size="sm" wire:click="kararGuncelle">Güncelle</x-filament::button>
                                            <x-filament::button size="sm" color="gray" wire:click="kararDuzenlemeIptal">Vazgeç</x-filament::button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </table>
            @else
                <p style="font-size:.82rem;color:rgb(107 114 128)">Henüz karar alınmadı — bir gündem maddesinden "Karar Yaz" ile ekleyin.</p>
            @endif
        </x-filament::section>

        {{-- 5. GEÇMİŞ TOPLANTILAR --}}
        @if ($this->toplantilar->count() > 1)
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Toplantılar</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->toplantilar as $tp)
                        <tr>
                            <td style="padding:.3rem .5rem">
                                @if ($tp->toplanti_no)<strong>No {{ $tp->toplanti_no }}</strong> — @endif
                                {{ $tp->tarih?->format('d.m.Y') }} {{ $tp->saat }} — {{ $tp->yer ?: 'Yer belirtilmedi' }}
                            </td>
                            <td style="padding:.3rem .5rem;text-align:right">
                                <x-filament::button size="xs" color="danger" wire:click="toplantiSil({{ $tp->id }})">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
