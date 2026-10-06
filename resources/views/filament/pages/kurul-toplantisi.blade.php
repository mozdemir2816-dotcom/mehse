@php
    $t = $this->toplanti;
    $firma = $this->firma;
    $m = $this->metrikler;
    $roller = \App\Support\KurulUyeleri::roller();
    $girdi = 'width:100%;padding:.45rem .6rem;border-radius:.45rem;border:1px solid var(--border);background:var(--panel-bg, #fff);font-size:.82rem';
    $etiket = 'display:block;font-weight:600;font-size:.75rem;color:var(--text-secondary);margin-bottom:.2rem';
    $durumRenk = ['taslak' => 'gray', 'planlandi' => 'info', 'tamamlandi' => 'success'];
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'isg_kurulu'])

    {{-- AKTİF İŞYERİ BAĞLAMI --}}
    <x-filament::section icon="heroicon-o-building-office-2">
        <x-slot name="heading">Aktif işyeri</x-slot>
        <x-slot name="description">Kurul üyeleri, toplantılar ve tutanaklar yalnız seçilen işyerinden gelir.</x-slot>

        <div class="grid gap-4 md:grid-cols-2 md:items-end">
            <div>
                <label style="{{ $etiket }}">İşyeri</label>
                <select wire:model.live="firmaId" style="{{ $girdi }}">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>

            @if ($firma)
                <div class="flex flex-wrap gap-2 text-xs">
                    <x-filament::badge color="gray">NACE {{ $firma->nace_kodu ?: '—' }}</x-filament::badge>
                    <x-filament::badge :color="$firma->tehlike_sinifi === 'cok_tehlikeli' ? 'danger' : ($firma->tehlike_sinifi === 'tehlikeli' ? 'warning' : 'success')">
                        {{ config('isg.tehlike_siniflari.'.$firma->tehlike_sinifi, '—') }}
                    </x-filament::badge>
                    <x-filament::badge color="info">Toplantı: {{ \App\Support\KurulUyeleri::periyotEtiketi($firma) }}</x-filament::badge>
                    <x-filament::badge :color="($firma->calisan_sayisi ?? 0) >= 50 ? 'warning' : 'gray'">
                        {{ (int) $firma->calisan_sayisi }} çalışan{{ ($firma->calisan_sayisi ?? 0) >= 50 ? ' · kurul zorunlu' : '' }}
                    </x-filament::badge>
                </div>
            @endif
        </div>
    </x-filament::section>

    @if ($firma)
        {{-- METRİKLER --}}
        <div class="grid gap-3 grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Aktif üyeler', $m['aktif_uye'], 'Yeni toplantıya katılımcı olarak aktarılır', 'var(--metric-blue)'],
                ['Planlı toplantılar', $m['planli'], 'Bugün ve sonrası', 'var(--metric-green)'],
                ['Toplam toplantı', $m['toplam'], 'Tarihsel kayıtlar dahil', 'var(--metric-purple)'],
                ['Zorunlu üyeler', $m['eksik'] ? $m['eksik'].' eksik' : 'Tam', $m['eksik'] ? 'Yalnız taslak kaydedilebilir' : 'Resmî kayıt yapılabilir', $m['eksik'] ? 'var(--metric-orange)' : 'var(--metric-green)'],
            ] as [$baslik, $deger, $alt, $renk])
                <div style="background:var(--panel-bg,#fff);border-radius:12px;padding:.9rem 1rem;border-left:4px solid {{ $renk }};box-shadow:0 0 0 1px rgb(15 23 42 / .05)">
                    <div style="font-size:.66rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-label)">{{ $baslik }}</div>
                    <div style="font-size:1.5rem;font-weight:700;color:{{ $renk }};line-height:1.3">{{ $deger }}</div>
                    <div style="font-size:.72rem;color:var(--text-secondary)">{{ $alt }}</div>
                </div>
            @endforeach
        </div>

        @if ($this->eksikZorunlular)
            <div style="border:1px solid rgb(217 119 6 / .35);background:rgb(217 119 6 / .07);border-radius:12px;padding:.8rem 1rem" class="flex flex-wrap items-center gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5" style="color:rgb(217 119 6)" />
                <div class="flex-1 text-sm" style="min-width:14rem">
                    <strong style="color:rgb(180 83 9)">Resmî durum engeli.</strong>
                    Eksik zorunlu üyeler: {{ implode(' · ', $this->eksikZorunlular) }}.
                    Tamamlanmadan toplantılar yalnız <em>Taslak</em> olarak kaydedilebilir.
                </div>
                <x-filament::button size="sm" color="warning" icon="heroicon-o-user-plus" wire:click="mountAction('uyeYonet')">Üyeleri tamamla</x-filament::button>
            </div>
        @endif

        {{-- KURUL ÜYELERİ --}}
        <x-filament::section icon="heroicon-o-user-group" collapsible>
            <x-slot name="heading">Kurul üyeleri ({{ $this->uyeler->count() }})</x-slot>
            <x-slot name="description">İSG Kurulları Hakkında Yönetmelik Md.6 — kalıcı üye listesi. Toplantı oluşturulurken katılımcı olarak kopyalanır.</x-slot>
            <x-slot name="afterHeader">
                <x-filament::button size="sm" color="gray" icon="heroicon-o-user-plus" wire:click="mountAction('uyeYonet')">Üye Yönet</x-filament::button>
            </x-slot>

            @if ($this->uyeler->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="color:var(--text-secondary);font-size:.7rem;text-transform:uppercase;letter-spacing:.05em">
                                <th class="px-3 py-2 text-left">#</th>
                                <th class="px-3 py-2 text-left">Ad soyad</th>
                                <th class="px-3 py-2 text-left">Görevi / unvanı</th>
                                <th class="px-3 py-2 text-left">Kurul rolü</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->uyeler as $i => $u)
                                <tr style="border-top:1px solid var(--border-light)">
                                    <td class="px-3 py-2" style="color:var(--text-label)">{{ $i + 1 }}</td>
                                    <td class="px-3 py-2 font-semibold">{{ $u->ad_soyad }}</td>
                                    <td class="px-3 py-2">{{ $u->gorev ?: '—' }}</td>
                                    <td class="px-3 py-2">
                                        <x-filament::badge :color="($roller[$u->rol]['zorunlu'] ?? false) ? 'info' : 'gray'">{{ $u->rolEtiketi() }}</x-filament::badge>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <x-filament::icon-button icon="heroicon-o-x-mark" color="danger" size="sm" label="Kuruldan çıkar"
                                            wire:click="uyeKaldir({{ $u->id }})" wire:confirm="{{ $u->ad_soyad }} kuruldan çıkarılsın mı? (Geçmiş tutanaklar etkilenmez.)" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-6 text-center text-sm" style="color:var(--text-secondary)">
                    Henüz kurul üyesi yok. <strong>Üye Yönet</strong> ile işveren, İGU, işyeri hekimi, İK sorumlusu ve çalışan temsilcisini ekleyin.
                </div>
            @endif
        </x-filament::section>

        {{-- TOPLANTILAR --}}
        <x-filament::section icon="heroicon-o-calendar-days">
            <x-slot name="heading">Toplantılar ({{ $this->toplantilar->count() }})</x-slot>
            <x-slot name="afterHeader">
                <x-filament::button size="sm" icon="heroicon-o-plus" wire:click="mountAction('toplantiPlanla')">Toplantı Planla</x-filament::button>
            </x-slot>

            @if ($this->toplantilar->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="color:var(--text-secondary);font-size:.7rem;text-transform:uppercase;letter-spacing:.05em">
                                <th class="px-3 py-2 text-left">Tarih</th>
                                <th class="px-3 py-2 text-left">No</th>
                                <th class="px-3 py-2 text-left">Tür / durum</th>
                                <th class="px-3 py-2 text-left">Gündem</th>
                                <th class="px-3 py-2 text-center">Karar</th>
                                <th class="px-3 py-2 text-center">Katılım</th>
                                <th class="px-3 py-2 text-left">Sonraki</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->toplantilar as $tp)
                                @php $secili = $toplantiId === $tp->id; @endphp
                                <tr style="border-top:1px solid var(--border-light);{{ $secili ? 'background:var(--info-bg)' : '' }}">
                                    <td class="px-3 py-2 whitespace-nowrap font-semibold">{{ $tp->tarih?->format('d.m.Y') ?: '—' }}
                                        <div class="text-xs font-normal" style="color:var(--text-label)">{{ $tp->saatAraligi() }}</div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $tp->toplanti_no ?: '—' }}</td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap gap-1">
                                            <x-filament::badge color="gray">{{ $tp->turEtiketi() }}</x-filament::badge>
                                            <x-filament::badge :color="$durumRenk[$tp->durum] ?? 'gray'">{{ $tp->durumEtiketi() }}</x-filament::badge>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2" style="max-width:18rem">
                                        <span class="line-clamp-2">{{ ($tp->gundem ?? [])[0] ?? '—' }}</span>
                                        @if (count($tp->gundem ?? []) > 1)
                                            <span class="text-xs" style="color:var(--text-label)">+{{ count($tp->gundem) - 1 }} madde</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">{{ count($tp->kararlar ?? []) }}</td>
                                    <td class="px-3 py-2 text-center whitespace-nowrap">{{ $tp->katilanSayisi() }}/{{ count($tp->katilimcilar ?? []) }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $tp->sonraki_toplanti?->format('d.m.Y') ?: '—' }}</td>
                                    <td class="px-3 py-2 text-right whitespace-nowrap">
                                        <x-filament::button size="xs" :color="$secili ? 'primary' : 'gray'" icon="heroicon-o-pencil-square" wire:click="toplantiSec({{ $tp->id }})">
                                            {{ $secili ? 'Açık' : 'Aç' }}
                                        </x-filament::button>
                                        <x-filament::icon-button icon="heroicon-o-trash" color="danger" size="sm" label="Sil"
                                            wire:click="toplantiSil({{ $tp->id }})" wire:confirm="Bu toplantı ve tutanağı silinsin mi?" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-6 text-center text-sm" style="color:var(--text-secondary)">
                    Toplantı kaydı yok. <strong>Toplantı Planla</strong> ile ilk toplantıyı oluşturun.
                </div>
            @endif
        </x-filament::section>
    @else
        <p class="text-sm" style="color:rgb(217 119 6)">Devam etmek için bir işyeri seçin.</p>
    @endif

    @if ($t)
        {{-- SEÇİLİ TOPLANTI: KÜNYE --}}
        <x-filament::section icon="heroicon-o-document-text">
            <x-slot name="heading">Toplantı {{ $t->toplanti_no ?: '' }} — {{ $t->tarih?->format('d.m.Y') }}</x-slot>
            <x-slot name="description">Tutanak künyesi. PDF / Excel sayfanın üstündeki düğmelerden indirilir.</x-slot>
            <x-slot name="afterHeader">
                <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" label="Kapat" wire:click="toplantiKapat" />
            </x-slot>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div><label style="{{ $etiket }}">Toplantı no</label><input type="text" wire:model="toplantiNo" style="{{ $girdi }}"></div>
                <div><label style="{{ $etiket }}">Belge no</label><input type="text" wire:model="belgeNo" style="{{ $girdi }}"></div>
                <div><label style="{{ $etiket }}">Revizyon</label><input type="text" wire:model="revizyonNo" style="{{ $girdi }}"></div>
                <div><label style="{{ $etiket }}">Tarih</label><input type="date" wire:model="tarih" style="{{ $girdi }}"></div>
                <div><label style="{{ $etiket }}">Başlangıç</label><input type="time" wire:model="saat" style="{{ $girdi }}"></div>
                <div><label style="{{ $etiket }}">Bitiş</label><input type="time" wire:model="bitisSaati" style="{{ $girdi }}"></div>
                <div><label style="{{ $etiket }}">Toplantı yeri</label><input type="text" wire:model="yer" style="{{ $girdi }}"></div>
                <div><label style="{{ $etiket }}">Toplantı başkanı</label><input type="text" wire:model="baskan" placeholder="İşveren / vekili" style="{{ $girdi }}"></div>
                <div>
                    <label style="{{ $etiket }}">Tür</label>
                    <select wire:model="tur" style="{{ $girdi }}">
                        @foreach (config('isg.kurul_toplantisi.turler') as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label style="{{ $etiket }}">Durum</label>
                    <select wire:model="durum" style="{{ $girdi }}">
                        @foreach (config('isg.kurul_toplantisi.durumlar') as $k => $v)
                            <option value="{{ $k }}" @disabled($this->eksikZorunlular && $k !== 'taslak')>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label style="{{ $etiket }}">Sonraki toplantı</label><input type="date" wire:model="sonrakiToplanti" style="{{ $girdi }}"></div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <label style="{{ $etiket }}">Notlar</label>
                    <textarea wire:model="notlar" rows="2" style="{{ $girdi }};font-family:inherit"></textarea>
                </div>
            </div>
            <div class="mt-3">
                <x-filament::button size="sm" icon="heroicon-o-check" wire:click="toplantiBilgileriniKaydet">Kaydet</x-filament::button>
            </div>
        </x-filament::section>

        {{-- KATILIMCILAR --}}
        <x-filament::section icon="heroicon-o-user-group">
            <x-slot name="heading">Katılım ({{ $t->katilanSayisi() }} / {{ count($t->katilimcilar ?? []) }})</x-slot>
            <x-slot name="description">Toplantı anındaki kurul üyeleri — tarihsel kopya. Üyeler değiştiyse "Üyeleri yeniden aktar".</x-slot>
            <x-slot name="afterHeader">
                <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-path" wire:click="uyeleriAktar">Üyeleri yeniden aktar</x-filament::button>
            </x-slot>

            @if ($t->katilimcilar)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        @foreach ($t->katilimcilar as $i => $k)
                            <tr style="border-top:{{ $i ? '1px solid var(--border-light)' : 'none' }}">
                                <td class="px-3 py-2 font-semibold">{{ ($k['ad_soyad'] ?? '') ?: '—' }}</td>
                                <td class="px-3 py-2">{{ ($k['gorev'] ?? null) ?: '—' }}</td>
                                <td class="px-3 py-2">
                                    @if (filled($k['rol'] ?? null))
                                        <x-filament::badge color="gray">{{ config('isg.kurul_toplantisi.roller.'.$k['rol'].'.ad', $k['rol']) }}</x-filament::badge>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <x-filament::button size="xs" :color="($k['katildi'] ?? false) ? 'success' : 'danger'" wire:click="katilimToggle({{ $i }})">
                                        {{ ($k['katildi'] ?? false) ? 'Katıldı' : 'Katılmadı' }}
                                    </x-filament::button>
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <x-filament::button size="xs" color="gray" icon="heroicon-o-pencil" wire:click="katilimciDuzenle({{ $i }})">Düzenle</x-filament::button>
                                    <x-filament::icon-button icon="heroicon-o-x-mark" color="danger" size="sm" label="Çıkar" wire:click="katilimciSil({{ $i }})" />
                                </td>
                            </tr>
                            @if ($duzenlenenKatilimciIndex === $i)
                                <tr>
                                    <td colspan="5" class="p-2">
                                        <div style="border:1px solid var(--primary);background:var(--info-bg);border-radius:12px;padding:.9rem">
                                            <div class="mb-2 text-sm font-semibold">Katılımcıyı düzenle (yalnız bu toplantı)</div>
                                            <div class="grid gap-2 sm:grid-cols-3">
                                                <div><label style="{{ $etiket }}">Ad soyad</label><input type="text" wire:model="katilimciForm.ad_soyad" style="{{ $girdi }}"></div>
                                                <div><label style="{{ $etiket }}">Görevi</label><input type="text" wire:model="katilimciForm.gorev" style="{{ $girdi }}"></div>
                                                <div>
                                                    <label style="{{ $etiket }}">Kuruldaki görevi</label>
                                                    <select wire:model="katilimciForm.rol" style="{{ $girdi }}">
                                                        <option value="">Kurul Üyesi</option>
                                                        @foreach (config('isg.kurul_toplantisi.roller') as $rk => $rt)<option value="{{ $rk }}">{{ $rt['ad'] }}</option>@endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mt-2 flex gap-2">
                                                <x-filament::button size="sm" wire:click="katilimciGuncelle">Güncelle</x-filament::button>
                                                <x-filament::button size="sm" color="gray" wire:click="katilimciDuzenlemeIptal">Vazgeç</x-filament::button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
            @endif

            @if ($this->calisanlar->isNotEmpty())
                <div class="mt-3">
                    <select x-on:change="if ($event.target.value) { $wire.katilimHizliEkle(+$event.target.value); $event.target.value = '' }" style="{{ $girdi }}">
                        <option value="">+ Firma personelinden katılımcı ekle…</option>
                        @foreach ($this->calisanlar as $c)
                            <option value="{{ $c->id }}">{{ $c->ad_soyad }}{{ $c->gorev ? ' — '.$c->gorev : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="mt-2 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                <input type="text" wire:model="yeniKatilimciAd" placeholder="Kurul dışı katılımcı — ad soyad" style="{{ $girdi }}">
                <select wire:model="yeniKatilimciGorev" style="{{ $girdi }}">
                    <option value="">Görev seçin</option>
                    @foreach ($this->katilimciGorevleri as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach
                </select>
                <x-filament::button size="sm" color="gray" wire:click="katilimciEkle">Ekle</x-filament::button>
            </div>
        </x-filament::section>

        {{-- GÜNDEM --}}
        <x-filament::section icon="heroicon-o-clipboard-document-list">
            <x-slot name="heading">Gündem</x-slot>

            <div class="mb-3 grid gap-2 sm:grid-cols-[1fr_auto]">
                <input type="text" wire:model="yeniGundemMaddesi" wire:keydown.enter="gundemEkle" placeholder="Gündem maddesi yazın" style="{{ $girdi }}">
                <x-filament::button size="sm" wire:click="gundemEkle">+ Ekle</x-filament::button>
            </div>

            <details class="mb-3">
                <summary class="cursor-pointer text-sm font-semibold" style="color:var(--text-secondary)">Hazır gündem maddeleri</summary>
                <div class="mt-2 flex flex-col gap-2">
                    @foreach ($this->hazirGundemMaddeleri as $kategori => $maddeler)
                        <div>
                            <div class="mb-1 text-xs font-bold" style="color:var(--primary)">{{ $kategori }}</div>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($maddeler as $madde)
                                    <x-filament::button size="xs" color="gray" wire:click="hazirGundemEkle('{{ addslashes($madde) }}')">+ {{ $madde }}</x-filament::button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </details>

            @if ($t->gundem)
                <div class="flex flex-col gap-2">
                    @foreach ($t->gundem as $i => $madde)
                        @if ($duzenlenenGundemIndex === $i)
                            <div class="flex flex-wrap items-center gap-2" style="border:1px solid var(--primary);background:var(--info-bg);border-radius:10px;padding:.5rem .75rem">
                                <strong class="text-sm">{{ $i + 1 }}.</strong>
                                <input type="text" wire:model="gundemDuzenMetni" wire:keydown.enter="gundemGuncelle" style="{{ $girdi }};flex:1;min-width:12rem">
                                <x-filament::button size="xs" wire:click="gundemGuncelle">Güncelle</x-filament::button>
                                <x-filament::button size="xs" color="gray" wire:click="gundemDuzenlemeIptal">Vazgeç</x-filament::button>
                            </div>
                        @else
                            <div class="flex flex-wrap items-center gap-2" style="border:1px solid var(--border);border-radius:10px;padding:.5rem .75rem">
                                <span class="flex-1 text-sm" style="min-width:12rem"><strong>{{ $i + 1 }}.</strong> {{ $madde }}</span>
                                <x-filament::button size="xs" color="gray" icon="heroicon-o-chat-bubble-left-ellipsis" wire:click="kararFormuAc({{ $i }})">Karar yaz</x-filament::button>
                                <x-filament::button size="xs" color="gray" icon="heroicon-o-pencil" wire:click="gundemDuzenle({{ $i }})">Düzenle</x-filament::button>
                                <x-filament::icon-button icon="heroicon-o-x-mark" color="danger" size="sm" label="Sil" wire:click="gundemSil({{ $i }})" />
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-sm" style="color:var(--text-secondary)">Henüz gündem maddesi eklenmedi.</p>
            @endif

            @if ($kararGundemIndex !== null && isset($t->gundem[$kararGundemIndex]))
                <div class="mt-3" style="border:1px solid var(--primary);background:var(--info-bg);border-radius:12px;padding:.9rem">
                    <div class="mb-2 text-sm font-semibold">Karar: {{ $t->gundem[$kararGundemIndex] }}</div>
                    <textarea wire:model="yeniKararMetni" rows="2" placeholder="Karar metni" style="{{ $girdi }};font-family:inherit"></textarea>
                    <div class="mt-2 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                        <input type="text" wire:model="yeniKararSorumlu" placeholder="Sorumlu" style="{{ $girdi }}">
                        <input type="date" wire:model="yeniKararTermin" style="{{ $girdi }}">
                        @if ($this->aiAktif)
                            <x-filament::button size="sm" color="gray" wire:click="kararAiOner">✨ AI öner</x-filament::button>
                        @endif
                    </div>
                    <div class="mt-2 flex gap-2">
                        <x-filament::button size="sm" wire:click="kararEkle">Karar ekle</x-filament::button>
                        <x-filament::button size="sm" color="gray" wire:click="$set('kararGundemIndex', null)">Vazgeç</x-filament::button>
                    </div>
                </div>
            @endif
        </x-filament::section>

        {{-- KARARLAR --}}
        <x-filament::section icon="heroicon-o-check-circle">
            <x-slot name="heading">Kararlar ve takip ({{ count($t->kararlar ?? []) }})</x-slot>

            @if ($t->kararlar)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="color:var(--text-secondary);font-size:.7rem;text-transform:uppercase;letter-spacing:.05em">
                                <th class="px-3 py-2 text-left">Gündem</th>
                                <th class="px-3 py-2 text-left">Karar</th>
                                <th class="px-3 py-2 text-left">Sorumlu</th>
                                <th class="px-3 py-2 text-left">Termin</th>
                                <th class="px-3 py-2 text-left">Durum</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($t->kararlar as $i => $k)
                                <tr style="border-top:1px solid var(--border-light)">
                                    <td class="px-3 py-2">{{ $k['gundem_maddesi'] ?? '' }}</td>
                                    <td class="px-3 py-2">{{ $k['karar_metni'] ?? '' }}</td>
                                    <td class="px-3 py-2">{{ ($k['sorumlu'] ?? null) ?: '—' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ ($k['termin'] ?? null) ?: '—' }}</td>
                                    <td class="px-3 py-2">
                                        <select x-on:change="$wire.kararDurumGuncelle({{ $i }}, $event.target.value)" style="{{ $girdi }};width:auto;padding:.25rem .45rem">
                                            <option value="beklemede" @selected(($k['durum'] ?? '') === 'beklemede')>Beklemede</option>
                                            <option value="devam_ediyor" @selected(($k['durum'] ?? '') === 'devam_ediyor')>Devam ediyor</option>
                                            <option value="tamamlandi" @selected(($k['durum'] ?? '') === 'tamamlandi')>Tamamlandı</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-right whitespace-nowrap">
                                        <x-filament::button size="xs" color="gray" icon="heroicon-o-pencil" wire:click="kararDuzenle({{ $i }})">Düzenle</x-filament::button>
                                        <x-filament::icon-button icon="heroicon-o-x-mark" color="danger" size="sm" label="Sil" wire:click="kararSil({{ $i }})" />
                                    </td>
                                </tr>
                                @if ($duzenlenenKararIndex === $i)
                                    <tr>
                                        <td colspan="6" class="p-2">
                                            <div style="border:1px solid var(--primary);background:var(--info-bg);border-radius:12px;padding:.9rem">
                                                <div class="mb-2 text-sm font-semibold">Kararı düzenle</div>
                                                <label style="{{ $etiket }}">Gündem maddesi</label>
                                                <input type="text" wire:model="yeniKararGundem" list="kurul-gundem-listesi" style="{{ $girdi }}">
                                                <datalist id="kurul-gundem-listesi">
                                                    @foreach ($t->gundem ?? [] as $g)<option value="{{ $g }}"></option>@endforeach
                                                </datalist>
                                                <label style="{{ $etiket }};margin-top:.5rem;display:block">Karar metni</label>
                                                <textarea wire:model="yeniKararMetni" rows="3" placeholder="Karar metni" style="{{ $girdi }};font-family:inherit"></textarea>
                                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                                    <input type="text" wire:model="yeniKararSorumlu" placeholder="Sorumlu" style="{{ $girdi }}">
                                                    <input type="date" wire:model="yeniKararTermin" style="{{ $girdi }}">
                                                </div>
                                                <div class="mt-2 flex gap-2">
                                                    <x-filament::button size="sm" wire:click="kararGuncelle">Güncelle</x-filament::button>
                                                    <x-filament::button size="sm" color="gray" wire:click="kararDuzenlemeIptal">Vazgeç</x-filament::button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm" style="color:var(--text-secondary)">Henüz karar alınmadı — bir gündem maddesinden "Karar yaz" ile ekleyin.</p>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
