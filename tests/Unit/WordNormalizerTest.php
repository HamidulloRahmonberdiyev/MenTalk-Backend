<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Vocabulary\WordNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WordNormalizerTest extends TestCase
{
    public static function spellings(): array
    {
        return [
            'arabic tashkeel' => ['ar', 'كَتَبَ', 'كتب'],
            'arabic alif with hamza above' => ['ar', 'أكل', 'اكل'],
            'arabic alif with hamza below' => ['ar', 'إسلام', 'اسلام'],
            'arabic alif madda' => ['ar', 'آمن', 'امن'],
            'arabic tatweel and shadda' => ['ar', 'مـحـمَّد', 'محمد'],
            'russian yo' => ['ru', 'Ёлка', 'елка'],
            'russian stress mark' => ['ru', 'молоко́', 'молоко'],
            'case and spaces' => ['en', "  Ice   Cream\t", 'ice cream'],
            'decomposed letters' => ['ru', "е\u{0308}ж", 'еж'],
        ];
    }

    #[DataProvider('spellings')]
    public function test_spellings_of_one_word_share_a_key(string $language, string $text, string $expected): void
    {
        $this->assertSame($expected, (new WordNormalizer)->normalize($text, $language));
    }

    public function test_other_languages_keep_their_letters(): void
    {
        // ё is a letter of its own outside Russian, and alif variants only merge in Arabic.
        $this->assertSame('ёж', (new WordNormalizer)->normalize('Ёж', 'en'));
        $this->assertSame('أ', (new WordNormalizer)->normalize('أ', 'ru'));
    }
}
