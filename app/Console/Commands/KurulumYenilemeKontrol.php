<?php

namespace App\Console\Commands;

use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Models\UzaktanEgitimFirmaAyari;
use App\Support\YilBasiTopluEgitim;
use Illuminate\Console\Command;

/**
 * SALT OKUNUR kontrol raporu (23.11.2026): Temel İSG Yenileme (4 ders saati) paketlerinin dersleri, süreleri,
 * sınav soruları, firma içerik onayları ve Yıl Başı Toplu Eğitim önizleme sayıları. Hiçbir kayıt yazmaz.
 * Canlıda: node deploy/onbellek.mjs --komut "kurulum:yenileme-kontrol"
 */
class KurulumYenilemeKontrol extends Command
{
    protected $signature = 'kurulum:yenileme-kontrol {--yil=}';

    protected $description = 'Yenileme eğitimi paketlerini ve yıl başı toplu eğitim önizlemesini raporlar (salt okunur)';

    public function handle(): int
    {
        $yil = (int) ($this->option('yil') ?: (now()->month >= 11 ? now()->year + 1 : now()->year));
        $dk = (int) config('isg.uzaktan_egitim.ders_saati_dk', 45);

        $paketler = EgitimPaketi::query()->with(['user', 'tumDersler'])->orderBy('id')->get();
        $this->line('== Paketler (tür) ==');
        foreach ($paketler as $p) {
            $this->line(sprintf('#%d %s | %s | %s | aktif=%s | sahibi=%s', $p->id, $p->kod, $p->ad, $p->turAnahtari(), $p->aktif ? 'evet' : 'hayır', $p->user?->name));
        }

        foreach ($paketler->where('egitim_turu', 'yenileme') as $p) {
            $p->unsetRelation('dersler');
            $hedef = $p->gerekenSure('az_tehlikeli');
            $this->newLine();
            $this->line("== YENİLEME PAKETİ #{$p->id}: {$p->ad} ==");
            $this->line('Açıklama: '.($p->aciklama ?: '—'));
            $this->line("Geçme puanı {$p->gecme_puani} · video izleme oranı %{$p->video_zorunlu_yuzde} · paylaşımlı=".($p->paylasildi ? 'evet' : 'hayır'));
            $this->line('Ders (tümü): '.$p->tumDersler->count().' · videolu: '.$p->dersler->count().' · video bekleyen: '.$p->videoBekleyenSayisi()
                .' · süresi boş: '.$p->dersler->whereNull('sure_sn')->count());
            foreach ($p->tumDersler as $d) {
                $this->line(sprintf('  M%s.%s %s | %s | %s', $d->modul ?? '-', $d->sira, $d->baslik,
                    $d->video_url ? ($d->saglayici ?? '?').' '.$d->video_url : 'VİDEO YOK',
                    $d->sure_sn ? round($d->sure_sn / 60, 1).' dk' : 'süre boş'));
            }
            $this->line(sprintf('Video %d dk + sınav %d dk = %d dk · hedef %d dk (%d ders saati) → %s',
                $p->toplamSureDk(), EgitimPaketi::sinavSureDk(), $p->onlineSureDk(), $hedef['gereken_dk'], $hedef['online_saat'],
                $p->onlineSureDk() >= $hedef['gereken_dk'] ? 'TAMAM' : 'EKSİK '.($hedef['gereken_dk'] - $p->onlineSureDk()).' dk'));
            $this->line('Yüz yüze kalan: az '.$p->gerekenSure('az_tehlikeli')['yuz_yuze_saat'].' / tehlikeli '.$p->gerekenSure('tehlikeli')['yuz_yuze_saat']
                .' / çok '.$p->gerekenSure('cok_tehlikeli')['yuz_yuze_saat'].' ders saati');
            $soru = $p->sorular()->get();
            $this->line('Soru: '.$soru->count().' (ortak '.$soru->where('kullanim', 'ortak')->count().', ön test '.$soru->where('kullanim', 'on_test')->count()
                .', final '.$soru->where('kullanim', 'final')->count().') · konulara göre: '.$soru->groupBy(fn ($s) => (int) $s->modul)->map->count()->toJson()
                .' · sınava hazır: '.($p->sinavaHazirMi() ? 'evet' : 'HAYIR')
                .' · çekilen: ön test '.config('isg.uzaktan_egitim.on_test_soru_sayisi').', final '.config('isg.uzaktan_egitim.final_soru_sayisi'));
            $this->line('Atama sayısı: '.$p->atamalar()->count());

            $firmalar = Firma::query()->where('user_id', $p->user_id)->orderBy('unvan')->get();
            $temel = EgitimPaketi::query()->gorunur($p->user_id)->where('egitim_turu', 'ilk_defa')->where('aktif', true)->withCount('atamalar')->get()
                ->sortByDesc(fn ($x) => [str_contains(mb_strtolower($x->ad), 'temel') ? 1 : 0, $x->atamalar_count])->first();
            $this->line('Varsayılan temel (8 ders saati) paket: '.($temel ? "#{$temel->id} {$temel->ad}" : 'YOK'));

            $this->newLine();
            $this->line("== {$yil} önizleme (firma: yenileme / temel / atlanan · onay Y/T · eksik) ==");
            $top = ['y' => 0, 't' => 0, 'hazir' => 0];
            foreach ($firmalar as $f) {
                $o = YilBasiTopluEgitim::firmaOnizleme($f, $yil, $temel, $p);
                if ($o['yenileme']->isEmpty() && $o['ilk_defa']->isEmpty()) {
                    continue;
                }
                $oy = UzaktanEgitimFirmaAyari::gonderimeHazirMi($f, $p) ? '✓' : '✗';
                $ot = $temel && UzaktanEgitimFirmaAyari::gonderimeHazirMi($f, $temel) ? '✓' : '✗';
                $top['y'] += $o['yenileme']->count();
                $top['t'] += $o['ilk_defa']->count();
                $top['hazir'] += $o['eksik'] ? 0 : 1;
                $this->line(sprintf('  %s: %d / %d / %d · onay %s%s · e-posta %s%s', $f->unvan, $o['yenileme']->count(), $o['ilk_defa']->count(),
                    $o['gecerli'] + $o['zaten_atanmis'], $oy, $ot, $f->eposta ?: 'YOK', $o['eksik'] ? ' · '.implode('; ', $o['eksik']) : ''));
            }
            $this->line("TOPLAM yenileme {$top['y']} · temel {$top['t']} · gönderime hazır firma {$top['hazir']}");
        }

        return self::SUCCESS;
    }
}
