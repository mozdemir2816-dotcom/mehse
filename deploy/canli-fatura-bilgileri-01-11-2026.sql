-- Fatura & Profil sayfası: users tablosuna fatura sütunları (migration 2026_11_01_120000).
-- Sırayla: ÖNCE bu SQL (phpMyAdmin > mehse veritabanı > SQL), SONRA kod deploy.
-- BİR KEZ çalıştırın. "Duplicate column name" hatası alırsanız sütunlar zaten eklenmiş demektir;
-- yalnız alttaki INSERT ve DOĞRULAMA kısmını çalıştırmanız yeterli.

ALTER TABLE `users`
  ADD COLUMN `fatura_tipi` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN `fatura_unvan` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN `vergi_dairesi` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN `vergi_no` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN `fatura_adresi` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN `fatura_il` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN `fatura_ilce` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_01_120000_add_fatura_bilgileri_to_users_table', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_01_120000_add_fatura_bilgileri_to_users_table');

-- DOĞRULAMA (beklenen: sutun 7, migration 1):
SELECT (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users'
          AND column_name IN ('fatura_tipi','fatura_unvan','vergi_dairesi','vergi_no','fatura_adresi','fatura_il','fatura_ilce')) AS sutun,
       (SELECT COUNT(*) FROM `migrations` WHERE `migration` = '2026_11_01_120000_add_fatura_bilgileri_to_users_table') AS migration_kaydi;
