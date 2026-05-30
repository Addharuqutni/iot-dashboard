<?php

namespace App\Http\Controllers;

use App\Services\EvaluationService;
use Illuminate\Http\JsonResponse;

class EvaluationController extends Controller
{
    public function __construct(private readonly EvaluationService $service)
    {
    }

    /**
     * Menampilkan halaman evaluasi sistem IoT (PDR, delay, status dashboard).
     *
     * Function ini me-render view evaluation dengan data awal hasil
     * fullReport() agar halaman tampil cepat tanpa menunggu polling.
     */
    public function index()
    {
        return view('evaluation', [
            'report' => $this->service->fullReport(),
        ]);
    }

    /**
     * Mengembalikan laporan evaluasi sebagai JSON untuk polling frontend.
     */
    public function metrics(): JsonResponse
    {
        return response()->json($this->service->fullReport());
    }
}
