<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Monitoring Tanaman</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-700">
    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8">

        {{-- HEADER --}}
        <header class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100/70 text-emerald-700 sm:h-10 sm:w-10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/><path d="M12 9v6"/><path d="M9 12h6"/></svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-lg font-semibold text-gray-900 sm:text-2xl">Dashboard Monitoring Tanaman</h1>
                    <p class="mt-0.5 text-xs text-gray-500 sm:text-sm">Data ESP32 — soil moisture, HC-SR04, DHT22.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-sm sm:gap-3">
                <a href="{{ route('evaluation') }}" class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-5"/></svg>
                    Evaluasi
                </a>
                <span id="liveStatus" class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium">
                    <span id="liveDot" class="h-2 w-2 rounded-full bg-gray-400"></span>
                    <span id="liveLabel">Connecting...</span>
                </span>
                <div class="flex items-center gap-1.5 text-xs text-gray-500 sm:text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span id="lastUpdate" class="font-medium text-gray-700">
                        {{ $latest?->created_at?->format('d M Y H:i:s') ?? '-' }}
                    </span>
                </div>
            </div>
        </header>

        {{-- EMPTY STATE --}}
        <section id="emptyState" class="{{ $latest ? 'hidden' : '' }} rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center">
            <h2 class="text-base font-medium text-gray-900">Belum ada data sensor</h2>
            <p class="mt-1 text-sm text-gray-500">
                Kirim data ESP32 ke
                <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-700">/api/sensor-data</code>
            </p>
        </section>

        {{-- DATA SECTION --}}
        <div id="dataSection" class="{{ $latest ? '' : 'hidden' }}">

            {{-- KPI CARDS --}}
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-lg border border-gray-200 bg-white p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Kelembapan Tanah</p>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100/70 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                        </span>
                    </div>
                    <div id="kpiMoisture" class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ $latest ? number_format($latest->moisture_percent, 1) . '%' : '—' }}
                    </div>
                    <p id="kpiMoistureMeta" class="mt-1 text-sm text-gray-500">{{ $latest?->soil_condition ?? '—' }}</p>
                </article>
                <article class="rounded-lg border border-gray-200 bg-white p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Cadangan Air</p>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-sky-100/70 text-sky-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h14l-1.5 16a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2L5 3z"/><path d="M5 8h14"/></svg>
                        </span>
                    </div>
                    <div id="kpiWater" class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ $latest && $latest->water_level_percent !== null ? number_format($latest->water_level_percent, 1) . '%' : '—' }}
                    </div>
                    <p id="kpiWaterMeta" class="mt-1 text-sm text-gray-500">{{ $latest?->water_status ?? '—' }}</p>
                </article>
                <article class="rounded-lg border border-gray-200 bg-white p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Suhu Udara</p>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100/70 text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/></svg>
                        </span>
                    </div>
                    <div id="kpiTemp" class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ $latest && $latest->temperature !== null ? number_format($latest->temperature, 1) . ' °C' : '—' }}
                    </div>
                    <p id="kpiTempMeta" class="mt-1 text-sm text-gray-500">
                        {{ $latest ? ($latest->dht_ok ? 'DHT22 OK' : 'DHT22 error') : '—' }}
                    </p>
                </article>
                <article class="rounded-lg border border-gray-200 bg-white p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">IKP</p>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100/70 text-rose-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 14l4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>
                        </span>
                    </div>
                    <div id="kpiIkp" class="mt-2 text-2xl font-semibold text-gray-900">{{ $latest?->ikp ?? '—' }}</div>
                    <p id="kpiIkpMeta" class="mt-1 text-sm text-gray-500">{{ $latest?->watering_status ?? '—' }}</p>
                </article>
            </section>

            {{-- EVALUASI RINGKAS (window: Hari Ini) --}}
            <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-lg border border-gray-200 bg-white p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">PDR (Hari Ini)</p>
                    <div id="evalPdr" class="mt-2 text-2xl font-semibold text-gray-900">—%</div>
                    <p id="evalPdrMeta" class="mt-1 text-xs text-gray-500">Packet Delivery Ratio</p>
                </article>
                <article class="rounded-lg border border-gray-200 bg-white p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Sent / Received</p>
                    <div id="evalCount" class="mt-2 text-2xl font-semibold text-gray-900">— / —</div>
                    <p id="evalLost" class="mt-1 text-xs text-gray-500">Hilang: —</p>
                </article>
                <article class="rounded-lg border border-gray-200 bg-white p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Delay Rata-rata</p>
                    <div id="evalDelay" class="mt-2 text-2xl font-semibold text-gray-900">— ms</div>
                    <p id="evalDelayMeta" class="mt-1 text-xs text-gray-500">min — / max — ms</p>
                </article>
                <article class="rounded-lg border border-gray-200 bg-white p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Status Sistem</p>
                    <div id="evalStatus" class="mt-2 text-2xl font-semibold text-gray-900">—</div>
                    <p id="evalStatusMeta" class="mt-1 text-xs text-gray-500">Online & value tampil</p>
                </article>
            </section>

            {{-- CHART + DETAIL --}}
            <section class="mt-6 grid gap-6 lg:grid-cols-3">
                <article class="rounded-lg border border-gray-200 bg-white p-5 lg:col-span-2">
                    <h2 class="flex items-center gap-2 text-base font-medium text-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Grafik Sensor
                    </h2>
                    <div class="mt-4 h-72">
                        <canvas id="sensorChart"></canvas>
                    </div>
                </article>

                <article class="rounded-lg border border-gray-200 bg-white p-5">
                    <h2 class="flex items-center gap-2 text-base font-medium text-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Detail Terbaru
                    </h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Device</dt><dd id="dDevice" class="text-gray-900">{{ $latest?->device_id ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Sequence</dt><dd id="dSeq" class="text-gray-900">{{ $latest?->sequence_no ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Soil Raw</dt><dd id="dSoilRaw" class="text-gray-900">{{ $latest?->soil_raw ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Jarak Air</dt><dd id="dDist" class="text-gray-900">{{ $latest && $latest->distance_cm !== null ? number_format($latest->distance_cm, 2) . ' cm' : '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Volume Air</dt><dd id="dVol" class="text-gray-900">{{ $latest && $latest->water_volume_ml !== null ? number_format($latest->water_volume_ml, 0) . ' ml' : '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Humidity</dt><dd id="dHum" class="text-gray-900">{{ $latest && $latest->humidity !== null ? number_format($latest->humidity, 1) . '%' : '—' }}</dd></div>
                        <div class="flex justify-between border-t border-gray-100 pt-2"><dt class="text-gray-500">Skor T/A/S</dt><dd id="dScores" class="text-gray-900">{{ $latest ? "{$latest->soil_score} / {$latest->water_score} / {$latest->temp_score}" : '—' }}</dd></div>
                    </dl>
                </article>
            </section>

            {{-- HISTORY TABLE --}}
            <section class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-5 py-3 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-base font-medium text-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                        Riwayat Data
                    </h2>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                        Auto-refresh tiap 2 detik
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-2.5 font-medium">Waktu</th>
                                <th class="px-5 py-2.5 font-medium">Tanah</th>
                                <th class="px-5 py-2.5 font-medium">Air</th>
                                <th class="px-5 py-2.5 font-medium">Suhu</th>
                                <th class="px-5 py-2.5 font-medium">Humidity</th>
                                <th class="px-5 py-2.5 font-medium">IKP</th>
                                <th class="px-5 py-2.5 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody id="historyBody" class="divide-y divide-gray-100">
                            @foreach ($readings as $reading)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-5 py-2.5 text-gray-500">{{ $reading->created_at->format('H:i:s') }}</td>
                                    <td class="px-5 py-2.5">{{ number_format($reading->moisture_percent, 1) }}% <span class="text-gray-400">·</span> <span class="text-gray-500">{{ $reading->soil_condition }}</span></td>
                                    <td class="px-5 py-2.5">{{ $reading->water_level_percent === null ? '—' : number_format($reading->water_level_percent, 1) . '%' }}</td>
                                    <td class="px-5 py-2.5">{{ $reading->temperature === null ? '—' : number_format($reading->temperature, 1) . ' °C' }}</td>
                                    <td class="px-5 py-2.5">{{ $reading->humidity === null ? '—' : number_format($reading->humidity, 1) . '%' }}</td>
                                    <td class="px-5 py-2.5 font-medium text-gray-900">{{ $reading->ikp }}</td>
                                    <td class="px-5 py-2.5 text-gray-600">{{ $reading->watering_status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <footer class="mt-10 text-center text-xs text-gray-400">
            IoT Plant Monitoring · {{ now()->format('Y') }}
        </footer>
    </main>

    <script>
        // ============================================================
        // REAL-TIME DASHBOARD
        // Polling /api/sensor-readings/history setiap 2 detik.
        // Update KPI, detail panel, tabel riwayat, dan chart tanpa reload.
        // Watchdog: > 25 detik tanpa data baru -> status berubah Disconnected.
        // ============================================================

        const POLL_INTERVAL_MS = 2000;          // polling tiap 2 detik
        const STALE_THRESHOLD_MS = 25000;       // > 25 detik tanpa data = disconnect
        const WATCHDOG_INTERVAL_MS = 1000;      // cek staleness tiap 1 detik
        const HISTORY_LIMIT = 50;
        const HISTORY_ROWS = 20; // jumlah baris tabel yang ditampilkan

        // ---------- Helper Format ----------
        const fmt1 = (v) => (v === null || v === undefined) ? '—' : Number(v).toFixed(1);
        const fmt2 = (v) => (v === null || v === undefined) ? '—' : Number(v).toFixed(2);
        const fmt0 = (v) => (v === null || v === undefined) ? '—' : Math.round(Number(v)).toString();

        const fmtDate = (iso) => {
            if (!iso) return '-';
            const d = new Date(iso);
            const pad = (n) => String(n).padStart(2, '0');
            const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            return `${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
        };

        const fmtTime = (iso) => {
            if (!iso) return '—';
            const d = new Date(iso);
            const pad = (n) => String(n).padStart(2, '0');
            return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
        };

        const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));

        // ---------- Live Status Indicator ----------
        const setLiveStatus = (state) => {
            const dot = document.getElementById('liveDot');
            const label = document.getElementById('liveLabel');
            const wrap = document.getElementById('liveStatus');

            wrap.classList.remove('border-emerald-200','bg-emerald-50','text-emerald-700','border-rose-200','bg-rose-50','text-rose-700','border-gray-200','bg-gray-100','text-gray-500');
            dot.classList.remove('bg-emerald-500','bg-rose-500','bg-gray-400','animate-pulse');

            if (state === 'live') {
                wrap.classList.add('border-emerald-200','bg-emerald-50','text-emerald-700');
                dot.classList.add('bg-emerald-500','animate-pulse');
                label.textContent = 'Live';
            } else if (state === 'error') {
                wrap.classList.add('border-rose-200','bg-rose-50','text-rose-700');
                dot.classList.add('bg-rose-500');
                label.textContent = 'Disconnected';
            } else if (state === 'stale') {
                wrap.classList.add('border-rose-200','bg-rose-50','text-rose-700');
                dot.classList.add('bg-rose-500');
                label.textContent = 'Disconnected';
            } else {
                wrap.classList.add('border-gray-200','bg-gray-100','text-gray-500');
                dot.classList.add('bg-gray-400');
                label.textContent = 'Connecting...';
            }
        };

        // ---------- Watchdog: deteksi data stale ----------
        // Kalau > STALE_THRESHOLD_MS tanpa data baru, paksa status -> Disconnected
        let lastDataReceivedAt = null;

        const checkStaleness = () => {
            if (lastDataReceivedAt === null) return;
            const diff = Date.now() - lastDataReceivedAt;
            if (diff > STALE_THRESHOLD_MS) {
                setLiveStatus('stale');
            }
        };

        // ---------- Chart Setup ----------
        let chart = null;

        const initChart = () => {
            const ctx = document.getElementById('sensorChart');
            if (!ctx) return;

            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [
                        { label: 'Moisture %', data: @json($moistureData), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.3, borderWidth: 2, pointRadius: 0 },
                        { label: 'Water %', data: @json($waterData), borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.1)', tension: 0.3, borderWidth: 2, pointRadius: 0 },
                        { label: 'Temperature °C', data: @json($temperatureData), borderColor: '#f59e0b', tension: 0.3, borderWidth: 2, pointRadius: 0 },
                        { label: 'Humidity %', data: @json($humidityData), borderColor: '#8b5cf6', tension: 0.3, borderWidth: 2, pointRadius: 0 },
                        { label: 'IKP', data: @json($ikpData), borderColor: '#ef4444', tension: 0.3, borderWidth: 2, pointRadius: 0 },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 400 },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#6b7280', usePointStyle: true, pointStyle: 'circle', padding: 14, font: { size: 11 } } },
                        tooltip: { backgroundColor: '#fff', borderColor: '#e5e7eb', borderWidth: 1, titleColor: '#111827', bodyColor: '#374151', padding: 10, cornerRadius: 6, displayColors: true, boxPadding: 4 },
                    },
                    scales: {
                        x: { ticks: { color: '#9ca3af', font: { size: 10 }, maxRotation: 0 }, grid: { color: '#f3f4f6', drawTicks: false }, border: { display: false } },
                        y: { ticks: { color: '#9ca3af', font: { size: 10 } }, grid: { color: '#f3f4f6', drawTicks: false }, border: { display: false }, beginAtZero: true },
                    },
                },
            });
        };

        // ---------- Update Chart ----------
        const updateChart = (readings) => {
            if (!chart) return;
            // readings urut terbaru -> oldest. Balik supaya chart kiri->kanan = oldest->latest.
            const ordered = [...readings].reverse();

            chart.data.labels = ordered.map(r => fmtTime(r.created_at));
            chart.data.datasets[0].data = ordered.map(r => r.moisture_percent);
            chart.data.datasets[1].data = ordered.map(r => r.water_level_percent);
            chart.data.datasets[2].data = ordered.map(r => r.temperature);
            chart.data.datasets[3].data = ordered.map(r => r.humidity);
            chart.data.datasets[4].data = ordered.map(r => r.ikp);
            chart.update('none');
        };

        // ---------- Update KPI + Detail ----------
        const updateLatest = (latest) => {
            if (!latest) return;

            document.getElementById('lastUpdate').textContent = fmtDate(latest.created_at);

            // KPI
            document.getElementById('kpiMoisture').textContent = fmt1(latest.moisture_percent) + '%';
            document.getElementById('kpiMoistureMeta').textContent = latest.soil_condition ?? '—';

            document.getElementById('kpiWater').textContent =
                latest.water_level_percent === null ? '—' : fmt1(latest.water_level_percent) + '%';
            document.getElementById('kpiWaterMeta').textContent = latest.water_status ?? '—';

            document.getElementById('kpiTemp').textContent =
                latest.temperature === null ? '—' : fmt1(latest.temperature) + ' °C';
            document.getElementById('kpiTempMeta').textContent = latest.dht_ok ? 'DHT22 OK' : 'DHT22 error';

            document.getElementById('kpiIkp').textContent = latest.ikp ?? '—';
            document.getElementById('kpiIkpMeta').textContent = latest.watering_status ?? '—';

            // Detail
            document.getElementById('dDevice').textContent = latest.device_id ?? '—';
            document.getElementById('dSeq').textContent = latest.sequence_no ?? '—';
            document.getElementById('dSoilRaw').textContent = latest.soil_raw ?? '—';
            document.getElementById('dDist').textContent =
                latest.distance_cm === null ? '—' : fmt2(latest.distance_cm) + ' cm';
            document.getElementById('dVol').textContent =
                latest.water_volume_ml === null ? '—' : fmt0(latest.water_volume_ml) + ' ml';
            document.getElementById('dHum').textContent =
                latest.humidity === null ? '—' : fmt1(latest.humidity) + '%';
            document.getElementById('dScores').textContent =
                `${latest.soil_score} / ${latest.water_score} / ${latest.temp_score}`;
        };

        // ---------- Update Tabel Riwayat ----------
        const updateTable = (readings) => {
            const tbody = document.getElementById('historyBody');
            if (!tbody) return;

            const rows = readings.slice(0, HISTORY_ROWS).map(r => {
                const water = r.water_level_percent === null ? '—' : fmt1(r.water_level_percent) + '%';
                const temp  = r.temperature === null ? '—' : fmt1(r.temperature) + ' °C';
                const hum   = r.humidity === null ? '—' : fmt1(r.humidity) + '%';
                return `
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-5 py-2.5 text-gray-500">${fmtTime(r.created_at)}</td>
                        <td class="px-5 py-2.5">${fmt1(r.moisture_percent)}% <span class="text-gray-400">·</span> <span class="text-gray-500">${escapeHtml(r.soil_condition)}</span></td>
                        <td class="px-5 py-2.5">${water}</td>
                        <td class="px-5 py-2.5">${temp}</td>
                        <td class="px-5 py-2.5">${hum}</td>
                        <td class="px-5 py-2.5 font-medium text-gray-900">${r.ikp}</td>
                        <td class="px-5 py-2.5 text-gray-600">${escapeHtml(r.watering_status)}</td>
                    </tr>
                `;
            }).join('');

            tbody.innerHTML = rows;
        };

        // ---------- Toggle Empty State ----------
        const toggleEmpty = (hasData) => {
            document.getElementById('emptyState').classList.toggle('hidden', hasData);
            document.getElementById('dataSection').classList.toggle('hidden', !hasData);
        };

        // ---------- Polling ----------
        let lastSeq = null;
        let lastId = null;

        const fetchData = async () => {
            try {
                const res = await fetch(`/api/sensor-readings/history?limit=${HISTORY_LIMIT}`, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const json = await res.json();
                const readings = json.data ?? [];

                if (readings.length === 0) {
                    toggleEmpty(false);
                    setLiveStatus('stale');
                    return;
                }

                toggleEmpty(true);

                const latest = readings[0];

                // Cek apakah ada data baru (sequence_no atau id berubah)
                const isNewData = latest.sequence_no !== lastSeq || latest.id !== lastId;

                if (isNewData) {
                    updateLatest(latest);
                    updateTable(readings);
                    if (!chart) initChart();
                    updateChart(readings);
                    lastSeq = latest.sequence_no;
                    lastId = latest.id;
                    lastDataReceivedAt = Date.now();
                    setLiveStatus('live');
                } else {
                    // Tidak ada data baru — cek staleness berdasar timestamp data terakhir
                    const latestTs = new Date(latest.created_at).getTime();
                    const age = Date.now() - latestTs;
                    if (age > STALE_THRESHOLD_MS) {
                        setLiveStatus('stale');
                    } else {
                        setLiveStatus('live');
                    }
                }
            } catch (err) {
                console.error('Polling error:', err);
                setLiveStatus('error');
            }
        };

        // ---------- Evaluasi Ringkas (window: Hari Ini) ----------
        const fetchEvaluation = async () => {
            try {
                const res = await fetch('/api/evaluation/metrics', {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });
                if (!res.ok) return;
                const r = await res.json();
                const m = r.metrics.today;
                const s = r.dashboard_status;

                document.getElementById('evalPdr').textContent = `${m.pdr}%`;
                document.getElementById('evalPdrMeta').textContent = m.pdr_estimated
                    ? 'Estimasi (firmware lama)'
                    : 'Packet Delivery Ratio';
                document.getElementById('evalCount').textContent = `${m.sent} / ${m.received}`;
                document.getElementById('evalLost').textContent = `Hilang: ${m.lost}`;
                document.getElementById('evalDelay').textContent =
                    m.delay_avg_ms === null ? '— ms' : `${m.delay_avg_ms} ms`;
                document.getElementById('evalDelayMeta').textContent =
                    `min ${m.delay_min_ms ?? '—'} / max ${m.delay_max_ms ?? '—'} ms`;

                const ok = s.online && s.value_displayed;
                document.getElementById('evalStatus').textContent = ok ? 'OK' : 'Cek';
                document.getElementById('evalStatusMeta').textContent =
                    `Online: ${s.online ? 'Ya' : 'Tidak'} · Value: ${s.value_displayed ? 'Ya' : 'Tidak'}`;
            } catch (err) {
                console.error('Evaluation polling error:', err);
            }
        };

        // ---------- Boot ----------
        document.addEventListener('DOMContentLoaded', () => {
            @if ($latest)
                initChart();
                // Inisialisasi watchdog dari timestamp data awal server
                lastDataReceivedAt = new Date('{{ $latest->created_at->toIso8601String() }}').getTime();
            @endif
            setLiveStatus('connecting');
            fetchData();
            fetchEvaluation();
            setInterval(fetchData, POLL_INTERVAL_MS);
            setInterval(checkStaleness, WATCHDOG_INTERVAL_MS);
            setInterval(fetchEvaluation, 5000);
        });
    </script>
</body>
</html>
