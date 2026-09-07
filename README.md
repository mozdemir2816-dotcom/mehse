# mehse İSG

Tek kişilik **İş Güvenliği Uzmanı** için İSG operasyon yönetim paneli. Bir uzmanın
portföyündeki firmaları, çalışanları, risk değerlendirmelerini ve tüm yasal
form/belge süreçlerini tek yerden yönetir.

- **Yığın:** Laravel 12 + Filament v5 (PHP 8.2+)
- **Veritabanı:** MySQL
- **Panel:** tek panel `admin`, açık tema, birincil renk mor, marka "mehse İSG"
- **Yol haritası & mimari kararlar:** [`MIMARI.md`](MIMARI.md)
- **Geliştirme kuralları:** [`CLAUDE.md`](CLAUDE.md)

## Öne çıkan modüller

| Grup | Modüller |
|---|---|
| **Yönetim** | Kontrol Merkezi (portföy karnesi), Profilim, Firmalar, Çalışanlar, İSG Profesyonelleri, Mevzuat, İSG-KATİP Robot (bilgi) |
| **Risk Yönetimi** | 6 adımlı Risk Değerlendirme sihirbazı (Manuel + Gemini AI + Excel), Kayıtlı Değerlendirmeler, Sektör Şablonları, Risk Prosedürleri, Risk Kütüphanesi, Acil Durum Planı + Krokisi |
| **Formlar & Belgeler** | DÖF, AI Saha Analizi, Saha Denetimi, Kurul Toplantısı, Atama Yazıları, Eğitim Katılım, İşbaşı Eğitim, Tatbikat, Tespit Öneri Defteri, Sertifika, Eğitim Soruları, KKD, İş İzin, Ceza/Tebliğ, İş Kazası Raporu, Talimat, Muayene (EK-2), E-Reçete |
| **Planlama & Arşiv** | Yıllık Planlar, Ziyaret Programı, Araçlar (hesaplayıcılar) |

Belge üreticileri PDF (dompdf) ve yerine göre Word (PhpWord) / Excel
(PhpSpreadsheet) çıktısı verir. `[AI]` rozetli modüller Google Gemini API'sini
kullanır; anahtar tanımlı değilse ilgili özellik sessizce devre dışı kalır,
uygulama kural tabanlı çalışmaya devam eder.

## Kurulum

Gereksinimler: PHP 8.2+, Composer, MySQL, Node.js (varlık derlemesi için).

```bash
# 1. Bağımlılıklar
composer install
npm install

# 2. Ortam dosyası
cp .env.example .env
php artisan key:generate
#   .env içinde DB_* ve (isteğe bağlı) GEMINI_API_KEY değerlerini doldurun

# 3. Veritabanı
php artisan migrate
php artisan db:seed            # Risk kütüphanesi + NACE + MYK referans verileri

# 4. Yüklenen dosyalar için symlink (fotoğraf kanıtları, kaşe/logo görselleri)
php artisan storage:link

# 5. Varlıklar
npm run build

# 6. İlk kullanıcı (tinker)
php artisan tinker
>>> \App\Models\User::create(['name' => 'Ad Soyad', 'email' => 'uzman@ornek.com', 'password' => bcrypt('parola')]);
```

Panel adresi: `/admin`

### XAMPP (yerel geliştirme)

```bash
"C:/xampp2/php/php.exe" artisan <komut>
"C:/xampp2/php/php.exe" "C:/xampp2/php/composer.phar" <komut>
```

Erişim: <http://localhost/mehse/> → `public/admin`

## Test

```bash
php artisan test
```

Test veritabanı `phpunit.xml` içinde sqlite `:memory:` olarak ayarlıdır; Gemini
API anahtarı testlerde boşa zorlanır (canlı istek atılmaz).

## Üretim notları

- `.env`: `APP_ENV=production`, `APP_DEBUG=false`, güçlü `APP_KEY`.
- `php artisan storage:link` çalıştırılmalı — yüklenen fotoğraf/kaşe görselleri
  aksi halde görünmez.
- Önbellek: `php artisan config:cache route:cache view:cache` (config yalnız
  veri içerir, closure içermez — cache güvenli).
- Kuyruk sürücüsü `database`; ağır e-posta/işlem yoksa `php artisan queue:work`
  şart değil.
- Abonelik/ödeme entegrasyonu kapsam dışıdır; `users.abonelik_*` alanları yalnız
  kalan gün göstergesi içindir.
