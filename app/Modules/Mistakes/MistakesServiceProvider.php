<?php

declare(strict_types=1);

namespace App\Modules\Mistakes;

use App\Modules\Conversation\Events\LearnerMadeMistakes;
use App\Modules\Mistakes\Listeners\CollectMistakes;
use App\Shared\Providers\ModuleServiceProvider;

/**
 * The mistakes notebook: every correction the tutor made, kept per learner, merged when made
 * again, and brought back for practice on a spaced repetition schedule.
 */
final class MistakesServiceProvider extends ModuleServiceProvider
{
    protected array $listeners = [LearnerMadeMistakes::class => CollectMistakes::class];
}
