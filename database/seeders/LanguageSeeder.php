<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

/** The languages the app ships with. Further languages are added in the admin panel. */
final class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->languages() as $sort => $language) {
            Language::query()->updateOrCreate(['code' => $language['code']], $language + ['sort' => $sort, 'active' => true]);
        }
    }

    private function languages(): array
    {
        return [
            [
                'code' => 'ru',
                'name' => 'Russian',
                'title' => ['uz' => 'Rus tili', 'ru' => 'Русский', 'en' => 'Russian'],
                'tutor_name' => 'Anna',
                'script_note' => ' (Cyrillic)',
                'follow_ups' => '"А у вас как?", "Расскажете подробнее?"',
                'rules' => '- Address the learner politely with "вы" unless they clearly prefer informal speech.',
                'basic_words' => 'привет, да, нет',
                'dictionary_form' => 'infinitive, nominative singular',
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'title' => ['uz' => 'Ingliz tili', 'ru' => 'Английский', 'en' => 'English'],
                'tutor_name' => 'Emma',
                'script_note' => null,
                'follow_ups' => '"How about you?", "Tell me more?"',
                'rules' => '- Use natural British English spelling and everyday expressions; a warm, friendly tone with first names is fine.',
                'basic_words' => 'hello, yes, no',
                'dictionary_form' => 'base form: infinitive without "to", singular noun',
            ],
        ];
    }
}
