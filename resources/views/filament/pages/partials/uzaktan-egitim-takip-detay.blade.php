{{-- Takip detayı (Yönetmelik Md.12/4 + Md.16): bir atamanın tüm kayıtları. Uzaktan Eğitim Atama sayfasından @include edilir. --}}
@php
    $d = $this->detay;
    $cl = $d->calisan;
    $dersler = $d->paket->tumDersler()->whereNotNull('video_url')->get();
    $ilerleme = $d->ilerlemeler->keyBy('egitim_dersi_id');
    $dk = fn (?int $sn) => $sn ? floor($sn / 60).' dk '.str_pad((string) ($sn % 60), 2, '0', STR_PAD_LEFT).' sn' : '—';
    $sinavlar = $d->sinavSonuclari->sortBy('deneme_no');
    $soluk = 'color:rgb(107 114 128)';
    $hucre = 'padding:.35rem .5rem';
    $bekleyen = $d->paket->videoBekleyenSayisi();
@endphp
<x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
    <x-slot name="heading">Takip Detayı — {{ $cl->ad_soyad }}</x-slot>
    <x-slot name="description">Yönetmelik Md.12/4: giriş-çıkış kayıtları, tamamlama oranları ve ölçme-değerlendirme sonuçları. {{ $d->paket->ad }} · {{ $d->turEtiketi() }}</x-slot>

    {{-- Özet --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:.75rem;font-size:.83rem">
        @foreach ([
            ['Durum', $d->durumEtiketi().($d->gecikti() ? ' · gecikti' : '')],
            ['Tamamlanan ders', $d->izlenenDersSayisi().' / '.$d->toplamDersSayisi().' (%'.$d->ilerlemeYuzdesi().')'],
            ['Fiili izleme', $dk($d->izlenenSure())],
            ['Ön test', $d->on_test_puani !== null ? $d->on_test_puani.' puan · '.$d->on_test_at?->format('d.m.Y H:i') : 'Çözmedi'],
            ['Final sınavı', $sinavlar->isEmpty() ? 'Girmedi' : ($d->sonSinav()->puan.' puan · '.($d->basariliMi() ? 'GEÇTİ' : 'kaldı'))],
            ['Kalan sınav hakkı', $d->basariliMi() ? '—' : $d->kalanSinavHakki().' / '.config('isg.uzaktan_egitim.sinav_hakki', 3).($d->yeniden_baslatma ? ' · '.$d->yeniden_baslatma.' kez baştan başladı' : '')],
            ['T.C. Kimlik No', $cl->maskeliTc() ?: '—'],
            ['KVKK + taahhüt onayı', $cl->kvkk_onay_at ? $cl->kvkk_onay_at->format('d.m.Y H:i').' · IP '.$cl->kvkk_onay_ip : 'Onaylamadı'],
            ['Atandı / son tarih', $d->atandi_at?->format('d.m.Y').' / '.($d->son_tarih?->format('d.m.Y') ?? '—')],
            ['Tamamlanma', $d->tamamlandi_at?->format('d.m.Y H:i') ?? '—'],
        ] as [$etiket, $deger])
            <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.5rem;padding:.55rem .7rem">
                <div style="font-size:.75rem;{{ $soluk }}">{{ $etiket }}</div>
                <div style="font-weight:600;margin-top:.15rem">{{ $deger }}</div>
            </div>
        @endforeach
    </div>

    {{-- Konu başlığı / video bazında --}}
    <div style="font-weight:700;margin:1.1rem 0 .4rem;font-size:.9rem">Konu başlığına göre izleme</div>
    <div style="overflow-x:auto">
        <table style="width:100%;font-size:.8rem;border-collapse:collapse;min-width:640px">
            <thead><tr style="text-align:left;background:rgb(107 114 128 / .08)">
                <th style="{{ $hucre }}">Video</th><th style="{{ $hucre }}">Video süresi</th>
                <th style="{{ $hucre }}">İzlenen</th><th style="{{ $hucre }}">Oran</th><th style="{{ $hucre }}">Durum</th>
            </tr></thead>
            <tbody>
            @foreach ($dersler->groupBy(fn ($x) => $x->modul ?? 0) as $modul => $grup)
                @php $biten = $grup->filter(fn ($x) => $ilerleme->get($x->id)?->izlendi)->count(); @endphp
                <tr style="background:rgb(79 70 229 / .06)">
                    <td colspan="5" style="{{ $hucre }};font-weight:700">
                        {{ $modul ? $modul.'. '.\App\Models\EgitimDersi::MODULLER[$modul] : 'Diğer dersler' }}
                        <span style="font-weight:400;{{ $soluk }}">· {{ $biten }}/{{ $grup->count() }} video · %{{ (int) round($biten / max(1, $grup->count()) * 100) }}</span>
                    </td>
                </tr>
                @foreach ($grup as $x)
                    @php $il = $ilerleme->get($x->id); @endphp
                    <tr style="border-top:1px solid rgb(107 114 128 / .12)">
                        <td style="{{ $hucre }}">{{ $x->siraEtiketi() }}. {{ \Illuminate\Support\Str::limit($x->baslik, 60) }}</td>
                        <td style="{{ $hucre }}">{{ $dk($x->sure_sn) }}</td>
                        <td style="{{ $hucre }}">{{ $il ? $dk($il->izlenen_sn) : 'Hiç açmadı' }}</td>
                        <td style="{{ $hucre }}">{{ $il ? '%'.$il->izleme_yuzdesi : '—' }}</td>
                        <td style="{{ $hucre }}">
                            @if ($il?->izlendi)
                                <span style="color:rgb(21 128 61);font-weight:600">✓ İzlendi</span> <span style="font-size:.75rem;{{ $soluk }}">{{ $il->izlendi_at?->format('d.m.Y H:i') }}</span>
                            @elseif ($il)
                                <span style="color:#b45309">Devam ediyor</span>
                            @else
                                <span style="{{ $soluk }}">Başlamadı</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach
            @if ($bekleyen > 0)
                <tr><td colspan="5" style="{{ $hucre }};font-size:.75rem;{{ $soluk }}">Not: {{ $bekleyen }} konu başlığının videosu henüz bağlanmadığı için çalışana gösterilmiyor.</td></tr>
            @endif
            </tbody>
        </table>
    </div>

    {{-- Oturumlar --}}
    <div style="font-weight:700;margin:1.1rem 0 .4rem;font-size:.9rem">Giriş-çıkış kayıtları (oturumlar)</div>
    @if ($d->girisler->isEmpty())
        <div style="font-size:.8rem;{{ $soluk }}">Bu eğitim henüz açılmadı.</div>
    @else
        <div style="overflow-x:auto">
            <table style="width:100%;font-size:.8rem;border-collapse:collapse;min-width:640px">
                <thead><tr style="text-align:left;background:rgb(107 114 128 / .08)">
                    <th style="{{ $hucre }}">Giriş</th><th style="{{ $hucre }}">Çıkış (son etkinlik)</th><th style="{{ $hucre }}">Net izleme</th>
                    <th style="{{ $hucre }}">Aktif katılım cevabı</th><th style="{{ $hucre }}">IP</th><th style="{{ $hucre }}">Cihaz / tarayıcı</th>
                </tr></thead>
                <tbody>
                @foreach ($d->girisler as $g)
                    <tr style="border-top:1px solid rgb(107 114 128 / .12)">
                        <td style="{{ $hucre }}">{{ $g->giris_at->format('d.m.Y H:i') }}</td>
                        <td style="{{ $hucre }}">{{ $g->cikis_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        <td style="{{ $hucre }}">{{ $dk($g->izleme_sn) }}</td>
                        <td style="{{ $hucre }}">{{ $g->yoklama_sayisi }}</td>
                        <td style="{{ $hucre }}">{{ $g->ip ?: '—' }}</td>
                        <td style="{{ $hucre }};font-size:.75rem;{{ $soluk }}">{{ \Illuminate\Support\Str::limit($g->cihaz ?: '—', 70) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Ön test --}}
    <div style="font-weight:700;margin:1.1rem 0 .4rem;font-size:.9rem">Ön test (seviye tespiti, Md.16/1)</div>
    @if ($d->on_test_at === null)
        <div style="font-size:.8rem;{{ $soluk }}">Henüz çözmedi.</div>
    @elseif (empty($d->on_test_cevaplar))
        <div style="font-size:.8rem;{{ $soluk }}">{{ $d->on_test_puani }} puan · {{ $d->on_test_at->format('d.m.Y H:i') }} (bu test soru bazlı cevap kaydı başlamadan önce çözüldü, yalnız puanı var).</div>
    @else
        @php $ot = collect($d->on_test_cevaplar); $otDogru = $ot->filter(fn ($c) => isset($c['verilen_index'], $c['dogru_index']) && (int) $c['verilen_index'] === (int) $c['dogru_index'])->count(); @endphp
        <details style="border:1px solid rgb(107 114 128 / .2);border-radius:.5rem;padding:.5rem .7rem">
            <summary style="cursor:pointer;font-size:.83rem">
                <strong>Ön test</strong> · {{ $d->on_test_at->format('d.m.Y H:i') }} · {{ $d->on_test_puani }} puan ({{ $otDogru }}/{{ $ot->count() }} doğru)
                <span style="{{ $soluk }}">· puan geçme şartı değildir, yalnız seviye tespiti</span>
            </summary>
            @include('filament.pages.partials.uzaktan-egitim-cevaplar', ['cevaplar' => $d->on_test_cevaplar])
        </details>
    @endif

    {{-- Sınav denemeleri --}}
    <div style="font-weight:700;margin:1.1rem 0 .4rem;font-size:.9rem">Final sınavı (Md.16): denemeler</div>
    @if ($sinavlar->isEmpty())
        <div style="font-size:.8rem;{{ $soluk }}">Henüz final sınavına girmedi.</div>
    @else
        @foreach ($sinavlar as $sn)
            <details style="border:1px solid rgb(107 114 128 / .2);border-radius:.5rem;padding:.5rem .7rem;margin-bottom:.45rem" @if ($loop->last) open @endif>
                <summary style="cursor:pointer;font-size:.83rem">
                    <strong>{{ $sn->deneme_no }}. deneme</strong> · {{ $sn->tamamlandi_at?->format('d.m.Y H:i') }} ·
                    {{ $sn->puan }} puan ({{ $sn->dogruSayisi() }}/{{ $sn->toplamSoru() }} doğru) ·
                    <span style="color:{{ $sn->gecti ? 'rgb(21 128 61)' : 'rgb(185 28 28)' }};font-weight:700">{{ $sn->gecti ? 'GEÇTİ' : 'KALDI' }}</span>
                    <span style="{{ $soluk }}">(geçme: {{ $d->gecmePuani() }})</span>
                </summary>
                @include('filament.pages.partials.uzaktan-egitim-cevaplar', ['cevaplar' => $sn->cevaplar])
            </details>
        @endforeach
    @endif

    {{-- Onay ve belge --}}
    <div style="font-weight:700;margin:1.1rem 0 .4rem;font-size:.9rem">Onay ve belge</div>
    <div style="font-size:.8rem;line-height:1.7">
        <div>4. konu (işyerine özgü): <strong>{{ $d->dorduncuKonuYuzYuzeMi() ? 'yüz yüze verilir' : 'uzaktan verilebilir' }}</strong>
            @if ($d->dorduncuKonuYuzYuzeMi())
                · İGU onayı: <strong>{{ $d->igu_onay_at ? $d->igu_onay_at->format('d.m.Y H:i') : 'bekliyor' }}</strong>
            @endif
        </div>
        <div>Eğitim kaydına işlenme: <strong>{{ ! $d->tamamlandi_at ? '—' : ($d->egitimKaydinaIslenirMi() ? 'İşlendi' : 'Bekliyor (4. konu yüz yüze)') }}</strong></div>
        @if ($d->belgeAcikMi())
            <div>Belge doğrulama adresi: <a href="{{ $d->dogrulamaUrl() }}" target="_blank" style="color:rgb(79 70 229)">{{ $d->dogrulamaUrl() }}</a></div>
        @endif
    </div>
</x-filament::section>
