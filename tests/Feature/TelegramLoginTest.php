<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Enums\TelegramLoginStatus;
use App\Modules\Account\Models\TelegramLogin;
use App\Modules\Account\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
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

    private function open(string $code, array $from = self::ALI): void
    {
        $this->webhook(['message' => [
            'message_id' => 1,
            'date' => time(),
            'chat' => ['id' => $from['id'], 'type' => 'private'],
            'from' => $from,
            'text' => "/start {$code}",
        ]])->assertNoContent();
    }

    private function press(string $action, string $loginId, array $from = self::ALI): void
    {
        $this->webhook(['callback_query' => [
            'id' => 'cq-'.Str::random(6),
            'from' => $from,
            'message' => ['message_id' => 77, 'date' => time(), 'chat' => ['id' => $from['id'], 'type' => 'private']],
            'data' => "login:{$action}:{$loginId}",
        ]])->assertNoContent();
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

    public function test_confirming_in_telegram_signs_the_app_in_once(): void
    {
        $login = $this->start();

        $this->open($login['code']);

        $prompt = $this->sent('sendMessage')[0];
        $this->assertSame(self::ALI['id'], $prompt['chat_id']);
        $this->assertStringContainsString('Pixel 8 · Android 15', $prompt['text']);
        $this->assertStringContainsString('Вход в MenTalk', $prompt['text'], 'The bot speaks the language the app asked for.');
        $this->assertSame("login:approve:{$login['id']}", $prompt['reply_markup']['inline_keyboard'][0][0]['callback_data']);
        $this->check($login)->assertExactJson(['data' => ['status' => 'opened']]);

        $this->press('approve', $login['id']);

        $user = User::query()->where('telegram_id', self::ALI['id'])->sole();
        $this->assertSame('Ali Valiyev', $user->name);
        $this->assertNull($user->email);
        $this->assertSame("{$user->id}/telegram-AQADb.jpg", $user->telegram_avatar_path, 'The largest photo size is copied.');
        Storage::disk(User::AVATAR_DISK)->assertExists($user->telegram_avatar_path);
        $this->assertSame('ru', $user->profile->ui_language->value);
        $this->assertStringContainsString('Вход подтверждён', $this->sent('editMessageText')[0]['text']);

        $token = $this->check($login)->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.user.name', 'Ali Valiyev')
            ->assertJsonPath('data.user.email', null)
            ->assertJsonPath('data.user.telegram_username', 'alivaliyev')
            ->assertJsonPath('data.user.avatar_url', Storage::disk(User::AVATAR_DISK)->url($user->telegram_avatar_path))
            ->json('data.token');

        $this->check($login)->assertExactJson(['data' => ['status' => 'expired']]);
        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.name', 'Ali Valiyev');
    }

    public function test_the_confirmation_offers_a_way_back_to_the_app(): void
    {
        $this->root = 'https://api.mentalk.test';
        $login = $this->start();
        $this->open($login['code']);

        $this->press('approve', $login['id']);

        $button = $this->sent('editMessageText')[0]['reply_markup']['inline_keyboard'][0][0];
        $this->assertSame('https://api.mentalk.test/telegram/return?lang=ru', $button['url']);

        $this->get('/telegram/return?lang=uz', ['User-Agent' => 'Mozilla/5.0 (Linux; Android 15)'])->assertOk()
            ->assertSee('intent://telegram-return#Intent;scheme=govori;package=com.hamidullo_rahmonberdiyev.govori;end', false)
            ->assertSee('Kirish tasdiqlandi');
        $this->get('/telegram/return', ['User-Agent' => 'Mozilla/5.0 (iPhone)'])->assertOk()->assertSee('govori://telegram-return', false);
    }

    public function test_a_returning_user_keeps_the_name_they_chose(): void
    {
        $user = User::factory()->create(['google_id' => null, 'email' => null, 'telegram_id' => self::ALI['id'], 'name' => 'Alijon']);

        $login = $this->start();
        $this->open($login['code']);
        $this->press('approve', $login['id']);

        $this->check($login)->assertJsonPath('data.status', 'approved')->assertJsonPath('data.user.id', $user->id)->assertJsonPath('data.user.name', 'Alijon');
        $this->assertSame(1, User::query()->count());
    }

    public function test_declining_tells_the_app(): void
    {
        $login = $this->start();
        $this->open($login['code']);

        $this->press('decline', $login['id']);

        $this->check($login)->assertExactJson(['data' => ['status' => 'declined']]);
        $this->assertSame(0, User::query()->count());
    }

    public function test_a_link_opened_by_one_telegram_user_cannot_be_confirmed_by_another(): void
    {
        $login = $this->start();
        $this->open($login['code'], self::ALI);

        $this->open($login['code'], self::VALI);
        $this->press('approve', $login['id'], self::VALI);

        $this->assertStringContainsString('уже открыт в другом аккаунте Telegram', $this->sent('sendMessage')[1]['text']);
        $this->check($login)->assertExactJson(['data' => ['status' => 'opened']]);
        $this->assertSame(0, User::query()->count());
    }

    public function test_an_expired_link_is_refused_by_the_bot_and_the_app(): void
    {
        $login = $this->start();
        $this->travel(301)->seconds();

        $this->open($login['code']);
        $this->press('approve', $login['id']);

        $this->assertStringContainsString('Срок запроса истёк', $this->sent('sendMessage')[0]['text']);
        $this->check($login)->assertExactJson(['data' => ['status' => 'expired']]);
        $this->assertSame(0, User::query()->count());
    }

    public function test_a_blocked_account_is_not_signed_in(): void
    {
        $user = User::factory()->create(['telegram_id' => self::ALI['id']]);
        $user->forceFill(['blocked_at' => now()])->save();

        $login = $this->start();
        $this->open($login['code']);
        $this->press('approve', $login['id']);

        $this->assertStringContainsString('заблокирован', $this->sent('editMessageText')[0]['text']);
        $this->check($login)->assertExactJson(['data' => ['status' => 'declined']]);
        $this->assertSame(0, $user->tokens()->count());
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
        $this->webhook(['message' => [
            'message_id' => 1,
            'date' => time(),
            'chat' => ['id' => self::VALI['id'], 'type' => 'private'],
            'from' => self::VALI,
            'text' => '/start',
        ]])->assertNoContent();

        $this->assertStringContainsString('Assalomu alaykum, Vali!', $this->sent('sendMessage')[0]['text']);
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
