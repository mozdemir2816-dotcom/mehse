# mehse İSG — Mimari & Yol Haritası

Tek kişilik İş Güvenliği Uzmanı için İSG operasyon paneli. Referans ekran
görüntüleri: `C:\Users\mozde\Desktop\isgpratik\` (kullanıcı ekledikçe artıyor).

> **Kural:** `isgpratik` ekranları referanstır; kod/marka/metin birebir kopyalanmaz,
> **modül ve akış** taklit edilir. Önceki projelerden (yeni-proje, katip-isg) **kod
> alınmaz** — sıfırdan.

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
| Yönetim | Profilim | `profilim` | **hazır** (künye + sayaçlar + 6 sekme) |
| Yönetim | İSG-KATİP Robot | `isg-katip-robot` | **hazır** (bilgi sayfası — gerçek eklenti yok, stub) |
| **Risk Yönetimi** | Risk Değerlendirme (6 adımlı sihirbaz) | `risk-degerlendirme` | **hazır** — Manuel + Yapay Zeka (Gemini) yöntemleri + PDF çıktısı; Kayıtlı/Excel kalan |
| Risk Yönetimi | Kayıtlı Değerlendirmeler | `risk-degerlendirmelerim` | **hazır** |
| Risk Yönetimi | Sektör Şablonları | `risk-sablonlari` | **hazır** (sihirbazdan oluşur, burada yönetilir) |
| Risk Yönetimi | Risk Kütüphanesi | `risk-kutuphanesi` | **hazır** |
| Risk Yönetimi | Acil Durum Planı | `acil-durum-plani` | **hazır** (firma + konu seçimi → PDF + 7 afiş) |
| **Formlar & Belgeler** | DÖF Oluştur `[AI]` | `dof` | planlandı |
| Formlar & Belgeler | AI Saha Analizi `[AI]` | `ai-saha-analizi` | planlandı |
| Formlar & Belgeler | Saha Denetimi | `saha-denetimi` | planlandı |
| Formlar & Belgeler | Kurul Toplantısı `[AI]` | `kurul-toplantisi` | **hazır** (toplantı + katılımcı + gündem + AI karar önerisi + PDF tutanak) |
| Formlar & Belgeler | Atama Yazıları | `atama-yazilari` | **hazır** (10 görev tipi, tekli/ekip → PDF) |
| Formlar & Belgeler | Eğitim Katılım | `egitim-katilim` | **hazır** (Genel/Sağlık/Teknik/İşyerine Özgü + 13 özel başlık + katılımcı listesi → PDF) |
| Formlar & Belgeler | İşbaşı Eğt. Tutanağı | `isbasi-egitim` | planlandı |
| Formlar & Belgeler | Tatbikat Tutanağı | `tatbikat` | planlandı |
| Formlar & Belgeler | Tespit Öneri Defteri | `tespit-oneri-defteri` | **hazır** (hazır katalog + serbest/AI destekli madde → PDF) |
| Formlar & Belgeler | Sertifika Oluştur | `sertifika` | planlandı |
| Formlar & Belgeler | Eğitim Soruları `[AI]` | `egitim-sorulari` | **hazır** (Gemini ile 10 soruluk sınav + manuel soru → PDF) |
| Formlar & Belgeler | KKD Formu | `kkd-formu` | **hazır** (6 kategori TS EN katalog, çoklu çalışan × çoklu KKD → PDF) |
| Formlar & Belgeler | İş İzin Formu | `is-izin-formu` | **hazır** (4 izin türü, dinamik güvenlik önlemleri → PDF) |
| Formlar & Belgeler | Ceza ve Tebliğ Tutanağı | `ceza-teblig` | **hazır** (ihlal kataloğu + serbest madde + yaptırım → PDF) |
| Formlar & Belgeler | İş Kazası Raporu | `is-kazasi-raporu` | planlandı |
| Formlar & Belgeler | Talimat Oluştur `[AI]` | `talimat` | **hazır** (30 hazır şablon + Gemini ile madde üretimi → PDF) |
| Formlar & Belgeler | Muayene Formu (EK-2) | `muayene-formu` | planlandı |
| Formlar & Belgeler | Ücretsiz E-Reçetem | `e-recetem` | planlandı |
| **Planlama & Arşiv** | Yıllık Planlar | `yillik-planlar` | planlandı |
| Planlama & Arşiv | Ziyaret Programı `[AI]` | `ziyaret-programi` | planlandı |
| Planlama & Arşiv | Araçlar | `araclar` | planlandı |
| — | **Panel (Dashboard)** | `/admin` | iskele (`PortföyÖzetiWidget`) |
| Yönetim | Firmalar | `firmalar` | **hazır** (Çalışanlar RelationManager dâhil) |
| Yönetim | Çalışanlar | `calisanlar` | **hazır** |

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

### Profilim (5, 137-147.jpg) — **kuruldu, bkz. Faz 3e**
- Künye: ad, e-posta, ünvan rozeti, Aktif rozeti, "Hesap Ayarları" (Filament profil).
- Sayaç: Firmalarım / Çalışanlarım / Risk Değerlendirmesi / Risk Şablonları / Önemli Risk /
  Raporlarım.
- Sekmeler: **Genel Bakış / Firmalar / Çalışanlar / Firma Takip / Risklerim / Diğer**.
  (isgpratik ayrıca Eğitimler 139-140, Evrak Takip 141, Pazarlama 143, Arşiv 144,
  Raporlar 146, Firma Ziyaretleri 147 sekmelerini gösterir → "Diğer" sekmesinde
  bekleyen liste; ilgili model gelince açılır.)
- Genel Bakış: Uyumluluk Skoru (conic-gradient gauge), Tehlike Sınıfı Dağılımı (bar),
  İlk Yardım bilgi kartı. Performans/trend/heatmap ileride.
- Firma Takip: firma × 12 yasal kriter matrisi (isgpratik 141-142 —
  `PortfoyKarne::firmaKriterMatrisi`).

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
  - **Kalan:** Kayıtlı Risklerim (klasörlü); `RiskSablonu` `maddeler` düzenleme
    (repeater); İnşaat gibi sektörlerde alt-faaliyet (Kazı/Kalıp/İskele/Çatı/
    Zemin İyileştirme) çoklu-seçim arayüzü (kullanıcı Tehlike Kütüphanesi'ne
    kendi kategorilerini yükledikçe kurulacak); sektörel "orijinal şablon" Word
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
  **Kapsam dışı:** isgpratik'in ikinci sekmesi "İşverene İPC Tebliği" (idari para cezası
  bildirimi, ayrı bir belge akışı) — yalnız "Çalışana Ceza Tutanağı" akışı yapıldı.
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
- **Faz 3+:** Kullanıcı ekran görüntülerini ekledikçe ilgili modül (DÖF, AI Saha Analizi,
  Saha Denetimi, Eğitim Katılım'a bağlı İşbaşı Eğt. Tutanağı, Tatbikat Tutanağı,
  Sertifika Oluştur, İş Kazası Raporu, Muayene Formu (EK-2 — çok büyük, tıbbi muayene
  formu), Ücretsiz E-Reçetem, Yıllık Planlar, Ziyaret Programı, Araçlar; "İşverene İPC
  Tebliği" — Ceza ve Tebliğ'in ikinci sekmesi). isgpratik kök klasöründe
  24-101(+133-135,158) numaralı ekran görüntüleri bu modüllere karşılık geliyor —
  kısmen incelendi (24-44, 60-87 görüldü: DÖF/AI Saha Analizi/Saha Denetimi/Kurul
  Toplantısı(✅)/Atama Yazıları(✅)/İşbaşı Eğt./Tatbikat/Tespit Öneri Defteri(✅)/Sertifika
  Oluştur/Eğitim Soruları(✅)/KKD(✅)/İş İzin(✅)/Ceza Tebliğ(✅)/İş Kazası Raporu/Talimat
  Oluştur(✅)/Muayene Formu (çok büyük, ertelendi)/Yıllık Planlar — bu sonuncusu için
  tam faaliyet listesi + ay bazlı durum matrisi görüldü, henüz kurulmadı), 88-101+
  133-135+158 henüz incelenmedi. Her biri Eğitim Katılım/Atama Yazıları/Kurul Toplantısı
  ile aynı desende (config-driven içerik + Filament Page + dompdf + test) tek tek
  kurulacak.

## Notlar

- AI özellikleri (`[AI]` rozetli modüller): sağlayıcı seçimi ileride; ilk etapta
  "istem kopyala / kural tabanlı" iskele, sonra Gemini/OpenAI entegrasyonu (env).
- Abonelik/ödeme: gerçek ödeme entegrasyonu kapsam dışı; `abonelik_*` alanları + kalan
  gün göstergesi yeterli.
- Test: `phpunit.xml` sqlite `:memory:` + `APP_URL=http://localhost`.
