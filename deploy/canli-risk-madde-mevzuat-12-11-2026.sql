-- 12.11.2026 — Risk analizi maddelerine mevzuat sütunu + kütüphaneden tek seferlik doldurma.
-- Kodu canlıya yüklemeden ÖNCE phpMyAdmin'de çalıştırın.

ALTER TABLE `u2858154_mehse`.`risk_maddeleri` ADD `mevzuat` TEXT NULL AFTER `oneri`;

-- Kütüphaneden gelen (tehlike_id dolu) maddelerin mevzuatı kütüphaneden kopyalanır.
UPDATE `u2858154_mehse`.`risk_maddeleri` rm
JOIN `u2858154_mehse`.`tehlikeler` t ON t.id = rm.tehlike_id
SET rm.mevzuat = t.mevzuat
WHERE (rm.mevzuat IS NULL OR TRIM(rm.mevzuat) = '')
  AND t.mevzuat IS NOT NULL AND TRIM(t.mevzuat) <> '';

-- Mevzuatı dolan analizlerin önbellekteki PDF'i geçersiz sayılır; bir sonraki indirmede mevzuatlı yeniden üretilir.
UPDATE `u2858154_mehse`.`risk_degerlendirmeleri` rd
SET rd.pdf_yolu = NULL, rd.pdf_hazir_at = NULL, rd.pdf_talep_edildi_at = NULL
WHERE rd.pdf_yolu IS NOT NULL
  AND EXISTS (SELECT 1 FROM `u2858154_mehse`.`risk_maddeleri` rm WHERE rm.risk_degerlendirmesi_id = rd.id AND rm.mevzuat IS NOT NULL AND TRIM(rm.mevzuat) <> '');

-- Kontrol: kaç madde doldu / toplam
SELECT SUM(mevzuat IS NOT NULL AND TRIM(mevzuat) <> '') AS mevzuatli, COUNT(*) AS toplam FROM `u2858154_mehse`.`risk_maddeleri`;
