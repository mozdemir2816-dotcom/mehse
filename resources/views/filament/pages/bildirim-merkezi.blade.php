@php
    $girdi = 'padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem';
    $th = 'text-align:left;padding:.55rem .6rem;font-size:.7rem;letter-spacing:.05em;text-transform:uppercase;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .2)';
    $td = 'padding:.6rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $renk = ['kritik' => 'rgb(220 38 38)', 'uyari' => 'rgb(217 119 6)', 'bilgi' => 'rgb(37 99 235)'];
    $s = $this->sayilar;
    $firmaAdi = $firmaId ? ($this->firmalar[$firmaId] ?? null) : null;
@endphp

<x-filament-panels::page>
    <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;justify-content:space-between">
        <div style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:flex-end">
            <div>
                <label style="display:block;font-size:.75rem;font-weight:600">İşyeri / Firma</label>
                <select wire:model.live="firmaId" style="{{ $girdi }};min-width:240px">
                    <option value="">Tüm işyerleri</option>
                    @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:.75rem;font-weight:600">Seviye</label>
                <select wire:model.live="seviye" style="{{ $girdi }}">
                    <option value="">Tümü</option>
                    @foreach (\App\Models\Bildirim::SEVIYELER as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <label style="display:flex;gap:.35rem;align-items:center;font-size:.8rem;padding-bottom:.45rem">
                <input type="checkbox" wire:model.live="okunanlariGoster"> Okunanları da göster
            </label>
        </div>
        <div style="display:flex;gap:.4rem">
            <x-filament::button color="gray" icon="heroicon-o-check" wire:click="tumunuOkundu">Tümünü okundu say</x-filament::button>
            <x-filament::button icon="heroicon-o-arrow-path" wire:click="sureleriKontrolEt">Süreleri Kontrol Et</x-filament::button>
        </div>
    </div>

    <x-filament::section>
        <p style="font-size:.8rem;color:rgb(107 114 128);line-height:1.5;margin-bottom:.8rem">
            Bu merkez otomatik süre uyarısı üretir: görevlendirme / sözleşme bitişi, İSG-KATİP no eksikliği, atanmamış profesyonel,
            doküman geçerliliği (risk değerlendirmesi, acil durum planı), sağlık muayenesi, geciken yıllık plan, SDS / PKD gözden geçirme
            terminleri ile periyodik kontrol, KKD, ortam ölçümü, DÖF ve eğitim süreleri. Sayfa açıldığında son taramadan
            {{ \App\Support\BildirimTarayici::OTOMATIK_TARAMA_DK }} dakika geçtiyse kendiliğinden tarar; sorun giderilince bildirim otomatik kapanır.
            Sağlık bildirimlerinde çalışan adı ve klinik bilgi gösterilmez.
            @if ($firmaAdi)<strong>{{ $firmaAdi }}</strong> işyerinin bildirimleri gösteriliyor.@endif
        </p>

        <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.8rem">
            @foreach ([['kritik', 'Kritik'], ['uyari', 'Uyarı'], ['bilgi', 'Bilgi']] as [$k, $ad])
                <button type="button" wire:click="$set('seviye', '{{ $seviye === $k ? '' : $k }}')"
                        style="border:1px solid {{ $renk[$k] }};border-radius:.6rem;padding:.4rem .8rem;font-size:.8rem;{{ $seviye === $k ? 'background:'.$renk[$k].';color:#fff' : 'color:'.$renk[$k] }}">
                    {{ $ad }} <strong>{{ $s[$k] }}</strong>
                </button>
            @endforeach
            <span style="font-size:.8rem;color:rgb(107 114 128);align-self:center">· {{ $s['okunmamis'] }} okunmamış</span>
        </div>

        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.83rem;min-width:760px">
                <tr>
                    <th style="{{ $th }}">Seviye</th>
                    <th style="{{ $th }}">Başlık</th>
                    <th style="{{ $th }}">Açıklama</th>
                    <th style="{{ $th }}">Tarih</th>
                    <th style="{{ $th }}">İşlem</th>
                </tr>
                @forelse ($this->bildirimler as $b)
                    <tr style="{{ $b->okundu_at ? 'opacity:.6' : '' }}">
                        <td style="{{ $td }};white-space:nowrap">
                            <span style="font-size:.72rem;font-weight:700;color:#fff;background:{{ $renk[$b->seviye] ?? 'gray' }};border-radius:.35rem;padding:.15rem .45rem">{{ $b->seviyeEtiketi() }}</span>
                            @unless ($b->okundu_at)<span title="Okunmamış" style="display:inline-block;width:.45rem;height:.45rem;border-radius:9px;background:rgb(220 38 38);margin-left:.3rem"></span>@endunless
                        </td>
                        <td style="{{ $td }}"><strong>{{ $b->baslik }}</strong>@unless ($firmaId)<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $b->firma?->unvan }}</div>@endunless</td>
                        <td style="{{ $td }};line-height:1.45">{{ $b->aciklama }}</td>
                        <td style="{{ $td }};white-space:nowrap">{{ $b->tarih?->format('d.m.Y') ?? '—' }}</td>
                        <td style="{{ $td }};white-space:nowrap">
                            @if ($b->url)
                                <x-filament::button size="xs" wire:click="git({{ $b->id }})">Git</x-filament::button>
                            @endif
                            <x-filament::button size="xs" color="gray" wire:click="okunduIsaretle({{ $b->id }})">{{ $b->okundu_at ? 'Okunmadı' : 'Okundu' }}</x-filament::button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="{{ $td }};text-align:center;color:rgb(107 114 128);padding:1.4rem">
                        {{ $firmaAdi ? '“'.$firmaAdi.'” için' : 'Hiç' }} açık bildirim yok. Süreleri Kontrol Et ile yeniden tarayabilirsiniz.
                    </td></tr>
                @endforelse
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
