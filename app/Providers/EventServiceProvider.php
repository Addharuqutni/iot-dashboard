<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Mendaftarkan event dan listener aplikasi.
     *
     * Function ini digunakan untuk konfigurasi tambahan event jika daftar
     * listener pada property listen belum cukup.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Menentukan apakah event listener ditemukan otomatis oleh Laravel.
     *
     * Function ini mengembalikan false agar aplikasi hanya memakai listener
     * yang didaftarkan secara eksplisit.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
