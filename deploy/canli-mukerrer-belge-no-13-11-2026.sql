-- Mükerrer belge numaralarını düzeltme (13.11.2026).
-- Sebep: numara "kayıt sayısı + 1" ile üretiliyordu; kayıt silinince sonraki kayıt mevcut numarayı tekrar alıyordu.
-- Kod düzeltmesi (App\Support\BelgeNumarasi) ile birlikte gelir; bu dosya MEVCUT tekrarları giderir.
-- Kural: aynı numaralı kayıtlardan EN ESKİSİ (küçük id) numarasını korur; sonrakiler yılın en büyük numarasından
-- devam eden yeni numara alır. Bursa Şehir Hastanesi formlarının RD-2026-H01…H33 numaraları (farklı hesaplarda)
-- bilinçli olarak dokunulmaz.
-- NOT: Yeni numara alan belgelerin daha önce yazdırılmış nüshalarında eski numara yazar; bir sonraki çıktıda
-- yeni numara görünür. Sonda listelenen kayıtlar numarası değişenlerdir.
-- phpMyAdmin > mehse veritabanı > SQL. Tekrar çalıştırmak güvenlidir (tekrar kalmadıysa hiçbir şey değişmez).

SET NAMES utf8mb4;

-- Değişecek kayıtları önce yedekle (kontrol için)
CREATE TABLE IF NOT EXISTS `_yedek_belge_no_13_11` (`tablo` varchar(64), `kayit_id` bigint unsigned, `eski_no` varchar(191), `tarih` timestamp DEFAULT CURRENT_TIMESTAMP);

-- 1) Risk değerlendirmeleri (RD-2026-xxx)
INSERT INTO `_yedek_belge_no_13_11` (`tablo`,`kayit_id`,`eski_no`)
SELECT 'risk_degerlendirmeleri', r.`id`, r.`belge_no` FROM `risk_degerlendirmeleri` r
WHERE r.`belge_no` REGEXP '^RD-2026-[0-9]+$'
  AND EXISTS (SELECT 1 FROM `risk_degerlendirmeleri` o WHERE o.`belge_no` = r.`belge_no` AND o.`id` < r.`id`);
SET @n := (SELECT COALESCE(MAX(CAST(SUBSTRING(`belge_no`, 9) AS UNSIGNED)), 0) FROM `risk_degerlendirmeleri` WHERE `belge_no` REGEXP '^RD-2026-[0-9]+$');
UPDATE `risk_degerlendirmeleri` SET `belge_no` = CONCAT('RD-2026-', LPAD(@n := @n + 1, 3, '0'))
WHERE `id` IN (SELECT `id` FROM (SELECT r.`id` FROM `risk_degerlendirmeleri` r WHERE r.`belge_no` REGEXP '^RD-2026-[0-9]+$'
  AND EXISTS (SELECT 1 FROM `risk_degerlendirmeleri` o WHERE o.`belge_no` = r.`belge_no` AND o.`id` < r.`id`)) x)
ORDER BY `id`;

-- 2) Eğitim katılım formları (EGT-2026-xxx)
INSERT INTO `_yedek_belge_no_13_11` (`tablo`,`kayit_id`,`eski_no`)
SELECT 'egitim_katilimlari', e.`id`, e.`belge_no` FROM `egitim_katilimlari` e
WHERE e.`belge_no` REGEXP '^EGT-2026-[0-9]+$'
  AND EXISTS (SELECT 1 FROM `egitim_katilimlari` o WHERE o.`belge_no` = e.`belge_no` AND o.`id` < e.`id`);
SET @n := (SELECT COALESCE(MAX(CAST(SUBSTRING(`belge_no`, 10) AS UNSIGNED)), 0) FROM `egitim_katilimlari` WHERE `belge_no` REGEXP '^EGT-2026-[0-9]+$');
UPDATE `egitim_katilimlari` SET `belge_no` = CONCAT('EGT-2026-', LPAD(@n := @n + 1, 3, '0'))
WHERE `id` IN (SELECT `id` FROM (SELECT e.`id` FROM `egitim_katilimlari` e WHERE e.`belge_no` REGEXP '^EGT-2026-[0-9]+$'
  AND EXISTS (SELECT 1 FROM `egitim_katilimlari` o WHERE o.`belge_no` = e.`belge_no` AND o.`id` < e.`id`)) x)
ORDER BY `id`;

-- 3) Atama yazıları (ATM-2026-xxx)
INSERT INTO `_yedek_belge_no_13_11` (`tablo`,`kayit_id`,`eski_no`)
SELECT 'atama_yazilari', a.`id`, a.`dokuman_no` FROM `atama_yazilari` a
WHERE a.`dokuman_no` REGEXP '^ATM-2026-[0-9]+$'
  AND EXISTS (SELECT 1 FROM `atama_yazilari` o WHERE o.`dokuman_no` = a.`dokuman_no` AND o.`id` < a.`id`);
SET @n := (SELECT COALESCE(MAX(CAST(SUBSTRING(`dokuman_no`, 10) AS UNSIGNED)), 0) FROM `atama_yazilari` WHERE `dokuman_no` REGEXP '^ATM-2026-[0-9]+$');
UPDATE `atama_yazilari` SET `dokuman_no` = CONCAT('ATM-2026-', LPAD(@n := @n + 1, 3, '0'))
WHERE `id` IN (SELECT `id` FROM (SELECT a.`id` FROM `atama_yazilari` a WHERE a.`dokuman_no` REGEXP '^ATM-2026-[0-9]+$'
  AND EXISTS (SELECT 1 FROM `atama_yazilari` o WHERE o.`dokuman_no` = a.`dokuman_no` AND o.`id` < a.`id`)) x)
ORDER BY `id`;

-- Doğrulama: üç satır da 0 olmalı
SELECT 'risk' AS tablo, COUNT(*) AS tekrar FROM (SELECT `belge_no` FROM `risk_degerlendirmeleri` WHERE `belge_no` REGEXP '^RD-2026-[0-9]+$' GROUP BY `belge_no` HAVING COUNT(*) > 1) x
UNION ALL SELECT 'egitim', COUNT(*) FROM (SELECT `belge_no` FROM `egitim_katilimlari` GROUP BY `belge_no` HAVING COUNT(*) > 1) x
UNION ALL SELECT 'atama', COUNT(*) FROM (SELECT `dokuman_no` FROM `atama_yazilari` GROUP BY `dokuman_no` HAVING COUNT(*) > 1) x;

-- Numarası değişen kayıtlar
SELECT y.`tablo`, y.`kayit_id`, y.`eski_no`,
  COALESCE(r.`belge_no`, e.`belge_no`, a.`dokuman_no`) AS yeni_no,
  f.`unvan` AS firma
FROM `_yedek_belge_no_13_11` y
LEFT JOIN `risk_degerlendirmeleri` r ON y.`tablo` = 'risk_degerlendirmeleri' AND r.`id` = y.`kayit_id`
LEFT JOIN `egitim_katilimlari` e ON y.`tablo` = 'egitim_katilimlari' AND e.`id` = y.`kayit_id`
LEFT JOIN `atama_yazilari` a ON y.`tablo` = 'atama_yazilari' AND a.`id` = y.`kayit_id`
LEFT JOIN `firmalar` f ON f.`id` = COALESCE(r.`firma_id`, e.`firma_id`, a.`firma_id`)
ORDER BY y.`tablo`, y.`kayit_id`;
