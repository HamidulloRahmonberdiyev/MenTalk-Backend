<?php

declare(strict_types=1);

namespace App\Modules\Account\Console\Commands;

use App\Modules\Account\Actions\HandleTelegramUpdate;
use App\Modules\Account\Exceptions\TelegramUnavailable;
use App\Modules\Account\Services\TelegramBot;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

/**
 * Runs the sign-in bot on a machine Telegram cannot reach (local development): long-polls for
 * updates and hands them to the same handler the webhook uses. Production uses the webhook.
 */
#[AsCommand(name: 'telegram:poll', description: 'Run the sign-in bot by long polling (local development)')]
final class PollTelegram extends Command
{
    protected $signature = 'telegram:poll {--once : Handle one batch of updates and stop}';

    private const WAIT_SECONDS = 25;

    public function handle(TelegramBot $bot, HandleTelegramUpdate $handle): int
    {
        if (($bot->call('getWebhookInfo')['url'] ?? '') !== '') {
            $this->components->error('Bu botda webhook o‘rnatilgan, polling ishlamaydi. Avval: php artisan telegram:setup --local');

            return self::FAILURE;
        }

        $this->components->info('Bot ishlayapti. To‘xtatish: Ctrl+C');
        $offset = 0;

        do {
            try {
                $updates = $bot->call('getUpdates', [
                    'offset' => $offset,
                    'timeout' => self::WAIT_SECONDS,
                    'allowed_updates' => ['message', 'callback_query'],
                ], timeout: self::WAIT_SECONDS + 10);
            } catch (TelegramUnavailable $error) {
                $this->components->warn($error->getMessage());
                sleep(3);

                continue;
            }

            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;
                $this->line('  ← '.implode(', ', array_diff(array_keys($update), ['update_id'])));

                try {
                    $handle($update);
                } catch (Throwable $error) {
                    report($error);
                    $this->components->error($error->getMessage());
                }
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}
