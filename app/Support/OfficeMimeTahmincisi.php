<?php

namespace App\Support;

use Symfony\Component\Mime\MimeTypeGuesserInterface;
use ZipArchive;

/**
 * Office Open XML (.docx / .xlsx / .pptx) dosyalarını ZIP içeriğine bakarak
 * tanır. libmagic (fileinfo) bu dosyaları ZIP girişlerinin sırasına göre
 * tahmin eder; bazı Word sürümlerinin ürettiği .docx'ler (ör. customXml /
 * stylesWithEffects içerenler) "application/octet-stream" çıkıyor ve
 * `mimetypes:` / `mimes:` doğrulaması (Filament acceptedFileTypes) gerçek bir
 * Word dosyasını reddediyordu. AppServiceProvider'da Symfony MimeTypes'a ilk
 * sırada kaydedilir; Office dosyası değilse null döner ve sıradaki
 * tahminciye (fileinfo) düşer.
 */
class OfficeMimeTahmincisi implements MimeTypeGuesserInterface
{
    /** Ana içerik parçası → MIME (kontrol sırası önemli değil, tek biri bulunur). */
    private const PARCALAR = [
        'word/document.xml' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xl/workbook.xml' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt/presentation.xml' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    public function isGuesserSupported(): bool
    {
        return class_exists(ZipArchive::class);
    }

    public function guessMimeType(string $path): ?string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $imza = @file_get_contents($path, false, null, 0, 4);
        if ($imza !== "PK\x03\x04") {
            return null;
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return null;
        }

        try {
            if ($zip->locateName('[Content_Types].xml') === false) {
                return null;
            }

            foreach (static::PARCALAR as $parca => $mime) {
                if ($zip->locateName($parca) !== false) {
                    return $mime;
                }
            }

            return null;
        } finally {
            $zip->close();
        }
    }
}
