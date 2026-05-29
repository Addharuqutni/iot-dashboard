<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Monitoring Tanaman</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-emerald-400">IoT Plant Monitoring</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Dashboard Penyiraman Tanaman Pot</h1>
                <p class="mt-2 text-slate-400">Data ESP32, soil moisture, HC-SR04, dan DHT22.</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3 text-sm text-slate-300">
                Terakhir update:
                <span class="font-semibold text-white">{{ $latest?->created_at?->format('d M Y H:i:s') ?? 'Belum ada data' }}</span>
            </div>
        </div>

        @if (!$latest)
            <section class="rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                <h2 class="text-xl font-semibold">Belum ada data sensor</h2>
                <p class="mt-2 text-slate-400">Kirim data dari ESP32 ke endpoint <code class="text-emerald-300">/api/sensor-data</code>.</p>
            </section>
        @else
            @php
                $cards = [
                    ['label' => 'Kelembapan Tanah', 'value' => number_format($latest->moisture_percent, 2) . '%', 'meta' => $latest->soil_condition, 'color' => 'emerald'],
                    ['label' => 'Cadangan Air', 'value' => $latest->water_level_percent === null ? 'Error' : number_format($latest->water_level_percent, 2) . '%', 'meta' => $latest->water_status, 'color' => 'sky'],
                    ['label' => 'Suhu Udara', 'value' => $latest->temperature === null ? 'Error' : number_format($latest->temperature, 2) . ' °C', 'meta' => $latest->dht_ok ? 'DHT22 OK' : 'DHT22 error', 'color' => 'amber'],
                    ['label' => 'IKP', 'value' => $latest->ikp, 'meta' => $latest->watering_status, 'color' => 'rose'],
                ];
            @endphp

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($cards as $card)
                    <article class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5 shadow-2xl shadow-black/20">
                        <p class="text-sm text-slate-400">{{ $card['label'] }}</p>
                        <div class="mt-3 text-3xl font-bold">{{ $card['value'] }}</div>
                        <div class="mt-4 inline-flex rounded-full bg-slate-800 px-3 py-1 text-sm text-slate-200">{{ $card['meta'] }}</div>
                    </article>
                @endforeach
            </section>

            <section class="mt-6 grid gap-6 lg:grid-cols-3">
                <article class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5 lg:col-span-2">
                    <h2 class="text-lg font-semibold">Grafik Sensor</h2>
                    <div class="mt-4 h-80">
                        <canvas id="sensorChart"></canvas>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
                    <h2 class="text-lg font-semibold">Detail Terbaru</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Device</dt><dd>{{ $latest->device_id }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Sequence</dt><dd>{{ $latest->sequence_no }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Soil Raw</dt><dd>{{ $latest->soil_raw }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Jarak Air</dt><dd>{{ $latest->distance_cm === null ? 'Error' : number_format($latest->distance_cm, 2) . ' cm' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Volume Air</dt><dd>{{ $latest->water_volume_ml === null ? 'Error' : number_format($latest->water_volume_ml, 2) . ' ml' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Humidity</dt><dd>{{ $latest->humidity === null ? 'Error' : number_format($latest->humidity, 2) . '%' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Skor Tanah/Air/Suhu</dt><dd>{{ $latest->soil_score }}/{{ $latest->water_score }}/{{ $latest->temp_score }}</dd></div>
                    </dl>
                </article>
            </section>

            <section class="mt-6 overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/80">
                <div class="border-b border-slate-800 px-5 py-4">
                    <h2 class="text-lg font-semibold">Riwayat Data</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800 text-sm">
                        <thead class="bg-slate-900 text-left text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Tanah</th>
                                <th class="px-4 py-3">Air</th>
                                <th class="px-4 py-3">Suhu</th>
                                <th class="px-4 py-3">Humidity</th>
                                <th class="px-4 py-3">IKP</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach ($readings as $reading)
                                <tr class="hover:bg-slate-800/40">
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $reading->created_at->format('H:i:s') }}</td>
                                    <td class="px-4 py-3">{{ number_format($reading->moisture_percent, 2) }}% · {{ $reading->soil_condition }}</td>
                                    <td class="px-4 py-3">{{ $reading->water_level_percent === null ? 'Error' : number_format($reading->water_level_percent, 2) . '%' }}</td>
                                    <td class="px-4 py-3">{{ $reading->temperature === null ? 'Error' : number_format($reading->temperature, 2) . ' °C' }}</td>
                                    <td class="px-4 py-3">{{ $reading->humidity === null ? 'Error' : number_format($reading->humidity, 2) . '%' }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ $reading->ikp }}</td>
                                    <td class="px-4 py-3">{{ $reading->watering_status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </main>

    @if ($latest)
        <script>
            const ctx = document.getElementById('sensorChart');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [
                        { label: 'Moisture %', data: @json($moistureData), borderColor: '#34d399', tension: 0.35 },
                        { label: 'Water %', data: @json($waterData), borderColor: '#38bdf8', tension: 0.35 },
                        { label: 'Temperature °C', data: @json($temperatureData), borderColor: '#f59e0b', tension: 0.35 },
                        { label: 'Humidity %', data: @json($humidityData), borderColor: '#a78bfa', tension: 0.35 },
                        { label: 'IKP', data: @json($ikpData), borderColor: '#fb7185', tension: 0.35 },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#cbd5e1' } } },
                    scales: {
                        x: { ticks: { color: '#94a3b8' }, grid: { color: '#1e293b' } },
                        y: { ticks: { color: '#94a3b8' }, grid: { color: '#1e293b' }, beginAtZero: true }
                    }
                }
            });
        </script>
    @endif
</body>
</html>
