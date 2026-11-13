-- Kütüphane: mükerrer manitou klasörlerini birleştirme (13.11.2026).
-- "Ekipman – Manitou / teleskopik yükleyici" (anahtar ekipman_manitou_teleskopik_yukleyici) ile
-- "Ekipman – Teleskopik yükleyici (Manitou)" (anahtar ekipman_teleskopik_yukleyici) birebir aynı 40 maddeyi içeriyor.
-- KALAN: ekipman_teleskopik_yukleyici (ekipman kataloğu bu anahtara bağlı). SİLİNEN: ekipman_manitou_teleskopik_yukleyici.
--   1) Silinecek klasörün yöntem ve NACE grupları, kalan klasörde boşsa ona aktarılır.
--   2) Risk değerlendirmelerinde silinecek klasörün maddesine bağlı satırlar, kalan klasördeki aynı maddeye bağlanır
--      (risk değerlendirmesi metinleri değişmez).
--   3) Kalan klasörde karşılığı olmayan madde varsa silinmez, kalan klasöre taşınır.
--   4) Silinecek klasördeki kopya maddeler ve boşalan klasör silinir.
-- phpMyAdmin > mehse veritabanı > SQL. Kod değişikliği YOK; tekrar çalıştırmak güvenlidir.

SET NAMES utf8mb4;
START TRANSACTION;

SET @tut := (SELECT `id` FROM `tehlike_kategorileri` WHERE `anahtar` = 'ekipman_teleskopik_yukleyici' LIMIT 1);
SET @sil := (SELECT `id` FROM `tehlike_kategorileri` WHERE `anahtar` = 'ekipman_manitou_teleskopik_yukleyici' LIMIT 1);

-- 1) Yöntem + NACE grupları
UPDATE `tehlike_kategorileri` t JOIN `tehlike_kategorileri` s ON s.`id` = @sil
SET t.`yontem` = COALESCE(t.`yontem`, s.`yontem`), t.`nace_gruplari` = COALESCE(t.`nace_gruplari`, s.`nace_gruplari`)
WHERE t.`id` = @tut;

-- 2) Risk maddelerini kalan klasördeki aynı maddeye bağla
UPDATE `risk_maddeleri` r
JOIN `tehlikeler` e ON e.`id` = r.`tehlike_id` AND e.`tehlike_kategorisi_id` = @sil
JOIN `tehlikeler` y ON y.`tehlike_kategorisi_id` = @tut AND y.`tehlike` = e.`tehlike`
SET r.`tehlike_id` = y.`id`;

UPDATE `tehlike_cakismalari` c
JOIN `tehlikeler` e ON e.`id` = c.`mevcut_tehlike_id` AND e.`tehlike_kategorisi_id` = @sil
JOIN `tehlikeler` y ON y.`tehlike_kategorisi_id` = @tut AND y.`tehlike` = e.`tehlike`
SET c.`mevcut_tehlike_id` = y.`id`;
UPDATE `tehlike_cakismalari` SET `tehlike_kategorisi_id` = @tut WHERE `tehlike_kategorisi_id` = @sil AND @tut IS NOT NULL;

-- 3) Karşılığı olmayan maddeleri taşı
UPDATE `tehlikeler` e
LEFT JOIN `tehlikeler` y ON y.`tehlike_kategorisi_id` = @tut AND y.`tehlike` = e.`tehlike`
SET e.`tehlike_kategorisi_id` = @tut
WHERE e.`tehlike_kategorisi_id` = @sil AND y.`id` IS NULL AND @tut IS NOT NULL;

-- 4) Kopyaları ve boş klasörü sil (hâlâ bağlı satır varsa silinmez)
DELETE e FROM `tehlikeler` e
JOIN `tehlikeler` y ON y.`tehlike_kategorisi_id` = @tut AND y.`tehlike` = e.`tehlike`
WHERE e.`tehlike_kategorisi_id` = @sil
  AND NOT EXISTS (SELECT 1 FROM `risk_maddeleri` r WHERE r.`tehlike_id` = e.`id`);
DELETE FROM `tehlike_kategorileri` WHERE `id` = @sil
  AND NOT EXISTS (SELECT 1 FROM `tehlikeler` e WHERE e.`tehlike_kategorisi_id` = @sil);

COMMIT;

-- Doğrulama: yalnız 1 manitou klasörü kalmalı (40 madde)
SELECT k.`id`, k.`ad`, k.`anahtar`, k.`yontem`, COUNT(e.`id`) AS madde
FROM `tehlike_kategorileri` k LEFT JOIN `tehlikeler` e ON e.`tehlike_kategorisi_id` = k.`id`
WHERE k.`ad` LIKE '%anitou%' GROUP BY k.`id`;
