-- Uzaktan eğitim: 4 modül (egitim_dersleri.modul) + İGU onayı (egitim_atamalari.igu_onay_at / igu_onaylayan_id).
-- Sırayla: ÖNCE bu SQL (phpMyAdmin > mehse veritabanı > SQL), SONRA kod deploy.
-- BİR KEZ çalıştırın. "Duplicate column name" alırsanız o sütun zaten eklenmiştir; ilgili ALTER'ı atlayıp devam edin.

ALTER TABLE `egitim_dersleri`
  ADD COLUMN `modul` tinyint unsigned DEFAULT NULL AFTER `egitim_paketi_id`;

ALTER TABLE `egitim_atamalari`
  ADD COLUMN `igu_onay_at` timestamp NULL DEFAULT NULL,
  ADD COLUMN `igu_onaylayan_id` bigint unsigned DEFAULT NULL;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_05_120000_add_modul_to_egitim_dersleri_table', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_05_120000_add_modul_to_egitim_dersleri_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_05_130000_add_igu_onay_to_egitim_atamalari_table', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_05_130000_add_igu_onay_to_egitim_atamalari_table');

-- DOĞRULAMA (beklenen: sutun 3, migration 2):
SELECT (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE()
          AND ((table_name = 'egitim_dersleri' AND column_name = 'modul')
            OR (table_name = 'egitim_atamalari' AND column_name IN ('igu_onay_at','igu_onaylayan_id')))) AS sutun,
       (SELECT COUNT(*) FROM `migrations` WHERE `migration` LIKE '2026_11_05_1%') AS migration_kaydi;
