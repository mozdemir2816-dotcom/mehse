<?php

namespace Tests\Feature;

use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\SahaBulgusu;
use App\Models\SahaDenetimi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KirikFotografTemizlemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_diskte_olmayan_kirik_fotograflari_temizler_gecerli_olanlari_korur(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('dof-foto/gecerli.jpg', 'img');

        $uzman = User::factory()->create();
        $firma = Firma::factory()->for($uzman)->create();

        $bulgu = SahaBulgusu::create([
            'firma_id' => $firma->id,
            'uygunsuzluk' => 'Korkuluk eksik',
            'fotograflar' => ['dof-foto/gecerli.jpg', 'dof-foto/eksik.jpeg'],
        ]);

        $sadeceKirik = SahaBulgusu::create([
            'firma_id' => $firma->id,
            'uygunsuzluk' => 'Kablo açıkta',
            'fotograflar' => ['dof-foto/yok.jpeg'],
        ]);

        $dof = DofRaporu::create([
            'firma_id' => $firma->id,
            'rapor_tarihi' => today(),
            'maddeler' => [
                ['tespit' => 'A', 'foto_yolu' => 'dof-foto/gecerli.jpg'],
                ['tespit' => 'B', 'foto_yolu' => 'dof-foto/eksik-dof.jpeg'],
            ],
        ]);

        $denetim = SahaDenetimi::create([
            'firma_id' => $firma->id,
            'denetim_tarihi' => today(),
            'durum' => 'taslak',
            'cevaplar' => [
                'M1' => ['sonuc' => 'uygun_degil', 'fotolar' => ['dof-foto/gecerli.jpg', 'dof-foto/yok-denetim.jpeg']],
            ],
        ]);

        // Dry-run veritabanına dokunmaz
        $this->artisan('kurulum:kirik-fotograflari-temizle', ['--dry-run' => true])
            ->assertSuccessful();
        $this->assertCount(2, $bulgu->fresh()->fotograflar);

        // Gerçek temizlik: yalnız eksik dosyalar kalkar, geçerliler ve metin kayıtları kalır
        $this->artisan('kurulum:kirik-fotograflari-temizle')
            ->assertSuccessful();

        $this->assertSame(['dof-foto/gecerli.jpg'], $bulgu->fresh()->fotograflar);
        $this->assertNull($sadeceKirik->fresh()->fotograflar);
        $this->assertSame('Kablo açıkta', $sadeceKirik->fresh()->uygunsuzluk);
        $this->assertSame('dof-foto/gecerli.jpg', $dof->fresh()->maddeler[0]['foto_yolu']);
        $this->assertNull($dof->fresh()->maddeler[1]['foto_yolu']);
        $this->assertSame(['dof-foto/gecerli.jpg'], $denetim->fresh()->cevaplar['M1']['fotolar']);
    }

    public function test_storage_yedek_rotasi_oturumla_public_dosyayi_sunar_ve_izinsiz_engeller(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('dof-foto/ornek.jpeg', 'fake-jpeg-bytes');

        // Misafir erişemez (girişe yönlenir)
        $this->get('/storage/dof-foto/ornek.jpeg')->assertRedirect();

        // Oturum açmış kullanıcı görebilir; path traversal ve izinli olmayan uzantılar 404
        $this->actingAs(User::factory()->create());
        $this->get('/storage/dof-foto/ornek.jpeg')->assertOk();
        $this->get('/storage/dof-foto/olmayan.jpeg')->assertNotFound();
        $this->get('/storage/../.env')->assertNotFound();
        $this->get('/storage/dof-foto/test.php')->assertNotFound();
    }
}
