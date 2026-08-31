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

| Grup | Modül | Slug | Durum |
|---|---|---|---|
| **Yönetim** | Kontrol Merkezi (İSG Komuta Merkezi) | `kontrol-merkezi` | planlandı |
| Yönetim | Profilim | `/admin/profile` (özel) | planlandı |
| Yönetim | İSG-KATİP Robot | `isg-katip-robot` | planlandı (Chrome eklentisi — sadece bilgi/stub) |
| **Risk Yönetimi** | Risk Değerlendirme (6 adımlı sihirbaz) | `risk-degerlendirme` | planlandı |
| Risk Yönetimi | Acil Durum Planı | `acil-durum-plani` | planlandı |
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
| — | **Panel (Dashboard)** | `/admin` | planlandı |

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

### Kontrol Merkezi (4.jpg)
- Başlık "İSG Komuta Merkezi — Canlı İSG Takibi".
- Sekmeler: **Günlük Akış** / **Firma Asistanı** / **Çalışan Asistanı**.
- Boş durum: "Harika! Tüm Görevler Güncel — Yaklaşan görevlendirme, evrak, ziyaret,
  eğitim veya tahsilat işlemi bulunmuyor."

### Profilim (5-6.jpg)
- Künye: ad, e-posta, rol rozeti. Butonlar: Mesajlarım, Kaşe Bilgisi, Tanıtım Turu, Nasıl Kullanılır.
- Sayaç: Firmalarım, Çalışanlarım, Risklerim, Notlarım, Önemli Risk, Raporlarım.
- Sekmeler: Genel Bakış / Firmalar / Çalışanlar / Eğitimler / Evrak Takip / Firma Takip /
  Pazarlama / Arşiv / Risklerim / Raporlar.
- Genel Bakış: Uyumluluk Skoru gauge, İlkYardım Sertifikası kartı, Performans Profili,
  Dönemsel Aktivite Trendi (çizgi), Tehlike Sınıfı Dağılımı (donut), Çalışan Dağılımı
  (firma başına), Son 90 Gün Aktivite (heatmap).

### İSG-KATİP Robot (7-9.jpg)
- Chrome eklentisi ile İSG-KATİP portal otomasyonu. "Teknik Gereksinimler", "Kurulum
  Rehberi", "Kontrol Paneli" (bot kartları: Çoklu Atama, Toplu Sözleşme İndir/Onayla/
  Sonlandır, Tıbbi Tetkik / Gezici Sağlık Aracı ataması, Süre Analizi …).
- **Uygulanabilirlik:** gerçek eklenti + resmî portal gerektiği için yalnız **bilgi
  sayfası** + günlük hak sayacı stub'ı olarak kurulacak.

### Risk Değerlendirme sihirbazı (10-18.jpg)
6 adım: **Firma Bilgileri → Ekleme Yöntemi → Risk Ekleme → Tercihler → Risklerim →
Önizleme & PDF**.
- **1. Firma Bilgileri:** firma seç (+ "Risk Değerlendirme Ekibi"), firma künye kartı,
  Rapor Tarihi + Geçerlilik Tarihi (tehlike sınıfına göre otomatik: az 6 / tehlikeli 4 /
  çok tehlikeli 2 yıl), firma logosu (opsiyonel).
- **2. Ekleme Yöntemi:** 5 seçenek — (a) **Yapay Zeka Sohbeti ile Risk Üret** (ÖNERİLEN,
  sektör-spesifik 5-50 soru → 100-300 madde, mevzuat referanslı önlemler), (b) **Manuel
  Seçim** (Risk Kütüphanesi — kategoriler: Fabrika/Atölye/Tersane/Maden/Enerji/… her biri
  N tehlike), (c) **Şablonlar & Paylaşılanlar** (kendi + paylaşılan şablonlar, puanlı),
  (d) **Kayıtlı Risklerim** (klasörlü), (e) **Excel'den Yükle** (.xlsx ≤5MB, çok sayfa).
- Günlük AI hak sayacı ("Bugün 5/5 hak kaldı").

## Veri modeli — ilk taslak

| Model / tablo | Alanlar (özet) |
|---|---|
| `User` / `users` | + `rol` (varsayılan `uzman`), `unvan` (C/B/A sınıfı İGU), `katip_no`, `kase_*`, `telefon`, `abonelik_*` |
| `Firma` / `firmalar` | `user_id`, `unvan`, `sgk_sicil_no`, `vergi_no`, `nace_kodu`, `nace_aciklama`, `tehlike_sinifi`, `adres`, `il`, `ilce`, `calisan_sayisi`, `sozlesme_baslangic/bitis`, `logo`, `aktif` |
| `Calisan` / `calisanlar` | `firma_id`, `ad_soyad`, `tc`, `gorev`, `departman`, `ise_giris`, `dogum_tarihi`, kan grubu, iletişim, `agir_tehlikeli_isde_calisir` |
| `RiskDegerlendirmesi` / `risk_degerlendirmeleri` | `firma_id`, `yontem`, `rapor_tarihi`, `gecerlilik_tarihi`, `belge_no`, `revizyon_no`, künye snapshot, `ekip` (json), `durum` |
| `RiskMaddesi` / `risk_maddeleri` | `risk_degerlendirmesi_id`, `bolum`, `faaliyet`, `tehlike`, `risk`, mevcut O/Ş → skor/düzey, önlemler, sorumlu, termin, rezidüel O/Ş |
| `TehlikeKategorisi` + `Tehlike` | risk kütüphanesi (kategori → tehlike madde) |
| `RiskSablonu` | kayıtlı / paylaşılan şablonlar, puan |
| (diğer modüller kendi modelleriyle eklenecek) |

## Faz planı

- **Faz 0 — Kurulum (bu commit):** Laravel 12 + Filament v5, koyu tema, MySQL `mehse`,
  admin kullanıcı, nav grupları, kök yönlendirme, `MIMARI.md` + `CLAUDE.md`.
- **Faz 1 — Çekirdek:** Firma + Çalışan CRUD (Filament resource), Dashboard iskeleti
  (sayaç kartları), NACE / tehlike sınıfı yardımcıları.
- **Faz 2 — Risk Değerlendirme:** 6 adımlı sihirbaz (özel Filament page), Risk Kütüphanesi,
  RiskDegerlendirmesi + RiskMaddesi, 5×5 / Fine-Kinney skorlama, PDF çıktı.
- **Faz 3+:** Kullanıcı ekran görüntülerini ekledikçe ilgili modül (DÖF, Saha Denetimi,
  Eğitim Katılım, Atama Yazıları, Tatbikat, KKD, İş İzni, İş Kazası, Talimat, Yıllık Plan,
  Ziyaret Programı, Kontrol Merkezi, Profilim …).

## Notlar

- AI özellikleri (`[AI]` rozetli modüller): sağlayıcı seçimi ileride; ilk etapta
  "istem kopyala / kural tabanlı" iskele, sonra Gemini/OpenAI entegrasyonu (env).
- Abonelik/ödeme: gerçek ödeme entegrasyonu kapsam dışı; `abonelik_*` alanları + kalan
  gün göstergesi yeterli.
- Test: `phpunit.xml` sqlite `:memory:` + `APP_URL=http://localhost`.
