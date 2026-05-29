<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->index();
            $table->unsignedBigInteger('sequence_no')->nullable()->index();

            $table->unsignedSmallInteger('soil_raw');
            $table->decimal('moisture_percent', 5, 2);
            $table->string('soil_condition', 30);

            $table->decimal('distance_cm', 6, 2)->nullable();
            $table->decimal('water_level_percent', 5, 2)->nullable();
            $table->decimal('water_volume_ml', 8, 2)->nullable();
            $table->string('water_status', 50);

            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->boolean('dht_ok')->default(false);

            $table->unsignedTinyInteger('soil_score');
            $table->unsignedTinyInteger('water_score');
            $table->unsignedTinyInteger('temp_score');
            $table->unsignedSmallInteger('ikp');
            $table->string('watering_status', 80);

            $table->timestamps();

            $table->index(['device_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
