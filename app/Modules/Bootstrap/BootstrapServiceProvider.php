<?php

declare(strict_types=1);

namespace App\Modules\Bootstrap;

use App\Modules\Bootstrap\Observers\ForgetBootstrap;
use App\Modules\Conversation\Models\Scenario;
use App\Modules\Conversation\Models\ScenarioBrief;
use App\Modules\Language\Models\Language;
use App\Shared\Providers\ModuleServiceProvider;

final class BootstrapServiceProvider extends ModuleServiceProvider
{
    protected array $observers = [
        Language::class => ForgetBootstrap::class,
        Scenario::class => ForgetBootstrap::class,
        ScenarioBrief::class => ForgetBootstrap::class,
    ];
}
