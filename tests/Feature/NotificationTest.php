<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Contracts\IdentityVerifier;
use App\Modules\Account\DTO\Identity;
use App\Modules\Account\Models\LearnerProfile;
use App\Modules\Account\Models\User;
use App\Modules\Notification\Enums\NotificationType;
use App\Modules\Notification\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Modules\Notification\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Modules\Notification\Jobs\SendAnnouncement;
use App\Modules\Notification\Models\Announcement;
use App\Modules\Notification\Models\DatabaseNotification;
use App\Modules\Notification\Notifications\StreakAtRisk;
use App\Modules\Notification\Notifications\StreakMilestone;
use App\Modules\Notification\Notifications\WordsDue;
use App\Modules\Progress\Services\ProgressTracker;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** The in-app inbox: its API, the events that fill it and announcements from the admin panel. */
final class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $profile = []): User
    {
        $user = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $user->id, ...$profile]);

        return $user;
    }

    public function test_lists_newest_first_in_the_learners_language_with_the_unread_count(): void
    {
        $user = $this->user(['ui_language' => 'ru']);
        $this->travel(-2)->hours();
        $user->notify(new WordsDue(12));
        $this->travelBack();
        $user->notify(new StreakAtRisk(5));

        $this->actingAs($user)->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'streak_at_risk')
            ->assertJsonPath('data.0.kind', 'streak')
            ->assertJsonPath('data.0.title', 'Сохраните серию в 5 дн.!')
            ->assertJsonPath('data.0.action', ['target' => 'scenarios', 'label' => 'Начать разговор'])
            ->assertJsonPath('data.0.read', false)
            ->assertJsonPath('data.1.body', 'Пора повторить слова: 12 шт. Это займёт всего пару минут.')
            ->assertJsonPath('data.1.action.target', 'practice')
            ->assertJsonPath('meta.unread_count', 2);

        // The app may switch language before the profile is saved.
        $this->actingAs($user)->getJson('/api/v1/notifications?lang=en')
            ->assertJsonPath('data.1.title', 'Words are waiting');
    }

    public function test_paginates_with_a_cursor(): void
    {
        $user = $this->user();
        foreach (range(1, 3) as $n) {
            $this->travel($n)->minutes();
            $user->notify(new WordsDue($n * 10));
        }

        $first = $this->actingAs($user)->getJson('/api/v1/notifications?limit=2')->assertJsonCount(2, 'data');
        $cursor = $first->json('meta.next_cursor');
        $this->assertNotNull($cursor);

        $this->actingAs($user)->getJson("/api/v1/notifications?limit=2&cursor={$cursor}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', '10 ta so‘zni takrorlash vaqti keldi. Bor-yo‘g‘i bir necha daqiqa.');
    }

    public function test_marks_read_by_id_and_all_up_to_a_moment(): void
    {
        $user = $this->user();
        $user->notify(new WordsDue(5));
        $user->notify(new StreakAtRisk(3));
        [$first, $second] = $user->notifications()->get()->all();

        $this->actingAs($user)->postJson('/api/v1/notifications/read', ['ids' => [$first->id]])
            ->assertOk()->assertJsonPath('data.unread_count', 1);
        // Resending, or naming an id that is not there, is harmless.
        $this->actingAs($user)->postJson('/api/v1/notifications/read', ['ids' => [$first->id, (string) Str::uuid()]])
            ->assertOk()->assertJsonPath('data.unread_count', 1);

        // One that arrives after the learner looked is not swept up by "mark all read".
        $before = now()->toIso8601String();
        $this->travel(1)->minutes();
        $user->notify(new WordsDue(9));

        $this->actingAs($user)->postJson('/api/v1/notifications/read', ['all' => true, 'before' => $before])
            ->assertOk()->assertJsonPath('data.unread_count', 1);
        $this->assertNotNull($second->fresh()->read_at);
        $this->actingAs($user)->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.unread_count', 1);
    }

    public function test_deletes_selected_or_all(): void
    {
        $user = $this->user();
        $user->notify(new WordsDue(5));
        $user->notify(new StreakAtRisk(3));
        $id = $user->notifications()->value('id');

        $this->actingAs($user)->postJson('/api/v1/notifications/delete', ['ids' => [$id]])->assertOk();
        $this->assertSame(1, $user->notifications()->count());

        $this->actingAs($user)->postJson('/api/v1/notifications/delete', ['all' => true])
            ->assertOk()->assertJsonPath('data.unread_count', 0);
        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_a_learner_cannot_touch_someone_elses_notifications(): void
    {
        $owner = $this->user();
        $owner->notify(new WordsDue(5));
        $id = $owner->notifications()->value('id');
        $intruder = $this->user();

        $this->actingAs($intruder)->getJson('/api/v1/notifications')->assertJsonCount(0, 'data');
        $this->actingAs($intruder)->postJson('/api/v1/notifications/read', ['ids' => [$id]])->assertOk();
        $this->actingAs($intruder)->postJson('/api/v1/notifications/delete', ['all' => true])->assertOk();

        $this->assertNull(DatabaseNotification::query()->find($id)->read_at);
    }

    public function test_selection_is_validated(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
        $user = $this->user();

        $this->actingAs($user)->postJson('/api/v1/notifications/read', [])->assertUnprocessable();
        $this->actingAs($user)->postJson('/api/v1/notifications/read', ['ids' => ['not-a-uuid']])->assertUnprocessable();
        $this->actingAs($user)->getJson('/api/v1/notifications?limit=500')->assertUnprocessable();
    }

    public function test_a_new_account_is_welcomed_once(): void
    {
        $this->app->instance(IdentityVerifier::class, new class implements IdentityVerifier
        {
            public function verify(string $idToken): Identity
            {
                return new Identity('g-7', 'w@b.test', 'Vali');
            }
        });

        $this->postJson('/api/v1/auth/google', ['id_token' => 'x', 'device' => 'Pixel'])->assertOk();
        $this->postJson('/api/v1/auth/google', ['id_token' => 'x', 'device' => 'Pixel'])->assertOk();

        $user = User::query()->where('google_id', 'g-7')->sole();
        $this->assertSame([NotificationType::Welcome->value], $user->notifications()->pluck('type')->all());
    }

    public function test_reaching_a_streak_milestone_is_congratulated_once(): void
    {
        Notification::fake();
        $user = $this->user(['streak' => 6, 'last_active_on' => CarbonImmutable::now('Asia/Tashkent')->subDay()->toDateString()]);
        $tracker = app(ProgressTracker::class);

        $tracker->record($user, minutes: 3, xp: 10);
        $tracker->record($user, minutes: 3, xp: 10);

        Notification::assertSentToTimes($user, StreakMilestone::class, 1);
        Notification::assertSentTo($user, StreakMilestone::class, fn (StreakMilestone $n) => $n->days === 7);
        $this->assertSame(7, $user->profile()->value('streak'));
    }

    public function test_daily_reminders_pick_one_nudge_per_learner_and_skip_the_rest(): void
    {
        Notification::fake();
        $today = CarbonImmutable::now('Asia/Tashkent');
        $hour = $today->hour;

        $atRisk = $this->user(['streak' => 4, 'last_active_on' => $today->subDay()->toDateString()]);
        $withWords = $this->user(['streak' => 0, 'last_active_on' => $today->subDays(3)->toDateString()]);
        $practisedToday = $this->user(['streak' => 2, 'last_active_on' => $today->toDateString()]);
        $gone = $this->user(['streak' => 0, 'last_active_on' => $today->subDays(40)->toDateString()]);
        $otherHour = $this->user(['timezone' => 'America/New_York', 'streak' => 4, 'last_active_on' => $today->subDay()->toDateString()]);
        $blocked = $this->user(['streak' => 4, 'last_active_on' => $today->subDay()->toDateString()]);
        $blocked->forceFill(['blocked_at' => now()])->save();

        foreach ([$withWords, $gone] as $user) {
            $this->dueWords($user, 6);
        }

        $this->artisan('notifications:remind', ['--hour' => $hour])->assertSuccessful();

        Notification::assertSentTo($atRisk, StreakAtRisk::class, fn (StreakAtRisk $n) => $n->streak === 4);
        Notification::assertNotSentTo($atRisk, WordsDue::class);
        Notification::assertSentTo($withWords, WordsDue::class, fn (WordsDue $n) => $n->count === 6);
        Notification::assertNothingSentTo($practisedToday);
        Notification::assertNothingSentTo($gone);
        Notification::assertNothingSentTo($blocked);
        if (CarbonImmutable::now('America/New_York')->hour !== $hour) {
            Notification::assertNothingSentTo($otherHour);
        }
    }

    public function test_a_reminder_is_not_repeated_within_the_day(): void
    {
        $today = CarbonImmutable::now('Asia/Tashkent');
        $user = $this->user(['streak' => 4, 'last_active_on' => $today->subDay()->toDateString()]);

        $this->artisan('notifications:remind', ['--hour' => $today->hour])->assertSuccessful();
        $this->artisan('notifications:remind', ['--hour' => $today->hour])->assertSuccessful();

        $this->assertSame(1, $user->notifications()->where('type', NotificationType::StreakAtRisk->value)->count());
    }

    public function test_an_announcement_reaches_its_audience_once_even_when_retried(): void
    {
        $uz = $this->user(['ui_language' => 'uz']);
        $ru = $this->user(['ui_language' => 'ru']);
        $blocked = $this->user(['ui_language' => 'uz']);
        $blocked->forceFill(['blocked_at' => now()])->save();

        $announcement = Announcement::query()->create([
            'kind' => 'news',
            'title' => ['uz' => 'Yangi ovozlar', 'ru' => 'Новые голоса'],
            'body' => ['uz' => 'Tutor endi tonga mos gapiradi.'],
            'action' => 'scenarios',
            'audience' => null,
        ]);
        $announcement->forceFill(['dispatched_at' => now()])->save();

        (new SendAnnouncement($announcement->id))->handle();
        // A retry after a crash halfway must not double the inbox.
        $announcement->forceFill(['sent_at' => null])->save();
        (new SendAnnouncement($announcement->id))->handle();

        $this->assertSame(2, $announcement->fresh()->recipients);
        $this->assertSame(1, $uz->notifications()->count());
        $this->assertSame(0, $blocked->notifications()->count());

        // Russian title, Uzbek body where no Russian one was written.
        $this->actingAs($ru)->getJson('/api/v1/notifications')
            ->assertJsonPath('data.0.title', 'Новые голоса')
            ->assertJsonPath('data.0.body', 'Tutor endi tonga mos gapiradi.')
            ->assertJsonPath('data.0.kind', 'news');
    }

    public function test_an_announcement_can_target_one_interface_language(): void
    {
        $uz = $this->user(['ui_language' => 'uz']);
        $ru = $this->user(['ui_language' => 'ru']);
        $announcement = Announcement::query()->create(['kind' => 'lesson', 'title' => ['uz' => 'T'], 'body' => ['uz' => 'B'], 'audience' => 'ru']);

        (new SendAnnouncement($announcement->id))->handle();

        $this->assertSame(0, $uz->notifications()->count());
        $this->assertSame(1, $ru->notifications()->count());
    }

    public function test_old_notifications_are_pruned_and_a_purged_account_takes_its_inbox_along(): void
    {
        $user = $this->user();
        $this->travel(-100)->days();
        $user->notify(new WordsDue(5));
        $this->travelBack();
        $user->notify(new WordsDue(6));

        $this->artisan('model:prune', ['--model' => [DatabaseNotification::class]])->assertSuccessful();
        $this->assertSame(1, $user->notifications()->count());

        $user->forceDelete();
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_admin_writes_and_sends_an_announcement(): void
    {
        Filament::setCurrentPanel('admin');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'password' => 'a-long-admin-password'])->save();
        $this->user();

        Livewire::actingAs($admin)->test(CreateAnnouncement::class)
            ->fillForm(['kind' => 'news', 'title.uz' => 'Salom', 'body.uz' => 'Yangilik bor'])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::query()->sole();
        $this->assertSame($admin->id, $announcement->created_by);
        $this->assertNull($announcement->dispatched_at);

        Livewire::actingAs($admin)->test(ListAnnouncements::class)
            ->callTableAction('send', $announcement)
            ->assertHasNoTableActionErrors();

        $announcement->refresh();
        $this->assertNotNull($announcement->sent_at);
        // The learner and the admin, who is a user too.
        $this->assertSame(2, $announcement->recipients);
    }

    private function dueWords(User $user, int $count): void
    {
        foreach (range(1, $count) as $n) {
            $wordId = DB::table('words')->insertGetId(['language' => 'en', 'text' => "w{$user->id}{$n}", 'normalized' => "w{$user->id}{$n}"]);
            DB::table('user_words')->insert([
                'id' => (string) Str::ulid(), 'user_id' => $user->id, 'word_id' => $wordId, 'source' => 'manual',
                'due_at' => now()->subHour(), 'created_at' => now(), 'updated_at' => now(), 'synced_at' => now(),
            ]);
        }
    }
}
