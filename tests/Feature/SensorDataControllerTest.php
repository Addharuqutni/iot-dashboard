<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensorDataControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_store_rejects_invalid_api_key(): void
    {
        config(['services.iot.api_key' => 'valid-key']);

        $this->postJson('/api/sensor-data', $this->payload(['api_key' => 'wrong-key']))
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthorized. API key tidak valid.']);
    }

    public function test_store_saves_sensor_payload_with_valid_api_key(): void
    {
        config(['services.iot.api_key' => 'valid-key']);
        Carbon::setTestNow('2026-05-30 10:00:05');

        $this->postJson('/api/sensor-data', $this->payload([
            'api_key' => 'valid-key',
            'sent_at' => '2026-05-30 17:00:00',
            'total_sent' => 10,
        ]))
            ->assertCreated()
            ->assertJsonPath('delay_ms', 5000);

        $this->assertDatabaseHas('sensor_readings', [
            'device_id' => 'esp32-001',
            'device_total_sent' => 10,
            'delay_ms' => 5000,
        ]);
    }

    public function test_future_sent_at_clamps_delay_to_zero(): void
    {
        config(['services.iot.api_key' => 'valid-key']);
        Carbon::setTestNow('2026-05-30 10:00:00');

        $this->postJson('/api/sensor-data', $this->payload([
            'api_key' => 'valid-key',
            'sent_at' => '2026-05-30 17:00:10',
        ]))
            ->assertCreated()
            ->assertJsonPath('delay_ms', 0);

        $this->assertSame(0, SensorReading::first()->delay_ms);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'api_key' => 'valid-key',
            'device_id' => 'esp32-001',
            'sequence_no' => 1,
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
        ], $overrides);
    }
}
