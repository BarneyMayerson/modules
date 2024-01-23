<?php

namespace Modules\Payment\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payment\Actions\CreatePaymentForOrder;
use Modules\Payment\Actions\CreatePaymentForOrderInterface;
use Modules\Payment\PayBuddyGateway;
use Modules\Payment\PayBuddySdk;
use Modules\Payment\PaymentGateway;

class PaymentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . "/../Database/Migrations");

        $this->app->bind(
            abstract: PaymentGateway::class,
            concrete: fn() => new PayBuddyGateway(new PayBuddySdk())
        );
        $this->app->bind(
            abstract: CreatePaymentForOrderInterface::class,
            concrete: fn() => new CreatePaymentForOrder()
        );
    }
}
