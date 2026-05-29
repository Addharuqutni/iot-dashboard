<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSensorReadingRequest;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorDataController extends Controller
{
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

    public function latest(): JsonResponse
    {
        $reading = SensorReading::latest()->first();

        return response()->json([
            'data' => $reading,
        ]);
    }

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
