-- Tatbikat: hedef (planlanan) tahliye süresi sütunu (migration 2026_11_02_120000).
-- Sırayla: ÖNCE bu SQL (phpMyAdmin'de soldan mehse veritabanını seçip SQL sekmesinde), SONRA kod deploy.
-- ALTER "#1060 Duplicate column name" verirse sütun zaten eklenmiştir, sorun yok.

ALTER TABLE `tatbikat_tutanaklari` ADD COLUMN `hedef_tahliye_dk` smallint unsigned NULL AFTER `tahliye_dk`;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_02_120000_add_hedef_tahliye_dk_to_tatbikat_tutanaklari_table', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_02_120000_add_hedef_tahliye_dk_to_tatbikat_tutanaklari_table');

-- Kontrol: hedef_tahliye_dk satırı görünmeli
SHOW COLUMNS FROM `tatbikat_tutanaklari` LIKE 'hedef_tahliye_dk';
