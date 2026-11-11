-- 11.11.2026: Zorunlu pakette final sorularına Ek-1 konu etiketi (1 Genel, 2 Sağlık, 3 Teknik)
-- ve 6331 md.4/4 atıf düzeltmesi. Tekrar çalıştırılabilir: yalnız konusu boş sorulara dokunur.
SET NAMES utf8mb4;

UPDATE egitim_paketi_sorulari SET modul = 1 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Risk değerlendirmesi yapmak veya yaptırmakla yükümlü olan kimdir?';
UPDATE egitim_paketi_sorulari SET modul = 1 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Çok tehlikeli sınıftaki bir işyerinde risk değerlendirmesi en geç kaç yılda bir yenilenir?';
UPDATE egitim_paketi_sorulari SET modul = 1 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Aşağıdakilerden hangisi "ramak kala olay"dır?';
UPDATE egitim_paketi_sorulari SET modul = 1 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'İş sağlığı ve güvenliği önlemlerinin maliyeti için aşağıdakilerden hangisi doğrudur?';
UPDATE egitim_paketi_sorulari SET modul = 1 WHERE kullanim = 'final' AND modul IS NULL AND soru = '50 ve daha fazla çalışanı olan ve altı aydan fazla süren sürekli işlerin yapıldığı işyerlerinde hangisinin kurulması zorunludur?';
UPDATE egitim_paketi_sorulari SET modul = 1 WHERE kullanim = 'final' AND modul IS NULL AND soru = '2 ile 50 arasında çalışanı bulunan bir işyerinde en az kaç çalışan temsilcisi görevlendirilir?';
UPDATE egitim_paketi_sorulari SET modul = 1 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Çalışan, işe gidip gelirken işverenin sağladığı servis aracında kaza geçirirse bu olay nasıl değerlendirilir?';
UPDATE egitim_paketi_sorulari SET modul = 1 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Çok tehlikeli sınıftaki işyerlerinde çalışanların temel İSG eğitimi en az ne sıklıkla tekrarlanır?';
UPDATE egitim_paketi_sorulari SET modul = 1 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'İşyerinde bir tehlike fark eden çalışan ne yapmalıdır?';
UPDATE egitim_paketi_sorulari SET modul = 1 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Uyuşturucu veya alkollü olarak işe gelinmesi için aşağıdakilerden hangisi doğrudur?';
UPDATE egitim_paketi_sorulari SET modul = 2 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Meslek hastalığı nedir?';
UPDATE egitim_paketi_sorulari SET modul = 2 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Taş ocağı, kum püskürtme ve maden işlerinde solunan serbest silis tozunun neden olduğu meslek hastalığı hangisidir?';
UPDATE egitim_paketi_sorulari SET modul = 2 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'İşe giriş ve periyodik sağlık muayenelerinin masrafını kim karşılar?';
UPDATE egitim_paketi_sorulari SET modul = 2 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'İlk yardımda olay yerinde yapılacak ilk iş aşağıdakilerden hangisidir?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Gürültü Yönetmeliği\'ne göre kulak koruyucu kullanımının zorunlu olduğu üst maruziyet eylem değeri kaçtır?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Ağır bir yükü elle kaldırırken doğru vücut duruşu hangisidir?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Ekranlı araçlarla (bilgisayar) çalışırken aşağıdakilerden hangisi doğrudur?';
UPDATE egitim_paketi_sorulari SET modul = 2 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Kan ve vücut sıvılarıyla temas riski olan işlerde biyolojik risklere karşı en etkili önlemlerden biri hangisidir?';
UPDATE egitim_paketi_sorulari SET modul = 2 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Uzun süreli iş stresi, aşırı iş yükü ve işyerinde psikolojik taciz (mobbing) hangi risk grubuna girer?';
UPDATE egitim_paketi_sorulari SET modul = 2 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Kimyasal madde sıçrayan göz için ilk yardımda ne yapılır?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Elektrik çarpmasına uğramış bir kişiye müdahalede ilk yapılması gereken nedir?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Elektrik kaynaklı bir yangında aşağıdaki söndürücülerden hangisi kullanılmamalıdır?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Yangın söndürme cihazı nasıl kullanılır?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Güvenlik işaretlerinde yuvarlak, mavi zeminli beyaz semboller ne anlama gelir?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Bir kimyasal maddenin tehlikeleri, kullanımı, depolanması ve ilk yardım bilgileri hangi belgede yer alır?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Bir makinenin bakım veya onarımına başlamadan önce ne yapılmalıdır?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Makine koruyucuları (muhafazalar) için hangisi doğrudur?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Yüksekte çalışırken düşmeye karşı kullanılması gereken kişisel koruyucu donanım hangisidir?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Forklift gibi iş ekipmanlarını kim kullanabilir?';
UPDATE egitim_paketi_sorulari SET modul = 3 WHERE kullanim = 'final' AND modul IS NULL AND soru = 'Acil durum alarmı çaldığında çalışan ne yapmalıdır?';

UPDATE egitim_paketi_sorulari SET aciklama = REPLACE(aciklama, 'md.4/3', 'md.4/4') WHERE kullanim = 'final' AND aciklama LIKE '%md.4/3%';

-- Doğrulama: beklenen 1|10, 2|7, 3|13 ve atıf sayısı 0
SELECT modul, COUNT(*) AS adet FROM egitim_paketi_sorulari WHERE kullanim = 'final' GROUP BY modul;
SELECT COUNT(*) AS eski_atif FROM egitim_paketi_sorulari WHERE aciklama LIKE '%md.4/3%';
