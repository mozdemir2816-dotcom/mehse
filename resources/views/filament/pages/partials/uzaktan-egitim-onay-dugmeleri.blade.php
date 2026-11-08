{{-- Çift onay düğmeleri (08.11.2026): önce İGU (tehlikeli / çok tehlikelide yüz yüze pratik), sonra İşyeri Hekimi; ikisinden sonra Sertifika. --}}
@php($dugme = 'border:0;border-radius:.4rem;padding:.15rem .5rem;cursor:pointer;font-size:.78rem;color:#fff')
@if ($a->iguOnayiBekliyorMu())
    <button wire:click="iguOnayla({{ $a->id }})"
        wire:confirm="{{ $a->dorduncuKonuYuzYuzeMi() ? '4. konu (işe ve işyerine özgü riskler) yüz yüze pratik olarak verildi ve sınav sonucu uygundur; onaylansın mı?' : 'Teknik konular ve sınav sonucu uygundur; İGU olarak onaylansın mı?' }}"
        style="{{ $dugme }};background:#b45309">{{ $a->dorduncuKonuYuzYuzeMi() ? 'Pratik + İGU Onayı' : 'İGU Onayı' }}</button>
@elseif ($a->ihOnayiBekliyorMu())
    @php($hekim = $a->calisan?->firma?->isyeriHekimi)
    <button wire:click="ihOnayla({{ $a->id }})"
        wire:confirm="Sağlık konuları işyeri hekimi {{ $hekim?->ad_soyad ?? '(firmada kayıtlı hekim yok)' }} adına onaylansın mı?"
        style="{{ $dugme }};background:#7c3aed">İH Onayı</button>
@endif
@if ($a->belgeAcikMi())
    <button wire:click="belgeIndir({{ $a->id }})" style="{{ $dugme }};background:rgb(20 184 166)">Sertifika</button>
@endif
