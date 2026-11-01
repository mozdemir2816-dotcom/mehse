-- 01.11.2026 — Fine-Kinney önlem sonrası puan: FOM'da düzeltilen kural TÜM firmaların mevcut analizlerine.
-- Yeni kayıtlarda kod zaten uyguluyor (RiskMaddesi::varsayilanSonOlasilik). Hastane şablonu hariç.
-- phpMyAdmin'de bir kez çalıştırın; tekrar çalıştırmak zararsızdır.
--
-- Düzeltilen maddeler:
--   a) önlem sonrası puanı ilk puanından DÜŞMEYEN (ilk puan >= 20)
--   b) eski varsayılan "son olasılık = 1" yüzünden önlem sonrasında hâlâ Önemli/Yüksek kalan (son puan >= 70)
-- Yeni son olasılık: ilk olasılığın altındaki, son puanı 70'in altına indiren en yüksek basamak (en düşük 0,1).

-- 0) Önce kaç madde etkileneceğini görmek isterseniz yalnızca bu SELECT'i çalıştırın:
SELECT r.belge_no, COUNT(*) AS madde
FROM risk_maddeleri m JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
WHERE r.yontem = 'fine_kinney' AND (r.cikti_sablonu IS NULL OR r.cikti_sablonu <> 'hastane')
  AND m.olasilik IS NOT NULL AND m.son_frekans IS NOT NULL AND m.son_siddet IS NOT NULL AND m.puan IS NOT NULL
  AND ((m.puan >= 20 AND m.son_puan >= m.puan) OR (m.son_olasilik = 1 AND m.olasilik > 1 AND m.son_puan >= 70))
GROUP BY r.belge_no ORDER BY r.belge_no;

-- 1) Düzeltme (etkilenen analizleri işaretlemek için önce PDF önbelleğini sıfırla)
UPDATE risk_degerlendirmeleri SET pdf_yolu = NULL, pdf_hazir_at = NULL, pdf_talep_edildi_at = NULL
WHERE id IN (SELECT * FROM (
    SELECT DISTINCT m.risk_degerlendirmesi_id
    FROM risk_maddeleri m JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
    WHERE r.yontem = 'fine_kinney' AND (r.cikti_sablonu IS NULL OR r.cikti_sablonu <> 'hastane')
      AND m.olasilik IS NOT NULL AND m.son_frekans IS NOT NULL AND m.son_siddet IS NOT NULL AND m.puan IS NOT NULL
      AND ((m.puan >= 20 AND m.son_puan >= m.puan) OR (m.son_olasilik = 1 AND m.olasilik > 1 AND m.son_puan >= 70))
) x);

UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.son_olasilik = CASE
        WHEN m.olasilik > 6   AND 6   * m.son_frekans * m.son_siddet < 70 THEN 6
        WHEN m.olasilik > 3   AND 3   * m.son_frekans * m.son_siddet < 70 THEN 3
        WHEN m.olasilik > 1   AND 1   * m.son_frekans * m.son_siddet < 70 THEN 1
        WHEN m.olasilik > 0.5 AND 0.5 * m.son_frekans * m.son_siddet < 70 THEN 0.5
        WHEN m.olasilik > 0.2 AND 0.2 * m.son_frekans * m.son_siddet < 70 THEN 0.2
        ELSE 0.1 END
WHERE r.yontem = 'fine_kinney' AND (r.cikti_sablonu IS NULL OR r.cikti_sablonu <> 'hastane')
  AND m.olasilik IS NOT NULL AND m.son_frekans IS NOT NULL AND m.son_siddet IS NOT NULL AND m.puan IS NOT NULL
  AND ((m.puan >= 20 AND m.son_puan >= m.puan) OR (m.son_olasilik = 1 AND m.olasilik > 1 AND m.son_puan >= 70));

-- 2) Son puan ve düzeyleri yeniden hesapla (düzey adları standart: KE / OL / ÖN / YK / ÇY)
UPDATE risk_maddeleri m
JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
SET m.son_puan = ROUND(m.son_olasilik * m.son_frekans * m.son_siddet, 1)
WHERE r.yontem = 'fine_kinney' AND (r.cikti_sablonu IS NULL OR r.cikti_sablonu <> 'hastane')
  AND m.son_olasilik IS NOT NULL AND m.son_frekans IS NOT NULL AND m.son_siddet IS NOT NULL;

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
WHERE r.yontem = 'fine_kinney' AND (r.cikti_sablonu IS NULL OR r.cikti_sablonu <> 'hastane');

-- Kontrol: 0 satır dönmeli
SELECT r.belge_no, m.id, m.tehlike, m.puan, m.son_olasilik, m.son_puan
FROM risk_maddeleri m JOIN risk_degerlendirmeleri r ON r.id = m.risk_degerlendirmesi_id
WHERE r.yontem = 'fine_kinney' AND (r.cikti_sablonu IS NULL OR r.cikti_sablonu <> 'hastane')
  AND m.puan >= 20 AND m.son_puan >= m.puan;
