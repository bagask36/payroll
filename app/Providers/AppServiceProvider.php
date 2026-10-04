<?php

namespace App\Providers;

use App\Contracts\DocumentStorageProvider;
use App\Contracts\FaceAiService;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Document\DocumentService;
use App\Services\Document\LocalStorageProvider;
use App\Services\FaceAi\LocalMockFaceAiService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FaceAiService::class, function () {
            return new LocalMockFaceAiService;
        });

        $this->app->singleton(DocumentStorageProvider::class, function () {
            return new LocalStorageProvider(disk: 'local');
        });

        $this->app->singleton(DocumentService::class);
        $this->app->singleton(AuditService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! app()->environment('local') || str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        if (! class_exists('PowerComponents\LivewirePowerGrid\Exportable')) {
            class_alias(\PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable::class, 'PowerComponents\LivewirePowerGrid\Exportable');
        }

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->isSuperAdmin()) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
