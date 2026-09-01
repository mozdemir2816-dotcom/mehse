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
| **Risk Yönetimi** | Risk Değerlendirme (6 adımlı sihirbaz) | `risk-degerlendirme` | **hazır** — Manuel + Yapay Zeka (kural tabanlı) yöntemleri; Şablon/Kayıtlı/Excel + PDF Faz 3b |
| Risk Yönetimi | Kayıtlı Değerlendirmeler | `risk-degerlendirmelerim` | **hazır** |
| Risk Yönetimi | Sektör Şablonları | `risk-sablonlari` | **hazır** (sihirbazdan oluşur, burada yönetilir) |
| Risk Yönetimi | Risk Kütüphanesi | `risk-kutuphanesi` | **hazır** |
| Risk Yönetimi | Acil Durum Planı | `acil-durum-plani` | **hazır** (firma + konu seçimi → PDF + 7 afiş) |
| **Formlar & Belgeler** | DÖF Oluştur `[AI]` | `dof` | planlandı |
| Formlar & Belgeler | AI Saha Analizi `[AI]` | `ai-saha-analizi` | planlandı |
| Formlar & Belgeler | Saha Denetimi | `saha-denetimi` | planlandı |
| Formlar & Belgeler | Kurul Toplantısı `[AI]` | `kurul-toplantisi` | planlandı |
| Formlar & Belgeler | Atama Yazıları | `atama-yazilari` | planlandı |
| Formlar & Belgeler | Eğitim Katılım | `egitim-katilim` | planlandı |
| Formlar & Belgeler | İşbaşı Eğt. Tutanağı | `isbasi-egitim` | planlandı |
| Formlar & Belgeler | Tatbikat Tutanağı | `tatbikat` | planlandı |
| Formlar & Belgeler | Tespit Öneri Defteri | `tespit-oneri-defteri` | planlandı |
| Formlar & Belgeler | Sertifika Oluştur | `sertifika` | planlandı |
| Formlar & Belgeler | Eğitim Soruları `[AI]` | `egitim-sorulari` | planlandı |
| Formlar & Belgeler | KKD Formu | `kkd-formu` | planlandı |
| Formlar & Belgeler | İş İzin Formu | `is-izin-formu` | planlandı |
| Formlar & Belgeler | Ceza ve Tebliğ Tutanağı | `ceza-teblig` | planlandı |
| Formlar & Belgeler | İş Kazası Raporu | `is-kazasi-raporu` | planlandı |
| Formlar & Belgeler | Talimat Oluştur `[AI]` | `talimat` | planlandı |
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
- **Faz 2 — Risk Değerlendirme çekirdeği ✅ (commit sonrası):**
  - `config/isg.php` → `risk_yontemleri`, `risk_matris_5x5` / `risk_fine_kinney` (ölçek
    metinleri + puan → düzey bantları; **Fine-Kinney ondalık anahtarlar string**),
    `risk_madde_durumlari`.
  - `App\Support\RiskSkorlama::hesapla($yontem, O, Ş, ?F)` — 5×5 (O×Ş) ve Fine-Kinney
    (O×F×Ş); `bant()`, `olcek()`.
  - `TehlikeKategorisi` + `Tehlike` (Risk Kütüphanesi) + `TehlikeKutuphanesiSeeder`
    (3 kategori / 13 tehlike başlangıç seti). `TehlikeResource` (kategoriye göre gruplu tablo).
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
    **PDF butonu "yakında"** (Faz 3b).
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
    yerine doğrudan o dosya indirilir (örn. sabotaj).
  - `barryvdh/laravel-dompdf` ilk kez kullanıldı; PDF blade'leri `resources/views/pdf/`.
  `AcilDurumPlaniTest` (8 test).
  - **Gemini (gerçek LLM) entegrasyonu ✅:** `App\Support\GeminiRiskDanismani` —
    Risk Sihirbazı AI adımında sektör + alt kategori + sohbet cevaplarıyla Gemini
    `generateContent` REST API'sine `responseSchema` (structured JSON output) ile
    istek atar; `RiskUretici::uret()` çıktısını kural tabanlı + kütüphane
    adaylarına `kaynak=llm` olarak ekler (aynı şema, mükerrer tehlike metni elenir).
    `config('services.gemini.key')` tanımlı değilse veya istek başarısız
    olursa/istisna atarsa sessizce boş dizi döner — sihirbaz kural tabanlı
    sonuçlarla çalışmaya devam eder. `.env`: `GEMINI_API_KEY` / `GEMINI_MODEL`
    (varsayılan `gemini-2.5-flash`). **Bilinçli tercih:** LLM önerileri, kütüphane
    önerileri gibi varsayılan olarak SEÇİLİ GELMİYOR — mevzuat riski taşıyan bir
    belgede LLM çıktısını sessizce onaylatmamak için uzman gözden geçirip seçmeli.
    `GeminiRiskDanismaniTest` (5 test, `Http::fake`).
  **90 test toplam.**
  - **Faz 3b (kalan):** Risk PDF çıktısı (kapak → prosedür → tablo → ekip);
    Kayıtlı Risklerim (klasörlü); Excel içe/dışa aktarma (yüklerken sektör sorulup
    şablona kaydedilecek); `RiskSablonu` `maddeler` düzenleme (repeater).
- **Faz 3+:** Kullanıcı ekran görüntülerini ekledikçe ilgili modül (DÖF, Saha Denetimi,
  Eğitim Katılım, Atama Yazıları, Tatbikat, KKD, İş İzni, İş Kazası, Talimat, Yıllık Plan,
  Ziyaret Programı, Kontrol Merkezi, Profilim …).

## Notlar

- AI özellikleri (`[AI]` rozetli modüller): sağlayıcı seçimi ileride; ilk etapta
  "istem kopyala / kural tabanlı" iskele, sonra Gemini/OpenAI entegrasyonu (env).
- Abonelik/ödeme: gerçek ödeme entegrasyonu kapsam dışı; `abonelik_*` alanları + kalan
  gün göstergesi yeterli.
- Test: `phpunit.xml` sqlite `:memory:` + `APP_URL=http://localhost`.
