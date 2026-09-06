# mehse İSG — Mimari & Yol Haritası

Tek kişilik İş Güvenliği Uzmanı için İSG operasyon paneli. Referans ekran
görüntüleri: `C:\Users\mozde\Desktop\isgpratik\` (kullanıcı ekledikçe artıyor).

> **Kural:** `isgpratik` ekranları referanstır; UI/renk/buton TASARIMI kod/marka/
> metin olarak birebir kopyalanmaz, **modül ve akış** taklit edilir. Önceki
> projelerden (yeni-proje, katip-isg) **kod alınmaz** — sıfırdan.
>
> **İSTİSNA — resmi/yasal belge ÇIKTILARI (Word/Excel/PDF):** Kullanıcı gerçek
> bir referans belge paylaşırsa (isgpratik'ten indirilmiş bir çıktı, ya da
> tamamen farklı bir kaynak — örn. gerçek bir OSGB'nin kendi şablonu, bkz.
> Sertifika'daki "Yıldız Grup" Excel şablonu), o belge BİREBİR ŞABLON olarak
> kullanılır (yalnız firma/kişi/tarih gibi değişken alanlar değişir, genel/
> yasal metne dokunulmaz) — bkz. `[[resmi-belge-gercek-sablon-kullan]]` hafıza
> notu ve `AcilDurumWordUretici`/`AtamaYazisiWordUretici`/
> `SertifikaYildizGrupUretici` örnekleri. Referans yoksa, kendi tasarımımızla
> (dompdf) üretiriz — ASLA yönetmelik/görev-sorumluluk gibi resmi içeriği
> tahminle uydurmayız.

## Yığın

- Laravel 12 + Filament v5 (PHP 8.2, XAMPP)
- MySQL: `mehse` (root / şifresiz)
- Erişim: <http://localhost/mehse/> (kök `index.php` → `public/admin`) veya
  doğrudan <http://localhost/mehse/public/admin>
- Panel: tek panel `admin`, **koyu tema varsayılan**, birincil renk mor (`Color::Violet`),
  marka "mehse İSG"
- Giriş: `mozdemir2816@gmail.com` / `mehse2026`

## Temel kavram

```
Kullanıcı (İSG Uzmanı — hesap sahibi, "user")
  └── Firma (işyeri: SGK sicil, NACE, tehlike sınıfı, çalışan sayısı, sözleşme, logo)
        ├── Calisan (personel)
        ├── RiskDegerlendirmesi → RiskMaddesi
        ├── (tüm form / belge kayıtları firma_id'ye bağlı)
        └── Evrak / eğitim / ziyaret takibi
```

Bir uzman ~100 firmaya hizmet verir. OSGB kavramı yok; her şey tek uzmanın portföyü.

## Navigasyon grupları (sol menü)

> **isgpratik 1-3.jpg'ye göre tam menü kuruldu.** "planlandı" satırlar
> `App\Filament\Pages\HazirlanryorPage` türeten **stub** sayfalardır (nav'da görünür,
> "🚧 hazırlanıyor" + referans notu); ilgili ekran görüntüsü gelince gerçek sayfayla
> değiştirilir. `[AI]` rozeti `HazirlanryorPage::$aiModulu`. `NavigasyonTest` (31 test).

| Grup | Modül | Slug | Durum |
|---|---|---|---|
| **Yönetim** | Kontrol Merkezi (İSG Komuta Merkezi) | `kontrol-merkezi` | **hazır** (3 sekme; portföy karnesi — 12 kriter) |
| Yönetim | Profilim | `profilim` | **TAMAMLANDI** (künye + sayaçlar + isgpratik'in 11 sekmesinin tamamı — Faz 5) |
| Yönetim | İSG-KATİP Robot | `isg-katip-robot` | **hazır** (bilgi sayfası — gerçek eklenti yok, stub) |
| Yönetim | Mevzuat | `mevzuat` | **hazır** (İSG'ye özgü ~40 kalemlik kanun/yönetmelik/tebliğ/rehber dizini, arama + kategori filtresi) |
| **Risk Yönetimi** | Risk Değerlendirme (6 adımlı sihirbaz) | `risk-degerlendirme` | **hazır** — Manuel + Yapay Zeka (Gemini) yöntemleri + PDF çıktısı; Kayıtlı/Excel kalan |
| Risk Yönetimi | Kayıtlı Değerlendirmeler | `risk-degerlendirmelerim` | **hazır** |
| Risk Yönetimi | Sektör Şablonları | `risk-sablonlari` | **hazır** (sihirbazdan oluşur, burada yönetilir) |
| Risk Yönetimi | Risk Prosedürleri | `risk-prosedurleri` | **hazır** (Matris/Fine-Kinney gerçek prosedür metni + kendi .docx'ini yükleme — Faz 6) |
| Risk Yönetimi | Risk Kütüphanesi | `risk-kutuphanesi` | **hazır** |
| Risk Yönetimi | Acil Durum Planı | `acil-durum-plani` | **hazır** (firma + konu seçimi → PDF + 7 afiş) |
| Risk Yönetimi | Acil Durum Krokisi | `acil-durum-krokisi` | **hazır** (SVG editör — 8 çekirdek sembol, 90°'ye kenetlenen duvar, plan altlığı, PDF) |
| **Formlar & Belgeler** | DÖF Oluştur `[AI]` | `dof` | **hazır** (çoklu madde + Gemini öneri + otomatik kaşe → PDF; AI Saha Analizi'nden bulgu aktarımı kabul eder) |
| Formlar & Belgeler | AI Saha Analizi `[AI]` | `ai-saha-analizi` | **hazır** (Gemini vision fotoğraf analizi → İSG Saha Gözetim Raporu PDF; seçili bulgular DÖF Oluştur'a aktarılabilir) |
| Formlar & Belgeler | Saha Denetimi | `saha-denetimi` | **hazır** (9 kategori/41 madde + sektöre özel kendi başlık/madde ekleme, foto kanıtı → PDF isgpratik'in gerçek raporuyla birebir) |
| Formlar & Belgeler | Kurul Toplantısı `[AI]` | `kurul-toplantisi` | **hazır** (toplantı + katılımcı + gündem + AI karar önerisi + PDF tutanak) |
| Formlar & Belgeler | Atama Yazıları | `atama-yazilari` | **hazır** (10 görev tipi, tekli/ekip; İSG Kurulu'nda İGU/Hekim otomatik + kurul görev tanımı + kaşe → PDF/gerçek isgpratik Word şablonu — üye başına ayrı belge/sayfa; Eğitim Katılım'a aktarım) |
| Formlar & Belgeler | Eğitim Katılım | `egitim-katilim` | **hazır** (Genel/Sağlık/Teknik/İşyerine Özgü + 13 özel başlık — her madde işaretlenip dakikası değiştirilebilir + katılımcı listesi → PDF; Atama Yazıları'ndan katılımcı aktarımı kabul eder) |
| Formlar & Belgeler | İşbaşı Eğt. Tutanağı | `isbasi-egitim` | **hazır** (konu kategorileri + eğitim yöntemi + TC gizleme → PDF) |
| Formlar & Belgeler | Tatbikat Tutanağı | `tatbikat` | **hazır** (10 senaryo + ekip + değerlendirme + DÖF önerisi → PDF) |
| Formlar & Belgeler | Tespit Öneri Defteri | `tespit-oneri-defteri` | **hazır** (hazır katalog + serbest/AI destekli madde → PDF) |
| Formlar & Belgeler | Sertifika Oluştur | `sertifika` | **hazır** (4 tip: İSG/Yüksekte/Kapalı Alan/Yangın; düzenlenebilir Eğitim Konuları; katılımcı başına sayfa + otomatik kaşe → resmi eğitim belgesi PDF'i (4 gerçek çerçeve seçeneği, tek sayfa) veya İSG tipinde "Yıldız Grup" gerçek Excel şablonu) |
| Formlar & Belgeler | Eğitim Soruları `[AI]` | `egitim-sorulari` | **hazır** (Gemini ile 10 soruluk sınav + manuel soru → PDF) |
| Formlar & Belgeler | KKD Formu | `kkd-formu` | **hazır** (6 kategori TS EN katalog, çoklu çalışan × çoklu KKD → PDF) |
| Formlar & Belgeler | İş İzin Formu | `is-izin-formu` | **hazır** (4 izin türü, dinamik güvenlik önlemleri → PDF) |
| Formlar & Belgeler | Ceza ve Tebliğ Tutanağı | `ceza-teblig` | **hazır** (2 sekme: Çalışana Ceza Tutanağı + İşverene İPC Tebliği → PDF) |
| Formlar & Belgeler | İş Kazası Raporu | `is-kazasi-raporu` | **hazır** (5N1K + kök neden analizi, SGK bildirim takibi → PDF) |
| Formlar & Belgeler | Talimat Oluştur `[AI]` | `talimat` | **hazır** (30 hazır şablon + kendi arşiv Excel yüklemesi + Gemini ile madde üretimi → PDF) |
| Formlar & Belgeler | Muayene Formu (EK-2) | `muayene-formu` | **hazır** (meslek öyküsü/özgeçmiş + sistemik muayene + tetkikler + sonuç/kanaat → PDF) |
| Formlar & Belgeler | Ücretsiz E-Reçetem | `e-recetem` | **hazır** (bilgi sayfası — e-Reçete süreci, uygunluk, SSS) |
| **Planlama & Arşiv** | Yıllık Planlar | `yillik-planlar` | **hazır** (3 sekme: Çalışma Planı + Eğitim Planı + Değerlendirme Raporu → PDF) |
| Planlama & Arşiv | Ziyaret Programı | `ziyaret-programi` | **hazır** (basitleştirilmiş: firma+yıl → 12 aylık satır, AI amaç önerisi → PDF) |
| Planlama & Arşiv | Araçlar | `araclar` | **hazır** (Kaza Sıklık/Ağırlık Hızı + Gürültü Lex,8h hesaplayıcıları + NACE Kod → Tehlike Sınıfı Sorgula + MYK Zorunluluk Sorgula) |
| — | **Panel (Dashboard)** | `/admin` | iskele (`PortföyÖzetiWidget`) |
| Yönetim | Firmalar | `firmalar` | **hazır** (Çalışanlar RelationManager dâhil) |
| Yönetim | Çalışanlar | `calisanlar` | **hazır** |
| Yönetim | İSG Profesyonelleri | `isg-profesyonelleri` | **hazır** (İGU/İşyeri Hekimi/DSP + kaşe/imza; "Firmalara Ata" ile toplu atama/kaldırma — Faz 5) |

## Ekran notları (görülen referanslar)

### Dashboard / Panel (1-3.jpg)
- Üst selamlama kartı: "Günaydın {ad} | {rol}", "Firma Ziyareti Bildir" + "AI Saha Analiz"
  hızlı butonları, sağda **Abonelik** kartı (plan adı, başlangıç/bitiş, kalan gün çubuğu).
- Global arama (Ctrl+K) — sayfa/özellik/komut.
- Sayaç kartları: **İSG Uyum Skoru /100**, Firma, Çalışanlar, Önemli Risk (skor > 140),
  7G Biten Evrak, Açık Görev, Eğitim Kapsamı, Eğitimsiz çalışan %.
- **İSG Performans Radarı** (5 eksen: Çalışan Kapsamı, Eğitim Durumu, Risk Yönetimi …
  "Senin skorun" vs "Hedef 80").
- **Aktivite Trendi** (7G/30G/90G — Raporlar/Riskler/Eğitimler/Evraklar çizgi).
- **Eğitim Sağlığı** (kapsam %, 7/14/30 gün biten kayıt, eğitimli/eğitimsiz).
- **Tehlike Sınıfı Dağılımı** (donut: Az/Tehlikeli/Çok Tehlikeli).
- **Ziyaret Programım** (aktif program / oluştur).
- Sağ kolon: **Yapılacaklar** listesi + duyuru/changelog akışı.
- Alt: özellik tanıtım kartları (Firma Arşiv, Firma Yetki, Risk Sihirbazı, İSG Robot AI …).

### Kontrol Merkezi (4 + 135-136.jpg) — **kuruldu, bkz. Faz 3d**
- Başlık "İSG Komuta Merkezi" + "Canlı İSG Takibi" rozeti.
- Sekmeler: **Günlük Akış** / **Firma Asistanı** / **Çalışan Asistanı**.
- Firma Asistanı: İSG Portföy Özeti + 12 yasal kriterin portföy tamamlanma oranı +
  yenileme süreleri + uzman tavsiyeleri (135.jpg).
- Çalışan Asistanı: firma seç → çalışan eksikleri; seçilmeden boş durum (136.jpg).
- Günlük Akış boş durum: "Harika! Tüm Görevler Güncel".

### Profilim (5, 137-147.jpg) — **TAMAMLANDI, bkz. Faz 3e + Faz 5**
- Künye: ad, e-posta, ünvan rozeti, Aktif rozeti, "Hesap Ayarları" (Filament profil).
- Sayaç: Firmalarım / Çalışanlarım / Risk Değerlendirmesi / Risk Şablonları / Önemli Risk /
  Raporlarım.
- Sekmeler (isgpratik'in 11 sekmesinin TAMAMI kuruldu, "Diğer" kaldırıldı):
  **Genel Bakış / Firmalar / Çalışanlar / Eğitimler / OSGB Takip / Firma Takip /
  Risklerim / Pazarlama / Arşiv / Raporlar / Firma Ziyaretleri**.
- Genel Bakış: Uyumluluk Skoru (conic-gradient gauge), Tehlike Sınıfı Dağılımı (bar),
  İlk Yardım bilgi kartı. Performans/trend/heatmap ileride.
- Eğitimler (139-140.jpg, Faz 5): çalışan × kullanıcının kendi `EgitimTuru` listesi
  (varsayılan tek madde — Temel İSG Eğitimi; "Konu Ekle"/× ile büyür/küçülür) + Excel
  toplu yükle/şablon indir (`EgitimKayitExcelIceAktarici`).
- OSGB Takip (141.jpg, Faz 5 — eski "Evrak Takip"): firma × kullanıcının `EvrakTuru`
  listesi (7 varsayılan madde + ekle/kaldır) + Excel toplu yükle; ortak anahtarlarda
  (`yillik_calisma_plani` vb.) tarih girilince Firma Takip'teki ilgili kriter de ✓
  işaretlenir (`PortfoyKarne::firmaKriterKarsilarMi`). Firma Takip'e de aynı "Evrak
  Türü Ekle" eklendi — iki sekme tek `EvrakTuru` state'ini paylaşır.
- Firma Takip: firma × 12+9 yasal/manuel kriter matrisi + kullanıcının özel evrak
  türleri (isgpratik 141-142 — `PortfoyKarne::firmaKriterMatrisi`/`firmaTakipKriterleri`).
- Pazarlama (143.jpg, Faz 5): `AdayFirma` — aday/teklif/kazanıldı/kaybedildi, "Kazanıldı"
  için `CreateFirma`'yı ön-dolu açan dönüştürme kısayolu.
- Arşiv (144.jpg, Faz 5): firma başına düz dosya listesi (`ArsivDosya`, klasör yok).
- Raporlar (146.jpg, Faz 5): mehse'deki 22 belge üretici modülünün TAMAMI tek listede
  (`App\Support\RaporKayitlari` + `config isg.raporlar.kaynaklar` — her satır bir
  model + üretici eşleşmesi, dosya arşivlemeden `Uretici::pdf($kayit)` ile anlık üretim).
- Firma Ziyaretleri (147.jpg, Faz 5): yeni tablo yok — `ZiyaretProgrami`'nin tarihli ay
  satırlarından türetilen salt-okunur takvim (`App\Support\ZiyaretTakvimi`).

### İSG-KATİP Robot (7-9.jpg)
- Chrome eklentisi ile İSG-KATİP portal otomasyonu. "Teknik Gereksinimler", "Kurulum
  Rehberi", "Kontrol Paneli" (bot kartları: Çoklu Atama, Toplu Sözleşme İndir/Onayla/
  Sonlandır, Tıbbi Tetkik / Gezici Sağlık Aracı ataması, Süre Analizi …).
- **Uygulanabilirlik:** gerçek eklenti + resmî portal gerektiği için yalnız **bilgi
  sayfası** + günlük hak sayacı stub'ı olarak kuruldu (bkz. Faz 3f).

### Risk Değerlendirme sihirbazı (10-18.jpg) — **kuruldu, bkz. Faz 3a**
6 adım: **Firma Bilgileri → Ekleme Yöntemi → Risk Ekleme → Tercihler → Risklerim →
Önizleme & PDF**.
- **1. Firma Bilgileri:** firma seç, künye kartı, Rapor + Geçerlilik Tarihi (tehlike
  sınıfına göre otomatik: az 6 / tehlikeli 4 / çok tehlikeli 2 yıl). (Ekip + logo Faz 3b.)
- **2. Ekleme Yöntemi:** 5 seçenek — (a) **Yapay Zeka** (ÖNERİLEN), (b) **Manuel Seçim**,
  (c) **Şablonlar & Paylaşılanlar** (sektör bazlı), (d) **Kayıtlı Risklerim**,
  (e) **Excel'den Yükle**. → a + b + c kuruldu; d/e "yakında".

### Yapay Zeka (kural tabanlı) risk üretimi (103-115.jpg) — **kuruldu**
Gerçek LLM yok; `App\Support\RiskUretici` + `config/isg.php → risk_ai`. Akış:
- **1. Aşama — Ana Sektör** (105): 13 sektör kartı (Fabrika/Üretim, İnşaat/Yapı,
  Sağlık/Hastane, Ofis/Hizmet/Finans, Gıda/Yemekhane, Ulaşım/Lojistik, Tarım/Hayvancılık,
  Maden/Taşocağı, Servis/Bakım, Depo/Lojistik, Eğitim/Okul, Eğlence/Otel, Diğer).
- **2. Aşama — Alt Kategori** (106): sektöre bağlı chip'ler, çoklu, boş bırakılabilir.
- **Sohbet — Sorular** (107-115): ~11 soru tek tek; her soruda mevzuat ipucu (italik),
  radyo veya çoklu; "Cevabı Gönder / Atla / ← Önceki Soru". Başlıkta "Soru X/N" +
  "N aday risk". Sektöre uymayan sorular atlanır (`insaat_yapi_turu` yalnız inşaat).
  Sorular: çalışan sayısı, vardiya, yangın/ilkyardım eğitimi, tahliye tatbikatı, yangın
  altyapısı, elektrik (KAKR + topraklama), mekanik havalandırma, kaza/ramak kala geçmişi,
  psikososyal risk.
- **Sonuç:** aday riskler = cevaptaki eksikliğin tetiklediği mevzuat riski (öneri O/Ş ile,
  seçili gelir) + sektöre göre kütüphane baz riskleri (`genel_isyeri` + eşleşen kategori).
  Çoklu seçim, "Tümünü Seç/Kaldır" → "Seçili N riski ekle" → Adım 5.
- Günlük AI hak sayacı isgpratik'te var; kural tabanlıda `config isg.risk_ai.gunluk_hak`
  yalnız gösterim, engellemez.

## Veri modeli — ilk taslak

| Model / tablo | Alanlar (özet) |
|---|---|
| `User` / `users` | + `rol` (varsayılan `uzman`), `unvan` (C/B/A sınıfı İGU), `katip_no`, `kase_*`, `telefon`, `abonelik_*` |
| `Firma` / `firmalar` | `user_id`, `unvan`, `sgk_sicil_no`, `vergi_no`, `nace_kodu`, `nace_aciklama`, `tehlike_sinifi`, `adres`, `il`, `ilce`, `calisan_sayisi`, `sozlesme_baslangic/bitis`, `logo`, `aktif` |
| `Calisan` / `calisanlar` | `firma_id`, `ad_soyad`, `tc`, `gorev`, `departman`, `ise_giris`, `dogum_tarihi`, kan grubu, iletişim, `agir_tehlikeli_isde_calisir` |
| `RiskDegerlendirmesi` / `risk_degerlendirmeleri` | `firma_id`, `yontem`, `rapor_tarihi`, `gecerlilik_tarihi`, `belge_no`, `revizyon_no`, künye snapshot, `ekip` (json), `durum` |
| `RiskMaddesi` / `risk_maddeleri` | `risk_degerlendirmesi_id`, `bolum`, `faaliyet`, `tehlike`, `risk`, mevcut O/Ş → skor/düzey, önlemler, sorumlu, termin, rezidüel O/Ş |
| `TehlikeKategorisi` + `Tehlike` | risk kütüphanesi (kategori → tehlike madde) |
| `RiskSablonu` / `risk_sablonlari` | `user_id`, `ad`, `sektor` (config risk_ai anahtarı) / `sektor_adi`, `yontem`, `maddeler` (json), `paylasildi`, `kullanim_sayisi` — sektörel toplu şablon; `scopeGorunur`, `maddeleriKopyala`, `olustur` |
| `AcilDurumPlani` / `acil_durum_planlari` | `firma_id` (unique), `dokuman_no` (AD-YYYY-NN), `rapor_tarihi`, `gecerlilik_tarihi` (tehlike sınıfına göre 2/4/6 yıl), `kapak_cercevesi`, `konular` (json — seçili acil durum konu anahtarları), `ekipler` (json — söndürme/kurtarma/koruma/ilk_yardım isim listeleri), kaşe/logo görselleri; `firmaIcin`, `konuAdlari`, `ekipListesi` |
| `RiskSablonu` | kayıtlı / paylaşılan şablonlar, puan |
| (diğer modüller kendi modelleriyle eklenecek) |

## Faz planı

- **Faz 0 — Kurulum (bu commit):** Laravel 12 + Filament v5, koyu tema, MySQL `mehse`,
  admin kullanıcı, nav grupları, kök yönlendirme, `MIMARI.md` + `CLAUDE.md`.
- **Faz 1 — Çekirdek ✅ (commit 270a685):** `config/isg.php`; `User` İSG alanları +
  `FilamentUser`; `Firma` modeli + `FirmaResource` (bölümlü form, tehlike sınıfı rozetli
  tablo, nav rozeti, portföy filtresi); `Calisan` modeli + `CalisanResource` + Firma
  altında RelationManager; `PortfoyOzetiWidget`. `FirmaCalisanTest` (6 test).
  - **Excel'den toplu firma yükleme ✅:** `App\Support\FirmaExcelIceAktarici`
    (phpoffice/phpspreadsheet) — ilk satır başlık, sütun adları Türkçe karakter/boşluk
    farkı gözetmeksizin eşleştirilir; yalnız "Unvan" zorunlu, "Tehlike Sınıfı" hem
    anahtar hem Türkçe etiketle eşleşir (bilinmeyense `az_tehlikeli`), tarihler Excel
    seri sayısı/metin olarak çözülür, boş satırlar sessizce atlanır. `ListFirmas`
    header'ında "Şablon İndir" (örnek .xlsx) + "Excel'den Yükle" (FileUpload modal,
    sonuç/hata özeti bildirimle). `FirmaExcelIceAktariciTest` (7 test).
  - **Excel'den toplu çalışan yükleme ✅:** `App\Support\CalisanExcelIceAktarici` —
    firma bağlamdan gelir (`CalisanlarRelationManager` üzerinden), Excel'de firma
    sütunu yok. Yalnız "Ad Soyad" zorunlu; T.C. Kimlik No verilmişse aynı firmada
    aynı TC ile tekrar yüklenirse günceller (mükerrer oluşmaz). Firma'nın Düzenle
    sayfasında "Çalışanlar" sekmesinde "Şablon İndir" + "Excel'den Toplu Yükle".
    **Not:** Filament v5'te RelationManager tablo aksiyonları testte `callAction()`
    değil `callTableAction()` ile çağrılır. `CalisanExcelIceAktariciTest` (6 test).
- **Faz 2 — Risk Değerlendirme çekirdeği ✅ (commit sonrası):**
  - `config/isg.php` → `risk_yontemleri`, `risk_matris_5x5` / `risk_fine_kinney` (ölçek
    metinleri + puan → düzey bantları; **Fine-Kinney ondalık anahtarlar string**),
    `risk_madde_durumlari`.
  - `App\Support\RiskSkorlama::hesapla($yontem, O, Ş, ?F)` — 5×5 (O×Ş) ve Fine-Kinney
    (O×F×Ş); `bant()`, `olcek()`.
  - `TehlikeKategorisi` + `Tehlike` (Risk Kütüphanesi) + `TehlikeKutuphanesiSeeder`
    (3 kategori / 13 tehlike başlangıç seti). `TehlikeResource` (kategoriye göre gruplu tablo).
    **Excel'den toplu yükleme ✅:** `App\Support\TehlikeExcelIceAktarici` —
    "Kategori" sütunundaki değer yoksa OTOMATİK OLUŞTURULUR (kullanıcı bu yolla
    kendi sektör/iş kategorilerini — Kazı Çalışmaları, Kalıp İşleri, Cam Üretimi,
    Toz Boyama, Metal, Havalandırma vb. — ekleyebiliyor); aynı kategoride aynı
    tehlike metni tekrar yüklenirse güncellenir. `ListTehlikes` header'ında
    "Şablon İndir" + "Excel'den Toplu Yükle". `TehlikeExcelIceAktariciTest` (6 test).
    Referans: kullanıcının `Desktop\isgpratik\RİSK ANALİZİ\finkeney prosödür..xlsx`
    dosyası (Kazı/Kalıp/İskele/Zemin gibi inşaat alt-faaliyetlerine göre gruplu
    gerçek bir Fine-Kinney risk tablosu — BÖLÜM sütunu kategori karşılığı).
    **Sonraki adım (kullanıcı verisi bekleniyor):** kullanıcı kendi sektörel
    risk analizlerini bu araçla yükledikçe, Risk Sihirbazı'nın Manuel/AI
    yöntemlerinde İnşaat gibi sektörlerde alt-faaliyet (Kazı/Kalıp/İskele/Çatı/
    Zemin İyileştirme) çoklu-seçim arayüzü kurulacak (`RiskUretici::
    KUTUPHANE_ESLESME` şu an sektör→tek kategori eşliyor, alt-kategoriye göre
    çoklu kategoriye genişleyecek).
  - `RiskDegerlendirmesi` (firma künye snapshot + `belge_no` `RD-…` + geçerlilik tehlike
    sınıfına göre otomatik) + `RiskMaddesi` (`saving` → puan/düzey + rezidüel puan/düzey
    yönteme göre). `RiskDegerlendirmesiResource` (Firma & Yöntem + Ekip formu) +
    **MaddelerRelationManager** (yönteme göre O/(F)/Ş select'leri, "Kütüphaneden aktar",
    puan rozetli tablo, sıralanabilir). Create → edit'e yönlendirir.
  - `RiskDegerlendirmeTest` (9 test toplam).
- **Faz 3a — Risk Değerlendirme Sihirbazı ✅ (isgpratik 10-18 + AI: 103-115):**
  - `App\Filament\Pages\RiskSihirbazi` (slug `risk-degerlendirme`, nav "Risk Yönetimi"
    sort 1). 6 adım: Firma Bilgileri → Ekleme Yöntemi → Risk Ekleme → Tercihler →
    Risklerim → Önizleme & Kaydet. Livewire state + elle yazılmış Blade (stepper +
    inline stil; özel Filament teması yok, `x-filament::*` bileşenleri).
  - **Adım 1:** firma seç (yalnız kullanıcının firmaları) + künye kartı + rapor/geçerlilik
    tarihi (tehlike sınıfına göre otomatik, `Firma::riskGecerlilikYili()`) + yöntem
    (5×5 / Fine-Kinney).
  - **Adım 2:** 5 yöntem kartı (`RiskSihirbazi::YONTEMLER`, madde listeli, "ÖNERİLEN"
    rozeti); **Manuel Seçim + Yapay Zeka** `hazir`, Şablon/Kayıtlı/Excel "yakında".
    Adıma göre buton etiketi (`ILERI_ETIKET`: "Yöntem Seç" / "Risk Ekle" …).
  - **Adım 3 (Manuel):** `App\Support\RiskKutuphanesi` — `TehlikeKategorisi` accordion,
    tehlike tek tek veya "Tümünü ekle"; mükerrer engellenir. "Elle boş madde ekle".
  - **Adım 3 (Yapay Zeka — kural tabanlı, isgpratik 103-115.jpg):** LLM YOK.
    `config/isg.php → risk_ai` (13 sektör + alt kategoriler + ~11 mevzuat referanslı
    soru; her seçenek eksiklikse `riskler[]` tetikler) + `App\Support\RiskUretici`.
    Alt akış (`aiAsama`): başlangıç → **1. Aşama sektör** → **2. Aşama alt kategori**
    → **sohbet** (sorular tek tek; radyo/çoklu; ipucu = mevzuat; "Cevabı Gönder / Atla
    / Önceki Soru"; sektöre uymayan soru atlanır) → **sonuç** (aday riskler, çoklu seç,
    "Tümünü Seç/Kaldır"). Adaylar = cevap-tetikli riskler (öneri O/Ş ile, seçili gelir)
    + sektöre göre kütüphane baz riskleri (`genel_isyeri` + eşleşen kategori).
    "Seçili N riski ekle" → `secilenler` → Adım 4.
  - **Adım 3 (Şablonlar):** `RiskSablonu` — sektöre göre gruplu liste; "Bu şablonu
    uygula" maddeleri `secilenler`'e toplu ekler (mükerrer atlanır), `kullanim_sayisi++`,
    Adım 4'e geçer. `RiskSablonuResource` (nav "Sektör Şablonları", sihirbazdan oluşur;
    List sektöre göre gruplu + Edit: ad/sektör/yöntem/**paylaş**; silme yalnız sahibi).
  - **Adım 3 (AI kısayolu):** sektör seçilince o sektörün kayıtlı şablonu varsa
    "sohbete girmeden direkt kullan" butonları çıkar.
  - **Adım 4:** Tercihler — puanlama yöntemi (5×5 / Fine-Kinney), etkilenen
    taşeron/ziyaretçi varsayılanı, varsayılan termin.
  - **Adım 5 (Risklerim):** seçilen maddeler kart listesi — bölüm/faaliyet/tehlike
    düzenlenebilir + O/(F)/Ş select → canlı puan/düzey (renk bandı).
  - **Adım 6:** özet (künye rozetleri + düzey dağılımı) + **Kaydet** →
    `RiskDegerlendirmesi` + `RiskMaddesi` kayıtları → kayıtlı değerlendirme edit'ine
    yönlendirir. **"Sektör şablonu olarak kaydet"** (ad + sektör → `RiskSablonu::olustur`).
    PDF butonu artık kayıtlı değerlendirme edit sayfasında gerçek çıktı üretiyor
    (`RiskDegerlendirmesiUretici`, Faz 3b).
  - `RiskDegerlendirmesiResource` artık **"Kayıtlı Değerlendirmeler"** (slug
    `risk-degerlendirmelerim`, sort 2, klasör ikonu); List header'ında "Yeni (Sihirbaz)"
    + "Boş kayıt". `barryvdh/laravel-dompdf` bağımlılığı eklendi (PDF için, henüz kullanılmıyor).
  - `RiskSihirbaziTest` (17 test).
- **Faz 3c — Sol menü iskelesi ✅ (isgpratik 1-3.jpg):** `App\Filament\Pages\HazirlanryorPage`
  (abstract; ortak `filament.pages.hazirlaniyor` view + `[AI]` rozeti + referans notu) →
  24 stub sayfa (Kontrol Merkezi, İSG-KATİP Robot, Acil Durum Planı, DÖF, AI Saha Analizi,
  Saha Denetimi, Kurul Toplantısı, Atama Yazıları, Eğitim Katılım, İşbaşı Eğitim, Tatbikat,
  Tespit Öneri Defteri, Sertifika, Eğitim Soruları, KKD, İş İzni, Ceza/Tebliğ, İş Kazası,
  Talimat, Muayene EK-2, E-Reçetem, Yıllık Planlar, Ziyaret Programı, Araçlar).
  4 nav grubu doğru sırada. `NavigasyonTest` (31 test — tüm menü sayfaları açılıyor).
- **Faz 3d — Kontrol Merkezi ✅ (isgpratik 135-136.jpg):** `App\Filament\Pages\KontrolMerkezi`
  (stub yerine geçti). 3 pill sekme:
  - **Firma Asistanı:** `App\Support\PortfoyKarne` — İSG Portföy Özeti (firma/çalışan
    sayısı, evrak eksiği, tam uyumlu, %uyum) + **12 yasal kriterin** portföy tamamlanma
    oranı (progress bar; `config isg.kontrol_merkezi.kriterler`). Kriterin `hazir=false`
    olanı 0/N gösterir, ilgili modül kurulunca `firmaKriterKarsilarMi()`'de gerçek
    kontrole geçer (şu an yalnız `risk_degerlendirmesi` hesaplanıyor). İSG Kurulu kriteri
    `kosul=elli_calisan` ile yalnız 50+ firmayı kapsar. + Yenileme süreleri
    (`risk_gecerlilik_yili`) + Uzman tavsiyeleri kartları.
  - **Çalışan Asistanı:** firma seç → çalışan karnesi (genç çalışan &lt;18, ağır/tehlikeli
    iş sayısı); muayene/eğitim/MYK ilgili modüller kurulunca eklenecek.
  - **Günlük Akış:** geçerliliği geçmiş / 60 gün içinde dolan risk değerlendirmeleri;
    boşsa "Harika! Tüm Görevler Güncel".
  `KontrolMerkeziTest` (6 test).
- **Faz 3e — Profilim ✅ (isgpratik 5-6, 137-147.jpg):** `App\Filament\Pages\Profilim`
  (slug `profilim`, Yönetim sort 1). Künye + 6 sayaç (`PortfoyKarne::profilOzeti`) + 6
  pill sekme. Header aksiyonları: **"Ünvan & İletişim"** (unvan/telefon/sertifika modalı →
  `User`) + **"Kaşe Bilgisi"** (kase_gorseli / imza_gorseli FileUpload → belge çıktıları).
  Künyede kaşe önizleme + "Kaşe yüklü/eksik" rozeti.
  - **Genel Bakış (5-6.jpg):** 4 mini stat (eğitimsiz çalışan —, önemli risk, çalışansız
    firma, evrak eksiği) + **Dönemsel Aktivite Trendi** (son 30 gün inline SVG sparkline) +
    uyumluluk skoru gauge + tehlike sınıfı dağılımı + **Çalışan Dağılımı** (firma → çalışan)
    + **Son 90 Gün Aktivite heatmap** (`PortfoyKarne::aktiviteGunluk`).
  - **Firmalar / Çalışanlar:** kompakt tablo + ilgili resource'a link. Çalışan boşsa
    "Henüz çalışan eklenmemiş".
  - **Firma Takip:** firma × 12 kriter matrisi (`firmaKriterMatrisi`; "—" = modül yok).
  - **Risklerim:** Sektör Şablonları / Risk Kütüphanesi / Risk Değerlendirmeleri sayaç
    kartları + linkler.
  - **Diğer:** isgpratik'te olup henüz kurulmayan 6 sekme (Eğitimler, Evrak Takip,
    Pazarlama, Arşiv, Raporlar, Firma Ziyaretleri) + referans notları.
  Hesap düzenleme hâlâ Filament'ın kendi profil sayfasında (`->profile()`, kullanıcı menüsü).
  `ProfilimTest` (7 test).
- **Faz 3f — İSG-KATİP Robot ✅ (isgpratik 7-9.jpg):** `App\Filament\Pages\IsgKatipRobot`
  (stub yerine geçti). **Bilgi sayfası** — gerçek Chrome eklentisi / portal erişimi YOK.
  `config isg.isg_katip`: 3 gereksinim + 4 kurulum adımı + **11 bot kataloğu** (Çoklu
  Atama, Toplu Sözleşme İndir/Onayla/Sonlandır, Tıbbi Tetkik/Gezici Sağlık Aracı ataması,
  Süre Analizi …) + günlük hak sayacı + "Neden/Güvende miyim" metinleri. "Kurmak için
  tıklayın" / "Eklentiyi İndir" / "Bağlantıyı Kontrol Et" hepsi stub bildirimi döndürür.
  `IsgKatipRobotTest` (3 test).
- **Faz 3g — Acil Durum Eylem Planı ✅ (isgpratik 19-21, 146-154.jpg):**
  `App\Filament\Pages\AcilDurumPlani` (stub yerine geçti) + `App\Models\AcilDurumPlani`
  (firma başına 1) + `App\Support\AcilDurumPlaniUretici` (dompdf) +
  `config isg.acil_durum` (21 konu, 7 kapak çerçevesi, 4 ekip, 7 afiş talimatı).
  - Firma seç → plan `firmaIcin` ile oluşur/yüklenir; doküman no + rapor tarihi +
    geçerlilik (tehlike sınıfına göre 2/4/6 yıl) + **acil durum konu sayfaları** seçimi
    + kapak çerçevesi + **destek ekipleri** (söndürme/kurtarma/koruma/
    ilk yardım — virgülle isim) → "Kaydet".
  - **Konu ön-seçimi (`App\Support\AcilDurumKonuSecici`):** sabit varsayılan yerine
    firmanın NACE kodu + tehlike sınıfına göre hesaplanır (`config isg.acil_durum.konular[].kosul`
    — `null` = her firmada seçili, `['nace'=>[…], 'tehlike'=>[…]]` = NACE ön eki VEYA
    tehlike sınıfı eşleşirse seçili). Kullanıcı elle ekleyip çıkarabilir.
  - Header: **Plan PDF** (dompdf, kapak + künye + ekip tablosu + seçili konu sayfaları +
    onay), **Word** (stub). **Acil Durum Afişleri:** 7 tip (Yangın/Deprem/İş Kazası/
    Elektrik/Kimyasal/Sel/Sabotaj) × A4/A3 → numaralı talimat afişi PDF. Bir afiş türü için
    hazır PDF tanımlıysa (`config .dosya`, `resources/belge/acil-durum-afisleri/`) dompdf
    yerine doğrudan o dosya indirilir. **7'si de gerçek dosya** (kullanıcının referans
    klasöründen; yangin/deprem/elektrik/kimyasal/sabotaj/sel/is_kazasi) —
    `setasign/fpdi`+`fpdf` ile her birinin alt %6'lık bandı beyaz dikdörtgenle
    maskelendi (kaynak dosyalardan birinde örnek müşteri firma adı gömülüydü;
    yalnız görsel maskeleme, PDF metin katmanında orijinal metin hâlâ var).
  - **Kapak/künye içeriği yönetmelik gereği zorunlu alanlarla tamamlandı ✅**
    (kullanıcının gerçek referans planlarıyla — ALTIN YAKUT + NİL UNLU örnekleri —
    karşılaştırılarak): kapakta NACE kodu + Hazırlayan (uzman adı/unvanı) + Rev.
    Tarihi/No; künye sayfasında "İşyeri İçin Belirlenen Acil Durumlar" numaralı
    listesi + Toplanma Yeri + "İşyerini Dışarıdan Etkileyebilecek İşyerleri"
    (serbest metin) + "Acil Durumlarda İrtibat Kurulacak Kuruluşlar ve
    Telefonları" (`config isg.acil_durum.irtibat_telefonlari`, sabit ulusal
    hatlar); yeni **Tahliye Planı** sayfası (`tahliye_plani_gorseli` — önceden
    var ama bağlanmamış kolon; header'da "Tahliye Planı Görseli" modal
    aksiyonu); ONAY sayfasında uzmanın adı/unvanı artık basılı. Konu listesi
    (21 acil durum) NİL UNLU'nun EK-1 listesiyle (19 konu) karşılaştırıldı,
    19/19 zaten birebir eşleşiyor. **Kapsam dışı bırakılan:** ekip tablosunun
    ad/soyad/sorumluluk alanı/telefon/imza düzeyinde detaylandırılması
    (referans belgelerde var, mevcut "isim listesi" yaklaşımından daha büyük
    bir veri modeli değişikliği gerektiriyor — ayrı iş).
  - `barryvdh/laravel-dompdf` ilk kez kullanıldı; PDF blade'leri `resources/views/pdf/`.
  - **Firmalar → hızlı erişim + orijinal şablonu koruyan ikinci çıktı (Word) ✅:**
    `FirmasTable` satır aksiyonu "Acil Durum Planı" → `?firma=` ile sayfaya gidip
    firmayı önceden seçili getirir (`mount()`). `App\Support\AcilDurumWordUretici`
    — kullanıcının gerçek referans belgesini (`resources/belge/acil-durum-plani-
    sablonu.docx`) birebir şablon olarak kullanır; yalnız 8 sabit alan (unvan,
    adres, SGK no, 2 tarih, tehlike sınıfı, hazırlayan ad/unvan) + "ACİL DURUM N:"
    11 satırlık liste + "ÇALIŞAN SAYISI:" satırı firmaya göre değişir, gerisi
    (hukuki metin, biçim, SmartArt diyagramları) aynen kalır — Word'ün aynı
    değeri birden fazla `<w:r>` koşusuna bölmesi ihtimaline karşı paragraf içi
    `<w:t>` düğümleri birleştirilip aralık bulunur, biçim bozulmadan geri
    dağıtılır. **Canlı testte 2 hata bulunup düzeltildi:** `mb_strtoupper`
    Türkçe nokta kuralını bilmiyor ("tekstil"→"TEKSTIL" değil "TEKSTİL" olmalı
    — `turkceBuyuk()` helper'ı eklendi); `response()->download()
    ->deleteFileAfterSend(true)` Windows'ta süreç takılmasına yol açıyordu —
    `AcilDurumPlaniUretici`'nin zaten kullandığı `streamDownload`+manuel
    `unlink()` desenine çevrildi.
  `AcilDurumPlaniTest` (13 test).
  - **Gemini (gerçek LLM) entegrasyonu ✅:** `App\Support\GeminiRiskDanismani` —
    Risk Sihirbazı AI adımında sektör + alt kategori + sohbet cevaplarıyla Gemini
    `generateContent` REST API'sine `responseSchema` (structured JSON output) ile
    istek atar; `RiskUretici::uret()` çıktısını kural tabanlı + kütüphane
    adaylarına `kaynak=llm` olarak ekler (aynı şema, mükerrer tehlike metni elenir).
    `config('services.gemini.key')` tanımlı değilse veya istek başarısız
    olursa/istisna atarsa sessizce boş dizi döner — sihirbaz kural tabanlı
    sonuçlarla çalışmaya devam eder. `.env`: `GEMINI_API_KEY` (gerçek anahtar
    girildi, `.env` git'e girmez) / `GEMINI_MODEL` (varsayılan `gemini-3.6-flash` —
    `gemini-2.5-flash` canlı testte "no longer available to new users" hatası verdi,
    Google'ın önerdiği modele geçildi). Timeout 45s (`thinking` gecikmesi nedeniyle
    canlı testte 14-20s sürdü, eski 20s limiti yetmiyordu). **Canlı API testi
    yapıldı — çalışıyor** (sektöre özel, mevzuat referanslı 4 öneri üretti).
    **Bilinçli tercih:** LLM önerileri, kütüphane önerileri gibi varsayılan olarak
    SEÇİLİ GELMİYOR — mevzuat riski taşıyan bir belgede LLM çıktısını sessizce
    onaylatmamak için uzman gözden geçirip seçmeli.
    `GeminiRiskDanismaniTest` (5 test, `Http::fake`). **`phpunit.xml`de
    `GEMINI_API_KEY` boş zorlanıyor** — yoksa gerçek anahtar varken bazı testler
    sessizce canlı API'ye istek atıp 20-30s sürüyordu.
  - **Panel çalışan sayısı düzeltmesi:** `PortfoyKarne::ozet()`/`profilOzeti()`
    artık `Calisan` (isim) kaydı sayısı yerine firmaların bildirdiği
    `calisan_sayisi` toplamını gösteriyor — kullanıcı çoğu zaman firmaya
    çalışan sayısını giriyor ama her çalışanı tek tek kayıt olarak eklemiyor.
    `calisanDagilimi()` ve "çalışansız firma" sayacı kasıtlı değiştirilmedi
    (onlar zaten Çalışan kaydı eksikliğini göstermeyi amaçlıyor).
  - **Faz 3b — Risk PDF çıktısı ✅** (kapak → künye → metodoloji → risk tablosu →
    ekip → onay, `RiskDegerlendirmesiUretici`).
  - **"Risk Değerlendirmenizden Yükleyin" (excel) yöntemi ✅:**
    `App\Support\RiskDegerlendirmesiExcelOkuyucu` — kullanıcının KENDİ Excel
    risk analizini hiçbir sabit şablona uydurmadan okur. Önce ilk ~60 satırda
    en çok tanınan başlığı içeren satır "başlık satırı" bulunur (gerçek
    dosyalarda üstte logo/lejant/ölçek tablosu olduğundan başlık nadiren 1.
    satırdadır), sonra her sütun eş anlamlı kelime kümeleriyle alana atanır.
    Olasılık/Frekans/Şiddet yalnızca hem ad hem ALTINDAKİ VERİ SAYISALSA kabul
    edilir. Kullanıcının `isgpratik\RİSK ANALİZİ\finkeney prosödür..xlsx`
    dosyasının "kent" sayfasıyla (292 satır, 16. satırda başlık, gerçek O/F/Ş
    puanları) doğrulandı. Sihirbaz Adım 3'te dosya yükle → tara → aday listesi
    (AI akışındaki gibi toggle) → seçilenlere ekle. Adaylar varsayılan SEÇİLİ
    gelir (kullanıcının kendi onayladığı veri olduğundan).
  - **Risk Kütüphanesi (Tehlike) Excel'den toplu yükleme + çakışma tespiti ✅:**
    `App\Support\TehlikeExcelIceAktarici` — "Kategori" sütunu yoksa otomatik
    oluşturulur (Kazı Çalışmaları, Cam Üretimi vb. yeni kategoriler bu yolla
    eklenebilir). `ListTehlikes` header'ında "Şablon İndir" + "Excel'den
    Toplu Yükle". **Not:** Bu, `Tehlike` (skorsuz referans kütüphane) için;
    yukarıdaki "excel" yöntemi ise gerçek O/Ş/Puan içeren tam risk
    değerlendirmeleri için — farklı hedef, farklı şema. Kullanıcı zaman
    içinde birden çok sektör/iş dosyasını TEK "master" kütüphaneye biriktirmek
    istediğinden: kategoriden bağımsız tüm kütüphaneyle metin benzerliği
    (`similar_text`, Türkçe normalize) karşılaştırılır — aynı kategoride
    ≥97% "zaten bu" sayılıp günceller, ≥80% (herhangi kategoride) otomatik
    eklenmeyip `App\Models\TehlikeCakismasi` olarak biriktirilir.
    `App\Filament\Pages\TehlikeCakismalari` (nav rozetli, boşken gizli):
    mevcut/yeni yan yana, "Mevcudu Koru / Yenisini Kullan / İkisini de Tut".
  - **Excel içe aktarmalarda "siyah ekran" (bellek taşması) düzeltmesi ✅:**
    Gerçek dünya dosyaları (biçimlendirilmiş/çok sayfalı) 512M `memory_limit`i
    aşıp fatal hataya yol açıyordu. `App\Support\ExcelBellek` (1024M'a çıkarır)
    + her üç Excel okuyucuda `setReadDataOnly(true)` (stil atlanır, ~%85 bellek
    tasarrufu). **Yan etki + düzeltme:** `setReadDataOnly` dosyanın "aktif
    sayfa" bilgisini güvenilmez kılıyor — `RiskDegerlendirmesiExcelOkuyucu`
    artık aktif sayfaya güvenmeden TÜM sayfaları tarayıp en iyi eşleşen
    başlık satırını bulan sayfayı kullanıyor (kullanıcı dosyayı hiç
    değiştirmeden olduğu gibi yükleyebiliyor).
  - **Excel'den gelen puan yöntem uyuşmazlığı düzeltmesi ✅:** Kullanıcı "olasılık/
    şiddet aktarılmıyor" dedi — kök neden, Adım 5'in O/Ş(/F) açılır listelerinin
    yalnız SEÇİLİ puanlama yönteminin (varsayılan 5x5 Matris, 1-5) sabit
    seçeneklerini göstermesiydi; dosya Fine-Kinney ölçeğindeyse (0.2/6/15 gibi)
    değer `secilenler`de duruyor ama listede seçili görünmüyordu. `excelSecilenleriEkle()`
    artık eklenen maddelerin 5x5 ölçeğine uyup uymadığını (veya Frekans dolu mu)
    kontrol edip gerekirse `$yontem`'i otomatik `fine_kinney`'e çeviriyor.
  - **Excel'den doğrudan sektörel şablon oluşturma ✅:** `ListRiskSablonus`
    header'ında "Excel'den Sektörel Şablon Oluştur" — sihirbazı hiç açmadan,
    dosyadaki TÜM adayları (seçim/filtre yok) `RiskDegerlendirmesiExcelOkuyucu`
    ile okuyup doğrudan `RiskSablonu::olustur()`'a veriyor; hiçbir alan/puan
    değiştirilmiyor. `RiskSkorlama::fineKinneyeUyuyorMu()` (RiskSihirbazi'dan
    taşındı, paylaşılır oldu) yöntemi otomatik seçiyor. Oluşan şablon mevcut
    "Şablonlar" akışıyla (değişiklik gerekmedi) her zamanki gibi yeni
    firmalara tek tıkla uygulanıyor, puanlar dahil.
  **130 test toplam.**
  - **Kalan:** ~~Kayıtlı Risklerim (klasörlü)~~ ve ~~`RiskSablonu` `maddeler`
    düzenleme~~ **✅ 06.09.2026'da tamamlandı** (bkz. aşağıdaki Durum notları);
    İnşaat gibi sektörlerde alt-faaliyet (Kazı/Kalıp/İskele/Çatı/Zemin
    İyileştirme) çoklu-seçim arayüzü (kullanıcı Tehlike Kütüphanesi'ne kendi
    kategorilerini yükledikçe kurulacak); sektörel "orijinal şablon" Word
    çıktısı (Acil Durum'daki gibi — risk tablosu değişken satır sayılı olduğundan
    daha karmaşık, ayrı ele alınacak).
- **Faz 3h — Eğitim Katılım Formu ✅ (isgpratik EĞİTİM klasörü, 33 ekran görüntüsü):**
  `App\Filament\Pages\EgitimKatilim` (stub yerine geçti) + `App\Models\EgitimKatilim`
  (firma başına çoklu kayıt — her "Form PDF" tıklaması yeni bir belge) +
  `App\Support\EgitimIcerikOlusturucu` + `App\Support\EgitimKatilimUretici` (dompdf) +
  `config isg.egitim`.
  - **"İş Sağlığı ve Güvenliği" (varsayılan) başlığı 4 sabit blok:** Genel Konular (4
    madde, 80dk), Sağlık Konuları (5 madde, 80dk), Teknik Konular (12 madde, 120dk) —
    hepsi ekran görüntülerinden birebir; **İşyerine Özgü Riskler** ise seçilen sektöre
    göre değişir (6 sektör hazır: İnşaat, Maden, Tekstil, Enerji/Elektrik, Çimento/Beton,
    Nakliye/Taşıma — her biri 5 madde). Tehlike sınıfına göre toplam süre bilgi kutusu
    (`config isg.egitim.sureler` — tüm 3 tehlike sınıfı için saat/işe-özgü-dk/dinlenme-dk
    kullanıcı tarafından doğrulandı: az tehlikeli 8s/90dk/120dk, tehlikeli 12s/135dk/180dk,
    çok tehlikeli 16s/180dk/240dk).
  - **Diğer 13 başlık tek bloklu "özel" eğitimlerdir** (Fiziksel Risk Etmenleri,
    Yükseklerde Çalışma, Kapalı Alanlarda Çalışma, İş Kazası Sonrası İşe Dönüş, Çalışan
    Temsilcisi, Risk Değerlendirme Ekibi, İSG Kurulu, Acil Durum Koordinatörü, Söndürme/
    Kurtarma/Koruma/İlk Yardım Ekibi, Destek Elemanları) — her biri kendi sabit numaralı
    konu listesiyle gelir, dakika/kişi kontrolü yok.
  - **Katılımcı listesi:** firma çalışanları otomatik listelenir (varsayılan hepsi
    seçili, toggle edilebilir) + manuel Ad Soyad/T.C./Görev ekleme + Excel toplu
    yükleme (`App\Support\KatilimciExcelOkuyucu` — `CalisanExcelIceAktarici` deseniyle
    aynı esnek sütun eşleştirme, ama veritabanına yazmaz, yalnız listeye ekler)."Elle
    eklenenleri firmaya da kaydet" işaretlenirse kayıt anında `Calisan` oluşturulur.
  - Konu içeriği kayıt anında `konu_secimleri` JSON'una **anlık görüntü** olarak
    yazılır (config sonradan değişse de geçmiş belgeler değişmez). Sayfa altında
    firmanın geçmiş eğitim kayıtları listelenir (PDF yeniden indir / sil).
  - PDF logo için ayrı yükleme eklenmedi — `Firma.logo` (zaten firma kaydında var)
    kullanılıyor.
  `EgitimKatilimTest` (11 test). **141 test toplam.**
- **Faz 3i — Atama Yazıları ✅ (isgpratik 38-44.jpg):** `App\Filament\Pages\AtamaYazilari`
  (stub yerine geçti) + `App\Models\AtamaYazisi` + `App\Support\AtamaYazisiUretici` (dompdf)
  + `config isg.atama.roller` (10 görev tipi). Her rol `tip` alanına göre iki şekilden
  birini kullanır: **'tekli'** (Çalışan Temsilcisi, İşveren Vekili, Risk Değerlendirme
  Ekibi, Bilgi Sahibi Çalışan, Acil Durum Koordinatörü) — tek çalışan + Görev Başlangıç/
  Bitiş tarihi; **'ekip'** (Söndürme/Kurtarma/Koruma/İlk Yardım Ekibi, İSG Kurulu) — çoklu
  çalışan seçimi + ★ ile "baş üye" işaretleme. İSG Kurulu'na özel "Firma Profilinden
  Otomatik Doldur" tüm firma çalışanlarını tek tıkla ekler. Her rolün mevzuat dayanağı
  metni (`aciklama`) PDF gövdesine basılır; anahtarlar örtüştüğü yerde `isg.egitim.
  ozel_basliklar` ile birebir aynı (calisan_temsilcisi, sondurme_ekibi, vb.). Word çıktısı
  **kapsam dışı** — isgpratik'in 10 rol için ayrı literal-şablon .docx'u yok, yalnız PDF.
  Seçim süreci evrakları (seçim duyurusu/aday başvuru/oy pusulası/seçim tutanağı — yalnız
  seçimli roller çalışan temsilcisi + İSG kurulu için anlamlı) **kapsam dışı** bırakıldı.
  `AtamaYazilariTest` (8 test).
- **Faz 3j — İSG Kurul Toplantısı ✅ (isgpratik yardım/kurul-toplantisi rehberi):**
  `App\Filament\Pages\KurulToplantisi` (stub yerine geçti) + `App\Models\KurulToplantisi`
  (firma başına çoklu toplantı) + `App\Support\KurulToplantisiUretici` (dompdf) +
  `App\Support\GeminiKararDanismani` (gerçek LLM) + `config isg.kurul_toplantisi`.
  Katılımcı/gündem/karar listeleri JSON tutulur (codebase'deki "liste of item" deseni,
  bkz. `AcilDurumPlani.konular`) — ayrı çocuk tablo açılmadı. Akış: toplantı oluştur
  (tarih/saat/yer/başkan) → **katılımcı** ekle (firma çalışanından hızlı seç veya manuel,
  katılım durumu tek tıkla toggle) → **gündem** ekle (manuel veya `config isg.
  kurul_toplantisi.hazir_gundem_maddeleri` — 5 kategori, kategorilere ayrılmış sabit
  liste, tek tıkla ekle) → her gündem maddesinden **"Karar Yaz"** ile karar ekle (metin/
  sorumlu/termin/durum) → **PDF Tutanak** indir. **Gerçek Gemini entegrasyonu:** karar
  formunda "✨ AI Öner" butonu `GeminiKararDanismani::oner()` ile gündem maddesi metninden
  tek cümlelik somut karar önerisi ister (`GeminiRiskDanismani` ile aynı desen — API
  anahtarı yoksa/istek başarısızsa sessizce null döner, kullanıcı elle yazar). Rehberde
  anlatılan "AI ile Gündem Önerisi" (sektöre özel 10 gündem maddesi) bilinçli olarak
  **kapsam dışı** bırakıldı — hazır kategorili liste zaten aynı ihtiyacı karşılıyor, AI
  bütçesi karar-metni önerisine ayrıldı.
  `KurulToplantisiTest` (11 test, `Http::fake`). **160 test toplam.**
- **Faz 3k — Eğitim Soruları ✅ (isgpratik 69-70.jpg):** `App\Filament\Pages\EgitimSorulari`
  (stub yerine geçti) + `App\Models\EgitimSinavi` + `App\Support\EgitimSinaviUretici`
  (dompdf) + `App\Support\GeminiSoruUretici` (gerçek LLM). Sektör listesi AYRI bir config
  bloğu AÇILMADI — `isg.risk_ai.sektorler` (13 sektör) + "Genel" doğrudan reuse edildi
  (tekrar veri girmemek için). "AI ile 10 Soru Üret" Gemini'den `responseSchema` ile
  4 şıklı çoktan seçmeli soru listesi ister (`GeminiRiskDanismani`/`GeminiKararDanismani`
  ile aynı desen — anahtar yoksa/istek başarısızsa boş dizi, kullanıcı elle ekler).
  Eksik şıklı/geçersiz `dogru_index`'li adaylar sessizce elenir. PDF: her katılımcı için
  ayrı sınav sayfası + "cevap anahtarı dahil mi" (isgpratik'teki "Sınavdan Önce/Sonra"
  toggle'ının karşılığı) işaretliyse sonda ayrı cevap anahtarı sayfası. **Kapsam dışı:**
  soru şablonu kaydetme/yükleme ("Şablonlarım"), sınav öncesi/sonrası ayrı PDF üretimi.
  `EgitimSorulariTest` (10 test, `Http::fake`).
- **Faz 3l — Tespit ve Öneri Defteri ✅ (isgpratik 65-66.jpg):**
  `App\Filament\Pages\TespitOneriDefteri` (stub yerine geçti) + `App\Models\
  TespitOneriDefteri` (firma başına TEK kayıt — `AcilDurumPlani` ile aynı `firmaIcin()`
  deseni) + `App\Support\TespitOneriDefteriUretici` (dompdf) + `App\Support\
  GeminiOneriDanismani` (gerçek LLM) + `config isg.tespit_oneri.katalog` (6 kategori,
  ~14 hazır madde — isgpratik'in "171 madde" kataloğunun küçük, GENİŞLETİLEBİLİR bir alt
  kümesi; tam katalog ekran görüntülerinden tek tek çıkarılamayacak kadar büyük, zamanla
  büyütülecek). Akış: katalogdan konu/arama filtresiyle madde bul → "+ Ekle" ile deftere
  ekle, VEYA "Diğer (Kendiniz Yazın)" bölümünde serbest tespit yazıp "✨ Yapay Zekadan
  Öneri Al" ile Gemini'den tek cümlelik öneri iste → deftere ekle. PDF çıktısı öncelik
  renkli (yüksek/orta/düşük) madde listesi + imza alanları.
  `TespitOneriDefteriTest` (9 test, `Http::fake`). **179 test toplam.**
- **Faz 3m — KKD Zimmet Formu ✅ (isgpratik 71-75.jpg):** `App\Filament\Pages\KkdFormu`
  (stub yerine geçti) + `App\Models\KkdZimmetFormu` + `App\Support\KkdZimmetFormuUretici`
  (dompdf) + `config isg.kkd.kategoriler` (6 kategori — Baş/Yüz, İşitme, Solunum, El/Kol,
  Ayak/Bacak, Gövde/Yükseklik — isgpratik ekranlarından birebir, her madde TS EN standart
  numarasıyla, toplam ~85 KKD maddesi). Çoklu çalışan (firma + manuel) × çoklu KKD
  (kategori bazlı "Kategorinin Tümünü Seç" dahil) seçilir; PDF her çalışan için AYRI
  teslim tutanağı sayfası üretir (KKD seti hepsinde aynı — isgpratik'te de tekil seçim).
  `KkdFormuTest` (7 test).
- **Faz 3n — İş İzin Formu (PTW) ✅ (isgpratik 76-78.jpg):** `App\Filament\Pages\
  IsIzinFormu` (stub yerine geçti) + `App\Models\IsIzinFormu` + `App\Support\
  IsIzinFormuUretici` (dompdf) + `config isg.is_izin` (4 izin türü: sıcak iş/yüksekte/
  kapalı alan/elektrik + 16 güvenlik önlemi maddesi, her biri `tur` alanıyla ilişkili —
  `null` = her izinde görünür, doluysa yalnız o tür seçiliyken). İzin türü değişince
  önlem listesi dinamik güncellenir; artık geçersiz olan seçili önlemler otomatik
  temizlenir. `IsIzinFormuTest` (7 test).
- **Faz 3o — İSG Ceza ve Tebliğ Tutanağı ✅ (isgpratik 79-80.jpg):**
  `App\Filament\Pages\CezaTeblig` (stub yerine geçti) + `App\Models\CezaTebligTutanagi`
  + `App\Support\CezaTebligTutanagiUretici` (dompdf) + `config isg.ceza_teblig`
  (2 ihlal kategorisi katalog + 4 yaptırım tipi). Çalışan bilgisi + olay bilgisi +
  tanıklar + katalogdan/serbest ihlal maddesi + tek seçimli yaptırım (Sözlü Uyarı/
  Yazılı İhtar/Ücret Kesme/Yazılı Savunma) + tebliğ/tebellüğ (imzaladı/imtina etti).
  **O zaman kapsam dışı bırakılmıştı:** isgpratik'in ikinci sekmesi "İşverene İPC
  Tebliği" — sonradan Faz 3ac'de tamamlandı.
  `CezaTebligTest` (9 test). **202 test toplam.**
- **Faz 3p — Talimat Oluştur ✅ (isgpratik 82-83.jpg):** `App\Filament\Pages\
  TalimatOlustur` (stub yerine geçti) + `App\Models\Talimat` + `App\Support\
  TalimatUretici` (dompdf) + `App\Support\GeminiTalimatUretici` (gerçek LLM) +
  `config isg.talimat` (12 kategori, **30 hazır şablon** — isgpratik'in 36 şablonluk
  kütüphanesinden ekran görüntülerinden çıkarılabilenler; başlık/kategori/açıklama/KKD
  listesi birebir). Şablon seçilince başlık/kategori/açıklama/KKD otomatik dolar; "AI ile
  Üret" Gemini'den 8-12 maddelik adım adım talimat metni ister (aynı `responseSchema`
  deseni — anahtar yoksa/istek başarısızsa boş, kullanıcı elle madde ekler). **Not:**
  isgpratik'in şablonlarının tam adım-adım gövde metinleri ekran görüntülerinde
  görünmüyordu (yalnız "İncele" tıklanınca açılan bir modal içinde olmalı) — bu yüzden
  gövde metni sabit kopyalanmadı, bilinçli olarak AI/manuel üretime bırakıldı.
  `TalimatOlusturTest` (10 test, `Http::fake`). **212 test toplam.**
  - **Kendi arşivinden toplu yükleme ✅:** Kullanıcı "talimatlara bende ekleme yapacağım
    kendi arşivimden" dedi — `App\Models\TalimatSablonu` (uzman bazlı, `talimat_sablonlari`
    tablosu) + `App\Support\TalimatSablonuExcelIceAktarici` (`FirmaExcelIceAktarici` ile
    aynı Şablon İndir/Excel Yükle deseni). Excel'de **Maddeler** sütunu hücre içi çok
    satırlı olabilir (Alt+Enter ile alt satıra geçilerek yazılan her satır ayrı madde
    olur); **KKD'ler** virgülle ayrılır; **Kategori** serbest metinle girilse de
    `config isg.talimat.kategoriler` adlarıyla normalize edilip eşleşirse otomatik
    anahtara bağlanır (eşleşmezse kategori boş/serbest kalır — veri kaybı olmaz).
    Yüklenen şablonlar Şablon Kütüphanesi listesinde 30 hazır şablonun yanına **"Arşivim"**
    rozetiyle eklenir, silinebilir; yalnız kendi kullanıcısına görünür (`user_id` scope).
    `sablonSec()` artık `(kaynak, anahtar)` alır ('hazir'=config index, 'ozel'=DB id).
    5 yeni test eklendi. **224 test toplam.**
- **Faz 3q — Yıllık Planlar ✅ (isgpratik 86-87.jpg):** `App\Filament\Pages\YillikPlanlar`
  (stub yerine geçti) + `App\Models\YillikPlan` (firma + yıl başına TEK kayıt, `AcilDurumPlani`
  ile aynı `firmaXIcin()` deseni) + `App\Support\YillikPlanUretici` (dompdf, A4 yatay) +
  `config isg.yillik_plan.varsayilan_faaliyetler` (14 faaliyet, isgpratik ekranından
  birebir — Risk Değ. Revizyonu, Tehlike Kaynak Analizi, Asansör/Basınçlı Kap/Elektrik
  periyodik kontrolleri, Topraklama/Gürültü/Toz/Aydınlatma ölçümleri, Yangın/Deprem
  Tatbikatı, İSG Kurul Toplantısı, Periyodik Sağlık Muayenesi). Firma+yıl seçilince plan
  otomatik oluşur ve varsayılan faaliyetler yüklenir; her ay hücresi tıklanarak **Boş →
  Planlandı → Tamamlandı** arasında döner (isgpratik'teki 3 renkli durum sistemi birebir).
  Faaliyet ekle/sil + "Varsayılana Sıfırla".
  `YillikPlanlarTest` (7 test). **219 test toplam.**
  - **Diğer 2 sekme sonradan eklendi ✅ (isgpratik 88-90.jpg):** "Yıllık Eğitim Planı"
    (`config isg.yillik_plan.varsayilan_egitimler`, 16 mevzuat eğitimi — Çalışma
    Mevzuatı, Yasal Hak ve Sorumluluklar, KKD Kullanımı, İlkyardım vb., her biri konu/
    süre(saat)/eğitici/hedef/hedef kitle + AYNI ay durum matrisi) ve "Yıllık
    Değerlendirme Raporu" (`config isg.yillik_plan.varsayilan_degerlendirmeler`,
    11 çalışma — Risk değerlendirmesi, Ortam ölçümleri, İşe giriş/periyodik muayeneler,
    Radyolojik/Biyolojik/Toksikolojik analizler, Fizyolojik/Psikolojik testler, Eğitim
    çalışmaları, Diğer — ay matrisi YOK, bunun yerine satır bazlı Tarih/Tekrar Sayısı/
    Yapan Kişi/Yöntem/Sonuç alanları; ilk 3'ü hariç hepsi mevzuat gereği önerilen
    varsayılan değerle gelir, kullanıcı üzerine yazar). `YillikPlan` modeline `egitimler`
    + `degerlendirmeler` JSON kolonları eklendi; sayfa artık 3 sekmeli
    (`$sekme` — 'calisma'/'egitim'/'degerlendirme'), `ayDurumDegistir()` hem faaliyetler
    hem eğitimler için `$alan` parametresiyle ortak kullanılıyor. Değerlendirme
    satırlarının serbest metin alanları `x-on:change="$wire.degerlendirmeGuncelle(...)"`
    ile güncelleniyor (Livewire nested-array `wire:model` sınırlaması nedeniyle —
    `KurulToplantisi.kararDurumGuncelle` ile aynı çözüm). PDF çıktısı artık 3 ayrı sayfa.
    5 yeni test. **228 test toplam.**
- **Faz 3r — İşbaşı Eğt. Tutanağı ✅ (isgpratik 60.jpg):** `App\Filament\Pages\IsbasiEgitim`
  (stub yerine geçti) + `App\Models\IsbasiEgitimTutanagi` + `App\Support\
  IsbasiEgitimTutanagiUretici` (dompdf) + `config isg.isbasi_egitim` (3 konu kategorisi —
  İşyeri Tanıtımı [5 madde], Görev ve Ekipman Tanıtımı [3 madde], İşe/İşyerine Özgü İSG
  [1 madde] — + eğitim yöntemleri: Uygulamalı/Teorik/Uygulamalı+Teorik). Firma çalışanından
  hızlı seçim; konu toggle + "Tümünü Seç"; TC No görünürlüğü `tc_gizli` bayrağıyla
  maskelenebilir (`tcGorunur()` — ilk 3 hane + `*`). `IsbasiEgitimTest` (8 test).
  **236 test toplam.**
- **Faz 3s — Tatbikat Tutanağı ✅ (isgpratik 61-65.jpg):** `App\Filament\Pages\
  TatbikatTutanagi` (stub yerine geçti) + `App\Models\TatbikatTutanagi` + `App\Support\
  TatbikatTutanagiUretici` (dompdf) + `config isg.tatbikat` (10 hazır senaryo — Yangın,
  Deprem, Genel Tahliye, Kimyasal Dökülme, İlkyardım/İş Kazası, Doğalgaz/LPG Kaçağı, Sel/Su
  Baskını, Elektrik Kaynaklı Yangın, Sabotaj/Güvenlik, Gıda Zehirlenmesi — her biri hazır
  metinle; süre dk). Senaryo seçilince metin otomatik dolar; görev alan ekipler (firma
  çalışanından hızlı ekle), değerlendirme kontrol listesi (5 varsayılan soru + serbest özel
  soru), tespit edilen eksiklikler, DÖF önerileri, katılımcılar bölümleri var; PDF aksiyonu
  yalnız firma seçiliyken görünür (`assertActionHidden` ile test edildi).
  `TatbikatTutanagiTest` (10 test). **246 test toplam.**
- **Faz 3t — İSG Profesyonelleri ✅ (kullanıcı talebi, isgpratik ekranı yok):**
  Kullanıcı "atama yazılarındaki İSG Kurulu'na görev tanımı ekle (İşveren/Çalışan
  Temsilcisi/Sivil Savunma Uzmanı/İnsan Kaynakları), İGU ve İşyeri Hekimini
  sistemden otomatik seç, kaşelerini yükleyip atandığı firmanın evraklarında
  otomatik bassın" dedi. `App\Models\IsgProfesyoneli` (yeni tablo
  `isg_profesyonelleri` — tip: igu/isyeri_hekimi/dsp, ad_soyad, unvan,
  sertifika_no, kase_gorseli, imza_gorseli; `user_id` scope, Firma ile aynı
  `booted()->saving()` deseni) + `App\Filament\Resources\IsgProfesyonelis\
  IsgProfesyoneliResource` (List/Create/Edit, nav "Yönetim" grubu, kaşe/imza
  `FileUpload`). `Firma`'ya `igu_id`/`isyeri_hekimi_id`/`dsp_id` nullable FK
  eklendi (`FirmaForm`'da 3 ayrı Select ile atanır). **Atama Yazıları — İSG
  Kurulu ekip seçiminde:** rol `isg_kurulu` seçilince (veya firma değişince)
  firmaya atanmış İGU + İşyeri Hekimi otomatik seçili gelir (`varsayilanProfesyonelIdler()`);
  DSP + diğerleri manuel `profesyonelToggle()` ile eklenir/çıkarılır. Ayrıca
  seçili her firma çalışanı için `config isg.atama.kurul_gorevleri` (Başkan/İGU/
  İşyeri Hekimi/İnsan Kaynakları Sorumlusu/Sivil Savunma Uzmanı/Usta-Formen/
  Çalışan Temsilcisi/DSP/Diğer) üzerinden kurul içindeki görev tanımı atanabilir
  (`kurulGorevleri[calisan_id]` — firma içi genel `gorev` alanının yerine PDF'te
  bu görev metni basılır). PDF (`atama-yazisi.blade.php`) üye tablosuna "Kaşe /
  İmza" sütunu eklendi — profesyonel üyenin `kase_gorseli`si varsa dompdf'e
  `storage_path('app/public/...')` ile gömülü resim olarak basılır (yoksa "—").
  Bu, projede kaşe görselinin bir PDF'e gerçekten basıldığı **ilk** kullanım
  (Profilim'in kendi kaşe/imza yüklemesi daha önce depolanıyordu ama hiçbir
  belgede kullanılmıyordu) — aynı desen ileride diğer modüllerin (Tatbikat,
  Risk Değerlendirmesi vb.) "(İmza – Kaşe)" placeholder'larına da uygulanabilir.
  `IsgProfesyoneliTest` (5 test) + `AtamaYazilariTest`'e 3 yeni test + `NavigasyonTest`'e
  1 sayfa. **255 test toplam.**
- **Faz 3u — Sertifika Oluştur ✅ (isgpratik 66-68.jpg):** `App\Filament\Pages\
  SertifikaOlustur` (stub yerine geçti) + `App\Models\Sertifika` + `App\Support\
  SertifikaUretici` (dompdf, A4 yatay, **katılımcı başına ayrı sayfa**) +
  `config isg.sertifika` (4 tip: İSG Sertifikası [çoklu eğitici — İGU+İşyeri
  Hekimi, `isg.egitim` 'genel' içeriğini reuse eder — sektör seçilince işyerine
  özgü riskler dahil], Yüksekte Çalışma/Kapalı Alanlarda Çalışma/Yangın Eğitimi
  [tek eğitici, `isg.egitim.ozel_basliklar`'dan sabit içerik reuse — yuksekte_
  calisma/kapali_alan/sondurme_ekibi]). Geçerlilik tarihi 3 hızlı buton ile
  hesaplanır (Az Tehlikeli +3 / Tehlikeli +2 / Çok Tehlikeli +1 yıl — Çalışanların
  İSG Eğitimlerinin Usul ve Esasları Yön.). Katılımcı listesi Eğitim Katılım ile
  birebir aynı desen (firma çalışanları hepsi seçili + manuel ekle + Excel toplu
  yükle). **Eğitici kaşesi firmaya atanmış İSG Profesyoneli'nden (Faz 3t)
  otomatik gelir** ve kayıt anında `egitici_igu_kase`/`egitici_hekim_kase`
  alanlarına anlık görüntü olarak snapshotlanır — Atama Yazıları'ndan sonra
  kaşe-basma deseninin **ikinci** kullanımı. Sertifika logosu (yok/sol/sağ/her
  iki taraf) + çerçeve stili (klasik/sade/mor) seçilebilir; isgpratik'in OSGB
  logosu sabit-sol kısıtı kapsam dışı (tek kullanıcılı panelde anlamsız).
  `SertifikaOlusturTest` (10 test). **265 test toplam.**
- **Faz 3v — DÖF Oluştur ✅ (isgpratik 158.jpg — "Çoklu DÖF Oluştur"):**
  `App\Filament\Pages\DofOlustur` (stub yerine geçti) + `App\Models\DofRaporu` +
  `App\Support\DofRaporuUretici` (dompdf, A4 yatay) + `config isg.dof` (4 öncelik:
  Düşük/Orta/Yüksek/Kritik; 4 durum: Açık/Devam Ediyor/Tamamlandı/Ertelendi).
  Rapor künyesi (Alan/Bölge, Gözetim Tarih Aralığı, Rapor Tarihi, Gözetim Yapan +
  Sertifika No, Sorumlu Kişi, İşveren/Vekili Adı) isgpratik ekranıyla birebir;
  Gözetim Yapan + Sertifika No firmaya atanmış İGU'dan otomatik dolar (üçüncü
  kaşe-basma kullanımı — `gozetim_yapan_kase` snapshotlanır). Çoklu madde ekleme
  (tespit + öncelik + öneri + sorumlu + termin + durum — durum listede tek tıkla
  güncellenir). **"Yapay Zekadan Öneri Al"** yeni `GeminiOneriDanismani`
  ÇAĞRISI değil, mevcut Tespit Öneri Defteri'nin AYNI sınıfını reuse eder (ayrı
  Gemini sınıfı yazmaya gerek kalmadı — tespit metninden öneri üretme ihtiyacı
  zaten aynı). isgpratik'in OSGB logosu / şablon kaydetme özellikleri kapsam
  dışı (tek kullanıcılı panelde anlamsız / ayrı iş). `DofOlusturTest` (10 test).
  **275 test toplam.**
- **Faz 3w — AI Saha Analizi ✅ (isgpratik AI SAHA ANALİZİ/1-6.jpg + gerçek
  örnek PDF):** Kullanıcı ekran görüntülerini `Desktop\isgpratik\AI SAHA
  ANALİZİ\` alt klasörüne ekledi — 6 ekran + isgpratik'ten indirilmiş gerçek
  bir "İSG Saha Gözetim Raporu" PDF örneği (Coklu-DOF-NİL-UNLU-...pdf).
  `App\Filament\Pages\AiSahaAnalizi` (stub yerine geçti) + `App\Models\
  SahaAnalizi` + `App\Support\SahaAnaliziUretici` (dompdf, A4 yatay) +
  `App\Support\GeminiSahaAnalizi` (**projede ilk Gemini VISION kullanımı** —
  önceki tüm Gemini sınıfları yalnız metin girdisi alıyordu; bu sefer fotoğraf
  `inline_data` (base64) olarak `parts` dizisine ekleniyor, `responseSchema`
  ile yapılandırılmış JSON dizisi isteniyor). Akış: firma seç → en fazla 10
  fotoğraf yükle (yalnız yeni eklenenler analiz edilir) + opsiyonel bağlam
  notu → "Fotoğrafları AI ile Analiz Et" → her fotoğraftaki uygunsuzluk için
  bulgu (bina/bölge, kategori, tespit, öneriler, yasal gerekçe, risk derecesi
  1-4) otomatik gelir; kullanıcı bulguları elden geçirip (metin düzenle/sil)
  seçtiklerini rapora dahil eder. **PDF çıktısı isgpratik'in gerçek "İSG Saha
  Gözetim Raporu" PDF'iyle BİREBİR aynı kolon düzeninde** (Sıra No/Bina-Bölge/
  Uygunsuzluk Fotosu/Tehlikeler/Uygun Hale Getirme/Örnek Resim/Yasal Gerekçe/
  Risk Derecesi + üstte künye + risk derecesi renk lejantı + alt kısımda İGU
  kaşe/imza) — dördüncü kaşe-basma kullanımı (`gozetim_yapan_kase`).
  **Kapsam dışı bırakıldı:** "Uygun Hale Getirme (Örnek Resim)" için ayrı
  manuel görsel yükleme (isgpratik'te opsiyonel, sütun PDF'te boş kalıyor);
  isgpratik'in analiz kredisi/kota sistemi (ödeme entegrasyonu zaten kapsam
  dışı). `AiSahaAnaliziTest` (9 test). **284 test toplam.**
- **Faz 3x — AI Saha Analizi → DÖF Oluştur entegrasyonu ✅ (isgpratik'teki
  "Seçilenleri Çoklu DÖF'e Aktar" akışı):** Kullanıcı "entegre edelim" dedi.
  Bulgular bölümünde en az bir bulgu seçiliyken "Seçilenleri DÖF'e Aktar (N)"
  butonu belirir (`AiSahaAnalizi::secilenleriDofeAktar()`). DÖF Oluştur'un
  madde şeması (tespit/öncelik/öneri/sorumlu/termin/durum) AI Saha Analizi'nin
  bulgu şemasından (bina_bolge/kategori/tespit/oneriler/yasal_gerekce/
  risk_derecesi) farklı olduğundan **DofRaporu şeması değiştirilmedi** (sıfır
  regresyon riski) — bunun yerine aktarım anında veri iki DÖF alanına
  katlanıyor: `bina_bolge` varsa `tespit`'e `[Bina/Bölge] ...` öneki olarak,
  `yasal_gerekce` varsa `oneri`'nin sonuna "Yasal dayanak: ..." satırı olarak
  ekleniyor; `risk_derecesi` (1-4) → `oncelik` (kritik/yuksek/orta/dusuk)
  eşleniyor. İki sayfa arasında veri geçişi Livewire session flash ile:
  `session(['dof_aktarim' => [...]])` + `$this->redirect(DofOlustur::getUrl())`,
  `DofOlustur::mount()` içinde `session()->pull('dof_aktarim')` okunup
  firma+maddeler otomatik yükleniyor ve bildirim gösteriliyor. Foto/bina-bölge/
  yasal-gerekçe için DÖF PDF'i DEĞİŞMEDİ (o zenginlik yalnız AI Saha
  Analizi'nin kendi "İSG Saha Gözetim Raporu" PDF'inde kalıyor). Aktarımdan
  sonra AI Saha Analizi'ndeki bulgular silinmiyor (isgpratik'teki gibi
  non-destructive — kullanıcı hem DÖF'e aktarabilir hem kendi Saha Gözetim
  Raporu'nu da üretebilir). `AiSahaAnaliziTest`'e 1 test (session/redirect) +
  `DofOlusturTest`'e 1 test (mount ile otomatik yükleme) eklendi.
  **286 test toplam.**
- **Faz 3y — Saha Denetimi ✅ (isgpratik SAHA DENETİMİ/1-15.jpg + gerçek
  örnek PDF):** Kullanıcı `Desktop\isgpratik\SAHA DENETİMİ\` alt klasörüne
  15 ekran + gerçek bir "Şantiye Denetim ve Değerlendirme" PDF örneği ekledi,
  "raporlama yöntemi aynı şekilde kalsın" dedi. `App\Filament\Pages\
  SahaDenetimi` (stub yerine geçti — model adıyla çakıştığı için Page'de
  `use App\Models\SahaDenetimi as SahaDenetimiModel`) + `App\Models\
  SahaDenetimi` + `App\Support\SahaDenetimiUretici` (dompdf, A4 yatay) +
  `config isg.saha_denetimi` — **"Şantiye Denetim ve Değerlendirme v1"
  kontrol listesi, 9 kategori/41 madde isgpratik'ten BİREBİR** (kritik/
  uygulanamaz-izni bayrakları gerçek PDF'teki "KRİTİK" etiketleriyle
  doğrulandı — yalnız Şantiye Ekipleri/3.1 uygulanamaz seçeneği yok).
  isgpratik'teki 9 adımlı sihirbaz yerine tüm kategoriler tek sayfada
  (kapsam/zaman gerekçesiyle bilinçli sadeleştirme — "raporlama/PDF çıktısı
  aynı kalsın" isteği zaten PDF'e odaklıydı, giriş UX'i değil). Her madde
  Uygun/Uygun Değil/Uygulanamaz; "Uygun Değil" açıklama zorunlu + fotoğraf
  opsiyonel. **ÖNEMLİ HATA VE DÜZELTME:** madde kodları ("1.1", "2.3" gibi)
  Livewire'ın dot-notation property yoluyla ÇAKIŞTI — `wire:model="cevaplar.
  1.1.aciklama"` her noktayı ayrı dizi seviyesi sanıp `cevaplar['1']['1']
  ['aciklama']` şeklinde yanlış yorumluyordu, kayıt sessizce boş kalıyordu.
  Çözüm: `anahtar()` yardımcı metodu ile nokta alt çizgiye çevrilip
  ($cevaplar/$fotoYuklemeleri dizilerinde "1_1" gibi) güvenli anahtar
  kullanıldı; gerçek "kod" yalnız görüntüleme/PDF'te kullanılıyor. **Bu ders
  ileride benzer "X.Y" kod şemalı herhangi bir modülde tekrar kontrol
  edilmeli.** PDF çıktısı gerçek örnekle birebir: künye + kategori başlıklı
  madde tablosu (KRİTİK etiketi kırmızı) + Ekip Üyesi/Görev/KKD tablosu +
  9 maddelik Sabit Güvenlik Uyarıları (3 sütun) + Genel Notlar + Denetçi
  Kaşe/İmza (beşinci kaşe-basma kullanımı) + her fotoğraf kanıtı için ayrı
  sayfa. **Kapsam dışı bırakıldı:** "Taslaklar" sekmesi (yarım kalan
  denetimi kaydedip sonra devam etme — şu an tek oturumda tamamlanmalı).
  `SahaDenetimiTest` (10 test). **297 test toplam.**
- **Faz 3z — Saha Denetimi: sektöre özel kontrol maddesi ekleme ✅
  (isgpratik'in Şablon Editörü'nün küçük/sektörel bir versiyonu):**
  Kullanıcı "kontrol listesine ekleme için konu başlığı ve içeriği eklemek
  istiyorum — İnşaatlar için Kazı Kontrol, Kalıp, Beton Demir, İş Makineleri,
  İskele; yükseklten düşme için kapatılması gereken merdivenler, asansörler,
  kat kenarları, galeriler" dedi, sonra "ileride sektörel olarak ayırabiliriz
  (Metal, Orman gibi), detaylı ekipman kontrol listesi ayarlamayı
  planlıyorum ona göre dizayn et" ekledi. `App\Models\
  SahaDenetimiOzelMadde` (yeni tablo, `user_id` scope — Talimat Oluştur'un
  "Arşivim" / Risk Kütüphanesi Excel-ekleme ile AYNI "kendi arşivinden ekle"
  deseninin bir başka kullanımı) — alanlar: `sektor_anahtari` (nullable,
  **isg.risk_ai.sektorler'i reuse eder** — Risk Sihirbazı AI adımı ve Eğitim
  Katılım'ın işyerine özgü riskler bölümüyle aynı 13 sektörlük ortak taksonomi,
  yeni bir sektör listesi icat edilmedi), `kategori_ad` (serbest metin —
  mevcut bir başlıkla birebir eşleşirse o başlığın altına eklenir, yoksa yeni
  başlık açılır), `ifade`, `kritik`, `uygulanamaz_izni`. Sayfaya "Sektör
  (opsiyonel)" seçici + firma bağımsız (AI Saha Analizi'nin fotoğraf
  yükleme dersinden ders alınarak firma seçilmeden de kullanılabilir)
  "Kendi Kontrol Başlığı/Madde Ekle" bölümü eklendi — `kategoriler()`
  computed artık sabit 41 maddeyi + seçili sektöre uygun (`sektor_anahtari`
  null VEYA seçili sektörle eşleşen) özel maddeleri birleştirip döndürüyor;
  `tumMaddeler()`/`cevaplar`/PDF hiçbir ek değişiklik gerekmeden bu birleşik
  listeyi otomatik kullanıyor (kod şeması `OZL{id}` — nokta içermediği için
  Faz 3y'deki Livewire dot-notation tuzağına takılmıyor). İnşaat sektörü için
  tarif edilen 6 başlık/33 madde (Kazı Kontrolü, Kalıp İşleri, Beton ve Demir
  İşleri, İş Makineleri, İskele, Yükseklten Düşme Önleme) kullanıcının
  hesabına `sektor_anahtari='insaat'` ile veritabanına eklendi (tinker ile,
  UI'nin kendisi de aynı işi yapar). **Aynı oturumda ek istek:** "Uygulanamaz
  olanları raporda listeleme, istatistiklere ekleme" — istatistik zaten hariç
  tutuyordu (uygunluk yüzdesi yalnız uygun/uygun değil sayar), PDF ana
  tablosu da artık `uygulanamaz` sonuçlu satırları FİLTRELEYİP basmıyor (boş
  kalan kategori de otomatik gizleniyor). `SahaDenetimiTest`'e 5 yeni test +
  1 PDF-filtre testi (view render edilip HTML'de uygulanamaz maddenin
  metninin GEÇMEDİĞİ doğrulandı — dompdf binary'sinde metin arama yapmak
  yerine bilerek ham Blade view'ı render edip assertStringNotContainsString
  kullanıldı). **302 test toplam.**
- **Faz 3aa — İş Kazası Raporu ✅ (isgpratik'te ekran görüntüsü yok —
  planNotu'ndaki 16.jpg mevcut değil; standart kaza inceleme raporu
  formatına göre kuruldu):** `App\Filament\Pages\IsKazasiRaporu` (stub
  yerine geçti) + `App\Models\IsKazasiRaporu` + `App\Support\
  IsKazasiRaporuUretici` (dompdf) + `config isg.is_kazasi` (9 kaza türü,
  4 ağırlık derecesi, 8 kök neden kategorisi — 6331 s.K. ve genel kabul
  görmüş 5N1K/kök neden analizi yöntemine göre). Kazazede firma
  çalışanından hızlı seçilebilir veya manuel girilir; kaza tanımı + "nasıl
  oldu" + kök neden kategorileri (çoklu seçim) + serbest kök neden açıklaması
  + alınan/alınacak önlemler + tanıklar (CezaTebliğ ile aynı ekle/sil deseni)
  + SGK bildirim takibi (6331 m.14 — 3 iş günü uyarı metni sayfa üstünde
  sabit). Rapor hazırlayan (İSG Uzmanı) ve kaşesi firmaya atanmış İGU'dan
  otomatik gelir. **Kapsam dışı bırakıldı:** AI Saha Analizi→DÖF benzeri bir
  "önlemleri DÖF'e aktar" entegrasyonu (istenirse ayrı iş — bu modülün
  kendi içinde zaten alınacak önlemler alanı var). `IsKazasiRaporuTest`
  (8 test). **310 test toplam.**
- **Faz 3ab — Ücretsiz E-Reçetem ✅ + Araçlar ✅ (ikisi de isgpratik'te
  planNotu'nda spesifik ekran görüntüsü referansı yok — genel bilgiyle
  kuruldu):** `EReetem` (mevcut stub sınıf adı korunarak, İsgKatipRobot ile
  aynı "gerçek entegrasyon yok, bilgi sayfası" deseninde) — e-Reçete süreci
  (muayene → hekim e-imzasıyla sisteme giriş → eczaneden ücretsiz temin),
  uygunluk kriterleri, kapsam dışı notu (mehse'den doğrudan MEDULA/e-Reçete
  yazılamaz), SSS; `config isg.e_recetem`. `Araclar` — kalıcı veri
  tutmayan, firma seçimi gerektirmeyen 2 bağımsız hesaplayıcı: Kaza Sıklık
  Hızı/Ağırlık Hızı (standart formül) ve Gürültü Maruziyet Düzeyi Lex,8h
  (çoklu ölçüm satırı ekle/sil, Gürültü Yönetmeliği eylem sınırlarına göre
  uyarı — `config isg.araclar.gurultu_sinirlari`). `EReetemTest` (3 test) +
  `AraclarTest` (10 test). **323 test toplam.**
- **Faz 3ac — İşverene İPC Tebliği ✅ (Ceza ve Tebliğ Tutanağı'nın isgpratik
  79-80.jpg'deki 2. sekmesi, Faz 3o'da ertelenmişti):** `CezaTeblig` sayfasına
  `aktifSekme` (tutanak/ipc) ile SEKME EKLENDİ — ayrı bir Page/URL açılmadı,
  isgpratik'in gerçek ekranındaki tek-sayfa-iki-sekme yapısı korundu. Firma
  seçici artık iki sekmenin ÜSTÜNDE paylaşılan tek alan. Yeni `App\Models\
  IpcTebligi` + `App\Support\IpcTebligiUretici` (dompdf) + `config
  isg.ceza_teblig.ipc_maddeleri` (6331 s.K. m.26 kapsamı, 9 ihlal başlığı —
  yıllık değişen TL tutarları config'e YAZILMADI, tutar tebligattan
  kullanıcı tarafından girilir). Peşin ödeme tutarı otomatik %25 indirimli
  hesaplanır (Kabahatler Kanunu m.17/6); PDF'te sabit "15 gün içinde Sulh
  Ceza Hakimliği'ne itiraz" bilgilendirmesi (m.27). Hazırlayan (İSG Uzmanı)
  + kaşesi firmaya atanmış İGU'dan otomatik (yedinci kaşe-basma kullanımı).
  `CezaTebligTest`'e 7 test eklendi (sekme görünürlük ayrımı dahil —
  `pdf`/`pdfIpc` aksiyonları yalnız kendi sekmesi aktifken görünür).
  **330 test toplam.**
- **Faz 3ad — Muayene Formu (EK-2) ✅ (isgpratik'te ekran görüntüsü yok —
  daha önce "çok büyük" gerekçesiyle ertelenmişti; İşyeri Hekimi ve Diğer
  Sağlık Personelinin Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında
  Yönetmelik EK-2'sine göre kuruldu):** `App\Filament\Pages\MuayeneFormu`
  (stub yerine geçti) + `App\Models\MuayeneFormu` + `App\Support\
  MuayeneFormuUretici` (dompdf) + `config isg.muayene` (4 muayene türü,
  10 sistemik muayene başlığı, 8 tetkik, 4 sonuç/kanaat, tehlike sınıfına
  göre periyot yılı). Çalışan firma çalışanından hızlı seçilir/manuel
  girilir; meslek öyküsü+maruziyet / özgeçmiş+soygeçmiş (serbest metin) /
  sistemik muayene (10 sistem × Normal-Anormal + not, KKD Formu'ndaki
  checklist desenine benzer) / tetkikler (8 kalem × Yapıldı toggle +
  sonuç + not) / sonuç-kanaat (4 seçenek, "Şartlı Uygundur" seçilince
  şart açıklaması alanı açılır) / "Tehlike Sınıfına Göre Öner" butonu
  (firma tehlike sınıfına göre bir sonraki kontrol tarihini otomatik
  hesaplar — Az Tehlikeli 5, Tehlikeli 3, Çok Tehlikeli 1 yıl). Hekim adı
  firmaya atanmış İşyeri Hekimi'nden otomatik, kaşesi PDF'e basılır
  (sekizinci kaşe-basma kullanımı). `MuayeneFormuTest` (10 test).
  **340 test toplam.**
- **Faz 3ae — Ziyaret Programı ✅ (isgpratik'te ekran görüntüsü yok; daha
  önce "aylık/haftalık takvim, sürükle-bırak, çoklu firma/OSGB atama —
  isgsuite/OSGB tarzı karmaşık" gerekçesiyle ertelenmişti; kullanıcı
  onayıyla BASİTLEŞTİRİLMİŞ liste olarak kuruldu):** `App\Filament\Pages\
  ZiyaretProgrami` (stub yerine geçti) + `App\Models\ZiyaretProgrami`
  (firma+yıl başına TEK kayıt, AcilDurumPlani/YillikPlan ile aynı
  `firmaYilIcin()` deseni) + `App\Support\ZiyaretProgramiUretici` (dompdf)
  + `config isg.ziyaret_programi.amac_kategorileri` (datalist için 8 hazır
  kategori). 12 aylık satır (Tarih/Amaç-Kapsam/Süre/Durum/Notlar); durum
  hücresi YillikPlanlar'daki Boş→Planlandı→Tamamlandı tıklama döngüsüyle
  birebir aynı (`ZiyaretProgrami::DURUM_SIRASI`). Gerçek takvim/sürükle-
  bırak arayüzü KAPSAM DIŞI bırakıldı — isgpratik'in görsel takvimi yerine
  düz liste. **Yeni Gemini entegrasyonu:** `GeminiZiyaretDanismani` —
  GeminiKararDanismani ile birebir aynı desen (API anahtarı yoksa/istek
  başarısızsa sessizce null); firma sektörü+tehlike sınıfı+ay adından o ay
  için kısa bir ziyaret amacı/kapsamı önerir, "✨" ikonlu buton ile ilgili
  ay satırının Amaç hücresine yazılır — bu, planNotu'ndaki "AI önerili"
  ifadesini gerçek (ama küçük kapsamlı) bir AI özelliğiyle karşılıyor.
  `ZiyaretProgramiTest` (8 test). **348 test toplam.**
- **Tüm sol menü modülleri tamamlandı.** `HazirlanryorPage` stub temel
  sınıfı ve `hazirlaniyor.blade.php` görünümü artık hiçbir sayfa tarafından
  kullanılmadığından SİLİNDİ (ölü kod). isgpratik kök klasöründe
  24-101(+133-135,158) + AI SAHA ANALİZİ + SAHA DENETİMİ alt klasörleri
  artık tam incelendi; 88-101 aralığı kapsam dışı isgpratik özellikleri
  (İSG Arşiv 2860 dosya, İSG Deneme Sınavı 3499 soru, genel Mevzuat sayfası
  — sol menümüzde yok). Yeni bir modül gerekirse Eğitim Katılım/Atama
  Yazıları/Kurul Toplantısı ile aynı desende (config-driven içerik +
  Filament Page + dompdf + test) kurulacak.

## Tasarım cilası (Faz 4)

Tüm modüller tamamlandıktan sonra kullanıcı, isgpratik.com'un gerçek ekran
görüntülerine göre (renk/banner/buton/uyarı alanı) görsel cila turuna
başlanmasını istedi. Sırasıyla ele alınacak.

- **Atama Yazıları ✅ (isgpratik.com/panel/atama-yazilari canlı ekran
  görüntüsü):** İkonlu pill-sekme rol seçici + role göre değişen mor
  gradyan başlık banner'ı + tüm rollerin yasal dayanağını listeleyen
  kalıcı "Bilgi" kutusu (config'teki mevcut `aciklama` alanlarından,
  yeni metin yazılmadı). Config'teki her role `ikon` (heroicon) eklendi.
  **Kullanıcı geri bildirimi (kritik düzeltme):** "Atamalar yapıldıktan
  sonra her biri için AYRI hazırlanmış atama evrakı yap; dosyanın içindeki
  benim koyduğum yazıyı istemiyorum." — iki kök sorun tespit edildi ve
  düzeltildi: (1) `pdf.atama-yazisi.blade.php`'nin gövde metnine
  `{{ $rol['aciklama'] }}` (uzun yasal/mevzuat açıklama paragrafı)
  doğrudan basılıyordu — resmi bir görevlendirme yazısında bulunmaması
  gereken bir metin, kaldırıldı. (2) Ekip rolünde (örn. Söndürme Ekibi, 3
  üye) TEK PDF içinde ortak bir üye tablosu üretiliyordu; artık
  Sertifika Oluştur'daki "katılımcı başına ayrı sayfa" deseniyle birebir
  aynı şekilde HER ÜYE KENDİ SAYFASINDA, kendi adına yazılmış gövde
  metni ve kendi imza satırıyla ayrı görevlendirme belgesi alıyor
  (`page-break-before: always`). Bu düzeltme hem 'tekli' hem 'ekip'
  rollerde aynı döngüyle çalışıyor (tekli roller zaten tek üyelik).
  "Eğitim Katılım Formu Oluştur" butonu — AI Saha Analizi→DÖF Oluştur'da
  kurulan session-aktarım deseniyle (`session(['egitim_katilim_aktarim'
  => [...]]); redirect(EgitimKatilim::getUrl());`) atanan üyeleri
  Eğitim Katılım'ın `manuelKatilimcilar`'ına aktarır; rol anahtarı
  `config('isg.egitim.ozel_basliklar')`'da varsa (8 rol: söndürme/
  kurtarma/koruma/ilkyardım ekibi, isg_kurulu, çalışan temsilcisi, risk
  değ. ekibi, acil durum koordinatörü) o eğitim başlığı otomatik seçilir,
  yoksa (işveren vekili/bilgi sahibi) "genel" İSG eğitimine düşer. İSG
  Kurulu'ndaki İGU/hekim/DSP gibi profesyonel üyeler (çalışan değil)
  katılımcı aktarımına DAHİL EDİLMEZ (`kase_gorseli` anahtarının varlığı
  ile ayırt edildi).
  **DÜZELTME (kullanıcı ikinci geri bildirimi — Word çıktısı için):**
  "Atama yazıların için indirilen Word'de isgpratik'teki GERÇEK belgeleri
  kullanmak istiyorum, buradaki belgeleri bozmadan firma/çalışan
  değişecek şekilde üreteceğiz, genel yazılara dokunmayacağız — senin
  hazırladıkların yönetmeliği karşılamıyor." İlk sürümde (yukarıdaki
  paragrafta bahsedilen, PhpWord ile sıfırdan yazılmış basit gövde metni)
  gerçekten eksikti — isgpratik'in gerçek çıktıları her rol için özel,
  numaralı "Görev ve Sorumluluklar" listesi ve tam yasal atıflar
  içeriyor. **Çözüm — AcilDurumWordUretici ile AYNI YÖNTEM (gerçek
  referans .docx'i BİREBİR ŞABLON kullan):** kullanıcının isgpratik'ten
  indirdiği 8 gerçek örnek çıktı (`ATAMA YAZISI/*/`) `resources/belge/
  atama-yazisi/{rol}.docx` olarak birebir kopyalandı (çalışan temsilcisi,
  işveren vekili, bilgi sahibi, söndürme/kurtarma/koruma/ilkyardım ekibi,
  isg kurulu); "Risk Değerlendirme Ekibi" için gerçek çıktı yalnız PDF
  olarak vardı — metni birebir doğrulanıp aynı düzende yeni bir .docx
  şablonu PhpWord ile bir kerelik inşa edildi (İÇERİK UYDURULMADI, gerçek
  PDF'ten kopyalandı). "Acil Durum Koordinatörü" için hiçbir referans
  (ne docx ne PDF) bulunamadığından bu rol için Word ÜRETİLMİYOR (buton
  gizli, `AtamaYazisiWordUretici::sablonVarMi()` false döner) — mevzuat
  uyumu doğrulanamayan içerik uydurulmadı. `AtamaYazisiWordUretici`
  baştan yazıldı: `AcilDurumWordUretici`'deki paragraf-birleştirip-
  aralık-bulma tekniğiyle firma/tarih/ad-soyad/TC/görev DEĞİŞTİRİLİYOR,
  genel/yasal metne DOKUNULMUYOR. "Ekip" şablonlarında (Söndürme/
  Kurtarma/Koruma/İlkyardım/İSG Kurulu) örnek üye tablo satırı gerçek üye
  sayısı kadar KLONLANIYOR (yeni teknik: `<w:tr>` DOM node cloneNode);
  tehlike sınıfı/çalışan sayısı/asgari görevlendirme (İşyerlerinde Acil
  Durumlar Yön. md.11 — her 30/40/50 çalışana 1 kişi formülü) canlı
  hesaplanıyor. `AtamaYazisiTest`'e 8 yeni test (şablon değişimi doğrulama
  + tablo klonlama + acil_durum_koordinatoru'nda buton gizli).
- **Eğitim Katılım + Sertifika Oluştur — düzenlenebilir "Eğitim Konuları"
  ✅ (kullanıcının paylaştığı isgpratik.com/egitim-katilim CANLI ekranı +
  isgpratik.com/sertifika CANLI ekranı):** Kullanıcı: "Eğitim butonunu
  buradaki sistemle aynı yap, sertifika seçeneklerini bölümlere/eğitim
  türüne göre değiştirmeye izin ver; sertifika da bu adresteki ile aynı
  olsun." `EgitimIcerikOlusturucu::olustur()` artık her maddeyi
  `{madde, dakika, dahil}` olarak döndürüyor (config'teki "özel başlık"
  serbest metin maddeleri de aynı şekle sarılıyor, varsayılan 15 dk —
  config içeriği sade kaldı, düzenlenebilirlik kod tarafında eklendi).
  Her iki sayfada `icerik` artık salt-okunur `#[Computed]` değil,
  kullanıcının değiştirebildiği stateful property; ortak
  `resources/views/filament/pages/partials/egitim-konulari.blade.php`
  partial'ı her maddeyi checkbox (dahil) + sayı kutusu (dakika) ile
  gösterir, kategori başlıkları dahil edilen maddelerin toplamını canlı
  hesaplar (`EgitimIcerikOlusturucu::bolumSuresi()` — din dakikası =
  fiili/3, "1 ders saati: 45 dk ders + 15 dk dinlenme" formülünden).
  PDF şablonları (`pdf/egitim-katilim.blade.php`, `pdf/sertifika.blade.php`)
  yalnız `dahil=true` maddeleri basıyor. **Sertifika PDF'i tamamen
  yeniden tasarlandı** — kullanıcının verdiği gerçek "MEHMET ÖZDEMİR İSG
  Sertifikası" çıktısıyla BİREBİR: "diploma" tarzı eski tasarım yerine
  resmi "EĞİTİM BELGESİ" düzeni (künye tablosu: Ad Soyad/TC/Görev/Eğitim
  Türü-Şekli/Firma/Tarih/Geçerlilik/Süre + yönetmelik cümlesi + "Eğitimin
  Konuları" 4 bölüm × Türk alfabesi harflendirmesiyle (a,b,c,ç,d...)
  maddeler + her bölüm başlığında "Fiili Ders: Xdk / Din: Ydk" + 3 sütunlu
  imza bloğu + "(1 ders saati...)" dipnotu + "Düzenleme Tarihi"). Yeni
  `tur` (İlk Defa/Tekrar) ve `sekil` (Yüz Yüze/Uzaktan/Karma) alanları
  `sertifikalar` tablosuna eklendi (`config isg.sertifika.turler/
  sekiller`) — reference'taki "Eğitim Türü / Şekli" bilgisi için.
  **Hata bulundu ve düzeltildi:** PHP'nin native `strtoupper()` Türkçe
  karakterleri bozuyordu ("GÜVENLİĞİ" yerine "GüVENLIğI") — yeni paylaşılan
  `App\Support\TurkceMetin::buyuk()` (üç sınıfta tekrarlanan aynı
  mb_strtoupper+i→İ mantığının merkezi hali) ile düzeltildi.
  `EgitimKatilimTest`/`SertifikaOlusturTest` mevcut testleri (yeni
  madde şekliyle) güncellendi + `SertifikaOlusturTest`'e 3 yeni test
  (tür/şekil kaydı, madde hariç bırakma PDF'e yansıması, referans belge
  alanlarının varlığı). **362 test toplam.**
- **Sertifika — gerçek çerçeve seçenekleri + tek sayfaya sığdırma ✅
  (kullanıcı: "sertifikanın diğer yolladığım seçenekleri de koy, bir
  sayfaya sığdır, gerekirse yazı boyutunu düşür"):** isgpratik EĞİTİM
  klasöründeki 3 farklı gerçek sertifika PDF çıktısı (`(1).pdf`/`(2)
  (1).pdf`/`(4) (1).pdf` — üçü de aynı kişi/içerik, yalnız farklı çerçeve)
  incelendi; her biri gerçek bir çerçeve tasarımı olduğu ortaya çıktı.
  `config isg.sertifika.cerceveler` daha önce İCAT EDİLMİŞ 3 seçenekten
  (klasik_siyah/sade/mor) isgpratik'teki GERÇEK 4 seçeneğe değiştirildi:
  `sade` (çerçevesiz), `mavi_kose` (mavi çift çizgi + 4 köşede dolu kare
  vurgu — `position:absolute` ile), `altin_susleme` (yuvarlak köşeli altın
  çift çizgi — `border-radius`), `gri_cizgi` (gri çift çizgi). Bu, [[resmi-
  belge-gercek-sablon-kullan]] dersinin PDF çerçevesi için de uygulanması.
  **Tek sayfaya sığdırma:** font boyutları küçültüldü (gövde 9.5px, madde
  listesi 8.5px, künye 9px) ve "özel" (tek bloklu) sertifika tiplerinin
  (Yükseklik/Kapalı Alan 18 madde/Yangın) madde listesi de artık "genel"
  tip gibi 2 sütuna bölünüyor (hem yer tasarrufu hem görsel denge).
  `Barryvdh\DomPDF`'in `getCanvas()->get_page_count()` API'siyle 4 tip ×
  4 çerçeve = 16 kombinasyon en kötü senaryo içerikle (çok tehlikeli
  sınıf, uzun firma/kişi adları, sektörlü işyerine özgü riskler) test
  edilip HEPSİNİN tek sayfaya sığdığı doğrulandı.
  `SertifikaOlusturTest`'e 5 yeni test (4 çerçeve config + `#[DataProvider]`
  ile 4 kombinasyon tek-sayfa testi). **367 test toplam.**
- **Sertifika — "Yıldız Grup" gerçek Excel şablonu ✅ (kullanıcı kendi
  Desktop'ından `Yeni klasör\eğitim Sertifikası.xlsx` paylaştı — GERÇEK bir
  OSGB'nin ("Yıldız Grup Ortak Sağlık ve Güvenlik Birimleri Ltd. Şti.")
  kullandığı dolu örnek sertifika, isgpratik'le hiç ilgisi yok):** [[resmi-
  belge-gercek-sablon-kullan]] dersi burada da uygulandı — dosya
  `resources/belge/sertifika-yildiz-grup.xlsx` olarak BİREBİR kopyalandı,
  yeni `App\Support\SertifikaYildizGrupUretici` PhpSpreadsheet ile şablonu
  açıp yalnız belirli hücreleri dolduruyor (`AcilDurumWordUretici`'nin
  docx'teki "birebir şablon" felsefesinin xlsx karşılığı).
  **Doğrulanan çarpıcı bulgu:** şablonun "Çıktı Sayfası"ndaki 1-3 kategori
  satır sayıları (Genel Konular=4 satır, Sağlık=5 satır, Teknik=12 satır)
  `config isg.egitim`'deki madde sayılarıyla BİREBİR aynı çıktı — ikisi de
  aynı resmi Ek-1 standardından geldiği için mükemmel hücre eşleşmesi
  sağladı (kategori başına madde metinleri de birebir aynı çıktı, örn.
  "Çalışma mevzuatı ile ilgili bilgiler"). 4. kategori (İşe Özgü Riskler)
  şablonda 14 satırlık boş alan bırakıyor; sektörün 5 maddesi doldurulup
  kalan 9 satır TEMİZLENİYOR (silinmiyor — satır silme, birleştirilmiş
  hücreleri/sonraki imza bloğunu kaydırma riski taşırdı).
  Madde metinleri şablonda "a)"/"b)"... harfi KALIN, geri kalanı normal
  RichText run'ları olarak saklı — `PhpOffice\PhpSpreadsheet\RichText\
  RichText` ile aynı biçim korunarak yeniden oluşturuluyor (düz string
  yazılsaydı kalın harf biçimi kaybolurdu).
  Eğitim türü ("İlk Defa"/"Tekrar") ve şekli ("Uzaktan"/"Yüz Yüze") seçimi
  şablonda TEXT DEĞİL, küçük bir onay işareti GÖRSELİ ile gösteriliyor —
  bu görseller silinip yeniden çizilmedi, `Drawing::setCoordinates()` ile
  seçilen satıra TAŞINDI (şablonun mekanizması aynen korunmuş oldu).
  Yalnız `tip='isg'` (4 kategorili genel içerik) ile uyumlu — diğer 3 tip
  bu şablona sığmadığından buton onlarda gizli. Çoklu katılımcı için
  ZIP indirme (isgpratik'in "ZIP İndir" düğmesiyle aynı fikir). Kaşe
  görselleri G25/K25 hücrelerine yeni `Drawing` olarak ekleniyor.
  `SertifikaOlusturTest`'e 6 test. **373 test toplam.**
  **Ek düzeltme (kullanıcı: "seçilen seçenekleri işaretle"):** Eğitim
  Türü/Şekli için "seçileni gizle mi, ikisini de gösterip kutucuk mu
  ekle" tercihi kullanıcıya soruldu, "sen seç" dedi — şablon formatını
  bozmama ilkesiyle tutarlı, DAHA AZ invaziv seçenek seçildi: her iki
  seçenek de metin olarak görünmeye devam ediyor (satır silinmiyor),
  yalnız seçili olanın yazısı KALIN yapılıyor (mevcut onay işareti
  görselinin taşınmasına ek olarak — iki işaret üst üste, tek bakışta
  belli). `SertifikaOlusturTest`'e 1 test daha. **374 test toplam.**
  **Düzeltme (kullanıcı: "onay için koyduğun buton yazının karşında
  olmuyor... çarpı koyarak daha belirgin yapabilirsin"):** Şablonun kendi
  onay işareti görselini (Resim 4/5) hedef satıra taşıma yaklaşımı hatalı
  çıktı — hedef satırda (I18/I21) görsele karşılık gelen bir kutucuk
  bulunmadığından görsel metinle hizasız duruyordu. Görseller artık
  taşınmıyor, TAMAMEN KALDIRILIYOR (`Drawing::setWorksheet(null, true)` —
  koleksiyon üzerinde dönerken silmek yerine önce toplanıp sonra
  kaldırıldı, aksi halde ArrayObject iterasyonu bozuluyordu) ve yerine
  güvenilir bir metin işareti kondu: seçili seçeneğin yanındaki (I sütunu)
  hücreye kalın siyah büyük punto "X" yazılıyor, kalın metinle birlikte
  çift katmanlı net bir işaret oluşuyor. **374 test toplam** (yeni test
  eklenmedi, mevcut test genişletildi).

## Profilim tamamlandı + İSG Profesyonelleri toplu atama (Faz 5)

Faz 4 (tasarım cilası) sürerken kullanıcı Profilim panelindeki eksik işleri
tamamlamaya yöneldi — isgpratik'te var olup mehse'de hiç kurulmamış son
sekmeler + İSG Profesyonelleri'ne toplu atama. Bu fazın sonunda Profilim
isgpratik'in **11 sekmesinin tamamına** sahip. **429 test toplam** (374 →
429, bu fazda 55 yeni test).

- **İSG Profesyonelleri — toplu firma atama ✅:** `IsgProfesyoneliResource`
  tablosuna "Firmalara Ata" satır aksiyonu — çoklu-seçim Select (mevcut
  atamalar önceden işaretli), kaydedince seçilenlere `IsgProfesyoneli::
  firmaAlani()` (tip'e göre `igu_id`/`isyeri_hekimi_id`/`dsp_id`) ile atar,
  işareti kaldırılanlardan siler. Artık her firmayı tek tek açıp atama
  yapmaya gerek yok.
- **Eğitim Kayıtları + Evrak Takip toplu Excel yükleme ✅ (isgpratik
  profilim 1-16.jpg):** Profilim'e `egitimler`/`evrak_takip` sekmeleri
  eklendi. `EgitimKaydi`/`EvrakKaydi` (tarih kayıtları) + `EgitimKayitExcelIceAktarici`/
  `EvrakKayitExcelIceAktarici` (mevcut `CalisanExcelIceAktarici` deseniyle
  birebir — şablon indir/toplu yükle, Türkçe-duyarsız başlık eşleme).
  **Kritik keşif:** `kontrol_merkezi.kriterler`'deki `hazir` bayrağı
  "gereksinim karşılandı" değil "mehse'de gerçek modül var" demek
  (`test_kriterler_hazir_olmayanlar_sifir_doner` + Kontrol Merkezi'nin
  "(modül yakında)" etiketiyle sabit) — bu yüzden manuel evrak verisi
  `hazir`'i DEĞİŞTİRMEDİ, yalnız Firma Takip'in hücre görünümünü besledi
  (`manuel_anahtar` alanı, `PortfoyKarne::firmaKriterKarsilarMi`); oran/yüzde
  hesabı hâlâ yalnız gerçek modüllere göre.
- **Eğitimler sadeleştirildi ✅:** kullanıcı 14 maddelik listeyi fazla buldu
  ("sadece temel iş sağlığı ve güvenliği... ilerde konu eklemek için buton
  bırak") — `EgitimTuru` (kullanıcı bazlı) yalnız 1 varsayılan madde
  ("Temel İş Sağlığı ve Güvenliği Eğitimi") ile başlıyor, "Konu Ekle"
  (hazır katalogdan seç veya özel yaz) + × ile kaldır. Kaldırma yalnız
  takip listesinden çıkarır, geçmiş `EgitimKaydi` silinmez.
- **Evrak Takip → OSGB Takip ✅:** isim değişti, 7 varsayılan maddeyle
  (İSG/İY Sözleşmesi, Yıllık Çalışma Planı, Yıllık Değerlendirme Raporu,
  Eğitim Katılım Formu, Kurul Toplantıları, Tespit Öneri Defteri)
  başlayan, `EvrakTuru` ile aynı desende ekle/kaldır'lı bir listeye
  dönüştü. Firma Takip'e de AYNI "Evrak Türü Ekle" eklendi — iki sekme tek
  `EvrakTuru` tablosunu paylaşır (`PortfoyKarne::firmaTakipKriterleri` config
  kriterleri + kullanıcının config'te karşılığı olmayan özel türlerini birleştirir).
- **Pazarlama / Arşiv / Raporlar / Firma Ziyaretleri ✅ (isgpratik
  `SİSTEME YÜKLENENLER/143-147.jpg`):** `BEKLEYEN_SEKMELER` const'u ve
  "Diğer" sekmesi kaldırıldı.
  - **Pazarlama:** `AdayFirma` (aday/teklif/kazanıldı/kaybedildi), "Kazanıldı"
    için `CreateFirma::fillForm()`'a `session()->pull()` + `$this->form->
    fillPartially()` ile ad/telefon/il aktaran dönüştürme kısayolu
    (`fillPartially` diğer alan varsayılanlarını bozmuyor — düz `fill()`
    kullansaydı bozardı).
  - **Arşiv:** `ArsivDosya`, firma başına düz dosya listesi (klasör yok).
    **Kendi bulduğum güvenlik açığı:** `arsivSeciliFirmaId` public Livewire
    property'si tek noktada (`arsivFirmaSec`) sahiplik kontrolünden geçse
    de istemci property'yi doğrudan değiştirebilir — dosya listeleme VE
    yükleme action'larının ikisine de AYRICA sahiplik kontrolü eklendi.
  - **Raporlar (en değerli mimari keşif):** mehse'deki 22 belge üretici
    sınıfı zaten aynı statik imzaya sahipti (`Uretici::pdf(Model $kayit):
    StreamedResponse`) — bu yüzden isgpratik'teki gibi ayrı bir dosya
    arşivi KURULMADAN, `App\Support\RaporKayitlari` + `config
    isg.raporlar.kaynaklar` (model + üretici eşleşmesi) ile TEK listede
    birleşti; "İndir" ilgili üreticiyi anlık çağırır. Yeni modül eklenince
    tek satır config yeter. **Ders:** config'e davranış (closure) YAZMA —
    `config:cache` ile `var_export` çöker; ikincil format uygunluk kontrolü
    `RaporKayitlari::ikincilUygunMu()`'da sabit `match()` ile yapıldı.
  - **Firma Ziyaretleri:** yeni tablo yok, `ZiyaretProgrami`'nin tarihli ay
    satırlarından türetilen salt-okunur takvim (`ZiyaretTakvimi`).

## Risk Analizi PDF: sütun ayrımı + her sayfa başlık/altbilgi/sayfa no (Faz 6 devamı)

Kullanıcı **440 test** aşamasında tabloyla ilgili ince ayar istedi: "Öneri
Sorumlu Termin"i ayrı sütunlara böl, "Düzey" kutusunu daralt ve yazıyı dikey
yap; ayrıca her sayfada tablo başlığının ve İşveren/İGU/Hekim/Temsilci/Destek
imza şeridinin tekrarlanmasını, sayfa numarası ve kapakta toplam sayfa
sayısını istedi.
- `pdf/risk-degerlendirmesi.blade.php`: Öneri/Sorumlu/Termin 3 ayrı `<th>`/`<td>`
  oldu. Düzey/Son Düzey hücreleri `.duzey-dikey` (`transform: rotate(-90deg)`,
  dar `.col-duzey` sütun) ile dikey yazıya çevrildi — dompdf'in resmen
  desteklediği `transform` özelliği kullanıldı (`writing-mode` DEĞİL, o
  dompdf'te güvenilir değil). Risk tablosu `<thead>`/`<tbody>`'ye ayrıldı —
  dompdf `<thead>`'i her sayfanın başında otomatik tekrarlıyor (native özellik,
  ekstra kod gerekmedi). Yeni `@page { margin: ... 78px ... }` + `position:
  fixed; bottom:-68px` alt bilgi şeridi (dompdf'in resmi header/footer
  tekniği) — 5 kutu: İşveren/Vekili (firma alanı), İGU (gerçek kaşe+imza,
  mevcut "kaşe deseni"), İşyeri Hekimi (firmaya atanmış `IsgProfesyoneli`nin
  kaşesi — `Firma::isyeriHekimi` ilk kez risk PDF'inde kullanıldı), Çalışan
  Temsilcisi/Destek Elemanı (`$rd->ekip`'te unvanında "temsilci"/"destek"
  geçen üye, Uretici'de heuristik eşleşme).
- Sayfa numarası + kapakta toplam sayfa: dompdf'in `Canvas::page_script()`
  API'si (render() SONRASI, output() ÖNCESİ çağrılır — sıra önemli, aksi halde
  sayfa sayısı henüz bilinmez). `{PAGE_NUM}`/`{PAGE_COUNT}` metnini HTML'e
  yazmak İŞE YARAMAZ (dompdf'in o path'i `Renderer/Text.php`'de yorum
  satırına alınmış/pasif) — gerçek yöntem PHP tarafından `$canvas->
  page_script(fn($pageNumber,$pageCount) => ...)` ile `$canvas->text()`
  çağırmak. Bu projede İLK sayfa numaralandırma kullanımı; ileride başka
  çok sayfalı PDF'lerde (Saha Denetimi, İş Kazası vb.) aynı desen kopyalanabilir.
- **Görsel doğrulama kısıtı:** bu makinede poppler/pdftoppm, imagemagick,
  Python+PyMuPDF YOK; headless Chrome de PDF'i screenshot'ta boş/siyah
  veriyor (bilinen bir headless Chrome kısıtı — PDF viewer plugin başlıksız
  modda güvenilir çalışmıyor). PDF gerçekten üretilip test edildi (440 test
  yeşil, hatasız render+page_script+output) ama piksel bazında konum/boyut
  kullanıcı gerçek PDF'i açana kadar doğrulanamadı — ince ayar gerekebilir.

## Risk Analizi: Kapak → Prosedür → Form + Risk Prosedürleri kütüphanesi (Faz 6)

Kullanıcı "risk analizi sayfasını düzenlemeye geldi, bitire bitire gidelim"
dedi ve `C:\Users\mozde\Desktop\isgpratik\RİSK ANALİZİ` klasöründeki GERÇEK
belgeleri verdi: bir prosedür `.docx` (Matris Yöntemi), bir Fine-Kinney
prosedür+rapor `.xlsx` (gerçek bir inşaat firmasının 150+ maddelik risk
tablosu dahil) ve bir kapak `.pptx`. **440 test toplam.** Kullanıcı işin
ortasında dışarı çıkıp "sen devam et, dönünce sorduğun seçenekleri
cevaplarım" dediği için kalan tasarım kararları (prosedür kütüphanesinin
şekli, yükleme akışı) kendi takdirimle verildi — kullanıcı dönünce gözden
geçirmesi gerekebilir.
- **`App\Models\RiskProsedur`** (yeni tablo, kullanıcı+yöntem başına tek
  kayıt): `varsayilanlariSeedEt()` kullanıcı hiç prosedürü yoksa Matris ve
  Fine-Kinney için GERÇEK referans metinle (docx/xlsx'ten satır satır
  alınan AMAÇ/KAPSAM/TANIMLAR/UYGULAMA/DÖKÜMANTASYON) başlatır — uydurma
  metin YOK, [[resmi-belge-gercek-sablon-kullan]] kuralı burada da geçerli.
  `App\Filament\Resources\RiskProsedurs\RiskProsedurResource` ("Risk
  Prosedürleri" — Risk Yönetimi menüsü): liste + "Prosedür Yükle" (kendi
  .docx'ini yükleyip bir yöntemin prosedürünü DEĞİŞTİRİR) + içerik görüntüle
  + sil. `App\Support\RiskProsedurDocxOkuyucu` PhpWord ile metni
  paragraf/başlık olarak ayrıştırır (sezgi: kısa + tamamen büyük harfli
  satır = başlık — gerçek referans docx'te AMAÇ/KAPSAM/5.1 gibi başlıkları
  doğru yakaladığı test edildi).
- **PDF sırası değişti:** kullanıcı netleştirdi — "kapak, seçilen prosedür,
  form sırasına göre çıkacak". `RiskDegerlendirmesiUretici::pdf()` artık
  `RiskProsedur::aktifIcin($userId, $rd->yontem)`'i view'a geçiriyor;
  `pdf.risk-degerlendirmesi.blade.php`'de Kapak'tan hemen sonra, Künye/
  Metodoloji/Risk Tablosu'ndan (Form) önce yeni bir prosedür sayfası
  eklendi. Kapağa da eksik olan SGK Sicil No + NACE Kodu eklendi (gerçek
  pptx kapak şablonunda vardı, mehse'de yoktu — bu, kullanıcının "verdiğim
  adrese göre kontrol et eksik varsa düzelt" isteğiyle bulunan gerçek bir
  eksiklikti).
- **Kontrol edildi, EKSİK ÇIKMADI:** kullanıcı "inşaat için yüklediğim
  Excel'i aynı sektör geldiğinde kullanalım, yeni yüklenecekse kütüphaneye
  ekle" dedi — bu tam olarak zaten var olan `RiskSablonu` + Risk
  Sihirbazı'nın "Şablonlar & Paylaşılanlar" (sektöre göre gruplu, tek
  tıkla uygulama) ve "Excel'den Sektörel Şablon Oluştur" (kendi Excel'ini
  sektöre etiketleyip kütüphaneye ekleme, `ListRiskSablonus`) akışlarıydı.
  Yeniden inşa EDİLMEDİ — mevcut olanın gerçek referans dosyayla (fine-kinney
  inşaat Excel'i) uyumlu çalıştığı kod okumasıyla doğrulandı.
- **Ders:** kullanıcı bir özelliği "eksik" sanıp isteyebilir ama bazen
  zaten var olur — yeniden yazmadan önce mevcut kodu (bu örnekte
  `RiskSihirbazi::ADIMLAR`/`YONTEMLER` ve `ListRiskSablonus`) MUTLAKA
  kontrol et; gereksiz yeniden inşa hem riskli hem israf.

## Risk Analizi PDF: bant adı, dikey Düzey hizalama düzeltmesi, No sütunu, toplam sayfa (Faz 6 devamı, 442 test)

Kullanıcı 440 test sonrası üç ince ayar daha istedi: en üst risk bandının adını
değiştir, dikey "Düzey" yazısının satırla hizasız kaydığı hatayı düzelt, risk
tablosuna sıra numarası ekle, kapaktaki toplam sayfa sayısını "Hazırlayan"ın
hemen altına konumlandır.
- **Bant adı:** `config('isg.risk_matris_5x5.bantlar')` ve
  `risk_fine_kinney.bantlar`'daki en üst bant `'Tolerans Gösterilemez Risk'` →
  `'Çok Yüksek Risk'` olarak değiştirildi (her iki yöntemde de, testte de).
- **Dikey Düzey hizalama hatası kök nedeni ve düzeltmesi:** önceki turda
  kullanılan `transform: rotate(-90deg)` dompdf'te kutuyu KENDİ döndürülmemiş
  doğal genişliğine göre ortalıyor; bu genişlik yazının uzunluğuna göre
  satırdan satıra değiştiği için döndürülmüş metin kendi hücresinin satırına
  göre kayıyordu (bant adı ne kadar uzunsa kayma o kadar büyüktü). Çözüm:
  `transform` tamamen terk edilip harf harf alt alta yazma tekniğine
  geçildi — `RiskMaddesi::duzeyDikey()`/`sonDuzeyDikey()` yeni metodları
  `mb_str_split()` ile Türkçe karakterleri bozmadan tek tek ayırıp `"\n"`
  ile birleştiriyor, blade'de `white-space: pre-line` ile satır satır
  gösteriliyor. Bu teknik normal tablo akışına (hücre otomatik yüksekliği)
  dayandığı için satırla hizası garanti — transform gibi ek bir konumlandırma
  katmanı yok. `.col-duzey` ayrıca 18px'ten 11px'e daraltıldı.
  **Ders:** dompdf'te `transform: rotate()` resmen destekleniyor olması onu
  tablo hücresi içinde güvenilir kılmıyor — döndürme merkezi öncesi kutunun
  doğal boyutuna bağlı olduğunda içerik uzunluğu değiştikçe kayma birikir;
  tablo satırıyla kesin hizalanması gereken dikey metin için karakter
  yığınlama daha sağlam.
- **No sütunu:** risk tablosuna en sola `.col-no` (16px) eklendi, değeri
  `{{ $loop->iteration }}` — 1, 2, 3 diye artan sıra numarası.
- **Kapakta toplam sayfa, "Hazırlayan"ın altında:** önceki turda mutlak
  canvas koordinatı (`$canvas->text(360, 30, ...)`) tahminiyle konumlanmıştı;
  bu, "Hazırlayan" satırının gerçek render pozisyonundan bağımsızdı ve
  kullanıcının net isteğiyle ("Hazırlayan'ın ALTINDA") yetersiz kaldığı
  ortaya çıktı. Çözüm: **iki geçişli render** — `RiskDegerlendirmesiUretici::
  pdf()` önce `toplamSayfa=null` ile bir kez render edip `$dompdf->
  getCanvas()->get_page_count()` ile sayfa sayısını öğreniyor, sonra bu
  değeri normal view verisi olarak geçirip İKİNCİ kez render ediyor — böylece
  "Toplam Sayfa: N" normal HTML akışında (`Hazırlayan` div'inin hemen
  altında) CSS ile konumlanıyor, koordinat tahmini gerekmiyor.
  **Ders:** bir değerin HTML akışında belirli bir öğeye göre kesin
  konumlanması gerektiğinde (mutlak canvas koordinatı değil), ve o değer
  ancak render sonrası bilinebiliyorsa (sayfa sayısı gibi), iki geçişli
  render dompdf'te güvenilir ve basit bir çözüm — piksel tahmini riskini
  tamamen ortadan kaldırıyor.
- Sayfa numarası mantığı da sadeleşti: `page_script` içinde `$pageNumber===1`
  ise (kapak) hiç yazmıyor, 2. sayfadan itibaren `"Sayfa N / Toplam"` yazmaya
  devam ediyor — toplam sayı kapak dahil tüm sayfaları sayıyor.
- Tam paket: **442 test, 1215 assertion, hatasız.**
- **Hâlâ görsel doğrulama YOK** (bkz. bir önceki fazın notu — poppler/
  imagemagick/Python yok, headless Chrome PDF screenshot'ı boş veriyor);
  kullanıcı gerçek PDF'i açtığında piksel bazlı ince ayar gerekebilir.

## Risk Analizi PDF: sayfa no konumu, Düzey kutusu tam boyama, önlem sonrası O/Ş sütunları (Faz 6 devamı, 443 test)

Kullanıcı gerçek PDF çıktısını inceledikten sonra üç somut hata/istek bildirdi:
sayfa numarası tablonun üstüne biniyor; dikey "Düzey" kutusu yalnız yazı
kadar boyanıyor (satırın ortasında değil); "Termin"in yanına önlem sonrası
Olasılık/Şiddet sütunu açılsın ve bunlar (girilmemişse) olasılık=1,
şiddet=öneri öncesiyle aynı varsayılıp çarpımdan Son Puan/Son Düzey
hesaplansın.
- **Sayfa no üst-marjdan alt-marja taşındı:** eski konum `$canvas->
  text(500, 15, ...)` üst marja (25px) çok yakındı ve her sayfanın başında
  tekrarlayan tablo başlığıyla (`<thead>`) çakışıyordu. Yeni konum alt
  marjda, tablo bittikten SONRA imza şeridinden ÖNCEki boşlukta: `.sayfa-alt`
  artık `bottom:-78px` (önceki `-68px` yerine, @page alt marjıyla `78px`
  birebir aynı) — şerit sayfanın gerçek alt kenarına dayanıyor, böylece
  içerik kutusunun bittiği yer ile şeridin başladığı yer arasında sayfa
  numarası için güvenli bir bant açılıyor. Koordinat `$canvas->get_width()`/
  `get_height()`'tan TÜRETİLİYOR (`width-90, height-64`), sabit sayı tahmini
  yerine. **Ders:** dompdf canvas'ına mutlak koordinatla yazı basarken, o
  koordinatın hangi CSS kutusuna (marj/içerik) denk geldiğini varsaymak
  yerine, marj değerleriyle TUTARLI/türetilmiş bir hesap kullan — "üstte 15px
  boşluk var sanırım" gibi bir tahmin gerçek dompdf sayfalama davranışında
  yanlış çıkabiliyor (kullanıcının gerçek PDF'i açmasıyla ortaya çıktı).
- **Düzey kutusu artık TAMAMEN boyanıyor:** renk artık iç `<span>`e değil,
  `<td class="col-duzey">`nin kendisine `style="background:..."` olarak
  uygulanıyor; `.col-duzey`e `vertical-align:middle` eklendi (tablo hücresi
  özgü — 2 class'lı seçici `table.risk td`nin `vertical-align:top`'unu
  spesifiklikle eziyor) — böylece harf yığını satırın YÜKSEKLİĞİ ne olursa
  olsun (yanındaki uzun Tehlike/Risk metni satırı ne kadar uzatırsa
  uzatsın) dikey ortada kalıyor, kutunun tamamı renkli.
- **Önlem sonrası Olasılık/Şiddet sütunları + otomatik varsayılan:**
  `RiskMaddesi`'nin `saving` hook'una, `son_olasilik`/`son_siddet`/
  (Fine-Kinney'de) `son_frekans` NULL ise varsayılan atanması eklendi:
  olasılık **1** (önlem genelde olasılığı düşürür), şiddet ve frekans
  **öneri ÖNCESİNDEKİ değerle aynı** (önlem şiddeti değil olasılığı azaltır
  varsayımı) — kullanıcı elle farklı bir değer girerse dokunulmuz. Risk
  tablosuna Termin'in hemen sağına iki yeni `<th>O</th><th>Ş</th>` sütunu
  eklendi (Son Puan/Son Düzey'den önce) — artık her zaman gerçek bir
  olasılık×şiddet çarpımından Son Puan/Son Düzey hesaplanıyor, "—" boş
  durumu yalnız hiç mevcut olasılık/şiddet girilmemiş (tamamlanmamış) bir
  maddede kalıyor. Filament formunda da "Önlem sonrası Olasılık" alanına
  `->default(1)` + her iki alana açıklayıcı `helperText` eklendi.
- Tam paket: **443 test, 1224 assertion, hatasız** (bir önceki turdaki
  `test_risk_maddesi_duzey_dikey_harf_harf_alt_alta_yazar` testinin eski
  "son_duzey boşsa null" beklentisi artık YENİ varsayılan davranışa göre
  güncellendi — kasıtlı davranış değişikliği, regresyon değil).

## Durum — 2026-09-04/05 (Kontrol Merkezi / Firma Takip mantık hataları + tasarım güncellemesi Md.1-4)

**443 test (değişmedi — sadece davranış/görsel düzeltmeleri, yeni özellik yok).**
Kullanıcı "sistemdeki mantık hatalarını test et, eksik gördüğün yerleri düzelt"
dedi; ardından ayrı bir turda "Gemini'nin ürettiği görsele göre Kontrol Merkezi'ni
güncelle" + tasarım Md.1-4 (renk paleti, tipografi, buton, gölge) onaylanıp
gerçek panele işlendi.

- **`PortfoyKarne::gercekModulVarMi()` — 6 gerçek mantık hatası bulundu ve
  düzeltildi:**
  1. `kontrol_merkezi.kriterler`'in 13 kriteri `hazir=false` kalmıştı (gerçek
     modülü olduğu HALDE) — `EgitimKatilim`, `TatbikatTutanagi`,
     `KurulToplantisi`, `IsIzinFormu`, `SahaDenetimi`, `IsKazasiRaporu`,
     `MuayeneFormu`, `Firma.igu_id`/`isyeri_hekimi_id`, `YillikPlan` (3
     kriter), `TespitOneriDefteri` artık gerçek modüle bakıyor.
  2. `gercekModulVarMi()===false` durumunda eski manuel `EvrakKaydi`
     kaydını YOK SAYAN bir `??`/null-coalescing hatası vardı (false, null
     DEĞİLDİR) — `=== true` kontrolüne çevrildi, artık "gerçek modül VEYA
     manuel kayıt" ikisinden biri yeterli (geçmiş manuel veri kaybolmuyor).
  3. `YillikPlan` sınıfı `use` edilmeden kullanılmıştı — yanlış namespace
     (`App\Support\YillikPlan`) çözülüp fatal hataya yol açıyordu.
  4. **35 firma vs 34 firma çelişkisi:** `Firmalar` nav rozeti `aktif=true`
     filtreliydi, Profilim'in TÜM hesapları (`ozet`, `kriterler`,
     `firmaKriterMatrisi`, `profilOzeti`, `calisanDagilimi`) filtrelemiyordu
     — 6 yerde `->where('aktif', true)` eklendi.
  5. `isg_kurulu` kriterinin 50-altı-çalışan muafiyeti yalnız `kriterler()`/
     `ozet()`'te uygulanıyordu, `firmaKriterMatrisi()`/`firmaTamUyumluMu()`
     bunu atlıyordu — küçük firmalar hem "Oran" hem "tam uyumlu" sayımında
     haksız yere cezalandırılıyordu. İkisine de aynı `$muaf` kontrolü eklendi.
  **Ders:** "aynı hesap birden fazla yerde tekrarlanıyorsa, bir yerde
  düzeltilen bir kural (muafiyet, filtre) diğer kopyalarda da UYGULANMALI" —
  bu oturumda 2 kez (aktif filtresi, isg_kurulu muafiyeti) aynı sınıftan hata
  bulundu; tek bir yerde düzeltip diğerini unutmak kolay.
- **"OSGB Takip" TAMAMEN KALDIRILDI (05.09.2026, aynı gün ikinci tur).**
  Önceki turda kullanıcı bu sekmeyi kaldırmak istemiş ama net onay
  vermemişti; bu turda netleşti: sekme + altındaki TÜM manuel evrak
  mekanizması (Excel toplu yükleme, "Evrak Türü Ekle" özel kriter ekleme,
  Firma Takip'teki "açık ✓" manuel-işaret gösterimi) tamamen gitti — sadece
  ayrı sekme görünümü değil, mekanizmanın kendisi.
  - Silinen: `App\Models\EvrakTuru`/`EvrakKaydi`, `App\Support\
    EvrakKayitExcelIceAktarici`, ilgili 2 migration (dev DB'de tablo
    boştu — 0 `evrak_kayitlari`, 8 `evrak_turleri` satırı, veri kaybı yok;
    tablolar `Schema::dropIfExists` ile elle düşürüldü, migration
    kayıtları da temizlendi — `migrate:fresh` KULLANILMADI).
    `Firma::evrakKayitlari()` ilişkisi, `Profilim`'in evrak* metodları/
    action'ları, blade'deki OSGB Takip bloğu, config'teki `manuel_anahtar`
    alanları hep birlikte kaldırıldı.
  - `PortfoyKarne::firmaKriterKarsilarMi()` artık SADECE
    `gercekModulVarMi()`'ye bakıyor (manuel fallback yok);
    `firmaTakipKriterleri()` artık salt config listesini döner (özel/ozel
    sütun kavramı gitti); `firmaKriterMatrisi()`'nin `evrakKayitlari` eager
    load'u ve manuel fallback dalı kaldırıldı. Firma Takip artık YALNIZ
    gerçek modüllere göre ✓/✗ gösterir.
  - 443 → **426 test** (17 test kaldırıldı: `EvrakKayitExcelIceAktariciTest`
    dosyası tamamen + `ProfilimTest`'teki 7 evrak-özel test), kalan hepsi
    geçiyor.
- **Tasarım Md.1-4 (AskUserQuestion'sız, kullanıcı Gemini görseline
  referansla onayladı) `AdminPanelProvider`'a işlendi:**
  - Md.1: özel `gray` paleti (`GrayVioletTint` — Gray'in lightness/chroma
    eğrisi aynı, hue Violet ailesine ~290° kaydırılmış) + `profilim.blade.php`/
    `kontrol-merkezi.blade.php`'deki 70+ düz gri (`107 114 128`) referansı
    `128 116 148`'e çevrildi.
  - Md.2: `->font('Public Sans', GoogleFontProvider)` +
    `->monoFont('JetBrains Mono', ...)`; başlıklarda Archivo (head render-hook
    ile CSS override, Filament'ın `->font()` API'sinde ayrı "başlık fontu"
    slotu yok).
  - Md.3: Filament'ın varsayılan dolu/solid buton stili zaten uygundu, ekstra
    değişiklik gerekmedi.
  - Md.4: seçici gölge (`$golge` — KÜNYE kartı + sayaç kartları, DİĞER
    kutular bilinçli düz kaldı) + turkuaz "seçili sekme" vurgusu
    (`$mor`→`$turkuaz`, profilim + kontrol-merkezi aktif tab pili).
  - **`->defaultThemeMode(ThemeMode::Light)`** — panel varsayılanı koyudan
    açığa çevrildi. **Kritik keşif:** Filament'ın `dark-mode.js`'i
    `localStorage.getItem('theme') ?? --default-theme-mode` sırasıyla karar
    veriyor — tarayıcıda ÖNCEDEN kayıtlı "dark" tercihi sunucu ayarını HER
    ZAMAN eziyor. `HEAD_START` render-hook'una (Alpine başlamadan ÖNCE
    çalışır) tek seferlik bir migration script eklendi: localStorage'da
    "theme" yoksa/daha önce migrate edilmemişse "light" yazıp bir damga
    bırakıyor, sonra hiç karışmıyor. **Çözüldü (05.09.2026, ikinci tur):**
    kullanıcı yeni bir gizli/InPrivate pencerede kontrol etti, açık tema
    doğru görünüyor — kod hiç bug'lı değilmiş. Kök neden: Filament'ın
    `wire:navigate` SPA-tarzı gezinmesi `<head>`'i yeniden yüklemiyor,
    kullanıcı deploy sonrası ESKİ açık sekmede gezinmeye devam ettiği için
    yeni migration script'i o sekmede hiç çalışmamış. **Ders: "tema/JS
    değişikliği görünmüyor" şikayetinde önce kullanıcının YENİ sekme/
    tam sayfa yenileme yapıp yapmadığını sor — Filament panelleri SPA gibi
    gezindiğinden `<head>` içeriği eski kalabilir.**
  - Kontrol Merkezi "İSG Portföy Özeti" kartları görsel olarak yenilendi:
    düz sayı yerine dolu-renk fayans + ikon rozeti + `conic-gradient`+`mask`
    ile tema-bağımsız (panel bg rengini tahmin etmeden) uyum yüzdesi halkası;
    21 kriter kartına renkli daire ikon rozeti eklendi.
  **Ders (tekrar karşılaşılabilir):** Filament panel `colors()`/tema
  ayarları SUNUCU tarafında anında canlıdır (config cache yok, `--default-
  theme-mode` CSS değişkeni her istekte `filament()->getDefaultThemeMode()
  ->value`'dan türetiliyor) — "tema değişikliği görünmüyor" şikayetinde önce
  tarayıcı `localStorage`'ını (client-side persisted override) şüphelen,
  sunucu/kod tarafını suçlamadan önce.
- **Bu oturumda AYRICA, mehse'yle İLGİSİZ 3 ayrı Fine-Kinney risk
  değerlendirmesi hazırlandı** (kullanıcının açık isteğiyle: "bu programla
  ilgili değil") — Rüzgar Enerjisi Kurulum/Bakım/Lojistik (54 madde, verilen
  6 PDF'ten), Yer Altı/Havai Hat Kablo Çekimi + Elektrik Bakımı (36 madde),
  Ameliyathane — Şehir Hastanesi (41 madde, formaldehit/UV/akü şarj/oksijen
  tüpü dahil). Üçü de Artifact + PhpSpreadsheet ile Excel olarak
  `Downloads/`'a teslim edildi, mehse kod tabanına HİÇ dokunmadı — karışık
  bağlamda tutmamak için MIMARI.md'ye ayrıntı yazılmadı, sadece bu not.

## Durum — 2026-09-05/06 (NACE Kod → Tehlike Sınıfı Sorgula)

**431 test** (426 → +5). `isgpratik`'te referans klasörüne eklenmiş ama henüz hiçbir
modülde karşılığı olmayan 4 ekran tarandı (95/99-100/101.jpg + "NACE Kod" tarihli
ekran görüntüsü): **Mevzuat kütüphanesi**, **Acil Durum Kroki Düzenleyici**, **MYK
Zorunluluk Sorgula**, **NACE Kod Tehlike Sınıfı Sorgula** — kullanıcı en küçük
kapsamlı olan NACE aracıyla başlanmasını seçti; diğer üçü sırada.

- **Resmi kaynak, tahmin değil:** Kullanıcının elinde NACE→tehlike sınıfı dosyası
  yoktu ("resmi şablon kullan" dedi) — mevzuatı ezbere yazmak yerine gerçek kaynak
  arandı ve bulundu: `mevzuat.gov.tr`'deki konsolide tebliğ sayfası HTML'i içinde
  `<a href="9.5.16909-Ek.xlsx">` linki 404 verdi (SPA route, gerçek dosya farklı
  yolda), ama Resmî Gazete arşivinden orijinal dosyaya doğrudan ulaşıldı:
  `resmigazete.gov.tr/eskiler/2012/12/20121226-11-1.xls` — 26/12/2012 tarihli ve
  28509 sayılı Resmî Gazete, "İş Sağlığı ve Güvenliğine İlişkin İşyeri Tehlike
  Sınıfları Tebliği" EK-1 (gerçek eski-format .xls, 3194 satır, NACE Rev.2 altılı
  kod hiyerarşisi + Tehlike Sınıfı sütunu).
  - PhpSpreadsheet ile parse edilip yalnız 6 haneli "yaprak" (`\d\d\.\d\d\.\d\d`)
    kodlar + üst tek harfli sektör başlığı (`sektor_adi`) ayıklandı →
    **2182 temiz kayıt**, `database/data/nace-kodlari.php` (kaynak yorumu dahil).
  - **Bilinçli sınırlama, MIMARI'ye not düşüldü:** bu tebliğ 2013-2026 arasında
    16 kez kısmen değiştirildi (son değişiklik 1/4/2026, 33211 sayılı RG);
    tam güncel konsolide tabloyu tek seferde indirmenin güvenilir bir yolu
    bulunamadı (mevzuat.gov.tr'nin ek dosyası 404, Resmî Gazete'nin 2025/03 PDF'i
    12MB'lık ayrı bir değişiklik tebliği — tam liste değil). Taban 2012 verisi
    kullanıldı, sayfaya görünür bir kaynak notu eklendi ("sık değişen sektörlerde
    güncel RG metniyle teyit önerilir") — sessizce "güncel" diye sunulmadı.
- `App\Models\NaceKodu` (`nace_kodlari` tablosu) — `normalizeKod()` kullanıcı
  girdisini ("01.11.14", "011114", boşluklu) noktalı 6 haneli forma çevirir,
  `bul()` ile sorgular. `NaceKoduSeeder` (`DatabaseSeeder`'a eklendi,
  `upsert` + 500'lük chunk).
- `App\Filament\Pages\Araclar`'a üçüncü bölüm: kod gir → Sorgula → tanım +
  renkli tehlike sınıfı rozeti (`FirmasTable` ile aynı renk kuralı:
  çok tehlikeli=danger/tehlikeli=warning/az tehlikeli=success). Geçersiz format
  ve listede bulunamayan kod için ayrı hata mesajları. `AraclarTest`'e 5 test
  eklendi (nokta/noktasız girdi, geçersiz format, bulunamayan kod, sıfırlama).

## Durum — 2026-09-06 (MYK Zorunluluk Sorgula)

**435 test** (431 → +4, isgpratik'te bulunan 4 yeni ekrandan ikincisi tamamlandı;
Mevzuat kütüphanesi ve Acil Durum Kroki Düzenleyici sırada).

- **Yine gerçek kaynak, tahmin yok:** isgpratik'in ekranı "158 meslek" gösteriyordu
  ama bu statik/eski bir sayı — MYK'nın kendi CANLI portalından (`portal.myk.gov.tr/
  index.php?belge_zorunlu=1&option=com_yeterlilik&view=arama`, "Belge Zorunluluğu
  Kapsamındaki Meslekler" sorgusu) doğrudan çekildi: **242 yeterlilik kodu**
  (05.09.2026 anlık görüntüsü — 23/3/2026 tarihli 33202 sayılı RG tebliğiyle 40
  meslek daha eklenmiş, haber kaynaklarında "244" deniyor; portalın kendi HTML'i
  242 benzersiz kod içeriyordu, küçük fark muhtemelen haberin yuvarlaması).
  - Sayfanın DOM yapısı: her yeterlilik kodu için 1+ REVİZYON alt satırı var
    (rowspan ile "Yeterlilik Adı"/"Kodu" tek satırda, sonraki revizyon satırları
    aynı "Belge Zorunluluk Tarihi"ni tekrarlıyor) — DOMDocument/DOMXPath ile
    grup grup ayrıştırılıp en güncel revizyonun tarihi esas alındı.
    3 kod (`11UY0036-2`, `11UY0037-2`, `12UY0062-3`) portalda tarih sütunu boş
    geldi — muhtemelen yeni eklenmiş ve zorunluluk tarihi henüz belirlenmemiş;
    veri OLDUĞU GİBİ (boş) tutuldu, tahmini tarih uydurulmadı.
  - `database/data/myk-meslekleri.php` (kaynak yorumu + çekim tarihi dahil).
- `App\Models\MykMeslek` (`myk_meslekleri` tablosu) — `ara()` meslek adında veya
  kodda kısmi eşleşme arar. `MykMeslekSeeder` (`DatabaseSeeder`'a eklendi).
- `Araclar` sayfasına dördüncü bölüm: serbest metin ara → Sorgula → aynı meslek
  adına ait TÜM yeterlilik kodları tek kartta gruplu (`groupBy('yeterlilik_adi')`,
  isgpratik'teki "Ahşap Mobilya İmalatçısı: 3 kod" görünümüyle aynı mantık) +
  Belge Zorunluluk Tarihi. Sonuç yoksa/aranmadıysa ayrı durumlar. Kaynağa canlı
  link + "MYK periyodik günceller" notu. `AraclarTest`'e 4 test eklendi.
- **Not (ileride karşılaşılabilir):** arama `LIKE` ile yapılıyor; hem sqlite
  (test) hem mehse'nin gerçek MySQL bağlantısı (`utf8mb4_unicode_ci`, `turkish_ci`
  DEĞİL) İ/I büyük-küçük dönüşümünü İngilizce kurallarla yapıyor
  (bkz. `[[turkce-buyuk-harf-donusumu]]`) — yani "kaynakçı" küçük harfle
  yazılırsa "Kaynakçı" ile HER ZAMAN eşleşeceği garanti değil. Testler kasıtlı
  olarak veride var olan büyük/küçük harfle yazıldı; case-insensitive Türkçe
  arama iddia edilmedi. Kullanıcıdan şikayet gelirse çözüm: sütun koleksiyonunu
  `utf8mb4_turkish_ci`'ye çevirmek ya da normalize edilmiş bir arama sütunu eklemek.

## Durum — 2026-09-06 (Mevzuat Kütüphanesi)

**441 test** (435 → +6: 5 `MevzuatTest` + 1 `NavigasyonTest`, isgpratik'te bulunan 4 yeni ekrandan üçüncüsü tamamlandı;
yalnız Acil Durum Kroki Düzenleyici kaldı — büyük kapsamı nedeniyle ayrı bir
oturuma bırakıldı).

- **Kapsam bilinçli olarak daraltıldı, isgpratik birebir kopyalanmadı:**
  isgpratik'in Mevzuat sayfası genel mevzuat.gov.tr tarzı bir kütüphaneydi
  (Anayasa/Kanun/Yönetmelik/Tebliğ/Rehber kategorileriyle, İSG dışı binlerce
  mevzuatı da kapsayan). NACE/MYK'nın aksine burada tek doğru bir "kaynak
  dosya" yok — bu yüzden mehse'nin MIMARI kuralı gereği ("modül ve akış taklit
  edilir, kod/marka/metin birebir kopyalanmaz") kapsam mehse'nin kendi
  modüllerinde (Risk Değerlendirme, DÖF, Ceza-Tebliğ, KKD Formu, Acil Durum
  Planı vb.) zaten dayanak olarak kullanılan ~40 kalemlik İSG'ye özgü bir
  seçkiye daraltıldı — rastgele/alakasız mevzuat (asansör, basınçlı kap vb.
  isgpratik'te vardı) eklenmedi. Bu, kullanıcıya AŞ sorulmadan alınan bir
  kapsam kararı — memnun kalınmazsa `config('isg.mevzuat.liste')` genişletilir.
  Açıklamalar genel/yönlendirici tutuldu (madde numarası iddia edilmedi),
  sayfada "resmi ve güncel metin için mevzuat.gov.tr/resmigazete.gov.tr"
  notu var.
- `config('isg.mevzuat')`: `kategoriler` (anayasa/kanun/yonetmelik/teblig/rehber)
  + `liste` (baslik/kategori/aciklama, ~40 kayıt). Ayrı veri dosyası değil,
  `config/isg.php` içinde (statik/küçük veri, NACE/MYK gibi 200+ satır değil).
- `App\Filament\Pages\Mevzuat` (`Yönetim` grubu, sort 3, slug `mevzuat`) —
  serbest metin arama (başlık+açıklamada) + kategori pilleri, Livewire
  `sonuclar()` computed metodu. `NavigasyonTest`'e eklendi, `MevzuatTest`
  5 test.

## Durum — 2026-09-06 (Acil Durum Krokisi Düzenleyici)

**451 test** (441 → +10: 9 `AcilDurumKrokisiTest` + 1 `NavigasyonTest`, isgpratik'te bulunan 4 yeni ekranın SONUNCUSU da
tamamlandı — bu turda hiç kod parçası isgpratik'ten alınmadı, akış/kavram
taklit edildi, ölçek bilinçli küçültüldü).

- **Kapsam kullanıcıyla netleştirildi (AskUserQuestion), tam isgpratik
  kopyası değil:** isgpratik'in editörü sürükle-bırak CAD motoru, tam ISO 7010
  sembol kütüphanesi ve vektörel PDF/PNG çıktısı vaat ediyordu. Kullanıcı üç
  soruda: **(1)** çizim motoru **SVG** (Canvas değil — dompdf'e vektör olarak
  daha kolay gömülür), **(2)** sembol paleti **çekirdek 8 sembol** (tam ISO
  7010 seti değil), **(3)** plan altlığı yükleme **v1'de dahil** olsun dedi.
  **Sürükleme YOK** (mehse'de hiçbir modülde raw canvas-drag JS yok, hepsi
  Livewire round-trip) — tıkla-yerleştir (sembol) / iki-tıkla-çiz (duvar) /
  listeden-sil modeli seçildi; bu, MIMARI'nin "modül ve akış taklit edilir,
  birebir kopyalanmaz" kuralına en uygun, mevcut mimariyle tutarlı yaklaşımdı.
- **Semboller resmi ISO 7010 sanatı İDDİA EDİLMEDİ:** `config('isg.kroki.
  semboller')` + `resources/views/filament/pages/partials/kroki-sembol.blade.php`
  — 8 basit şematik piktogram (çıkış oku, toplanma yeri, merdiven/kaçış yönü,
  söndürücü, hidrant, alarm butonu, ilk yardım, oda etiketi), yalnızca temel
  SVG ilkelleriyle (rect/circle/polygon/line — dompdf uyumluluğu için path/arc
  kullanılmadı). Bu partial hem editörde hem PDF'te AYNEN paylaşılıyor —
  ikisi asla birbirinden sapmaz. Sayfada açık not: "gerçek, yönetmeliğe uygun
  işaretler zaten Acil Durum Planı → Acil Durum Afişleri'nde gerçek dosya
  olarak var, bu yalnız konum şeması."
- `acil_durum_krokileri` tablosu (firma başına 1, `duvarlar`/`semboller` json)
  — `App\Models\AcilDurumKrokisi::firmaIcin()` (AcilDurumPlani ile aynı desen),
  `lejant()` sembol tipine göre gruplar.
- `App\Filament\Pages\AcilDurumKrokisi` (Risk Yönetimi, sort 6, slug
  `acil-durum-krokisi`) — SVG tuvaline Alpine `x-on:click` ile tıklanan nokta
  `getBoundingClientRect()`+`viewBox` oranıyla SVG koordinatına çevrilip
  `$wire.nokta(x,y)` çağrılır. **90°'ye kenetleme:** iki nokta arası yatay
  fark dikey farktan büyükse çizgi tam yatay, değilse tam dikey yapılır
  (`AcilDurumKrokisi::nokta()`). Plan altlığı `FileUpload` ile yüklenir (aynı
  `AcilDurumPlani`'nin "Tahliye Planı Görseli" deseni), SVG'de `<image opacity=.5>`
  olarak arkaya basılır. `App\Support\AcilDurumKrokisiUretici` (dompdf, A4 yatay)
  — editördeki AYNI `elemanlar`/partial'dan PDF üretir + lejant.
  **Bilinçli olarak yapılmayan:** mevcut "Tahliye Planı Görseli" (AcilDurumPlani)
  alanına otomatik aktarım — SVG'den rastere (PNG) sunucu tarafı dönüşüm için
  güvenilir bir araç (Imagick+librsvg vb.) doğrulanmadığından iki özellik
  bağımsız tutuldu, entegrasyon ayrı bir iş.
  `AcilDurumKrokisiTest` (9 test), `NavigasyonTest`'e eklendi.
  **Kalan (ileride):** eleman sürükleyerek taşıma (şu an sil+yeniden ekle),
  daha geniş ISO 7010 seti, döndürme (rotasyon) desteği.

## Durum — 2026-09-06 (Kayıtlı Risklerim — klasörlü görünüm)

**452 test** (451 → +1). Faz 3a'dan beri açık duran "Kalan" kalemlerden ilki
kapatıldı: kullanıcı 4 kalemden bunu seçti (Kayıtlı Risklerim, RiskSablonu
madde düzenleme, İnşaat alt-faaliyet seçimi, sektörel Word çıktısı arasından).

- Filament v5'in kendi `Grouping\Group` özelliği kullanıldı — özel bir
  klasör/ağaç UI'ı YAZILMADI: `RiskDegerlendirmesisTable`'a
  `->groups([Group::make('firma.unvan')->label('Firma')->collapsible()])`
  + `->defaultGroup('firma.unvan')` eklendi. Sonuç: "Kayıtlı Değerlendirmeler"
  listesi artık varsayılan olarak firma başına daraltılabilir bir bölüme
  ayrılıyor (klasör hissi), kullanıcı isterse tablo üstündeki grup seçiciden
  kaldırabilir/değiştirebilir (Filament'ın standart davranışı).
  `RiskDegerlendirmeTest`'e 1 test eklendi (`getDefaultGroup()->getColumn()`
  kontrolü + 2 farklı firma kaydının ikisinin de göründüğü doğrulaması).
  **Ders:** Filament'ın yerleşik özelliği varken (`groups()`/`defaultGroup()`)
  özel klasör görünümü kodlamaya gerek yoktu — kullanıcı isteğini "kod yaz"
  değil "doğru API'yi bul" olarak çözmek daha az kod, daha az bakım demek.

## Durum — 2026-09-06 (RiskSablonu Madde Düzenleme + güvenlik düzeltmesi)

**456 test** (452 → +4). Faz 3a'nın "Kalan" listesindeki ikinci kalem kapatıldı.

- `RiskSablonuResource` form'una `Repeater::make('maddeler')` eklendi —
  bölüm/faaliyet/tehlike/risk/mevcut önlem/olasılık/(fine-kinney'de frekans)/
  şiddet/öneri/sorumlu/termin alanları, `RiskDegerlendirmesi`nin
  `MaddelerRelationManager`'ıyla AYNI `RiskSkorlama::olcek()` seçenekleri
  kullanılıyor (tutarlı O/F/Ş listeleri). O/Ş(/F) Select'leri formdaki
  `yontem` alanına (`->live()`) göre `Get('../../yontem')` ile reaktif —
  yöntem değişince ölçek de değişiyor. Repeater daraltılabilir, sıra
  düğmeleriyle yeniden sıralanabilir, varsayılan 0 öğeyle başlıyor.
- **Yan etkide bulunan güvenlik düzeltmesi:** Repeater'ı eklerken fark edildi
  — `RiskSablonuResource::getEloquentQuery()` paylaşılan şablonları HERKESE
  görünür kılıyordu (`scopeGorunur`) ama edit FORMUNUN kendisinde sahiplik
  kontrolü YOKTU (yalnız `DeleteAction`'da vardı) — yani paylaşılan bir
  şablonu açan HERHANGİ bir kullanıcı `ad`/`sektor`/`yontem`'ini de,
  şimdi eklenen madde listesini de değiştirip kaydedebilirdi. Bu, Repeater
  eklenmeden ÖNCE de var olan bir açıktı (form zaten ad/sektör/yöntem
  düzenliyordu), yeni yazma yeteneği ekleyince etkisi büyüdüğü için bu
  turda düzeltildi: `RiskSablonuResource::canEdit()` eklendi
  (`$record->user_id === Filament::auth()->id()`), Filament'ın
  `EditRecord::authorizeAccess()`'i bunu otomatik çağırıp sahip değilse
  403 döndürüyor; tablo satırındaki "Düzenle" aksiyonu da (Delete'te
  zaten olduğu gibi) otomatik gizleniyor. **Bilinçli ödün:** artık paylaşılan
  bir şablonu sahibi olmayan biri "sadece bakmak" için de açamıyor
  (ayrı bir salt-okunur View sayfası yok) — güvenlik, "önizleme" kolaylığına
  tercih edildi; önizleme ihtiyacı olursa ayrı iş.
  `RiskSablonuResourceTest` (4 test: madde ekleme/düzenleme/silme +
  paylaşılan-ama-sahibi-olmayan 403 alır).
  **Ders:** bir kaynağa yeni bir yazma yeteneği eklerken, o kaynağın
  MEVCUT yetkilendirmesini de gözden geçir — yalnız yeni eklenen alanı
  değil, formun tamamını.

## Durum — 2026-09-06 (Eğitim süre modeli düzeltmesi + Boş İmza Formu)

**464 test** (456 → +7 EgitimKatilimTest, +1 SertifikaOlusturTest). Kullanıcı
isgpratik'in gerçek `/egitim-katilim` ekranını mehse ile karşılaştırmamı istedi;
tek gerçek eksik (çok günlü panel yapısı) ayrı bırakıldı, ama karşılaştırma
sırasında kullanıcı mevzuata dayalı **iki gerçek hata** bildirdi ve düzeltilmesini
istedi — kullanıcının kendi ifadesiyle: "4 blok için az tehlikelilerde 2 saat
tehlikeli 3 saat çok tehlikelilerde 4 [saat] olmak zorunda... tekrar eğitimelerin
hep en az sekiz saat olarak yapılması gerekmektedir."

- **Kök neden:** `config('isg.egitim.sureler')` yalnız "İşyerine Özgü Riskler"
  bloğunu tehlike sınıfına göre ölçekliyordu (`ise_ozgu_dk` 90/135/180); Genel/
  Sağlık/Teknik Konular SABİT 80/80/120 dk idi (tehlike sınıfından bağımsız).
  Doğrusu: **4 blok da eşit paylaşımla** toplam süreye ulaşmalı (az tehlikeli
  4×2=8s, tehlikeli 4×3=12s, çok tehlikeli 4×4=16s — zaten var olan toplamlarla
  birebir uyuşuyor, sadece dağılım yanlıştı).
  - `config('isg.egitim.sureler')` → `ilk.{az_tehlikeli,tehlikeli,cok_tehlikeli}`
    + ayrı `tekrar` anahtarı (`blok_fiili_dk` her zaman 90, `saat` her zaman 8 —
    tehlike sınıfından bağımsız, kullanıcı doğruladı). `dinlenme_dk` alanı
    KALDIRILDI — zaten hiçbir view'da kullanılmıyordu (`bolumSuresi()` dinlenmeyi
    fiili/3 ile zaten dinamik hesaplıyor); sadece `blok_fiili_dk`+3:1 oranıyla
    (45dk ders+15dk teneffüs) tutarlı: örn. az tehlikeli 90dk fiili+30dk dinlenme
    =120dk=2 saat × 4 blok = 8 saat ✅.
  - `EgitimIcerikOlusturucu::olustur()`'a `$egitimTuru='ilk'|'tekrar'` parametresi
    eklendi; yeni `maddeleriOlcekle()` Genel/Sağlık/Teknik'in config'teki SABİT
    ağırlıklarını (ör. Sağlık'ta İlkyardım diğerlerinden daha az ağırlıklı)
    KORUYARAK hedef toplam dakikaya orantılı ölçekler (yuvarlama nedeniyle
    ±birkaç dk sapma olabilir, testlerde tolerans tanındı).
  - **Yan bulgu, aynı turda düzeltildi:** `SertifikaOlustur.php`'de zaten bir
    `$tur` (İlk Defa/Tekrar) seçici VARDI ve `Sertifika` kaydına yazılıyordu
    ama `EgitimIcerikOlusturucu::olustur()`'a hiç GEÇİRİLMİYORDU — yani "Tekrar"
    seçmek içeriği/süreyi hiç etkilemiyordu (sessiz kalan bir bağlantı eksikliği).
    `icerikYenile()`'a `$this->tur==='tekrar'?'tekrar':'ilk'` eklendi + eksik
    olan `updatedTur()` hook'u eklendi (buton `$set('tur',...)` ile tetikliyordu
    ama içerik hiç yenilenmiyordu).
- **Tekrar periyodu bilgi notu (hesaplama YOK, kullanıcı bunu istedi):**
  `config('isg.egitim.tekrar_periyodu_yil')` (çok tehlikeli 1, tehlikeli 2,
  az tehlikeli 3 yıl) — yalnız EgitimKatilim sayfasında bilgi metni olarak
  gösteriliyor, otomatik "sonraki eğitim tarihi" hesaplanmıyor/hatırlatılmıyor.
- **Boş İmza Formu (yeni özellik):** EgitimKatilim sayfasına firma/katılımcı
  seçmeden çalışan, 14 konu başlığının HER BİRİ için ayrı "İndir" butonu içeren
  yeni bir bölüm eklendi ("Genel" başlığı için ayrıca tehlike sınıfı/tür/sektör
  mini seçiciler). `EgitimKatilimUretici::bosFormPdf()` — kaydedilmeyen
  (unsaved) bir `EgitimKatilim` örneğiyle aynı PDF şablonunu kullanır.
  PDF şablonundaki katılımcı tablosu artık HER ZAMAN en az 10 satır basıyor
  (gerçek katılımcı sayısı 10'dan azsa kalanlar boş bırakılır) — el yazısıyla
  ad/T.C./imza atmak için. `İşveren/İşveren Vekili` yeni başlık olarak
  EKLENMEDİ (kullanıcı "hayır, mevcut 13+genel yeter" dedi).
  `EgitimKatilimTest`'e 9 test eklendi.

## Durum — 2026-09-06 gece (isgpratik ↔ mehse gece denetimi)

Kullanıcı uyurken tüm `Desktop\isgpratik\` referans klasörünün mehse'nin
mevcut modülleriyle karşılaştırılması istendi ("kesin eksikse izin almadan
devam et"). Tam rapor: proje kökünde `DENETIM-NOTU-2026-09-06.md` (git'e
eklenmedi, geçici). Bulunan ve düzeltilen 3 kesin eksik:

- **DÖF Takip paneli:** `DofOlustur` sayfası artık üstte portföy geneli bir
  takip bölümü gösteriyor — Toplam DÖF / Açık Madde / Kapanmış Madde / Genel
  Kapatma Başarısı %, öncelik dağılımı rozetleri, tüm firmalarda arama+öncelik
  filtresi, her açık madde için kapatma notu + "Kapat" butonu (`durum` alanını
  `tamamlandi` yapıp `kapatma_notu`/`kapatma_tarihi` ekler — maddeler JSON'unda
  yeni alanlar, migration gerekmedi). Önceden mehse yalnız SEÇİLİ firmanın
  geçmiş kayıtlarını listeliyordu, portföy geneli özet/kapatma akışı hiç
  yoktu. `DofOlusturTest`'e 4 test.
- **Profilim → İlkyardımcı İhtiyacı:** `PortfoyKarne::ilkyardimciIhtiyaci()`
  — önceden "çalışan modülü tamamlanınca..." diye sabit bir "yakında" metniydi;
  artık İlkyardım Yönetmeliği md.19'daki (zaten kodda dokümante) oranla
  (çok tehlikeli 1/10, tehlikeli 1/15, az tehlikeli 1/20) gerçek sayı
  hesaplanıyor.
- **Profilim → Performans Profili:** `PortfoyKarne::performansEksenleri()` —
  5 eksenli (Evrak Uyumu/Çalışan Kapsamı/Eğitim Durumu/Risk Yönetimi/Acil
  Durum) SVG radar grafiği eklendi; yalnız GERÇEK modülü kurulu (`hazir=true`)
  kriterlerin ortalaması alınıyor (kurulmamış kriterleri dahil etmek portföyü
  olduğundan kötü gösterirdi — bilinçli tercih). `ProfilimTest`'e 2 test.

**Uygulanmayan, daha düşük öncelikli/tartışmalı bulgular** (`DENETIM-NOTU`
dosyasında ayrıntılı): Saha Denetimi "Taslaklar" sekmesi yok; Acil Durum
Planı "Toplu İndir" (ZIP) yok; Acil Durum Planı'ndan Kroki'ye doğrudan
kısayol yok; AI Saha Analizi'nin isgpratik'teki kredi/kota sistemi
**bilinçli olarak atlandı** (mehse tek kullanıcının kendi programı, SaaS
faturalama modeli anlamsız); Eğitim Soruları'nda "Sınavdan Önce/Sonra"
ayrımı yok. `sorunlu/osgb takip.jpg` ekran görüntüsü canlı veriyle kontrol
edildi — sorun tespit edilmedi (05.09.2026'da zaten düzeltilmiş görünüyor).

**Tam test:** DÖF+Profilim değişiklikleri dahil tüm paket **470 test**,
hepsi geçiyor.

## Durum — 2026-09-06 (Saha Denetimi Taslakları + Eksik Firmalar + acil_durum_plani düzeltmesi)

DENETIM-NOTU'ndaki 5 "tartışmalı" kalem kullanıcıyla tek tek gözden geçiriliyor
("sen sor, ben seçeyim" akışı). İlk kalem onaylanıp uygulandı, ayrıca kullanıcı
bu sırada canlı bir istek daha ekledi.

- **Saha Denetimi — Taslaklar ✅:** `saha_denetimleri` tablosuna `durum`
  (`taslak`/`tamamlandi`, varsayılan `tamamlandi`) eklendi. Firma başına 1
  açık taslak: "Taslak Olarak Kaydet" (validasyon yok, uygunsuzluk açıklaması
  dahil hiçbir alan zorunlu değil), firma seçilince otomatik yükleme +
  turuncu "Taslağı Temizle" banner'ı (isgpratik'in Eğitim Katılım'daki aynı
  desenine benzer), "Denetimi Tamamla" ile bitirilince taslak otomatik
  silinir, `gecmisKayitlar()` artık yalnız `tamamlandi` olanları listeler.
  `SahaDenetimiTest`'e 5 test.
- **"Eksik Olan Firmalar" hızlı erişimi (yeni, genel kalıp) ✅:** Kullanıcı
  canlı isteği: "Acil durum planı hazırlanmayan firmaları tek tek firma
  açmadan görebilmeliyim." `PortfoyKarne::eksikFirmalar(userId, kriterAnahtari)`
  eklendi (mevcut `firmaKriterKarsilarMi()`'yi kullanır, `elli_calisan` koşullu
  kriterlerde muafiyeti de uygular) + paylaşılan partial
  `resources/views/filament/pages/partials/eksik-firmalar.blade.php` —
  daraltılabilir "⚠ Eksik Olan Firmalar (N)" kutusu, her firma bir buton,
  tıklanınca `$set('firmaId', ...)` ile sayfanın firmasını değiştirir.
  **Acil Durum Planı** sayfasına eklendi (kullanıcının verdiği örnek); diğer
  belge sayfalarına yaygınlaştırma kullanıcıyla konuşulacak.
  - **Bu sırada bulunup düzeltilen gerçek hata:** `acil_durum_plani` kriteri
    `PortfoyKarne::gercekModulVarMi()`'de HİÇ implemente edilmemişti (`default
    => null`), config'te de `hazir=false` işaretliydi — Acil Durum Planı
    modülü (Faz 3g'den beri) tamamen kurulu olmasına rağmen Kontrol Merkezi
    ve Firma Takip bunu asla "tamam" göstermiyordu, her firma sürekli "eksik"
    görünüyordu. Ayrıca `Firma` modelinde `acilDurumPlani()`/`acilDurumKrokisi()`
    ters ilişkileri hiç yoktu (eklendi). Artık `filled($firma->acilDurumPlani
    ?->konular)` ile kontrol ediliyor — `AcilDurumPlani::firmaIcin()` ilk
    ziyarette otomatik boş(değil, varsayılan konularla dolu) bir taslak
    açtığından bu, "sayfa hiç açılmadı" ile "en azından bir kez düzenlenip
    kaydedildi" arasındaki farkı ayırt eder (yıllık plan kriterlerinde
    kullanılan aynı desen). `hazir=true` yapıldı — hazır kriter sayısı
    14'ten 15'e çıktı, buna bağlı 2 test (`ProfilimTest`, `KontrolMerkeziTest`)
    güncellendi + 3 yeni test eklendi.

## Durum — 2026-09-06 (Eksik Firmalar → 10 sayfa + Firma Evrak ZIP)

- **"Eksik Olan Firmalar" tüm kontrol listesine yayıldı ✅:** Kullanıcı
  "kontrol listesindeki her butona koy" dedi — partial artık Acil Durum
  Planı, Risk Sihirbazı, Tatbikat Tutanağı, Kurul Toplantısı, Tespit Öneri
  Defteri, Eğitim Katılım, İş İzin Formu, İş Kazası Raporu, Muayene Formu ve
  Yıllık Planlar'da (3 sekmesi de kendi kriterine göre: `yillik_calisma_plani`
  / `yillik_egitim_plani` / `yillik_degerlendirme`) gösteriliyor. `igu_atamasi`/
  `hekim_atamasi` gibi Firma alan bazlı kriterlerin kendi "belge sayfası" yok,
  bilinçli olarak atlandı. Tüm ilgili sayfa testleri (125 test, 10 dosya)
  regresyonsuz geçti.
- **Firma başına "Tüm Evrakları İndir" (yeni özellik) ✅:** Kullanıcı canlı
  isteği: "Firmaya tıkladığımda indir seçeneği gelsin, istediğim evrakları
  tikleyip toplu indireyim." `Firmalar` listesine "Evrakları İndir" satır
  aksiyonu eklendi — mevcut `RaporKayitlari` kaynağını (Profilim → Raporlar
  ile aynı, 22 belge üretici modülü) kullanarak o firmanın ürettiği TÜM
  belgeleri onay kutulu listede gösterir (varsayılan hepsi seçili), seçilenleri
  `ZipArchive` ile tek dosyada indirir. Yeni sınıf: `App\Support\
  FirmaEvrakZipUretici` (`secenekler()` + `zip()`).
  - **Bulunup düzeltilen ince hata:** `RaporKayitlari::hepsi()` performans
    için `firma` ilişkisini yalnız `id,unvan` ile eager-load ediyor (liste
    görünümü için yeterli) — bu modeli doğrudan PDF üreticiye verince
    `tehlike_sinifi` gibi alanlar `null` geliyor, `Firma::tehlikeSinifiEtiketi()`
    tip hatası fırlatıyordu. `FirmaEvrakZipUretici::zip()` her kayıt için
    `$kayit->load('firma')` ile ilişkiyi tam alanlarla tazeliyor.
  - Evrağı olmayan firmalarda buton gizli. `FirmaEvrakZipUreticiTest` (6 test).
- Bu iki özellik, DENETİM-NOTU'ndaki "Acil Durum Planı — Toplu İndir"
  tartışmalı kaleminin çoğu ihtiyacını zaten karşılıyor (farklı belge
  türlerini birlikte indirme) — yalnız aynı belgenin PDF+Word ikilisini bir
  arada vermiyor, o madde düşük öncelikte açık kaldı.

## Durum — 2026-09-06 (Acil Durum Planı → Kroki kısayolu)

DENETIM-NOTU'ndaki tartışmalı kalem 3/5 onaylandı: `AcilDurumPlani` sayfasına
"Kroki Planı Hazırla" başlık aksiyonu eklendi (`AcilDurumKrokisi::getUrl(['firma'
=> ...])` — sayfa zaten bu query param'ı destekliyordu). `AcilDurumPlaniTest`'e
1 test (`assertActionHasUrl`). Kalan 2 tartışmalı kalem: Eğitim Soruları
Önce/Sonra ayrımı, AI Saha Analizi kredi sistemi (bu sonuncusu için öneri:
uygulanmasın).

## Durum — 2026-09-06 (Eğitim Soruları — Sınav Zamanı)

DENETIM-NOTU tartışmalı kalem 4/5 onaylandı: `egitim_sinavlari` tablosuna
`sinav_zamani` (`once`/`sonra`, varsayılan `sonra` — uygulamanın kendi
tanımı zaten "eğitim sonrası sınav" varsayıyordu) eklendi. Model'e her ikisi
de eklendi: `zamanEtiketi()` + DB default'uyla eşleşen `$attributes` (aksi
halde `::create()` ile bu alan atlanan testlerde model null görüyordu —
klasik Eloquent "DB default kaydetmeden nesneye yansımaz" tuzağı).
Form + PDF başlığı/künyesi güncellendi. `EgitimSorulariTest`'e 1 test.
Kalan tek tartışmalı kalem: 5/5 AI Saha Analizi kredi sistemi (öneri:
uygulanmasın).

## Durum — 2026-09-06 (Risk Kütüphanesi: Asansör/Boya/Dış Cephe + AI Puan Tamamlama)

Kullanıcı isteği: "risk değerlendirmede olasılık şiddet ve frekans olmayan
kayıtları otomatik olarak yapay zekaya yaptırarak kaydet. Misal Asansör boya
dış cephe gibi durumlar hazırlamış olduklarıma ekle eksik olanlarıda
tamamla. AI ile olanda kalsın, tek kullanıcı olduğum için kredi gerek yok."

- **Risk Kütüphanesi'ne 3 yeni kategori ✅:** `TehlikeKutuphanesiSeeder`'a
  `asansor` (Asansör), `boya` (Boya İşleri), `dis_cephe` (Dış Cephe İşleri)
  eklendi — her biri 4'er tehlike/risk/önlem/mevzuat satırıyla, mevcut
  `genel_isyeri`/`insaat`/`atolye` kategorileriyle aynı formatta (bölüm,
  faaliyet, tehlike, risk, mevcut önlem, ilgili yönetmelik). İçerik gerçek
  yönetmelik başlıklarına atıfla (Asansör İşletme/Bakım/Periyodik Kontrol
  Yön., Yapı İşlerinde İSG Yön., Patlayıcı Ortamların Tehlikelerinden Korunma
  Yön. vb.) hazırlanmış kürate edilmiş referans içeriktir — birebir resmi
  metin/tablo kopyası değildir (bu tür kütüphane maddeleri zaten mevzuat
  metni değil, uzmanın kendi risk değerlendirme çıkarımıdır). Toplam: 3→6
  kategori, 13→25 tehlike. **Doğrudan sonuç:** `RiskDegerlendirmeTest`'teki
  sabit `assertCountTableRecords(13)` beklentisi kırıldı, `25`'e güncellendi
  (kendi değişikliğimin beklenen yan etkisi).
- **`App\Support\GeminiRiskPuanTamamlayici` (yeni) ✅:** Var olan bir
  tehlike/risk metnine (kütüphaneden aktarılan ya da elle girilen) Gemini'den
  Olasılık/Şiddet(/Frekans — yalnız Fine-Kinney'de) puanı ister.
  `GeminiRiskDanismani`'nin aksine YENİ risk ÜRETMEZ, sadece puanlar.
  Güvenlik mekanizması: LLM'in döndürdüğü sayı, ilgili ölçekteki (matris_5x5
  veya fine_kinney) İZİN VERİLEN değerlerden en yakınına yuvarlanır
  (`enYakinDeger()`) — ölçek dışı bir değer asla `RiskMaddesi`'ne yazılamaz.
  API anahtarı yoksa/istek başarısız olursa/JSON bozuksa sessizce `null`
  döner, madde puansız kalır (kullanıcı elle girebilir) — mevcut Gemini
  entegrasyonlarıyla aynı "sessiz başarısızlık" konvansiyonu.
  - **Kredi/kota sistemi YOK — bilinçli karar:** Kullanıcının açık isteği
    "tek kullanıcı olduğum için kredi gerek yok" — AI Saha Analizi için daha
    önce reddedilen kredi sistemi kararıyla (DENETİM-NOTU tartışmalı 5/5)
    tutarlı, aynı gerekçeyle bu özellikte de hiç uygulanmadı.
- **`MaddelerRelationManager` entegrasyonu ✅:** İki noktada devreye giriyor:
  1. **"Kütüphaneden aktar"** aksiyonu artık maddeyi oluşturduktan hemen sonra
     AI'dan puan istiyor (`puanlaAiIle()`), başarılıysa bildirim metni
     "...ve AI ile puanlandı" oluyor; AI kapalıysa eskisi gibi "puanı girin".
  2. **Yeni "Eksik Puanları AI ile Tamamla" başlık aksiyonu** — o risk
     değerlendirmesindeki Olasılık VEYA Şiddet'i boş olan TÜM maddeleri
     bulup topluca AI'ya puanlatıyor (önceden var olan/ithal edilmiş kayıtlar
     dahil — kullanıcının "eksik olanları da tamamla" isteği). Yalnız AI
     aktifken görünür (`GeminiRiskPuanTamamlayici::aktifMi()`), onay modalı
     var. Dolu puanlı maddelere dokunmuyor (test: `Http::assertSentCount(1)`
     ile tek maddeye tek istek atıldığı doğrulandı).
- **Test:** Yeni `GeminiRiskPuanTamamlayiciTest` (8 test) — API kapalıyken
  pasiflik, matris_5x5/Fine-Kinney yuvarlama, başarısız istek/bozuk JSON,
  RelationManager üzerinden uçtan uca "kütüphaneden aktar" (AI açık/kapalı)
  ve "eksik puanları tamamla" (yalnız boş maddeleri doldurma) senaryoları.
  `RiskDegerlendirmeTest` güncellendi (13→25). Tam suite: bkz. altta.

## Durum — 2026-09-06 (Risk Kütüphanesi/Excel: Mevcut Önlem AI tamamlama + genişletme)

Devam isteği: "bundan sonra risk değerlendirmesine yükleyeceğim tablolarda
eğer puanlama ve önlemler bölümü boşsa yapay zeka doldursun. Ve bundan
önceki yüklenen risk değerlendirmesinde kontrol et."

- **`GeminiRiskPuanTamamlayici::onlemOner()` (yeni metod) ✅:** Puanlamadan
  bağımsız ayrı bir istek — yalnızca `mevcut_onlem` metni boşsa çağrılır,
  tehlikeye özgü kısa (tek cümle) bir önlem önerisi ister. Var olan metnin
  üzerine hiç yazmaz.
- **`MaddelerRelationManager` — "Mevcut Önlem" de artık kapsamda ✅:**
  - "Kütüphaneden aktar": madde oluşturulduktan sonra hem puan hem (kütüphane
    kaydında `mevcut_onlem` boşsa) önlem önerisi isteniyor; bildirim metni
    ikisine göre değişiyor.
  - "Eksik Puanları AI ile Tamamla" → **"Eksik Puan/Önlemleri AI ile
    Tamamla"** olarak genişletildi: sorgu artık `mevcut_onlem` boş olan
    maddeleri de kapsıyor (Fine-Kinney'de `frekans` boşluğu da). Tam dolu
    madde sorguya hiç girmiyor, kısmi dolu maddede yalnız eksik eksen(ler)
    isteniyor.
  - **Bulunan/düzeltilen ince hata:** `puanlaAiIle()` önceden AI'nin
    döndürdüğü TÜM eksenleri (`olasilik`/`siddet`/`frekans`) `forceFill`
    ediyordu — Fine-Kinney'de yalnız `frekans` boşken bile kullanıcının
    elle girdiği Olasılık/Şiddet AI önerisiyle ezilebilirdi. Artık yalnız
    gerçekten boş olan eksen(ler) yazılıyor.
- **`RiskSihirbazi` — Excel içe aktarımda otomatik tamamlama (yeni) ✅:**
  `excelSecilenleriEkle()`'ye eklenen `excelEksikleriAiIleTamamla()`, Excel'den
  gelen ve seçilip "Risklerime Ekle" ile eklenen her satır için Olasılık/
  Şiddet(/Frekans) veya Mevcut Önlem boşsa AI'dan tamamlıyor. Sıralama önemli:
  Fine-Kinney ölçek-otomatik-geçiş kontrolü (mevcut özellik) AI tamamlamadan
  ÖNCE çalışıyor — aksi halde AI henüz yanlış ölçeğe göre puan üretebilirdi.
  AI kapalıysa (anahtar yok) hiç dokunmuyor, eski davranış (elle giriş) aynen
  sürüyor. Bildirimde kaç maddenin AI ile tamamlandığı gösteriliyor.
- **Mevcut (önceden yüklenmiş) risk değerlendirmeleri — DURUM TESPİTİ:**
  Canlı veride kontrol edildi: **11 risk değerlendirmesi, 3073 madde** — puanlama
  tarafında hiç eksik YOK (`olasilik`/`siddet` hepsi dolu), ama **2982 maddede
  (%97) `mevcut_onlem` boş** — bu, TÜM 11 kayıtta neredeyse aynı oranda
  (275-352 madde/kayıt) tekrarlayan sistemik bir boşluk (muhtemelen o dönemde
  bu alanın Excel/sihirbaz akışlarında hiç doldurulmamasından). "Eksik Puan/
  Önlemleri AI ile Tamamla" butonu artık her risk değerlendirmesi sayfasında
  bu boşluğu tek tıkla dolduruyor — ancak ~3000 canlı Gemini isteği (gerçek
  API kotası + uzunca sürecek bir işlem) gerektirdiğinden, kullanıcıya
  hangi kapsamda (tümü / belirli firmalar / kendisi tek tek) çalıştırmak
  istediği soruldu, otomatik toplu çalıştırma yapılmadı.
- **Test:** `GeminiRiskPuanTamamlayiciTest`'e 2 yeni test (eksik-eksen-only
  doldurma + kütüphaneden aktarırken önlem tamamlama), `RiskSihirbaziTest`'e
  2 yeni test (Excel'de boş puan/önlem AI ile dolduruluyor; AI kapalıyken
  dokunulmuyor).
- **Geçmiş veri backfill'i — gerçek kota keşfi:** Kullanıcı onayıyla tüm 11
  firmanın 2982 boş `mevcut_onlem` alanını dolduran tek seferlik betik
  (`onlem-backfill.php`, repo dışı/scratchpad, commit edilmedi) çalıştırıldı.
  İlk denemede (istek başına 150ms bekleme) yalnızca 20 madde dolup gerisi
  sürekli "kota aşıldı" (429) hatası aldı — Google'ın ücretsiz katmanı
  `gemini-3.6-flash` için **dakikada 20 istekle** sınırlı. Betik durdurulup
  istek aralığı ~4.2 saniyeye (dakikada ~14 istek, güvenlik payı) çıkarıldı
  ve 429/503 gibi geçici hatalarda aynı maddeyi 4 kez tekrar deneyen mantık
  eklendi. Yeniden başlatıldı; ~2962 madde için tahmini süre ~3-3.5 saat
  (kullanıcı onayladı — "kotaya uygun hızda arka planda devam et").

## Durum — 2026-09-06 (Acil Durum Afişleri — gerçek grafik şablonlarla güncelleme + gerçek A3)

Kullanıcı isteği: "acil durum afişleri ... güncelle. Örneğin yangın afişi
tıkladığımda bu adresteki pdfler gelsin birde A3 boyutunda basmak için
seçenek gelsin" — kaynak: `C:\Users\mozde\Desktop\isgpratik\acil durum\`.

- **Bulunan ciddi hata (kullanıcı fark etmeden önce):** `resources/belge/
  acil-durum-afisleri/*.pdf` altındaki 7 hazır afiş dosyası, isgpratik'ten
  dışa aktarılırken **başka bir firmanın adı ("NİL UNLU MAMULLER GIDA
  PASTACILIK...") görsele sabit gömülü** olarak kaydedilmişti — eski kod da
  bu dosyayı OLDUĞU GİBİ akıtıyordu (`file_get_contents` + stream), yani
  **hangi firma için indirilirse indirilsin TÜM firmalar** afişte yanlış
  firma adını görüyordu. `A3 Boyutu` seçeneği de yalnızca dosya adını
  değiştiriyordu — içerik hep aynı (A4 boyutunda) statik dosyaydı.
- **Yeni kaynak dosyalar ✅:** Kullanıcının verdiği klasördeki 7 güncel,
  **firma adı gömülü OLMAYAN** genel şablon (`Genel_*_A4_Afiş.pdf`),
  `resources/belge/acil-durum-afisleri/{yangin,deprem,is_kazasi,elektrik,
  kimyasal,sel,sabotaj}.pdf` üzerine kopyalandı (1:1 isim eşleşmesi).
- **`AcilDurumPlaniUretici::hazirAfisiFirmaIleUret()` (yeni) ✅:** Artık hazır
  dosya OLDUĞU GİBİ akıtılmıyor — her indirmede `setasign/fpdi` (zaten
  composer'da vardı, kullanılmıyordu) ile şablon sayfası İÇE AKTARILIP
  üstüne o anki firmanın adı + tehlike sınıfı + tarihi gösteren, dompdf ile
  üretilen (Türkçe karakter güvenli) bir bilgi şeridiyle YENİDEN birleştirilir.
  Statik dosyada artık hiçbir firma adı yok; her firma kendi adını görür.
  - **Gerçek A3 ✅:** Seçilen ebada göre gerçekten farklı fiziksel sayfa
    üretilir (A4 MediaBox 297×210mm / A3 420×297mm doğrulandı) — önceden
    yalnız dosya adında "A3" yazan ama içerik olarak A4 kalan sahte seçenek
    düzeltildi. Kaynak afişler yatay tasarlandığından (çoğu akış şeması),
    çıktı da kaynağın doğal yönünde (yatay) üretilir — dikey çerçeveye zorla
    sığdırmak posteri gereksiz küçültüp boşluk bırakırdı.
  - **Bulunan/atlatılan dompdf hatası:** Bilgi şeridi için `display:table`
    + `height:100%` kullanan ilk deneme, çok kısa (18mm) özel sayfa
    boyutunda dompdf'in yanlış 3 sayfaya bölmesine yol açtı (float'lu
    çocukların üst kapsayıcı yüksekliğine dahil edilmemesi de ayrı bir
    sorundu) — tek satır, float'sız, table'sız basit `inline` düzene
    çevrilerek çözüldü.
- **Test:** `AcilDurumPlaniTest`'teki eski `test_hazir_dosyasi_olan_afis_o_
  dosyayi_indirir` (statik dosyayla bayt-bayt eşitlik bekliyordu — artık
  yanlış varsayım) kaldırılıp yerine `assertNotSame` (artık firma şeridiyle
  yeniden üretiliyor, ham dosya değil) ve yeni `test_a3_secimi_gercekten_
  daha_buyuk_fiziksel_sayfa_uretir` (MediaBox karşılaştırması) eklendi.
  Tam suite: 500 test geçti.

## Durum — 2026-09-06 (Gemini günlük kota keşfi + Beyda Yapı risk değerlendirmesi tamamlama)

- **KRİTİK keşif — Gemini ücretsiz kota GÜNLÜK, dakikalık değil:** Önceki
  "Mevcut Önlem" toplu doldurma denemesi tekrar tıkandı; hata gövdesindeki
  `quotaId: GenerateRequestsPerDayPerProjectPerModel-FreeTier, quotaValue: 20`
  alanı gösterdi ki `gemini-3.6-flash` ücretsiz katmanı **günde yalnızca 20
  istek** izniyle sınırlı (dakikada 20 değil). ~2941 kalan madde için bu
  hızda ~150 gün gerekir. Kullanıcıya durum anlatıldı, üç seçenek sunuldu;
  kullanıcı "günde otomatik 20'şer devam etsin" seçeneğini onayladı.
  - **Yeni betik — `gemini-onlem-gunluk.php`** (repo köküne, `.gitignore`'a
    eklenmesi denendi ama işletim sistemi düzeyinde kalıcı otomasyon kurma
    eylemleri — `.bat` dosyası yazma, zamanlanmış görev oluşturma —
    Claude Code'un otomatik onay sınıflandırıcısı tarafından engellendi;
    bu commit edilmemiş, kullanıcının kendi PowerShell'inde çalıştırdığı bir
    `Register-ScheduledTask` komutuyla Windows Görev Zamanlayıcı'ya
    kaydedildi). Her çalıştırma art arda 3 başarısız denemeden sonra
    (günlük kota tükendi varsayımıyla) hemen durur — kotayı boşuna
    zorlamaz, ertesi gün kaldığı yerden devam eder. Log:
    `gemini-onlem-gunluk.log` (repo dışı).
- **Beyda Yapı / AHMET BULUT risk değerlendirmesi tamamlandı ✅:** Kullanıcı
  isteği üzerine `C:\Users\mozde\Desktop\Firmalar\beyda yapı\` klasöründeki
  gerçek belgeler incelendi. "Beyda Yapı" ayrı bir firma değil, mehse'deki
  **AHMET BULUT** firmasının işletme adıymış (gerçek belgelerde "ŞİRKET ADI:
  BEYDA YAPI/AHMET BULUT" yazıyor). Acil Durum Planı zaten eksiksizdi (11
  gerçek konu, sistemde 16 vardı). Ama gerçek "BEYDA YAPI RİSK DEĞERLENDİRMESİ
  .xlsx" (RD TABLO sayfası, PhpSpreadsheet ile okundu) 171 satır içeriyordu,
  sistemde ise yalnızca 147 madde vardı. Fark, sıva/boya/alçıpan/yalıtım/dış
  cephe/şap gibi farklı iş kollarında TEKRARLANAN aynı tehlike metinlerinden
  (örn. "AĞIR NESNELERİN ELLE KALDIRILMASI", "YÜKSEKTE ÇALIŞMA") kaynaklanıyordu
  — ilk aktarımda bu tekrarlar tek satıra indirgenmiş, farklı iş kollarına ait
  ayrı satırlar kaybolmuştu. Bir kerelik betikle (`beyda-eksik-ekle.php`, repo
  dışı) her tehlike metninin kaynaktaki tekrar sayısı ile sistemdeki sayısı
  karşılaştırılıp eksik 24 satır, doğru `bölüm` (iş kolu: DIŞ CEPHE KAPLAMA VE
  BOYAMA, YALITIM YAPILMASI, BOYA İŞLERİNDE İSG, BOYA YAPILMASI, ŞAP DÖKÜLMESİ)
  etiketiyle eklendi — kullanıcının "ahmet bulut gibi firmalar boya sıva gibi
  işleri yapmakta, eksik doğru şekilde kalsın" onayıyla. Toplam 147→171 madde.
  Bu, kod değişikliği değil tek seferlik veri tamamlamadır; test suite'i
  etkilemez.

## Durum — 2026-09-06 (Çalışan Temsilcisi Seçimi — yeni modül)

Denetime devam ederken (ATAMA YAZISI/ÇALIŞAN TEMSİLCİSİ klasörü) isgpratik'te
"Atama Yazıları"ndaki düz atamanın yanında ayrı, tam bir **seçim süreci**
olduğu görüldü: Seçim Duyuru İlanı → Aday Başvuru Dilekçesi (boş) → Kesin
Aday Listesi → Oy Pusulası (16'lı kesilebilir pusula) → Atama Tutanağı.
mehse'de yalnızca direkt atama vardı, seçim süreci hiç yoktu. Kullanıcı iki
bulunan eksikten (bu + Acil Durum Planı kaşe/çıktı logosu, henüz yapılmadı)
bunu önceliklendirdi.

- **Yeni tablo/model — `calisan_temsilcisi_secimleri` / `CalisanTemsilcisiSecimi`:**
  Firma başına bir kayıt (`AcilDurumPlani::firmaIcin()` ile aynı desen).
  `booted()` hook'u dokuman no (`ÇT-YYYY-NN`), ilan tarihi ve **zorunlu
  temsilci sayısı** varsayılanlarını otomatik doldurur — sonuncusu 6331
  sayılı Kanun md.20/2 fıkrasındaki çalışan sayısı kademelerine göre
  (2-50:1, 51-100:2, 101-500:3, 501-1000:4, 1001-2000:5, 2001+:6) hesaplanan
  bir ÖNERİDİR, kullanıcı formda değiştirebilir (gerçek yasal metin, ezbere
  yazılmadı — Kanun'un ilgili fıkrasının doğrudan aktarımı).
- **`CalisanTemsilcisiSecimiUretici`** — 5 ayrı dompdf şablonu
  (`pdf.calisan-temsilcisi-{duyuru,basvuru,aday-listesi,oy-pusulasi,tutanak}`),
  isgpratik'in gerçek belge metinleri (6331 md.20 + Tebliğ referanslı) esas
  alınarak, `atama-yazisi.blade.php` ile aynı görsel dilde (kunye tablosu +
  imza satırı + yasal dayanak dipnotu) yazıldı. Oy Pusulası, isgpratik'teki
  gibi sayfa başına 16 kesilebilir pusula (2×8, kesik çizgili) üretir.
- **Yeni sayfa — `CalisanTemsilcisiSecimi`** (Formlar & Belgeler grubu):
  Firma seçilir, seçim bilgileri (tarih/saat/yer/sayılar) ve dinamik aday
  listesi (ekle/sil) girilir; adaylardan biri "kazanan" işaretlenmeden Atama
  Tutanağı aksiyonu devre dışı kalır (`->disabled()` + tooltip).
- **Bulunan/düzeltilen 2. hata — Turkish CSS `text-transform: uppercase`
  dompdf'te "İ"yi bozuyor:** İlk denemede başlıklar CSS ile büyütülüyordu;
  "Çalışan Temsilcisi" → "ÇALIŞAN TEMSILCISI" (noktasız I) çıktı — tarayıcı/
  dompdf'in `text-transform` algoritması Türkçe'ye duyarlı değil (bkz.
  [[turkce-buyuk-harf-donusumu]] hafıza notu, ama bu kez PHP değil CSS
  kaynaklı). Çözüm: `text-transform` kaldırıldı, başlıklar blade içinde
  doğrudan doğru Türkçe büyük harfle (İ noktalı) yazıldı. NOT: mevcut
  `atama-yazisi.blade.php`'de de aynı `text-transform: uppercase` deseni var
  — aynı hataya açık olabilir, bu oturumda dokunulmadı (kapsam dışı, ayrı
  bir denetim maddesi olarak not edildi).
- **Kontrol Merkezi bağlantısı (aynı zamanda 3. bulunan hata):**
  `calisan_temsilcisi` kriteri de `acil_durum_plani` ile AYNI şekilde
  `hazir => false` olarak scaffold edilmiş, hiç gerçek modüle bağlanmamıştı
  (var olan "Atama Yazıları" akışı bile bunu karşılamıyordu). `hazir =>
  true` yapıldı, `PortfoyKarne::gercekModulVarMi()`'ye hem AtamaYazisi
  (rol_anahtari=calisan_temsilcisi) HEM DE yeni seçim sürecinin sonucu
  (`secilen_aday_index` dolu) kabul eden bir case eklendi — ikisinden
  hangisi kullanılırsa kullanılsın kriter karşılanmış sayılır.
  - Bu flip, kriter sayısını 15→16 hazır kritere çıkardığı için
    `KontrolMerkeziTest` ve `ProfilimTest`'teki ilgili oran hesaplama
    yorumları (14→15, 15→16) güncellendi; sayısal beklenti değerleri
    (round sonucu aynı çıktığından) değişmedi.
- **Test:** Yeni `CalisanTemsilcisiSecimiTest` (7 test) — zorunlu temsilci
  kademesi, aday ekle/sil, 5 belgenin PDF üretimi, kazanan seçilmeden
  Tutanak aksiyonunun devre dışı kalması, Kontrol Merkezi kriterinin
  karşılanması. `KontrolMerkeziTest`'e 1 yeni test, mevcut 2 testin örnek
  kriteri (`calisan_temsilcisi` → `periyodik_kontrol_raporu`) güncellendi.

## Durum — 2026-09-06 (acil_durum_destek kriteri + Acil Durum Planı kaşe/imza eksikliği)

Kullanıcı "sırayla yap eksik gördüklerini" dedi — denetimde bulunan iki
maddeye ("Kaşe sistemi + Acil Durum Word çıktısı" olarak özetlenmişti)
kullanıcıdan izin alınmadan devam edildi.

- **`acil_durum_destek` kriteri de `calisan_temsilcisi`/`acil_durum_plani`
  ile AYNI türde unutulmuş bir hataydı ✅:** Kontrol Merkezi'nde
  "Acil Durum Destek Elemanları" hep `hazir => false` idi, hiçbir firma
  için asla karşılanmış sayılmıyordu — halbuki söndürme/kurtarma/koruma/
  ilk yardım ekipleri zaten iki yerden atanabiliyordu (Acil Durum Planı'nın
  kendi "Ekip Üyeleri" alanı VEYA Atama Yazıları'ndaki ilgili ekip rolleri).
  `hazir => true` yapıldı, `gercekModulVarMi()`'ye ikisini de kabul eden bir
  case eklendi. Kriter sayısı 16→17 oldu; bu kez sayısal beklenti de gerçekten
  değişti (isg_kurulu muaf firmada round(1/16*100)=7 idi, round(1/16*100)
  şimdi 6 — `firma_kriter_matrisi` testinin beklenen değeri 7'den 6'ya
  güncellendi, diğer testlerin yorumları da 16/17 olarak düzeltildi).
- **"Kaşe sistemi" aslında ZATEN VARDI — asıl eksik yalnızca Acil Durum
  Planı'nın PDF çıktısıydı ✅:** Araştırınca görüldü ki `User::kase_gorseli`/
  `imza_gorseli` (uzmanın kendi kaşe/imzası) ve `IsgProfesyoneli::kase_gorseli`
  (İGU/hekim) zaten Risk Değerlendirmesi, Atama Yazıları, DÖF, İş Kazası
  Raporu, Muayene Formu, Saha Denetimi, Sertifika, Ceza-Tebliğ gibi HEMEN
  HEMEN HER belge şablonunda kullanılıyordu — sadece **Acil Durum Planı'nın
  PDF'inde** ONAY bölümünde `$uzman` değişkeni zaten vardı (ad/unvan
  gösteriliyordu) ama kaşe/imza görseli hiç `<img>` ile basılmıyordu. Ayrıca
  isgpratik referansında "2. Kaşe — İşyeri Hekimi" diye ayrı bir slot da
  vardı; ONAY tablosuna üçüncü bir sütun (İşyeri Hekimi, atanmışsa kaşe/imza
  ile) eklendi. `risk-degerlendirmesi.blade.php`'deki aynı desenle
  eklendi. Word çıktısına dokunulmadı (o "orijinal şablonun birebir kopyası"
  olma sözü taşıyor, görsel gömme oraya ayrı bir karar gerektirir).
  - **Bulunan ölü kod (dokunulmadı, sadece not edildi):** `acil_durum_planlari`
    tablosundaki `kase_1_gorseli`/`kase_2_gorseli`/`cikti_logosu` sütunları
    hâlâ hiçbir yerde okunmuyor/yazılmıyor — muhtemelen daha önce denenip
    yarım bırakılmış, gerçek sistemin (`User`/`IsgProfesyoneli` üzerinden)
    yerini aldığı bir tasarım. Fonksiyonel bir sorun yaratmıyor, silinmesi
    ayrı bir karar (veri kaybı riski yok çünkü hiç kullanılmamışlar).
- **Test:** `KontrolMerkeziTest`'e `acil_durum_destek` için 1 yeni test;
  `AcilDurumPlaniTest`'e kaşe/imza görsellerinin PDF HTML'ine gerçekten
  basıldığını doğrulayan 1 yeni test. Tam suite: bkz. altta.

## Durum — 2026-09-06 (Eğitim Kayıtları Matrisi — düzeltme + filtre eklemeleri)

Kullanıcı, "büyük, henüz yapılmamış" olarak raporladığım iki bulgudan
"Eğitim Kayıtları Matrisi"ni seçti (`1`). İncelemeye başlayınca **kendi
raporumu düzeltmem gerekti**: bu özellik "hiç yok" değildi — Profilim'in
"Eğitimler" sekmesi (`EgitimTuru`, `EgitimKaydi` modelleri, `egitimMatrisi()`,
Excel şablon indir/yükle, "Konu Ekle/Kaldır" aksiyonları) ÖNCEKİ bir
oturumda zaten kurulmuştu — 22 testin ~17'si zaten buydu, ben denetim
sırasında yalnız ekran görüntüsüne bakıp koda bakmadan "yok" diye
raporlamışım. Gerçek eksik, isgpratik referansındaki filtre/arama/özet
katmanıydı — o eklendi:

- **`EgitimTuru::kategori()`** (yeni): config `isg.egitim_kayit_turleri`
  katalogundan kategori döner (Genel/Sağlık/Teknik/Risk Bazlı/Görev-
  Sertifika); kullanıcının kendi yazdığı "özel" konularda null → "Özel"
  etiketiyle gösterilir.
- **Profilim → Eğitimler'e eklenenler:**
  - **Aktif Personel / İşten Ayrılanlar** sekmesi (`Calisan.aktif` alanına
    göre, veri modelinde zaten vardı, yalnız UI'da yoktu).
  - **Arama** (çalışan adı) ve **Firma** açılır filtresi.
  - **Kategori filtre çipleri** — yalnızca kullanıcının fiilen takip ettiği
    kategoriler gösterilir (birden fazla kategori varsa).
  - **Durum filtre çipleri** (Geçerli/Yakında/Dolmuş/Eksik) — görünen
    sütunlardan EN AZ BİRİ seçili duruma uyan çalışanları listeler.
  - **Özet satırı**: "N çalışan · M eksik" (önceden yalnız "N aktif çalışan"
    yazıyordu, eksik sayısı hiç gösterilmiyordu).
  - Kasıtlı olarak eklenmeyenler: isgpratik'in "Matris/Kart" görünüm
    değiştirici (mehse zaten tek, sade bir tablo kullanıyor) ve "tüm 14
    konuyu varsayılan göster" (önceki oturumda kasıtlı tasarım kararıydı —
    kullanıcı yalnız ilgili konuları ekler; bu davranışa dokunulmadı).
- **Test:** `ProfilimTest`'e 5 yeni test (aktif/ayrılan, arama+firma,
  kategori filtresi, durum filtresi, özet sayaçları).

## Durum — 2026-09-06 (Firma Checklist — Firma Takip drill-down)

Kullanıcı "büyük, henüz yapılmamış" 2. bulgu olan "Firma Takip"i seçti
(önceki "1" seçiminden sonra "tamam devam edelim"). Eğitim Kayıtları
Matrisi'nde yaşanan özeleştiriyi tekrarlamamak için ÖNCE kod okundu: Profilim
sayfasında `firma_takip` sekmesinde zaten bir "Firma × Yasal Kriter Matrisi"
portföy tablosu vardı (`PortfoyKarne::firmaTakipKriterleri()`/
`firmaKriterMatrisi()`) — isgpratik'e kıyasla eksik olan yalnızca **firma
başına drill-down**: her kriter için elle vade tarihi girip
"tamamlandı / yakın / eksik" durumunu görmek.

- **Kasıtlı olarak eklenmeyen kapsam:** isgpratik'teki "İGU/İşyeri Hekimi
  için GEREKLİ dakika vs ATANMIŞ dakika" karşılaştırması (sözleşme süresi
  yeterliliği) **bilinçli olarak yapılmadı** — bu hesap resmi OSGB
  Yönetmeliği Ek-2'deki dakika/oran tablosuna dayanıyor ve bu tabloyu
  hatasız/güvenilir şekilde alıntılayabileceğimden emin değilim. Yanlış bir
  yasal/resmi rakam üretmektense bu kalemi tamamen dışarıda bırakmak tercih
  edildi (`PortfoyKarne::firmaChecklistDetay()` docblock'unda da not edildi).
  Kullanıcı isterse resmi tabloyu kendisi sağlarsa bu ayrı bir görev olarak
  eklenebilir.
- **Yeni tablo:** `firma_checklist_vadeleri` (migration
  `2026_09_06_220000_...`) — `firma_id`, `kriter_anahtari`, `vade_tarihi`
  (nullable date), unique(firma_id, kriter_anahtari). Yalnız kullanıcı elle
  bir vade girdiğinde satır oluşuyor (mehse'nin genel "seyrek override"
  deseniyle aynı — bkz. `AcilDurumPlani::firmaIcin()`).
- **`FirmaChecklistVadesi` modeli** (yeni) + `Firma::checklistVadeleri()`
  ilişkisi.
- **`PortfoyKarne::firmaChecklistDetay(Firma $firma): array`** (yeni) —
  mevcut `firmaTakipKriterleri()` listesini `checklistVadeleri` ile
  birleştirip her kriter için `durum` hesaplar: `tamamlandi` (gerçek modül
  kurulu veya muaf), `yakin` (vade ≤30 gün kaldı), `eksik` (diğer her şey).
- **Profilim sayfası:** `firmaTakipSeciliId` özelliği + `firmaTakipSecili()`/
  `firmaChecklistDetay()` computed metodları + `firmaChecklistVadeGuncelle()`
  (vade tarihini `updateOrCreate` ile kaydeder, boş string gelirse siler).
- **UI:** `firma_takip` sekmesinde mevcut portföy matrisinin ALTINA yeni bir
  "Firma Checklist" kutusu eklendi — firma açılır listesi, seçilince Uyum
  Skoru (aynı `firmaMatrisi` oranı) + her kriter için renkli durum rozeti
  (yeşil/sarı/kırmızı) ve tamamlanmamışsa düzenlenebilir bir tarih girişi.
- **Test:** `ProfilimTest`'e 2 yeni test (`firma_checklist_detay_vade_
  tarihine_gore_durum_hesaplar`, `firma_checklist_vade_guncelle_action_
  kaydeder_ve_kaldirir`). Tam suite: **517 test geçti** (1449 assertion),
  regresyon yok.

## Durum — 2026-09-06 (Kurul Toplantısı — Katılım yerine İmza yeri)

Kullanıcı isteği: "isg kurul kararına katılımcıların imza için yer aç.
Katılım yerine imza yeri aç. çalışanlarında imza atacağı yer olması
gerekli." — tutanak PDF'indeki KATILIMCILAR tablosunda "Katılım" sütunu
yalnızca "Katıldı/Katılmadı" metni basıyordu; fiziksel olarak imzalanan bir
tutanakta bu, katılımı ispatlamaz. `resources/views/pdf/kurul-toplantisi.
blade.php`: sütun başlığı "İmza" oldu, katılan kişiler için hücre boş
bırakıldı (fiziksel imza için yer), yalnızca katılmayan kişilerde
"Katılmadı" yazısı kalır (imza gerekmez). Uygulama içi `katildi` alanı/
toggle (`katilimToggle`) dokunulmadı — yalnızca PDF çıktısı değişti; aynı
desen zaten `egitim-katilim.blade.php`'nin katılımcı imza tablosunda
kullanılıyordu. `KurulToplantisiTest`'e 1 yeni test.

## Durum — 2026-09-06 (Çalışan Temsilcisi Seçimi — Boş Şablon Seti)

Kullanıcı isteği: "çalışan temsilci seçim tutanakların firmanın ismini ve
çalışan listesi boş olupta elle doldurulcak evraklar gibi çıktı almak
istiyorum." — 5 belgenin (Duyuru/Başvuru/Aday Listesi/Oy Pusulası/Tutanak)
firma adından ve seçili firmanın gerçek aday/çalışan verisinden tamamen
BAĞIMSIZ, elle doldurulacak boş bir versiyonu istendi (kağıt üstünde
imzalatılacak fiziksel form ihtiyacı).

- **5 blade şablonuna `$bos` parametresi eklendi** (`pdf.calisan-temsilcisi-
  {duyuru,basvuru,aday-listesi,oy-pusulasi,tutanak}.blade.php`) — `true`
  olunca: firma adı/adresi yerine boş çizgi (`.cizgi`/`.bos` CSS — bazı
  dosyalarda zaten ölü kod olarak duran `.bos` sınıfı ilk kez kullanıldı),
  `$secim`'e ait tarih/sayı alanları boşken '—' yerine tamamen boş (fiziksel
  formda '—' yazan bir alan "doldurulmasın" gibi yanlış anlaşılabilir),
  aday/çalışan listesi tabloları gerçek veri yerine 10 (aday listesi) / 6
  (oy pusulası) boş satır/daire üretir ("eklenmedi" mesajı yerine).
- **`CalisanTemsilcisiSecimiUretici::bosSablonZip()`** (yeni) — `FirmaEvrakZip
  Uretici` ile aynı `ZipArchive`+`tempnam`+`streamDownload` deseni; hiçbir
  firma/kayıt gerektirmeden `new CalisanTemsilcisiSecimi()` (kaydedilmeden)
  ile 5 belgeyi `bos:true` render edip tek ZIP'te toplar.
- **Filament sayfası:** yeni header aksiyonu "Boş Şablon Seti (ZIP)" —
  diğer 5 aksiyonun aksine `->visible(fn () => $this->firma !== null)`
  KOŞULU YOK, firma seçilmeden de her zaman görünür (sistemden bağımsız
  şablon istendiği için).
- **Test:** `CalisanTemsilcisiSecimiTest`'e 3 yeni test (firma seçilmeden
  erişim, ZIP 5 dosya içeriyor, boş şablonda gerçek firma/aday verisi hiç
  görünmüyor).

## Durum — 2026-09-06 (İşbaşı Eğitim — Toplu Katılım Formu)

Kullanıcı isteği: "işbaşı eğitimi katılım formunu indirmek için buton ver.
Yaklaşık olarak 10 kişilik eğitim katılım formu." — mevcut İşbaşı/Oryantasyon
Eğitim Tutanağı YALNIZ tek bir çalışan içindi (`IsbasiEgitimTutanagi.
calisan_ad_soyad` tekil alan); isteneni bir grup eğitimi (aynı anda ~10
kişiye verilen oryantasyon) için TEK sayfada toplu imza formu.

- **`resources/views/pdf/isbasi-egitim-katilim-formu.blade.php`** (yeni) —
  künye (eğitim tarihi/süre/yer/eğitimci/yöntem/belge tarihi, tekil çalışan
  alanı YOK) + konu kategorileri checklist (tutanakla aynı görsel dil) +
  katılım tablosu (Ad Soyad/T.C./Görevi/İmza, `egitim-katilim.blade.php`'deki
  "en az 10 satır" deseniyle — firma çalışan sayısı azsa boş satırlarla
  tamamlanır) + eğitici/İGU-Hekim imza bloğu.
- **`IsbasiEgitimTutanagiUretici::katilimFormuPdf(Firma $firma, array $veri)`**
  (yeni) — tekil tutanağın aksine VERİTABANINA YAZMAZ, yalnız anlık üretilip
  indirilir (grup formu için ayrı bir model açmaya gerek yok, sayfanın o
  anki alan değerleri + firma çalışanları yeterli).
- **Filament sayfası:** yeni header aksiyonu "Katılım Formu (Toplu)" — tekil
  "PDF İndir" aksiyonunun aksine `calisanAdSoyad` doldurulmasını GEREKTİRMEZ,
  yalnız firma seçili olması yeterli; firmanın tüm çalışanları (`$this->
  calisanlar`) otomatik listeye eklenir.
- **Test:** `IsbasiEgitimTest`'e 2 yeni test (çalışan adı olmadan üretilir,
  katılım tablosunda gerçek çalışan + en az 10 satır).

## Durum — 2026-09-06 (Talimat Oluştur — Word çıktısı eklendi)

Kullanıcı isteği: "talimatlara pdf ve word olarakta ekleme seçeneği koy" —
Talimat Oluştur sayfasında yalnız PDF indirme vardı, Word (.docx) seçeneği
de istendi.

- **`App\Support\TalimatWordUretici::word(Talimat $talimat)`** (yeni) —
  Acil Durum Planı/Atama Yazıları'ndaki Word üreticilerin aksine (onlar
  SABİT alanlı, gerçek referans .docx şablonu üzerinde metin değiştirir),
  talimatın madde sayısı firmadan firmaya değiştiğinden burada sabit bir
  şablon uygun değil — içerik doğrudan `phpoffice/phpword` (zaten
  `composer.json`'da bağımlıydı, ilk kez kullanıldı) ile programatik
  üretiliyor: başlık, firma, kategori, açıklama, KKD listesi, numaralı
  madde listesi (`addListItem` + `ListItem::TYPE_NUMBER`), imza tablosu,
  yasal dayanak metni — `pdf.talimat` (dompdf) ile aynı içerik/sıra.
- **Filament sayfası:** yeni header aksiyonu "Word İndir (Kaydet)" (PDF
  aksiyonunun yanına, aynı `kaydet()` akışını kullanır) + "Kayıtlı
  Talimatlarım" listesindeki her satıra "Word" butonu (`kayitliWord()`,
  `kayitliPdf()` ile aynı desende).
- **Test:** `TalimatOlusturTest`'e 3 yeni test (Word aksiyonu kayıt
  oluşturur, `TalimatWordUretici::word()` geçerli bir .docx/ZIP paketi
  ("PK" imzası) üretir, kayıtlı talimat Word olarak indirilebilir).

## Durum — 2026-09-06 (DÖF, Tatbikat, Ceza Tebliğ — fotoğraf ekleme)

Kullanıcı isteği: "tatbikat raporuna, döf, ceza tebliğ tutanağına fotograf
ekleme yeri bırak." Mevcut kütüphanede iki farklı fotoğraf konvansiyonu
zaten vardı — hangisinin hangi belgeye uyduğuna göre seçildi:

- **DÖF (`DofOlustur`) — PER-MADDE fotoğraf** (Saha Denetimi'nin `foto_yolu`
  deseniyle birebir aynı): her DÖF maddesi kendi tespitine ait tek bir
  kanıt fotoğrafı taşıyabilir (`maddeler` json'undaki her öğeye `foto_yolu`
  eklendi — migration GEREKMEDİ, zaten esnek json sütun). Madde eklerken
  `yeniFoto` (Livewire `WithFileUploads`) dosya seçilirse `dof-foto/`
  diskine kaydedilir.
- **Tatbikat Tutanağı ve Ceza/Tebliğ Tutanağı — GENEL çoklu fotoğraf**
  (AI Saha Analizi'nin çoklu-yükleme deseniyle aynı): bu ikisi madde bazlı
  değil (tatbikatın toplanma yeri/ekip müdahalesi, olay yerinin genel
  görünümü gibi tüm tutanağa ait kanıtlar), bu yüzden her tabloya yeni bir
  `fotograflar` (json path dizisi) sütunu eklendi (migration
  `2026_09_06_230000_...`). `yeniFotograflar` (çoklu `WithFileUploads`) +
  `fotoSil()` ile kaydetmeden önce kaldırılabilir; `kaydet()` sırasında
  `tatbikat-foto/` / `ceza-teblig-foto/` diskine yazılır.
- **PDF çıktıları:** üçünde de Saha Denetimi'ndeki `.foto-sayfa` deseni
  birebir taşındı — her fotoğraf, ana raporun ARDINDAN ayrı, tam sayfalık
  bir "FOTOĞRAF KANITI" sayfası olarak eklenir. Saha Denetimi'nin aksine
  buradakilere açıkça `page-break-before: always` eklendi (orijinalinde
  yoktu, yalnızca resmin boyutu nedeniyle "genelde" ayrı sayfaya taşıyordu
  — burada garantili hâle getirildi, ileride Saha Denetimi'ne de
  uygulanabilir bir iyileştirme).
- **Yan bulunan gerçek hata (düzeltildi):** `CezaTebligTutanagi::
  yaptirimEtiketi(): string` dönüş tipi vardı ama `yaptirim` alanı boşken
  (kullanıcı henüz bir yaptırım seçmemişse) `config(...)`'ten `null` dönüp
  PDF üretiminde `TypeError` ile PATLIYORDU — canlıda da olurdu, yalnızca
  bu oturumda foto testleri yazılırken tesadüfen ortaya çıktı. `?? '—'`
  eklendi.
- **Test:** `DofOlusturTest`'e 2, `TatbikatTutanagiTest`'e 2,
  `CezaTebligTest`'e 2 yeni test (yükleme+kayıt+silme, PDF'de kanıt
  sayfasının gerçekten oluştuğu).

**Kullanıcıya önerilen ek yerler (istenirse ayrı iş olarak eklenebilir,
şimdilik UYGULANMADI):**
- **İş Kazası Raporu** — olay yeri/kaza sonrası durumun fotoğrafı, gerçek
  OSGB pratiğinde neredeyse her zaman istenir; en güçlü aday.
- **Tespit Öneri Defteri** — DÖF ile aynı "tespit" doğasında, aynı
  per-madde fotoğraf deseni doğrudan uygulanabilir.
- Daha düşük öncelikli: İş İzin Formu (çalışma alanı/izin panosu fotoğrafı).

## Notlar

- AI özellikleri (`[AI]` rozetli modüller): sağlayıcı seçimi ileride; ilk etapta
  "istem kopyala / kural tabanlı" iskele, sonra Gemini/OpenAI entegrasyonu (env).
- Abonelik/ödeme: gerçek ödeme entegrasyonu kapsam dışı; `abonelik_*` alanları + kalan
  gün göstergesi yeterli.
- Test: `phpunit.xml` sqlite `:memory:` + `APP_URL=http://localhost`.
