-- Tanıtım sayfası teklif formu: teklif_talepleri tablosu (migration 2026_10_29_090000). Tekrar çalıştırılabilir.
-- Sırayla: ÖNCE bu SQL (phpMyAdmin), SONRA kod deploy.

CREATE TABLE IF NOT EXISTS `teklif_talepleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firma_adi` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kisi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefon` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `calisan_sayisi` int unsigned NOT NULL,
  `tehlike_sinifi` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `durum` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yeni',
  `not` text COLLATE utf8mb4_unicode_ci,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tarayici` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teklif_talepleri_durum_index` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_29_090000_create_teklif_talepleri_table', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_10_29_090000_create_teklif_talepleri_table');

-- DOĞRULAMA (beklenen: tablo 1, migration 1):
SELECT (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'teklif_talepleri') AS tablo,
       (SELECT COUNT(*) FROM `migrations` WHERE `migration` = '2026_10_29_090000_create_teklif_talepleri_table') AS migration_kaydi;
