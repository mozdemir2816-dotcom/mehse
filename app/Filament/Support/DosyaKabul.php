<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Symfony\Component\Mime\MimeTypes;

/**
 * FileUpload alanını dosya UZANTISIYLA sınırlar (MIME yerine).
 *
 * Neden: paylaşımlı sunucunun libmagic'i .docx/.xlsx'i yanlış tanıyabiliyor,
 * telefonlar tür bildirmeyebiliyor — sunucu doğrulaması `extensions:` ile.
 * Ama FilePond input'un `accept` özniteliğini kendi acceptedFileTypes listesi
 * olarak okur ve MIME ile karşılaştırır; listede yalnız ".docx" gibi uzantılar
 * olunca HER dosya tarayıcıda "Geçersiz dosya tipi" ile reddediliyordu
 * (06-07.10.2026, Hazır Rapor Yükle / DÖF aktarma). Bu yüzden:
 *  - accept = uzantılar + karşılık gelen MIME türleri (pencere uzantıyla,
 *    FilePond MIME ile eşleşir),
 *  - mimeTypeMap = uzantı → MIME (tarayıcı türü boş/yanlış bildirse de
 *    FilePond türü uzantıdan alır).
 */
class DosyaKabul
{
    /** @param  array<int, string>  $uzantilar  noktasız: ['pdf', 'docx'] */
    public static function uygula(FileUpload $alan, array $uzantilar): FileUpload
    {
        $uzantilar = array_map('strtolower', $uzantilar);

        return $alan
            ->rules(['extensions:'.implode(',', $uzantilar)])
            ->extraInputAttributes(['accept' => static::accept($uzantilar)])
            ->mimeTypeMap(static::harita($uzantilar));
    }

    /** @param  array<int, string>  $uzantilar */
    public static function accept(array $uzantilar): string
    {
        $mimeler = collect($uzantilar)
            ->flatMap(fn (string $u) => MimeTypes::getDefault()->getMimeTypes($u))
            ->unique();

        return collect($uzantilar)->map(fn (string $u) => '.'.$u)
            ->concat($mimeler)
            ->implode(',');
    }

    /** @param  array<int, string>  $uzantilar  @return array<string, string> */
    public static function harita(array $uzantilar): array
    {
        return collect($uzantilar)
            ->mapWithKeys(fn (string $u) => [$u => MimeTypes::getDefault()->getMimeTypes($u)[0] ?? 'application/octet-stream'])
            ->all();
    }
}
