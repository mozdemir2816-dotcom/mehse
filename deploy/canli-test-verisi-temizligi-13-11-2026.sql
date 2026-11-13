-- Test verisi kalıntılarının temizliği (13.11.2026 sistem denetimi).
-- Yalnız birebir test girişi olduğu belli olan kayıtlar silinir; her satır adı + firma + belge no ile eşleştirilir
-- (canlıda farklı bir kayıt varsa silinmez). Silinenler önce `_yedek_test_verisi_13_11` tablosuna kopyalanır.
-- phpMyAdmin > mehse veritabanı > SQL. Tekrar çalıştırmak güvenlidir.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `_yedek_test_verisi_13_11` (`tablo` varchar(64), `kayit_id` bigint unsigned, `veri` longtext, `tarih` timestamp DEFAULT CURRENT_TIMESTAMP);

-- 1) Anlamsız adlı atama yazıları (ABDULLETİF YALÇINKAYA ve AHMET BULUT): DFSD, XVVDBDG, SDFGFDSGDF, hasan ekmek, FGDGDG
--    (TC alanlarında 5–10 haneli uydurma numaralar var). Ana sayfada "çalışan temsilcisi eğitimi verilmeli" görevi bunlardan çıkıyordu.
INSERT INTO `_yedek_test_verisi_13_11` (`tablo`,`kayit_id`,`veri`)
SELECT 'atama_yazilari', `id`, CONCAT_WS(' | ', `firma_id`, `dokuman_no`, `rol_anahtari`, `tarih`, `uyeler`) FROM `atama_yazilari`
WHERE (`firma_id`, `dokuman_no`) IN ((2,'ATM-2026-004'),(1,'ATM-2026-006'),(1,'ATM-2026-007'),(1,'ATM-2026-008'),(1,'ATM-2026-009'))
  AND (`uyeler` LIKE '%"FGDGDG"%' OR `uyeler` LIKE '%"DFSD"%' OR `uyeler` LIKE '%"XVVDBDG"%' OR `uyeler` LIKE '%"SDFGFDSGDF"%' OR `uyeler` LIKE '%"hasan ekmek"%')
  AND NOT EXISTS (SELECT 1 FROM `_yedek_test_verisi_13_11` y WHERE y.`tablo` = 'atama_yazilari' AND y.`kayit_id` = `atama_yazilari`.`id`);
DELETE FROM `atama_yazilari`
WHERE (`firma_id`, `dokuman_no`) IN ((2,'ATM-2026-004'),(1,'ATM-2026-006'),(1,'ATM-2026-007'),(1,'ATM-2026-008'),(1,'ATM-2026-009'))
  AND (`uyeler` LIKE '%"FGDGDG"%' OR `uyeler` LIKE '%"DFSD"%' OR `uyeler` LIKE '%"XVVDBDG"%' OR `uyeler` LIKE '%"SDFGFDSGDF"%' OR `uyeler` LIKE '%"hasan ekmek"%');

-- 2) Silinmiş firmadaki (GANA MÜHENDİSLİK) test çalışanı "asdasdfsf"
INSERT INTO `_yedek_test_verisi_13_11` (`tablo`,`kayit_id`,`veri`)
SELECT 'calisanlar', `id`, CONCAT_WS(' | ', `firma_id`, `ad_soyad`, `tc`, `gorev`) FROM `calisanlar`
WHERE `ad_soyad` = 'asdasdfsf' AND `gorev` = 'fsdfdsfsdf'
  AND NOT EXISTS (SELECT 1 FROM `_yedek_test_verisi_13_11` y WHERE y.`tablo` = 'calisanlar' AND y.`kayit_id` = `calisanlar`.`id`);
DELETE FROM `calisanlar` WHERE `ad_soyad` = 'asdasdfsf' AND `gorev` = 'fsdfdsfsdf';

-- 3) Aynı firmada iki kez kayıtlı çalışan: İSMAİL ÖZHELVACI İNŞAAT'ta "Melek Arda" (aynı TC). Eski kayıt kalır;
--    yeni kopya, hiçbir eğitim / zimmet / kurul vb. kaydına bağlı değilse silinir.
INSERT INTO `_yedek_test_verisi_13_11` (`tablo`,`kayit_id`,`veri`)
SELECT 'calisanlar', c.`id`, CONCAT_WS(' | ', c.`firma_id`, c.`ad_soyad`, c.`tc`, c.`gorev`) FROM `calisanlar` c
WHERE c.`tc` = '50923629512' AND EXISTS (SELECT 1 FROM `calisanlar` o WHERE o.`firma_id` = c.`firma_id` AND o.`tc` = c.`tc` AND o.`id` < c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `acil_ekip_uyeleri` x WHERE x.`calisan_id` = c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `egitim_atamalari` x WHERE x.`calisan_id` = c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `egitim_girisleri` x WHERE x.`calisan_id` = c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `egitim_kayitlari` x WHERE x.`calisan_id` = c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `ise_donus_belgeleri` x WHERE x.`calisan_id` = c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `is_kazasi_raporlari` x WHERE x.`calisan_id` = c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `kkd_zimmetleri` x WHERE x.`calisan_id` = c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `kurul_uyeleri` x WHERE x.`calisan_id` = c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `meslek_hastaligi_bildirimleri` x WHERE x.`calisan_id` = c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `muayene_formlari` x WHERE x.`calisan_id` = c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `olay_kayitlari` x WHERE x.`calisan_id` = c.`id`)
  AND NOT EXISTS (SELECT 1 FROM `_yedek_test_verisi_13_11` y WHERE y.`tablo` = 'calisanlar' AND y.`kayit_id` = c.`id`);
DELETE c FROM `calisanlar` c
JOIN `_yedek_test_verisi_13_11` y ON y.`tablo` = 'calisanlar' AND y.`kayit_id` = c.`id`
WHERE c.`tc` = '50923629512';

-- 4) ABDULLETİF YALÇINKAYA çalışan temsilcisi seçimi (ÇT-2026-01) — kullanıcı onayı 13.11.2026: test kaydı.
--    Adaylar "MEHMET ÖZDEMİR" ve "AYŞE YILMAZ". Silinince Kontrol Merkezi bu firmada temsilciyi eksik gösterir.
INSERT INTO `_yedek_test_verisi_13_11` (`tablo`,`kayit_id`,`veri`) SELECT 'calisan_temsilcisi_secimleri', `id`, CONCAT_WS(' | ', `firma_id`, `dokuman_no`, `adaylar`) FROM `calisan_temsilcisi_secimleri` WHERE `firma_id` = 1 AND `dokuman_no` = 'ÇT-2026-01'
  AND `adaylar` LIKE '%AY%E YILMAZ%' AND `adaylar` LIKE '%MEHMET%'
  AND NOT EXISTS (SELECT 1 FROM `_yedek_test_verisi_13_11` y WHERE y.`tablo` = 'calisan_temsilcisi_secimleri' AND y.`kayit_id` = `calisan_temsilcisi_secimleri`.`id`);
DELETE FROM `calisan_temsilcisi_secimleri` WHERE `firma_id` = 1 AND `dokuman_no` = 'ÇT-2026-01'
  AND `adaylar` LIKE '%AY%E YILMAZ%' AND `adaylar` LIKE '%MEHMET%';

-- Silinen kayıtlar
SELECT `tablo`, `kayit_id`, `veri` FROM `_yedek_test_verisi_13_11` ORDER BY `tablo`, `kayit_id`;
