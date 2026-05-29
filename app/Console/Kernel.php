<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Menentukan jadwal command otomatis aplikasi.
     *
     * Function ini dipakai jika aplikasi membutuhkan task terjadwal,
     * misalnya menjalankan command Laravel setiap jam atau setiap hari.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Mendaftarkan command console milik aplikasi.
     *
     * Function ini memuat command dari folder app/Console/Commands
     * dan route console dari routes/console.php.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
