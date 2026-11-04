<?php

declare(strict_types=1);

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Models\Language;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * The printable page. The app turns this HTML into a PDF on the phone, which keeps the fonts off the
 * app bundle and lets the design change without a release. Everything is inlined, so printing needs
 * no further network request.
 */
final class CertificateDocument
{
    /** Each family ships per script, and the browser takes only those the name needs. */
    private const SCRIPTS = [
        'cyrillic' => 'U+0301,U+0400-045F,U+0490-0491,U+04B0-04B1,U+2116',
        'latin-ext' => 'U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF',
        'latin' => 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD',
    ];

    private const FACES = [
        ['Playfair Display', 'playfair-display', 600, 'normal'],
        ['Playfair Display', 'playfair-display', 400, 'italic'],
        ['Inter', 'inter', 400, 'normal'],
        ['Inter', 'inter', 600, 'normal'],
    ];

    public function html(Certificate $certificate): string
    {
        $url = $certificate->verifyUrl();

        return view('certificates.document', [
            'certificate' => $certificate,
            'language' => Language::query()->find($certificate->language)?->title['en'] ?? strtoupper($certificate->language),
            'fonts' => self::fonts(),
            'logo' => 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('images/certificate-logo.png'))),
            'qr' => self::qr($url),
            'verifyLabel' => preg_replace('#^https?://#', '', $url),
            'rosette' => Guilloche::rosette(118, 232, 62, 92, 18, 12),
            'waves' => Guilloche::waves(236, 595, 30),
            'nameSize' => self::nameSize($certificate->holder_name),
        ])->render();
    }

    /** Shrinks a long name so it stays inside its two-line slot. */
    private static function nameSize(string $name): int
    {
        return match (true) {
            mb_strlen($name) <= 20 => 46,
            mb_strlen($name) <= 28 => 36,
            mb_strlen($name) <= 40 => 27,
            mb_strlen($name) <= 80 => 22,
            default => 16,
        };
    }

    private static function fonts(): string
    {
        $css = '';

        foreach (self::FACES as [$family, $file, $weight, $style]) {
            foreach (self::SCRIPTS as $script => $range) {
                $data = base64_encode((string) file_get_contents(resource_path("fonts/certificate/{$file}-{$script}-{$weight}-{$style}.woff2")));
                $css .= "@font-face{font-family:'{$family}';font-style:{$style};font-weight:{$weight};src:url(data:font/woff2;base64,{$data}) format('woff2');unicode-range:{$range}}";
            }
        }

        return $css;
    }

    private static function qr(string $url): string
    {
        return (new QRCode(new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => false,
            'svgAddXmlHeader' => false,
            'connectPaths' => true,
            'drawLightModules' => false,
            'markupDark' => '#0B1F3A',
        ])))->render($url);
    }
}
