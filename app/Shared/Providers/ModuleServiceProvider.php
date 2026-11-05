<?php

declare(strict_types=1);

namespace App\Shared\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * What every module wires into the framework. A module declares its observers, policies, listeners
 * and commands here, and keeps its routes in `routes/api.php` and `routes/web.php` next to its provider.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    private const API_PREFIX = 'api/v1';

    /** @var array<class-string, class-string> Model => observer */
    protected array $observers = [];

    /** @var array<class-string, class-string> Model => policy */
    protected array $policies = [];

    /** @var array<class-string, class-string> Event => listener */
    protected array $listeners = [];

    /** @var list<class-string> */
    protected array $consoleCommands = [];

    public function boot(): void
    {
        foreach ($this->observers as $model => $observer) {
            $model::observe($observer);
        }

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        foreach ($this->listeners as $event => $listener) {
            Event::listen($event, $listener);
        }

        $this->commands($this->consoleCommands);
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $routes = dirname((string) (new ReflectionClass($this))->getFileName()).'/routes';

        if (is_file("{$routes}/api.php")) {
            Route::middleware('api')->prefix(self::API_PREFIX)->group("{$routes}/api.php");
        }

        if (is_file("{$routes}/web.php")) {
            Route::middleware('web')->group("{$routes}/web.php");
        }
    }
}
