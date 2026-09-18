@php
    $teal = 'rgb(14 116 144)';
    $girdi = 'margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        6331 s.K. m.14 — hastalığın meslek hastalığı olduğunun işyeri hekimi, sağlık kuruluşu veya
        sigortalı tarafından öğrenildiği tarihten itibaren işveren <strong>3 iş günü</strong> içinde
        SGK'ya bildirmekle yükümlüdür. Meslek hastalığı genelde işyeri hekimi/sağlık gözetimi
        sürecinde tespit edilir — firmanızda atanmış bir işyeri hekimi olması sürecin takibini kolaylaştırır.
    </p>

    @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'meslek_hastaligi_bildirimi'])

    <x-filament::section icon="heroicon-o-heart" icon-color="info">
        <x-slot name="heading">1. Firma & Çalışan</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId" style="{{ $girdi }}">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            @if ($this->firma)
                <div>
                    <label style="font-weight:600;font-size:.82rem">Hızlı Çalışan Seç</label>
                    <select wire:model.live="calisanHizliSecId" style="{{ $girdi }}">
                        <option value="">Firmaya kayıtlı çalışan yok / manuel gir</option>
                        @foreach ($this->calisanlar as $c)
                            <option value="{{ $c->id }}">{{ $c->ad_soyad }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        @if ($this->firma)
            @if (! $this->firma->isyeriHekimi)
                <p style="margin-top:.75rem;font-size:.78rem;color:#d97706">
                    Bu firmaya atanmış bir işyeri hekimi yok — meslek hastalığı tespiti genelde hekim
                    üzerinden yürüdüğü için "İSG Profesyonelleri" bölümünden atama yapmanız önerilir
                    (yine de sağlık kuruluşu veya sigortalının kendisi de bildirim kaynağı olabilir).
                </p>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-top:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Ad Soyad <span style="color:#ef4444">*</span></label>
                    <input type="text" wire:model="calisanAdSoyad" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">T.C. Kimlik No</label>
                    <input type="text" wire:model="calisanTc" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Görevi</label>
                    <input type="text" wire:model="calisanGorevi" style="{{ $girdi }}">
                </div>
            </div>
        @endif
    </x-filament::section>

    @if ($this->firma)
        <x-filament::section icon="heroicon-o-clock" icon-color="warning">
            <x-slot name="heading">2. Öğrenme Bilgileri ve Bildirim Süresi</x-slot>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Öğrenme Kaynağı</label>
                    <select wire:model="ogrenmeKaynagi" style="{{ $girdi }}">
                        @foreach ($this->ogrenmeKaynaklari as $anahtar => $etiket)
                            <option value="{{ $anahtar }}">{{ $etiket }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Öğrenme Tarihi <span style="color:#ef4444">*</span></label>
                    <input type="date" wire:model.live="ogrenmeTarihi" style="{{ $girdi }}">
                </div>
            </div>

            @if ($this->bildirimSonTarihiOnizleme)
                <div style="margin-top:.75rem;padding:.6rem .8rem;border-radius:.5rem;background:rgb(14 116 144 / .08);border:1px solid {{ $teal }};font-size:.85rem;font-weight:600">
                    SGK'ya bildirim son tarihi: {{ $this->bildirimSonTarihiOnizleme }} (3 iş günü)
                </div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-document-text" icon-color="info">
            <x-slot name="heading">3. Tanı Bilgileri</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Tanı Koyan Hastane</label>
                    <input type="text" wire:model="taniHastane" placeholder="Yetkili meslek hastalığı hastanesi (üniversite/eğitim-araştırma hastanesi)" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Tanı Tarihi</label>
                    <input type="date" wire:model="taniTarihi" style="{{ $girdi }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Sağlık Kurulu Rapor No</label>
                    <input type="text" wire:model="saglikKuruluRaporNo" style="{{ $girdi }}">
                </div>
            </div>
            <label style="font-weight:600;font-size:.8rem;margin-top:.75rem;display:block">Meslek Hastalığı Tanısı</label>
            <textarea wire:model="meslekHastaligiTanisi" rows="2" placeholder="Teşhis edilen hastalık ve ilişkilendirilen maruziyet"
                style="width:100%;margin-top:.2rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem"></textarea>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-building-office-2" icon-color="info">
            <x-slot name="heading">4. SGK Bildirimi ve Sonucu</x-slot>

            <label style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;font-weight:600;cursor:pointer">
                <input type="checkbox" wire:model.live="sgkBildirimiYapildi">
                SGK'ya bildirim yapıldı
            </label>

            @if ($sgkBildirimiYapildi)
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-top:.75rem">
                    <div>
                        <label style="font-weight:600;font-size:.8rem">Bildirim Tarihi</label>
                        <input type="date" wire:model="sgkBildirimTarihi" style="{{ $girdi }}">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.8rem">Bildirim Yöntemi</label>
                        <select wire:model="sgkBildirimYontemi" style="{{ $girdi }}">
                            <option value="">— Seçin —</option>
                            @foreach ($this->bildirimYontemleri as $anahtar => $etiket)
                                <option value="{{ $anahtar }}">{{ $etiket }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-top:.75rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">SGK Sağlık Kurulu Durumu</label>
                    <select wire:model="sgkKurulOnayDurumu" style="{{ $girdi }}">
                        @foreach ($this->kurulOnayDurumlari as $anahtar => $etiket)
                            <option value="{{ $anahtar }}">{{ $etiket }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Meslekte Kazanma Gücü Kaybı (%)</label>
                    <input type="number" step="0.1" min="0" max="100" wire:model="meslekteKazanmaGucuKaybiYuzde" placeholder="≥10 ise meslek hastalığı süreci başlar" style="{{ $girdi }}">
                </div>
            </div>

            <label style="font-weight:600;font-size:.8rem;margin-top:.75rem;display:block">Notlar</label>
            <textarea wire:model="notlar" rows="2"
                style="width:100%;margin-top:.2rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem"></textarea>
        </x-filament::section>

        @if ($this->gecmisKayitlar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Bildirimler</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisKayitlar as $m)
                        <tr>
                            <td style="padding:.3rem .5rem">
                                {{ $m->belge_no }} — {{ $m->calisan_ad_soyad }} ({{ $m->ogrenme_tarihi?->format('d.m.Y') }})
                                @if ($m->suresiGectiMi())
                                    <span style="color:#ef4444;font-weight:600">· süre geçti</span>
                                @elseif (! $m->sgk_bildirimi_yapildi)
                                    <span style="color:#d97706;font-weight:600">· bildirim bekliyor</span>
                                @endif
                            </td>
                            <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $m->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $m->id }})">Sil</x-filament::button>
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
