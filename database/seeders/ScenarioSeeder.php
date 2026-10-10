<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Scenario;
use Illuminate\Database\Seeder;

final class ScenarioSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->scenarios() as $sort => $scenario) {
            Scenario::query()->updateOrCreate(['id' => $scenario['id']], $scenario + ['sort' => $sort, 'active' => true]);
        }
    }

    private function scenarios(): array
    {
        return [
            [
                'id' => 'cafe',
                'icon' => 'cafe',
                'tint' => '#F59E0B',
                'gradient' => ['#FDE68A', '#F59E0B'],
                'title' => ['uz' => 'Kafe', 'ru' => 'Кафе', 'en' => 'Cafe'],
                'subtitle' => ['uz' => 'Kofe buyurtma qiling', 'ru' => 'Закажите кофе', 'en' => 'Order a coffee'],
                'brief' => 'You are a friendly waiter in a small cafe. The learner is a customer ordering food and drinks.',
                'next_topics' => 'drinks, desserts, size and extras, paying the bill',
            ],
            [
                'id' => 'airport',
                'icon' => 'airplane',
                'tint' => '#3B82F6',
                'gradient' => ['#BFDBFE', '#3B82F6'],
                'title' => ['uz' => 'Aeroport', 'ru' => 'Аэропорт', 'en' => 'Airport'],
                'subtitle' => ['uz' => 'Ro\'yxatdan o\'ting', 'ru' => 'Пройдите регистрацию', 'en' => 'Check in for a flight'],
                'brief' => 'You are an airline check-in agent. The learner is a passenger checking in for a flight.',
                'next_topics' => 'luggage, seat choice, boarding time, gate',
            ],
            [
                'id' => 'shop',
                'icon' => 'cart',
                'tint' => '#10B981',
                'gradient' => ['#A7F3D0', '#10B981'],
                'title' => ['uz' => 'Do\'kon', 'ru' => 'Магазин', 'en' => 'Shop'],
                'subtitle' => ['uz' => 'Xarid qiling', 'ru' => 'Сделайте покупки', 'en' => 'Go shopping'],
                'brief' => 'You are a shop assistant in a clothing store. The learner is a customer looking for something to buy.',
                'next_topics' => 'sizes, colors, price, trying on, paying',
            ],
            [
                'id' => 'doctor',
                'icon' => 'medkit',
                'tint' => '#EF4444',
                'gradient' => ['#FECACA', '#EF4444'],
                'title' => ['uz' => 'Shifokor', 'ru' => 'У врача', 'en' => 'At the doctor'],
                'subtitle' => ['uz' => 'Shikoyatlaringizni ayting', 'ru' => 'Расскажите о симптомах', 'en' => 'Describe your symptoms'],
                'brief' => 'You are a calm family doctor. The learner is a patient describing how they feel.',
                'next_topics' => 'symptoms, how long, medicine, advice',
            ],
        ];
    }
}
