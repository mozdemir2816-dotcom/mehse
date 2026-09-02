@php
    $mor = 'rgb(139 92 246)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $rol = $this->rol;
    $grad = 'linear-gradient(135deg, rgb(139 92 246), rgb(99 102 241))';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma ve görev tipini seçin, üye bilgilerini girin; "PDF İndir" ile 6331 sayılı Kanun ve
        ilgili yönetmelikler kapsamındaki görevlendirme yazısını oluşturun.
    </p>

    {{-- SEKME PİLLERİ --}}
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;background:rgb(107 114 128 / .06);padding:.5rem;border-radius:9999px">
        @foreach ($this->roller as $anahtar => $r)
            @php $secili = $rolAnahtari === $anahtar; @endphp
            <button type="button" wire:click="$set('rolAnahtari','{{ $anahtar }}')"
                style="display:inline-flex;align-items:center;gap:.35rem;padding:.45rem .9rem;border-radius:9999px;cursor:pointer;font-size:.78rem;font-weight:600;border:none;white-space:nowrap;
                    color:{{ $secili ? '#fff' : 'inherit' }};
                    background:{{ $secili ? $grad : 'transparent' }}">
                @if ($r['ikon'] ?? null)
                    <x-filament::icon :icon="$r['ikon']" style="width:1rem;height:1rem"/>
                @endif
                {{ $r['ad'] }}
            </button>
        @endforeach
    </div>

    {{-- BAŞLIK BANNER --}}
    <div style="{{ $kutu }};background:{{ $grad }};color:#fff;border:none;display:flex;align-items:center;gap:.6rem">
        <x-filament::icon icon="heroicon-o-document-text" style="width:1.4rem;height:1.4rem"/>
        <span style="font-size:1.05rem;font-weight:800">{{ $rol['ad'] ?? '' }} Atama Yazısı</span>
    </div>

    {{-- 1. FİRMA BİLGİLERİ --}}
    <x-filament::section icon="heroicon-o-building-office" icon-color="primary">
        <x-slot name="heading">1. Firma Bilgileri</x-slot>

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
                <label style="font-weight:600;font-size:.82rem">Tarih</label>
                <input type="date" wire:model="tarih"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">İşveren / İşveren Vekili</label>
                <input type="text" wire:model="isverenVekiliAdi" placeholder="İşveren / vekili adı"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. ÜYE BİLGİLERİ --}}
        <x-filament::section icon="heroicon-o-user-group" icon-color="primary">
            <x-slot name="heading">2. {{ $rol['ad'] ?? '' }} — Üye Bilgileri</x-slot>
            <x-slot name="description">{{ $rol['aciklama'] ?? '' }}</x-slot>

            @if (! $this->ekipMi)
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Hızlı Çalışan Seç</label>
                        <select wire:model.live="tekHizliSecId"
                            style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                            <option value="">Firmaya kayıtlı çalışan yok / manuel gir</option>
                            @foreach ($this->calisanlar as $c)
                                <option value="{{ $c->id }}">{{ $c->ad_soyad }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Ad Soyad <span style="color:#ef4444">*</span></label>
                        <input type="text" wire:model="tekAdSoyad"
                            style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.82rem">T.C. Kimlik No</label>
                        <input type="text" wire:model="tekTc"
                            style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Görev / Unvan</label>
                        <input type="text" wire:model="tekGorev"
                            style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Görev Başlangıç Tarihi</label>
                        <input type="date" wire:model="gorevBaslangic"
                            style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Görev Bitiş Tarihi (Opsiyonel)</label>
                        <input type="date" wire:model="gorevBitis"
                            style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                </div>
                @if ($rolAnahtari === 'calisan_temsilcisi')
                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;cursor:pointer;margin-top:.75rem">
                        <input type="checkbox" wire:model="basTemsilci"> Baş Çalışan Temsilcisi
                    </label>
                @endif
            @else
                @if ($rolAnahtari === 'isg_kurulu')
                    <div style="margin-bottom:1rem">
                        <div style="font-weight:600;font-size:.82rem;margin-bottom:.4rem">İSG Profesyonelleri (Otomatik)</div>
                        @if ($this->firmaProfesyonelleri->isEmpty())
                            <p style="font-size:.8rem;color:rgb(107 114 128)">
                                Bu firmaya İGU / İşyeri Hekimi / DSP atanmamış —
                                <a href="{{ \App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource::getUrl() }}" style="color:{{ $mor }};text-decoration:underline">İSG Profesyonelleri</a>
                                panelinden ekleyip Firma düzenleme sayfasından atayın.
                            </p>
                        @else
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.35rem">
                                @foreach ($this->firmaProfesyonelleri as $p)
                                    @php $psecili = in_array($p->id, $secilenProfesyonelIdler, true); @endphp
                                    <button type="button" wire:click="profesyonelToggle({{ $p->id }})"
                                        style="text-align:left;padding:.45rem .6rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;color:inherit;
                                            border:1px solid {{ $psecili ? $mor : 'rgb(107 114 128 / .3)' }};
                                            background:{{ $psecili ? 'rgb(139 92 246 / .08)' : 'transparent' }}">
                                        {{ $psecili ? '☑' : '☐' }} {{ $p->ad_soyad }}
                                        <span style="color:rgb(107 114 128)">— {{ $p->tipEtiketi() }}</span>
                                        @if ($p->kase_gorseli) <span title="Kaşe yüklü">🖋️</span> @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                    <div style="font-weight:600;font-size:.82rem">Ekip Üyeleri</div>
                    @if ($rolAnahtari === 'isg_kurulu')
                        <x-filament::button size="xs" color="gray" wire:click="firmaProfilindenDoldur">Firma Profilinden Otomatik Doldur</x-filament::button>
                    @endif
                </div>

                @if ($this->calisanlar->isEmpty())
                    <p style="font-size:.82rem;color:rgb(107 114 128)">Bu firmaya kayıtlı çalışan bulunamadı — önce Çalışanlar sekmesinden ekleyin.</p>
                @else
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.35rem">
                        @foreach ($this->calisanlar as $c)
                            @php $secili = in_array($c->id, $secilenCalisanIdler, true); @endphp
                            <div style="display:flex;align-items:center;gap:.3rem;padding:.45rem .3rem;border-radius:.4rem;
                                border:1px solid {{ $secili ? $mor : 'rgb(107 114 128 / .3)' }};
                                background:{{ $secili ? 'rgb(139 92 246 / .08)' : 'transparent' }}">
                                <button type="button" wire:click="calisanToggle({{ $c->id }})" style="flex:1;text-align:left;background:none;border:none;cursor:pointer;font-size:.8rem;color:inherit">
                                    {{ $secili ? '☑' : '☐' }} {{ $c->ad_soyad }}
                                    @if ($c->gorev) <span style="color:rgb(107 114 128)">— {{ $c->gorev }}</span> @endif
                                </button>
                                @if ($secili && $rolAnahtari === 'isg_kurulu')
                                    <select wire:model="kurulGorevleri.{{ $c->id }}"
                                        style="font-size:.72rem;padding:.15rem .3rem;border-radius:.3rem;border:1px solid rgb(107 114 128 / .35);background:transparent;max-width:9rem">
                                        <option value="">Kurul görevi seç</option>
                                        @foreach ($this->kurulGorevSecenekleri as $ganahtar => $gad)
                                            <option value="{{ $ganahtar }}">{{ $gad }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                @if ($secili)
                                    <button type="button" wire:click="basUyeSec({{ $c->id }})" title="Baş üye"
                                        style="background:none;border:none;cursor:pointer;font-size:.9rem;color:{{ $basUyeId === $c->id ? '#f59e0b' : 'rgb(107 114 128 / .4)' }}">★</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </x-filament::section>

        {{-- 3. GEÇMİŞ KAYITLAR --}}
        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Atama Yazıları</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <tr>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Doküman No</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Görev Tipi</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Tarih</th>
                        <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Üye</th>
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach ($this->gecmisKayitlar as $k)
                        <tr>
                            <td style="padding:.35rem .5rem">{{ $k->dokuman_no }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->rolEtiketi() }}</td>
                            <td style="padding:.35rem .5rem">{{ $k->tarih?->format('d.m.Y') }}</td>
                            <td style="padding:.35rem .5rem">{{ count($k->uyeler ?? []) }}</td>
                            <td style="padding:.35rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="danger" wire:click="gecmisPdf({{ $k->id }})">PDF</x-filament::button>
                                @if (\App\Support\AtamaYazisiWordUretici::sablonVarMi($k->rol_anahtari))
                                    <x-filament::button size="xs" color="info" wire:click="gecmisWord({{ $k->id }})">Word</x-filament::button>
                                @endif
                                <x-filament::button size="xs" color="gray" wire:click="gecmisSil({{ $k->id }})">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif

    {{-- BİLGİ: tüm görev tiplerinin yasal dayanağı --}}
    <div style="{{ $kutu }};background:rgb(59 130 246 / .06);border-color:rgb(59 130 246 / .25)">
        <div style="display:flex;align-items:center;gap:.5rem;font-weight:700;color:rgb(37 99 235);margin-bottom:.5rem">
            <x-filament::icon icon="heroicon-o-information-circle" style="width:1.15rem;height:1.15rem"/>
            Bilgi
        </div>
        <ul style="margin:0;padding-left:1.1rem;font-size:.78rem;color:rgb(75 85 99);display:flex;flex-direction:column;gap:.4rem">
            @foreach ($this->roller as $r)
                <li><strong>{{ $r['ad'] }}:</strong> {{ $r['aciklama'] }}</li>
            @endforeach
        </ul>
    </div>
</x-filament-panels::page>
