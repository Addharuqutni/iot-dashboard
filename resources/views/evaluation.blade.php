<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evaluasi Sistem - IoT Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-700">
    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8">

        {{-- HEADER --}}
        <header class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100/70 text-indigo-700 sm:h-10 sm:w-10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-5"/></svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-lg font-semibold text-gray-900 sm:text-2xl">Evaluasi Sistem IoT</h1>
                    <p class="mt-0.5 text-xs text-gray-500 sm:text-sm">Packet Delivery Ratio, delay pengiriman, dan status sistem.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-sm sm:gap-3">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                    Dashboard
                </a>
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    Auto-refresh tiap 5 detik
                </span>
                <span id="genAt" class="text-xs text-gray-500">Generated: {{ $report['generated_at'] }}</span>
            </div>
        </header>

        {{-- STATUS DASHBOARD --}}
        <section class="mb-6 grid gap-4 sm:grid-cols-3">
            <article class="rounded-lg border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Dashboard Online</p>
                <div id="statusOnline" class="mt-2 text-2xl font-semibold text-gray-900">
                    {{ $report['dashboard_status']['online'] ? 'Ya' : 'Tidak' }}
                </div>
                <p class="mt-1 break-all text-xs text-gray-500">{{ $report['dashboard_status']['url'] ?? '-' }}</p>
            </article>
            <article class="rounded-lg border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Value Tampil</p>
                <div id="statusValue" class="mt-2 text-2xl font-semibold text-gray-900">
                    {{ $report['dashboard_status']['value_displayed'] ? 'Ya' : 'Tidak' }}
                </div>
                <p id="statusValueMeta" class="mt-1 text-xs text-gray-500">
                    Umur data terakhir:
                    <span id="latestAge">{{ $report['dashboard_status']['latest_age_seconds'] ?? '—' }}</span> detik
                </p>
            </article>
            <article class="rounded-lg border border-gray-200 bg-white p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Threshold Stale</p>
                <div class="mt-2 text-2xl font-semibold text-gray-900">
                    {{ $report['dashboard_status']['threshold_seconds'] }} detik
                </div>
                <p class="mt-1 text-xs text-gray-500">Data lebih lama dianggap disconnect.</p>
            </article>
        </section>

        {{-- METRIK PER WINDOW --}}
        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-3">
                <h2 class="text-base font-medium text-gray-900">Metrik Pengiriman</h2>
                <p class="mt-0.5 text-xs text-gray-500">Sent dihitung dari counter ESP32 (device_total_sent). Received dihitung dari row di database.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-2.5 font-medium">Metrik</th>
                            <th class="px-5 py-2.5 font-medium">1 Jam Terakhir</th>
                            <th class="px-5 py-2.5 font-medium">Hari Ini</th>
                            <th class="px-5 py-2.5 font-medium">All-time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-5 py-2.5 font-medium text-gray-700">Jumlah data terkirim</td>
                            <td class="px-5 py-2.5" id="sent-hour">{{ $report['metrics']['hour']['sent'] }}</td>
                            <td class="px-5 py-2.5" id="sent-today">{{ $report['metrics']['today']['sent'] }}</td>
                            <td class="px-5 py-2.5" id="sent-all">{{ $report['metrics']['all']['sent'] }}</td>
                        </tr>
                        <tr class="bg-gray-50/50">
                            <td class="px-5 py-2.5 font-medium text-gray-700">Jumlah data masuk DB</td>
                            <td class="px-5 py-2.5" id="received-hour">{{ $report['metrics']['hour']['received'] }}</td>
                            <td class="px-5 py-2.5" id="received-today">{{ $report['metrics']['today']['received'] }}</td>
                            <td class="px-5 py-2.5" id="received-all">{{ $report['metrics']['all']['received'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-2.5 font-medium text-gray-700">Packet Delivery Ratio</td>
                            <td class="px-5 py-2.5" id="pdr-hour">{{ $report['metrics']['hour']['pdr'] }}%</td>
                            <td class="px-5 py-2.5" id="pdr-today">{{ $report['metrics']['today']['pdr'] }}%</td>
                            <td class="px-5 py-2.5" id="pdr-all">{{ $report['metrics']['all']['pdr'] }}%</td>
                        </tr>
                        <tr class="bg-gray-50/50">
                            <td class="px-5 py-2.5 font-medium text-gray-700">Packet hilang</td>
                            <td class="px-5 py-2.5" id="lost-hour">{{ $report['metrics']['hour']['lost'] }}</td>
                            <td class="px-5 py-2.5" id="lost-today">{{ $report['metrics']['today']['lost'] }}</td>
                            <td class="px-5 py-2.5" id="lost-all">{{ $report['metrics']['all']['lost'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-2.5 font-medium text-gray-700">Delay rata-rata</td>
                            <td class="px-5 py-2.5" id="davg-hour">{{ $report['metrics']['hour']['delay_avg_ms'] ?? '—' }} ms</td>
                            <td class="px-5 py-2.5" id="davg-today">{{ $report['metrics']['today']['delay_avg_ms'] ?? '—' }} ms</td>
                            <td class="px-5 py-2.5" id="davg-all">{{ $report['metrics']['all']['delay_avg_ms'] ?? '—' }} ms</td>
                        </tr>
                        <tr class="bg-gray-50/50">
                            <td class="px-5 py-2.5 font-medium text-gray-700">Delay min</td>
                            <td class="px-5 py-2.5" id="dmin-hour">{{ $report['metrics']['hour']['delay_min_ms'] ?? '—' }} ms</td>
                            <td class="px-5 py-2.5" id="dmin-today">{{ $report['metrics']['today']['delay_min_ms'] ?? '—' }} ms</td>
                            <td class="px-5 py-2.5" id="dmin-all">{{ $report['metrics']['all']['delay_min_ms'] ?? '—' }} ms</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-2.5 font-medium text-gray-700">Delay max</td>
                            <td class="px-5 py-2.5" id="dmax-hour">{{ $report['metrics']['hour']['delay_max_ms'] ?? '—' }} ms</td>
                            <td class="px-5 py-2.5" id="dmax-today">{{ $report['metrics']['today']['delay_max_ms'] ?? '—' }} ms</td>
                            <td class="px-5 py-2.5" id="dmax-all">{{ $report['metrics']['all']['delay_max_ms'] ?? '—' }} ms</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 bg-gray-50 px-5 py-2.5 text-xs text-gray-500">
                <span id="estNote">
                    @if ($report['metrics']['all']['pdr_estimated'])
                        ⚠ PDR estimasi (ESP32 belum mengirim total_sent). Update firmware ESP32 untuk akurasi penuh.
                    @else
                        PDR akurat berdasar counter total_sent dari ESP32.
                    @endif
                </span>
            </div>
        </section>

        <footer class="mt-10 text-center text-xs text-gray-400">
            IoT Plant Monitoring · Evaluasi · {{ now()->format('Y') }}
        </footer>
    </main>

    <script>
        const POLL_MS = 5000;

        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        const ms = (v) => v === null || v === undefined ? '— ms' : `${v} ms`;

        const fetchReport = async () => {
            try {
                const res = await fetch('/api/evaluation/metrics', {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const r = await res.json();

                // Status dashboard
                set('statusOnline', r.dashboard_status.online ? 'Ya' : 'Tidak');
                set('statusValue', r.dashboard_status.value_displayed ? 'Ya' : 'Tidak');
                set('latestAge', r.dashboard_status.latest_age_seconds ?? '—');
                set('genAt', 'Generated: ' + r.generated_at);

                // Metrik per window
                ['hour', 'today', 'all'].forEach(w => {
                    const m = r.metrics[w];
                    set(`sent-${w}`, m.sent);
                    set(`received-${w}`, m.received);
                    set(`pdr-${w}`, `${m.pdr}%`);
                    set(`lost-${w}`, m.lost);
                    set(`davg-${w}`, ms(m.delay_avg_ms));
                    set(`dmin-${w}`, ms(m.delay_min_ms));
                    set(`dmax-${w}`, ms(m.delay_max_ms));
                });

                // Catatan estimasi (ambil dari window all)
                const note = document.getElementById('estNote');
                if (note) {
                    note.textContent = r.metrics.all.pdr_estimated
                        ? '⚠ PDR estimasi (ESP32 belum mengirim total_sent). Update firmware ESP32 untuk akurasi penuh.'
                        : 'PDR akurat berdasar counter total_sent dari ESP32.';
                }
            } catch (err) {
                console.error('Evaluation polling error:', err);
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            fetchReport();
            setInterval(fetchReport, POLL_MS);
        });
    </script>
</body>
</html>
