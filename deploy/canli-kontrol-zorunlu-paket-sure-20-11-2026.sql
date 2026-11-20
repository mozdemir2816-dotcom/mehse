-- SALT OKUNUR: Zorunlu Temel İSG paketinde video süresi ne kadar eksik? (20.11.2026)
-- Hedef 360 dk (8 ders saati x 45 dk). Hiçbir şeyi değiştirmez.

-- 1) Konu (modül) bazında süre + süresi girilmemiş video sayısı
SELECT d.modul,
       COUNT(*)                                   AS video_sayisi,
       ROUND(SUM(COALESCE(d.sure_sn, 0)) / 60, 1) AS sure_dk,
       SUM(d.sure_sn IS NULL OR d.sure_sn = 0)    AS suresi_bos_video
FROM egitim_dersleri d
JOIN egitim_paketleri p ON p.id = d.egitim_paketi_id
WHERE p.ad = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)'
GROUP BY d.modul
ORDER BY d.modul;

-- 2) Toplam ve eksik
SELECT ROUND(SUM(COALESCE(d.sure_sn, 0)) / 60, 1)       AS toplam_dk,
       360                                              AS hedef_dk,
       GREATEST(0, CEIL(360 - SUM(COALESCE(d.sure_sn, 0)) / 60)) AS eksik_dk
FROM egitim_dersleri d
JOIN egitim_paketleri p ON p.id = d.egitim_paketi_id
WHERE p.ad = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)';
