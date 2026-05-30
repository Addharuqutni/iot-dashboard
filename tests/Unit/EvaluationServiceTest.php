<?php

namespace Tests\Unit;

use App\Models\SensorReading;
use App\Services\EvaluationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_metrics_reset_when_latest_reading_is_stale(): void
    {
        Carbon::setTestNow('2026-05-30 10:01:00');
        SensorReading::factory()->create([
            'created_at' => Carbon::parse('2026-05-30 10:00:30'),
            'updated_at' => Carbon::parse('2026-05-30 10:00:30'),
        ]);

        $metrics = app(EvaluationService::class)->metrics('today');

        $this->assertTrue($metrics['reset']);
        $this->assertSame(0, $metrics['sent']);
        $this->assertSame(0, $metrics['received']);
        $this->assertSame(0.0, $metrics['pdr']);
    }

    public function test_metrics_can_skip_disconnect_reset(): void
    {
        Carbon::setTestNow('2026-05-30 10:01:00');
        SensorReading::factory()->create([
            'created_at' => Carbon::parse('2026-05-30 10:00:30'),
            'updated_at' => Carbon::parse('2026-05-30 10:00:30'),
        ]);

        $metrics = app(EvaluationService::class)->metrics('today', resetOnDisconnect: false);

        $this->assertFalse($metrics['reset']);
        $this->assertSame(1, $metrics['sent']);
        $this->assertSame(1, $metrics['received']);
        $this->assertSame(100.0, $metrics['pdr']);
    }

    public function test_metrics_with_partial_counter_uses_only_counter_rows_for_pdr(): void
    {
        Carbon::setTestNow('2026-05-30 10:00:10');
        SensorReading::factory()->create([
            'device_total_sent' => 10,
            'created_at' => Carbon::parse('2026-05-30 10:00:00'),
            'updated_at' => Carbon::parse('2026-05-30 10:00:00'),
        ]);
        SensorReading::factory()->create([
            'device_total_sent' => null,
            'created_at' => Carbon::parse('2026-05-30 10:00:01'),
            'updated_at' => Carbon::parse('2026-05-30 10:00:01'),
        ]);

        $metrics = app(EvaluationService::class)->metrics('today');

        $this->assertSame(1, $metrics['sent']);
        $this->assertSame(1, $metrics['received']);
        $this->assertTrue($metrics['pdr_estimated']);
        $this->assertSame(100.0, $metrics['pdr']);
    }

    public function test_metrics_handles_device_counter_reset(): void
    {
        Carbon::setTestNow('2026-05-30 10:00:10');
        SensorReading::factory()->create([
            'device_total_sent' => 5,
            'created_at' => Carbon::parse('2026-05-30 10:00:00'),
            'updated_at' => Carbon::parse('2026-05-30 10:00:00'),
        ]);
        SensorReading::factory()->create([
            'device_total_sent' => 6,
            'created_at' => Carbon::parse('2026-05-30 10:00:01'),
            'updated_at' => Carbon::parse('2026-05-30 10:00:01'),
        ]);
        SensorReading::factory()->create([
            'device_total_sent' => 1,
            'created_at' => Carbon::parse('2026-05-30 10:00:02'),
            'updated_at' => Carbon::parse('2026-05-30 10:00:02'),
        ]);

        $metrics = app(EvaluationService::class)->metrics('today');

        $this->assertSame(3, $metrics['sent']);
        $this->assertSame(3, $metrics['received']);
        $this->assertSame(100.0, $metrics['pdr']);
    }
}
