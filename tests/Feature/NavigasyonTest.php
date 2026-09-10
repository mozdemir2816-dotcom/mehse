<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Sol menü — isgpratik 1-3.jpg. Kurulu modüller + "hazırlanıyor" stub sayfaları
 * tam görünmeli ve açılmalı.
 */
class NavigasyonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public static function sayfalar(): array
    {
        return [
            // Yönetim
            'kontrol merkezi' => ['/admin/kontrol-merkezi'],
            'profilim' => ['/admin/profilim'],
            'isg-katip robot' => ['/admin/isg-katip-robot'],
            'mevzuat' => ['/admin/mevzuat'],
            'firmalar' => ['/admin/firmalar'],
            'calisanlar' => ['/admin/calisanlar'],
            'isg profesyonelleri' => ['/admin/isg-profesyonelleri'],
            // Risk Yönetimi
            'risk sihirbazi' => ['/admin/risk-degerlendirme'],
            'kayitli degerlendirmeler' => ['/admin/risk-degerlendirmelerim'],
            'sektor sablonlari' => ['/admin/risk-sablonlari'],
            'jsa ise ozgu risk' => ['/admin/jsa'],
            'risk kutuphanesi' => ['/admin/risk-kutuphanesi'],
            'acil durum plani' => ['/admin/acil-durum-plani'],
            'acil durum krokisi' => ['/admin/acil-durum-krokisi'],
            // Formlar & Belgeler
            'dof olustur' => ['/admin/dof'],
            'ai saha analizi' => ['/admin/ai-saha-analizi'],
            'saha denetimi' => ['/admin/saha-denetimi'],
            'kurul toplantisi' => ['/admin/kurul-toplantisi'],
            'atama yazilari' => ['/admin/atama-yazilari'],
            'egitim katilim' => ['/admin/egitim-katilim'],
            'isbasi egitim' => ['/admin/isbasi-egitim'],
            'tatbikat tutanagi' => ['/admin/tatbikat'],
            'tespit oneri defteri' => ['/admin/tespit-oneri-defteri'],
            'onayli defter nushalari' => ['/admin/onayli-defter-nushalari'],
            'sertifika olustur' => ['/admin/sertifika'],
            'egitim sorulari' => ['/admin/egitim-sorulari'],
            'soru bankasi' => ['/admin/soru-bankasi'],
            'kkd formu' => ['/admin/kkd-formu'],
            'is izin formu' => ['/admin/is-izin-formu'],
            'ceza teblig' => ['/admin/ceza-teblig'],
            'is kazasi raporu' => ['/admin/is-kazasi-raporu'],
            'olay kayitlari' => ['/admin/olay-kayitlari'],
            'talimat olustur' => ['/admin/talimat'],
            'muayene formu' => ['/admin/muayene-formu'],
            'periyodik kontrol' => ['/admin/periyodik-kontrol'],
            'kkd secim matrisi' => ['/admin/kkd-secim-matrisi'],
            'kimyasal risk degerlendirmesi' => ['/admin/kimyasal-risk-degerlendirmesi'],
            'kimyasal sicili' => ['/admin/kimyasal-sicili'],
            'e-recetem' => ['/admin/e-recetem'],
            // Planlama & Arşiv
            'ortam olcumleri' => ['/admin/ortam-olcumleri'],
            'kaza istatistikleri' => ['/admin/kaza-istatistikleri'],
            'saglik gozetimi' => ['/admin/saglik-gozetimi'],
            'yillik planlar' => ['/admin/yillik-planlar'],
            'uzaktan egitim paketleri' => ['/admin/uzaktan-egitim-paketleri'],
            'uzaktan egitim atama' => ['/admin/uzaktan-egitim-atama'],
            'ziyaret programi' => ['/admin/ziyaret-programi'],
            'araclar' => ['/admin/araclar'],
        ];
    }

    #[DataProvider('sayfalar')]
    public function test_sayfa_acilir(string $url): void
    {
        $this->get($url)->assertSuccessful();
    }

    public function test_nav_gruplari_dogru_sirada(): void
    {
        $panel = Filament::getPanel('admin');
        $gruplar = collect($panel->getNavigationGroups())
            ->map(fn ($g) => is_string($g) ? $g : $g->getLabel())
            ->values()->all();

        $this->assertSame(
            ['Yönetim', 'Risk Yönetimi', 'Formlar & Belgeler', 'Planlama & Arşiv'],
            $gruplar,
        );
    }
}
