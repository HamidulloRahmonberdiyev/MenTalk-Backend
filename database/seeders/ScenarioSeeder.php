<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Scenario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * The scenarios the app ships with, with a brief for every language it ships with. Briefs may use
 * the language placeholders ({tutor}, {language}); they are filled when the prompt is built.
 */
final class ScenarioSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->scenarios() as $sort => $scenario) {
            $model = Scenario::query()->updateOrCreate(
                ['id' => $scenario['id']],
                Arr::except($scenario, ['briefs', 'topics']) + ['sort' => $sort, 'active' => true],
            );

            foreach ($scenario['briefs'] as $language => $brief) {
                $model->briefs()->updateOrCreate(['language' => $language], ['brief' => $brief, 'next_topics' => $scenario['topics']]);
            }
        }
    }

    private function scenarios(): array
    {
        return [
            [
                'id' => 'intro',
                'icon' => 'people',
                'tint' => '#2AABEE',
                'gradient' => ['#5CC3F2', '#1E88C7'],
                'title' => ['uz' => 'Tanishuv', 'ru' => 'Знакомство', 'en' => 'Meeting people'],
                'subtitle' => ['uz' => 'Tanishing va o\'zingiz haqingizda gapiring', 'ru' => 'Познакомьтесь и расскажите о себе', 'en' => 'Get acquainted and talk about yourself'],
                'briefs' => [
                    'ru' => 'Two people meeting for the first time at a friendly event in Moscow. You are {tutor}, getting to know the learner: name, where they are from, work or studies, hobbies.',
                    'en' => 'Two people meeting for the first time at a friendly event in London. You are {tutor}, getting to know the learner: name, where they are from, work or studies, hobbies.',
                ],
                'topics' => 'family, hometown, favourite food, music and films, travel, weekend plans, dreams, daily routine',
            ],
            [
                'id' => 'cafe',
                'icon' => 'cafe',
                'tint' => '#EF5B4C',
                'gradient' => ['#B07A50', '#4A2F1F'],
                'title' => ['uz' => 'Kafeda', 'ru' => 'В кафе', 'en' => 'At the café'],
                'subtitle' => ['uz' => 'Buyurtma, menyu, to\'lov', 'ru' => 'Заказ, меню, оплата', 'en' => 'Ordering, menu, payment'],
                'briefs' => [
                    'ru' => 'A café in Moscow. You are {tutor}, the waitress. Take the learner\'s order: drinks, food, dessert, then payment (card or cash).',
                    'en' => 'A café in London. You are {tutor}, the waitress. Take the learner\'s order: drinks, food, dessert, then payment (card or cash).',
                ],
                'topics' => 'favourite dishes and cuisines, recipes, restaurants in their city, coffee or tea habits, trying something new, who they eat out with',
            ],
            [
                'id' => 'shop',
                'icon' => 'cart',
                'tint' => '#22B573',
                'gradient' => ['#4FCB8F', '#137A4C'],
                'title' => ['uz' => 'Do\'konda', 'ru' => 'В магазине', 'en' => 'At the store'],
                'subtitle' => ['uz' => 'Xarid, narxlar, savollar', 'ru' => 'Покупки, цены, вопросы', 'en' => 'Shopping, prices, questions'],
                'briefs' => [
                    'ru' => 'A clothing and grocery store in Moscow. You are {tutor}, the shop assistant. Help the learner find things, answer questions about sizes, prices, payment.',
                    'en' => 'A clothing and grocery store in London. You are {tutor}, the shop assistant. Help the learner find things, answer questions about sizes, prices, payment.',
                ],
                'topics' => 'fashion and favourite colours, gifts for friends and family, prices at home versus here, weekend shopping habits, a recent purchase',
            ],
            [
                'id' => 'taxi',
                'icon' => 'car',
                'tint' => '#F5A524',
                'gradient' => ['#FBC658', '#C77A0B'],
                'title' => ['uz' => 'Taksida', 'ru' => 'В такси', 'en' => 'In a taxi'],
                'subtitle' => ['uz' => 'Yo\'nalish, manzil, to\'lov', 'ru' => 'Маршруты, адрес, оплата', 'en' => 'Routes, address, payment'],
                'briefs' => [
                    'ru' => 'A taxi ride in Moscow. You are {tutor}, the driver. Ask where to go, discuss the route, the address, how long it takes and how to pay.',
                    'en' => 'A London taxi ride. You are {tutor}, the driver. Ask where to go, discuss the route, the address, how long it takes and how to pay.',
                ],
                'topics' => 'the city they see through the window, traffic, favourite places, weather, plans for the evening, travel stories',
            ],
            [
                'id' => 'work',
                'icon' => 'briefcase',
                'tint' => '#5A8DEE',
                'gradient' => ['#7FA8F5', '#2F5FC4'],
                'title' => ['uz' => 'Ishda', 'ru' => 'На работе', 'en' => 'At work'],
                'subtitle' => ['uz' => 'Hamkasblar, vazifalar, muloqot', 'ru' => 'Коллеги, задачи, общение', 'en' => 'Colleagues, tasks, small talk'],
                'briefs' => [
                    'ru' => 'An office in Moscow. You are {tutor}, a friendly colleague. Small talk, today\'s tasks, a meeting, plans for the week.',
                    'en' => 'An office in London. You are {tutor}, a friendly colleague. Small talk, today\'s tasks, a meeting, plans for the week.',
                ],
                'topics' => 'their job or studies, colleagues, lunch breaks, a hobby after work, plans for the weekend, a holiday they are looking forward to',
            ],
            [
                'id' => 'airport',
                'icon' => 'airplane',
                'tint' => '#17B2D6',
                'gradient' => ['#5CC3F2', '#0A6796'],
                'title' => ['uz' => 'Aeroportda', 'ru' => 'В аэропорту', 'en' => 'At the airport'],
                'subtitle' => ['uz' => 'Ro\'yxatdan o\'tish, pasport nazorati', 'ru' => 'Регистрация, паспортный контроль', 'en' => 'Check-in, passport control'],
                'briefs' => [
                    'ru' => 'Sheremetyevo Airport, Moscow. You are {tutor}, the check-in and passport-control officer. Check-in, luggage, documents, boarding, the purpose of the trip.',
                    'en' => 'Heathrow Airport, London. You are {tutor}, the check-in and passport-control officer. Check-in, luggage, documents, boarding, the purpose of the trip.',
                ],
                'topics' => 'travel stories, favourite countries, what they like to do in a new city, packing, the trip ahead, family waiting at home',
            ],
            [
                'id' => 'doctor',
                'icon' => 'medkit',
                'tint' => '#EF4444',
                'gradient' => ['#FECACA', '#EF4444'],
                'title' => ['uz' => 'Shifokorda', 'ru' => 'У врача', 'en' => 'At the doctor'],
                'subtitle' => ['uz' => 'Shikoyatlaringizni ayting', 'ru' => 'Расскажите о симптомах', 'en' => 'Describe your symptoms'],
                'briefs' => [
                    'ru' => 'A clinic in Moscow. You are {tutor}, a calm family doctor. The learner is a patient describing how they feel.',
                    'en' => 'A GP surgery in London. You are {tutor}, a calm family doctor. The learner is a patient describing how they feel.',
                ],
                'topics' => 'symptoms, how long, medicine, advice, healthy habits, sleep and sport',
            ],
        ];
    }
}
