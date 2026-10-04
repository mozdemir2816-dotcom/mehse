@php
    use App\Support\ArsivKurali;

    $girdi = 'width:100%;min-width:0;padding:.55rem .8rem;border-radius:.6rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.9rem';
    $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.8rem;background:var(--sgr-kart, transparent);box-shadow:0 1px 2px rgb(0 0 0 / .05)';
    $baslikStil = 'font-size:.74rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:rgb(107 114 128)';
    $soluk = 'color:rgb(107 114 128)';
    $renk = ['gecikmis' => '#dc2626', 'yaklasan' => '#d97706', 'eksik' => '#6b7280', 'tamam' => '#15803d', 'muaf' => '#6b7280', 'takipsiz' => '#6b7280'];
    $kategoriler = config('arsiv.kategoriler');
    $gecikmisler = $this->sorunlar->where('durum', 'gecikmis');
    $th = 'text-align:left;padding:.5rem .55rem;font-size:.7rem;letter-spacing:.05em;text-transform:uppercase;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .2)';
    $td = 'padding:.5rem .55rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
@endphp

<x-filament-panels::page>
    <style>
        .arsiv-panel { background: #fff; color: #111827; }
        .dark .arsiv-panel { background: #18181b; color: #f4f4f5; }
        .arsiv-kart:hover { border-color: rgb(59 130 246 / .5) !important; }
        :root { --sgr-kart: #fff; }
        .dark { --sgr-kart: rgb(255 255 255 / .03); }
    </style>

    {{-- ÜST ARAÇLAR --}}
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin-top:-.5rem">
        @if ($kategoriAnahtari)
            <x-filament::button color="gray" icon="heroicon-o-arrow-left" wire:click="kategoriAc(null)">Arşiv</x-filament::button>
        @endif
        <div style="margin-left:auto;display:flex;gap:.5rem;flex-wrap:wrap">
            {{ $this->sablonlarAction }}
            {{ $this->hatirlatmaAction }}
            {{ $this->yeniKayitAction }}
        </div>
    </div>

    <select wire:model.live="firmaId" style="{{ $girdi }};{{ $kart }}">
        <option value="">Tüm Firmalar</option>
        @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
    </select>

    @if (! $kategoriAnahtari)
        {{-- ================= GENEL BAKIŞ ================= --}}
        @if ($gecikmisler->isNotEmpty())
            <details style="color:#dc2626">
                <summary style="cursor:pointer;font-weight:600;font-size:.95rem">⚠ {{ $gecikmisler->count() }} süresi geçmiş</summary>
                <div style="display:flex;flex-direction:column;gap:.3rem;margin-top:.5rem">
                    @foreach ($gecikmisler as $s)
                        <button type="button" wire:click="firmaKategoriAc({{ $s['firma']->id }}, '{{ $s['kategori'] }}')"
                            style="text-align:left;background:rgb(220 38 38 / .06);border:1px solid rgb(220 38 38 / .25);border-radius:.5rem;padding:.45rem .65rem;font-size:.82rem;color:inherit;cursor:pointer">
                            <strong>{{ $s['baslik'] }}</strong> · {{ $s['firma']->unvan }}
                            <span style="display:block;{{ $soluk }}">{{ $s['mesaj'] }}</span>
                        </button>
                    @endforeach
                </div>
            </details>
        @endif

        @if ($this->calisanListesiEksik->isNotEmpty())
            <div style="border:1px solid rgb(217 119 6 / .35);background:rgb(217 119 6 / .07);border-radius:.8rem;padding:.9rem 1rem">
                <div style="font-weight:700;color:#b45309">⚠ {{ $this->calisanListesiEksik->count() }} firmada çalışan listesi eksik</div>
                <div style="font-size:.85rem;{{ $soluk }};margin:.2rem 0 .6rem">Bu firmalarda eğitim ve sağlık eksikleri hiç görünmez; önce listeyi kurun.</div>
                <div style="font-size:.8rem;margin-bottom:.6rem">{{ $this->calisanListesiEksik->pluck('unvan')->take(6)->implode(', ') }}@if ($this->calisanListesiEksik->count() > 6) …@endif</div>
                <x-filament::button tag="a" :href="\App\Filament\Resources\Calisans\CalisanResource::getUrl()" icon="heroicon-o-chevron-right" icon-position="after" style="width:100%">Çalışanlara Git</x-filament::button>
            </div>
        @endif

        {{-- Sekmeler --}}
        <div style="display:flex;gap:.25rem;padding:.25rem;border-radius:.7rem;background:rgb(107 114 128 / .08);overflow-x:auto">
            @foreach (\App\Filament\Pages\DokumanYonetimi::SEKMELER as $anahtar => $ad)
                <button type="button" wire:click="$set('sekme', '{{ $anahtar }}')"
                    style="flex:1;white-space:nowrap;padding:.5rem .8rem;border-radius:.55rem;border:none;cursor:pointer;font-size:.88rem;{{ $sekme === $anahtar ? 'background:var(--sgr-kart, #fff);box-shadow:0 1px 3px rgb(0 0 0 / .12);font-weight:600' : 'background:transparent;color:rgb(107 114 128)' }}">
                    {{ $ad }}@if ($anahtar === 'takip' && $this->sorunlar->isNotEmpty()) <span style="font-size:.72rem;color:#dc2626">({{ $this->sorunlar->count() }})</span>@endif
                </button>
            @endforeach
        </div>

        @if ($sekme === 'kategoriler')
            @if ($this->tumu->isEmpty())
                <p style="{{ $soluk }};font-size:.92rem">Bu kapsamda henüz arşiv kaydı yok. Bir kategoriye dokunarak ilk belgeyi ekleyin.</p>
            @endif

            @php
                $takipGrubu = config('arsiv.takip_grubu');
                $osgb = collect($kategoriler)->where('grup', $takipGrubu);
            @endphp

            {{-- 1. OSGB ARŞİV EVRAKLARI --}}
            <div>
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.5rem;margin:.4rem 0 .2rem">
                    <span style="{{ $baslikStil }}">{{ config('arsiv.gruplar')[$takipGrubu] }}</span>
                    @if ($firmaId)
                        @php
                            $satir = $this->matris[$firmaId] ?? [];
                            $gerekli = $osgb->keys()->filter(fn ($a) => ! in_array($satir[$a]['durum'] ?? '', ['muaf', 'takipsiz'], true));
                            $tamamSay = $gerekli->filter(fn ($a) => ($satir[$a]['durum'] ?? '') === 'tamam')->count();
                        @endphp
                        <span style="font-size:.85rem;font-weight:600;color:{{ $tamamSay === $gerekli->count() ? '#15803d' : '#b45309' }}">{{ $tamamSay }} / {{ $gerekli->count() }} güncel</span>
                    @endif
                </div>
                <p style="font-size:.8rem;{{ $soluk }};margin-bottom:.6rem">Firmaya imzalatılmış evrakların taranmış / fotoğraflanmış hallerini yükleyin — fiziksel arşivin sistemdeki karşılığı.</p>

                @if ($firmaId)
                    {{-- Firma seçiliyken: kontrol listesi --}}
                    <div style="display:flex;flex-direction:column;gap:.45rem">
                        @foreach ($osgb as $anahtar => $k)
                            @php
                                $d = $this->matris[$firmaId][$anahtar];
                                $g = $d['gecerli'];
                                $imza = $this->kartlar[$anahtar]['imza'];
                            @endphp
                            <div wire:key="ck-{{ $anahtar }}" style="{{ $kart }};padding:.7rem .85rem;display:flex;gap:.7rem;align-items:center;flex-wrap:wrap;border-left:4px solid {{ $renk[$d['durum']] }}">
                                <span style="font-size:1.1rem;width:1.4rem;text-align:center;color:{{ $renk[$d['durum']] }}">{{ ['tamam' => '✓', 'gecikmis' => '⚠', 'yaklasan' => '◷', 'eksik' => '○', 'muaf' => '–', 'takipsiz' => '·'][$d['durum']] }}</span>
                                <button type="button" wire:click="kategoriAc('{{ $anahtar }}')" style="flex:1;min-width:180px;text-align:left;background:none;border:none;padding:0;cursor:pointer;color:inherit">
                                    <span style="display:block;font-weight:600">{{ $k['ad'] }}</span>
                                    <span style="display:block;font-size:.8rem;color:{{ in_array($d['durum'], ['gecikmis', 'yaklasan'], true) ? $renk[$d['durum']] : 'rgb(107 114 128)' }}">
                                        @if ($g){{ $g->baslangic_tarihi?->format('d.m.Y') ?? $g->created_at?->format('d.m.Y') }} · @endif{{ $d['mesaj'] }}
                                    </span>
                                    @if ($imza)<span style="display:block;font-size:.76rem;color:#b45309">{{ $imza }} belge imza bekliyor</span>@endif
                                </button>
                                <div style="display:flex;gap:.35rem">
                                    @if ($g)
                                        <x-filament::button size="sm" color="gray" icon="heroicon-o-eye" wire:click="goruntule({{ $g->id }})">Aç</x-filament::button>
                                    @endif
                                    @if ($d['durum'] !== 'muaf')
                                        <x-filament::button size="sm" icon="heroicon-o-arrow-up-tray"
                                            wire:click="mountAction('yeniKayit', { kategori: '{{ $anahtar }}', firma: {{ $firmaId }}, yontem: 'yukle' })">Yükle</x-filament::button>
                                    @endif
                                </div>
                                @if (in_array($anahtar, ['yillik_calisma_plani', 'yillik_egitim_plani', 'yillik_degerlendirme'], true))
                                    @php $kendiFormu = $this->sablonlar->firstWhere('kategori', $anahtar); @endphp
                                    <div style="flex-basis:100%;display:flex;gap:.9rem;flex-wrap:wrap;padding-left:2.1rem;font-size:.8rem">
                                        <button type="button" wire:click="hazirIndir('{{ $anahtar }}')" style="background:none;border:none;padding:0;cursor:pointer;color:rgb(37 99 235)">⤓ Hazır Excel'i indir (düzeltip yükleyin)</button>
                                        @if ($kendiFormu)
                                            <button type="button" wire:click="mountAction('yeniKayit', { kategori: '{{ $anahtar }}', firma: {{ $firmaId }}, yontem: 'sablon' })" style="background:none;border:none;padding:0;cursor:pointer;color:rgb(37 99 235)">✦ Formumdan üret ({{ $kendiFormu->ad }})</button>
                                        @else
                                            <button type="button" wire:click="mountAction('sablonlar', { kategori: '{{ $anahtar }}' })" style="background:none;border:none;padding:0;cursor:pointer;color:rgb(37 99 235)">＋ Örnek formumu yükle (birebir aynısı üretilsin)</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.7rem">
                        @foreach ($osgb as $anahtar => $k)
                            @include('filament.pages.partials.arsiv-kart', ['anahtar' => $anahtar, 'k' => $k, 'o' => $this->kartlar[$anahtar], 'kart' => $kart, 'firmaSayisi' => $this->kapsamFirmalari->count()])
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 2. DİĞER BELGELER --}}
            <details @if ($this->tumu->contains(fn ($d) => \App\Support\ArsivKurali::kategori($d->kategori)['grup'] !== $takipGrubu)) open @endif>
                <summary style="cursor:pointer;{{ $baslikStil }};margin:.6rem 0 .55rem">{{ config('arsiv.gruplar')['diger'] }}</summary>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.7rem">
                    @foreach (collect($kategoriler)->where('grup', '!=', $takipGrubu) as $anahtar => $k)
                        @include('filament.pages.partials.arsiv-kart', ['anahtar' => $anahtar, 'k' => $k, 'o' => $this->kartlar[$anahtar], 'kart' => $kart, 'firmaSayisi' => $this->kapsamFirmalari->count()])
                    @endforeach
                </div>
            </details>
        @elseif ($sekme === 'takip')
            @forelse ($this->sorunlar as $s)
                <div wire:key="sorun-{{ $s['firma']->id }}-{{ $s['kategori'] }}" style="{{ $kart }};padding:.75rem .9rem;display:flex;gap:.7rem;align-items:center;border-left:4px solid {{ $renk[$s['durum']] }}">
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600">{{ $s['baslik'] }}</div>
                        <div style="font-size:.8rem;{{ $soluk }}">{{ $s['firma']->unvan }}</div>
                        <div style="font-size:.8rem;color:{{ $renk[$s['durum']] }}">{{ ArsivKurali::DURUMLAR[$s['durum']] }} — {{ $s['mesaj'] }}</div>
                    </div>
                    <x-filament::button size="sm" icon="heroicon-o-plus"
                        wire:click="mountAction('yeniKayit', { kategori: '{{ $s['kategori'] }}', firma: {{ $s['firma']->id }} })">Ekle</x-filament::button>
                </div>
            @empty
                <div style="{{ $kart }};padding:1.2rem;text-align:center;{{ $soluk }}">Takip edilecek eksik ya da gecikmiş belge yok. 🎉</div>
            @endforelse
        @elseif ($sekme === 'uyum')
            @php $takipliler = collect($kategoriler)->filter(fn ($k, $a) => $k['kural'] !== 'kayit' && ! in_array($a, $this->haric, true)); @endphp
            <div style="{{ $kart }};overflow-x:auto">
                <table style="border-collapse:collapse;font-size:.8rem;min-width:100%">
                    <tr>
                        <th style="{{ $th }};position:sticky;left:0;background:var(--sgr-kart, #fff);min-width:160px">Firma</th>
                        @foreach ($takipliler as $anahtar => $k)
                            <th style="{{ $th }};text-align:center;min-width:76px;text-transform:none;letter-spacing:0;font-size:.68rem">{{ $k['ad'] }}</th>
                        @endforeach
                    </tr>
                    @foreach ($this->kapsamFirmalari as $f)
                        <tr wire:key="uyum-{{ $f->id }}">
                            <td style="{{ $td }};position:sticky;left:0;background:var(--sgr-kart, #fff);font-weight:600">{{ $f->unvan }}</td>
                            @foreach ($takipliler as $anahtar => $k)
                                @php $d = $this->matris[$f->id][$anahtar]; @endphp
                                <td style="{{ $td }};text-align:center">
                                    <button type="button" wire:click="firmaKategoriAc({{ $f->id }}, '{{ $anahtar }}')" title="{{ ArsivKurali::DURUMLAR[$d['durum']] }} — {{ $d['mesaj'] }}"
                                        style="background:none;border:none;cursor:pointer;font-size:1rem;color:{{ $renk[$d['durum']] }}">
                                        {{ ['tamam' => '✓', 'gecikmis' => '✕', 'yaklasan' => '◷', 'eksik' => '!', 'muaf' => '–', 'takipsiz' => '·'][$d['durum']] }}
                                    </button>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            </div>
            <p style="font-size:.75rem;{{ $soluk }}">✓ tamam · ◷ yaklaşıyor · ! eksik · ✕ gecikmiş · – gerekmiyor (ör. 50'den az çalışan) (takip dışı kategoriler Hatırlatma Ayarları'ndan eklenebilir). Hücreye dokunarak kategoriyi açın.</p>
        @else
            {{-- Tüm Belgeler (eski Doküman Yönetimi listesi) --}}
            <x-filament::section>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.5rem;margin-bottom:.8rem">
                    <input type="search" wire:model.live.debounce.400ms="arama" placeholder="Ara… (başlık, dosya, kişi, not, firma)" style="{{ $girdi }}">
                    <select wire:model.live="kategori" style="{{ $girdi }}">
                        <option value="">Tüm kategoriler</option>
                        @foreach ($kategoriler as $k => $v)<option value="{{ $k }}">{{ $v['ad'] }}</option>@endforeach
                    </select>
                    <select wire:model.live="durum" style="{{ $girdi }}">
                        <option value="">Tüm durumlar</option>
                        <option value="aktif">Aktif</option>
                        <option value="imza">İmza bekliyor</option>
                        <option value="yaklasan">Süresi yaklaşan</option>
                        <option value="dolmus">Süresi dolan</option>
                        <option value="pasif">Pasif</option>
                    </select>
                    <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" wire:click="excelRapor">Excel Rapor</x-filament::button>
                </div>

                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.82rem;min-width:760px">
                        <tr>@foreach (['Belge', 'Kategori', 'Belge tarihi', 'Geçerlilik sonu', 'Durum', ''] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                        @forelse ($this->dokumanlar as $d)
                            <tr wire:key="dok-{{ $d->id }}" style="{{ $d->aktif ? '' : 'opacity:.6' }}">
                                <td style="{{ $td }};max-width:320px">
                                    <button type="button" wire:click="goruntule({{ $d->id }})" style="background:none;border:none;padding:0;cursor:pointer;text-align:left;color:inherit"><strong>{{ $d->etiket() }}</strong></button>
                                    <div style="font-size:.72rem;{{ $soluk }}">{{ $d->firma?->unvan }}@if ($d->kisi_adi) · {{ $d->kisi_adi }}@endif · {{ \Illuminate\Support\Str::limit($d->dosya_adi, 40) }}</div>
                                </td>
                                <td style="{{ $td }}">{{ $d->kategoriEtiketi() }}</td>
                                <td style="{{ $td }};white-space:nowrap">{{ $d->baslangic_tarihi?->format('d.m.Y') ?? '—' }}@if ($d->yil)<div style="font-size:.7rem;{{ $soluk }}">Yıl {{ $d->yil }}</div>@endif</td>
                                <td style="{{ $td }};white-space:nowrap">{{ $d->gecerlilik_sonu?->format('d.m.Y') ?? 'Süresiz' }}</td>
                                <td style="{{ $td }};white-space:nowrap;font-weight:700;color:{{ ['Aktif' => '#15803d', 'Pasif' => '#6b7280', 'Süresi yaklaşıyor' => '#d97706', 'Süresi doldu' => '#dc2626', 'İmza bekliyor' => '#b45309'][$d->durumEtiketi()] ?? 'inherit' }}">{{ $d->durumEtiketi() }}</td>
                                <td style="{{ $td }};width:1%"><div style="display:grid;grid-template-columns:1fr 1fr;gap:.25rem;min-width:170px">
                                    <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="indir({{ $d->id }})">İndir</x-filament::button>
                                    <x-filament::button size="xs" color="gray" wire:click="mountAction('duzenle', { id: {{ $d->id }} })">Düzelt</x-filament::button>
                                    <x-filament::button size="xs" color="gray" wire:click="durumDegistir({{ $d->id }})">{{ $d->aktif ? 'Pasife Al' : 'Aktifleştir' }}</x-filament::button>
                                    <x-filament::button size="xs" color="danger" wire:click="sil({{ $d->id }})" wire:confirm="Kayıt ve dosyası silinsin mi?">Sil</x-filament::button>
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="{{ $td }};text-align:center;{{ $soluk }};padding:1.4rem">Belge bulunamadı. "Yeni Kayıt" ile ekleyin.</td></tr>
                        @endforelse
                    </table>
                </div>
                <p style="font-size:.72rem;{{ $soluk }};margin-top:.6rem">Profilim → Arşiv'e yüklenen dosyalar da burada "Diğer" kategorisinde görünür.</p>
            </x-filament::section>
        @endif
    @else
        {{-- ================= KATEGORİ SAYFASI ================= --}}
        @php
            $k = ArsivKurali::kategori($kategoriAnahtari);
            $detay = $this->kategoriDetay;
            $durum = $detay['durum'];
            $bolum = match ($k['kural']) {
                'yillik_plan' => ArsivKurali::beklenenYil($kategoriAnahtari).' '.(str_contains($k['ad'], 'Plan') ? 'Planları' : 'Belgeleri'),
                'yillik_rapor' => ArsivKurali::beklenenYil($kategoriAnahtari).' Raporu',
                'periyodik' => 'Yürürlükteki Belge',
                'suresiz' => $k['bolum'] ?? 'Yürürlükteki Belgeler',
                default => 'Kayıtlar',
            };
        @endphp

        <div>
            <div style="{{ $baslikStil }};margin-bottom:.55rem">{{ $bolum }}</div>

            @if ($durum && ArsivKurali::takipliMi($kategoriAnahtari))
                @php $g = $durum['gecerli']; @endphp
                <div style="{{ $kart }};padding:.9rem 1rem;display:flex;gap:.75rem;margin-bottom:.6rem;{{ $g ? '' : 'border-style:dashed' }};border-left:4px solid {{ $renk[$durum['durum']] }}">
                    <span style="font-size:1.2rem;color:{{ $renk[$durum['durum']] }}">{{ ['tamam' => '✓', 'gecikmis' => '⚠', 'yaklasan' => '◷', 'eksik' => 'ⓘ', 'muaf' => '–', 'takipsiz' => '·'][$durum['durum']] }}</span>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600">{{ $g ? $g->etiket() : $durum['baslik'] }}</div>
                        <div style="font-size:.85rem;{{ $soluk }}">{{ $durum['mesaj'] }}</div>
                        <div style="display:flex;gap:.4rem;margin-top:.45rem;flex-wrap:wrap">
                            @if ($g)
                                <button type="button" wire:click="goruntule({{ $g->id }})"
                                    style="font-size:.8rem;padding:.2rem .6rem;border-radius:.4rem;border:1px solid rgb(37 99 235 / .4);background:transparent;cursor:pointer;color:rgb(37 99 235)">Aç</button>
                            @endif
                            @if (! $g || in_array($durum['durum'], ['gecikmis', 'yaklasan'], true))
                                <button type="button" wire:click="mountAction('yeniKayit', { kategori: '{{ $kategoriAnahtari }}', firma: {{ $firmaId }} })"
                                    style="font-size:.8rem;padding:.2rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;cursor:pointer;color:inherit">+ {{ $g ? 'Yenile' : 'Ekle' }}</button>
                            @endif
                            <span style="font-size:.8rem;padding:.2rem .6rem;border-radius:.4rem;border:1px dashed rgb(107 114 128 / .3);color:{{ $renk[$durum['durum']] }}">· {{ $g ? ArsivKurali::DURUMLAR[$durum['durum']] : 'Kayıt yok' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            @php $liste = $detay['dosyada']->when($durum && ($durum['gecerli'] ?? null), fn ($c) => $c->reject(fn ($d) => $d->id === $durum['gecerli']->id)); @endphp
            @if ($liste->isNotEmpty())
                @if ($durum && ArsivKurali::takipliMi($kategoriAnahtari))<div style="{{ $baslikStil }};margin:.8rem 0 .45rem;font-size:.68rem">Önceki kayıtlar</div>@endif
                <div style="display:flex;flex-direction:column;gap:.45rem">
                    @foreach ($liste as $d)
                        <button type="button" wire:key="kayit-{{ $d->id }}" wire:click="goruntule({{ $d->id }})"
                            style="{{ $kart }};text-align:left;padding:.7rem .9rem;cursor:pointer;color:inherit;display:flex;gap:.6rem;align-items:center;{{ $d->aktif ? '' : 'opacity:.6' }}">
                            <x-filament::icon icon="heroicon-o-document-text" style="width:1.2rem;height:1.2rem;color:rgb(37 99 235);flex-shrink:0" />
                            <span style="flex:1;min-width:0">
                                <span style="display:block;font-weight:600">{{ $d->etiket() }}</span>
                                <span style="display:block;font-size:.78rem;{{ $soluk }}">
                                    @unless ($firmaId){{ $d->firma?->unvan }} · @endunless
                                    {{ $d->baslangic_tarihi?->format('d.m.Y') ?? $d->created_at?->format('d.m.Y') }}
                                    @if ($d->gecerlilik_sonu) · {{ $d->gecerlilik_sonu->format('d.m.Y') }}'e kadar @endif
                                </span>
                            </span>
                            <span style="font-size:.75rem;font-weight:600;color:{{ ['Süresi doldu' => '#dc2626', 'Süresi yaklaşıyor' => '#d97706', 'Pasif' => '#6b7280'][$d->durumEtiketi()] ?? '#15803d' }}">{{ $d->durumEtiketi() }}</span>
                        </button>
                    @endforeach
                </div>
            @elseif (! $durum || ! ArsivKurali::takipliMi($kategoriAnahtari))
                <div style="{{ $kart }};padding:1.6rem;text-align:center;{{ $soluk }}">Bu bölümde kayıt yok.</div>
            @endif
        </div>

        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
                <span style="{{ $baslikStil }}">Üretilmiş Belgeler @if ($detay['uretilmis']->isNotEmpty())({{ $detay['uretilmis']->count() }})@endif</span>
                <button type="button" wire:click="mountAction('yeniKayit', { kategori: '{{ $kategoriAnahtari }}', yontem: 'sablon' })" style="background:none;border:none;cursor:pointer;font-size:.88rem;color:inherit">+ Belge ekle</button>
            </div>
            <div style="display:flex;gap:.9rem;flex-wrap:wrap;font-size:.8rem;margin-bottom:.45rem">
                @if ($firmaId && \App\Support\ArsivUretici::varMi($kategoriAnahtari))
                    <button type="button" wire:click="hazirIndir('{{ $kategoriAnahtari }}')" style="background:none;border:none;padding:0;cursor:pointer;color:rgb(37 99 235)">⤓ Hazır belgeyi indir (düzeltip "Yeni Kayıt → Belge Yükle" ile ekleyin)</button>
                @endif
                <button type="button" wire:click="mountAction('sablonlar', { kategori: '{{ $kategoriAnahtari }}' })" style="background:none;border:none;padding:0;cursor:pointer;color:rgb(37 99 235)">＋ Örnek formumu yükle</button>
            </div>
            @if ($detay['uretilmis']->isNotEmpty())
                <p style="font-size:.82rem;{{ $soluk }};margin-bottom:.45rem">Şablondan üretilenler; imzalandıktan sonra "Dosyaya ekle" ile arşive alın.</p>
                <div style="display:flex;flex-direction:column;gap:.45rem">
                    @foreach ($detay['uretilmis'] as $d)
                        <div wire:key="uretilen-{{ $d->id }}" style="{{ $kart }};padding:.7rem .9rem;display:flex;gap:.6rem;align-items:center">
                            <button type="button" wire:click="goruntule({{ $d->id }})" style="flex:1;min-width:0;text-align:left;background:none;border:none;cursor:pointer;color:inherit">
                                <span style="display:block;font-weight:600">{{ $d->etiket() }}</span>
                                <span style="display:block;font-size:.78rem;{{ $soluk }}">@unless ($firmaId){{ $d->firma?->unvan }} · @endunless{{ $d->sablon_adi }}</span>
                            </button>
                            <x-filament::button size="sm" color="gray" icon="heroicon-o-document-plus" wire:click="mountAction('dosyayaEkle', { id: {{ $d->id }} })">Dosyaya ekle</x-filament::button>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="{{ $kart }};padding:1.3rem;text-align:center;{{ $soluk }}">Şablondan üretilmiş belge yok.</div>
            @endif
        </div>

        <div x-data="{ acik: false }" style="{{ $kart }};padding:.9rem 1rem;display:flex;gap:.6rem">
            <span style="{{ $soluk }}">ⓘ</span>
            <div style="flex:1">
                <div style="{{ $baslikStil }};margin-bottom:.3rem">Bu kategorinin kuralı</div>
                <div style="font-size:.88rem;{{ $soluk }}" :style="acik ? '' : 'display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden'">{{ $k['kural_metni'] }}</div>
                @if (mb_strlen($k['kural_metni']) > 150)
                    <button type="button" x-on:click="acik = ! acik" x-text="acik ? 'Daha az göster' : 'Devamını göster'" style="background:none;border:none;padding:0;margin-top:.3rem;cursor:pointer;color:rgb(37 99 235);font-size:.85rem"></button>
                @endif
            </div>
        </div>

        @if (! $firmaId && ArsivKurali::takipliMi($kategoriAnahtari))
            <div>
                <div style="{{ $baslikStil }};margin-bottom:.45rem">Firmalara göre durum</div>
                <div style="display:flex;flex-direction:column;gap:.35rem">
                    @foreach ($this->kapsamFirmalari as $f)
                        @php $d = $detay['firmalar'][$f->id]; @endphp
                        <button type="button" wire:key="fd-{{ $f->id }}" wire:click="$set('firmaId', {{ $f->id }})"
                            style="{{ $kart }};text-align:left;padding:.55rem .8rem;cursor:pointer;color:inherit;display:flex;gap:.6rem;align-items:center;border-left:4px solid {{ $renk[$d['durum']] }}">
                            <span style="flex:1">{{ $f->unvan }}<span style="display:block;font-size:.76rem;{{ $soluk }}">{{ $d['mesaj'] }}</span></span>
                            <span style="font-size:.76rem;font-weight:600;color:{{ $renk[$d['durum']] }}">{{ ArsivKurali::DURUMLAR[$d['durum']] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- ================= BELGE GÖRÜNTÜLEYİCİ ================= --}}
    @if ($d = $this->goruntulenen)
        @php
            $uzanti = strtolower(pathinfo($d->dosya_yolu, PATHINFO_EXTENSION));
            $gorsel = in_array($uzanti, ['jpg', 'jpeg', 'png', 'webp'], true);
            $kk = ArsivKurali::kategori($d->kategori);
        @endphp
        <div style="position:fixed;inset:0;z-index:30;background:rgb(0 0 0 / .45);display:flex;align-items:center;justify-content:center;padding:1rem" wire:click.self="goruntule(null)">
            <div class="arsiv-panel" style="width:100%;max-width:520px;max-height:92vh;overflow-y:auto;border-radius:1rem;padding:1.3rem;display:flex;flex-direction:column;gap:.8rem">
                <div style="display:flex;gap:.5rem;align-items:flex-start">
                    <div style="flex:1;text-align:center">
                        <div style="font-size:1.15rem;font-weight:700">{{ $d->etiket() }}</div>
                        <div style="{{ $soluk }}">{{ $d->firma?->unvan }}</div>
                    </div>
                    <button type="button" wire:click="goruntule(null)" style="background:none;border:none;cursor:pointer;font-size:1.2rem;{{ $soluk }}">✕</button>
                </div>

                <div style="border:1px dashed rgb(107 114 128 / .35);border-radius:.8rem;padding:1.2rem;text-align:center">
                    @if ($gorsel && \Illuminate\Support\Facades\Storage::disk('public')->exists($d->dosya_yolu))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($d->dosya_yolu) }}" style="max-width:100%;max-height:320px;border-radius:.5rem">
                    @else
                        <x-filament::icon icon="heroicon-o-document-text" style="width:2.4rem;height:2.4rem;color:rgb(37 99 235);margin:0 auto" />
                    @endif
                    <div style="font-weight:600;margin-top:.5rem;word-break:break-all">{{ $d->dosya_adi }}</div>
                    <div style="font-size:.78rem;{{ $soluk }}">{{ $d->boyutEtiketi() }}</div>
                    <x-filament::button icon="heroicon-o-arrow-down-tray" wire:click="indir({{ $d->id }})" style="margin-top:.6rem">Dosyayı Aç</x-filament::button>
                </div>

                @foreach (array_filter([
                    'Kategori' => $kk['ad'],
                    'Durum' => $d->durumEtiketi(),
                    ($kk['alanlar']['yil'] ?? 'Yıl') => $d->yil,
                    ($kk['alanlar']['kisi'] ?? 'Kişi') => $d->kisi_adi,
                    ($kk['alanlar']['tarih'] ?? 'Belge Tarihi') => $d->baslangic_tarihi?->format('d.m.Y'),
                    'Geçerlilik Sonu' => $d->gecerlilik_sonu?->format('d.m.Y'),
                    'Üretildiği şablon' => $d->sablon_adi,
                    'Not' => $d->aciklama,
                ], fn ($v) => filled($v)) as $etiket => $deger)
                    <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.6rem;padding:.5rem .75rem">
                        <div style="font-size:.75rem;{{ $soluk }}">{{ $etiket }}</div>
                        <div style="font-size:.95rem">{{ $deger }}</div>
                    </div>
                @endforeach

                @if ($d->imzaBekliyorMu())
                    <x-filament::button color="success" icon="heroicon-o-document-plus" wire:click="mountAction('dosyayaEkle', { id: {{ $d->id }} })">Dosyaya ekle</x-filament::button>
                @endif
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
                    <x-filament::button color="gray" icon="heroicon-o-pencil" wire:click="mountAction('duzenle', { id: {{ $d->id }} })">Düzelt</x-filament::button>
                    <x-filament::button color="danger" outlined icon="heroicon-o-trash" wire:click="sil({{ $d->id }})" wire:confirm="Kayıt ve dosyası silinsin mi?">Sil</x-filament::button>
                    <x-filament::button color="gray" wire:click="goruntule(null)">Kapat</x-filament::button>
                    <x-filament::button icon="heroicon-o-arrow-down-tray" wire:click="indir({{ $d->id }})">İndir</x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
