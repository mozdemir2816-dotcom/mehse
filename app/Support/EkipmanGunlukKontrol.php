<?php

namespace App\Support;

use App\Models\IsEkipmani;
use App\Models\IsEkipmaniBakimi;
use App\Models\IsEkipmaniGunlukKontrolu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Operatörün günlük kontrol kaydı (12.11.2026) — QR operatör ekranı ve panel aynı yolu kullanır.
 * Bir madde "Uygun Değil" ise: arıza kaydı açılır (makine kullanıma uygun değil), firmanın İSG uzmanına
 * panel bildirimi (BildirimTarayici — kayıt kapanana kadar açık kalır), bakım sorumlusuna e-posta.
 */
class EkipmanGunlukKontrol
{
    /**
     * @param  array<int, array{cevap?: string, not?: string}>  $cevaplar  soru sırasına göre
     */
    public static function kaydet(IsEkipmani $ekipman, string $operator, array $cevaplar, ?string $not = null, string $kaynak = 'qr', ?string $ip = null): IsEkipmaniGunlukKontrolu
    {
        $form = EkipmanKatalogu::kontrolFormu($ekipman->tip);
        $sorular = $form['sorular'] ?? [];

        $satirlar = [];
        foreach ($sorular as $i => $soru) {
            $cevap = (string) ($cevaplar[$i]['cevap'] ?? '');
            $satirlar[] = [
                'soru' => $soru,
                'cevap' => array_key_exists($cevap, IsEkipmaniGunlukKontrolu::CEVAPLAR) ? $cevap : 'Y',
                'not' => Str::limit(trim((string) ($cevaplar[$i]['not'] ?? '')), 500, ''),
            ];
        }
        $uygunsuz = array_values(array_filter($satirlar, fn (array $s) => $s['cevap'] === 'UD'));

        [$kontrol, $bakim] = DB::transaction(function () use ($ekipman, $operator, $form, $satirlar, $uygunsuz, $not, $kaynak, $ip) {
            $kontrol = $ekipman->gunlukKontroller()->create([
                'kontrol_zamani' => now(),
                'operator_adi' => Str::limit(trim($operator), 120, ''),
                'form_adi' => $form['ad'] ?? null,
                'cevaplar' => $satirlar,
                'uygunsuz_sayisi' => count($uygunsuz),
                'kullanima_uygun' => $uygunsuz === [],
                'notu' => filled($not) ? Str::limit(trim($not), 1000, '') : null,
                'kaynak' => $kaynak,
                'ip' => $ip,
            ]);

            $bakim = $uygunsuz === [] ? null : $ekipman->bakimlar()->create([
                'gunluk_kontrol_id' => $kontrol->id,
                'tur' => 'ariza',
                'durum' => 'acik',
                'baslik' => 'Günlük kontrolde '.count($uygunsuz).' uygunsuzluk — '.$ekipman->ekipman_adi,
                'aciklama' => $kontrol->notu,
                'uygunsuz_maddeler' => $uygunsuz,
                'bildiren' => $kontrol->operator_adi,
                'sorumlu' => $ekipman->bakim_sorumlusu,
                'acilis_zamani' => now(),
            ]);

            return [$kontrol, $bakim];
        });

        if ($bakim) {
            static::bildir($ekipman, $bakim);
        }

        return $kontrol;
    }

    /** İSG uzmanına panel bildirimi (tarama) + bakım sorumlusuna e-posta. Hata kaydı engellemez. */
    public static function bildir(IsEkipmani $ekipman, IsEkipmaniBakimi $bakim): void
    {
        $firma = $ekipman->firma;

        try {
            if ($firma?->user) {
                BildirimTarayici::tara($firma->user, $firma->id);
            }
        } catch (Throwable $e) {
            Log::warning('Arıza bildirimi taranamadı', ['bakim' => $bakim->id, 'hata' => $e->getMessage()]);
        }

        $alici = trim((string) $ekipman->bakim_sorumlusu_eposta);
        if (! filter_var($alici, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $maddeler = collect($bakim->uygunsuz_maddeler ?? [])
            ->map(fn (array $m) => '- '.$m['soru'].(filled($m['not'] ?? null) ? ' ('.$m['not'].')' : ''))->implode("\n");
        $metin = "Sayın {$ekipman->bakim_sorumlusu},\n\n"
            .($firma?->unvan ?? '')." firmasındaki \"{$ekipman->ekipman_adi}\""
            .($ekipman->seri_no ? " (seri no {$ekipman->seri_no})" : '')
            ." için operatör {$bakim->bildiren} günlük kontrolde uygunsuzluk bildirdi. Makine onarılıp kontrol edilene kadar KULLANILMAMALIDIR.\n\n"
            ."Uygun olmayan maddeler:\n{$maddeler}\n"
            .($bakim->aciklama ? "\nOperatör notu: {$bakim->aciklama}\n" : '')
            ."\nArıza kaydı no: {$bakim->id} — ".$bakim->acilis_zamani->format('d.m.Y H:i');

        try {
            Mail::raw($metin, fn ($m) => $m->to($alici)->subject('Arıza bildirimi: '.$ekipman->ekipman_adi));
        } catch (Throwable $e) {
            Log::warning('Arıza e-postası gönderilemedi', ['bakim' => $bakim->id, 'hata' => $e->getMessage()]);
        }
    }
}
