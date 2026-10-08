<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/**
 * Çıktı almadan kaydetme (kullanıcı 08.10.2026: "her şeyde çıktı almak
 * istemiyorum, çıktı alarak kaydediyoruz"). Belge sayfalarında kayıt yalnız
 * PDF/Word/Excel düğmesine bağlıydı ve her çıktı YENİ kayıt açıyordu.
 *
 *  - "Kaydet": sayfanın kaydet()'ini çağırır, belgeyi hatırlar; tekrar
 *    kaydetmek ve ardından çıktı almak AYNI kaydı günceller (kopya açılmaz).
 *  - Çıktı alındıktan sonra form DEĞİŞMEDEN alınan diğer çıktılar aynı belgeyi
 *    kullanır (PDF + Excel + sertifika tek kayıt); form değiştirilip kaydedilirse
 *    yeni belge açılır — aynı formdan başka çalışanın / eğitimin belgesini
 *    üretme alışkanlığı ilkinin üzerine yazmaz.
 *  - "Yeni kayıt" ve firma değişimi hatırlananı bırakır.
 *
 * Kullanan sayfa: private kaydet(): ?Model sağlar; kaydet() içinde
 * `new Model([...])` yerine `$this->kayitIcin(Model::class, [...])` kullanır;
 * çıktı aksiyonları kaydet() başarılıysa ciktiAlindi() çağırır;
 * updatedFirmaId() içinde kayitUnut().
 */
trait KaydetSecenegi
{
    /** @var array<string, int> model sınıfı => hatırlanan kayıt id */
    public array $kayitlar = [];

    /** "Kaydet" ile hatırlanan belgenin görünen numarası. */
    public ?string $kayitNo = null;

    /** Son çıktıdaki belge içeriğinin parmak izi (null = çıktıdan sonra değişiklik serbest). */
    public ?string $ciktiImzasi = null;

    /** Bu istekte kayitIcin()'e verilen alan anahtarları ve oluşan model. */
    protected array $sonAlanAnahtarlari = [];

    protected ?Model $sonKayit = null;

    /**
     * Hatırlanan kayıt varsa alanlarla doldurulmuş hâli, yoksa yeni model.
     * Çıktıdan sonra form değişmişse hatırlanan bırakılır, yeni model döner.
     */
    protected function kayitIcin(string $sinif, array $alanlar): Model
    {
        $this->sonAlanAnahtarlari = array_keys($alanlar);
        $id = $this->kayitlar[$sinif] ?? null;
        $mevcut = $id ? $sinif::query()->whereKey($id)->when(isset($alanlar['firma_id']), fn ($q) => $q->where('firma_id', $alanlar['firma_id']))->first() : null;

        // Parmak izi kaydedilmiş belgeyle karşılaştırılır (kayıt sonrası eklenen
        // alanlar — ör. DÖF maddelerine bulgu_id — "değişiklik" sayılmasın).
        if ($mevcut && $this->ciktiImzasi !== null
            && static::belgeImzasi((clone $mevcut)->fill($alanlar), $this->sonAlanAnahtarlari) !== $this->ciktiImzasi) {
            $this->kayitUnut();
            $mevcut = null;
        }

        return $this->sonKayit = $mevcut ? $mevcut->fill($alanlar) : new $sinif($alanlar);
    }

    /** Çıktı aksiyonu, kaydet() başarılıysa çağırır: belge hatırlanır, kaydedilmiş hâli işaretlenir. */
    protected function ciktiAlindi(): void
    {
        if ($this->sonKayit?->exists) {
            $this->kayitHatirla($this->sonKayit);
            $this->ciktiImzasi = static::belgeImzasi($this->sonKayit->fresh() ?? $this->sonKayit, $this->sonAlanAnahtarlari);
        }
    }

    /** @param  array<int, string>  $anahtarlar */
    private static function belgeImzasi(Model $m, array $anahtarlar): string
    {
        return md5(json_encode(array_intersect_key($m->attributesToArray(), array_flip($anahtarlar))));
    }

    protected function kayitHatirla(Model $kayit): void
    {
        $this->kayitlar[$kayit::class] = $kayit->getKey();
        $this->kayitNo = $kayit->getAttribute('belge_no') ?: $kayit->getAttribute('sertifika_no') ?: '#'.$kayit->getKey();
    }

    public function kayitUnut(): void
    {
        $this->kayitlar = [];
        $this->kayitNo = null;
        $this->ciktiImzasi = null;
    }

    protected function kaydetAction(): Action
    {
        return Action::make('sadeceKaydet')
            ->label(fn () => $this->kayitNo ? 'Kaydet ('.$this->kayitNo.')' : 'Kaydet')
            ->icon('heroicon-o-check')
            ->color('success')
            ->tooltip('Çıktı almadan kaydeder; tekrar kaydetmek ve sonra çıktı almak aynı belgeyi günceller.')
            ->action(function (): void {
                $kayit = $this->kaydet();

                if (! $kayit) {
                    return;   // kaydet() kendi uyarısını gösterdi
                }

                $this->kayitHatirla($kayit);
                $this->ciktiImzasi = null;   // elle kaydedildi: sonraki değişiklikler aynı belgeyi günceller

                Notification::make()
                    ->title('Kaydedildi')
                    ->body($this->kayitNo.' — çıktıyı istediğiniz zaman alabilirsiniz.')
                    ->success()->send();
            });
    }

    protected function yeniKayitAction(): Action
    {
        return Action::make('yeniKayit')
            ->label('Yeni kayıt')
            ->icon('heroicon-o-document-plus')
            ->color('gray')
            ->visible(fn () => $this->kayitNo !== null)
            ->tooltip('Kaydedilen belgeyi bırakır; sonraki kayıt yeni belge olarak açılır (form alanları korunur).')
            ->action(function (): void {
                $this->kayitUnut();
                Notification::make()->title('Yeni kayıt')->body('Sonraki kayıt yeni belge olarak açılacak.')->send();
            });
    }
}
