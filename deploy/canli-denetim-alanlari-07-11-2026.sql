-- Uzaktan eğitim denetim kanıtı: KVKK/taahhüt onayı (calisanlar), giriş günlüğünde cihaz (egitim_girisleri),
-- belge QR doğrulama kodu (egitim_atamalari).
-- Sırayla: ÖNCE bu SQL (phpMyAdmin > mehse veritabanı > SQL), SONRA kod deploy. Aksi halde çalışan portalı hata verir.
-- BİR KEZ çalıştırın. "Duplicate column name" alırsanız o sütun zaten eklenmiştir; ilgili satırı atlayıp devam edin.

ALTER TABLE `calisanlar`
  ADD COLUMN `kvkk_onay_at` timestamp NULL DEFAULT NULL,
  ADD COLUMN `kvkk_onay_ip` varchar(45) DEFAULT NULL;

ALTER TABLE `egitim_girisleri`
  ADD COLUMN `cihaz` varchar(255) DEFAULT NULL AFTER `ip`;

ALTER TABLE `egitim_atamalari`
  ADD COLUMN `dogrulama_kodu` varchar(32) DEFAULT NULL,
  ADD UNIQUE KEY `egitim_atamalari_dogrulama_kodu_unique` (`dogrulama_kodu`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_07_100000_add_denetim_alanlari_to_uzaktan_egitim', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`)
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_07_100000_add_denetim_alanlari_to_uzaktan_egitim');

-- DOĞRULAMA (beklenen: sutun 4, migration_kaydi 1):
SELECT (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE()
          AND ((table_name = 'calisanlar' AND column_name IN ('kvkk_onay_at','kvkk_onay_ip'))
            OR (table_name = 'egitim_girisleri' AND column_name = 'cihaz')
            OR (table_name = 'egitim_atamalari' AND column_name = 'dogrulama_kodu'))) AS sutun,
       (SELECT COUNT(*) FROM `migrations` WHERE `migration` = '2026_11_07_100000_add_denetim_alanlari_to_uzaktan_egitim') AS migration_kaydi;
