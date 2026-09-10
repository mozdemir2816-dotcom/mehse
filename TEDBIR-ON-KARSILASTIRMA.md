# TEDBİR ON ↔ mehse Karşılaştırma ve Eksik Modül Raporu

**Tarih:** 10.09.2026
**Kaynak:** `C:\Users\mozde\Desktop\Yeni klasör\` — TEDBİR ON İSG Yönetim Platformu v4.21 (9 ekran görüntüsü, LinkedIn paylaşımı — Sercan Fehmi Bahar / Egeli OSGB)
**Kıyas:** mehse İSG (bu proje) — ~45 modül

TEDBİR ON masaüstü/Excel tabanlı, tek OSGB kullanımına yönelik bir araç. mehse ile
modül kapsamı büyük ölçüde örtüşüyor. Aşağıda ekran ekran karşılaştırma, ardından
bu oturumda eklenenler ve öncelik sıralı öneriler var.

---

## Bu oturumda EKLENENLER

| Modül | Ne yapar | Dosyalar |
|-------|----------|----------|
| **Ortam Ölçümleri Takibi** (`/admin/ortam-olcumleri`, Planlama & Arşiv) | Gürültü, toz, aydınlatma, termal konfor, kimyasal maruziyet vb. ölçümler; ölçüm tarihi + periyot → sonraki ölçüm otomatik; ölçülen/sınır değer karşılaştırması; A4 yatay takip listesi PDF | `OrtamOlcumu` modeli, `OrtamOlcumleri` sayfası, `OrtamOlcumuUretici`, `config isg.ortam_olcum` (4 grup ~22 parametre kataloğu), migration `2026_09_10_150000`, `OrtamOlcumleriTest` (7 test) |
| **Kaza İstatistikleri** (`/admin/kaza-istatistikleri`, Planlama & Arşiv) | Firma × yıl. İş Kazası Raporları + Olay Kayıtları (iş kazası tipi) otomatik hesaba katılır + elle "harici kaza"; aylık çalışan/çalışma saati → **Kaza Sıklık Oranı** ve **Kaza Ağırlık Oranı** (Türkiye ×1.000.000 / OSHA ×200.000); PDF + Excel rapor | `KazaIstatistigi` modeli, `KazaIstatistikleri` sayfası, `KazaIstatistigiUretici` (pdf+excel), `config isg.kaza_istatistik`, migration `2026_09_10_160000`, `KazaIstatistikleriTest` (9 test) |

Her ikisi de `raporlar.kaynaklar`'a eklendi (Profilim > Raporlar + Firma Evrak paketinde görünür). Migration'lar dev DB'ye çalıştırıldı.

---

## Ekran ekran karşılaştırma

### 1. Firmalar / Personel
| TEDBİR ON | mehse | Durum |
|-----------|-------|-------|
| Firma Yönetimi, Arşivlenen Firmalar | Firmalar (arşiv dahil) | ✅ Var |
| Personel | Çalışanlar | ✅ Var |
| Hazırlanmış Belgeler / Evrak Teslim Tutanağı | Firma Evrak ZIP + Raporlar | ✅ Var |
| Tespit Öneri | Tespit ve Öneri Defteri | ✅ Var |
| Firma Veri Giriş / Firma Takip | KontrolMerkezi + FirmaResource | 🟡 Kısmen (firma bazlı derin görünüm zayıf) |
| **Sağlık Tetkikleri** | MuayeneFormu (EK-2) var, **takip matrisi yok** | 🔴 **Eksik** |
| MOBİL SAHA VERİSİNİ İÇERİ AKTAR | AI Saha Analizi + Saha Denetimi foto | 🟡 Mobil app yok (PWA bekliyor) |

### 2. Eğitimler
| TEDBİR ON | mehse | Durum |
|-----------|-------|-------|
| Temel İSG Takibi (kişi bazlı yenileme, kalan gün, GEÇERLİ/90 GÜN İÇİNDE) | Profilim > Eğitimler matrisi (`EgitimKaydi` + `durum()`) | 🟡 Var ama görünürlüğü zayıf; "kalan gün" sütunu / firma bazlı ekran yok |
| Yüksekte / Yangın / İş Kazası İşe Dönüş eğitimi | EgitimKatilim (özel başlıklar) + Sertifika | ✅ Var (İşe Dönüş **belgesi** hariç) |
| Toplu / Manuel Sertifika | SertifikaOlustur + Yıldız Grup + Uzaktan | ✅ Var |
| Katılım Formları | EgitimKatilim + boş form + İşbaşı | ✅ Var |
| **İş Kazası Sonrası İşe Dönüş Belgesi** | — | 🔴 **Eksik** (küçük belge) |

### 3. Risk Analizleri
| TEDBİR ON | mehse | Durum |
|-----------|-------|-------|
| 5X5 / Fine-Kinney Risk Analizi | RiskDegerlendirmesi (her ikisi) + RiskSihirbazı | ✅ Var |
| Risk Analizi Kütüphanesi | RiskSablonu + RiskKütüphanesi (Tehlike) + JSA | ✅ Var (daha güçlü) |
| **5X5 Proses / Fine-Kinney Proses** | — | 🟡 Proses (süreç akışı) ayrımı yok — düşük öncelik |
| **KKD Risk Analizi** (iş × KKD seçim matrisi) | KKD Zimmet var, **seçim matrisi yok** | 🔴 **Eksik** (`İNŞAAT KKD MATRİKSİ.xlsx` hazır) |
| **Kimyasal Risk Analizi** (kontrol bantlama) | Kimyasal Sicili (envanter + SDS) var, **risk değerlendirmesi yok** | 🔴 **Eksik** |

### 4. Planlar ve Değerlendirme
| TEDBİR ON | mehse | Durum |
|-----------|-------|-------|
| Yıllık Çalışma / Eğitim Planı | YillikPlanlar | ✅ Var |
| Yıllık Değerlendirme | YillikDegerlendirme | ✅ Var |
| İSG Kurulu / Kurul Takibi | KurulToplantisi (toplantı no + geçmiş) | 🟡 "Takip" (yaklaşan toplantı) zayıf |
| **Ortam Ölçümleri + Kayıtları** | ✅ **BU OTURUMDA EKLENDİ** | ✅ Yeni |
| Yangın Tatbikat Raporu | TatbikatTutanagi | ✅ Var |
| Acil Durum Planı | AcilDurumPlani (senaryo havuzu + Word) | ✅ Var |
| Tahliye Planı / Kroki | AcilDurumKrokisi | ✅ Var |

### 5. Atama ve Takip
| TEDBİR ON | mehse | Durum |
|-----------|-------|-------|
| Atama Yazıları / Talimatlar | AtamaYazilari + TalimatOlustur | ✅ Var |
| Periyodik Kontrol Takibi + Arşivi | PeriyodikKontrol | ✅ Var |
| Makine ve Ekipman | PeriyodikKontrol kataloğu | ✅ Var |
| İSG İç Denetim | SahaDenetimi (saha gözetimi) | 🟡 45001 tarzı yönetim sistemi iç denetim listesi yok |
| **Yangın Güvenliği Genel Durum Değerlendirmesi** (bina yangın sınıfı + EK-1 + Word rapor) | — | 🔴 **Eksik** (orta-büyük modül) |

### 6. Uzman Özeti — Firma Çalışma Merkezi
| TEDBİR ON | mehse | Durum |
|-----------|-------|-------|
| Firma bazlı yasal çalışma/belge durumu, **Belge / İmza / Firmada 3-durum**, yenileme tarihleri | KontrolMerkezi (portföy geneli kriter matrisi) + PortfoyKarne | 🟡 Firma bazlı derin görünüm + 3-durum yok |
| **İçindekiler Listesi (Dosya Fihristi) Oluştur** | — | 🔴 **Eksik** (küçük) |
| Dosya Konumu Bağla | — | 🟡 Web tabanlı — gerekmez |

### 7. İş Kazaları
| TEDBİR ON | mehse | Durum |
|-----------|-------|-------|
| İş Kazası Tutanağı / Kayıtlı Kazalar | IsKazasiRaporu + OlayKayitlari | ✅ Var |
| 5 Neden Analizi | OlayKaydi `bes_neden` + kök neden | ✅ Var |
| **Balık Kılçığı (Ishikawa) Analizi** | — (5N var, 6M balık kılçığı görseli yok) | 🔴 **Eksik** (küçük-orta) |
| **Kaza İstatistikleri** | ✅ **BU OTURUMDA EKLENDİ** | ✅ Yeni |

### 8-11. Diğer
| TEDBİR ON | mehse | Durum |
|-----------|-------|-------|
| Uygunsuzluklar (DÖF) | DofOlustur | ✅ Var |
| Mevzuat | Mevzuat sayfası | ✅ Var |
| **Şablonlar — Şablon Editörü / Profil sistemi** (belge türü başına Word şablonu seç: SECO orijinal / logolu / özel) | Şablonlar sabit (birkaçı gerçek dosya: Yıldız Grup xlsx, Acil Durum/Atama Word) | 🟡 Kullanıcının kendi şablonunu belge türüne bağlaması sınırlı |
| Sistem (güncelleme, dağıtım) | Web tabanlı | — Gerekmez |

---

## Öncelik sıralı öneriler (kalan eksikler)

| # | Öneri | Boyut | Not |
|---|-------|-------|-----|
| 1 | ✅ **Kaza İstatistikleri** | — | **YAPILDI** (10.09) |
| 2 | ✅ **Ortam Ölçümleri** | — | **YAPILDI** (10.09) |
| 3 | ✅ **Sağlık Gözetimi Takibi** — çalışan × 14 tetkik türü, periyot/tehlike sınıfından sonraki tetkik, "tüm çalışanlara ekle" | — | **YAPILDI** (11.09) |
| 4 | ✅ **Balık Kılçığı (Ishikawa) PDF eki** — OlayKaydi 6M, "5N/kök nedenlerden doldur" | — | **YAPILDI** (11.09) |
| 5 | ⏳ **Eğitim/Sağlık matrisine "kalan gün" + yaklaşan/dolmuş vurgusu** | S | Sağlık Gözetimi'nde kırmızı vurgu var; Profilim > Eğitimler matrisine kalan gün sütunu kaldı |
| 6 | ✅ **KKD Seçim Matrisi** — iş kalemi × 9 KKD sütunu | — | **YAPILDI** (11.09) |
| 7 | ✅ **Kimyasal Risk Değerlendirmesi** — COSHH Essentials kontrol bantlama (1-4) | — | **YAPILDI** (11.09) |
| 8 | ⏳ **Firma bazlı Çalışma Merkezi + Dosya Fihristi** — FirmaResource'a "Çalışma Durumu" sekmesi (Belge/İmza/Teslim 3-durum) + fihrist PDF | M | KontrolMerkezi mantığını firma bazına indir |
| 9 | ⏳ **İş Kazası Sonrası İşe Dönüş Belgesi** — kaza sonrası hekim onayı + iş kısıtları belgesi | S | IsKazasiRaporu'na bağlı küçük belge |
| 10 | ⏳ **Yangın Güvenliği Değerlendirme Raporu** — bina kullanım türü + kat/kullanıcı yükü + EK-1 faaliyet + bölüm/risk checklist → yangın tehlike sınıfı + rapor | M/L | Standart "BEÇ" benzeri; en büyük tek eksik |
| 11 | ⏳ **Şablon profil sistemi** — "logolu/kurumlu" varyant + birkaç belge için özel Word yükleme | M | Tek kullanıcıda düşük öncelik |
| 12 | ⏳ **İç Denetim Kontrol Listesi** (ISO 45001 tarzı, SahaDenetimi'nden ayrı) | M | Düşük öncelik |

## Ayrıca — isgpratik'e göre yeniden yazılan ekranlar (11.09)

**Periyodik Kontrol** (`Desktop\isgpratik\iş ekipmanları\`): "Ekipman & Periyodik
Kontrol Motoru"na dönüştürüldü — ayrı `IsEkipmani` modeli, 5 yasal kategori × tip
kataloğu (her tip için TS/EN standart + deney + periyot), "Yeni Ekipman Tanımla"
bağımlı-select modal, 4 KPI kartı (Vizesi Geçerli / Yaklaşan / Süresi Dolan), kategori
sekmeleri + arama + durum filtresi, "Müfettiş Teftiş Paketi" PDF + Excel.

**İş Kazası Raporu** (`Desktop\isgpratik\iş kazası\`): "İş Kazası İnceleme ve Kök
Neden Analiz Raporu" — 6 bölümlü akış: Genel Bilgiler → 5 Neden (sabit sorular) →
Balık Kılçığı 6M (örnek bulgu chip'leri) → DÖF tablosu → Foto & Notlar → Önizleme
(3 stat + eksik alan). "Taslak Kaydet" / "Raporu Tamamla". Yeni comprehensive PDF.

**Not:** Kaza İstatistikleri ve Ortam Ölçümleri için Kontrol Merkezi'ne yeni kriter
EKLENMEDİ (uygulanabilirlik firmaya göre değişir + oran testlerini etkiler). İstenirse
`ortam_olcum_raporu` koşullu kriter olarak eklenebilir.
