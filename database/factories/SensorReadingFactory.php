<?php

namespace Database\Factories;

use App\Models\SensorReading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SensorReading>
 */
class SensorReadingFactory extends Factory
{
    protected $model = SensorReading::class;

    public function definition(): array
    {
        return [
            'device_id' => 'esp32-001',
            'sequence_no' => 1,
            'sent_at' => now(),
            'device_total_sent' => 1,
            'delay_ms' => 100,
            'soil_raw' => 2000,
            'moisture_percent' => 51.5,
            'soil_condition' => 'Lembab',
            'distance_cm' => 10.5,
            'water_level_percent' => 75.0,
            'water_volume_ml' => 1200.0,
            'water_status' => 'Cukup',
            'temperature' => 28.5,
            'humidity' => 70.0,
            'dht_ok' => true,
            'soil_score' => 80,
            'water_score' => 75,
            'temp_score' => 85,
            'ikp' => 240,
            'watering_status' => 'Aman',
        ];
    }
}
