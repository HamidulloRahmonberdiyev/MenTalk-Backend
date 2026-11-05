<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Account\Jobs\PurgeUserData;
use App\Modules\Account\Models\User;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Modules\Speech\DTO\SpeechClip;
use App\Modules\Speech\Services\CachedSynthesizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Data written before the classes moved into modules has to keep working after a deploy. */
final class ModuleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_stored_under_the_old_user_class_still_belong_to_the_user(): void
    {
        $user = User::factory()->create();

        DB::table('notifications')->insert([
            'id' => '01HZZZZZZZZZZZZZZZZZZZZZZZ',
            'type' => 'welcome',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => '{}',
        ]);

        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame('App\Models\User', $user->getMorphClass());
    }

    public function test_a_purge_queued_before_the_move_can_still_be_unserialized(): void
    {
        $name = 'App\Jobs\PurgeUserData';
        $payload = 'O:'.strlen($name).':"'.$name.'":1:{s:6:"userId";s:3:"abc";}';

        $job = unserialize($payload);

        $this->assertInstanceOf(PurgeUserData::class, $job);
        $this->assertSame('abc', $job->userId);
    }

    public function test_a_cached_speech_clip_is_read_back_on_a_store_that_serializes(): void
    {
        config(['cache.stores.array.serialize' => true, 'cache.serializable_classes' => false]);
        Cache::forgetDriver('array');

        $inner = new class implements SpeechSynthesizer
        {
            public int $calls = 0;

            public function speak(string $text, string $voice): SpeechClip
            {
                $this->calls++;

                return new SpeechClip('https://cdn.test/clip.wav', 1200);
            }
        };

        $synthesizer = new CachedSynthesizer($inner);
        $synthesizer->speak('Salom', 'Kore');
        $clip = $synthesizer->speak('Salom', 'Kore');

        $this->assertSame(1, $inner->calls);
        $this->assertSame('https://cdn.test/clip.wav', $clip->url);
        $this->assertSame(1200, $clip->durationMs);
    }
}
