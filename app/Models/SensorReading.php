<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'sequence_no',
        'sent_at',
        'device_total_sent',
        'delay_ms',
        'soil_raw',
        'moisture_percent',
        'soil_condition',
        'distance_cm',
        'water_level_percent',
        'water_volume_ml',
        'water_status',
        'temperature',
        'humidity',
        'dht_ok',
        'soil_score',
        'water_score',
        'temp_score',
        'ikp',
        'watering_status',
    ];

    protected $casts = [
        'sequence_no' => 'integer',
        'sent_at' => 'datetime',
        'device_total_sent' => 'integer',
        'delay_ms' => 'integer',
        'soil_raw' => 'integer',
        'moisture_percent' => 'float',
        'distance_cm' => 'float',
        'water_level_percent' => 'float',
        'water_volume_ml' => 'float',
        'temperature' => 'float',
        'humidity' => 'float',
        'dht_ok' => 'boolean',
        'soil_score' => 'integer',
        'water_score' => 'integer',
        'temp_score' => 'integer',
        'ikp' => 'integer',
    ];
}
