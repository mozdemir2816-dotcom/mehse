<?php

namespace App\Support;

/**
 * Komuta Merkezi sayfalarının ortak "aktif firma" seçimi (29.10.2026). Kontrol Merkezi, Firma Denetim &
 * Operasyon, Risk Merkezi (ve eski İşyeri Durumu / Tesis Uygunluğu / Uzman Raporları adresleri) aynı
 * firmayı gösterir: bir sayfada seçilen firma oturumda saklanır, diğer sayfalar onu açar — her sayfada
 * firma yeniden seçtirilmez. null = "Tüm Portföy".
 */
class KomutaFirma
{
    private const ANAHTAR = 'komuta_merkezi.firma_id';

    public static function sec(?int $firmaId): void
    {
        session([self::ANAHTAR => $firmaId ?: null]);
    }

    public static function oturumdaki(): ?int
    {
        return session(self::ANAHTAR) ?: null;
    }

    /**
     * Sayfa açılırken firma: önce adresteki ?firma= (başka sayfalardan gelen bağlantılar), yoksa
     * oturumdaki seçim. Kullanıcının firma listesinde olmayan değer yok sayılır.
     *
     * @param  array<int, string>  $izinli  id => unvan
     */
    public static function baslangic(array $izinli): ?int
    {
        $adresten = request()->integer('firma') ?: null;
        $id = $adresten ?? self::oturumdaki();

        if (! $id || ! array_key_exists($id, $izinli)) {
            return null;
        }

        if ($adresten) {
            self::sec($id);
        }

        return $id;
    }
}
