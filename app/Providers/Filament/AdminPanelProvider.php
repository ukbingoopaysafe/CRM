<?php

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
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\App;
use App\Filament\Resources\RentalRequests\RentalRequestResource;
use App\Filament\Resources\ResaleRequests\ResaleRequestResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Brokers\BrokerResource;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        App::setLocale('ar');
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('سيبل العقارية')
            ->font('Cairo')
            ->colors([
                'primary' => Color::hex('#016a71'),
                'gray' => [
                    50 => '#faf8f5',
                    100 => '#f5f3ef',
                    200 => '#e8e6e1',
                    300 => '#d1d1cd',
                    400 => '#b5b4af',
                    500 => '#92918b',
                    600 => '#72706b',
                    700 => '#4f4d47',
                    800 => '#27251e',
                    900 => '#1a1914',
                    950 => '#000000',
                ],
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop(false)
            ->resources([
        \App\Filament\Resources\RentalRequests\RentalRequestResource::class,
        \App\Filament\Resources\ResaleRequests\ResaleRequestResource::class,
        \App\Filament\Resources\Clients\ClientResource::class,
        \App\Filament\Resources\Brokers\BrokerResource::class,
    ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
