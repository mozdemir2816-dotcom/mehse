<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\SahaBulgusu;
use App\Models\SaglikGozetimi;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Canlı Portföy Verisini Sorgulayan Akıllı İSG Asistanı (K3).
 * Mevzuat dışındaki operasyonel soruları kullanıcının kendi gerçek DB verisinden
 * sıfır API maliyetiyle ve anlık olarak yanıtlar.
 */
class CanliPortfoyAsistani
{
    public const HIZLI_SORGULAR = [
        ['anahtar' => 'dof', 'etiket' => '📊 Açık & Gecikmiş DÖF\'ler', 'soru' => 'Açık ve gecikmiş DÖF kayıtlarımı özetle'],
        ['anahtar' => 'egitim', 'etiket' => '🎓 Yaklaşan / Eksik Eğitimler', 'soru' => 'Eğitim süresi yaklaşan veya eksik çalışanları listele'],
        ['anahtar' => 'ihmal', 'etiket' => '⏳ İhmal Edilen Firmalar', 'soru' => 'Uzun süredir gidilmeyen ihmal edilen firmalar hangileri'],
        ['anahtar' => 'ekipman', 'etiket' => '🔧 Uygunsuz / Yaklaşan Ekipmanlar', 'soru' => 'Periyodik kontrolü uygunsuz veya süresi yaklaşan ekipmanlar'],
        ['anahtar' => 'saglik', 'etiket' => '🩺 Yaklaşan Muayeneler', 'soru' => 'Sağlık muayenesi süresi yaklaşan veya geçen kayıtlar'],
    ];

    /**
     * Soru canlı portföy verisiyle ilgiliyse anlık gerçek veriyi döner; değilse null (mevzuat danışmanına düşer).
     *
     * @return array{yanit: string, kaynaklar: array<int, string>, canli_veri: bool}|null
     */
    public static function sor(User $user, string $soru): ?array
    {
        $q = mb_strtolower(strtr(trim($soru), ['İ' => 'i', 'I' => 'ı']), 'UTF-8');

        $esles = fn (array $kelimeler): bool => collect($kelimeler)->contains(fn (string $k) => str_contains($q, $k));

        $firmalar = Firma::query()->where('user_id', $user->id)->where('aktif', true)->get();
        $firmaIdler = $firmalar->pluck('id')->all();

        // 1) Açık / gecikmiş DÖF ve bulgular
        if ($esles(['döf', 'dof', 'bulgu', 'uygunsuzluk', 'kapanış', 'kapanis'])) {
            $aciklar = SahaBulgusu::query()
                ->whereIn('firma_id', $firmaIdler)
                ->where('durum', '!=', 'kapandi')
                ->with('firma:id,unvan')
                ->orderByRaw('termin IS NULL, termin ASC')
                ->get();

            $geciken = $aciklar->filter(fn (SahaBulgusu $b) => $b->termin && $b->termin->isPast())->count();
            $onayBekleyen = $aciklar->filter->isverenOnayiBekliyorMu()->count();

            $satirlar = [
                "📊 Canlı DÖF & Saha Bulgusu Özeti ({$firmalar->count()} aktif firma):",
                "• Toplam açık kayıt: {$aciklar->count()} | Gecikmiş: {$geciken} | İşveren kanıtı onay bekleyen: {$onayBekleyen}",
            ];

            foreach ($aciklar->take(6) as $b) {
                $t = $b->termin?->format('d.m.Y') ?: 'Terminsiz';
                $satirlar[] = "  - {$b->bulgu_no} ({$b->firma?->unvan}) · {$t}: ".\Illuminate\Support\Str::limit((string) $b->uygunsuzluk, 70);
            }

            if ($aciklar->isEmpty()) {
                $satirlar[] = '✓ Harika! Aktif firmalarınızda açık DÖF/bulgu bulunmuyor.';
            }

            return ['yanit' => implode("\n", $satirlar), 'kaynaklar' => ['Canlı Portföy · Saha Bulguları'], 'canli_veri' => true];
        }

        // 2) Eğitim süresi yaklaşan / eksik çalışanlar
        if ($esles(['eğitim', 'egitim']) && $esles(['yaklaşan', 'yaklasan', 'eksik', 'süresi', 'suresi', 'listele', 'çalışan', 'calisan', 'kimler'])) {
            $dolmus = 0;
            $yaklasan = 0;
            $kayitsiz = 0;
            $detay = [];

            foreach ($firmalar->take(25) as $f) {
                $oz = EgitimTakibi::ozet(EgitimTakibi::satirlar($f));
                $dolmus += $oz['dolmus'];
                $yaklasan += $oz['yaklasan'];
                $kayitsiz += $oz['kayit_yok'];
                if ($oz['dolmus'] + $oz['yaklasan'] + $oz['kayit_yok'] > 0) {
                    $detay[] = "  - {$f->secimEtiketi()}: {$oz['dolmus']} dolmuş, {$oz['yaklasan']} yaklaşan, {$oz['kayit_yok']} kayıtsız";
                }
            }

            $satirlar = [
                '🎓 Canlı İSG Eğitim Durumu:',
                "• Süresi dolmuş: {$dolmus} | Yaklaşan: {$yaklasan} | Kaydı olmayan çalışan: {$kayitsiz}",
                ...array_slice($detay, 0, 6),
            ];
            if (! $detay) {
                $satirlar[] = '✓ Tüm aktif firmalarda çalışan eğitimleri güncel görünüyor.';
            }

            return ['yanit' => implode("\n", $satirlar), 'kaynaklar' => ['Canlı Portföy · Eğitim Yenileme Takibi'], 'canli_veri' => true];
        }

        // 3) İhmal edilen firmalar / ziyaretler
        if ($esles(['ihmal', 'gidilmeyen', 'ziyaret', 'uğranmayan', 'ugranmayan'])) {
            $ihmal = OsgbKadroAnalizi::ihmalEdilenFirmalar($user->id, 8);
            $satirlar = ['⏳ En Uzun Süredir Ziyaret Edilmeyen Firmalar:'];
            foreach ($ihmal as $ih) {
                $durum = $ih['gecen_gun'] === null ? 'Hiç gidilmedi' : $ih['gecen_gun'].' gündür ziyaret yok';
                $satirlar[] = "  - {$ih['unvan']} ({$ih['tehlike_sinifi']}) · {$durum}";
            }
            if (! $ihmal) {
                $satirlar[] = 'Aktif firma kaydı bulunamadı.';
            }

            return ['yanit' => implode("\n", $satirlar), 'kaynaklar' => ['Canlı Portföy · OSGB Kadro & Ziyaret Takibi'], 'canli_veri' => true];
        }

        // 4) Periyodik kontrol / ekipman
        if ($esles(['ekipman', 'periyodik', 'vize', 'uygunsuz'])) {
            $ekipmanlar = IsEkipmani::query()
                ->whereIn('firma_id', $firmaIdler)
                ->where('aktif', true)
                ->with('firma:id,unvan')
                ->get();

            $uygunsuz = $ekipmanlar->filter(fn (IsEkipmani $e) => $e->vizeDurumu() === 'uygun_degil');
            $dolmus = $ekipmanlar->filter(fn (IsEkipmani $e) => $e->vizeDurumu() === 'dolmus');
            $yaklasan = $ekipmanlar->filter(fn (IsEkipmani $e) => $e->vizeDurumu() === 'yaklasan');

            $satirlar = [
                '🔧 Canlı Periyodik Kontrol & Ekipman Özeti:',
                "• Uygun Değil (kilitli): {$uygunsuz->count()} | Süresi dolmuş: {$dolmus->count()} | Yaklaşan: {$yaklasan->count()}",
            ];
            foreach ($uygunsuz->merge($dolmus)->merge($yaklasan)->take(6) as $e) {
                $satirlar[] = "  - {$e->ekipman_adi} ({$e->firma?->unvan}) · {$e->vizeDurumEtiketi()}";
            }

            return ['yanit' => implode("\n", $satirlar), 'kaynaklar' => ['Canlı Portföy · Periyodik Kontrol'], 'canli_veri' => true];
        }

        // 5) Sağlık muayeneleri
        if ($esles(['muayene', 'sağlık', 'saglik', 'tetkik', 'hekim']) && $esles(['yaklaşan', 'yaklasan', 'geçen', 'gecen', 'eksik', 'listele', 'özet', 'ozet', 'kimler'])) {
            $bugun = Carbon::today();
            $gecen = 0;
            $yaklasan = 0;
            $ornek = [];

            foreach (SaglikGozetimi::query()->whereIn('firma_id', $firmaIdler)->with('firma:id,unvan')->get() as $sg) {
                foreach ((array) ($sg->satirlar ?? []) as $s) {
                    if (empty($s['sonraki_tarih'])) {
                        continue;
                    }
                    $t = Carbon::parse($s['sonraki_tarih'])->startOfDay();
                    $fark = (int) $bugun->diffInDays($t, false);
                    if ($fark < 0) {
                        $gecen++;
                        if (count($ornek) < 6) {
                            $ornek[] = "  - ".($s['ad_soyad'] ?? 'Çalışan')." ({$sg->firma?->unvan}) · Süresi geçti ({$t->format('d.m.Y')})";
                        }
                    } elseif ($fark <= 30) {
                        $yaklasan++;
                        if (count($ornek) < 6) {
                            $ornek[] = "  - ".($s['ad_soyad'] ?? 'Çalışan')." ({$sg->firma?->unvan}) · {$fark} gün kaldı ({$t->format('d.m.Y')})";
                        }
                    }
                }
            }

            $satirlar = [
                '🩺 Canlı Sağlık Gözetimi Özeti:',
                "• Süresi geçmiş muayene/tetkik: {$gecen} | 30 gün içinde yaklaşan: {$yaklasan}",
                ...$ornek,
            ];

            return ['yanit' => implode("\n", $satirlar), 'kaynaklar' => ['Canlı Portföy · Sağlık Gözetimi'], 'canli_veri' => true];
        }

        // 6) Genel portföy / özet / bugün ne yapmalıyım
        if ($esles(['portföy', 'portfoy', 'özet', 'ozet', 'bugün ne', 'bugun ne', 'durumum'])) {
            $ozet = PortfoyKarne::ozet($user->id);
            $uyusmaz = count(OsgbKadroAnalizi::sinifUyusmazliklari($user->id));
            $acikDof = SahaBulgusu::query()->whereIn('firma_id', $firmaIdler)->where('durum', '!=', 'kapandi')->count();

            $yanit = implode("\n", [
                '📌 Canlı Portföy Durum Özetiniz:',
                "• Aktif Firma: {$ozet['firma']} | Kayıtlı Çalışan: {$ozet['calisan']} | Genel Uyum: %{$ozet['uyum_yuzde']}",
                "• Tam Uyumlu Firma: {$ozet['tam_uyumlu']} | Evrak Eksiği Olan: {$ozet['evrak_eksigi']}",
                "• Açık DÖF / Bulgu: {$acikDof} | Uzman Sınıf Uyuşmazlığı: {$uyusmaz}",
            ]);

            return ['yanit' => $yanit, 'kaynaklar' => ['Canlı Portföy · Kontrol Merkezi'], 'canli_veri' => true];
        }

        return null;
    }
}
