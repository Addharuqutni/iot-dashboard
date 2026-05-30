<?php

namespace App\Services;

use App\Models\SensorReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EvaluationService
{
    /**
     * Threshold dalam detik untuk menentukan apakah dashboard masih
     * menampilkan data segar (value tampil = true).
     */
    public const VALUE_FRESH_THRESHOLD_SECONDS = 25;

    /**
     * Mengambil rentang waktu (from, to) untuk window evaluasi.
     *
     * Window yang didukung:
     * - 'recent': 15 menit terakhir
     * - 'today' : sejak 00:00 hari ini sampai sekarang
     * - 'all'   : sejak data pertama sampai sekarang
     */
    public function windowRange(string $window): array
    {
        $now = Carbon::now();

        return match ($window) {
            'recent' => [
                'from' => $now->copy()->subMinutes(15),
                'to' => $now,
                'label' => '15 Menit Terakhir',
            ],
            'today' => [
                'from' => $now->copy()->startOfDay(),
                'to' => $now,
                'label' => 'Hari Ini',
            ],
            default => [
                'from' => null,
                'to' => $now,
                'label' => 'All-time',
            ],
        };
    }

    /**
     * Mengambil awal sesi aktif terakhir.
     *
     * Jika data terakhir lebih tua dari threshold, device dianggap disconnected
     * dan evaluasi reset ke 0. Jika device reconnect, sesi baru dimulai dari
     * record pertama setelah gap > threshold.
     */
    public function activeSessionStart(): ?Carbon
    {
        $latest = SensorReading::latest()->first();
        if (! $latest || ! $latest->created_at) {
            return null;
        }

        $latestAge = abs(now()->diffInSeconds($latest->created_at, false));
        if ($latestAge > self::VALUE_FRESH_THRESHOLD_SECONDS) {
            return null;
        }

        $readings = SensorReading::query()
            ->latest()
            ->limit(1000)
            ->get(['id', 'created_at']);

        $previousNewer = null;
        foreach ($readings as $reading) {
            if ($previousNewer) {
                $gap = abs($previousNewer->created_at->diffInSeconds($reading->created_at, false));
                if ($gap > self::VALUE_FRESH_THRESHOLD_SECONDS) {
                    return $previousNewer->created_at;
                }
            }

            $previousNewer = $reading;
        }

        return $readings->last()?->created_at;
    }

    /**
     * Metrik kosong saat device disconnected / belum ada data.
     */
    public function zeroMetrics(string $window): array
    {
        $range = $this->windowRange($window);

        return [
            'window' => [
                'key' => $window,
                'label' => $range['label'],
                'from' => $range['from']?->toIso8601String(),
                'to' => $range['to']->toIso8601String(),
            ],
            'sent' => 0,
            'received' => 0,
            'lost' => 0,
            'pdr' => 0.0,
            'pdr_estimated' => false,
            'delay_avg_ms' => null,
            'delay_min_ms' => null,
            'delay_max_ms' => null,
            'delay_samples' => 0,
            'reset' => true,
        ];
    }

    /**
     * Menghitung metrik evaluasi (sent, received, PDR, delay) untuk satu window.
     *
     * Evaluasi selalu dibatasi sesi aktif terakhir. Saat device disconnected,
     * metrics otomatis reset ke 0.
     */
    public function metrics(string $window, ?Carbon $activeSessionStart = null, bool $resetOnDisconnect = true): array
    {
        $range = $this->windowRange($window);

        if ($resetOnDisconnect) {
            $activeSessionStart ??= $this->activeSessionStart();

            if (! $activeSessionStart) {
                return $this->zeroMetrics($window);
            }
        }

        $from = $range['from'];
        if ($resetOnDisconnect && $activeSessionStart && (! $from || $activeSessionStart->gt($from))) {
            $from = $activeSessionStart;
        }

        $query = SensorReading::query();
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        $query->where('created_at', '<=', $range['to']);

        $received = (clone $query)->count();

        // Sent = sum atas device dari (max - min + 1) device_total_sent.
        $sentRows = (clone $query)
            ->whereNotNull('device_total_sent')
            ->select('device_id', DB::raw('MIN(device_total_sent) AS min_total'), DB::raw('MAX(device_total_sent) AS max_total'))
            ->groupBy('device_id')
            ->get();

        $sent = 0;
        foreach ($sentRows as $row) {
            $sent += ((int) $row->max_total) - ((int) $row->min_total) + 1;
        }

        $isEstimated = $sent === 0;
        if ($isEstimated) {
            // Tidak ada device_total_sent valid → estimasi sent = received.
            $sent = $received;
        }

        $pdr = $sent > 0 ? round(($received / $sent) * 100, 2) : 0.0;
        if ($pdr > 100) {
            $pdr = 100.0;
        }

        $delay = (clone $query)
            ->whereNotNull('delay_ms')
            ->selectRaw('AVG(delay_ms) AS avg_ms, MIN(delay_ms) AS min_ms, MAX(delay_ms) AS max_ms, COUNT(*) AS n')
            ->first();

        $delayAvg = $delay && $delay->n > 0 ? round((float) $delay->avg_ms, 2) : null;
        $delayMin = $delay && $delay->n > 0 ? (int) $delay->min_ms : null;
        $delayMax = $delay && $delay->n > 0 ? (int) $delay->max_ms : null;
        $delaySamples = $delay ? (int) $delay->n : 0;

        return [
            'window' => [
                'key' => $window,
                'label' => $range['label'],
                'from' => $from?->toIso8601String(),
                'to' => $range['to']->toIso8601String(),
            ],
            'sent' => $sent,
            'received' => $received,
            'lost' => max(0, $sent - $received),
            'pdr' => $pdr,
            'pdr_estimated' => $isEstimated,
            'delay_avg_ms' => $delayAvg,
            'delay_min_ms' => $delayMin,
            'delay_max_ms' => $delayMax,
            'delay_samples' => $delaySamples,
            'reset' => false,
        ];
    }

    /**
     * Mengambil status dashboard online dan apakah value sensor saat ini tampil.
     *
     * online: jika request sampai sini berarti server hidup.
     * value_displayed: true jika ada reading dengan umur <= threshold detik.
     */
    public function dashboardStatus(): array
    {
        $latest = SensorReading::latest()->first();

        $latestAge = null;
        $valueDisplayed = false;
        if ($latest && $latest->created_at) {
            $latestAge = (int) abs(now()->diffInSeconds($latest->created_at, false));
            $valueDisplayed = $latestAge <= self::VALUE_FRESH_THRESHOLD_SECONDS;
        }

        return [
            'online' => true,
            'url' => config('app.url'),
            'value_displayed' => $valueDisplayed,
            'latest_age_seconds' => $latestAge,
            'latest_at' => $latest?->created_at?->toIso8601String(),
            'threshold_seconds' => self::VALUE_FRESH_THRESHOLD_SECONDS,
        ];
    }

    /**
     * Mengambil semua metrik evaluasi (3 window) dan status dashboard.
     */
    public function fullReport(bool $resetOnDisconnect = true): array
    {
        $activeSessionStart = $resetOnDisconnect ? $this->activeSessionStart() : null;

        return [
            'metrics' => [
                'recent' => $this->metrics('recent', $activeSessionStart, $resetOnDisconnect),
                'today' => $this->metrics('today', $activeSessionStart, $resetOnDisconnect),
                'all' => $this->metrics('all', $activeSessionStart, $resetOnDisconnect),
            ],
            'dashboard_status' => $this->dashboardStatus(),
            'active_session_start' => $activeSessionStart?->toIso8601String(),
            'reset_on_disconnect' => $resetOnDisconnect,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
