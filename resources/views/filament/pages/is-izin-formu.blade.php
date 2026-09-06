@php
    $turuncu = 'rgb(245 158 11)';
    $yesil = 'rgb(16 185 129)';
    $mor = 'rgb(139 92 246)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Sıcak iş, yüksekte çalışma, kapalı alan veya elektrik çalışması için iş izni
        (Permit to Work) düzenleyin; ilgili güvenlik önlemlerini işaretleyip onaylayın.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'calisma_izin_formu'])

    {{-- 1. İŞ TANIMI & LOKASYON --}}
    <x-filament::section icon="heroicon-o-key" icon-color="warning">
        <x-slot name="heading">1. İş Tanımı ve Lokasyon</x-slot>

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
                <label style="font-weight:600;font-size:.82rem">Çalışma Alanı / Lokasyon</label>
                <input type="text" wire:model="calismaAlani" placeholder="Örn: Kazan Dairesi, Çatı Katı"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Başlangıç Zamanı</label>
                <input type="datetime-local" wire:model="baslangic"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Bitiş Zamanı</label>
                <input type="datetime-local" wire:model="bitis"
                    style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            </div>
        </div>
        <div style="margin-top:1rem">
            <label style="font-weight:600;font-size:.82rem">Yapılacak İşin Detayı</label>
            <textarea wire:model="isDetayi" rows="2" placeholder="Yapılacak işi detaylıca açıklayınız..."
                style="width:100%;margin-top:.3rem;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-family:inherit;font-size:.85rem"></textarea>
        </div>
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. İZİN TÜRÜ --}}
        <x-filament::section icon="heroicon-o-fire" icon-color="warning">
            <x-slot name="heading">2. İzin Türü (Birden Fazla Seçilebilir)</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.5rem">
                @foreach ($this->turler as $anahtar => $ad)
                    @php $secili = in_array($anahtar, $izinTurleri, true); @endphp
                    <button type="button" wire:click="izinTuruToggle('{{ $anahtar }}')"
                        style="padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.82rem;text-align:center;
                            border:2px solid {{ $secili ? $turuncu : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(245 158 11 / .1)' : 'transparent' }}">
                        {{ $ad }}
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 3. GÜVENLİK ÖNLEMLERİ --}}
        <x-filament::section icon="heroicon-o-shield-check" icon-color="success">
            <x-slot name="heading">3. Güvenlik Önlemleri Kontrol Listesi</x-slot>
            <x-slot name="description">Aşağıdaki önlemlerin alındığını doğrulayınız</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.4rem">
                @foreach ($this->onlemler as $madde)
                    @php $secili = in_array($madde, $secilenOnlemler, true); @endphp
                    <button type="button" wire:click="onlemToggle('{{ addslashes($madde) }}')"
                        style="text-align:left;padding:.5rem .7rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                            border:1px solid {{ $secili ? $yesil : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(16 185 129 / .08)' : 'transparent' }}">
                        {{ $secili ? '☑' : '☐' }} {{ $madde }}
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 4. KKD --}}
        <x-filament::section icon="heroicon-o-shield-exclamation" icon-color="primary">
            <x-slot name="heading">4. Gerekli Kişisel Koruyucu Donanımlar</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.5rem">
                @foreach ($this->kkdSecenekleri as $kkd)
                    @php $secili = in_array($kkd, $secilenKkdler, true); @endphp
                    <button type="button" wire:click="kkdToggle('{{ $kkd }}')"
                        style="padding:.5rem;border-radius:.5rem;cursor:pointer;font-size:.8rem;text-align:center;
                            border:2px solid {{ $secili ? $mor : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(139 92 246 / .1)' : 'transparent' }}">
                        {{ $kkd }}
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 5. ONAY & İMZALAR --}}
        <x-filament::section icon="heroicon-o-pencil-square" icon-color="danger">
            <x-slot name="heading">5. Onay ve İmzalar</x-slot>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Başlık (PDF'de görünecek)</label>
                    <input type="text" wire:model="onay1Baslik" placeholder="Formen / Mühendis / Şef vb."
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem;margin-bottom:.4rem">
                    <label style="font-weight:600;font-size:.8rem">Ad Soyad</label>
                    <input type="text" wire:model="onay1Ad" placeholder="Ad Soyad"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Başlık (PDF'de görünecek)</label>
                    <input type="text" wire:model="onay2Baslik" placeholder="İSG Uzmanı / Amir / Müdür vb."
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem;margin-bottom:.4rem">
                    <label style="font-weight:600;font-size:.8rem">Ad Soyad</label>
                    <input type="text" wire:model="onay2Ad" placeholder="Ad Soyad"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
            </div>
        </x-filament::section>

        {{-- 6. GEÇMİŞ İZİNLER --}}
        @if ($this->gecmisFormlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş İş İzinleri</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisFormlar as $f)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $f->izin_no }} — {{ $f->calisma_alani ?: 'Alan belirtilmedi' }}</td>
                            <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $f->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $f->id }})">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
