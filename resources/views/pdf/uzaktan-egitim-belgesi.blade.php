<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 0; }
    body { margin: 0; color: #1f2937; }
    .cerceve { margin: 26px; border: 3px double #0f766e; padding: 30px 44px; height: 480px; position: relative; }
    .ust { text-align: center; }
    .marka { font-size: 11px; letter-spacing: 2px; color: #0f766e; }
    h1 { font-size: 30px; margin: 14px 0 2px; letter-spacing: 1px; }
    .alt-baslik { font-size: 12px; color: #6b7280; }
    .icerik { text-align: center; margin-top: 30px; line-height: 1.7; }
    .isim { font-size: 24px; font-weight: bold; margin: 10px 0; border-bottom: 1px solid #9ca3af; display: inline-block; padding: 0 24px 4px; }
    .egitim { font-size: 15px; font-weight: bold; margin-top: 8px; }
    .kunye { margin-top: 20px; font-size: 11px; color: #4b5563; }
    .kunye span { display: inline-block; margin: 0 12px; }
    .imza { position: absolute; bottom: 20px; left: 44px; right: 44px; }
    .imza table { width: 100%; }
    .imza td { text-align: center; font-size: 10px; padding-top: 34px; border-top: 1px solid #9ca3af; width: 33%; }
    .belge-no { position: absolute; top: 30px; right: 44px; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>
<div class="cerceve">
    <div class="belge-no">Belge No: UE-{{ $atama->id }} · {{ now()->format('d.m.Y') }}</div>

    <div class="ust">
        <div class="marka">MEHSE · İŞ SAĞLIĞI VE GÜVENLİĞİ</div>
        <h1>UZAKTAN EĞİTİM KATILIM BELGESİ</h1>
        <div class="alt-baslik">6331 sayılı Kanun ve Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik uyarınca</div>
    </div>

    <div class="icerik">
        Aşağıda kimliği belirtilen çalışan,
        <div class="isim">{{ $calisan?->ad_soyad ?? '—' }}</div>
        <br>
        <span class="egitim">“{{ $paket?->ad }}”</span><br>
        uzaktan eğitim programını tamamlamış ve yapılan değerlendirme sınavında
        <strong>%{{ $sinav?->puan ?? '—' }}</strong> puan alarak <strong>başarılı</strong> olmuştur.
    </div>

    <div class="kunye">
        <span><strong>İşyeri:</strong> {{ $firma?->unvan ?? '—' }}</span>
        <span><strong>SGK Sicil No:</strong> {{ $firma?->sgk_sicil_no ?? '—' }}</span>
        <span><strong>Eğitim türü:</strong> {{ config('isg.uzaktan_egitim.egitim_turleri.'.$atama->egitim_turu, $atama->egitim_turu) }}</span>
        <br>
        <span><strong>Ders sayısı:</strong> {{ $paket?->dersler->count() }}</span>
        <span><strong>Toplam süre:</strong> ~{{ $paket?->toplamSureDk() }} dk</span>
        <span><strong>Tamamlanma:</strong> {{ $atama->tamamlandi_at?->format('d.m.Y') }}</span>
    </div>

    <div class="imza">
        <table>
            <tr>
                <td>Eğitimi Düzenleyen<br>{{ $atama->atayan?->name ?? 'İş Güvenliği Uzmanı' }}</td>
                <td>İşyeri Hekimi</td>
                <td>İşveren / İşveren Vekili</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
