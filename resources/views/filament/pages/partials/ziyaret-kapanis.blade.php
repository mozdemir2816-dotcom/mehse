{{-- Ziyaret Modu: açık bulgu / DÖF maddesini yerinde kapatma ($an: anahtar, $kapat: Livewire çağrısı) --}}
@if ($this->acikKapanis === $an)
    <div style="margin-top:.5rem;padding:.6rem;border-radius:.6rem;background:rgb(22 163 74 / .06);border:1px solid rgb(22 163 74 / .25);display:flex;flex-direction:column;gap:.45rem">
        <textarea wire:model="kapanisNot.{{ $an }}" rows="2" placeholder="Yapılan düzeltme (isteğe bağlı)"
            style="width:100%;padding:.45rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.88rem;color:inherit"></textarea>
        <div style="display:flex;gap:.45rem;align-items:center;flex-wrap:wrap">
            <label style="display:inline-flex;align-items:center;gap:.3rem;min-height:2.4rem;padding:0 .75rem;border-radius:.55rem;border:1px solid rgb(37 99 235 / .4);color:rgb(37 99 235);font-size:.85rem;font-weight:600;cursor:pointer">
                📷 {{ ($this->kapanisFoto[$an] ?? null) ? 'Fotoğraf eklendi ✓' : 'Sonrası fotoğrafı' }}
                <input type="file" wire:model="kapanisFoto.{{ $an }}" accept="image/*" capture="environment" style="display:none">
            </label>
            <span wire:loading wire:target="kapanisFoto.{{ $an }}" style="font-size:.78rem;color:rgb(107 114 128)">Yükleniyor…</span>
            <button type="button" wire:click="{{ $kapat }}" wire:loading.attr="disabled"
                style="margin-left:auto;min-height:2.4rem;padding:0 1rem;border-radius:.55rem;border:none;background:#15803d;color:#fff;font-weight:700;cursor:pointer">✓ Giderildi</button>
        </div>
    </div>
@else
    <button type="button" wire:click="kapanisAc('{{ $an }}')"
        style="margin-top:.45rem;min-height:2.3rem;padding:0 .85rem;border-radius:.55rem;border:1px solid rgb(22 163 74 / .45);background:transparent;color:#15803d;font-weight:600;font-size:.85rem;cursor:pointer">✓ Giderildi olarak kapat</button>
@endif
