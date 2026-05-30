<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSensorReadingRequest extends FormRequest
{
    /**
     * Memeriksa izin request berdasarkan API key dari ESP32.
     *
     * Function ini membandingkan api_key pada payload dengan IOT_API_KEY
     * yang disimpan di konfigurasi Laravel.
     */
    public function authorize(): bool
    {
        return hash_equals((string) config('services.iot.api_key'), (string) $this->input('api_key'));
    }

    /**
     * Menentukan aturan validasi untuk payload sensor.
     *
     * Function ini memastikan semua field dari ESP32 memiliki tipe data,
     * rentang nilai, dan pilihan status yang sesuai sebelum disimpan.
     */
    public function rules(): array
    {
        return [
            'api_key' => ['required', 'string'],
            'device_id' => ['required', 'string', 'max:80'],
            'sequence_no' => ['nullable', 'integer', 'min:0'],

            // Field evaluasi: timestamp kirim dari ESP32 (ISO8601 / "Y-m-d H:i:s")
            // dan counter total request kumulatif. Dipakai untuk hitung PDR & delay.
            // Alias diterima untuk kompatibilitas firmware lama:
            //   device_timestamp -> sent_at
            //   sequence_no      -> total_sent (fallback)
            'sent_at' => ['nullable', 'date'],
            'device_timestamp' => ['nullable', 'date'],
            'total_sent' => ['nullable', 'integer', 'min:0'],
            'device_epoch' => ['nullable', 'integer', 'min:0'],

            'soil_raw' => ['required', 'integer', 'min:0', 'max:4095'],
            'moisture_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'soil_condition' => ['required', 'string', Rule::in(['Kering', 'Lembab', 'Basah'])],

            'distance_cm' => ['nullable', 'numeric', 'min:0', 'max:400'],
            'water_level_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'water_volume_ml' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'water_status' => ['required', 'string', 'max:50'],

            'temperature' => ['nullable', 'numeric', 'min:-40', 'max:80'],
            'humidity' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'dht_ok' => ['required', 'boolean'],

            'soil_score' => ['required', 'integer', 'min:0', 'max:100'],
            'water_score' => ['required', 'integer', 'min:0', 'max:100'],
            'temp_score' => ['required', 'integer', 'min:0', 'max:100'],
            'ikp' => ['required', 'integer', 'min:0', 'max:300'],
            'watering_status' => ['required', 'string', 'max:80'],
        ];
    }

    /**
     * Mengembalikan response JSON saat API key tidak valid.
     *
     * Function ini mengganti response authorization default Laravel agar
     * ESP32 menerima pesan error JSON yang mudah dibaca di Serial Monitor.
     */
    protected function failedAuthorization(): void
    {
        abort(response()->json(['message' => 'Unauthorized. API key tidak valid.'], 401));
    }
}
