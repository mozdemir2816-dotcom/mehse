@php
    $kutu = 'border:1px solid rgb(107 114 128 / .25);border-radius:.75rem;padding:.75rem';
    $girdi = 'width:100%;padding:.4rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem';
    $btn = 'padding:.32rem .55rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .3);font-size:.76rem;background:transparent;cursor:pointer;white-space:nowrap';
@endphp

<x-filament-panels::page>
    <div style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:260px;max-width:560px">
            <label style="font-weight:600;font-size:.8rem">Firma</label>
            <select wire:model.live="firmaId" style="{{ $girdi }}">
                <option value="">Firma seçin...</option>
                @foreach ($this->firmalar as $id => $unvan)<option value="{{ $id }}">{{ $unvan }}</option>@endforeach
            </select>
        </div>
    </div>

    @if ($this->firma)
        {{-- Firma başına kayıtlı tasarımlar: aç / sil / yeni --}}
        <div style="{{ $kutu }};display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
            <button type="button" wire:click="yeni" style="{{ $btn }};{{ ! $tasarimId ? 'background:#2563eb;color:#fff;border-color:#2563eb;font-weight:700' : '' }}">
                + Yeni Tasarım
            </button>

            @forelse ($this->tasarimlar as $t)
                <div style="display:flex;align-items:center;gap:.3rem;border:1px solid rgb(107 114 128 / .25);border-radius:.5rem;padding:.25rem .4rem;{{ $tasarimId === $t->id ? 'background:#eff6ff;border-color:#2563eb' : '' }}">
                    @if ($this->gorselUrl($t))
                        <img src="{{ $this->gorselUrl($t) }}" alt="" style="width:28px;height:28px;object-fit:contain;border-radius:.25rem;border:1px solid rgb(107 114 128 / .25)">
                    @endif
                    <button type="button" wire:click="ac({{ $t->id }})" style="background:none;border:none;cursor:pointer;font-size:.76rem;font-weight:{{ $tasarimId === $t->id ? '700' : '500' }}">
                        {{ $t->baslik }}
                    </button>
                    <button type="button" wire:click="sil({{ $t->id }})" wire:confirm="'{{ $t->baslik }}' silinsin mi?" style="background:none;border:none;cursor:pointer;color:#dc2626;font-size:.76rem" title="Sil">✕</button>
                </div>
            @empty
                <span style="font-size:.76rem;color:rgb(107 114 128)">Bu firma için henüz kayıtlı tabela tasarımı yok.</span>
            @endforelse
        </div>

        {{-- Editör: yalıtılmış iframe (kendi Tailwind CDN teması). Kaydet butonu postMessage ile gönderir, kaydet()'e gider. --}}
        <div wire:ignore wire:key="tabela-{{ $this->firma->id }}-{{ $tasarimId ?? 'yeni' }}"
            x-data="{
                kaydediliyor: false,
                mesaj(e) {
                    if (e.source !== this.$refs.cerceve.contentWindow) { return; }
                    const m = e.data || {};
                    if (m.tip === 'kaydet') {
                        this.kaydediliyor = true;
                        this.$wire.kaydet(m.baslik, m.veri, m.png).then(() => { this.kaydediliyor = false; });
                    }
                },
            }"
            x-on:message.window="mesaj($event)">
            <div style="font-size:.74rem;color:rgb(107 114 128);margin-bottom:.3rem">
                Tasarımı bitirince <strong>"mehse'ye Kaydet"</strong> düğmesine basın.
                <span x-show="kaydediliyor" style="color:#2563eb;font-weight:600">· Kaydediliyor…</span>
            </div>
            <iframe x-ref="cerceve" title="Tabela / Güvenlik Levhası Tasarım Atölyesi" srcdoc="{{ $this->editorHtml }}"
                style="width:100%;height:calc(100vh - 320px);min-height:640px;border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;background:#fff"></iframe>
        </div>
    @endif
</x-filament-panels::page>
