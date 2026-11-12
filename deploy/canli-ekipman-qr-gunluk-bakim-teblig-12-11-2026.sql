-- Akıllı ekipman takibi + tebliğ paketi (12.11.2026) — migration 2026_11_12_120000_create_ekipman_qr_gunluk_kontrol_bakim
-- Kod yüklenmeden ÖNCE phpMyAdmin'de çalıştırın. Bir kez çalıştırılır (tekrarında "Duplicate column" hatası verir).
--  is_ekipmanlari  : üretim yılı, operatör QR anahtarı / PIN, bakım sorumlusu, tebliğ durumu + imzalı nüsha
--  is_ekipmani_gunluk_kontrolleri : QR / panelden doldurulan günlük kontrol
--  is_ekipmani_bakimlari          : bakım / arıza kaydı ("Uygun Değil" çıkınca otomatik açılır)
--  talimat_sablonlari             : ekipman tipine bağlı kütüphane talimatı (yapay zekâ taslağı)

USE `u2858154_mehse`;

alter table `is_ekipmanlari` add `uretim_yili` smallint unsigned null after `marka_model`;
alter table `is_ekipmanlari` add `qr_anahtar` varchar(40) null after `aktif`;
alter table `is_ekipmanlari` add `qr_pin` varchar(191) null after `qr_anahtar`;
alter table `is_ekipmanlari` add `bakim_sorumlusu` varchar(191) null after `qr_pin`;
alter table `is_ekipmanlari` add `bakim_sorumlusu_eposta` varchar(191) null after `bakim_sorumlusu`;
alter table `is_ekipmanlari` add `teblig_cikti_at` datetime null after `bakim_sorumlusu_eposta`;
alter table `is_ekipmanlari` add `isverene_teblig_edildi` tinyint(1) not null default '0' after `teblig_cikti_at`;
alter table `is_ekipmanlari` add `teblig_tarihi` date null after `isverene_teblig_edildi`;
alter table `is_ekipmanlari` add `imzali_belge_yolu` varchar(191) null after `teblig_tarihi`;
alter table `is_ekipmanlari` add unique `is_ekipmanlari_qr_anahtar_unique`(`qr_anahtar`);
create table `is_ekipmani_gunluk_kontrolleri` (`id` bigint unsigned not null auto_increment primary key, `is_ekipmani_id` bigint unsigned not null, `kontrol_zamani` datetime not null, `operator_adi` varchar(191) not null, `form_adi` varchar(191) null, `cevaplar` json not null, `uygunsuz_sayisi` smallint unsigned not null default '0', `kullanima_uygun` tinyint(1) not null default '1', `notu` text null, `kaynak` varchar(10) not null default 'qr', `ip` varchar(45) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `is_ekipmani_gunluk_kontrolleri` add constraint `is_ekipmani_gunluk_kontrolleri_is_ekipmani_id_foreign` foreign key (`is_ekipmani_id`) references `is_ekipmanlari` (`id`) on delete cascade;
alter table `is_ekipmani_gunluk_kontrolleri` add index `ekipman_gunluk_zaman_index`(`is_ekipmani_id`, `kontrol_zamani`);
create table `is_ekipmani_bakimlari` (`id` bigint unsigned not null auto_increment primary key, `is_ekipmani_id` bigint unsigned not null, `gunluk_kontrol_id` bigint unsigned null, `tur` varchar(10) not null default 'ariza', `durum` varchar(10) not null default 'acik', `baslik` varchar(191) not null, `aciklama` text null, `uygunsuz_maddeler` json null, `bildiren` varchar(191) null, `sorumlu` varchar(191) null, `acilis_zamani` datetime not null, `kapanis_zamani` datetime null, `kapanis_notu` text null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `is_ekipmani_bakimlari` add constraint `is_ekipmani_bakimlari_is_ekipmani_id_foreign` foreign key (`is_ekipmani_id`) references `is_ekipmanlari` (`id`) on delete cascade;
alter table `is_ekipmani_bakimlari` add constraint `is_ekipmani_bakimlari_gunluk_kontrol_id_foreign` foreign key (`gunluk_kontrol_id`) references `is_ekipmani_gunluk_kontrolleri` (`id`) on delete set null;
alter table `is_ekipmani_bakimlari` add index `ekipman_bakim_durum_index`(`is_ekipmani_id`, `durum`);

alter table `talimat_sablonlari` add `ekipman_tipi` varchar(191) null after `kategori`;
alter table `talimat_sablonlari` add `yz_taslak` tinyint(1) not null default '0' after `ekipman_tipi`;
alter table `talimat_sablonlari` add index `talimat_sablonlari_ekipman_tipi_index`(`ekipman_tipi`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_12_120000_create_ekipman_qr_gunluk_kontrol_bakim', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;

-- Doğrulama: beklenen  yeni_sutun = 9 | tablo = 2 | talimat_sutun = 2
SELECT (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'u2858154_mehse' AND TABLE_NAME = 'is_ekipmanlari'
          AND COLUMN_NAME IN ('uretim_yili','qr_anahtar','qr_pin','bakim_sorumlusu','bakim_sorumlusu_eposta','teblig_cikti_at','isverene_teblig_edildi','teblig_tarihi','imzali_belge_yolu')) AS yeni_sutun,
       (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'u2858154_mehse' AND TABLE_NAME IN ('is_ekipmani_gunluk_kontrolleri','is_ekipmani_bakimlari')) AS tablo,
       (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'u2858154_mehse' AND TABLE_NAME = 'talimat_sablonlari' AND COLUMN_NAME IN ('ekipman_tipi','yz_taslak')) AS talimat_sutun;
