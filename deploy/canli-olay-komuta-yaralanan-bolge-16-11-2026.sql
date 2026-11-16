-- 16.11.2026 — Olay Komuta 360°: olay kaydına yaralanan vücut bölgesi
-- Canlıya yüklemeden ÖNCE phpMyAdmin'de çalıştırın.
ALTER TABLE `olay_kayitlari` ADD COLUMN `yaralanan_bolge` VARCHAR(191) NULL AFTER `yaralanma_turu`;

INSERT INTO `migrations` (`migration`,`batch`) SELECT '2026_11_16_100000_add_yaralanan_bolge_to_olay_kayitlari', (SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_16_100000_add_yaralanan_bolge_to_olay_kayitlari');
