<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 24px 30px; }
    body { margin: 0; color: #111827; font-size: 9.8px; line-height: 1.42; }
    .baslik { text-align: center; border-bottom: 3px double #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
    .baslik h1 { font-size: 14px; margin: 0 0 3px; color: #0f172a; }
    .baslik .alt { font-size: 9.5px; color: #475569; }
    h2 { font-size: 11px; margin: 12px 0 5px; padding: 4px 8px; background: #0f172a; color: #fff; border-radius: 3px; }
    table.kunye, table.liste { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9.2px; }
    table.kunye td, table.liste th, table.liste td { border: 1px solid #94a3b8; padding: 4px 6px; vertical-align: top; }
    table.kunye td.etiket { background: #f1f5f9; font-weight: bold; width: 23%; }
    table.liste th { background: #e2e8f0; text-align: left; font-weight: bold; }
    .rozet-zorunlu { display: inline-block; padding: 2px 7px; border-radius: 3px; background: #dc2626; color: #fff; font-weight: bold; font-size: 8.5px; }
    .rozet-bilgi { display: inline-block; padding: 2px 7px; border-radius: 3px; background: #0284c7; color: #fff; font-weight: bold; font-size: 8.5px; }
    .kutu { border: 1px solid #cbd5e1; background: #f8fafc; padding: 7px 9px; border-radius: 4px; margin-bottom: 10px; font-size: 9px; }
    table.imza { width: 100%; border-collapse: collapse; margin-top: 18px; font-size: 9px; page-break-inside: avoid; }
    table.imza td { border: 1px solid #94a3b8; width: 33.33%; text-align: center; padding: 6px 6px 36px; vertical-align: top; }
    table.imza .rol { font-weight: bold; background: #f1f5f9; display: block; padding: 3px; margin: -6px -6px 6px; border-bottom: 1px solid #94a3b8; }
</style>
</head>
<body>

    <div class="baslik">
        <h1>YAPI İŞLERİ SAĞLIK VE GÜVENLİK PLANI (SGP)</h1>
        <div class="alt">
            6331 Sayılı İSG Kanunu ve Yapı İşlerinde İş Sağlığı ve Güvenliği Yönetmeliği (m.5, m.7, m.8, Ek-1, Ek-2, Ek-3) Uyarınca Düzenlenmiştir
        </div>
    </div>

    <h2>1. YAPI İŞYERİ / ŞANTİYE KÜNYESİ VE EK-3 BİLDİRİM ÖZETİ</h2>
    <table class="kunye">
        <tr>
            <td class="etiket">İşveren / Firma Ünvanı</td><td>{{ $firma->unvan }}</td>
            <td class="etiket">SGK Sicil / NACE</td><td>{{ $firma->sgk_sicil_no ?: '—' }} / {{ $firma->nace_kodu ?: '—' }}</td>
        </tr>
        <tr>
            <td class="etiket">Proje / Şantiye Adı</td><td>{{ $veri['santiye_adi'] }}</td>
            <td class="etiket">Şantiye Adresi</td><td>{{ $veri['santiye_adresi'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="etiket">İşveren / Proje Müdürü</td><td>{{ $veri['proje_muduru'] }}</td>
            <td class="etiket">Başlangıç Tarihi</td><td>{{ $veri['baslangic_tarihi'] }}</td>
        </tr>
        <tr>
            <td class="etiket">Sağlık ve Güvenlik Koordinatörü</td>
            <td>Hazırlık: {{ $veri['hazirlik_koordinatoru'] }} / Uygulama: {{ $veri['uygulama_koordinatoru'] }}</td>
            <td class="etiket">İSG Profesyonelleri</td>
            <td>İGU: {{ $firma->igu?->ad_soyad ?: '—' }} | İH: {{ $firma->isyeriHekimi?->ad_soyad ?: '—' }}</td>
        </tr>
        <tr>
            <td class="etiket">Tahmini Süre / Azami Çalışan</td>
            <td>{{ $veri['tahmini_sure_gun'] }} İş Günü / Aynı Anda Azami {{ $veri['azami_calisan'] }} Çalışan</td>
            <td class="etiket">Toplam Yevmiye / Alt İşveren</td>
            <td>{{ $veri['toplam_yevmiye'] }} Yevmiye / {{ $veri['taseron_sayisi'] }} Alt İşveren (Taşeron)</td>
        </tr>
        <tr>
            <td class="etiket">Ek-3 Yapı İşleri Bildirimi</td>
            <td colspan="3">
                @if ($veri['ek3_zorunlu'])
                    <span class="rozet-zorunlu">EK-3 YAPI İŞLERİ BİLDİRİMİ ZORUNLU</span>
                    Yapı işi 30 iş gününden fazla sürecek ve devamlı 20'den fazla çalışan istihdam edilecek (veya 500 yevmiyeden fazla) olduğundan işe başlamadan önce Çalışma ve İş Kurumu İl Müdürlüğüne Ek-3 bildirimi yapılması zorunludur (Yönetmelik m.8).
                @else
                    <span class="rozet-bilgi">İHTİYARİ / STANDART SGP</span>
                    Girilen süre ve yevmiye değerleri Ek-3 bildirim eşiğinin altında olmakla birlikte, yapı sahasında birden fazla işveren/ekip çalışması halinde SGP uygulanması esastır.
                @endif
            </td>
        </tr>
    </table>

    <h2>2. YAPI İŞ FAZLARI, ÖZEL RİSK TAŞIYAN İŞLER (EK-2) VE GÜVENLİK TEDBİRLERİ</h2>
    <table class="liste">
        <tr>
            <th style="width:24%">Yapı İş Fazı / İş Kalemi</th>
            <th style="width:23%">Başlıca Tehlike ve Riskler</th>
            <th style="width:35%">Uygulanacak Teknik Önlem ve Saha Kuralları</th>
            <th style="width:18%">Zorunlu KKD</th>
        </tr>
        @foreach ($fazlar as $f)
            <tr>
                <td><strong>{{ $f['baslik'] }}</strong></td>
                <td>{{ $f['tehlikeler'] }}</td>
                <td>{{ $f['onlemler'] }}</td>
                <td>{{ $f['kkd'] }}</td>
            </tr>
        @endforeach
    </table>

    @if ($taseronlar->isNotEmpty())
        <h2>3. ŞANTİYEDE FAALİYET GÖSTEREN ALT İŞVERENLER (TAŞERON KOORDİNASYONU)</h2>
        <table class="liste">
            <tr>
                <th>Alt İşveren Ünvanı</th>
                <th>Yapılan İş / Faaliyet</th>
                <th>Yetkili / İletişim</th>
                <th>Sözleşme & Evrak Uyumu</th>
            </tr>
            @foreach ($taseronlar as $t)
                <tr>
                    <td><strong>{{ $t->unvan }}</strong></td>
                    <td>{{ $t->yapilan_is ?: '—' }}</td>
                    <td>{{ $t->yetkili_ad_soyad ?: '—' }} {{ $t->telefon ? '('.$t->telefon.')' : '' }}</td>
                    <td>SGK İşe Giriş, İSG Eğitimi, Periyodik Muayene (EK-2), MYK ve KKD Zimmet kontrolü zorunludur.</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($oncelikliRiskler->isNotEmpty())
        <h2>4. FİRMA RİSK DEĞERLENDİRMESİNDEN AKTARILAN ÖNCELİKLİ RİSK MADDELERİ</h2>
        <table class="liste">
            <tr>
                <th style="width:18%">Bölüm / Faaliyet</th>
                <th style="width:28%">Tehlike ve Risk</th>
                <th style="width:8%">Skor</th>
                <th style="width:46%">Kararlaştırılan Düzeltici / Önleyici Faaliyet</th>
            </tr>
            @foreach ($oncelikliRiskler as $m)
                <tr>
                    <td>{{ $m->bolum ?: $m->faaliyet ?: 'Saha Geneli' }}</td>
                    <td><strong>{{ $m->tehlike }}</strong> — {{ $m->risk }}</td>
                    <td style="text-align:center;font-weight:bold">{{ (int) $m->olasilik * (int) $m->siddet }}</td>
                    <td>{{ $m->oneri ?: $m->mevcut_onlem }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>5. ŞANTİYE GENEL KURALLARI, İŞ İZİN SİSTEMİ (PTW) VE ACİL DURUM ORGANİZASYONU</h2>
    <div class="kutu">
        <strong>• İşe Giriş ve MYK Zorunluluğu:</strong> SGK işe giriş bildirgesi, işe giriş sağlık raporu (EK-2), çok tehlikeli sınıfa uygun en az 16 saatlik İSG eğitimi, işbaşı oryantasyon tutanağı, KKD zimmet tutanağı ve MYK Mesleki Yeterlilik Belgesi olmayan hiçbir personel (taşeron dahil) sahada çalıştırılamaz.<br>
        <strong>• İş İzin Sistemi (Permit to Work):</strong> Yüksekte çalışma, sıcak iş (kaynak/kesme), derin kazı, kapalı alan, elektrik/EKED ve ağır kaldırma operasyonları öncesinde yazılı İş İzin Formu düzenlenir.<br>
        <strong>• Acil Durum ve Tahliye:</strong> Şantiye toplanma alanı girişte görünür şekilde işaretlenir. Acil durum telefonları (112 Tek Acil Çağrı, 187 Doğalgaz, 186 Elektrik) şantiye girişinde ilan edilir.
        @if (! empty($veri['ozel_kurallar']))
            <br><strong>• Projeye Özel Şantiye Kuralları:</strong> {{ $veri['ozel_kurallar'] }}
        @endif
    </div>

    <table class="imza">
        <tr>
            <td>
                <span class="rol">SAĞLIK VE GÜVENLİK KOORDİNATÖRÜ / İGU</span>
                {{ $veri['uygulama_koordinatoru'] }}<br>
                (İmza – Kaşe)
            </td>
            <td>
                <span class="rol">İŞYERİ HEKİMİ</span>
                {{ $firma->isyeriHekimi?->ad_soyad ?: '................................' }}<br>
                (İmza – Kaşe)
            </td>
            <td>
                <span class="rol">İŞVEREN / PROJE MÜDÜRÜ (ONAY)</span>
                {{ $veri['proje_muduru'] }}<br>
                (İmza – Kaşe)
            </td>
        </tr>
    </table>

</body>
</html>
