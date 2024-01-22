<?php

namespace Modules\Order\Infrastructure\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . "/../Database/Migrations");
        $this->mergeConfigFrom(__DIR__ . "/../config.php", "order");

        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        $this->loadViewsFrom(
            path: __DIR__ . "/../../UI/Views",
            namespace: "order"
        );

        Blade::anonymousComponentPath(
            path: __DIR__ . "/../../UI/Views/components",
            prefix: "order"
        );
        Blade::componentNamespace(
            namespace: "Modules\\Order\\UI\\ViewComponents",
            prefix: "order"
        );
    }
}
