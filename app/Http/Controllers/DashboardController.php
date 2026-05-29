<?php

namespace App\Http\Controllers;

use App\Models\SensorReading;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard monitoring tanaman.
     *
     * Function ini mengambil data sensor terbaru, riwayat data terakhir,
     * dan menyiapkan data grafik untuk dikirim ke view dashboard.
     */
    public function index(): View
    {
        $latest = SensorReading::latest()->first();

        $readings = SensorReading::query()
            ->latest()
            ->limit(50)
            ->get();

        $chartReadings = $readings->reverse()->values();

        return view('dashboard', [
            'latest' => $latest,
            'readings' => $readings,
            'chartLabels' => $chartReadings->map(fn ($item) => $item->created_at->format('H:i:s')),
            'moistureData' => $chartReadings->pluck('moisture_percent'),
            'waterData' => $chartReadings->pluck('water_level_percent'),
            'temperatureData' => $chartReadings->pluck('temperature'),
            'humidityData' => $chartReadings->pluck('humidity'),
            'ikpData' => $chartReadings->pluck('ikp'),
        ]);
    }
}
