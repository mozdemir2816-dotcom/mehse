<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\KurulToplantisi;
use Illuminate\Support\Collection;

/**
 * İSG Kurulu toplantısında önceki toplantıya KATILANLARI yeni toplantıya aktarır (kullanıcı 03.11.2026: iki ayrı firma
 * ortak kurul toplantısı yapıyor; her seferinde listeyi alıp güncellemekle uğraşılmasın).
 * Kaynak toplantı kullanıcının HERHANGİ bir firmasından seçilebilir (ortak toplantıda diğer firmanın kaydı da kullanılır).
 */
class KurulKatilimciAktarici
{
    /** Kullanıcının toplantı seçenekleri: önce bu firmanınkiler (yeniden eskiye), sonra diğer firmalarınkiler. @return array<int, string> */
    public static function kaynakSecenekleri(Firma $firma, ?int $haricToplantiId, int $userId): array
    {
        $firmaIdleri = Firma::query()->where('user_id', $userId)->pluck('id');

        return KurulToplantisi::query()
            ->with('firma:id,unvan')
            ->whereIn('firma_id', $firmaIdleri)
            ->whereNotNull('tarih')
            ->when($haricToplantiId, fn ($q) => $q->whereKeyNot($haricToplantiId))
            ->orderByRaw('CASE WHEN firma_id = ? THEN 0 ELSE 1 END', [$firma->id])
            ->orderByDesc('tarih')->orderByDesc('id')
            ->limit(60)->get()
            ->filter(fn (KurulToplantisi $t) => $t->katilanSayisi() > 0)
            ->mapWithKeys(fn (KurulToplantisi $t) => [$t->id => ($t->firma_id === $firma->id ? '' : '['.\Illuminate\Support\Str::limit((string) $t->firma?->unvan, 28).'] ')
                .($t->toplanti_no ?: 'Toplantı').' — '.$t->tarih->format('d.m.Y').' ('.$t->katilanSayisi().' katılan)'])
            ->all();
    }

    /** Varsayılan kaynak: bu firmanın, hedef toplantı tarihinden önceki (yoksa herhangi) son toplantısı; katılanı olmalı. */
    public static function varsayilanKaynakId(Firma $firma, ?KurulToplantisi $hedef = null, ?string $hedefTarih = null): ?int
    {
        $tarih = $hedef?->tarih?->toDateString() ?? $hedefTarih;

        $sorgu = fn (bool $oncesi) => KurulToplantisi::query()
            ->where('firma_id', $firma->id)->whereNotNull('tarih')
            ->when($hedef, fn ($q) => $q->whereKeyNot($hedef->id))
            ->when($oncesi && $tarih, fn ($q) => $q->whereDate('tarih', '<=', $tarih))
            ->orderByDesc('tarih')->orderByDesc('id')->get()
            ->first(fn (KurulToplantisi $t) => $t->katilanSayisi() > 0);

        return ($sorgu(true) ?? $sorgu(false))?->id;
    }

    /** Toplantıya KATILANLAR (Katıldı işaretli), aktarmaya hazır. @return array<int, array<string, mixed>> */
    public static function katilanlar(KurulToplantisi $kaynak): array
    {
        return collect($kaynak->katilimcilar ?? [])
            ->filter(fn ($k) => is_array($k) && ($k['katildi'] ?? false) && filled(trim((string) ($k['ad_soyad'] ?? ''))))
            ->map(fn (array $k) => [
                'ad_soyad' => trim((string) $k['ad_soyad']),
                'gorev' => ($k['gorev'] ?? null) ?: null,
                'rol' => ($k['rol'] ?? null) ?: null,
                'katildi' => true,
            ])->values()->all();
    }

    /**
     * Hedef toplantının katılım listesini kaynaktaki katılanlarla DEĞİŞTİRİR ($mod 'degistir') ya da onlara EKLER
     * ('ekle': aynı adlı kayıt varsa mevcut korunur, eksik görev/rol tamamlanır).
     *
     * @return int aktarılan (yeni listeye giren) kişi sayısı
     */
    public static function aktar(KurulToplantisi $hedef, KurulToplantisi $kaynak, string $mod = 'degistir'): int
    {
        if ($hedef->firma?->user_id !== $kaynak->firma?->user_id) {
            return 0;   // başka kullanıcının toplantısı
        }

        $katilanlar = collect(self::katilanlar($kaynak));

        if ($mod !== 'ekle') {
            $hedef->update(['katilimcilar' => $katilanlar->all()]);

            return $katilanlar->count();
        }

        $liste = collect($hedef->katilimcilar ?? []);
        $anahtar = fn ($k) => EgitimTakibi::adAnahtari($k['ad_soyad'] ?? '');   // Türkçe İ/I ve boşluk farkını yok sayar
        $eklenen = 0;

        foreach ($katilanlar as $k) {
            $var = $liste->search(fn ($m) => $anahtar($m) === $anahtar($k));

            if ($var === false) {
                $liste->push($k);
                $eklenen++;

                continue;
            }

            $mevcut = $liste[$var];
            $liste[$var] = [...$mevcut, 'gorev' => ($mevcut['gorev'] ?? null) ?: $k['gorev'], 'rol' => ($mevcut['rol'] ?? null) ?: $k['rol']];
        }

        $hedef->update(['katilimcilar' => $liste->values()->all()]);

        return $eklenen;
    }
}
