-- İşe özgü eğitim kütüphanesine iş kolu sütunu (migration 2026_11_03_170000).
-- Sırayla: ÖNCE bu SQL (phpMyAdmin > mehse veritabanı > SQL), SONRA kod deploy.
-- BİR KEZ çalıştırın. "Duplicate column name" hatası alırsanız sütun zaten eklenmiş demektir;
-- yalnız alttaki INSERT ve DOĞRULAMA kısmını çalıştırmanız yeterli.

ALTER TABLE `ise_ozgu_egitim_konulari`
  ADD COLUMN `is_kolu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `is_kalemleri`;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_03_170000_add_is_kolu_to_ise_ozgu_egitim_konulari_table', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_03_170000_add_is_kolu_to_ise_ozgu_egitim_konulari_table');

-- DOĞRULAMA (beklenen: sutun 1, migration 1):
SELECT (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'ise_ozgu_egitim_konulari'
          AND column_name = 'is_kolu') AS sutun,
       (SELECT COUNT(*) FROM `migrations` WHERE `migration` = '2026_11_03_170000_add_is_kolu_to_ise_ozgu_egitim_konulari_table') AS migration_kaydi;
