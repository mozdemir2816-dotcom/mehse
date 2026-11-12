-- Karacabey ortak çalışan temizliği (12.11.2026)
-- 24 kişi hem firma 21 (KARACABEY ORMAN İŞLETME MÜDÜRLÜĞÜ, asıl görevleriyle) hem firma 22
-- (DÖNER SERMAYE, hepsi "ORMAN İŞÇİSİ") altında kayıtlı. 02.09.2026'daki içe aktarmada 7 dk arayla
-- iki kez yüklenmiş. Firma 22'deki kopyalar hiçbir kayda bağlı değil (12.11.2026 00:02 yedeğinde
-- kontrol edildi); kurul üyeliği gibi bağlantılar firma 21'deki kayıtlarda.
--
-- Silinen: yalnız firma 22'de olup aynı TC ile firma 21'de de bulunan, görevi ORMAN İŞÇİSİ olan
-- ve hiçbir tabloda kullanılmayan çalışan kayıtları. Calisanlar tablosunda çöp kutusu yok — silme kalıcıdır
-- (günlük yedekte durur).

-- 1) ÖNCE BAKIN: beklenen 24 satır
SELECT b.id, b.ad_soyad, b.gorev, a.gorev AS isletme_mudurlugu_gorevi
FROM calisanlar b
JOIN calisanlar a ON a.tc = b.tc AND a.firma_id = 21
WHERE b.firma_id = 22 AND b.tc <> '' AND b.gorev = 'ORMAN İŞÇİSİ';

-- 2) SİL (bağlı kaydı olanlar korunur)
DELETE b FROM calisanlar b
JOIN calisanlar a ON a.tc = b.tc AND a.firma_id = 21
WHERE b.firma_id = 22 AND b.tc <> '' AND b.gorev = 'ORMAN İŞÇİSİ'
  AND NOT EXISTS (SELECT 1 FROM acil_ekip_uyeleri x WHERE x.calisan_id = b.id)
  AND NOT EXISTS (SELECT 1 FROM egitim_atamalari x WHERE x.calisan_id = b.id)
  AND NOT EXISTS (SELECT 1 FROM egitim_girisleri x WHERE x.calisan_id = b.id)
  AND NOT EXISTS (SELECT 1 FROM egitim_kayitlari x WHERE x.calisan_id = b.id)
  AND NOT EXISTS (SELECT 1 FROM ise_donus_belgeleri x WHERE x.calisan_id = b.id)
  AND NOT EXISTS (SELECT 1 FROM is_kazasi_raporlari x WHERE x.calisan_id = b.id)
  AND NOT EXISTS (SELECT 1 FROM kkd_zimmetleri x WHERE x.calisan_id = b.id)
  AND NOT EXISTS (SELECT 1 FROM kurul_uyeleri x WHERE x.calisan_id = b.id)
  AND NOT EXISTS (SELECT 1 FROM meslek_hastaligi_bildirimleri x WHERE x.calisan_id = b.id)
  AND NOT EXISTS (SELECT 1 FROM muayene_formlari x WHERE x.calisan_id = b.id)
  AND NOT EXISTS (SELECT 1 FROM olay_kayitlari x WHERE x.calisan_id = b.id);

-- 3) DOĞRULAMA: beklenen  ortak_tc = 0 | firma21 = 36 | firma22 = 29
SELECT
  (SELECT COUNT(*) FROM (SELECT tc FROM calisanlar WHERE firma_id IN (21,22) AND tc <> '' GROUP BY tc HAVING COUNT(DISTINCT firma_id) > 1) x) AS ortak_tc,
  (SELECT COUNT(*) FROM calisanlar WHERE firma_id = 21) AS firma21,
  (SELECT COUNT(*) FROM calisanlar WHERE firma_id = 22) AS firma22;
