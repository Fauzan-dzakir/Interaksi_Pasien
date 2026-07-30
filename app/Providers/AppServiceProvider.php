<?php

namespace App\Providers;

use Carbon\Carbon;
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
        // Carbon tidak otomatis mengikuti locale Laravel — tanpa ini,
        // diffForHumans() (dipakai di pusat notifikasi & dashboard unit)
        // selalu tampil dalam bahasa Inggris ("2 hours ago") walau
        // APP_LOCALE sudah id.
        Carbon::setLocale(config('app.locale'));
    }
}
