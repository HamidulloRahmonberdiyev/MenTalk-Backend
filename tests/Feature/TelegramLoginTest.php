<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Enums\TelegramLoginStatus;
use App\Modules\Account\Models\TelegramLogin;
use App\Modules\Account\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class TelegramLoginTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'webhook-secret-for-tests';

    private const ALI = ['id' => 555001, 'is_bot' => false, 'first_name' => 'Ali', 'last_name' => 'Valiyev', 'username' => 'alivaliyev', 'language_code' => 'ru'];

    private const VALI = ['id' => 555002, 'is_bot' => false, 'first_name' => 'Vali', 'language_code' => 'uz'];

    private const ALI_PHONE = '998901234567';

    private const REMOVE_KEYBOARD = ['remove_keyboard' => true];

    protected function setUp(): void
    {
        parent::setUp();

        config(['telegram.bot_token' => '123:test-token', 'telegram.webhook_secret' => self::SECRET, 'telegram.bot_username' => 'mentalk_auth_bot']);
        Storage::fake(User::AVATAR_DISK);

        Http::fake([
            'api.telegram.org/file/*' => Http::response('jpeg-bytes'),
            'api.telegram.org/*' => fn (Request $request) => Http::response(['ok' => true, 'result' => match (Str::afterLast($request->url(), '/')) {
                'sendMessage' => ['message_id' => 77],
                'getUserProfilePhotos' => ['total_count' => 1, 'photos' => [[
                    ['file_id' => 'small', 'file_unique_id' => 'AQADs', 'width' => 160, 'height' => 160],
                    ['file_id' => 'big', 'file_unique_id' => 'AQADb', 'width' => 640, 'height' => 640],
                ]]],
                'getFile' => ['file_id' => 'big', 'file_path' => 'photos/file_1.jpg'],
                default => true,
            }]),
        ]);
    }

    /** @return array{id: string, secret: string, code: string} */
    private function start(array $body = ['device' => 'Pixel 8 · Android 15', 'ui_language' => 'ru']): array
    {
        $data = $this->postJson('/api/v1/auth/telegram', $body)->assertCreated()->json('data');

        return ['id' => $data['id'], 'secret' => $data['secret'], 'code' => Str::after($data['bot_url'], 'start=')];
    }

    /** Where Telegram reaches this server; links the bot hands out are built from it. */
    private string $root = '';

    private function webhook(array $update): TestResponse
    {
        return $this->postJson("{$this->root}/api/v1/telegram/webhook", ['update_id' => random_int(1, PHP_INT_MAX), ...$update], ['X-Telegram-Bot-Api-Secret-Token' => self::SECRET]);
    }

    /** A private message from $from to the bot. */
    private function message(array $from, array $content): void
    {
        $this->webhook(['message' => [
            'message_id' => random_int(1, 1000),
            'date' => time(),
            'chat' => ['id' => $from['id'], 'type' => 'private'],
            'from' => $from,
            ...$content,
        ]])->assertNoContent();
    }

    private function open(string $code, array $from = self::ALI): void
    {
        $this->message($from, ['text' => "/start {$code}"]);
    }

    private function say(string $text, array $from = self::ALI): void
    {
        $this->message($from, ['text' => $text]);
    }

    /** What the "share phone number" button sends: the sender's own number, with their user id. */
    private function sharePhone(array $from = self::ALI, string $phone = self::ALI_PHONE, ?int $owner = null): void
    {
        $this->message($from, ['contact' => array_filter([
            'phone_number' => $phone,
            'first_name' => $from['first_name'],
            'user_id' => $owner ?? $from['id'],
        ])]);
    }

    private function check(array $login): TestResponse
    {
        return $this->postJson("/api/v1/auth/telegram/{$login['id']}", ['secret' => $login['secret']]);
    }

    /** @return list<array<string, mixed>> the bodies of the bot's calls to one Bot API method */
    private function sent(string $method): array
    {
        return Http::recorded(fn (Request $request) => str_ends_with($request->url(), "/{$method}"))
            ->map(fn (array $pair) => $pair[0]->data())->values()->all();
    }

    /** @return array<string, mixed> the bot's latest message */
    private function lastMessage(): array
    {
        return Arr::last($this->sent('sendMessage'));
    }

    private function assertAsksForThePhone(array $message): void
    {
        $this->assertSame(
            [[['text' => '📱 Отправить номер', 'request_contact' => true]], [['text' => '✖️ Отменить']]],
            $message['reply_markup']['keyboard'],
        );
    }

    public function test_start_returns_deep_links_and_keeps_only_hashes(): void
    {
        $data = $this->postJson('/api/v1/auth/telegram', ['device' => 'iPhone 15'])->assertCreated()
            ->assertJsonPath('data.bot_username', 'mentalk_auth_bot')
            ->assertJsonPath('data.poll_interval_ms', 2000)
            ->json('data');

        $code = Str::after($data['bot_url'], 'https://t.me/mentalk_auth_bot?start=');
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}$/', $code);
        $this->assertSame("tg://resolve?domain=mentalk_auth_bot&start={$code}", $data['app_url']);
        $this->assertSame(64, strlen($data['secret']));

        $row = TelegramLogin::query()->sole();
        $this->assertSame(TelegramLogin::hash($code), $row->code_hash);
        $this->assertSame(TelegramLogin::hash($data['secret']), $row->secret_hash);

        $this->check($data)->assertOk()->assertExactJson(['data' => ['status' => 'pending']]);
    }

    public function test_start_needs_a_configured_bot(): void
    {
        config(['telegram.bot_token' => null]);

        $this->postJson('/api/v1/auth/telegram', ['device' => 'iPhone'])->assertStatus(503)->assertJsonPath('type', 'telegram_unavailable');
    }

    public function test_sharing_the_phone_number_signs_the_app_in_once(): void
    {
        $login = $this->start();

        $this->open($login['code']);

        $prompt = $this->lastMessage();
        $this->assertSame(self::ALI['id'], $prompt['chat_id']);
        $this->assertStringContainsString('Pixel 8 · Android 15', $prompt['text']);
        $this->assertStringContainsString('Вход в MenTalk', $prompt['text'], 'The bot speaks the language the app asked for.');
        $this->assertStringContainsString('«📱 Отправить номер»', $prompt['text']);
        $this->assertAsksForThePhone($prompt);
        $this->check($login)->assertExactJson(['data' => ['status' => 'opened']]);

        $this->sharePhone();

        $user = User::query()->where('telegram_id', self::ALI['id'])->sole();
        $this->assertSame('Ali Valiyev', $user->name);
        $this->assertSame('+998901234567', $user->phone);
        $this->assertNull($user->email);
        $this->assertSame("{$user->id}/telegram-AQADb.jpg", $user->telegram_avatar_path, 'The largest photo size is copied.');
        Storage::disk(User::AVATAR_DISK)->assertExists($user->telegram_avatar_path);
        $this->assertSame('ru', $user->profile->ui_language->value);
        $this->assertStringContainsString('Вход подтверждён', $this->lastMessage()['text']);
        $this->assertSame(self::REMOVE_KEYBOARD, $this->lastMessage()['reply_markup'], 'The phone button goes away once used.');

        $token = $this->check($login)->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.user.name', 'Ali Valiyev')
            ->assertJsonPath('data.user.email', null)
            ->assertJsonPath('data.user.phone', '+998901234567')
            ->assertJsonPath('data.user.telegram_username', 'alivaliyev')
            ->assertJsonPath('data.user.avatar_url', Storage::disk(User::AVATAR_DISK)->url($user->telegram_avatar_path))
            ->json('data.token');

        $this->check($login)->assertExactJson(['data' => ['status' => 'expired']]);
        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.phone', '+998901234567');

        // Opening the used link again only points back to the app.
        $this->open($login['code']);
        $this->assertStringContainsString('Вход подтверждён', $this->lastMessage()['text']);
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_the_confirmation_offers_a_way_back_to_the_app(): void
    {
        $this->root = 'https://api.mentalk.test';
        $login = $this->start();
        $this->open($login['code']);

        $this->sharePhone();

        // One message per keyboard: the first removes the phone button, the second carries the link.
        [, $received, $approved] = $this->sent('sendMessage');
        $this->assertSame(self::REMOVE_KEYBOARD, $received['reply_markup']);
        $this->assertStringContainsString('Вход подтверждён', $approved['text']);
        $this->assertSame('https://api.mentalk.test/telegram/return?lang=ru', $approved['reply_markup']['inline_keyboard'][0][0]['url']);

        $this->get('/telegram/return?lang=uz', ['User-Agent' => 'Mozilla/5.0 (Linux; Android 15)'])->assertOk()
            ->assertSee('intent://telegram-return#Intent;scheme=govori;package=com.hamidullo_rahmonberdiyev.govori;end', false)
            ->assertSee('Kirish tasdiqlandi');
        $this->get('/telegram/return', ['User-Agent' => 'Mozilla/5.0 (iPhone)'])->assertOk()->assertSee('govori://telegram-return', false);
    }

    public function test_a_returning_user_keeps_their_name_and_gets_their_current_number(): void
    {
        $user = User::factory()->create(['google_id' => null, 'email' => null, 'telegram_id' => self::ALI['id'], 'name' => 'Alijon']);
        $user->forceFill(['phone' => '+998900000000'])->save();

        $login = $this->start();
        $this->open($login['code']);
        $this->sharePhone(phone: '+998 90 123 45 67');

        $this->check($login)->assertJsonPath('data.status', 'approved')->assertJsonPath('data.user.id', $user->id)->assertJsonPath('data.user.name', 'Alijon');
        $this->assertSame('+998901234567', $user->refresh()->phone, 'The number follows the Telegram account.');
        $this->assertSame(1, User::query()->count());
    }

    public function test_someone_elses_contact_card_is_refused(): void
    {
        $login = $this->start();
        $this->open($login['code']);

        $this->sharePhone(phone: '998935554433', owner: self::VALI['id']);

        $this->assertStringContainsString('Отправьте свой номер', $this->lastMessage()['text']);
        $this->assertAsksForThePhone($this->lastMessage());

        // A card for someone who is not on Telegram carries no user id at all.
        $this->message(self::ALI, ['contact' => ['phone_number' => '998935554433', 'first_name' => 'Mom']]);

        $this->assertStringContainsString('Отправьте свой номер', $this->lastMessage()['text']);
        $this->check($login)->assertExactJson(['data' => ['status' => 'opened']]);
        $this->assertSame(0, User::query()->count());
    }

    public function test_a_number_typed_by_hand_is_not_enough(): void
    {
        $login = $this->start();
        $this->open($login['code']);

        $this->say('+998901234567');

        $this->assertStringContainsString('Вводить номер вручную не нужно', $this->lastMessage()['text']);
        $this->assertAsksForThePhone($this->lastMessage());
        $this->check($login)->assertExactJson(['data' => ['status' => 'opened']]);
        $this->assertSame(0, User::query()->count());
    }

    public function test_cancelling_tells_the_app(): void
    {
        $login = $this->start();
        $this->open($login['code']);

        $this->say('✖️ Отменить');

        $this->assertStringContainsString('Вход отменён', $this->lastMessage()['text']);
        $this->assertSame(self::REMOVE_KEYBOARD, $this->lastMessage()['reply_markup']);
        $this->check($login)->assertExactJson(['data' => ['status' => 'declined']]);

        // The cancelled request cannot be answered after all.
        $this->sharePhone();

        $this->check($login)->assertExactJson(['data' => ['status' => 'declined']]);
        $this->assertSame(0, User::query()->count());
    }

    public function test_a_link_opened_by_one_telegram_user_cannot_be_confirmed_by_another(): void
    {
        $login = $this->start();
        $this->open($login['code'], self::ALI);

        $this->open($login['code'], self::VALI);
        $this->assertStringContainsString('уже открыт в другом аккаунте Telegram', $this->lastMessage()['text']);

        $this->sharePhone(self::VALI, '998935554433');

        $this->assertStringContainsString('So‘rov muddati tugagan', $this->lastMessage()['text'], 'Vali has nothing to answer.');
        $this->check($login)->assertExactJson(['data' => ['status' => 'opened']]);
        $this->assertSame(0, User::query()->count());
    }

    public function test_an_expired_link_is_refused_by_the_bot_and_the_app(): void
    {
        $login = $this->start();
        $this->open($login['code']);
        $this->travel(301)->seconds();

        $this->sharePhone();

        $this->assertStringContainsString('Срок запроса истёк', $this->lastMessage()['text']);
        $this->assertSame(self::REMOVE_KEYBOARD, $this->lastMessage()['reply_markup'], 'A stale phone button is cleared.');

        $this->open($login['code']);

        $this->assertStringContainsString('Срок запроса истёк', $this->lastMessage()['text']);
        $this->check($login)->assertExactJson(['data' => ['status' => 'expired']]);
        $this->assertSame(0, User::query()->count());
    }

    public function test_a_blocked_account_is_not_signed_in(): void
    {
        $user = User::factory()->create(['telegram_id' => self::ALI['id']]);
        $user->forceFill(['blocked_at' => now()])->save();

        $login = $this->start();
        $this->open($login['code']);
        $this->sharePhone();

        $this->assertStringContainsString('заблокирован', $this->lastMessage()['text']);
        $this->assertSame(self::REMOVE_KEYBOARD, $this->lastMessage()['reply_markup']);
        $this->check($login)->assertExactJson(['data' => ['status' => 'declined']]);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertNull($user->refresh()->phone);
    }

    public function test_without_an_open_request_the_bot_points_to_the_app(): void
    {
        $this->say('salom', self::VALI);

        $this->assertStringContainsString('Telegram orqali kirish', $this->lastMessage()['text']);
        $this->assertSame(self::REMOVE_KEYBOARD, $this->lastMessage()['reply_markup']);
    }

    public function test_checking_needs_the_secret(): void
    {
        $login = $this->start();

        $this->check(['id' => $login['id'], 'secret' => str_repeat('x', 64)])->assertNotFound();
        $this->check(['id' => 'not-an-id', 'secret' => $login['secret']])->assertNotFound();
    }

    public function test_the_webhook_only_accepts_telegram(): void
    {
        $this->postJson('/api/v1/telegram/webhook', ['update_id' => 1], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])->assertForbidden();
        $this->postJson('/api/v1/telegram/webhook', ['update_id' => 1])->assertForbidden();
    }

    public function test_plain_start_greets_in_the_telegram_language(): void
    {
        $this->say('/start', self::VALI);

        $this->assertStringContainsString('Assalomu alaykum, Vali!', $this->lastMessage()['text']);
    }

    public function test_old_attempts_are_pruned(): void
    {
        $this->start();
        $this->travel(25)->hours();
        $this->start();

        $this->artisan('model:prune', ['--model' => [TelegramLogin::class]])->assertSuccessful();

        $this->assertSame(1, TelegramLogin::query()->count());
        $this->assertSame(TelegramLoginStatus::Pending, TelegramLogin::query()->sole()->status);
    }
}
