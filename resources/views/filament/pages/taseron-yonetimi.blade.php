@php
    $inp = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $lbl = 'font-weight:600;font-size:.82rem';
    $th = 'text-align:left;padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3);font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:rgb(107 114 128)';
    $td = 'padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $rozet = fn (string $renk) => "font-size:.72rem;padding:.1rem .5rem;border-radius:999px;white-space:nowrap;border:1px solid {$renk};";
    $yesil = 'rgb(22 163 74 / .55)';
    $sari = 'rgb(245 158 11 / .6)';
    $kirmizi = 'rgb(220 38 38 / .6)';
    $gri = 'rgb(107 114 128 / .45)';
    $tarih = fn ($d) => $d?->format('d.m.Y') ?? '—';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İşyerlerinizin altında çalışan alt işveren ve taşeron firmaların sözleşme, çalışan, belge ve iş izni
        uygunluğunu işyeri bazında yönetin. Taşeron çalışanları ana personel listesine ve personel sayısına eklenmez.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;align-items:end">
        <div>
            <label style="{{ $lbl }}">İşyeri</label>
            <select wire:model.live="firmaId" style="{{ $inp }}">
                <option value="">Tüm işyerleri</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
        </div>
        <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer;padding-bottom:.5rem">
            <input type="checkbox" wire:model.live="pasifleriGoster"> Pasifleri de göster
        </label>
        <div style="text-align:right">
            <x-filament::button icon="heroicon-o-plus" wire:click="mountAction('yeniTaseron')">Yeni Taşeron / Alt İşveren</x-filament::button>
        </div>
    </div>

    {{-- LİSTE --}}
    <x-filament::section icon="heroicon-o-building-office" icon-color="primary">
        <x-slot name="heading">Taşeronlar / Alt İşverenler ({{ $this->taseronlar->count() }})</x-slot>

        @if ($this->taseronlar->isNotEmpty())
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:820px">
                    <tr>
                        @foreach (['Firma', 'Tür', 'İşyeri', 'Sözleşme', 'Çalışan', 'Belge', 'Durum', ''] as $b)
                            <th style="{{ $th }}">{{ $b }}</th>
                        @endforeach
                    </tr>
                    @foreach ($this->taseronlar as $t)
                        @php $eksik = $t->eksikler(); @endphp
                        <tr @if ($seciliId === $t->id) style="background:rgb(124 58 237 / .07)" @endif>
                            <td style="{{ $td }}">
                                <strong>{{ $t->unvan }}</strong>
                                @if ($t->faaliyet)<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $t->faaliyet }}</div>@endif
                            </td>
                            <td style="{{ $td }}">{{ $t->tur === 'taseron' ? 'Taşeron' : 'Alt İşveren' }}</td>
                            <td style="{{ $td }}">{{ $t->firma?->unvan }}</td>
                            <td style="{{ $td }}">
                                {{ $t->sozlesme_no ?: '—' }}
                                <div style="font-size:.72rem;color:{{ in_array($t->sozlesmeDurumu(), ['bitti', 'yaklasan'], true) ? '#dc2626' : 'rgb(107 114 128)' }}">bitiş: {{ $tarih($t->sozlesme_bitis) }}</div>
                            </td>
                            <td style="{{ $td }}">{{ $t->calisanlar->where('aktif', true)->count() }}</td>
                            <td style="{{ $td }}">{{ $t->belgeler->count() }}</td>
                            <td style="{{ $td }}">
                                @if (! $t->aktif)
                                    <span style="{{ $rozet($gri) }}">Pasif</span>
                                @elseif ($eksik)
                                    <span style="{{ $rozet($kirmizi) }}" title="{{ implode("\n", $eksik) }}">Eksik var ({{ count($eksik) }})</span>
                                @else
                                    <span style="{{ $rozet($yesil) }}">Uygun</span>
                                @endif
                            </td>
                            <td style="{{ $td }};text-align:right">
                                <x-filament::button size="xs" wire:click="yonet({{ $t->id }})">Yönet</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @else
            <p style="font-size:.83rem;color:rgb(107 114 128);text-align:center;padding:1rem">Kayıt yok. "Yeni Taşeron / Alt İşveren" ile ekleyin.</p>
        @endif
    </x-filament::section>

    {{-- YÖNET PANELİ --}}
    @if ($t = $this->secili)
        @php $eksik = $t->eksikler(); @endphp
        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
            <x-slot name="heading">{{ $t->unvan }} — {{ $t->turEtiketi() }}</x-slot>
            <x-slot name="description">
                {{ $t->firma?->unvan }} · Sözleşme {{ $t->sozlesme_no ?: '—' }} ({{ $tarih($t->sozlesme_baslangic) }} – {{ $tarih($t->sozlesme_bitis) }})
                @if ($t->yetkili) · Yetkili: {{ $t->yetkili }} {{ $t->telefon }} @endif
            </x-slot>
            <x-slot name="afterHeader">
                <x-filament::button size="xs" color="gray" icon="heroicon-o-x-mark" wire:click="kapat">Kapat</x-filament::button>
            </x-slot>

            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.9rem">
                <x-filament::button size="sm" color="primary" icon="heroicon-o-document-arrow-down" wire:click="uygunlukRaporu">Uygunluk Raporu (PDF)</x-filament::button>
                <x-filament::button size="sm" color="gray" icon="heroicon-o-pencil-square" wire:click="mountAction('taseronDuzenle')">Bilgileri Düzenle</x-filament::button>
                <x-filament::button size="sm" color="{{ $t->aktif ? 'warning' : 'success' }}" wire:click="aktiflikDegistir">{{ $t->aktif ? 'Pasife Al' : 'Aktifleştir' }}</x-filament::button>
                <x-filament::button size="sm" color="danger" wire:click="taseronSil" wire:confirm="Bu kayıt; çalışanları, belgeleri ve iş izni bağlarıyla birlikte silinsin mi?">Sil</x-filament::button>
            </div>

            @if (! $t->aktif)
                <div style="border:1px solid {{ $gri }};border-radius:.6rem;padding:.6rem .9rem;font-size:.82rem">Bu kayıt pasif — uygunluk kontrolü yapılmaz.</div>
            @elseif ($eksik)
                <div style="border:1px solid {{ $kirmizi }};background:rgb(220 38 38 / .06);border-radius:.6rem;padding:.6rem .9rem;font-size:.82rem;color:#b91c1c">
                    <strong>Eksik var:</strong>
                    @foreach ($eksik as $e)<br>• {{ $e }}@endforeach
                </div>
            @else
                <div style="border:1px solid {{ $yesil }};background:rgb(22 163 74 / .06);border-radius:.6rem;padding:.6rem .9rem;font-size:.82rem;color:#15803d">
                    Uygun — sözleşme, zorunlu belgeler ve çalışan eğitim / sağlık kayıtları geçerli.
                </div>
            @endif
        </x-filament::section>

        {{-- ÇALIŞANLAR --}}
        <x-filament::section icon="heroicon-o-users" icon-color="gray">
            <x-slot name="heading">Taşeron Çalışanları</x-slot>
            <x-slot name="description">Ana personel listesine eklenmez. Eğitim / sağlık geçerliliği {{ config('isg.tehlike_siniflari.'.$t->etkinTehlikeSinifi(), 'tehlike sınıfı') }} periyoduna göre hesaplanır.</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:.75rem;align-items:end">
                <div><label style="{{ $lbl }}">Ad soyad *</label><input type="text" wire:model="calisanAd" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Görev</label><input type="text" wire:model="calisanGorev" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Kimlik no (maskelenir)</label><input type="text" wire:model="calisanTc" maxlength="11" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">İşe giriş</label><input type="date" wire:model="calisanIseGiris" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Son İSG eğitimi</label><input type="date" wire:model="calisanEgitim" style="{{ $inp }}"></div>
                <div><label style="{{ $lbl }}">Son sağlık raporu</label><input type="date" wire:model="calisanSaglik" style="{{ $inp }}"></div>
            </div>
            <div style="text-align:right;margin-top:.6rem">
                <x-filament::button size="sm" icon="heroicon-o-user-plus" wire:click="calisanEkle">Çalışan Ekle</x-filament::button>
            </div>

            @if ($t->calisanlar->isNotEmpty())
                <div style="overflow-x:auto;margin-top:.75rem">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:760px">
                        <tr>
                            @foreach (['Ad Soyad', 'Görev', 'Kimlik', 'İSG Eğitimi', 'Sağlık Raporu', 'Durum', ''] as $b)
                                <th style="{{ $th }}">{{ $b }}</th>
                            @endforeach
                        </tr>
                        @foreach ($t->calisanlar as $c)
                            <tr style="{{ $c->aktif ? '' : 'opacity:.55' }}">
                                <td style="{{ $td }}">{{ $c->ad_soyad }}</td>
                                <td style="{{ $td }}">{{ $c->gorev ?: '—' }}</td>
                                <td style="{{ $td }}">{{ $c->tc_maskeli ?: '—' }}</td>
                                <td style="{{ $td }}">
                                    <span style="{{ $rozet($c->egitimGecerliMi() ? $yesil : $kirmizi) }}">{{ $c->isg_egitim_tarihi ? 'bitiş '.$tarih($c->egitimBitis()) : 'Yok' }}</span>
                                </td>
                                <td style="{{ $td }}">
                                    <span style="{{ $rozet($c->saglikGecerliMi() ? $yesil : $kirmizi) }}">{{ $c->saglik_raporu_tarihi ? 'bitiş '.$tarih($c->saglikBitis()) : 'Yok' }}</span>
                                </td>
                                <td style="{{ $td }}">{{ $c->aktif ? 'Aktif' : 'Pasif' }}</td>
                                <td style="{{ $td }};text-align:right;white-space:nowrap">
                                    <x-filament::button size="xs" color="primary" wire:click="mountAction('calisanDuzenle', { id: {{ $c->id }} })">Düzenle</x-filament::button>
                                    <x-filament::button size="xs" color="{{ $c->aktif ? 'warning' : 'success' }}" wire:click="calisanAktiflik({{ $c->id }})">{{ $c->aktif ? 'Pasife Al' : 'Aktifleştir' }}</x-filament::button>
                                    <x-filament::button size="xs" color="danger" wire:click="calisanSil({{ $c->id }})" wire:confirm="Çalışan silinsin mi?">Sil</x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </x-filament::section>

        {{-- BELGELER --}}
        <x-filament::section icon="heroicon-o-paper-clip" icon-color="gray">
            <x-slot name="heading">Belge ve Geçerlilik</x-slot>
            <x-slot name="description">
                {{ $t->turEtiketi() }} için zorunlu belgeler:
                {{ collect(config('isg.taseron.belge_turleri'))->filter(fn ($b) => in_array($t->tur, $b['zorunlu'], true))->pluck('ad')->implode(', ') }}
            </x-slot>
            <x-slot name="afterHeader">
                <x-filament::button size="sm" icon="heroicon-o-plus" wire:click="mountAction('belgeEkle')">Belge Ekle</x-filament::button>
            </x-slot>

            @if ($t->belgeler->isNotEmpty())
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:640px">
                        <tr>
                            @foreach (['Belge', 'Geçerlilik', 'Dosya', 'Not', ''] as $b)
                                <th style="{{ $th }}">{{ $b }}</th>
                            @endforeach
                        </tr>
                        @foreach ($t->belgeler as $b)
                            @php $d = $b->durum(); @endphp
                            <tr>
                                <td style="{{ $td }}">{{ $b->turEtiketi() }}@if ($b->baslik)<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $b->baslik }}</div>@endif</td>
                                <td style="{{ $td }}">
                                    <span style="{{ $rozet(['dolmus' => $kirmizi, 'yaklasan' => $sari, 'gecerli' => $yesil, 'suresiz' => $gri][$d]) }}">
                                        {{ $b->gecerlilik_sonu ? $tarih($b->gecerlilik_sonu) : 'Süresiz' }}{{ $d === 'dolmus' ? ' — doldu' : ($d === 'yaklasan' ? ' — yaklaşıyor' : '') }}
                                    </span>
                                </td>
                                <td style="{{ $td }}">
                                    @if ($b->dosya_yolu)
                                        <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="belgeIndir({{ $b->id }})">{{ \Illuminate\Support\Str::limit($b->dosya_adi, 28) }}</x-filament::button>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="{{ $td }}">{{ $b->notu }}</td>
                                <td style="{{ $td }};text-align:right">
                                    <x-filament::button size="xs" color="danger" wire:click="belgeSil({{ $b->id }})" wire:confirm="Belge ve dosyası silinsin mi?">Sil</x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @else
                <p style="font-size:.83rem;color:rgb(107 114 128)">Belge kaydı yok.</p>
            @endif
        </x-filament::section>

        {{-- İŞ İZNİ BAĞLARI --}}
        <x-filament::section icon="heroicon-o-key" icon-color="gray">
            <x-slot name="heading">Çalışma İzni Bağlantıları</x-slot>
            <x-slot name="description">Bağ kurmak iş izni kaydını değiştirmez; yalnız taşeronla ilişkilendirir.</x-slot>

            <div style="display:flex;gap:.75rem;align-items:end;flex-wrap:wrap">
                <div style="flex:1;min-width:240px">
                    <label style="{{ $lbl }}">İş izni</label>
                    <select wire:model="baglanacakIzinId" style="{{ $inp }}">
                        <option value="">— İş izni seçin —</option>
                        @foreach ($this->baglanabilirIzinler as $id => $ad)
                            <option value="{{ $id }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <x-filament::button size="sm" icon="heroicon-o-link" wire:click="izinBagla">Taşerona Bağla</x-filament::button>
            </div>
            @if (! $this->baglanabilirIzinler && $t->isIzinleri->isEmpty())
                <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:.4rem">Bu işyerinde iş izni kaydı yok — İş İzin Formu'ndan oluşturabilirsiniz.</p>
            @endif

            @if ($t->isIzinleri->isNotEmpty())
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;margin-top:.75rem">
                    <tr>
                        @foreach (['İzin No', 'Çalışma Alanı', 'Başlangıç', ''] as $b)
                            <th style="{{ $th }}">{{ $b }}</th>
                        @endforeach
                    </tr>
                    @foreach ($t->isIzinleri as $iz)
                        <tr>
                            <td style="{{ $td }}">{{ $iz->izin_no ?: '#'.$iz->id }}</td>
                            <td style="{{ $td }}">{{ $iz->calisma_alani ?: '—' }}</td>
                            <td style="{{ $td }}">{{ $iz->baslangic?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td style="{{ $td }};text-align:right">
                                <x-filament::button size="xs" color="danger" wire:click="izinBagKaldir({{ $iz->id }})">Bağı Kaldır</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
