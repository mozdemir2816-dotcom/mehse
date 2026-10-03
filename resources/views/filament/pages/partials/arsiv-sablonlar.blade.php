{{-- Arşiv > Belge Şablonları modalı: kayıtlı şablonlar + kullanılabilir sistem alanları --}}
@php($ac = '{'.'{')
@php($kapa = '}'.'}')
<div style="display:flex;flex-direction:column;gap:.8rem">
    @if ($sablonlar->isEmpty())
        <div style="padding:.8rem;border:1px dashed rgb(107 114 128 / .35);border-radius:.6rem;text-align:center;font-size:.85rem;color:rgb(107 114 128)">
            Henüz şablon yok. Aşağıdan ilk şablonunuzu yükleyin.
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:.4rem">
            @foreach ($sablonlar as $s)
                <div wire:key="sablon-{{ $s->id }}" style="display:flex;gap:.6rem;align-items:flex-start;padding:.6rem .7rem;border:1px solid rgb(107 114 128 / .25);border-radius:.6rem">
                    <span style="font-size:.7rem;font-weight:700;padding:.15rem .4rem;border-radius:.3rem;background:rgb(59 130 246 / .1);color:rgb(37 99 235)">{{ strtoupper($s->tur) }}</span>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:.88rem">{{ $s->ad }}</div>
                        <div style="font-size:.74rem;color:rgb(107 114 128)">{{ $s->kategoriEtiketi() }} · {{ count($s->yer_tutucular ?? []) }} alan</div>
                        @if ($s->yer_tutucular)
                            <div style="font-size:.7rem;color:rgb(107 114 128);margin-top:.2rem;word-break:break-word">{{ collect($s->yer_tutucular)->map(fn ($a) => $ac.$a.$kapa)->implode(' ') }}</div>
                        @endif
                    </div>
                    <button type="button" wire:click="sablonIndir({{ $s->id }})" style="background:none;border:none;cursor:pointer;font-size:.78rem;color:rgb(37 99 235)">İndir</button>
                    <button type="button" wire:click="sablonSil({{ $s->id }})" wire:confirm="Şablon silinsin mi? (Ondan üretilmiş belgeler arşivde kalır.)" style="background:none;border:none;cursor:pointer;font-size:.78rem;color:#ef4444">Sil</button>
                </div>
            @endforeach
        </div>
    @endif

    <details style="border:1px solid rgb(107 114 128 / .25);border-radius:.6rem;padding:.55rem .75rem">
        <summary style="cursor:pointer;font-weight:600;font-size:.82rem">Sistemden otomatik dolan alanlar</summary>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:.25rem .8rem;margin-top:.5rem;font-size:.75rem">
            @foreach (config('arsiv.sistem_alanlari') as $anahtar => $ad)
                <div><code style="font-size:.72rem">{{ $ac.$anahtar.$kapa }}</code> <span style="color:rgb(107 114 128)">{{ $ad }}</span></div>
            @endforeach
        </div>
        <p style="font-size:.72rem;color:rgb(107 114 128);margin-top:.5rem">Listede olmayan her yer tutucu (ör. <code>{{ $ac }}egitim_suresi{{ $kapa }}</code>) belge üretilirken formda sorulur.</p>
    </details>
</div>
