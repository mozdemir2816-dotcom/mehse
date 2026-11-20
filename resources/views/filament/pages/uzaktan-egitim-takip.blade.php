@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem;line-height:1.5">
        Atanan uzaktan eğitimlerin takibi: kim ne kadar izledi, giriş–çıkış saatleri, ön test ve final puanları.
        Eğitimi tamamlayan çalışanın sertifikası <strong>onaydan sonra</strong> buradan alınır
        (tehlikeli / çok tehlikeli işyerinde 4. konu yüz yüze verilip <strong>İGU Onayı</strong> verilince).
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem">
        <div>
            <label class="mehse-etiket">Firma</label>
            <select wire:model.live="firmaId" style="{{ $girdi }}">
                <option value="">— Tüm firmalar —</option>
                @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
            </select>
        </div>
    </div>


    {{-- ===================== GÖNDERİLENLER (kart listesi) ===================== --}}
    @if ($this->gorunum === 'kart')
        @php
            $tum = $this->tumAtamalar;
            $filtreler = ['hepsi' => 'Tümü', 'devam' => 'Devam eden / başlamadı', 'basarisiz' => 'Sınavda kaldı', 'onay_bekliyor' => 'İGU / pratik onayı bekliyor', 'ih_onay_bekliyor' => 'İH onayı bekliyor', 'tamamlandi' => 'Tamamlandı', 'gecikti' => 'Süresi geçen'];
            $liste = $this->gonderilenler;
        @endphp
        <x-filament::section icon="heroicon-o-paper-airplane" icon-color="primary">
            <x-slot name="heading">Gönderilenler</x-slot>
            <x-slot name="description">Her kart bir çalışanın eğitimidir: ilerleme, son giriş ve son geçerlilik tarihi. "Ayrıntılı kayıt" giriş-çıkış, sınav cevapları ve zaman çizelgesini açar.</x-slot>
            <x-slot name="afterHeader">
                <button type="button" wire:click="$set('gorunum', 'tablo')" style="font-size:.8rem;cursor:pointer;background:none;border:1px solid rgb(107 114 128 / .35);border-radius:.5rem;padding:.35rem .8rem;color:inherit">Tablo görünümü</button>
            </x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.8rem;margin-bottom:.8rem">
                <div>
                    <label class="mehse-etiket" for="gon-ara">Ara</label>
                    <input id="gon-ara" type="search" wire:model.live.debounce.300ms="gonderilenAra" placeholder="Çalışan ya da firma" style="{{ $girdi }}">
                </div>
                <div style="display:flex;align-items:end">
                    <button type="button" wire:click="$refresh" style="{{ $girdi }};cursor:pointer;font-weight:600;margin-top:0">Yenile</button>
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.8rem" role="group" aria-label="Durum süzgeci">
                @foreach ($filtreler as $k => $v)
                    <button type="button" wire:click="$set('genelFiltre', '{{ $k }}')" aria-pressed="{{ $genelFiltre === $k ? 'true' : 'false' }}" style="font-size:.78rem;padding:.3rem .7rem;border-radius:999px;cursor:pointer;min-height:2rem;border:1px solid {{ $genelFiltre === $k ? 'rgb(79 70 229)' : 'rgb(107 114 128 / .35)' }};background:{{ $genelFiltre === $k ? 'rgb(79 70 229)' : 'transparent' }};color:{{ $genelFiltre === $k ? '#fff' : 'inherit' }}">
                        {{ $v }} ({{ $tum->when($this->firmaId, fn ($c) => $c->where('calisan.firma_id', $this->firmaId))->filter(fn ($a) => \App\Filament\Pages\UzaktanEgitimAtama::genelFiltreUyar($a, $k))->count() }})
                    </button>
                @endforeach
            </div>
            @if ($this->firma)
                <div style="margin-bottom:.8rem"><div style="display:flex;flex-wrap:wrap;gap:.4rem">
                    <x-filament::button size="sm" color="primary" icon="heroicon-o-document-text" wire:click="denetimTutanagi">Denetim Tutanağı (PDF)</x-filament::button>
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-table-cells" wire:click="takipExcel">Takip Listesi (Excel)</x-filament::button>
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-key" wire:click="girisBilgileriIndir('excel')" wire:confirm="Kendi şifresini belirlememiş ve eğitimi bitmemiş çalışanlara YENİ geçici şifre üretilir (önceki geçici şifreler geçersiz olur). Devam edilsin mi?">Giriş Bilgileri (Excel)</x-filament::button>
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-printer" wire:click="girisBilgileriIndir('pdf')" wire:confirm="Kendi şifresini belirlememiş ve eğitimi bitmemiş çalışanlara YENİ geçici şifre üretilir (önceki geçici şifreler geçersiz olur). Devam edilsin mi?">Giriş Kartları (PDF)</x-filament::button>
                </div></div>
            @endif

            @if ($liste->isEmpty())
                <p style="font-size:.85rem;color:rgb(107 114 128)">{{ $tum->isEmpty() ? 'Henüz gönderilmiş uzaktan eğitim yok. Atama sekmesinden eğitim gönderin.' : 'Bu süzgece uyan eğitim yok.' }}</p>
            @else
                <div style="display:grid;gap:.7rem">
                    @foreach ($liste as $a)
                        @php
                            $toplam = $a->paket->dersler->count();
                            $biten = $a->ilerlemeler->where('izlendi', true)->count();
                            $sonSinav = $a->sinavSonuclari->first();
                            $sonGiris = $a->girisler->last()?->giris_at;
                            $renk = match ($a->durum) { 'tamamlandi' => '#15803d', 'basarisiz' => '#b91c1c', 'onay_bekliyor' => '#b45309', 'ih_onay_bekliyor' => '#2563eb', default => '#475569' };
                        @endphp
                        <div wire:key="gon-{{ $a->id }}" style="border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:.9rem 1rem">
                            <div style="display:flex;justify-content:space-between;gap:.6rem;flex-wrap:wrap;align-items:flex-start">
                                <div>
                                    <div style="font-weight:800;font-size:1rem">{{ $a->calisan->ad_soyad }}</div>
                                    <div class="mehse-soluk" style="font-size:.8rem">{{ $a->calisan->firma->unvan }} · {{ config('isg.tehlike_siniflari.'.$a->calisan->firma->tehlike_sinifi, $a->calisan->firma->tehlike_sinifi) }} · {{ $a->turEtiketi() }} · {{ \App\Filament\Pages\UzaktanEgitimAtama::telefonMaskele($a->calisan->telefon) }}</div>
                                </div>
                                <span style="border:1px solid {{ $renk }};color:{{ $renk }};border-radius:999px;padding:.1rem .7rem;font-size:.75rem;font-weight:700">{{ $a->durumEtiketi() }}</span>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem 1.2rem;margin-top:.7rem;font-size:.82rem">
                                <div><div class="mehse-soluk">İlerleme</div><strong>Ön test {{ $a->on_test_puani ?? '—' }} · Ders {{ $biten }}/{{ $toplam }} · Son sınav {{ $sonSinav ? $sonSinav->puan : '—' }}</strong></div>
                                <div><div class="mehse-soluk">Gönderim</div><strong>{{ $a->atandi_at?->format('d.m.Y') ?? '—' }}</strong></div>
                                <div><div class="mehse-soluk">Son giriş</div><strong>{{ $sonGiris?->format('d.m.Y H:i') ?? '—' }}</strong></div>
                                <div><div class="mehse-soluk">Son geçerlilik</div><strong>{{ $a->son_tarih?->format('d.m.Y') ?? '—' }}</strong>@if (\App\Filament\Pages\UzaktanEgitimAtama::genelFiltreUyar($a, 'gecikti')) <span style="color:#b91c1c;font-size:.75rem">· geçti</span>@endif</div>
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.8rem;align-items:center">
                                @include('filament.pages.partials.uzaktan-egitim-onay-dugmeleri')
                                @if (in_array($a->durum, ['atandi', 'devam', 'basarisiz'], true))
                                    <a href="{{ $this->hatirlatmaLinki($a) }}" target="_blank" rel="noopener" style="font-size:.8rem;padding:.3rem .7rem;border:1px solid rgb(107 114 128 / .4);border-radius:.45rem;text-decoration:none;color:inherit;min-height:2rem;display:inline-flex;align-items:center">Bilgileri yeniden gönder (WhatsApp)</a>
                                @endif
                                <button type="button" wire:click="detayaGit({{ $a->id }})" style="font-size:.8rem;padding:.3rem .7rem;border:1px solid rgb(79 70 229 / .5);border-radius:.45rem;background:none;color:rgb(79 70 229);font-weight:600;cursor:pointer;min-height:2rem">Ayrıntılı kayıt</button>
                                @if (\App\Support\UzaktanEgitimAtayici::baslamadiMi($a))
                                    <button type="button" wire:click="atamaIptal({{ $a->id }})" wire:confirm="Eğitim iptal edilsin mi? Çalışan henüz başlamadığı için kayıt silinir." style="font-size:.8rem;padding:.3rem .7rem;border:1px solid rgb(185 28 28 / .5);border-radius:.45rem;background:none;color:#b91c1c;cursor:pointer;min-height:2rem">İptal et</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @else
        <div style="display:flex;justify-content:flex-end">
            <button type="button" wire:click="$set('gorunum', 'kart')" style="font-size:.8rem;cursor:pointer;background:none;border:1px solid rgb(107 114 128 / .35);border-radius:.5rem;padding:.35rem .8rem;color:inherit">Kart görünümü (Gönderilenler)</button>
        </div>
    @endif

    @if ($this->gorunum === 'tablo' && ! $this->firma && $this->tumAtamalar->isEmpty())
        <p style="font-size:.85rem;color:rgb(107 114 128)">Henüz atanmış uzaktan eğitim yok. Atama sekmesinden eğitim atayın.</p>
    @elseif ($this->gorunum === 'tablo' && $this->firma && $this->atamalar->isEmpty())
        <p style="font-size:.85rem;color:rgb(107 114 128)">Bu firmada atanmış uzaktan eğitim yok.</p>
    @endif

    @if ($this->gorunum === 'tablo' && ! $this->firma && $this->tumAtamalar->isNotEmpty())
        @php
            $tum = $this->tumAtamalar;
            $filtreler = ['hepsi' => 'Tümü', 'devam' => 'Devam eden / başlamadı', 'basarisiz' => 'Sınavda kaldı', 'onay_bekliyor' => 'İGU / pratik onayı bekliyor', 'ih_onay_bekliyor' => 'İH onayı bekliyor', 'tamamlandi' => 'Tamamlandı', 'sertifika' => 'Sertifika hazır', 'gecikti' => 'Son tarihi geçti'];
            $h = 'padding:.45rem .5rem';
        @endphp
        <x-filament::section icon="heroicon-o-users" icon-color="primary">
            <x-slot name="heading">Tüm Firmalar — Eğitim Takibi</x-slot>
            <x-slot name="description">Bütün firmalarınızdaki uzaktan eğitim atamaları. Bir satırda "Detay"a basınca o firma açılır ve çalışanın tüm kayıtları (izleme, giriş-çıkış, ön test ve sınav cevapları) gösterilir.</x-slot>

            <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.8rem">
                @foreach ($filtreler as $k => $v)
                    <button wire:click="$set('genelFiltre', '{{ $k }}')" style="font-size:.78rem;padding:.25rem .65rem;border-radius:999px;cursor:pointer;border:1px solid {{ $genelFiltre === $k ? 'rgb(79 70 229)' : 'rgb(107 114 128 / .3)' }};background:{{ $genelFiltre === $k ? 'rgb(79 70 229)' : 'transparent' }};color:{{ $genelFiltre === $k ? '#fff' : 'inherit' }}">
                        {{ $v }} ({{ $tum->filter(fn ($a) => \App\Filament\Pages\UzaktanEgitimAtama::genelFiltreUyar($a, $k))->count() }})
                    </button>
                @endforeach
            </div>

            @if ($this->genelListe->isEmpty())
                <p style="font-size:.82rem;color:rgb(107 114 128)">Bu süzgece uyan atama yok.</p>
            @else
                <div style="overflow-x:auto">
                    <table style="width:100%;font-size:.8rem;border-collapse:collapse;min-width:900px">
                        <thead><tr style="text-align:left;background:rgb(107 114 128 / .08)">
                            <th style="{{ $h }}">Firma</th><th style="{{ $h }}">Çalışan</th><th style="{{ $h }}">Eğitim</th>
                            <th style="{{ $h }}">İlerleme</th><th style="{{ $h }}">Ön test</th><th style="{{ $h }}">Final</th>
                            <th style="{{ $h }}">Son giriş</th><th style="{{ $h }}">Durum</th><th style="{{ $h }}"></th>
                        </tr></thead>
                        <tbody>
                        @foreach ($this->genelListe as $a)
                            @php
                                $toplam = $a->paket->dersler->count();
                                $biten = $a->ilerlemeler->where('izlendi', true)->count();
                                $son = $a->sinavSonuclari->first();
                                $renk = match ($a->durum) { 'tamamlandi' => 'rgb(21 128 61)', 'basarisiz' => 'rgb(185 28 28)', 'onay_bekliyor' => '#b45309', 'ih_onay_bekliyor' => '#2563eb', default => 'rgb(107 114 128)' };
                            @endphp
                            <tr wire:key="genel-{{ $a->id }}" style="border-top:1px solid rgb(107 114 128 / .15)">
                                <td style="{{ $h }}">{{ \Illuminate\Support\Str::limit($a->calisan->firma->unvan, 30) }}</td>
                                <td style="{{ $h }}">{{ $a->calisan->ad_soyad }}<div class="mehse-soluk">{{ $a->calisan->gorev }}</div></td>
                                <td style="{{ $h }}">{{ \Illuminate\Support\Str::limit($a->paket->ad, 32) }}<div class="mehse-soluk">{{ $a->turEtiketi() }}</div></td>
                                <td style="{{ $h }};min-width:110px">
                                    {{ $biten }}/{{ $toplam }} ders
                                    <div style="height:5px;background:rgb(107 114 128 / .15);border-radius:3px;margin-top:.2rem"><div style="height:5px;border-radius:3px;background:rgb(79 70 229);width:{{ $toplam ? round($biten / $toplam * 100) : 0 }}%"></div></div>
                                </td>
                                <td style="{{ $h }}">{{ $a->on_test_puani !== null ? $a->on_test_puani.' puan' : '—' }}</td>
                                <td style="{{ $h }}">{{ $son ? $son->puan.' puan' : '—' }}@if ($a->sinavSonuclari->isNotEmpty())<div class="mehse-soluk">{{ $a->sinavSonuclari->count() }} deneme</div>@endif</td>
                                <td style="{{ $h }}">{{ $a->girisler->last()?->giris_at?->format('d.m.Y') ?? 'Girmedi' }}</td>
                                <td style="{{ $h }}">
                                    <span style="color:{{ $renk }};font-weight:600">{{ $a->durumEtiketi() }}</span>
                                    @if (\App\Filament\Pages\UzaktanEgitimAtama::genelFiltreUyar($a, 'gecikti'))<div style="font-size:.75rem;color:rgb(185 28 28)">son tarih {{ $a->son_tarih->format('d.m.Y') }} geçti</div>@endif
                                </td>
                                <td style="{{ $h }};white-space:nowrap">
                                    @include('filament.pages.partials.uzaktan-egitim-onay-dugmeleri')
                                    <button wire:click="detayaGit({{ $a->id }})" style="background:none;border:0;color:rgb(79 70 229);cursor:pointer;font-size:.8rem;font-weight:600">Detay</button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif

    @if ($this->firma && $this->atamalar->isNotEmpty())
        @if ($this->gorunum === 'tablo')
        <x-filament::section icon="heroicon-o-chart-bar" icon-color="gray">
            <x-slot name="heading">Atanan Eğitimler — {{ $this->firma->unvan }}</x-slot>
            <x-slot name="description">Her oturumun giriş–çıkış saati ve fiili izleme süresi kaydedilir. Temel İSG eğitimi bitince çalışanın Eğitim Kayıtları'na işlenir (tehlikeli / çok tehlikeli işyerinde 4. konu yüz yüze verilince).</x-slot>
            <x-slot name="afterHeader">
                <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                    <x-filament::button size="sm" color="primary" icon="heroicon-o-document-text" wire:click="denetimTutanagi">Denetim Tutanağı (PDF)</x-filament::button>
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-table-cells" wire:click="takipExcel">Takip Listesi (Excel)</x-filament::button>
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-key" wire:click="girisBilgileriIndir('excel')" wire:confirm="Kendi şifresini belirlememiş ve eğitimi bitmemiş çalışanlara YENİ geçici şifre üretilir (önceki geçici şifreler geçersiz olur). Devam edilsin mi?">Giriş Bilgileri (Excel)</x-filament::button>
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-printer" wire:click="girisBilgileriIndir('pdf')" wire:confirm="Kendi şifresini belirlememiş ve eğitimi bitmemiş çalışanlara YENİ geçici şifre üretilir (önceki geçici şifreler geçersiz olur). Devam edilsin mi?">Giriş Kartları (PDF)</x-filament::button>
                </div>
            </x-slot>
            <div style="overflow-x:auto">
                <table style="width:100%;font-size:.83rem;border-collapse:collapse;min-width:860px">
                    <thead><tr style="text-align:left;background:rgb(107 114 128 / .08)">
                        <th style="padding:.5rem">Çalışan</th><th style="padding:.5rem">Görev</th><th style="padding:.5rem">Eğitim</th>
                        <th style="padding:.5rem">Eğitime Girişler</th><th style="padding:.5rem">İlerleme</th><th style="padding:.5rem">Sınav</th>
                        <th style="padding:.5rem">Tamamlandı</th><th style="padding:.5rem"></th>
                    </tr></thead>
                    <tbody>
                    @foreach ($this->atamalar as $a)
                        <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                            <td style="padding:.5rem">{{ $a->calisan->ad_soyad }}</td>
                            <td style="padding:.5rem">{{ $a->calisan->gorev ?: '—' }}</td>
                            <td style="padding:.5rem">{{ \Illuminate\Support\Str::limit($a->paket->ad, 40) }}<div class="mehse-soluk">{{ $a->turEtiketi() }} · atandı {{ $a->atandi_at?->format('d.m.Y') }}</div></td>
                            <td style="padding:.5rem;vertical-align:top">
                                @php($portal = $this->portalGirisleri->get($a->calisan_id, collect()))
                                @if ($a->girisler->isEmpty() && $portal->isEmpty())
                                    <span class="mehse-soluk-metin">Henüz girmedi</span>
                                @else
                                    <details>
                                        <summary style="cursor:pointer">
                                            {{ $a->girisler->count() }} giriş
                                            @if ($a->girisler->isNotEmpty())<span style="color:rgb(107 114 128);font-size:.75rem">· son {{ $a->girisler->last()->giris_at->format('d.m.Y') }}</span>@endif
                                        </summary>
                                        <div style="font-size:.75rem;margin-top:.3rem;line-height:1.5">
                                            @foreach ($a->girisler as $g)<div>{{ $g->giris_at->format('d.m.Y H:i') }} – {{ $g->cikis_at?->format('H:i') ?? '?' }}@if ($g->izleme_sn) · {{ round($g->izleme_sn / 60) }} dk izleme @endif @if ($g->yoklama_sayisi) · {{ $g->yoklama_sayisi }} katılım cevabı @endif</div>@endforeach
                                            @foreach ($a->ilerlemeler->where('izlendi', true)->sortBy('izlendi_at') as $il)
                                                <div style="color:rgb(21 128 61)">✓ {{ \Illuminate\Support\Str::limit($il->ders?->baslik, 30) }} — {{ $il->izlendi_at?->format('d.m.Y H:i') }}</div>
                                            @endforeach
                                            @if ($portal->isNotEmpty())
                                                <div style="color:rgb(107 114 128);margin-top:.2rem">Portala giriş: {{ $portal->map(fn ($g) => $g->giris_at->format('d.m.Y H:i'))->implode(', ') }}</div>
                                            @endif
                                        </div>
                                    </details>
                                @endif
                            </td>
                            <td style="padding:.5rem">{{ $a->izlenenDersSayisi() }}/{{ $a->toplamDersSayisi() }} ders<div class="mehse-soluk">{{ round($a->ilerlemeler->sum('izlenen_sn') / 60) }} dk fiili izleme</div></td>
                            <td style="padding:.5rem">
                                {{ $a->sonSinav() ? $a->sonSinav()->puan.' puan' : '—' }}
                                <div class="mehse-soluk">
                                    @if ($a->on_test_puani !== null)ön test {{ $a->on_test_puani }} · @endif
                                    {{ $a->sinavSonuclari()->count() }} deneme
                                    @if ($a->yeniden_baslatma) · <span style="color:#b91c1c">{{ $a->yeniden_baslatma }} kez baştan</span>@endif
                                </div>
                            </td>
                            <td style="padding:.5rem">
                                @if ($a->durum === 'onay_bekliyor')
                                    <span style="color:#b45309;font-weight:700">⏳ Sınav geçti · {{ $a->durumEtiketi() }}</span>
                                @elseif ($a->durum === 'ih_onay_bekliyor')
                                    <span style="color:#2563eb;font-weight:700">⏳ İGU onayladı · İH Onayı Bekliyor</span>
                                    <div class="mehse-soluk">Hekim: {{ $a->calisan?->firma?->isyeriHekimi?->ad_soyad ?? 'firmada kayıtlı değil' }}</div>
                                @elseif ($a->durum === 'tamamlandi')
                                    <span style="color:rgb(21 128 61);font-weight:700">☑ Tamamlandı</span>
                                    <div class="mehse-soluk">{{ $a->tamamlandi_at?->format('d.m.Y') }}@if ($a->egitimKaydinaIslenirMi()) · kayda işlendi @elseif ($a->dorduncuKonuYuzYuzeMi()) · <span style="color:#b45309">1–3. konular tamam, 4. konu yüz yüze bekleniyor</span> @endif</div>
                                @else
                                    <span style="font-size:.75rem;color:{{ $a->durum === 'basarisiz' ? 'rgb(185 28 28)' : 'rgb(107 114 128)' }}">
                                        ☐ {{ $a->durumEtiketi() }}@if ($a->gecikti()) · gecikti @endif
                                    </span>
                                @endif
                            </td>
                            <td style="padding:.5rem;white-space:nowrap">
                                @include('filament.pages.partials.uzaktan-egitim-onay-dugmeleri')
                                <button wire:click="detayAc({{ $a->id }})" style="background:none;border:0;color:rgb(79 70 229);cursor:pointer;font-size:.8rem;font-weight:600">{{ $this->detayId === $a->id ? 'Detayı Kapat' : 'Detay' }}</button>
                                <button wire:click="sifreYenile({{ $a->calisan_id }})" wire:confirm="Yeni geçici şifre üretilsin mi?" style="background:none;border:0;color:rgb(107 114 128);cursor:pointer;font-size:.8rem">Şifre</button>
                                <button wire:click="atamaSil({{ $a->id }})" wire:confirm="Atama silinsin mi?" style="background:none;border:0;color:#b91c1c;cursor:pointer;font-size:.8rem">Sil</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        @endif

        @if ($this->detay)
            @include('filament.pages.partials.uzaktan-egitim-takip-detay')
        @endif

        @if ($this->hatirlatilacaklar->isNotEmpty())
            <x-filament::section icon="heroicon-o-bell-alert" icon-color="warning" collapsible>
                <x-slot name="heading">Hatırlatma — eğitimi tamamlamayanlar ({{ $this->hatirlatilacaklar->count() }})</x-slot>
                <x-slot name="description">Her düğme WhatsApp'ı hazır hatırlatma mesajıyla açar (şifre gönderilmez). Telefon numarası kayıtlı değilse kişiyi WhatsApp'ta siz seçersiniz.</x-slot>
                <div style="display:flex;flex-direction:column;gap:.35rem">
                    @foreach ($this->hatirlatilacaklar as $a)
                        <div wire:key="hat-{{ $a->id }}" style="display:flex;justify-content:space-between;align-items:center;gap:.6rem;flex-wrap:wrap;font-size:.83rem;border-top:1px solid rgb(107 114 128 / .15);padding-top:.35rem">
                            <span>{{ $a->calisan->ad_soyad }} <span style="color:rgb(107 114 128);font-size:.75rem">· {{ $a->izlenenDersSayisi() }}/{{ $a->toplamDersSayisi() }} ders · {{ $a->durumEtiketi() }}@if ($a->gecikti()) · <span style="color:#b91c1c">gecikti</span>@endif @unless ($a->calisan->telefon) · telefon yok @endunless</span></span>
                            <a href="{{ $this->hatirlatmaLinki($a) }}" target="_blank" rel="noopener" style="background:#15803d;color:#fff;padding:.2rem .6rem;border-radius:.4rem;font-size:.75rem;text-decoration:none;white-space:nowrap">📲 WhatsApp Hatırlat</a>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
