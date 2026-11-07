<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Services;

use App\Modules\Account\Enums\Level;
use App\Modules\Certificate\Models\Certificate;
use App\Modules\Language\Models\Language;
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

    /** A4 portrait in CSS pixels at 72 dpi: the page the app prints and shows. */
    public const WIDTH = 595;

    public const HEIGHT = 842;

    /** What each level means, in the manner of the CEFR "can do" descriptors (our own wording). */
    private const DESCRIPTORS = [
        'beginner' => 'Can understand and use familiar everyday expressions and very basic phrases, introduce themselves and others, and answer simple questions about personal details when the other person speaks slowly and clearly.',
        'elementary' => 'Can understand sentences and frequently used expressions on familiar topics, take part in short routine exchanges, and describe their background, surroundings and immediate needs in simple terms.',
        'intermediate' => 'Can deal with most situations likely to arise in everyday life, talk about experiences, plans and opinions, and produce simple connected speech on topics that are familiar or of personal interest.',
        'advanced' => 'Can interact with a degree of fluency and spontaneity that makes regular conversation with native speakers comfortable, and give clear, detailed views on a wide range of subjects.',
    ];

    public function html(Certificate $certificate): string
    {
        $url = $certificate->verifyUrl();

        return view('certificates.document', [
            'certificate' => $certificate,
            'language' => Language::query()->find($certificate->language)?->title['en'] ?? strtoupper($certificate->language),
            'levels' => Level::cases(),
            'descriptor' => self::DESCRIPTORS[$certificate->level->value],
            'skills' => [
                'Speaking' => $certificate->speaking,
                'Vocabulary' => $certificate->vocabulary,
                'Grammar' => $certificate->grammar,
            ],
            'fonts' => self::fonts(),
            'logo' => 'data:image/png;base64,'.base64_encode((string) file_get_contents(resource_path('images/certificate-logo.png'))),
            'qr' => self::qr($url),
            'verifyLabel' => preg_replace('#^https?://#', '', $url),
            'rosette' => Guilloche::rosette(121, 432, 44, 57, 18, 12),
            'seal' => Guilloche::rosette(527, 742, 22, 36, 14, 10),
            // Drawn upright, then turned on their side to run along the header band.
            'waves' => Guilloche::waves(84, self::WIDTH, 16),
            'nameSize' => self::nameSize($certificate->holder_name),
        ])->render();
    }

    /** Shrinks a long name so it stays on its line in the holder box. */
    private static function nameSize(string $name): int
    {
        return match (true) {
            mb_strlen($name) <= 24 => 24,
            mb_strlen($name) <= 34 => 20,
            mb_strlen($name) <= 48 => 16,
            mb_strlen($name) <= 80 => 12,
            default => 10,
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
