# CLAUDE.md

## Proje: mehse İSG

Tek kişilik İş Güvenliği Uzmanı için İSG operasyon yönetim paneli. `isgpratik` ekran
görüntüleri referans (`C:\Users\mozde\Desktop\isgpratik\`), yol haritası: `MIMARI.md`.

- **Yığın:** Laravel 12 + Filament v5 (PHP 8.2, XAMPP)
- **DB:** MySQL `mehse` (root / şifresiz)
- **Erişim:** <http://localhost/mehse/> → `public/admin`
- **Panel:** tek panel `admin`, koyu tema varsayılan, birincil renk mor
- **Giriş:** `mozdemir2816@gmail.com` / `mehse2026`

### Komutlar

```bash
"C:/xampp2/php/php.exe" artisan <komut>
"C:/xampp2/php/php.exe" "C:/xampp2/php/composer.phar" <komut>

php artisan migrate        # yalnızca yeni migration'lar
php artisan db:seed --class=TehlikeKutuphanesiSeeder
php artisan test           # 73 test
```

### Kurallar

- **`isgpratik`'in kodu / markası / metinleri birebir kopyalanmaz** — modül ve akış
  mantığı referans alınır.
- **Önceki projelerden (yeni-proje, katip-isg) kod alınmaz.** Sıfırdan.
- Model & tablo adları Türkçe (`Firma`, `firmalar`, `RiskDegerlendirmesi`).
- Filament v5: `Filament\Schemas\Schema`, `Filament\Actions\*`, `->recordActions()`,
  `->toolbarActions()`. `$guarded = ['id']`, enum sabitleri model içinde `const` dizi.
- Mevzuata bağlı sabitler `config/isg.php`.
- Her modül: kullanıcı ilgili `isgpratik` ekran görüntüsünü verince planlanıp yazılır.

### Diğer projeler (KARIŞTIRMA)

- `C:\xampp2\htdocs\yeni-proje\` — ayrı İSG/OSGB projesi
- `C:\xampp2\htdocs\katip-isg\` — ayrı tek-firma İSG projesi
