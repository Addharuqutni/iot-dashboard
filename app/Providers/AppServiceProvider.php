<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Mendaftarkan service aplikasi ke Laravel container.
     *
     * Function ini digunakan untuk binding class, singleton, atau service
     * lain yang perlu tersedia melalui dependency injection.
     */
    public function register(): void
    {
        //
    }

    /**
     * Menjalankan proses awal service aplikasi.
     *
     * Function ini dipakai untuk konfigurasi global saat aplikasi mulai,
     * seperti macro, observer, atau pengaturan model.
     */
    public function boot(): void
    {
        //
    }
}
