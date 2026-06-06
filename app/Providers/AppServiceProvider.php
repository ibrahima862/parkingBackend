<?php

namespace App\Providers;

use App\Models\Parking;
use App\Models\Reservation;
use App\Models\User;
use App\Observers\ParkingObserver;
use App\Observers\ReservationObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Parking::observe(ParkingObserver::class);
        Parking::observe(ParkingObserver::class);
        Reservation::observe(ReservationObserver::class);
    }
}
