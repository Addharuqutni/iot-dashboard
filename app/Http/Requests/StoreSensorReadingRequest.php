<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSensorReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hash_equals((string) config('services.iot.api_key'), (string) $this->input('api_key'));
    }

    public function rules(): array
    {
        return [
            'api_key' => ['required', 'string'],
            'device_id' => ['required', 'string', 'max:80'],
            'sequence_no' => ['nullable', 'integer', 'min:0'],

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

    protected function failedAuthorization(): void
    {
        abort(response()->json(['message' => 'Unauthorized. API key tidak valid.'], 401));
    }
}
