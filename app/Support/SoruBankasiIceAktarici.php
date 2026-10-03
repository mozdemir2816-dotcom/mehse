<?php

namespace App\Support;

use App\Models\SoruBankasiSorusu;

/**
 * Soru Bankası toplu taslak yükleme (isgsuite "JSON şablonu / Toplu taslak
 * yükle"). Her kayıt doğrulanır; geçerli olanlar TASLAK olarak eklenir,
 * hatalılar satır numarasıyla raporlanır. Yayımlama yine elle yapılır.
 */
class SoruBankasiIceAktarici
{
    /** İndirilebilir örnek şablon. */
    public static function sablon(): array
    {
        return [[
            'soru_kodu' => 'NACE-41.00-001',
            'konu' => 'yuksekte_calisma',
            'zorluk' => 'orta',
            'soru' => 'Yüksekte çalışmada düşmeye karşı öncelikli önlem hangisidir?',
            'secenekler' => ['Toplu koruma (korkuluk, ağ) sağlamak', 'Yalnız emniyet kemeri vermek', 'Çalışanı uyarmak', 'İşi hızlandırmak'],
            'dogru' => 'A',
            'gerekce' => 'Kontrol hiyerarşisinde toplu koruma, kişisel korumadan önce gelir.',
            'kapsam' => ['sektor' => 'insaat', 'nace' => ['41', '43.21']],
            'kaynaklar' => [['ad' => 'Yapı İşlerinde İş Sağlığı ve Güvenliği Yönetmeliği', 'url' => 'https://www.mevzuat.gov.tr/', 'madde' => 'Ek-4', 'tarih' => now()->toDateString()]],
        ]];
    }

    /**
     * @return array{kayitlar: array<int, array<string, mixed>>, hatalar: array<int, string>}
     */
    public static function cozumle(string $json): array
    {
        $veri = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $json), true);

        if (! is_array($veri)) {
            return ['kayitlar' => [], 'hatalar' => ['Dosya geçerli bir JSON değil: '.json_last_error_msg()]];
        }
        if (! array_is_list($veri)) {
            $veri = $veri['sorular'] ?? [$veri];
        }

        $konular = config('isg.soru_bankasi.konular');
        $sektorler = config('isg.risk_ai.sektorler');
        $kayitlar = [];
        $hatalar = [];

        foreach ($veri as $i => $r) {
            $no = $i + 1;
            $sec = array_values(array_map(fn ($s) => trim((string) $s), (array) ($r['secenekler'] ?? [])));
            $dogru = $r['dogru'] ?? $r['dogru_index'] ?? null;
            $dogruIndex = is_int($dogru) ? $dogru : (is_string($dogru) && preg_match('/^[A-Da-d]$/', $dogru) ? ord(strtoupper($dogru)) - 65 : null);
            $kaynaklar = collect((array) ($r['kaynaklar'] ?? []))->filter(fn ($k) => is_array($k) && filled($k['ad'] ?? null))
                ->map(fn ($k) => ['ad' => trim($k['ad']), 'url' => $k['url'] ?? null, 'madde' => $k['madde'] ?? null, 'tarih' => $k['tarih'] ?? null])->values()->all();
            $sektor = $r['kapsam']['sektor'] ?? null;

            $hata = match (true) {
                blank($r['soru'] ?? null) => 'soru metni boş',
                count($sec) !== 4 || count(array_filter($sec)) !== 4 => '4 dolu seçenek gerekli',
                count(array_unique(array_map('mb_strtolower', $sec))) !== 4 => 'seçenekler birbirinden farklı olmalı',
                $dogruIndex === null || $dogruIndex < 0 || $dogruIndex > 3 => 'doğru cevap A-D olmalı',
                blank($r['gerekce'] ?? $r['aciklama'] ?? null) => 'doğru cevabın gerekçesi boş',
                $kaynaklar === [] => 'en az bir doğrulanabilir kaynak gerekli',
                filled($r['konu'] ?? null) && ! array_key_exists($r['konu'], $konular) => 'bilinmeyen konu: '.$r['konu'],
                filled($sektor) && ! array_key_exists($sektor, $sektorler) => 'bilinmeyen sektör: '.$sektor,
                default => null,
            };

            if ($hata) {
                $hatalar[] = "{$no}. kayıt: {$hata}";

                continue;
            }

            $kayitlar[] = [
                'soru_kodu' => filled($r['soru_kodu'] ?? null) ? mb_substr(trim($r['soru_kodu']), 0, 60) : null,
                'surum' => max(1, (int) ($r['surum'] ?? 1)),
                'konu' => $r['konu'] ?? 'genel_isg',
                'zorluk' => array_key_exists($r['zorluk'] ?? '', config('isg.soru_bankasi.zorluklar')) ? $r['zorluk'] : 'orta',
                'soru' => trim($r['soru']),
                'secenekler' => $sec,
                'dogru_index' => $dogruIndex,
                'aciklama' => trim($r['gerekce'] ?? $r['aciklama']),
                'kaynaklar' => $kaynaklar,
                'kaynak' => $kaynaklar[0]['ad'],
                'sektor_anahtari' => $sektor ?: null,
                'nace_onekleri' => SoruBankasiSorusu::naceOnekleriniHazirla(implode(',', (array) ($r['kapsam']['nace'] ?? []))),
                'inceleme_notu' => $r['inceleme_notu'] ?? null,
            ];
        }

        return ['kayitlar' => $kayitlar, 'hatalar' => $hatalar];
    }
}
