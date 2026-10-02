@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Bir eğitim paketini firmadaki çalışanlara atayın; sistem portal giriş bilgisini (e-posta + geçici şifre)
        üretir. E-postası olmayan çalışanlara otomatik bir geçici kullanıcı kodu oluşturulur. Çalışanlar
        <strong>{{ url('/egitim') }}</strong> adresinden girer. Paketleri
        <strong>Uzaktan Eğitim Paketleri</strong> ekranından oluşturursunuz.
    </p>

    <x-filament::section icon="heroicon-o-academic-cap" icon-color="primary">
        <x-slot name="heading">Atama</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId" style="{{ $girdi }}">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Eğitim Paketi <span style="color:#ef4444">*</span></label>
                <select wire:model="paketId" style="{{ $girdi }}">
                    <option value="">— Paket seçin —</option>
                    @foreach ($this->paketler as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Eğitim Türü</label>
                <select wire:model="egitimTuru" style="{{ $girdi }}">
                    @foreach (config('isg.uzaktan_egitim.egitim_turleri') as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Son Tarih (opsiyonel)</label>
                <input type="date" wire:model="sonTarih" style="{{ $girdi }}">
            </div>
        </div>

        @if ($this->firma)
            <div style="{{ $kutu }};margin-top:1rem">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                    <span style="font-weight:600;font-size:.85rem">Çalışanlar ({{ count($secilenCalisanlar) }} seçili)</span>
                    <x-filament::button size="xs" color="gray" wire:click="tumunuSec">Tümünü Seç</x-filament::button>
                </div>
                @if ($this->calisanlar->isEmpty())
                    <p style="font-size:.82rem;color:rgb(107 114 128)">Bu firmada aktif çalışan yok.</p>
                @else
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.4rem">
                        @foreach ($this->calisanlar as $c)
                            <label style="display:flex;gap:.5rem;align-items:flex-start;font-size:.85rem;cursor:pointer;padding:.3rem">
                                <input type="checkbox" wire:click="calisanToggle({{ $c->id }})" @checked(in_array($c->id, $secilenCalisanlar))>
                                <span>
                                    {{ $c->ad_soyad }}
                                    @if (blank($c->eposta))<span style="color:#b45309;font-size:.72rem"> (e-posta yok — kullanıcı kodu üretilecek)</span>@endif
                                    @if (filled($c->sifre))<span style="color:rgb(21 128 61);font-size:.72rem"> ✓ hesap var</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <div style="margin-top:.8rem">
                    <x-filament::button wire:click="ata" icon="heroicon-o-paper-airplane">Ata + Giriş Bilgisi Üret</x-filament::button>
                </div>
            </div>
        @endif

        @if ($sonUretilenGiris)
            <div style="{{ $kutu }};margin-top:1rem;border-color:rgb(21 128 61 / .5);background:rgb(21 128 61 / .05)">
                <div style="font-weight:600;font-size:.85rem;margin-bottom:.4rem">Giriş Bilgileri — çalışanlara iletin ({{ url('/egitim') }})</div>
                <table style="width:100%;font-size:.82rem;border-collapse:collapse">
                    <tr style="text-align:left;color:rgb(107 114 128)"><th style="padding:.3rem">Ad Soyad</th><th style="padding:.3rem">E-posta / Kullanıcı Kodu</th><th style="padding:.3rem">Geçici Şifre</th></tr>
                    @foreach ($sonUretilenGiris as $g)
                        <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                            <td style="padding:.3rem">{{ $g['ad_soyad'] }}</td>
                            <td style="padding:.3rem;font-family:monospace">
                                {{ $g['eposta'] }}
                                @unless (str_contains($g['eposta'], '@'))<span style="color:#b45309;font-size:.7rem"> (kod)</span>@endunless
                            </td>
                            <td style="padding:.3rem;font-family:monospace">{{ $g['sifre'] ?? '(mevcut şifre korunuyor)' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </x-filament::section>

    @if ($this->firma && $this->atamalar->isNotEmpty())
        <x-filament::section icon="heroicon-o-chart-bar" icon-color="gray">
            <x-slot name="heading">Atanan Eğitimler — {{ $this->firma->unvan }}</x-slot>
            <x-slot name="description">Çalışanın eğitime girdiği her tarih otomatik kaydedilir. Temel İSG eğitimi (ilk defa / yenileme) bitince çalışanın Eğitim Kayıtları'na tamamlanma tarihiyle işlenir.</x-slot>
            <x-slot name="afterHeader"><x-filament::button size="sm" color="gray" icon="heroicon-o-table-cells" wire:click="takipExcel">Takip Listesi (Excel)</x-filament::button></x-slot>
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
                            <td style="padding:.5rem">{{ \Illuminate\Support\Str::limit($a->paket->ad, 40) }}<div style="font-size:.72rem;color:rgb(107 114 128)">{{ config('isg.uzaktan_egitim.egitim_turleri.'.$a->egitim_turu, $a->egitim_turu) }} · atandı {{ $a->atandi_at?->format('d.m.Y') }}</div></td>
                            <td style="padding:.5rem;vertical-align:top">
                                @php($portal = $this->portalGirisleri->get($a->calisan_id, collect()))
                                @if ($a->girisler->isEmpty() && $portal->isEmpty())
                                    <span style="color:rgb(107 114 128)">Henüz girmedi</span>
                                @else
                                    <details>
                                        <summary style="cursor:pointer">
                                            {{ $a->girisler->count() }} giriş
                                            @if ($a->girisler->isNotEmpty())<span style="color:rgb(107 114 128);font-size:.75rem">· son {{ $a->girisler->last()->giris_at->format('d.m.Y') }}</span>@endif
                                        </summary>
                                        <div style="font-size:.75rem;margin-top:.3rem;line-height:1.5">
                                            @foreach ($a->girisler as $g)<div>{{ $g->giris_at->format('d.m.Y H:i') }}</div>@endforeach
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
                            <td style="padding:.5rem">{{ $a->izlenenDersSayisi() }}/{{ $a->toplamDersSayisi() }} ders</td>
                            <td style="padding:.5rem">{{ $a->sonSinav() ? '%'.$a->sonSinav()->puan : '—' }}</td>
                            <td style="padding:.5rem">
                                @if ($a->durum === 'tamamlandi')
                                    <span style="color:rgb(21 128 61);font-weight:700">☑ Tamamlandı</span>
                                    <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $a->tamamlandi_at?->format('d.m.Y') }}@if (in_array($a->egitim_turu, ['ilk_defa', 'yenileme'], true)) · kayda işlendi @endif</div>
                                @else
                                    <span style="font-size:.75rem;color:{{ $a->durum === 'basarisiz' ? 'rgb(185 28 28)' : 'rgb(107 114 128)' }}">
                                        ☐ {{ $a->durumEtiketi() }}@if ($a->gecikti()) · gecikti @endif
                                    </span>
                                @endif
                            </td>
                            <td style="padding:.5rem;white-space:nowrap">
                                @if ($a->basariliMi())
                                    <button wire:click="belgeIndir({{ $a->id }})" style="background:none;border:0;color:rgb(20 184 166);cursor:pointer;font-size:.8rem">Belge</button>
                                @endif
                                <button wire:click="sifreYenile({{ $a->calisan_id }})" wire:confirm="Yeni geçici şifre üretilsin mi?" style="background:none;border:0;color:rgb(107 114 128);cursor:pointer;font-size:.8rem">Şifre</button>
                                <button wire:click="atamaSil({{ $a->id }})" wire:confirm="Atama silinsin mi?" style="background:none;border:0;color:#ef4444;cursor:pointer;font-size:.8rem">Sil</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
