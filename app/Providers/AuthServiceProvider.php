<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Mendaftarkan service autentikasi dan otorisasi aplikasi.
     *
     * Function ini digunakan untuk mengatur policy, gate, atau aturan
     * otorisasi lain jika aplikasi membutuhkannya.
     */
    public function boot(): void
    {
        //
    }
}
