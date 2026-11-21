-- 21.11.2026 — Ekipman afiş kütüphanesi (makine başına resim + YAPIN / YAPMAYIN / KKD)
-- Canlıya yüklemeden ÖNCE phpMyAdmin'de çalıştırın. Satırlar sayfa ilk açılınca config'ten oluşur.
CREATE TABLE IF NOT EXISTS `ekipman_afisleri` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `anahtar` VARCHAR(80) NOT NULL,
  `ad` VARCHAR(255) NOT NULL,
  `grup` VARCHAR(255) NULL,
  `gorsel_yolu` VARCHAR(255) NULL,
  `yapin` JSON NULL,
  `yapmayin` JSON NULL,
  `kkd` JSON NULL,
  `yz_taslak` TINYINT(1) NOT NULL DEFAULT 1,
  `onaylandi_at` TIMESTAMP NULL,
  `onaylayan_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ekipman_afisleri_anahtar_unique` (`anahtar`),
  KEY `ekipman_afisleri_onaylayan_id_foreign` (`onaylayan_id`),
  CONSTRAINT `ekipman_afisleri_onaylayan_id_foreign` FOREIGN KEY (`onaylayan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_21_100000_create_ekipman_afisleri_table', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_21_100000_create_ekipman_afisleri_table');

-- Doğrulama (1 beklenir):
SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'ekipman_afisleri';
