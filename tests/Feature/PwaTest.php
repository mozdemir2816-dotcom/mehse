<?php

namespace Tests\Feature;

use Tests\TestCase;

/** PWA (13.10.2026): telefona uygulama olarak kurulum — manifest, ikonlar, service worker. */
class PwaTest extends TestCase
{
    public function test_giris_sayfasi_manifest_ve_service_worker_baglar(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee(asset('manifest.webmanifest'), false)
            ->assertSee('apple-touch-icon', false)
            ->assertSee('serviceWorker', false)
            ->assertSee('mehse-pwa-bant', false);
    }

    public function test_manifest_gecerli_ve_ikonlari_mevcut(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        // Göreli yollar: hem yerel alt dizinde (/mehse/public) hem canlı kökte (/) çalışsın.
        $this->assertSame('admin', $manifest['start_url']);

        $boyutlar = [];
        foreach ($manifest['icons'] as $ikon) {
            $this->assertFileExists(public_path($ikon['src']));
            [$g, $y] = getimagesize(public_path($ikon['src']));
            $this->assertSame($ikon['sizes'], "{$g}x{$y}");
            $boyutlar[] = $ikon['sizes'].' '.($ikon['purpose'] ?? 'any');
        }

        $this->assertContains('192x192 any', $boyutlar);
        $this->assertContains('512x512 any', $boyutlar);
        $this->assertContains('512x512 maskable', $boyutlar);
        $this->assertFileExists(public_path('images/pwa/apple-touch-icon.png'));
    }

    public function test_service_worker_sayfa_ve_kisisel_veri_saklamaz(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        // Sayfa açılışları yalnız ağdan; önbellek yalnız çevrimdışı sayfası için.
        $this->assertStringContainsString("istek.mode === 'navigate'", $sw);
        $this->assertStringContainsString('fetch(istek).catch(() => caches.match(CEVRIMDISI))', $sw);
        // POST (Livewire) ve /storage (yüklenen evrak) hiç önbelleğe girmez.
        $this->assertStringContainsString("if (istek.method !== 'GET') return;", $sw);
        $this->assertStringContainsString("!url.pathname.includes('/storage/')", $sw);
        $this->assertFileExists(public_path('offline.html'));
    }
}
