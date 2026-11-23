@php
    $toplam = $this->toplam;
    $rozet = 'display:inline-block;min-width:2.2rem;text-align:center;padding:.15rem .5rem;border-radius:9999px;font-weight:700;font-size:.8rem;font-variant-numeric:tabular-nums';
@endphp

<x-filament-panels::page>
    <p class="mehse-bolum-aciklama" style="margin-top:-.5rem;max-width:72ch;line-height:1.55">
        Önceden temel İSG eğitimi kaydı olan çalışanlara <strong>4 ders saatlik (4×45 = 180 dk) Yenileme</strong>,
        <strong>hiç eğitim kaydı olmayanlara 8 ders saatlik (8×45 = 360 dk) Temel</strong> eğitim atanır.
        {{ $yil }} içinde zaten eğitim almış olanlar ve devam eden eğitimi bulunanlar atlanır. Gönderimde kullanıcı kodu ve
        geçici şifre üretilir, firma e-postasına giriş kartları PDF'i gider ve WhatsApp mesajı hazırlanır.
    </p>

    <x-filament::section>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;align-items:end">
            <div>
                <label class="mehse-etiket" for="yb-yil">Eğitim yılı</label>
                <input id="yb-yil" type="number" min="2024" max="2100" wire:model.live.debounce.500ms="yil" class="mehse-girdi">
            </div>
            <div>
                <label class="mehse-etiket" for="yb-yenileme">Yenileme paketi (4 ders saati)</label>
                <select id="yb-yenileme" wire:model.live="yenilemePaketId" class="mehse-girdi">
                    <option value="">— Seçin —</option>
                    @foreach ($this->paketler('yenileme') as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="mehse-etiket" for="yb-temel">Temel paket (8 ders saati, kaydı olmayanlar)</label>
                <select id="yb-temel" wire:model.live="temelPaketId" class="mehse-girdi">
                    <option value="">— Seçin —</option>
                    @foreach ($this->paketler('ilk_defa') as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="mehse-etiket" for="yb-kapsam">Yenileme kimlere</label>
                <select id="yb-kapsam" wire:model.live="kapsam" class="mehse-girdi">
                    @foreach (\App\Support\YilBasiTopluEgitim::KAPSAMLAR as $k => $etiket)<option value="{{ $k }}">{{ $etiket }}</option>@endforeach
                </select>
            </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;margin-top:.9rem">
            <x-filament::button size="sm" color="warning" icon="heroicon-o-shield-check" wire:click="topluOnayVer"
                wire:confirm="Seçili iki paketin eğitim içeriğini inceledim; TÜM firmalarım için içerik onayı verilsin mi? (Firma Ayarları'ndaki onayla aynı)">
                Tüm firmalara içerik onayı ver
            </x-filament::button>
            <span class="mehse-soluk-metin" style="font-size:.8rem">Onay her firma için Firma Ayarları sekmesinde ayrı ayrı da verilebilir; paket içeriği değişirse onay yenilenmelidir.</span>
        </div>
        @if (! $this->paketler('yenileme'))
            <p class="mehse-uyari-not" style="margin-top:.75rem">
                Henüz <strong>Temel İSG Yenileme Eğitimi</strong> paketi yok. Paketler sekmesinde <em>Yeni</em> → <em>⚡ Standart Paket</em> →
                “Temel İSG Yenileme Eğitimi” seçip YouTube videolarınızı ekleyin.
            </p>
        @endif
    </x-filament::section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem">
        @foreach ([['Firma', $toplam['firma']], ['4 ders saati yenileme', $toplam['yenileme']], ['8 ders saati temel', $toplam['ilk_defa']], ['Gönderime hazır firma', $toplam['hazir']]] as [$etiket, $deger])
            <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.6rem;padding:.7rem .9rem">
                <div class="mehse-soluk-metin" style="font-size:.8rem">{{ $etiket }}</div>
                <div style="font-size:1.5rem;font-weight:700;font-variant-numeric:tabular-nums">{{ $deger }}</div>
            </div>
        @endforeach
    </div>

    <x-filament::section>
        <x-slot name="heading">Önizleme — {{ $yil }}</x-slot>
        <x-slot name="description">Firmayı seçin, ayrıntı için satırı açın. Eksik ayarı olan firma seçilemez.</x-slot>

        <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin-bottom:.75rem">
            <x-filament::button size="sm" color="gray" wire:click="hazirlariSec" icon="heroicon-o-check-circle">Hazır olanların tümünü seç</x-filament::button>
            <x-filament::button size="sm" color="gray" wire:click="secimiTemizle">Seçimi temizle</x-filament::button>
            <label style="display:flex;gap:.4rem;align-items:center;font-size:.85rem;min-height:44px">
                <input type="checkbox" wire:model="epostaGonder"> Firma e-postasına giriş kartları gönderilsin
            </label>
            <span style="flex:1"></span>
            <x-filament::button wire:click="gonder" icon="heroicon-o-paper-airplane" :disabled="! $seciliFirmalar"
                wire:confirm="Seçili {{ count($seciliFirmalar) }} firmaya {{ $yil }} eğitimleri atanacak, geçici şifreler üretilecek{{ $epostaGonder ? ' ve firma e-postalarına gönderilecek' : '' }}. Devam edilsin mi?"
                wire:loading.attr="disabled" wire:target="gonder">
                <span wire:loading.remove wire:target="gonder">Seçili {{ count($seciliFirmalar) }} firmaya gönder</span>
                <span wire:loading wire:target="gonder">Gönderiliyor…</span>
            </x-filament::button>
        </div>

        @if (! $this->onizleme)
            <p class="mehse-soluk-metin">{{ $yil }} için eğitim gönderilecek çalışan bulunamadı.</p>
        @else
            <div style="overflow-x:auto">
                <table class="mehse-tablo" style="min-width:760px">
                    <thead>
                        <tr>
                            <th class="mehse-th" style="width:44px"><span class="sr-only">Seç</span></th>
                            <th class="mehse-th">Firma</th>
                            <th class="mehse-th" style="text-align:center">4 ders saati yenileme</th>
                            <th class="mehse-th" style="text-align:center">8 ders saati temel</th>
                            <th class="mehse-th" style="text-align:center">Atlanan</th>
                            <th class="mehse-th">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->onizleme as $firmaId => $o)
                            @php $hazir = $this->gonderilebilirMi($o); @endphp
                            <tr wire:key="yb-{{ $firmaId }}-{{ $yil }}">
                                <td class="mehse-td" style="text-align:center">
                                    <input type="checkbox" value="{{ $firmaId }}" wire:model.live="seciliFirmalar" @disabled(! $hazir)
                                        aria-label="{{ $o['firma']->secimEtiketi() }} seç" style="width:20px;height:20px">
                                </td>
                                <td class="mehse-td">
                                    <details>
                                        <summary style="cursor:pointer;font-weight:600">{{ $o['firma']->secimEtiketi() }}</summary>
                                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.75rem;margin-top:.5rem;font-size:.82rem">
                                            <div>
                                                <div style="font-weight:600;margin-bottom:.25rem">Yenileme (4 ders saati)</div>
                                                @forelse ($o['yenileme'] as $s)
                                                    <div>{{ $s['calisan']->ad_soyad }} <span class="mehse-soluk-metin">· son {{ $s['son_egitim']->format('d.m.Y') }} · yenileme {{ $s['yenileme']->format('d.m.Y') }}</span></div>
                                                @empty <div class="mehse-soluk-metin">—</div> @endforelse
                                            </div>
                                            <div>
                                                <div style="font-weight:600;margin-bottom:.25rem">Temel (8 ders saati) — kaydı yok</div>
                                                @forelse ($o['ilk_defa'] as $s)
                                                    <div>{{ $s['calisan']->ad_soyad }}@if ($s['calisan']->gorev) <span class="mehse-soluk-metin">· {{ $s['calisan']->gorev }}</span>@endif</div>
                                                @empty <div class="mehse-soluk-metin">—</div> @endforelse
                                            </div>
                                        </div>
                                        <div class="mehse-soluk-metin" style="font-size:.78rem;margin-top:.4rem">
                                            E-posta: {{ $o['firma']->eposta ?: 'yok' }} · Telefon: {{ $o['firma']->telefon ?: 'yok' }}
                                        </div>
                                    </details>
                                </td>
                                <td class="mehse-td" style="text-align:center"><span style="{{ $rozet }};background:rgb(37 99 235 / .12)">{{ $o['yenileme']->count() }}</span></td>
                                <td class="mehse-td" style="text-align:center"><span style="{{ $rozet }};background:rgb(124 58 237 / .12)">{{ $o['ilk_defa']->count() }}</span></td>
                                <td class="mehse-td" style="text-align:center;font-size:.8rem" title="Yenilemesi sonraki yıllarda / devam eden eğitimi var">
                                    {{ $o['gecerli'] + $o['zaten_atanmis'] }}
                                </td>
                                <td class="mehse-td" style="font-size:.82rem">
                                    @if ($o['eksik'])
                                        <span class="mehse-hata">{{ implode(' · ', $o['eksik']) }}</span>
                                    @elseif ($o['bildirim']?->eposta_gonderildi_at)
                                        <span style="color:rgb(21 128 61)">Gönderildi {{ $o['bildirim']->eposta_gonderildi_at->format('d.m.Y H:i') }}</span>
                                        @if ($hazir)<br><span class="mehse-soluk-metin">yeni eklenenler var</span>@endif
                                    @elseif ($o['bildirim'])
                                        <span style="color:#b45309">Atandı, e-posta gitmedi{{ $o['bildirim']->eposta_hatasi ? ': '.$o['bildirim']->eposta_hatasi : '' }}</span>
                                    @else
                                        Hazır
                                    @endif
                                    @if (! $o['firma']->eposta && $hazir)<br><span style="color:#b45309">Firma e-postası yok — yalnız WhatsApp / PDF</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    @if ($sonuclar)
        <x-filament::section>
            <x-slot name="heading">Gönderim sonucu</x-slot>
            <div style="overflow-x:auto">
                <table class="mehse-tablo" style="min-width:720px">
                    <thead>
                        <tr>
                            <th class="mehse-th">Firma</th>
                            <th class="mehse-th" style="text-align:center">Atanan (4 / 8 ders saati)</th>
                            <th class="mehse-th">E-posta</th>
                            <th class="mehse-th">Firma yetkilisine</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sonuclar as $firmaId => $s)
                            <tr wire:key="ybs-{{ $firmaId }}">
                                <td class="mehse-td" style="font-weight:600">{{ $s['unvan'] }}</td>
                                @if ($s['hata'] ?? null)
                                    <td class="mehse-td" colspan="3"><span class="mehse-hata">Gönderilmedi: {{ $s['hata'] }}</span></td>
                                @else
                                    <td class="mehse-td" style="text-align:center;font-variant-numeric:tabular-nums">{{ $s['yenileme'] }} / {{ $s['ilk_defa'] }}</td>
                                    <td class="mehse-td" style="font-size:.82rem">
                                        @switch($s['eposta_durum'])
                                            @case('gitti') <span style="color:rgb(21 128 61)">✓ {{ $s['eposta'] }}</span> @break
                                            @case('hata') <span class="mehse-hata">{{ $s['eposta_hatasi'] }}</span> @break
                                            @default <span class="mehse-soluk-metin">Gönderilmedi (seçenek kapalı)</span>
                                        @endswitch
                                    </td>
                                    <td class="mehse-td">
                                        <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                                            @if ($s['whatsapp'])
                                                <x-filament::button size="sm" color="success" tag="a" href="{{ $s['whatsapp'] }}" target="_blank" icon="heroicon-o-chat-bubble-left-right">WhatsApp mesajı</x-filament::button>
                                            @endif
                                            <x-filament::button size="sm" color="gray" wire:click="kartlariIndir({{ $firmaId }})" icon="heroicon-o-arrow-down-tray"
                                                wire:confirm="Kendi şifresini belirlememiş çalışanlara YENİ geçici şifre üretilir; e-postayla giden kartlardaki şifreler geçersiz olur. İndirilsin mi?">
                                                Giriş kartları PDF
                                            </x-filament::button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mehse-soluk-metin" style="font-size:.8rem;margin-top:.6rem">WhatsApp mesajında şifre yoktur; şifreler e-postadaki ya da indirdiğiniz PDF'teki kartlardadır.</p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
