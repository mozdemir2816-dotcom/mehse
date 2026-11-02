-- Yangın Güvenliği: uzman önerisi söndürücü m² sütunu (migration 2026_11_02_100000).
-- Sırayla: ÖNCE bu SQL (phpMyAdmin'de soldan mehse veritabanını seçip SQL sekmesinde), SONRA kod deploy.
-- Not: Paylaşımlı sunucuda information_schema'ya erişim yok; bu yüzden düz ALTER kullanılır.
-- ALTER "#1060 Duplicate column name" verirse sütun zaten eklenmiştir, sorun yok.

ALTER TABLE `yangin_guvenligi_degerlendirmeleri` ADD COLUMN `ysc_uzman_m2` smallint unsigned NULL AFTER `taban_alani_m2`;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_02_100000_add_ysc_uzman_m2_to_yangin_guvenligi_degerlendirmeleri_table', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_02_100000_add_ysc_uzman_m2_to_yangin_guvenligi_degerlendirmeleri_table');

-- Kontrol: ysc_uzman_m2 satırı görünmeli
SHOW COLUMNS FROM `yangin_guvenligi_degerlendirmeleri` LIKE 'ysc_uzman_m2';
