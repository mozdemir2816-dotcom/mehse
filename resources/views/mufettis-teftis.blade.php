<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Salt-Okunur İSG Teftiş Dosyası | Mehse İSG</title>
    <style>
        body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:#f8fafc; color:#0f172a; padding:1.25rem; }
        .kap { max-width:980px; margin:0 auto; }
        .kart { background:#fff; border:1px solid #cbd5e1; border-radius:14px; padding:1.1rem 1.25rem; margin-bottom:1rem; }
        .ust { background:linear-gradient(135deg,#0f172a,#1e3a8a); color:#fff; border:none; }
        .rozet { display:inline-block; font-size:.72rem; font-weight:700; padding:.2rem .6rem; border-radius:999px; }
        .ok { background:#dcfce7; color:#166534; }
        .eksik { background:#fee2e2; color:#991b1b; }
        .muaf { background:#e2e8f0; color:#475569; }
        table { width:100%; border-collapse:collapse; font-size:.86rem; }
        th, td { text-align:left; padding:.55rem .65rem; border-bottom:1px solid #e2e8f0; }
        th { font-size:.76rem; color:#64748b; text-transform:uppercase; }
        .izgara { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:.75rem; margin-top:.75rem; }
        .kutu { background:rgb(255 255 255/.1); border-radius:10px; padding:.65rem .8rem; font-size:.84rem; }
    </style>
</head>
<body>
<div class="kap">
    @if (! $gecerli)
        <div class="kart" style="text-align:center;padding:2.5rem">
            <div style="font-size:2.4rem">🔒</div>
            <h1 style="font-size:1.25rem">Teftiş Bağlantısı Geçersiz veya Süresi Dolmuş</h1>
            <p style="color:#64748b;font-size:.92rem">Bu salt-okunur teftiş linkinin süresi dolmuş veya İSG uzmanı tarafından iptal edilmiştir.</p>
        </div>
    @else
        <div class="kart ust">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
                <div>
                    <span class="rozet" style="background:rgb(255 255 255/.18);color:#fff">🔍 SALT-OKUNUR TEFTİŞ ERİŞİMİ</span>
                    <h1 style="margin:.45rem 0 .2rem;font-size:1.45rem">{{ $firma->unvan }}</h1>
                    <div style="opacity:.85;font-size:.88rem">{{ $paylasim->baslik }}</div>
                </div>
                <div style="text-align:right;font-size:.8rem;opacity:.9">
                    <div>Geçerlilik Sonu: <strong>{{ $paylasim->bitis_tarihi->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</strong></div>
                    <div>Erişim Sayısı: <strong>{{ $paylasim->erisim_sayisi }}</strong></div>
                    @if ($paylasim->mufettis_adi)<div>Yetkili: <strong>{{ $paylasim->mufettis_adi }}</strong></div>@endif
                </div>
            </div>
            <div class="izgara">
                <div class="kutu"><div style="opacity:.7;font-size:.75rem">NACE / Tehlike Sınıfı</div><strong>{{ $firma->nace_kodu ?: '—' }} · {{ $firma->tehlikeSinifiEtiketi() }}</strong></div>
                <div class="kutu"><div style="opacity:.7;font-size:.75rem">Çalışan Sayısı</div><strong>{{ $firma->calisan_sayisi ?: '—' }} kişi</strong></div>
                <div class="kutu"><div style="opacity:.7;font-size:.75rem">İş Güvenliği Uzmanı</div><strong>{{ $firma->igu?->ad_soyad ?? $paylasim->user?->name ?? '—' }}</strong></div>
                <div class="kutu"><div style="opacity:.7;font-size:.75rem">İşyeri Hekimi</div><strong>{{ $firma->isyeriHekimi?->ad_soyad ?? '—' }}</strong></div>
            </div>
        </div>

        @if ($paylasim->notlar)
            <div class="kart" style="border-left:4px solid #2563eb">
                <strong>Uzman Notu:</strong> {{ $paylasim->notlar }}
            </div>
        @endif

        <div class="kart">
            <h2 style="margin:0 0 .75rem;font-size:1.05rem">1. 6331 Sayılı Kanun Yasal Uyum Kontrol Listesi</h2>
            <table>
                <thead><tr><th>Yasal Kriter</th><th>Durum</th></tr></thead>
                <tbody>
                    @foreach ($checklist as $k)
                        <tr>
                            <td>{{ $k['ad'] }}</td>
                            <td>
                                @if ($k['muaf'] ?? false)
                                    <span class="rozet muaf">Kapsam Dışı / Muaf</span>
                                @elseif ($k['tamam'] ?? false)
                                    <span class="rozet ok">✓ Mevcut / Uyumlu</span>
                                @else
                                    <span class="rozet eksik">Eksik / Bekliyor</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="kart">
            <h2 style="margin:0 0 .75rem;font-size:1.05rem">2. Risk Değerlendirmeleri ({{ $riskler->count() }})</h2>
            @forelse ($riskler as $r)
                <div style="padding:.45rem 0;border-bottom:1px solid #e2e8f0;font-size:.88rem">
                    <strong>{{ $r->belge_no ?: 'Risk Değerlendirmesi #'.$r->id }}</strong>
                    · Yöntem: {{ $r->yontem ?: '5x5' }}
                    · Tarih: {{ ($r->rapor_tarihi ?? $r->created_at)?->format('d.m.Y') }}
                    @if ($r->gecerlilik_tarihi) · Geçerlilik: {{ $r->gecerlilik_tarihi->format('d.m.Y') }} @endif
                </div>
            @empty
                <p style="font-size:.86rem;color:#64748b">Kayıtlı risk değerlendirmesi yok.</p>
            @endforelse
        </div>

        <div class="kart">
            <h2 style="margin:0 0 .75rem;font-size:1.05rem">3. Periyodik Kontrol &amp; İş Ekipmanları ({{ $ekipmanlar->count() }})</h2>
            <table>
                <thead><tr><th>Ekipman</th><th>Son Muayene</th><th>Sonraki Muayene</th><th>Kontrol Kuruluşu / Durum</th></tr></thead>
                <tbody>
                    @forelse ($ekipmanlar as $e)
                        <tr>
                            <td><strong>{{ $e->ekipman_adi }}</strong></td>
                            <td>{{ $e->son_muayene_tarihi?->format('d.m.Y') ?: '—' }}</td>
                            <td>{{ $e->sonraki_vize_tarihi?->format('d.m.Y') ?: '—' }}</td>
                            <td>{{ $e->muayene_yapan ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:#64748b">Kayıtlı ekipman bulunmuyor.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="kart">
            <h2 style="margin:0 0 .75rem;font-size:1.05rem">4. İSG Eğitim Oturumları ({{ $egitimler->count() }}) &amp; Kurul Toplantıları ({{ $kurullar->count() }})</h2>
            @if ($egitimler->isNotEmpty())
                <table>
                    <thead><tr><th>Eğitim Konusu / Başlık</th><th>Tarih</th><th>Süre</th></tr></thead>
                    <tbody>
                        @foreach ($egitimler as $eg)
                            <tr>
                                <td><strong>{{ method_exists($eg, 'basliklarEtiketi') ? ($eg->basliklarEtiketi() ?: 'Temel İSG Eğitimi') : 'Temel İSG Eğitimi' }}</strong></td>
                                <td>{{ ($eg->belge_tarihi ?? $eg->created_at)?->format('d.m.Y') ?: '—' }}</td>
                                <td>{{ $eg->sure_saat ? $eg->sure_saat.' saat' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="font-size:.86rem;color:#64748b">Kayıtlı yüz yüze eğitim oturumu bulunmuyor.</p>
            @endif
            @if ($kurullar->isNotEmpty())
                <table style="margin-top:.75rem">
                    <thead><tr><th>İSG Kurulu Toplantısı</th><th>Tarih</th><th>Durum</th></tr></thead>
                    <tbody>
                        @foreach ($kurullar as $kr)
                            <tr>
                                <td><strong>{{ $kr->toplanti_no ?: 'Toplantı #'.$kr->id }}</strong></td>
                                <td>{{ ($kr->toplanti_tarihi ?? $kr->created_at)?->format('d.m.Y') ?: '—' }}</td>
                                <td>{{ $kr->durum ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="kart">
            <h2 style="margin:0 0 .75rem;font-size:1.05rem">5. Yasal Arşiv Evrakları ({{ $arsivler->count() }})</h2>
            <table>
                <thead><tr><th>Belge Adı</th><th>Kategori</th><th>Başlangıç</th><th>Geçerlilik Bitiş</th></tr></thead>
                <tbody>
                    @forelse ($arsivler as $a)
                        <tr>
                            <td><strong>{{ $a->etiket() }}</strong></td>
                            <td>{{ $a->kategori }}</td>
                            <td>{{ $a->baslangic_tarihi?->format('d.m.Y') ?: '—' }}</td>
                            <td>{{ $a->gecerlilik_sonu?->format('d.m.Y') ?: 'Süresiz' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:#64748b">Yüklü arşiv evrakı bulunmuyor.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p style="text-align:center;font-size:.76rem;color:#64748b">
            KVKK gereği çalışanların kişisel sağlık verileri bu özet teftiş ekranında maskelenmektedir. Tüm erişimler tarih ve IP ile kayıt altına alınır.
        </p>
    @endif
</div>
</body>
</html>
