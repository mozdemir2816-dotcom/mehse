-- Eğitim katılım formuna iş kolu / çalışan grubu (migration 2026_11_03_150000).
-- Sırayla: ÖNCE bu SQL (phpMyAdmin > mehse veritabanı > SQL), SONRA kod deploy.
-- BİR KEZ çalıştırın. "Duplicate column name" hatası alırsanız sütunlar zaten eklenmiş demektir;
-- yalnız alttaki INSERT ve DOĞRULAMA kısmını çalıştırmanız yeterli.

ALTER TABLE `egitim_katilimlari`
  ADD COLUMN `is_kolu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `egitim_yeri`,
  ADD COLUMN `is_kolu_kalemleri` json DEFAULT NULL AFTER `is_kolu`;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_03_150000_add_is_kolu_to_egitim_katilimlari_table', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_03_150000_add_is_kolu_to_egitim_katilimlari_table');

-- DOĞRULAMA (beklenen: sutun 2, migration 1):
SELECT (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'egitim_katilimlari'
          AND column_name IN ('is_kolu','is_kolu_kalemleri')) AS sutun,
       (SELECT COUNT(*) FROM `migrations` WHERE `migration` = '2026_11_03_150000_add_is_kolu_to_egitim_katilimlari_table') AS migration_kaydi;
