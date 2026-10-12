<?php

namespace App\Support;

use App\Models\AcilDurumPlani;
use Illuminate\Support\Carbon;

/**
 * Acil Durum Planı belgesinin (FOM MAKİNA ADEP düzeni) tüm değerleri —
 * PDF (`pdf/acil-durum-plani`) ve Word (`AcilDurumBelgeWord`) aynı diziyi
 * kullanır, böylece iki çıktı birbirinden sapmaz. Boş bırakılan işyerine
 * özgü bilgiler `null` döner; görünüm bunları noktalı satır olarak basar.
 */
class AcilDurumBelgeVerisi
{
    public static function hazirla(AcilDurumPlani $plan): array
    {
        $plan->loadMissing('firma.igu', 'firma.user');
        $firma = $plan->firma;
        $u = AcilDurumHazirlik::sablonlaTamamla(array_replace(AcilDurumHazirlik::varsayilan(), $plan->uygulama ?? []), $firma?->user);
        $dolu = fn ($x) => filled($x) ? trim((string) $x) : null;
        $tarih = fn ($t) => $t ? Carbon::parse($t)->format('d.m.Y') : null;

        // Hazırlayan (Md.12/1-b): firmaya atanmış İGU, yoksa hesap sahibi.
        $hesap = $firma?->user;
        $igu = $firma?->igu;
        $hazirlayan = [
            'ad' => $igu?->ad_soyad ?: $hesap?->name,
            'unvan' => $igu ? $igu->unvan : ($hesap?->unvan ? $hesap->unvanEtiketi() : null),
            'kase' => $igu?->kase_gorseli ?: $hesap?->kase_gorseli,
            'imza' => $igu?->imza_gorseli ?: $hesap?->imza_gorseli,
        ];

        $isveren = $dolu($firma?->isveren_ad);
        $isverenVekili = $dolu($firma?->isveren_vekili);

        // "Rev.01 — 01.01.2027" gibi serbest metinden tarih ayrılır.
        $revizyon = $dolu($plan->revizyon_no);
        $revizyonTarihi = $revizyon && preg_match('/\d{2}\.\d{2}\.\d{4}/', $revizyon, $m) ? $m[0] : null;
        $revizyonNo = $revizyon ? (trim(preg_replace('/[\s—–-]*\d{2}\.\d{2}\.\d{4}[\s—–-]*/u', ' ', $revizyon)) ?: null) : null;

        $ekipler = $plan->ekipDetaylari(array_keys(config('isg.acil_durum.belge_ekipleri')));

        $koordinator = [
            'ad' => $dolu($u['koordinator_ad']) ?? $isverenVekili ?? $isveren,
            'tel' => $dolu($u['koordinator_tel']),
            'vekil' => $dolu($u['koordinator_vekil']),
            'vekil_tel' => $dolu($u['koordinator_vekil_tel']),
        ];

        $sektor = AcilDurumSektor::profil($u, $firma);
        foreach ($sektor['onlemler_atif'] as $konu => $cumle) {
            if (in_array($konu, $plan->konular ?? [], true)) {
                $sektor['onlemler'] = trim($sektor['onlemler'].' '.$cumle);
            }
        }

        $senaryolar = collect(config('isg.acil_durum.konular'))
            ->whereIn('anahtar', $plan->konular ?? [])
            ->map(function (array $k) use ($sektor) {
                $s = config('acil_durum_senaryolari.'.$k['anahtar'], []);
                $s['aciklama'] = $sektor['senaryo_aciklama'][$k['anahtar']] ?? $s['aciklama'] ?? null;
                $akis = resource_path('belge/acil-durum-akis/'.$k['anahtar'].'.png');

                return [
                    'anahtar' => $k['anahtar'],
                    'ad' => $k['ad'],
                    'aciklama' => $s['aciklama'] ?? null,
                    'mudahale' => $s['mudahale'] ?? [],
                    'olay_sonrasi' => $s['olay_sonrasi'] ?? [],
                    'ek' => [...($sektor['senaryo_ek'][$k['anahtar']] ?? []), ...($s['ek'] ?? [])],
                    'akis' => is_file($akis) ? $akis : null,
                ];
            })->values()->all();

        $riskler = collect(config('isg.acil_durum.risk_listesi'))->map(function ($ad, $anahtar) use ($u) {
            $puan = collect(config('isg.acil_durum.risk_kolonlari'))->keys()
                ->mapWithKeys(fn ($k) => [$k => $u['risk_puanlari'][$anahtar][$k] ?? null])->all();
            $girilen = array_filter($puan);

            return ['ad' => $ad, 'puan' => $puan, 'toplam' => $girilen ? array_sum($girilen) : null];
        })->values()->all();

        // EK-2 iç iletişim: işveren, vekil, koordinatör, ardından ekip görevlileri.
        $icIletisim = [
            ['rol' => 'İŞVEREN', 'ad' => $isveren, 'tel' => $dolu($u['isveren_tel']) ?? $dolu($firma?->telefon)],
            ['rol' => 'İŞVEREN VEKİLİ', 'ad' => $isverenVekili, 'tel' => $dolu($u['isveren_vekili_tel'])],
            ['rol' => 'KOORDİNATÖR', 'ad' => $koordinator['ad'].($koordinator['vekil'] ? ' / Vekili: '.$koordinator['vekil'] : ''),
                'tel' => implode(' / ', array_filter([$koordinator['tel'], $koordinator['vekil_tel']])) ?: null],
        ];
        foreach (config('isg.acil_durum.belge_ekipleri') as $anahtar => $ekip) {
            foreach ($ekipler[$anahtar] ?? [] as $uye) {
                $icIletisim[] = ['rol' => $ekip['ad'], 'ad' => $uye['ad'].($uye['yedek'] ? ' (yedek)' : ''), 'tel' => $dolu($uye['telefon'])];
            }
        }

        // En yakın hastane: plan ekranındaki alanlar, boşsa acil iletişim
        // listesinde adı "hastane" geçen ilk kayıt (o kayıt ayrıca tekrar edilmez).
        $listeHastane = collect($u['iletisim'] ?? [])->first(fn ($i) => str_contains(mb_strtolower((string) ($i['ad'] ?? ''), 'UTF-8'), 'hastane'));
        $hastaneAd = $dolu($u['hastane_ad']) ?? $dolu($listeHastane['ad'] ?? null);
        $hastaneTel = $dolu($u['hastane_tel']) ?? $dolu($listeHastane['telefon'] ?? null);

        $disIletisim = collect(config('isg.acil_durum.dis_iletisim'))
            ->map(fn ($tel, $birim) => ['birim' => $birim, 'tel' => $tel, 'aciklama' => null])->values()->all();
        array_splice($disIletisim, 9, 0, [[
            'birim' => 'EN YAKIN HASTANE', 'tel' => $hastaneTel, 'aciklama' => $hastaneAd, 'doldurulacak' => true,
        ]]);
        foreach ($u['iletisim'] ?? [] as $i) {
            $ayniHastane = ($hastaneAd && trim((string) ($i['ad'] ?? '')) === $hastaneAd) || ($hastaneTel && trim((string) ($i['telefon'] ?? '')) === $hastaneTel);
            if (trim((string) ($i['telefon'] ?? '')) !== '112' && filled($i['ad'] ?? null) && ! $ayniHastane) {
                $disIletisim[] = ['birim' => mb_strtoupper(str_replace('i', 'İ', $i['ad']), 'UTF-8'), 'tel' => $dolu($i['telefon'] ?? null), 'aciklama' => $dolu($i['aciklama'] ?? null)];
            }
        }

        $cevresel = collect(config('isg.acil_durum.cevresel_riskler'))->map(fn ($r, $i) => [
            ...$r,
            'durum' => $u['cevresel_riskler'][$i]['durum'] ?? '',
            'not' => $dolu($u['cevresel_riskler'][$i]['not'] ?? null),
        ])->all();

        $makineler = $firma ? $firma->isEkipmanlari()->where('aktif', true)->pluck('ekipman_adi')
            ->countBy()->map(fn ($n, $ad) => $n > 1 ? "{$ad} ({$n})" : $ad)->values()->all() : [];
        $kimyasallar = $firma ? $firma->kimyasalUrunler()->pluck('urun_adi')->unique()->values()->all() : [];

        $durumYazi = fn (string $d, string $not) => match ($d) {
            'var' => $not !== '' ? $not : null,
            'uygulanamaz' => 'İşyerinde bulunmuyor / uygulanamaz.',
            default => null,
        };

        $degisiklikler = [[
            'no' => '01', 'konu' => 'İlk düzenleme', 'yapan' => $hazirlayan['ad'], 'tarih' => $tarih($plan->rapor_tarihi),
        ]];
        if ($revizyon) {
            $degisiklikler[] = ['no' => '02', 'konu' => 'Revizyon: '.$revizyon, 'yapan' => $hazirlayan['ad'], 'tarih' => $revizyonTarihi];
        }

        return [
            'plan' => $plan,
            'firma' => $firma,
            'u' => $u,
            'sektor' => $sektor,
            'unvan' => $firma?->unvan,
            'sgk' => $dolu($firma?->sgk_sicil_no),
            'adres' => $dolu($firma?->adres),
            'telefon' => $dolu($u['isveren_tel']) ?? $dolu($firma?->telefon),
            'logo' => $firma?->logo && is_file(storage_path('app/public/'.$firma->logo)) ? storage_path('app/public/'.$firma->logo) : null,
            'nace' => $dolu(trim(($firma?->nace_kodu ?? '').' '.($firma?->nace_aciklama ?? ''))),
            'faaliyet' => $dolu($firma?->nace_aciklama),
            'tehlike' => $firma ? mb_strtoupper(str_replace('i', 'İ', $firma->tehlikeSinifiEtiketi()), 'UTF-8') : null,
            'calisan' => (int) ($firma?->calisan_sayisi ?? 0),
            'isveren' => $isveren,
            'isveren_vekili' => $isverenVekili,
            'hazirlayan' => $hazirlayan,
            'dokuman_no' => $plan->dokuman_no,
            'yayin' => $tarih($plan->rapor_tarihi),
            'gecerlilik' => $tarih($plan->gecerlilik_tarihi),
            'revizyon_tarihi' => $revizyonTarihi,
            'revizyon_no' => $revizyonNo,
            'yenileme_yil' => config('isg.acil_durum.gecerlilik_yili.'.($firma?->tehlike_sinifi ?? 'az_tehlikeli'), 6),
            'koordinator' => $koordinator,
            'ekipler' => $ekipler,
            'senaryolar' => $senaryolar,
            'riskler' => $riskler,
            'ic_iletisim' => $icIletisim,
            'dis_iletisim' => $disIletisim,
            'cevresel' => $cevresel,
            'disaridan' => $dolu($plan->disaridan_etkileyebilecek_isyerleri),
            'makineler' => $makineler,
            'kimyasallar' => $kimyasallar,
            'toplanma' => $dolu($plan->toplanma_yeri),
            'ozel_risk' => $durumYazi($u['ozel_risk_alanlari'], (string) $u['ozel_risk_notu']),
            'enerji_kesme' => $durumYazi($u['enerji_kesme'], (string) $u['enerji_kesme_notu']),
            'son_tatbikat' => $tarih($u['son_tatbikat']),
            'sonraki_tatbikat' => $tarih($u['sonraki_tatbikat']),
            'tahliye_gorsel' => $plan->tahliyeGorselYolu(),
            'degisiklikler' => $degisiklikler,
        ];
    }
}
