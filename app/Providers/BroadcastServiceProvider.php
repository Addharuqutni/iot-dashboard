<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Mendaftarkan route broadcast aplikasi.
     *
     * Function ini mengaktifkan route broadcasting dan memuat definisi
     * channel dari routes/channels.php.
     */
    public function boot(): void
    {
        Broadcast::routes();

        require base_path('routes/channels.php');
    }
}
