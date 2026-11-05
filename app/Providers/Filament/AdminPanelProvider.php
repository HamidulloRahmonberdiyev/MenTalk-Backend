<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->discoverModuleResources($panel)
            ->default()
            ->id('admin')
            ->domain(config('admin.domain'))
            ->path(config('admin.path'))
            ->brandName('MenTalk Admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups(['Kontent', 'Foydalanuvchilar'])
            ->sidebarCollapsibleOnDesktop()
            ->unsavedChangesAlerts()
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Modules/Admin/Filament/Widgets'), for: 'App\Modules\Admin\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /** Each module keeps its admin screens in its own `Filament/Resources`. */
    private function discoverModuleResources(Panel $panel): Panel
    {
        foreach (glob(app_path('Modules/*/Filament/Resources'), GLOB_ONLYDIR) as $resources) {
            $module = basename(dirname($resources, 2));

            $panel->discoverResources(in: $resources, for: "App\\Modules\\{$module}\\Filament\\Resources");
        }

        return $panel;
    }
}
