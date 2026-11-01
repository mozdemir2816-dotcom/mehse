<?php

namespace Tests\Feature;

use App\Console\Commands\VeritabaniYedekle;
use App\Models\TeklifTalebi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class VeritabaniYedekleTest extends TestCase
{
    use RefreshDatabase;

    private string $gercekStorage;

    protected function setUp(): void
    {
        parent::setUp();
        // Gerçek yedek klasörüne dokunmamak için geçici storage.
        $this->gercekStorage = storage_path();
        $this->app->useStoragePath(storage_path('framework/testing/yedek-'.uniqid()));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path());
        $this->app->useStoragePath($this->gercekStorage);
        parent::tearDown();
    }

    public function test_gzipli_sql_yedegi_alir_ve_30_gunden_eskileri_siler(): void
    {
        TeklifTalebi::create(['firma_adi' => "O'Brien Metal", 'kisi' => 'K', 'telefon' => '0532', 'calisan_sayisi' => 3, 'tehlike_sinifi' => 'Tehlikeli']);
        $klasor = storage_path(VeritabaniYedekle::KLASOR);
        File::ensureDirectoryExists($klasor);
        File::put($eski = $klasor.'/mehse-2026-01-01_030000.sql.gz', 'x');
        touch($eski, now()->subDays(31)->getTimestamp());
        File::put($yeni = $klasor.'/mehse-2026-10-31_030000.sql.gz', 'x');
        touch($yeni, now()->subDays(2)->getTimestamp());

        $this->artisan('mehse:yedekle')->assertSuccessful();

        $this->assertFileDoesNotExist($eski);
        $this->assertFileExists($yeni);
        $dosyalar = collect(File::glob($klasor.'/mehse-*.sql.gz'))->reject(fn ($f) => $f === $yeni)->values();
        $this->assertCount(1, $dosyalar);

        $sql = gzdecode(File::get($dosyalar[0]));
        $this->assertStringContainsString('DROP TABLE IF EXISTS "teklif_talepleri"', $sql);
        $this->assertStringContainsString("'O''Brien Metal'", $sql);
    }

    public function test_her_gece_zamanlanir(): void
    {
        $gorev = collect(Schedule::events())->first(fn ($e) => $e->description === 'mehse:yedekle');

        $this->assertNotNull($gorev);
        $this->assertSame('0 3 * * *', $gorev->expression);
        $this->assertSame('Europe/Istanbul', $gorev->timezone);
    }
}
