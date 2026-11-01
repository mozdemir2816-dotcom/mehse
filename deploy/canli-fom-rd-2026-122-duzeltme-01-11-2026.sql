-- 01.11.2026 — FOM MAKİNA RD-2026-122 çıktı düzeltmeleri + Fine-Kinney önlem sonrası puan onarımı.
-- phpMyAdmin'de canlı veritabanında (u2858154_mehse) SQL sekmesinden bir kez çalıştırın.
-- Tekrar çalıştırmak zararsızdır (koşullar düzeltilmiş satırları yeniden seçmez).

-- 1) Adreste mükerrer "NO:NO:" yazımı (firma kartı + rapor kapağındaki kopya)
UPDATE firmalar SET adres = REPLACE(adres, 'NO:NO:', 'No:') WHERE adres LIKE '%NO:NO:%';
UPDATE risk_degerlendirmeleri SET firma_adres = REPLACE(firma_adres, 'NO:NO:', 'No:') WHERE firma_adres LIKE '%NO:NO:%';

-- 2) RD-2026-122 kapak alanları
UPDATE risk_degerlendirmeleri
SET proje_adi = 'FOM Makina İmalat ve Montaj Tesisi',
    kapsam_notu = 'Talaşlı İmalat, Mekanik Montaj, Kaynak/Boya, Depo ve İdari Alanlar'
WHERE belge_no = 'RD-2026-122';

-- 3) Fine-Kinney önlem sonrası olasılık hatası: ilk olasılık 1'in ALTINDAYKEN varsayılan "1" atanmış,
--    önlem sonrası puan ilkinden YÜKSEK çıkmış (ör. 0,5×3×15=22,5 → 45). Tüm FK analizlerinde (hastane hariç)
--    bir alt basamağa indirilir: 0,5 → 0,2 ; 0,2 → 0,1.
UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.son_olasilik = CASE WHEN m.olasilik >= 0.5 THEN 0.2 ELSE 0.1 END
WHERE r.yontem = 'fine_kinney' AND (r.cikti_sablonu IS NULL OR r.cikti_sablonu <> 'hastane')
  AND m.olasilik < 1 AND m.son_olasilik = 1;

--    RD-2026-122'de ilk olasılığı 1 olup önlem sonrası da 1 kalan (puan hiç düşmeyen) maddeler → 0,5
UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.son_olasilik = 0.5
WHERE r.belge_no = 'RD-2026-122' AND r.yontem = 'fine_kinney'
  AND m.olasilik = 1 AND m.son_olasilik = 1;

-- 4) Puan ve düzeyleri yeniden hesapla (düzey adları config bantlarıyla standart: KE/OL/ÖN/YK/ÇY)
--    a) yukarıda değişen satırlar (tüm FK analizleri)  b) RD-2026-122'nin tüm satırları
UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.son_puan = ROUND(m.son_olasilik * m.son_frekans * m.son_siddet, 1)
WHERE r.yontem = 'fine_kinney' AND (r.cikti_sablonu IS NULL OR r.cikti_sablonu <> 'hastane')
  AND m.son_olasilik IS NOT NULL AND m.son_frekans IS NOT NULL AND m.son_siddet IS NOT NULL
  AND (r.belge_no = 'RD-2026-122' OR m.son_puan > m.puan);

UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.duzey = CASE
        WHEN m.puan IS NULL THEN m.duzey
        WHEN m.puan >= 400 THEN 'Çok Yüksek Risk' WHEN m.puan >= 200 THEN 'Yüksek Risk'
        WHEN m.puan >= 70 THEN 'Önemli Risk' WHEN m.puan >= 20 THEN 'Olası Risk' ELSE 'Kabul Edilebilir Risk' END,
    m.son_duzey = CASE
        WHEN m.son_puan IS NULL THEN m.son_duzey
        WHEN m.son_puan >= 400 THEN 'Çok Yüksek Risk' WHEN m.son_puan >= 200 THEN 'Yüksek Risk'
        WHEN m.son_puan >= 70 THEN 'Önemli Risk' WHEN m.son_puan >= 20 THEN 'Olası Risk' ELSE 'Kabul Edilebilir Risk' END
WHERE r.yontem = 'fine_kinney' AND (r.cikti_sablonu IS NULL OR r.cikti_sablonu <> 'hastane')
  AND (r.belge_no = 'RD-2026-122' OR m.son_olasilik < m.olasilik);

-- 5) RD-2026-122 termin: varsayılan "SÜREKLİ" yerine ilk risk düzeyine göre
--    (eğitim / talimat / periyodik maddeler SÜREKLİ kalır; elle yazılmış tarihler değişmez)
UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.termin = CASE
        WHEN m.puan >= 400 THEN 'Derhal / 3 Gün'
        WHEN m.puan >= 200 THEN '1 Ay'
        WHEN m.puan >= 70 THEN '3 Ay'
        ELSE m.termin END
WHERE r.belge_no = 'RD-2026-122' AND r.yontem = 'fine_kinney'
  AND UPPER(m.termin) IN ('SÜREKLİ', 'SUREKLI')
  AND CONCAT_WS(' ', m.tehlike, m.oneri) NOT LIKE '%eğitim%'
  AND CONCAT_WS(' ', m.tehlike, m.oneri) NOT LIKE '%egitim%'
  AND CONCAT_WS(' ', m.tehlike, m.oneri) NOT LIKE '%talimat%'
  AND CONCAT_WS(' ', m.tehlike, m.oneri) NOT LIKE '%periyodik%';

-- 6) Önbellekteki eski PDF'i geçersiz kıl (bir sonraki indirmede yeniden üretilir; sayfa sayısı ve ekip güncel)
UPDATE risk_degerlendirmeleri
SET pdf_yolu = NULL, pdf_hazir_at = NULL, pdf_talep_edildi_at = NULL
WHERE belge_no = 'RD-2026-122'
   OR id IN (SELECT DISTINCT risk_degerlendirmesi_id FROM risk_maddeleri WHERE son_olasilik < olasilik AND son_olasilik IN (0.1, 0.2));

-- Kontrol: 0 satır dönmeli (önlem sonrası puanı ilkinden yüksek FK maddesi)
SELECT r.belge_no, m.id, m.tehlike, m.puan, m.son_puan
FROM risk_maddeleri m JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
WHERE r.yontem = 'fine_kinney' AND (r.cikti_sablonu IS NULL OR r.cikti_sablonu <> 'hastane') AND m.son_puan > m.puan;
