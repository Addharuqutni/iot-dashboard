# Dashboard Laravel IoT Monitoring Tanaman

Paket implementasi untuk menerima data ESP32 dan menampilkan dashboard monitoring tanaman.

## File yang disediakan

```txt
app/Models/SensorReading.php
app/Http/Requests/StoreSensorReadingRequest.php
app/Http/Controllers/SensorDataController.php
app/Http/Controllers/DashboardController.php
database/migrations/2026_05_29_000000_create_sensor_readings_table.php
resources/views/dashboard.blade.php
snippets/api.php
snippets/web.php
snippets/services.php.patch
snippets/env.example
```

## Instalasi ke project Laravel

Salin file sesuai folder masing-masing ke project Laravel.

Jika file route masih kosong, isi:

`routes/api.php`

```php
<?php

use App\Http\Controllers\SensorDataController;
use Illuminate\Support\Facades\Route;

Route::post('/sensor-data', [SensorDataController::class, 'store']);
Route::get('/sensor-readings/latest', [SensorDataController::class, 'latest']);
Route::get('/sensor-readings/history', [SensorDataController::class, 'history']);
```

`routes/web.php`

```php
<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index']);
```

Tambahkan konfigurasi ini ke `config/services.php` di dalam array `return`:

```php
'iot' => [
    'api_key' => env('IOT_API_KEY'),
],
```

Tambahkan ke `.env`:

```env
IOT_API_KEY=ganti_dengan_api_key_rahasia
```

Generate API key:

```bash
php artisan tinker
```

```php
Str::random(64)
```

Jalankan migration:

```bash
php artisan migrate
```

Jalankan server lokal:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Dashboard:

```txt
http://IP-LAPTOP:8000/dashboard
```

Endpoint ESP32:

```txt
http://IP-LAPTOP:8000/api/sensor-data
```

## Konfigurasi ESP32 lokal

Gunakan IP laptop/server, bukan `localhost`.

```cpp
const char* SERVER_URL = "http://192.168.1.10:8000/api/sensor-data";
const char* API_KEY = "isi_sama_dengan_IOT_API_KEY";
const bool USE_HTTPS = false;
```

## Payload yang diterima

```json
{
  "api_key": "secret",
  "device_id": "POT-001",
  "sequence_no": 1,
  "soil_raw": 3000,
  "moisture_percent": 50.0,
  "soil_condition": "Lembab",
  "distance_cm": 12.5,
  "water_level_percent": 62.5,
  "water_volume_ml": 937.5,
  "water_status": "Cukup",
  "temperature": 29.5,
  "humidity": 70.2,
  "dht_ok": true,
  "soil_score": 30,
  "water_score": 10,
  "temp_score": 10,
  "ikp": 50,
  "watering_status": "Perlu dipantau"
}
```

## Test API dengan curl

```bash
curl -X POST http://127.0.0.1:8000/api/sensor-data \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "api_key":"ganti_dengan_api_key_rahasia",
    "device_id":"POT-001",
    "sequence_no":1,
    "soil_raw":3000,
    "moisture_percent":50.00,
    "soil_condition":"Lembab",
    "distance_cm":12.50,
    "water_level_percent":62.50,
    "water_volume_ml":937.50,
    "water_status":"Cukup",
    "temperature":29.50,
    "humidity":70.20,
    "dht_ok":true,
    "soil_score":30,
    "water_score":10,
    "temp_score":10,
    "ikp":50,
    "watering_status":"Perlu dipantau"
  }'
```
