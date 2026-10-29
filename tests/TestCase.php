<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    /**
     * Her test süreci kendi TEST_TOKEN'ını alır; Storage::fake() sahte disk
     * klasörünü bu token'la ayırır. Aksi halde eşzamanlı iki `artisan test`
     * (veya --parallel) aynı sahte klasörü silip birbirinin dosyasını yok eder.
     * --parallel altında paratest zaten token verir, ona dokunulmaz.
     */
    protected function setUp(): void
    {
        if (empty($_SERVER['TEST_TOKEN'])) {
            $_SERVER['TEST_TOKEN'] = $_ENV['TEST_TOKEN'] = 'p'.getmypid();
            register_shutdown_function(function (): void {
                $fs = new Filesystem;
                foreach (glob(dirname(__DIR__).'/storage/framework/testing/*_test_p'.getmypid()) ?: [] as $klasor) {
                    $fs->deleteDirectory($klasor);
                }
                foreach (glob(dirname(__DIR__).'/storage/framework/testing/disks/*_test_p'.getmypid()) ?: [] as $klasor) {
                    $fs->deleteDirectory($klasor);
                }
            });
        }

        parent::setUp();

        // 'local' disk de sahte: aksi halde testler gerçek storage/app/private'a yazar ve süreçler çakışır.
        Storage::fake('local');
    }
}
