<?php

namespace Tests\Feature;

use App\Filament\Pages\Ayarlar;
use App\Models\Bildirim;
use App\Models\Firma;
use App\Models\PushAbonelik;
use App\Models\User;
use App\Support\TelefonBildirimi;
use App\Support\WebPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Telefon bildirimi (Web Push, 13.10.2026). Şifreleme biçimi ayrıca Node
 * http_ece referans kütüphanesiyle çözülerek doğrulandı; bu testler alıcı
 * tarafını PHP'de çözerek aynı biçimi korur.
 */
class TelefonBildirimiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->uzman = User::factory()->create();
    }

    public function test_sifreli_yuk_alici_anahtariyla_cozulur(): void
    {
        [$aliciOzel, $aliciAcik] = $this->aliciAnahtari();
        $auth = random_bytes(16);

        $govde = WebPush::sifrele('{"title":"Çalışan · ğüşıöç"}', $aliciAcik, $auth);

        $this->assertSame('{"title":"Çalışan · ğüşıöç"}', $this->coz($govde, $aliciOzel, $aliciAcik, $auth));
    }

    public function test_vapid_basligi_gecerli_es256_imzasi_tasir(): void
    {
        $baslik = WebPush::vapidBasligi('https://fcm.googleapis.com/fcm/send/abc', 1_700_000_000);

        $this->assertMatchesRegularExpression('/^vapid t=[\w-]+\.[\w-]+\.[\w-]+, k=[\w-]+$/', $baslik);
        preg_match('/t=([^,]+), k=(.+)$/', $baslik, $m);
        [$h, $p, $s] = explode('.', $m[1]);

        $iddia = json_decode(WebPush::b64c($p), true);
        $this->assertSame('https://fcm.googleapis.com', $iddia['aud']);
        $this->assertSame(1_700_000_000 + 12 * 3600, $iddia['exp']);

        $imza = WebPush::b64c($s);
        $this->assertSame(64, strlen($imza));
        $this->assertSame(1, openssl_verify("$h.$p", $this->hamdanDer($imza), $this->acikPem(WebPush::b64c($m[2])), OPENSSL_ALGO_SHA256));
        $this->assertSame($m[2], WebPush::acikAnahtar());
    }

    public function test_gonderim_basliklari_ve_basari_kaydi(): void
    {
        Http::fake(['*' => Http::response('', 201)]);
        $abonelik = $this->abonelik();

        $this->assertSame('gonderildi', WebPush::gonder($abonelik, ['title' => 'Deneme']));

        Http::assertSent(fn (Request $r) => $r->url() === $abonelik->endpoint
            && $r->header('Content-Encoding')[0] === 'aes128gcm'
            && $r->header('TTL')[0] === '86400'
            && str_starts_with($r->header('Authorization')[0], 'vapid t='));
        $this->assertNotNull($abonelik->fresh()->son_basari_at);
    }

    public function test_tarayici_birakan_abonelik_silinir(): void
    {
        Http::fake(['*' => Http::response('', 410)]);
        $abonelik = $this->abonelik();

        $this->assertSame('gecersiz', WebPush::gonder($abonelik, ['title' => 'Deneme']));
        $this->assertModelMissing($abonelik);
    }

    public function test_gunluk_ozet_yeni_uyarilari_bir_kez_gonderir(): void
    {
        Http::fake(['*' => Http::response('', 201)]);
        Firma::factory()->for($this->uzman)->create(['unvan' => 'Deneme Yapı', 'aktif' => true]);
        $this->abonelik();

        $ilk = TelefonBildirimi::gunlukOzet($this->uzman);

        $this->assertGreaterThan(0, $ilk['uyari']);
        $this->assertSame(1, $ilk['gonderildi']);
        Http::assertSentCount(1);
        $this->assertSame(0, Bildirim::where('user_id', $this->uzman->id)->whereIn('seviye', TelefonBildirimi::SEVIYELER)->whereNull('push_at')->count());
        // "bilgi" seviyesi (ör. sözleşme bitişi girilmemiş) telefona gönderilmez.
        $this->assertGreaterThan(0, Bildirim::where('seviye', 'bilgi')->count());
        $this->assertSame(0, Bildirim::where('seviye', 'bilgi')->whereNotNull('push_at')->count());

        // Ertesi gün aynı uyarılar tekrar gönderilmez.
        $ikinci = TelefonBildirimi::gunlukOzet($this->uzman);
        $this->assertSame(0, $ikinci['uyari']);
        Http::assertSentCount(1);
    }

    public function test_komut_abone_kullanicilara_gonderir(): void
    {
        Http::fake(['*' => Http::response('', 201)]);
        Firma::factory()->for($this->uzman)->create(['aktif' => true]);
        $this->abonelik();
        Firma::factory()->for(User::factory()->create())->create(['aktif' => true]); // abonesiz kullanıcı

        $this->artisan('mehse:telefon-bildirimi')->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_abonelik_yoksa_hic_gonderim_yok(): void
    {
        Http::fake();
        Firma::factory()->for($this->uzman)->create(['aktif' => true]);

        $this->assertSame(0, TelefonBildirimi::gunlukOzet($this->uzman)['abonelik']);
        Http::assertNothingSent();
    }

    public function test_ozet_basligi_sayilari_ve_ilk_uc_maddeyi_icerir(): void
    {
        $bildirimler = collect([
            new Bildirim(['seviye' => 'kritik', 'baslik' => 'İşyeri hekimi atanmamış']),
            new Bildirim(['seviye' => 'uyari', 'baslik' => 'Sözleşme bitişi yaklaşıyor']),
            new Bildirim(['seviye' => 'uyari', 'baslik' => 'İSG-KATİP no eksik']),
            new Bildirim(['seviye' => 'uyari', 'baslik' => 'Dördüncü']),
        ]);

        $yuk = TelefonBildirimi::ozetYuku($bildirimler);

        $this->assertSame('MEHSE · 1 kritik, 3 uyarı', $yuk['title']);
        $this->assertStringContainsString('• İşyeri hekimi atanmamış', $yuk['body']);
        $this->assertStringContainsString('… ve 1 bildirim daha', $yuk['body']);
        $this->assertStringNotContainsString('Dördüncü', $yuk['body']);
    }

    public function test_ayarlardan_abone_olunur_ve_kaldirilir(): void
    {
        $this->actingAs($this->uzman);
        $abonelik = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/xyz', 'keys' => ['p256dh' => 'BAAA', 'auth' => 'abcd']];

        Livewire::test(Ayarlar::class)
            ->assertSee('Telefon bildirimleri')
            ->call('pushAboneOl', $abonelik, 'Android · Chrome');

        $this->assertSame('Android · Chrome', PushAbonelik::where('user_id', $this->uzman->id)->sole()->cihaz);

        // Aynı cihaz tekrar abone olursa çift kayıt olmaz.
        Livewire::test(Ayarlar::class)->call('pushAboneOl', $abonelik, 'Android · Chrome');
        $this->assertSame(1, PushAbonelik::count());

        Livewire::test(Ayarlar::class)->call('pushAbonelikKaldir', $abonelik['endpoint']);
        $this->assertSame(0, PushAbonelik::count());
    }

    public function test_http_olmayan_abonelik_reddedilir(): void
    {
        $this->actingAs($this->uzman);

        Livewire::test(Ayarlar::class)->call('pushAboneOl', ['endpoint' => 'http://kotu.example/x', 'keys' => ['p256dh' => 'a', 'auth' => 'b']]);

        $this->assertSame(0, PushAbonelik::count());
    }

    public function test_baskasinin_cihazi_silinemez(): void
    {
        $baskasi = PushAbonelik::kaydet(User::factory()->create()->id, ['endpoint' => 'https://x.example/1', 'keys' => ['p256dh' => 'a', 'auth' => 'b']]);
        $this->actingAs($this->uzman);

        Livewire::test(Ayarlar::class)->call('pushCihazSil', $baskasi->id);

        $this->assertModelExists($baskasi);
    }

    private function abonelik(): PushAbonelik
    {
        [, $acik] = $this->aliciAnahtari();

        return PushAbonelik::kaydet($this->uzman->id, [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-'.uniqid(),
            'keys' => ['p256dh' => WebPush::b64($acik), 'auth' => WebPush::b64(random_bytes(16))],
        ], 'Android · Chrome');
    }

    /** @return array{0: \OpenSSLAsymmetricKey, 1: string} */
    private function aliciAnahtari(): array
    {
        $ini = php_ini_loaded_file();
        $ayar = PHP_OS_FAMILY === 'Windows' && $ini ? ['config' => dirname($ini).'/extras/ssl/openssl.cnf'] : [];
        $ozel = openssl_pkey_new($ayar + ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $ec = openssl_pkey_get_details($ozel)['ec'];

        return [$ozel, "\x04".str_pad($ec['x'], 32, "\0", STR_PAD_LEFT).str_pad($ec['y'], 32, "\0", STR_PAD_LEFT)];
    }

    /** Alıcı (tarayıcı) tarafı — RFC 8291 çözme. */
    private function coz(string $govde, \OpenSSLAsymmetricKey $aliciOzel, string $aliciAcik, string $auth): string
    {
        $tuz = substr($govde, 0, 16);
        $idlen = ord($govde[20]);
        $gondericiAcik = substr($govde, 21, $idlen);
        $sifreli = substr($govde, 21 + $idlen);

        $ortak = openssl_pkey_derive($this->acikPem($gondericiAcik), $aliciOzel, 32);
        $ikm = hash_hkdf('sha256', $ortak, 32, "WebPush: info\0".$aliciAcik.$gondericiAcik, $auth);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $tuz);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $tuz);

        $duz = openssl_decrypt(substr($sifreli, 0, -16), 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, substr($sifreli, -16));
        $this->assertNotFalse($duz, 'GCM etiketi doğrulanamadı');
        $this->assertSame("\x02", substr($duz, -1));

        return substr($duz, 0, -1);
    }

    private function acikPem(string $ham): string
    {
        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode(hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$ham), 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }

    private function hamdanDer(string $ham): string
    {
        $tamsayi = function (string $b): string {
            $b = ltrim($b, "\0");
            if ($b === '' || ord($b[0]) > 0x7F) {
                $b = "\0".$b;
            }

            return "\x02".chr(strlen($b)).$b;
        };
        $icerik = $tamsayi(substr($ham, 0, 32)).$tamsayi(substr($ham, 32));

        return "\x30".chr(strlen($icerik)).$icerik;
    }
}
