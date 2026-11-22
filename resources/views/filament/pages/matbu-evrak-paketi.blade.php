<x-filament-panels::page>
    @php
        $firma = $this->firma;
        $ekipmanlar = $this->ekipmanlar;
        $kimyasallar = $this->kimyasallar;
        $ozetsizler = $this->ozetsizler;
        $yeniler = $this->yeniler;
        $yeniSayi = count($yeniler['ekipman']) + count($yeniler['kimyasal']);
    @endphp

    <style>
        .mep-satir { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
        .mep-adim { display:flex; flex-direction:column; gap:.7rem; }
        .mep-girdi { width:100%; max-width:440px; padding:.55rem .75rem; border-radius:.5rem; border:1px solid rgb(128 128 140 / .35); background:transparent; color:inherit; }
        .mep-metin { display:block; width:100%; box-sizing:border-box; padding:.75rem .9rem; border:1px solid rgb(128 128 140 / .35); border-radius:.6rem; background:rgb(128 128 140 / .05); color:inherit; font-family:ui-monospace, Consolas, monospace; font-size:.82rem; line-height:1.5; resize:vertical; }
        .mep-etiket { display:inline-flex; align-items:center; gap:.3rem; font-size:.78rem; font-weight:600; padding:.15rem .5rem; border-radius:.35rem; background:rgb(128 128 140 / .14); white-space:nowrap; }
        .mep-etiket.var { background:rgb(22 163 74 / .12); color:#166534; }
        .mep-etiket.yok { background:rgb(220 38 38 / .1); color:#991b1b; }
        .mep-etiket.bekle { background:rgb(245 158 11 / .16); color:#92400e; }
        .dark .mep-etiket.var { color:#86efac; } .dark .mep-etiket.yok { color:#fca5a5; } .dark .mep-etiket.bekle { color:#fcd34d; }
        .mep-tablo { width:100%; border-collapse:collapse; }
        .mep-tablo td, .mep-tablo th { padding:.55rem .6rem; text-align:left; vertical-align:top; border-bottom:1px solid rgb(128 128 140 / .2); font-size:.88rem; }
        .mep-tablo th { font-size:.78rem; font-weight:600; opacity:.75; }
        .mep-tablo td.sec { width:2.2rem; }
        .mep-tablo input[type=checkbox] { width:1.1rem; height:1.1rem; }
        .mep-soluk { font-size:.78rem; opacity:.7; }
        .mep-ozet { display:flex; flex-wrap:wrap; gap:1rem 2rem; align-items:center; justify-content:space-between; }
        .mep-sayi { font-size:1.05rem; }
        .mep-sayi strong { font-size:1.5rem; font-variant-numeric:tabular-nums; }
    </style>

    <p class="mehse-bolum-aciklama" style="max-width:76ch">
        Firmaya eklenen ekipman ve kimyasalların takip edilmesi gereken tüm evrakı tek PDF'te alın, yazdırıp işverene teslim edin:
        ekipman için çalışma talimatı, aylık günlük kontrol çizelgesi ve güvenlik afişi; kimyasal için güvenlik afişi ve MSDS özeti.
        Paketin ilk sayfası içindekiler ve işverenin imzalayacağı teslim tutanağıdır.
    </p>

    <x-filament::section icon="heroicon-o-building-office-2" icon-color="primary">
        <x-slot name="heading">Firma</x-slot>
        <select wire:model.live="firmaId" class="mep-girdi" aria-label="Firma">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
        </select>
    </x-filament::section>

    @if ($firma)
        <x-filament::section>
            <div class="mep-ozet">
                <div class="mep-sayi" role="status" aria-atomic="true">
                    @if ($yeniSayi)
                        Evrakı gönderilmemiş: <strong>{{ count($yeniler['ekipman']) }}</strong> ekipman, <strong>{{ count($yeniler['kimyasal']) }}</strong> kimyasal
                    @else
                        Tüm evrak gönderilmiş. Yeni ekipman ya da kimyasal eklenince burada görünür.
                    @endif
                </div>
                <div class="mep-satir">
                    @if ($yeniSayi)
                        <x-filament::button icon="heroicon-o-printer" wire:click="yenileriIndir" wire:loading.attr="disabled" wire:target="yenileriIndir">
                            <span wire:loading.remove wire:target="yenileriIndir">Yalnız yenileri indir</span><span wire:loading wire:target="yenileriIndir">PDF hazırlanıyor…</span>
                        </x-filament::button>
                    @endif
                    <x-filament::button color="gray" icon="heroicon-o-check-circle" wire:click="secilenleriIndir" wire:loading.attr="disabled" wire:target="secilenleriIndir">
                        <span wire:loading.remove wire:target="secilenleriIndir">Seçilenleri indir</span><span wire:loading wire:target="secilenleriIndir">PDF hazırlanıyor…</span>
                    </x-filament::button>
                    <x-filament::button color="gray" icon="heroicon-o-document-duplicate" wire:click="tumunuIndir" wire:loading.attr="disabled" wire:target="tumunuIndir">
                        <span wire:loading.remove wire:target="tumunuIndir">Tümünü indir</span><span wire:loading wire:target="tumunuIndir">PDF hazırlanıyor…</span>
                    </x-filament::button>
                </div>
            </div>
            @if ($ozetsizler->isNotEmpty())
                <p class="mep-soluk" style="margin-top:.6rem">MSDS özeti olmayan {{ $ozetsizler->count() }} kimyasal pakete girmez; aşağıdan özetini hazırlayın.</p>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-wrench-screwdriver" icon-color="primary">
            <x-slot name="heading">Ekipmanlar ({{ count($ekipmanlar) }})</x-slot>
            @if ($ekipmanlar === [])
                <p style="font-size:.9rem">Bu firmada aktif ekipman yok. Ekipmanları <a class="text-primary-600 underline" href="{{ \App\Filament\Pages\PeriyodikKontrol::getUrl(['firma' => $firma->id]) }}">Periyodik Kontrol</a> sayfasından ekleyin.</p>
            @else
                <div style="overflow-x:auto">
                    <table class="mep-tablo">
                        <thead><tr><th scope="col"><span class="sr-only">Seç</span></th><th scope="col">Ekipman</th><th scope="col">Belgeler</th><th scope="col">Durum</th></tr></thead>
                        <tbody>
                            @foreach ($ekipmanlar as $d)
                                @php $e = $d['e']; @endphp
                                <tr wire:key="e-{{ $e->id }}">
                                    <td class="sec"><input type="checkbox" value="{{ $e->id }}" wire:model="seciliEkipman" aria-label="{{ $e->ekipman_adi }} seç"></td>
                                    <td>
                                        <div style="font-weight:600">{{ $e->ekipman_adi }}</div>
                                        <div class="mep-soluk">{{ collect([$e->tip, $e->konum])->filter()->implode(' — ') }}</div>
                                    </td>
                                    <td>
                                        <div class="mep-satir">
                                            <span class="mep-etiket {{ $d['talimat'] ? 'var' : 'yok' }}">Talimat: {{ $d['talimat'] ? 'var' : 'yok' }}</span>
                                            <span class="mep-etiket {{ $d['form'] ? 'var' : 'yok' }}">Günlük kontrol: {{ $d['form'] ? 'var' : 'yok' }}</span>
                                            @if (! $d['afis'])
                                                <span class="mep-etiket yok">Afiş: yok</span>
                                            @elseif ($d['afis']->onayBekliyorMu())
                                                <span class="mep-etiket bekle">Afiş: onay bekliyor</span>
                                            @else
                                                <span class="mep-etiket var">Afiş: var</span>
                                            @endif
                                        </div>
                                        @if (! $d['talimat'] || ! $d['form'])
                                            <div class="mep-soluk" style="margin-top:.35rem">Eksik belgeyi <a class="text-primary-600 underline" href="{{ \App\Filament\Pages\TopluMakinePaketi::getUrl() }}">Toplu Makine Paketi</a> ile üretin.</div>
                                        @elseif (! $d['afis'])
                                            <div class="mep-soluk" style="margin-top:.35rem">Afiş için <a class="text-primary-600 underline" href="{{ \App\Filament\Pages\EkipmanAfisleri::getUrl() }}">Ekipman Afişleri</a>'ne resim ve yazı yükleyin.</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($e->isverene_teblig_edildi)
                                            <span class="mep-etiket var">Tebliğ edildi {{ $e->teblig_tarihi?->format('d.m.Y') }}</span>
                                        @elseif ($d['gonderildi'])
                                            <span class="mep-etiket var">Gönderildi {{ $d['gonderildi']->format('d.m.Y') }}</span>
                                        @else
                                            <span class="mep-etiket bekle">Yeni — gönderilmedi</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-beaker" icon-color="primary">
            <x-slot name="heading">Kimyasallar ({{ $kimyasallar->count() }})</x-slot>
            @if ($kimyasallar->isEmpty())
                <p style="font-size:.9rem">Bu firmada kayıtlı kimyasal yok. Kimyasalları <a class="text-primary-600 underline" href="{{ \App\Filament\Pages\KimyasalSicili::getUrl(['firma' => $firma->id]) }}">Kimyasal Sicili</a>'nden ekleyin.</p>
            @else
                <div style="overflow-x:auto">
                    <table class="mep-tablo">
                        <thead><tr><th scope="col"><span class="sr-only">Seç</span></th><th scope="col">Kimyasal</th><th scope="col">MSDS özeti</th><th scope="col">Durum</th><th scope="col">Belgeler</th></tr></thead>
                        <tbody>
                            @foreach ($kimyasallar as $k)
                                <tr wire:key="k-{{ $k->id }}">
                                    <td class="sec">
                                        @if ($k->ozetVarMi())<input type="checkbox" value="{{ $k->id }}" wire:model="seciliKimyasal" aria-label="{{ $k->urun_adi }} seç">@endif
                                    </td>
                                    <td>
                                        <div style="font-weight:600">{{ $k->urun_adi }}</div>
                                        <div class="mep-soluk">{{ collect([$k->cas_no ? 'CAS '.$k->cas_no : null, $k->kullanim_alani])->filter()->implode(' — ') }}</div>
                                    </td>
                                    <td>
                                        @if (! $k->ozetVarMi())
                                            <span class="mep-etiket yok">Yok</span>
                                        @elseif ($k->ozetOnayBekliyorMu())
                                            <div class="mep-adim" style="gap:.35rem;align-items:flex-start">
                                                <span class="mep-etiket bekle">YZ taslağı — onay bekliyor</span>
                                                <x-filament::button size="sm" icon="heroicon-o-check" wire:click="onayla({{ $k->id }})">Özeti onayla</x-filament::button>
                                            </div>
                                        @else
                                            <span class="mep-etiket var">Onaylı {{ $k->ozet_onaylandi_at?->format('d.m.Y') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (! $k->ozetVarMi())
                                            <span class="mep-soluk">Özet hazırlanınca basılır</span>
                                        @elseif ($k->evrak_cikti_at)
                                            <span class="mep-etiket var">Gönderildi {{ $k->evrak_cikti_at->format('d.m.Y') }}</span>
                                        @else
                                            <span class="mep-etiket bekle">Yeni — gönderilmedi</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($k->ozetVarMi())
                                            <div class="mep-satir">
                                                <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="kimyasalAfisi({{ $k->id }})">Afiş</x-filament::button>
                                                <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="msdsOzeti({{ $k->id }})">MSDS özeti</x-filament::button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        @if ($ozetsizler->isNotEmpty())
            <x-filament::section icon="heroicon-o-sparkles" icon-color="primary">
                <x-slot name="heading">Eksik MSDS özetlerini hazırlayın ({{ $ozetsizler->count() }})</x-slot>
                <x-slot name="description">Önce başka firmalarınızda hazır özet var mı bakın; kalanlar için istemi Gemini'ye yapıştırıp cevabı aşağıya kaydedin. Yapay zekâ kotası harcanmaz.</x-slot>
                <div class="mep-adim">
                    <div class="mep-satir">
                        <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="hazirOzetleriKullan">Başka firmalardaki özetleri kullan</x-filament::button>
                        <span class="mep-soluk">Aynı ad ya da CAS numarasıyla eşleşenler kopyalanır.</span>
                    </div>

                    @foreach ($this->istemler as $i => $istem)
                        <div x-data="{ k: false, kopyala() { navigator.clipboard.writeText(this.$refs.m.value).then(() => { this.k = true; setTimeout(() => this.k = false, 2000) }) } }" wire:key="istem-{{ $i }}-{{ md5($istem) }}">
                            <div class="mep-satir" style="margin-bottom:.4rem">
                                <strong style="font-size:.9rem">İstem {{ $i + 1 }}{{ count($this->istemler) > 1 ? ' / '.count($this->istemler) : '' }}</strong>
                                <x-filament::button size="sm" icon="heroicon-o-clipboard-document" x-on:click="kopyala()">
                                    <span x-show="! k">Kopyala</span><span x-show="k" x-cloak>Kopyalandı</span>
                                </x-filament::button>
                                <x-filament::button size="sm" color="gray" tag="a" href="https://gemini.google.com/app" target="_blank" rel="noopener" icon="heroicon-o-arrow-top-right-on-square">Gemini'yi aç</x-filament::button>
                            </div>
                            <textarea x-ref="m" readonly rows="5" class="mep-metin" aria-label="İstem {{ $i + 1 }}">{{ $istem }}</textarea>
                        </div>
                    @endforeach

                    <label for="mep-cevap" style="font-weight:600;font-size:.9rem">Gemini'nin cevabı</label>
                    <textarea id="mep-cevap" class="mep-metin" rows="8" wire:model="cevap" placeholder='[{"id": 12, "urun_adi": "...", ...}]'></textarea>
                    <div class="mep-satir">
                        <x-filament::button icon="heroicon-o-arrow-down-tray" wire:click="ozetleriKaydet" wire:loading.attr="disabled" wire:target="ozetleriKaydet">
                            <span wire:loading.remove wire:target="ozetleriKaydet">Özetleri kaydet</span><span wire:loading wire:target="ozetleriKaydet">Kaydediliyor…</span>
                        </x-filament::button>
                        <span class="mep-soluk">Köşeli parantezle başlayan bölümün tamamını yapıştırın. Birden çok istem varsa her cevabı sırayla kaydedin.</span>
                    </div>
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
