<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah kolom untuk fitur evaluasi sistem IoT.
     *
     * Kolom baru:
     * - sent_at: timestamp waktu kirim dari ESP32 (NTP).
     * - device_total_sent: counter total request kumulatif dari ESP32.
     * - delay_ms: selisih received_at - sent_at dalam ms (dihitung server).
     */
    public function up(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('sequence_no')->index();
            $table->unsignedBigInteger('device_total_sent')->nullable()->after('sent_at');
            $table->integer('delay_ms')->nullable()->after('device_total_sent');
        });
    }

    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->dropIndex(['sent_at']);
            $table->dropColumn(['sent_at', 'device_total_sent', 'delay_ms']);
        });
    }
};
