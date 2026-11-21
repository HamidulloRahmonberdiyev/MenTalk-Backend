<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Bootstrap\Services\BootstrapPayload;
use App\Modules\Conversation\Models\Scenario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Fifteen more everyday topics, with a brief for Russian and for English, added after the topics
 * already on the path, roughly from the easiest to the hardest.
 *
 * Unlike `ScenarioSeeder` it only adds topics that are not there yet: a topic an admin has edited or
 * switched off is left exactly as it is, so it is safe to run on production more than once:
 * `php artisan db:seed --class=TopicPackSeeder --force`.
 */
final class TopicPackSeeder extends Seeder
{
    public function run(): void
    {
        $existing = Scenario::query()->whereKey(array_column(self::topics(), 'id'))->pluck('id')->all();
        $missing = array_values(array_filter(self::topics(), fn (array $topic) => ! in_array($topic['id'], $existing, true)));

        if ($missing === []) {
            $this->command?->info('All topics of the pack are already there; nothing changed.');

            return;
        }

        DB::transaction(function () use ($missing) {
            // After every topic already on the path, so nobody's progress moves.
            $sort = (int) Scenario::query()->max('sort');

            foreach ($missing as $topic) {
                $scenario = Scenario::query()->create(
                    Arr::except($topic, ['briefs', 'topics']) + ['sort' => ++$sort, 'active' => true],
                );

                foreach ($topic['briefs'] as $language => $brief) {
                    $scenario->briefs()->create(['language' => $language, 'brief' => $brief, 'next_topics' => $topic['topics']]);
                }
            }
        });

        // The observers already forgot it, but a request between that and the commit may have cached the old list.
        Cache::forget(BootstrapPayload::CACHE_KEY);

        $this->command?->info('Added '.count($missing).' topics: '.implode(', ', array_column($missing, 'id')));
    }

    /** Public, so tests can check every topic of the pack. */
    public static function topics(): array
    {
        return [
            [
                'id' => 'directions',
                'icon' => 'navigate',
                'tint' => '#6366F1',
                'gradient' => ['#9C9EF8', '#4346D1'],
                'title' => ['uz' => 'Yo\'l so\'rash', 'ru' => 'Как пройти?', 'en' => 'Asking the way'],
                'subtitle' => ['uz' => 'Manzil, yo\'nalish, transport', 'ru' => 'Адрес, направление, транспорт', 'en' => 'Addresses, directions, transport'],
                'briefs' => [
                    'ru' => 'A street in central Moscow, near Tverskaya Street. You are {tutor}, a friendly local passer-by. The learner is lost and asks you the way to a museum, a metro station or a café: explain left and right, straight on, how far it is and which bus or metro line to take.',
                    'en' => 'A busy street in central London, near Oxford Circus. You are {tutor}, a friendly local passer-by. The learner is lost and asks you the way to a museum, a Tube station or a café: explain left and right, straight on, how far it is and which bus or Tube line to take.',
                ],
                'topics' => 'their neighbourhood at home, favourite places in their city, getting around by public transport, walking versus driving, a time they got lost, sights worth seeing',
            ],
            [
                'id' => 'market',
                'icon' => 'basket',
                'tint' => '#16A34A',
                'gradient' => ['#5BD08A', '#15803D'],
                'title' => ['uz' => 'Bozorda', 'ru' => 'На рынке', 'en' => 'At the market'],
                'subtitle' => ['uz' => 'Meva-sabzavot, narx, savdolashish', 'ru' => 'Овощи, фрукты, торг', 'en' => 'Fresh produce, prices, haggling'],
                'briefs' => [
                    'ru' => 'The Danilovsky farmers\' market in Moscow. You are {tutor}, a cheerful stallholder selling fruit, vegetables, cheese and honey. Offer a taste, say the price per kilo, weigh the goods, haggle a little and give the change.',
                    'en' => 'Borough Market in London. You are {tutor}, a cheerful stallholder selling fruit, vegetables, cheese and honey. Offer a taste, say the price per kilo, weigh the goods, haggle a little and give the change.',
                ],
                'topics' => 'favourite fruit and vegetables, cooking at home, bazaars in their country, seasonal food, healthy eating, a family recipe',
            ],
            [
                'id' => 'hotel',
                'icon' => 'bed',
                'tint' => '#8B5CF6',
                'gradient' => ['#B39BFA', '#6D3FD6'],
                'title' => ['uz' => 'Mehmonxonada', 'ru' => 'В гостинице', 'en' => 'At the hotel'],
                'subtitle' => ['uz' => 'Joy band qilish, xona, xizmatlar', 'ru' => 'Бронь, номер, услуги', 'en' => 'Booking, rooms, services'],
                'briefs' => [
                    'ru' => 'The reception desk of a hotel in central Moscow. You are {tutor}, the receptionist. Check the learner in: the booking, the passport, the room type, breakfast hours and Wi-Fi, then help with a small problem in the room (the air conditioning or a noisy neighbour).',
                    'en' => 'The reception desk of a hotel in central London. You are {tutor}, the receptionist. Check the learner in: the booking, the passport, the room type, breakfast hours and Wi-Fi, then help with a small problem in the room (the heating or a noisy neighbour).',
                ],
                'topics' => 'the purpose of their trip, places they want to visit, hotels versus renting a flat, their dream holiday, travelling alone or with family, local food to try',
            ],
            [
                'id' => 'pharmacy',
                'icon' => 'bandage',
                'tint' => '#14B8A6',
                'gradient' => ['#5EDBC8', '#0F8A7E'],
                'title' => ['uz' => 'Dorixonada', 'ru' => 'В аптеке', 'en' => 'At the pharmacy'],
                'subtitle' => ['uz' => 'Dori so\'rash, qabul qilish tartibi', 'ru' => 'Лекарства, дозировка, советы', 'en' => 'Medicine, dosage, advice'],
                'briefs' => [
                    'ru' => 'A pharmacy in Moscow. You are {tutor}, the pharmacist. The learner has a cold, a headache or an allergy. Ask about the symptoms, suggest something that needs no prescription, explain how and when to take it, and say when it is better to see a doctor.',
                    'en' => 'A chemist\'s on a London high street. You are {tutor}, the pharmacist. The learner has a cold, a headache or an allergy. Ask about the symptoms, suggest something that needs no prescription, explain how and when to take it, and say when it is better to see a GP.',
                ],
                'topics' => 'staying healthy in winter, home remedies from their family, sleep, sport and diet, stress at work or school, how they look after themselves',
            ],
            [
                'id' => 'station',
                'icon' => 'train',
                'tint' => '#0EA5E9',
                'gradient' => ['#5CC8F5', '#0B76B0'],
                'title' => ['uz' => 'Vokzalda', 'ru' => 'На вокзале', 'en' => 'At the station'],
                'subtitle' => ['uz' => 'Chipta, jadval, platforma', 'ru' => 'Билеты, расписание, платформа', 'en' => 'Tickets, timetables, platforms'],
                'briefs' => [
                    'ru' => 'The ticket office at Leningradsky railway station in Moscow. You are {tutor}, the ticket clerk. Sell the learner a train ticket to Saint Petersburg: the date and time, one way or return, the class of seat, the price, the platform, and what to do if the train is delayed.',
                    'en' => 'The ticket office at King\'s Cross station in London. You are {tutor}, the ticket clerk. Sell the learner a train ticket to Edinburgh: the date and time, single or return, standard or first class, the price, the platform, and what to do if the train is delayed.',
                ],
                'topics' => 'trains versus planes, a memorable journey, their hometown, travelling with luggage, what they do on long trips, a city they want to visit next',
            ],
            [
                'id' => 'post',
                'icon' => 'mail',
                'tint' => '#F59E0B',
                'gradient' => ['#FCC85A', '#C77A06'],
                'title' => ['uz' => 'Pochtada', 'ru' => 'На почте', 'en' => 'At the post office'],
                'subtitle' => ['uz' => 'Posilka, xat, yetkazib berish', 'ru' => 'Посылки, письма, доставка', 'en' => 'Parcels, letters, delivery'],
                'briefs' => [
                    'ru' => 'A post office in Moscow. You are {tutor}, the counter clerk. The learner wants to send a parcel home to their family: what is inside, the weight, the delivery options and how long they take, the customs form, the price and the tracking number.',
                    'en' => 'A post office in London. You are {tutor}, the counter clerk. The learner wants to send a parcel home to their family: what is inside, the weight, the delivery options and how long they take, the customs form, the price and the tracking number.',
                ],
                'topics' => 'gifts they send or receive, family far away, online shopping, staying in touch with friends, a parcel that got lost, letters versus messages',
            ],
            [
                'id' => 'cinema',
                'icon' => 'film',
                'tint' => '#E11D48',
                'gradient' => ['#F7708E', '#B0123A'],
                'title' => ['uz' => 'Kinoteatrda', 'ru' => 'В кинотеатре', 'en' => 'At the cinema'],
                'subtitle' => ['uz' => 'Chipta, seans, film haqida', 'ru' => 'Билеты, сеансы, обсуждение фильма', 'en' => 'Tickets, showtimes, films'],
                'briefs' => [
                    'ru' => 'A cinema in Moscow. You are {tutor}, a friend the learner meets there. Together choose a film and a showtime, buy tickets and snacks, pick seats, and afterwards share what you both thought of the film.',
                    'en' => 'A cinema in Leicester Square, London. You are {tutor}, a friend the learner meets there. Together choose a film and a showtime, buy tickets and snacks, pick seats, and afterwards share what you both thought of the film.',
                ],
                'topics' => 'favourite films and genres, actors they like, series they are watching, the cinema versus streaming at home, a film that made them laugh or cry, books turned into films',
            ],
            [
                'id' => 'hairdresser',
                'icon' => 'cut',
                'tint' => '#EC4899',
                'gradient' => ['#F58CC4', '#C02679'],
                'title' => ['uz' => 'Sartaroshxonada', 'ru' => 'В парикмахерской', 'en' => 'At the hairdresser\'s'],
                'subtitle' => ['uz' => 'Soch turmagi, uslub, maslahat', 'ru' => 'Стрижка, стиль, советы', 'en' => 'Haircuts, styles, advice'],
                'briefs' => [
                    'ru' => 'A hair salon in Moscow. You are {tutor}, the hairdresser. Ask what the learner would like (a trim or a new style, how short, a fringe or not), advise what would suit them, chat while you work and show the result in the mirror.',
                    'en' => 'A hair salon in London. You are {tutor}, the hairdresser. Ask what the learner would like (a trim or a new style, how short, a fringe or not), advise what would suit them, chat while you work and show the result in the mirror.',
                ],
                'topics' => 'their style and favourite clothes, a special event coming up, fashion trends, how often they change their look, plans for the weekend, holidays',
            ],
            [
                'id' => 'gym',
                'icon' => 'barbell',
                'tint' => '#F97316',
                'gradient' => ['#FDA35C', '#C9520A'],
                'title' => ['uz' => 'Sport zalida', 'ru' => 'В спортзале', 'en' => 'At the gym'],
                'subtitle' => ['uz' => 'Abonement, mashg\'ulot, murabbiy', 'ru' => 'Абонемент, тренировки, тренер', 'en' => 'Membership, workouts, trainers'],
                'briefs' => [
                    'ru' => 'A gym in Moscow. You are {tutor}, a personal trainer. The learner wants to join: talk about membership options and opening hours, ask about their goals and experience, show them a few machines and plan a simple first workout.',
                    'en' => 'A gym in London. You are {tutor}, a personal trainer. The learner wants to join: talk about membership options and opening hours, ask about their goals and experience, show them a few machines and plan a simple first workout.',
                ],
                'topics' => 'favourite sports, staying motivated, healthy food, sport at school, a team they support, morning or evening workouts',
            ],
            [
                'id' => 'guests',
                'icon' => 'home',
                'tint' => '#65A30D',
                'gradient' => ['#A3D65C', '#4D7C0F'],
                'title' => ['uz' => 'Mehmondorchilikda', 'ru' => 'В гостях', 'en' => 'Visiting friends'],
                'subtitle' => ['uz' => 'Tabrik, dasturxon, suhbat', 'ru' => 'Поздравления, застолье, разговор', 'en' => 'Greetings, dinner, conversation'],
                'briefs' => [
                    'ru' => 'A birthday dinner at a friend\'s flat in Moscow. You are {tutor}, the host. Welcome the learner, thank them for the gift, introduce your family, offer food and tea, talk about the dishes and make a short toast together.',
                    'en' => 'A birthday dinner at a friend\'s flat in London. You are {tutor}, the host. Welcome the learner, thank them for the gift, introduce your family, offer food and tea, talk about the dishes and make a short toast together.',
                ],
                'topics' => 'birthdays and holidays in their country, traditional dishes, hospitality at home, gifts, family traditions, the best party they remember',
            ],
            [
                'id' => 'banking',
                'icon' => 'card',
                'tint' => '#2563EB',
                'gradient' => ['#6C9BF5', '#1D47B8'],
                'title' => ['uz' => 'Bankda', 'ru' => 'В банке', 'en' => 'At the bank'],
                'subtitle' => ['uz' => 'Hisob ochish, karta, valyuta', 'ru' => 'Счёт, карта, обмен валюты', 'en' => 'Accounts, cards, currency'],
                'briefs' => [
                    'ru' => 'A bank branch in Moscow. You are {tutor}, a bank consultant. Help the learner open an account and get a card: the documents needed, the type of account, the fees, the mobile app, exchanging money, and what to do if a card is lost.',
                    'en' => 'A bank branch in London. You are {tutor}, a bank adviser. Help the learner open an account and get a debit card: the documents needed, the type of account, the fees, the mobile app, exchanging money, and what to do if a card is lost.',
                ],
                'topics' => 'saving money, cash versus cards, online shopping, something big they are saving for, prices in their country, a first salary or pocket money',
            ],
            [
                'id' => 'rent',
                'icon' => 'key',
                'tint' => '#B45309',
                'gradient' => ['#E0A24E', '#83400A'],
                'title' => ['uz' => 'Uy ijarasi', 'ru' => 'Аренда квартиры', 'en' => 'Renting a flat'],
                'subtitle' => ['uz' => 'Kvartira ko\'rish, shartnoma, narx', 'ru' => 'Просмотр, договор, цена', 'en' => 'Viewings, contracts, rent'],
                'briefs' => [
                    'ru' => 'A flat viewing in a residential district of Moscow. You are {tutor}, the owner of the flat. Show the learner around, answer questions about the rooms, the furniture, the rent and the deposit, the bills, the neighbours and the nearest metro station, and agree on the next steps.',
                    'en' => 'A flat viewing in north London. You are {tutor}, the owner of the flat. Show the learner around, answer questions about the rooms, the furniture, the rent and the deposit, the bills, the neighbours and the nearest Tube station, and agree on the next steps.',
                ],
                'topics' => 'their home and favourite room, living alone or with family, their ideal neighbourhood, moving to a new city, housework, neighbours',
            ],
            [
                'id' => 'university',
                'icon' => 'school',
                'tint' => '#C026D3',
                'gradient' => ['#E07BEA', '#9317A3'],
                'title' => ['uz' => 'Universitetda', 'ru' => 'В университете', 'en' => 'At university'],
                'subtitle' => ['uz' => 'Qabul, darslar, yotoqxona', 'ru' => 'Поступление, занятия, общежитие', 'en' => 'Admissions, classes, campus life'],
                'briefs' => [
                    'ru' => 'The international students\' office of a university in Moscow. You are {tutor}, a student adviser. Help the learner enrol: choosing a course, the timetable, the student card, the library, the dormitory and clubs to join.',
                    'en' => 'The international students\' office of a university in London. You are {tutor}, a student adviser. Help the learner enrol: choosing a course, the timetable, the student ID card, the library, student accommodation and societies to join.',
                ],
                'topics' => 'what they study or want to study, favourite subjects, teachers they remember, exams and how they prepare, student life, plans after graduation',
            ],
            [
                'id' => 'lost',
                'icon' => 'search',
                'tint' => '#475569',
                'gradient' => ['#8394AB', '#334155'],
                'title' => ['uz' => 'Yo\'qolgan buyum', 'ru' => 'Бюро находок', 'en' => 'Lost property'],
                'subtitle' => ['uz' => 'Buyumni tasvirlash, ariza', 'ru' => 'Описание вещи, заявление', 'en' => 'Describing items, reporting'],
                'briefs' => [
                    'ru' => 'The lost property office of the Moscow metro. You are {tutor}, the officer on duty. The learner has lost a bag or a phone. Ask what it looks like, what was inside, when and where they last had it, take their contact details and reassure them.',
                    'en' => 'The lost property office of the London Underground. You are {tutor}, the officer on duty. The learner has lost a bag or a phone. Ask what it looks like, what was inside, when and where they last had it, take their contact details and reassure them.',
                ],
                'topics' => 'things they always carry, a time they lost something important, their phone and favourite apps, being organised, kind strangers, an unlucky day that ended well',
            ],
            [
                'id' => 'interview',
                'icon' => 'business',
                'tint' => '#047857',
                'gradient' => ['#3FB58C', '#035E44'],
                'title' => ['uz' => 'Ish suhbatida', 'ru' => 'Собеседование', 'en' => 'Job interview'],
                'subtitle' => ['uz' => 'O\'zingizni tanishtirish, tajriba, savollar', 'ru' => 'Самопрезентация, опыт, вопросы', 'en' => 'Introductions, experience, questions'],
                'briefs' => [
                    'ru' => 'A job interview at a company office in Moscow. You are {tutor}, the HR manager. Interview the learner for a job they want: ask them to introduce themselves, about their education and experience, their strengths and weaknesses and why they want this job, then answer their questions about the salary and the schedule.',
                    'en' => 'A job interview at a company office in London. You are {tutor}, the hiring manager. Interview the learner for a job they want: ask them to introduce themselves, about their education and experience, their strengths and weaknesses and why they want this job, then answer their questions about the salary and working hours.',
                ],
                'topics' => 'their dream job, skills they are proud of, teamwork, learning languages for work, work-life balance, where they see themselves in five years',
            ],
        ];
    }
}
