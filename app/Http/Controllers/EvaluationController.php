<?php

namespace App\Http\Controllers;

use App\Services\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            // Halaman /evaluation tidak reset saat device disconnected.
            'report' => $this->service->fullReport(resetOnDisconnect: false),
        ]);
    }

    /**
     * Mengembalikan laporan evaluasi sebagai JSON untuk polling frontend.
     */
    public function metrics(Request $request): JsonResponse
    {
        $resetOnDisconnect = $request->boolean('reset_on_disconnect', true);

        return response()->json($this->service->fullReport($resetOnDisconnect));
    }
}
