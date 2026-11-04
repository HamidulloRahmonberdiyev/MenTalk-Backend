<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Auth\DeleteAccount;
use App\Enums\NotificationKind;
use App\Jobs\SendAnnouncement;
use App\Models\Announcement;
use App\Models\DatabaseNotification;
use App\Models\LearnerProfile;
use App\Models\PushToken;
use App\Models\User;
use App\Notifications\StreakAtRisk;
use App\Notifications\StreakMilestone;
use App\Notifications\WordsDue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/** Getting notifications onto phones: device registration, sending, and cleaning up dead tokens. */
final class PushTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'ExponentPushToken[abc]';

    private const SEND_URL = 'exp.host/--/api/v2/push/send';

    private const RECEIPTS_URL = 'exp.host/--/api/v2/push/getReceipts';

    private function user(array $profile = []): User
    {
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id, ...$profile]);

        return $user;
    }

    /** Registers through the API with a real session, as the app does. Returns the session token. */
    private function register(User $user, string $token = self::TOKEN): string
    {
        $session = $user->createToken('phone')->plainTextToken;

        $this->withToken($session)->postJson('/api/v1/me/push-token', ['token' => $token, 'platform' => 'android'])->assertNoContent();
        $this->app['auth']->forgetGuards();

        return $session;
    }

    /** Expo accepts everything, and every receipt comes back as `$receipt`. */
    private function fakeExpo(array $receipt = ['status' => 'ok']): void
    {
        Http::fake([
            self::SEND_URL => fn (Request $request) => Http::response([
                'data' => array_map(fn () => ['status' => 'ok', 'id' => fake()->uuid()], $request->data()),
            ]),
            self::RECEIPTS_URL => fn (Request $request) => Http::response([
                'data' => array_fill_keys($request->data()['ids'], $receipt),
            ]),
        ]);
    }

    private function sentMessages(): array
    {
        return Http::recorded(fn (Request $request) => str_contains($request->url(), self::SEND_URL))
            ->flatMap(fn (array $pair) => $pair[0]->data())
            ->all();
    }

    public function test_registering_twice_keeps_one_row_and_a_new_user_takes_the_device_over(): void
    {
        $first = $this->user();
        $second = $this->user();

        $this->register($first);
        $this->register($first);
        $this->assertSame(1, PushToken::query()->count());

        $this->register($second);
        $this->assertSame($second->id, PushToken::query()->sole()->user_id);
    }

    public function test_rejects_anything_that_is_not_an_expo_token(): void
    {
        $session = $this->user()->createToken('phone')->plainTextToken;

        $this->withToken($session)->postJson('/api/v1/me/push-token', ['token' => 'nonsense', 'platform' => 'android'])
            ->assertUnprocessable()->assertJsonValidationErrors('token');
    }

    public function test_requires_a_session(): void
    {
        $this->postJson('/api/v1/me/push-token', ['token' => self::TOKEN, 'platform' => 'android'])->assertUnauthorized();
    }

    public function test_a_notification_reaches_the_phone_in_the_learners_language(): void
    {
        $this->fakeExpo();
        $user = $this->user(['ui_language' => 'ru']);
        $this->register($user);

        $user->notify(new StreakMilestone(7));

        $inbox = DatabaseNotification::query()->sole();
        [$message] = $this->sentMessages();

        $this->assertSame(self::TOKEN, $message['to']);
        $this->assertSame((string) __('notifications.streak_milestone.title', ['n' => 7], 'ru'), $message['title']);
        $this->assertSame('inbox', $message['channelId']);
        $this->assertSame('high', $message['priority']);
        // The same id as the inbox row, so a tap can be matched to it.
        $this->assertSame(['source' => 'mentalk-push', 'id' => $inbox->id, 'kind' => 'achievement', 'action' => null], $message['data']);
    }

    public function test_daily_reminders_are_left_to_the_device(): void
    {
        $this->fakeExpo();
        $user = $this->user();
        $this->register($user);

        $user->notify(new StreakAtRisk(5));
        $user->notify(new WordsDue(12));

        $this->assertSame(2, DatabaseNotification::query()->count());
        Http::assertNothingSent();
    }

    public function test_signing_out_stops_the_pushes(): void
    {
        $this->fakeExpo();
        $user = $this->user();
        $session = $this->register($user);

        $this->withToken($session)->deleteJson('/api/v1/auth/session')->assertNoContent();

        $this->assertSame(0, PushToken::query()->count());
    }

    public function test_deleting_the_account_stops_the_pushes(): void
    {
        $user = $this->user();
        $this->register($user);

        app(DeleteAccount::class)($user);

        $this->assertSame(0, PushToken::query()->count());
    }

    public function test_an_expired_session_or_a_blocked_learner_gets_nothing(): void
    {
        $this->fakeExpo();
        $expired = $this->user();
        $this->register($expired);
        $expired->tokens()->update(['expires_at' => now()->subMinute()]);

        $blocked = $this->user();
        $this->register($blocked, 'ExponentPushToken[blocked]');
        $blocked->forceFill(['blocked_at' => now()])->save();

        $expired->notify(new StreakMilestone(7));
        $blocked->notify(new StreakMilestone(7));

        Http::assertNothingSent();
    }

    public function test_a_token_refused_in_the_ticket_is_forgotten(): void
    {
        Http::fake([self::SEND_URL => Http::response([
            'data' => [['status' => 'error', 'message' => 'gone', 'details' => ['error' => 'DeviceNotRegistered']]],
        ])]);
        $user = $this->user();
        $this->register($user);

        $user->notify(new StreakMilestone(7));

        $this->assertSame(0, PushToken::query()->count());
    }

    public function test_a_token_refused_in_the_receipt_is_forgotten(): void
    {
        $this->fakeExpo(['status' => 'error', 'message' => 'gone', 'details' => ['error' => 'DeviceNotRegistered']]);
        $user = $this->user();
        $this->register($user);

        $user->notify(new StreakMilestone(7));

        $this->assertSame(0, PushToken::query()->count());
    }

    public function test_bad_credentials_are_logged_and_the_token_is_kept(): void
    {
        Log::spy();
        $this->fakeExpo(['status' => 'error', 'message' => 'bad key', 'details' => ['error' => 'InvalidCredentials']]);
        $user = $this->user();
        $this->register($user);

        $user->notify(new StreakMilestone(7));

        $this->assertSame(1, PushToken::query()->count());
        Log::shouldHaveReceived('warning')->withArgs(fn (string $text, array $context) => $context['error'] === 'InvalidCredentials');
    }

    private function announcement(): Announcement
    {
        return Announcement::query()->forceCreate([
            'kind' => NotificationKind::News->value,
            'title' => ['uz' => 'Yangilik', 'ru' => 'Новость'],
            'body' => ['uz' => 'Matn', 'ru' => 'Текст'],
            'dispatched_at' => now(),
        ]);
    }

    public function test_an_announcement_is_pushed_in_each_learners_language(): void
    {
        $this->fakeExpo();
        $this->register($this->user(['ui_language' => 'uz']), 'ExponentPushToken[uz]');
        $this->register($this->user(['ui_language' => 'ru']), 'ExponentPushToken[ru]');
        $this->user(); // No device: inbox only.

        SendAnnouncement::dispatchSync($this->announcement()->id);

        $this->assertSame(3, DatabaseNotification::query()->count());
        $this->assertEqualsCanonicalizing(
            ['ExponentPushToken[uz]' => 'Yangilik', 'ExponentPushToken[ru]' => 'Новость'],
            collect($this->sentMessages())->pluck('title', 'to')->all(),
        );
    }

    public function test_a_retried_announcement_does_not_push_again_to_those_already_reached(): void
    {
        $this->fakeExpo();
        $user = $this->user();
        $this->register($user);
        $announcement = $this->announcement();

        SendAnnouncement::dispatchSync($announcement->id);
        // As if the job died after the inserts, before it could mark itself as sent.
        $announcement->forceFill(['sent_at' => null])->save();
        SendAnnouncement::dispatchSync($announcement->id);

        $this->assertCount(1, $this->sentMessages());
    }

    public function test_expo_being_down_does_not_cost_the_inbox_its_row(): void
    {
        Http::fake([self::SEND_URL => Http::response([], 503)]);
        $user = $this->user();
        $this->register($user);

        try {
            $user->notify(new StreakMilestone(7));
        } catch (\Throwable) {
            // A failed push job; the worker logs it. The inbox row is what matters here.
        }

        $this->assertSame(1, DatabaseNotification::query()->count());
    }
}
