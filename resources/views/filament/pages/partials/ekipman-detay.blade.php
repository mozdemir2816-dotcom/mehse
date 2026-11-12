{{-- Ekipman detayı (12.11.2026): QR operatör erişimi, belgeler + tebliğ, bakım/arıza, günlük kontrol geçmişi. --}}
@php
    $teblig = $e->tebligDurumu();
    $acik = $bakimlar->where('durum', '!=', 'kapali');
@endphp
<div class="mehse-detay">
    <style>
        .mehse-detay { display:grid; gap:1rem; font-size:.875rem }
        .mehse-detay .ust { display:grid; gap:1rem; grid-template-columns:1fr }
        @media (min-width: 768px) { .mehse-detay .ust { grid-template-columns:1fr 1fr } }
        .mehse-detay .kutu { border:1px solid rgb(107 114 128 / .25); border-radius:.75rem; padding:.9rem 1rem }
        .mehse-detay h3 { font-weight:700; font-size:.9rem; margin:0 0 .6rem }
        .mehse-detay .qr { display:flex; gap:1rem; align-items:flex-start }
        .mehse-detay .qr img { width:112px; height:112px; border-radius:.4rem; background:#fff; flex:none }
        .mehse-detay .adres { font-family:ui-monospace,monospace; font-size:.72rem; word-break:break-all; color:rgb(107 114 128) }
        .mehse-detay .dugmeler { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.6rem }
        .mehse-detay table { width:100%; border-collapse:collapse; font-size:.8rem }
        .mehse-detay th { text-align:left; font-weight:600; color:rgb(107 114 128); padding:.35rem .4rem; border-bottom:1px solid rgb(107 114 128 / .25) }
        .mehse-detay td { padding:.4rem; border-bottom:1px solid rgb(107 114 128 / .12); vertical-align:top }
        .mehse-detay .rozet { display:inline-flex; align-items:center; gap:.25rem; font-size:.72rem; font-weight:700; border-radius:999px; padding:.15rem .5rem; white-space:nowrap }
        .mehse-detay .r-ok { color:#15803d; background:rgb(21 128 61 / .1) } .mehse-detay .r-red { color:#b91c1c; background:rgb(185 28 28 / .1) }
        .mehse-detay .r-sari { color:#a16207; background:rgb(161 98 7 / .12) } .mehse-detay .r-gri { color:rgb(75 85 99); background:rgb(107 114 128 / .12) }
        .dark .mehse-detay .r-ok { color:#4ade80 } .dark .mehse-detay .r-red { color:#f87171 } .dark .mehse-detay .r-sari { color:#facc15 } .dark .mehse-detay .r-gri { color:#d1d5db }
        .mehse-detay .bos { color:rgb(107 114 128) }
        .mehse-detay ul.ud { margin:.2rem 0 0; padding-left:1rem; color:#b91c1c } .dark .mehse-detay ul.ud { color:#f87171 }
    </style>

    <div class="ust">
        <section class="kutu" aria-labelledby="d-qr">
            <h3 id="d-qr">Operatör QR erişimi</h3>
            <div class="qr">
                <img src="{{ $e->qrDataUri() }}" alt="{{ $e->ekipman_adi }} operatör QR kodu">
                <div style="min-width:0">
                    <p style="margin:0 0 .4rem">Operatör QR'ı okutunca giriş yapmadan talimatı okur ve günlük kontrolü doldurur.
                        {{ $e->pinGerekliMi() ? 'PIN tanımlı.' : 'PIN yok.' }}</p>
                    <div class="adres">{{ $e->operatorAdresi() }}</div>
                    <div class="dugmeler">
                        <x-filament::button tag="a" size="xs" color="gray" icon="heroicon-m-arrow-top-right-on-square" :href="$e->operatorAdresi()" target="_blank">Operatör ekranını aç</x-filament::button>
                        <x-filament::button size="xs" color="gray" icon="heroicon-m-cog-6-tooth" wire:click="mountAction('ekipmanAyar', { id: {{ $e->id }} })">Ayarlar / PIN</x-filament::button>
                    </div>
                </div>
            </div>
            <dl style="display:grid;grid-template-columns:auto 1fr;gap:.2rem .8rem;margin:.8rem 0 0;font-size:.8rem">
                <dt class="bos">Marka / model</dt><dd style="margin:0">{{ $e->marka_model ?: '—' }}{{ $e->uretim_yili ? ' ('.$e->uretim_yili.')' : '' }}</dd>
                <dt class="bos">Bakım sorumlusu</dt><dd style="margin:0">{{ $e->bakim_sorumlusu ?: '—' }}{{ $e->bakim_sorumlusu_eposta ? ' · '.$e->bakim_sorumlusu_eposta : '' }}</dd>
                <dt class="bos">Periyodik kontrol</dt><dd style="margin:0">{{ $e->sonraki_vize_tarihi?->format('d.m.Y') ?? '—' }}{{ $e->kalanGun() !== null ? ' ('.$e->kalanGun().' gün)' : '' }}</dd>
            </dl>
        </section>

        <section class="kutu" aria-labelledby="d-belge">
            <h3 id="d-belge">Belgeler ve tebliğ</h3>
            <p style="margin:0 0 .5rem">
                @if ($teblig === 'edildi')
                    <span class="rozet r-ok">✓ İşverene tebliğ edildi — {{ $e->teblig_tarihi?->format('d.m.Y') }}</span>
                @elseif ($teblig === 'bekliyor')
                    <span class="rozet r-sari">Çıktı alındı — imza bekliyor ({{ $e->teblig_cikti_at->format('d.m.Y') }})</span>
                @else
                    <span class="rozet r-gri">Tebliğ paketi henüz alınmadı</span>
                @endif
            </p>
            <ul style="margin:0;padding-left:1rem">
                <li>Talimat: {{ $talimat?->baslik ?? 'firmada yok' }}</li>
                <li>Günlük kontrol formu: {{ $form['ad'] ?? 'kütüphanede yok' }}</li>
            </ul>
            <div class="dugmeler">
                <x-filament::button size="xs" icon="heroicon-m-printer" wire:click="tebligPaketiIndir({{ $e->id }})">Tebliğ paketini indir (3 PDF)</x-filament::button>
                <x-filament::button size="xs" color="gray" icon="heroicon-m-camera" wire:click="mountAction('tebligYukle', { id: {{ $e->id }} })">İmzalı nüshayı yükle</x-filament::button>
                @if ($e->imzali_belge_yolu)
                    <x-filament::button size="xs" color="gray" icon="heroicon-m-paper-clip" wire:click="imzaliNushaIndir({{ $e->id }})">İmzalı nüsha</x-filament::button>
                @endif
                @if ($talimat)
                    <x-filament::button size="xs" color="gray" icon="heroicon-m-document-text" wire:click="talimatPdf({{ $talimat->id }})">Talimat (standart PDF)</x-filament::button>
                @endif
            </div>
            <p class="bos" style="margin:.6rem 0 0;font-size:.75rem">Paket: talimat (A4 dikey) · aylık 1–31 gün kontrol çizelgesi (A4 yatay) · tebliğ tutanağı. Hepsinde ekipmanın QR'ı var.</p>
        </section>
    </div>

    <section class="kutu" aria-labelledby="d-bakim">
        <h3 id="d-bakim">Bakım / arıza kayıtları
            @if ($acik->isNotEmpty())<span class="rozet r-red">{{ $acik->count() }} açık — kullanılmamalı</span>@endif
        </h3>
        @if ($bakimlar->isEmpty())
            <p class="bos" style="margin:0">Kayıt yok. Günlük kontrolde "Uygun değil" işaretlenince arıza kaydı kendiliğinden açılır.</p>
        @else
            <table>
                <thead><tr><th>Açılış</th><th>Kayıt</th><th>Durum</th><th></th></tr></thead>
                <tbody>
                    @foreach ($bakimlar as $b)
                        <tr>
                            <td style="white-space:nowrap">{{ $b->acilis_zamani->format('d.m.Y H:i') }}</td>
                            <td>
                                <strong>{{ $b->baslik }}</strong>
                                @if ($b->uygunsuz_maddeler)
                                    <ul class="ud">@foreach ($b->uygunsuz_maddeler as $m)<li>{{ $m['soru'] }}@if (filled($m['not'] ?? null)) — <em>{{ $m['not'] }}</em>@endif</li>@endforeach</ul>
                                @endif
                                @if ($b->bildiren)<div class="bos">Bildiren: {{ $b->bildiren }}</div>@endif
                                @if ($b->kapanis_notu)<div class="bos">Kapanış ({{ $b->kapanis_zamani?->format('d.m.Y') }}): {{ $b->kapanis_notu }}</div>@endif
                            </td>
                            <td>
                                <span @class(['rozet', 'r-red' => $b->durum !== 'kapali', 'r-ok' => $b->durum === 'kapali'])>{{ \App\Models\IsEkipmaniBakimi::DURUMLAR[$b->durum] ?? $b->durum }}</span>
                            </td>
                            <td style="text-align:right">
                                @if ($b->durum !== 'kapali')
                                    <x-filament::button size="xs" color="gray" wire:click="mountAction('bakimKapat', { id: {{ $b->id }} })">Kapat</x-filament::button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="kutu" aria-labelledby="d-gunluk">
        <h3 id="d-gunluk">Günlük kontrol geçmişi <span class="bos" style="font-weight:400">(son 30)</span></h3>
        @if ($gunlukler->isEmpty())
            <p class="bos" style="margin:0">Henüz günlük kontrol girilmedi. Operatör QR'ı okutup doldurduğunda burada görünür.</p>
        @else
            <table>
                <thead><tr><th>Tarih</th><th>Operatör</th><th>Sonuç</th><th>Not</th></tr></thead>
                <tbody>
                    @foreach ($gunlukler as $g)
                        <tr>
                            <td style="white-space:nowrap">{{ $g->kontrol_zamani->format('d.m.Y H:i') }}</td>
                            <td>{{ $g->operator_adi }} <span class="bos">· {{ $g->kaynak === 'qr' ? 'QR' : 'panel' }}</span></td>
                            <td>
                                @if ($g->kullanima_uygun)
                                    <span class="rozet r-ok">✓ Uygun</span>
                                @else
                                    <span class="rozet r-red">✕ {{ $g->uygunsuz_sayisi }} uygunsuz</span>
                                @endif
                            </td>
                            <td class="bos">{{ $g->notu ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
