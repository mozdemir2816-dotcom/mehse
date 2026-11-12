@php
    $mavi = 'rgb(37 99 235)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $grad = 'linear-gradient(135deg, rgb(37 99 235), rgb(29 78 216))';
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <p class="mehse-bolum-aciklama">
        6331 sayılı Kanun md.20 uyarınca çalışan temsilcisi <strong>seçim</strong> süreci: duyuru → aday başvuruları →
        kesin aday listesi → oy pusulası → seçim sonucu atama tutanağı. Doğrudan atama için "Atama Yazıları"
        sayfasını kullanabilirsiniz.
    </p>

    <div style="{{ $kutu }};background:{{ $grad }};color:#fff;border:none;display:flex;align-items:center;gap:.6rem">
        <x-filament::icon icon="heroicon-o-user-group" style="width:1.4rem;height:1.4rem"/>
        <span style="font-size:1.05rem;font-weight:800">Çalışan Temsilcisi Seçimi</span>
    </div>

    <x-filament::section icon="heroicon-o-building-office" icon-color="primary">
        <x-slot name="heading">1. Firma Seçin</x-slot>

        <select wire:model.live="firmaId"
            style="width:100%;max-width:28rem;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </x-filament::section>

    @if ($this->firma)
        <x-filament::section icon="heroicon-o-megaphone" icon-color="primary">
            <x-slot name="heading">2. Seçim Bilgileri</x-slot>
            <x-slot name="description">Duyuru, aday listesi ve oy pusulasında kullanılır.</x-slot>

            <div class="mehse-izgara">
                <div>
                    <label class="mehse-etiket">İşyeri Çalışan Sayısı</label>
                    <input type="number" min="0" wire:model="isyeriCalisanSayisi"
                        class="mehse-girdi">
                </div>
                <div>
                    <label class="mehse-etiket">Zorunlu Temsilci Sayısı</label>
                    <input type="number" min="1" wire:model="zorunluTemsilciSayisi"
                        class="mehse-girdi">
                    <p style="font-size:.75rem;color:rgb(107 114 128);margin-top:.2rem">6331 md.20 kademesine göre önerilir, gerekirse değiştirin.</p>
                </div>
                <div>
                    <label class="mehse-etiket">Aday Başvuru Son Tarihi</label>
                    <input type="date" wire:model="adayBasvuruSonTarihi"
                        class="mehse-girdi">
                </div>
                <div>
                    <label class="mehse-etiket">Seçim Tarihi</label>
                    <input type="date" wire:model="secimTarihi"
                        class="mehse-girdi">
                </div>
                <div>
                    <label class="mehse-etiket">Seçim Saati</label>
                    <input type="time" wire:model="secimSaati"
                        class="mehse-girdi">
                </div>
                <div>
                    <label class="mehse-etiket">Seçim Yeri</label>
                    <input type="text" wire:model="secimYeri" placeholder="Örn: Toplantı Salonu"
                        class="mehse-girdi">
                </div>
                <div>
                    <label class="mehse-etiket">Görevlendirme Tarihi</label>
                    <input type="date" wire:model="gorevlendirmeTarihi"
                        class="mehse-girdi">
                    <p style="font-size:.75rem;color:rgb(107 114 128);margin-top:.2rem">Boş bırakılırsa seçim tarihi kullanılır (Atama Tutanağı için).</p>
                </div>
            </div>

            <div style="margin-top:1rem">
                <x-filament::button size="sm" color="gray" wire:click="kaydetVeBildir">Kaydet</x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-list-bullet" icon-color="primary">
            <x-slot name="heading">3. Adaylar</x-slot>
            <x-slot name="description">Kesin Aday Listesi ve Oy Pusulası bu listeden üretilir. Seçim sonucunda kazananı işaretleyip "Atama Tutanağı"nı indirin.</x-slot>

            <div style="display:flex;flex-direction:column;gap:.5rem">
                @forelse ($adaylar as $i => $aday)
                    <div style="display:flex;align-items:center;gap:.5rem;padding:.5rem;border-radius:.5rem;
                        border:1px solid {{ $secilenAdayIndex === $i ? $mavi : 'rgb(107 114 128 / .3)' }};
                        background:{{ $secilenAdayIndex === $i ? 'rgb(37 99 235 / .08)' : 'transparent' }}">
                        <input type="text" wire:model="adaylar.{{ $i }}.ad_soyad" placeholder="Ad Soyad"
                            style="flex:2;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <input type="text" wire:model="adaylar.{{ $i }}.unvan" placeholder="Unvan / Görev"
                            style="flex:1;padding:.4rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <button type="button" wire:click="kazananSec({{ $i }})" title="Seçim kazananı"
                            style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:{{ $secilenAdayIndex === $i ? '#15803d' : 'rgb(107 114 128 / .4)' }}">
                            {{ $secilenAdayIndex === $i ? '✓' : '○' }}
                        </button>
                        <button type="button" wire:click="adaySil({{ $i }})" title="Sil"
                            style="background:none;border:none;cursor:pointer;color:#b91c1c">✕</button>
                    </div>
                @empty
                    <p style="font-size:.82rem;color:rgb(107 114 128)">Henüz aday eklenmedi.</p>
                @endforelse
            </div>

            <div style="margin-top:.75rem">
                <x-filament::button size="sm" color="gray" icon="heroicon-o-plus" wire:click="adayEkle">Aday Ekle</x-filament::button>
            </div>
        </x-filament::section>

        @if ($this->atamaEgitim)
            <x-filament::section icon="heroicon-o-document-check" icon-color="primary">
                <x-slot name="heading">Atama yazısı ve eğitim</x-slot>
                <x-slot name="description">Kazanan kaydedilince atama yazısı otomatik hazırlanır; çalışan temsilcisi eğitimi kaydı olmayanlara eğitim verilmelidir.</x-slot>
                <table class="mehse-tablo">
                    @foreach ($this->atamaEgitim as $t)
                        <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                            <td style="padding:.45rem .4rem;font-weight:700">{{ $t['ad_soyad'] }}@if ($t['unvan']) <span style="font-weight:400;color:rgb(107 114 128)">— {{ $t['unvan'] }}</span>@endif</td>
                            <td style="padding:.45rem .4rem;color:{{ $t['atama'] ? 'rgb(21 128 61)' : 'rgb(180 83 9)' }}">{{ $t['atama'] ? '✓ Atama yazısı var' : '⚠ Atama yazısı yok' }}</td>
                            <td style="padding:.45rem .4rem;color:{{ $t['egitim_yok'] ? 'rgb(180 83 9)' : 'rgb(21 128 61)' }}">{{ $t['egitim_yok'] ? '⚠ Eğitimi yok' : '✓ Eğitim kaydı var' }}</td>
                        </tr>
                    @endforeach
                </table>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.6rem">
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-document-arrow-down" wire:click="temsilciAtamaPdf">Atama yazısı (PDF)</x-filament::button>
                    @php $pl = \App\Support\EkipAtamaEgitim::temsilciPlanliEgitim($this->firma); @endphp
                    @if ($pl)
                        <span style="font-size:.78rem;color:rgb(37 99 235);align-self:center">📋 Eğitim Katılım'da planlandı: <strong>{{ $pl->belge_no }}</strong> ({{ $pl->katilimciSayisi() }} kişi)</span>
                    @endif
                    <x-filament::button tag="a" size="sm" color="warning" icon="heroicon-o-academic-cap" :href="\App\Filament\Pages\EgitimKatilim::getUrl(['firma' => $this->firma->id, 'baslik' => 'calisan_temsilcisi'])">Eğitim Katılım'da aç</x-filament::button>
                    <x-filament::button tag="a" size="sm" color="gray" icon="heroicon-o-check-circle" :href="\App\Filament\Pages\EgitimYenilemeTakibi::getUrl(['firma' => $this->firma->id, 'sekme' => 'kayitlar'])">Katılım / Sonuç gir</x-filament::button>
                </div>
            </x-filament::section>
        @endif
    @else
        <p class="mehse-uyari-not">Devam etmek için bir firma seçin.</p>
    @endif

    <div style="{{ $kutu }};background:rgb(59 130 246 / .06);border-color:rgb(59 130 246 / .25)">
        <div style="display:flex;align-items:center;gap:.5rem;font-weight:700;color:{{ $mavi }};margin-bottom:.5rem">
            <x-filament::icon icon="heroicon-o-information-circle" style="width:1.15rem;height:1.15rem"/>
            Bilgi
        </div>
        <p style="margin:0;font-size:.78rem;color:rgb(75 85 99);line-height:1.6">
            Aday başvuru süresi 6331 sayılı Kanun ve Çalışan Temsilcisinin Nitelikleri ve Seçilme Usul ve Esaslarına
            İlişkin Tebliğ uyarınca yedi günden az olamaz. Zorunlu temsilci sayısı çalışan sayısına göre kademeli
            olarak belirlenir (2–50: 1, 51–100: 2, 101–500: 3, 501–1000: 4, 1001–2000: 5, 2001+: 6) — sistem bu
            kademeye göre öneri sunar, siz gerekirse değiştirebilirsiniz.
        </p>
    </div>
</x-filament-panels::page>
