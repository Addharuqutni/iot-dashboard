<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSensorReadingRequest;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorDataController extends Controller
{
    /**
     * Menerima data sensor dari ESP32 dan menyimpannya ke database.
     *
     * Function ini memakai StoreSensorReadingRequest untuk validasi payload
     * dan API key, lalu menghapus api_key sebelum data disimpan.
     */
    public function store(StoreSensorReadingRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['api_key']);

        $reading = SensorReading::create($data);

        return response()->json([
            'message' => 'Data sensor berhasil disimpan.',
            'id' => $reading->id,
            'received_at' => $reading->created_at?->toISOString(),
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
