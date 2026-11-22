<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\CanliEgitimOturumu;
use App\Models\EgitimKaydi;
use App\Models\Firma;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Canlı (Senkron) Eğitim Oturumu, >=70 Puan QR Sertifika ve
 * "Bugün Ne Yapmalıyım? (İşe Başlama Eğitimi Eksik Çalışanlar)" öncelik kuyruğu (E5).
 */
class CanliEgitimYonetici
{
    /**
     * İşe giriş tarihi tanımlı ancak temel İSG / işbaşı eğitim kaydı bulunmayan
     * aktif çalışanları aciliyet sırasıyla (en yeni işe giren üstte) döner.
     *
     * @return Collection<int, Calisan>
     */
    public static function iseBaslamaEksikKuyrugu(Firma $firma): Collection
    {
        $calisanlar = $firma->calisanlar()
            ->where('aktif', true)
            ->whereNull('isten_cikis')
            ->orderByDesc('ise_giris')
            ->orderBy('ad_soyad')
            ->get();

        if ($calisanlar->isEmpty()) {
            return collect();
        }

        $egitimliIdler = EgitimKaydi::query()
            ->whereIn('calisan_id', $calisanlar->pluck('id'))
            ->pluck('calisan_id')
            ->all();

        $katilimAdlar = $firma->egitimKatilimlari()
            ->get(['katilimcilar'])
            ->flatMap(fn ($k) => collect($k->katilimcilar ?? [])->pluck('ad_soyad'))
            ->map(fn ($a) => mb_strtolower(trim((string) $a), 'UTF-8'))
            ->filter()
            ->all();

        return $calisanlar->reject(fn (Calisan $c) => in_array($c->id, $egitimliIdler, true)
            || in_array(mb_strtolower(trim((string) $c->ad_soyad), 'UTF-8'), $katilimAdlar, true))->values();
    }

    /** Kategoriye özel 5 hızlı ölçme-değerlendirme sorusu. */
    public static function varsayilanSorular(string $kategori): array
    {
        return match ($kategori) {
            'yuksekte_8s' => [
                ['soru' => 'Yüksekte çalışma ekipmanında emniyet kemeri hangi noktaya bağlanmalıdır?', 'secenekler' => ['Sertifikalı yaşam hattı / ankraj noktasına', 'Hareketli boruya', 'Korkuluksuz iskele kenarına', 'Rastgele bir kabloya'], 'dogru' => 0],
                ['soru' => 'Emniyet kemeri kullanılmadan önce ne yapılmalıdır?', 'secenekler' => ['Gözle dikiş, karabina ve kolon kontrolü', 'Hiç kontrol gerekmez', 'Yalnız yılda bir bakılır', 'Sadece amir kontrol eder'], 'dogru' => 0],
                ['soru' => 'Yüksekte çalışmada düşen cisim riskine karşı hangisi zorunludur?', 'secenekler' => ['Çene bağlı baret ve alan bariyerlemesi', 'Sadece eldiven', 'Kulak tıkacı', 'Toz maskesi'], 'dogru' => 0],
                ['soru' => 'Merdiven kullanımında temel güvenlik kuralı nedir?', 'secenekler' => ['Üç nokta teması ve uygun eğim açısı', 'En üst basamağa çıkmak', 'Tek elle yük taşımak', 'Kaygan zemine sabitlemeden koymak'], 'dogru' => 0],
                ['soru' => 'Şiddetli rüzgâr veya yağışta açık alanda yüksekte çalışma ne yapılmalıdır?', 'secenekler' => ['Derhal durdurulmalıdır', 'Hızlandırılarak bitirilmelidir', 'Aynı şekilde devam edilir', 'Yalnız baret takılarak sürdürülür'], 'dogru' => 0],
            ],
            'isyeri_hijyeni_4s', 'gida_hijyeni_4s' => [
                ['soru' => 'Çalışma alanında el hijyeni en kritik hangi anlarda uygulanmalıdır?', 'secenekler' => ['İşe başlamadan, tuvalet sonrası ve malzeme değişiminde', 'Yalnız mesai sonunda', 'Haftada bir', 'Yalnız kirlilik görünürse'], 'dogru' => 0],
                ['soru' => 'Kimyasal temizlik maddeleri nasıl depolanmalıdır?', 'secenekler' => ['Orijinal etiketli kabında, gıdadan ayrı ve kilitli/havalandırmalı alanda', 'Boş su şişelerinde', 'Üretim tezgâhının üstünde açıkta', 'Gıda ürünlerinin yanında'], 'dogru' => 0],
                ['soru' => 'Bulaşıcı hastalık belirtisi olan çalışan ne yapmalıdır?', 'secenekler' => ['Amirine ve işyeri hekimine bilgi vermelidir', 'Gizleyip çalışmaya devam etmelidir', 'Kendi kendine ilaç alıp üretime girmelidir', 'Hiçbir şey yapmamalıdır'], 'dogru' => 0],
                ['soru' => 'Çapraz bulaşmayı önlemek için hangisi doğrudur?', 'secenekler' => ['Temiz ve kirli alan ekipmanlarını renk/alan bazında ayırmak', 'Aynı bezi tüm yüzeylerde kullanmak', 'Eldiveni tüm gün değiştirmemek', 'Atık kutusunu açık bırakmak'], 'dogru' => 0],
                ['soru' => 'İşyeri soyunma ve yemek alanlarının hijyeni kimin sorumluluğundadır?', 'secenekler' => ['İşveren altyapı sağlar, tüm çalışanlar hijyen kurallarına uyar', 'Yalnız yeni işe girenler', 'Sadece temizlik görevlisi', 'Kimsenin sorumluluğu değildir'], 'dogru' => 0],
            ],
            default => [
                ['soru' => '6331 sayılı Kanun\'a göre çalışanların temel yükümlülüğü nedir?', 'secenekler' => ['Talimatlara uymak, KKD kullanmak ve tehlikeleri bildirmek', 'Yalnız işvereni izlemek', 'KKD\'yi istemezse takmamak', 'Tehlikeyi bildirmemek'], 'dogru' => 0],
                ['soru' => 'Ramak kala olay yaşandığında ne yapılmalıdır?', 'secenekler' => ['Derhal İSG birimine / amire bildirilmelidir', 'Yaralanma olmadığı için gizlenmelidir', 'Yıl sonunda hatırlanırsa söylenir', 'Önemsenmemelidir'], 'dogru' => 0],
                ['soru' => 'Acil durum alarmı çaldığında ilk hareket ne olmalıdır?', 'secenekler' => ['İş ekipmanını güvenle durdurup acil toplanma alanına gitmek', 'Asansörle hızlıca inmek', 'Eşyaları toplamaya çalışmak', 'Sosyal medyada paylaşmak'], 'dogru' => 0],
                ['soru' => 'Elektrik panosu yangınında hangi söndürücü kullanılmaz?', 'secenekler' => ['Su', 'CO2', 'Kuru Kimyevi Toz', 'Halokarbon'], 'dogru' => 0],
                ['soru' => 'Kişisel Koruyucu Donanım (KKD) ne zaman tercih edilir?', 'secenekler' => ['Toplu koruma önlemleriyle risk tamamen giderilemediğinde', 'Hiçbir önlem alınmadan ilk ve tek çare olarak', 'Sadece müfettiş geldiğinde', 'İsteğe bağlı olarak'], 'dogru' => 0],
            ],
        };
    }

    public static function oturumOlustur(
        User $user,
        Firma $firma,
        string $kategori,
        string $baslik,
        string $oturumTarihi,
        ?string $toplantiUrl = null,
        ?int $sureSaat = null,
    ): CanliEgitimOturumu {
        $varsayilanSaat = CanliEgitimOturumu::KATEGORILER[$kategori]['saat'] ?? 4;

        return CanliEgitimOturumu::create([
            'user_id' => $user->id,
            'firma_id' => $firma->id,
            'baslik' => trim($baslik),
            'kategori' => array_key_exists($kategori, CanliEgitimOturumu::KATEGORILER) ? $kategori : 'temel_isg',
            'sure_saat' => $sureSaat && $sureSaat > 0 ? $sureSaat : $varsayilanSaat,
            'oturum_tarihi' => $oturumTarihi,
            'toplanti_url' => $toplantiUrl ? trim($toplantiUrl) : null,
            'katilim_token' => Str::random(36),
            'gecme_notu' => 70,
            'sorular' => static::varsayilanSorular($kategori),
            'sonuclar' => [],
            'aktif' => true,
        ]);
    }

    public static function sertifikaPdf(CanliEgitimOturumu $oturum, array $sonuc): StreamedResponse
    {
        $oturum->loadMissing(['firma', 'user']);
        $dogrulamaUrl = route('canli-egitim.sertifika', ['token' => $oturum->katilim_token, 'kod' => $sonuc['kod']]);
        $qrUri = (new QRCode(new QROptions(['outputBase64' => true, 'addQuietzone' => true])))->render($dogrulamaUrl);

        $pdf = Pdf::loadView('pdf.canli-egitim-sertifika', [
            'oturum' => $oturum,
            'sonuc' => $sonuc,
            'qrUri' => $qrUri,
            'dogrulamaUrl' => $dogrulamaUrl,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'canli-egitim-sertifika-'.Str::slug($sonuc['ad_soyad'] ?? 'katilimci').'.pdf',
        );
    }
}
