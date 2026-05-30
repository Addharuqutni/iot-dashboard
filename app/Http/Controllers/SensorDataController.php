<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSensorReadingRequest;
use App\Models\SensorReading;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorDataController extends Controller
{
    /**
     * Menerima data sensor dari ESP32 dan menyimpannya ke database.
     *
     * Function ini memakai StoreSensorReadingRequest untuk validasi payload
     * dan API key, lalu menghapus api_key sebelum data disimpan. Jika ESP32
     * mengirim sent_at, server menghitung delay_ms = now - sent_at.
     */
    public function store(StoreSensorReadingRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['api_key']);

        // Timezone yang dipakai ESP32 (firmware kirim format "Y-m-d H:i:s"
        // tanpa offset, sesuai GMT_OFFSET_SEC = 7*3600 = WIB).
        $deviceTimezone = config('services.iot.device_timezone', 'Asia/Jakarta');

        // Alias firmware lama: device_timestamp -> sent_at.
        if (empty($data['sent_at']) && ! empty($data['device_timestamp'])) {
            $data['sent_at'] = $data['device_timestamp'];
        }
        unset($data['device_timestamp']);

        // Alias: total_sent fallback ke sequence_no jika belum dikirim ESP32.
        if (! array_key_exists('total_sent', $data) && array_key_exists('sequence_no', $data)) {
            $data['total_sent'] = $data['sequence_no'];
        }

        if (array_key_exists('total_sent', $data)) {
            $data['device_total_sent'] = $data['total_sent'];
            unset($data['total_sent']);
        }

        // device_epoch tidak disimpan (redundant dengan sent_at).
        unset($data['device_epoch']);

        // Hitung delay pengiriman packet jika ESP32 mengirim sent_at.
        // Parse dengan timezone device, bukan timezone aplikasi, supaya
        // delay tidak meleset karena offset zona waktu.
        if (! empty($data['sent_at'])) {
            $sentAt = Carbon::parse($data['sent_at'], $deviceTimezone)->utc();
            $data['sent_at'] = $sentAt;

            $delayMs = now()->diffInMilliseconds($sentAt, false);
            // Negatif berarti clock device lebih maju dari server; clamp ke 0.
            $data['delay_ms'] = max(0, (int) abs($delayMs));
        }

        $reading = SensorReading::create($data);

        return response()->json([
            'message' => 'Data sensor berhasil disimpan.',
            'id' => $reading->id,
            'received_at' => $reading->created_at?->toISOString(),
            'delay_ms' => $reading->delay_ms,
        ], 201);
    }

    /**
     * Mengambil satu data sensor paling baru.
     *
     * Function ini digunakan oleh endpoint API untuk menampilkan kondisi
     * sensor terakhir yang tersimpan di database.
     */
    public function latest(): JsonResponse
    {
        $reading = SensorReading::latest()->first();

        return response()->json([
            'data' => $reading,
        ]);
    }

    /**
     * Mengambil riwayat data sensor dengan batas jumlah data.
     *
     * Function ini membaca query parameter limit, membatasi nilainya dari
     * 1 sampai 500, lalu mengembalikan data terbaru sesuai limit tersebut.
     */
    public function history(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 50), 1), 500);

        $readings = SensorReading::query()
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $readings,
        ]);
    }
}
