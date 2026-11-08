<?php

namespace Tests\Feature;

use App\Support\RiskAlanTamamlama;
use Tests\TestCase;

class RiskAlanTamamlamaAiMetinTest extends TestCase
{
    public function test_tekrarlayan_bozuk_ya_da_uzun_ai_metni_reddedilir(): void
    {
        $bozuk = 'Elektrik İşletme Sorumlusu / Yetkili Ekip'.str_repeat(' free test team', 40);
        $this->assertNull(RiskAlanTamamlama::aiMetniGecerli($bozuk, 255));
        $this->assertNull(RiskAlanTamamlama::aiMetniGecerli(str_repeat('a', 300), 255));
        $this->assertNull(RiskAlanTamamlama::aiMetniGecerli('   ', 255));
        $this->assertSame('Elektrik İşletme Sorumlusu / Yetkili Ekip', RiskAlanTamamlama::aiMetniGecerli(' Elektrik İşletme Sorumlusu / Yetkili Ekip ', 255));
        $this->assertSame('Isıl işlem, ısıl yalıtım ve ısıl kontrol yapılır.', RiskAlanTamamlama::aiMetniGecerli('Isıl işlem, ısıl yalıtım ve ısıl kontrol yapılır.', 5000));
    }

    public function test_uygula_bozuk_sorumluyu_yazmaz(): void
    {
        $t = new \App\Models\Tehlike(['sorumlu' => null]);
        $degisen = RiskAlanTamamlama::uygula($t, ['sorumlu' => 'Ekip'.str_repeat(' free test team', 30)], true);
        $this->assertNotContains('sorumlu', $degisen);
        $this->assertNull($t->sorumlu);
    }
}
