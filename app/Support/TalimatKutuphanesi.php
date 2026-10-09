<?php

namespace App\Support;

/**
 * Talimat Oluştur hazır şablon kütüphanesi: `isg.talimat.sablonlar` (genel,
 * düz maddeli) + `talimat_insaat` (kullanıcının kendi inşaat talimatları,
 * birebir; Kalıp/Demir bölümlü). İndeksler birleşik listedeki sıradır —
 * genel şablonlar önde olduğundan eski 'hazir' anahtarları değişmez.
 */
class TalimatKutuphanesi
{
    /** @return array<int, array<string, mixed>> */
    public static function hazirSablonlar(): array
    {
        return array_values([...config('isg.talimat.sablonlar', []), ...config('talimat_insaat', [])]);
    }

    /** Şablondan firmaya kaydedilecek talimat alanları. */
    public static function talimatAlanlari(array $sablon): array
    {
        return [
            'baslik' => $sablon['baslik'],
            'kategori' => $sablon['kategori'] ?? null,
            'aciklama' => $sablon['aciklama'] ?? null,
            'dokuman_no' => $sablon['dokuman_no'] ?? null,
            'kkdler' => $sablon['kkdler'] ?? [],
            'maddeler' => $sablon['maddeler'] ?? [],
            'bolumler' => $sablon['bolumler'] ?? null,
            'taahhut' => $sablon['taahhut'] ?? null,
        ];
    }

    /**
     * Düz talimatı kullanıcının bölümlü yapısına çevirir (Kalıp/Demir talimatı
     * düzeni). Mevcut maddeler "Genel Kurallar"a, KKD'ler KKD bölümüne gider;
     * acil durum ve çalışan yükümlülükleri kullanıcının talimatlarındaki
     * ortak maddelerle dolar. Boş kalan bölümler çıktıya basılmaz.
     *
     * @param  array<int, string>  $maddeler
     * @param  array<int, string>  $kkdler
     * @return array<int, array{baslik: string, aciklama: string, maddeler: array<int, string>}>
     */
    public static function bolumIskeleti(string $baslik, array $maddeler = [], array $kkdler = []): array
    {
        $konu = trim(preg_replace('/\s*(güvenli\s+)?(çalışma\s+)?talimatı$/iu', '', $baslik)) ?: $baslik;

        return [
            ['baslik' => 'AMAÇ', 'aciklama' => 'Bu talimatın amacı; '.$konu.' sırasında çalışanların sağlık ve güvenliğini sağlamak, iş kazalarını ve meslek hastalıklarını önlemektir.', 'maddeler' => []],
            ['baslik' => 'KAPSAM', 'aciklama' => 'Bu talimat; '.$konu.' ile ilgili tüm faaliyetlerde çalışan personeli kapsar.', 'maddeler' => []],
            ['baslik' => 'GENEL KURALLAR', 'aciklama' => '', 'maddeler' => array_values($maddeler)],
            ['baslik' => 'KİŞİSEL KORUYUCU DONANIMLAR (KKD)', 'aciklama' => $kkdler ? 'Çalışanlar aşağıdaki ekipmanları kullanmak zorundadır:' : '', 'maddeler' => array_values($kkdler)],
            ['baslik' => 'YASAKLAR', 'aciklama' => '', 'maddeler' => []],
            ['baslik' => 'ACİL DURUM VE BİLDİRİM', 'aciklama' => '', 'maddeler' => [
                'Tehlike durumları derhal yetkililere bildirilmelidir.',
                'İş kazaları anında rapor edilmelidir.',
                'Acil durum prosedürlerine uyulmalıdır.',
            ]],
            ['baslik' => 'ÇALIŞAN YÜKÜMLÜLÜKLERİ', 'aciklama' => '', 'maddeler' => [
                'Tüm iş güvenliği kurallarına uymak zorunludur.',
                'KKD kullanımı ihmal edilmemelidir.',
                'İş güvenliği ekipmanları izinsiz kaldırılmamalıdır.',
                'Güvenli olmayan durumlarda çalışma durdurulmalıdır.',
            ]],
        ];
    }
}
