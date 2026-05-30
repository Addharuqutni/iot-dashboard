# Dokumentasi Project — IoT Dashboard Monitoring Tanaman

Dokumentasi ini menjelaskan project Laravel untuk menerima data sensor dari ESP32, menyimpan data ke database, menampilkan kondisi tanaman pada dashboard real-time, dan mengevaluasi kualitas pengiriman (PDR + delay).

---

## 1. Gambaran Umum

Project ini adalah aplikasi monitoring tanaman berbasis Laravel. Sistem membaca data dari ESP32 (sensor kelembapan tanah, sensor jarak air, DHT22). ESP32 mengirim data ke Laravel via REST API; Laravel memvalidasi, menghitung delay, menyimpan, lalu menampilkan pada dashboard. Tersedia juga halaman evaluasi yang menghitung Packet Delivery Ratio (PDR) dan delay rata-rata per window waktu.

### Tujuan Sistem

- Menerima data sensor dari ESP32.
- Memvalidasi API key dan payload sebelum data masuk database.
- Menyimpan riwayat pembacaan sensor.
- Menampilkan kondisi terbaru tanaman.
- Menampilkan grafik perubahan sensor.
- Memberikan status penyiraman lewat nilai IKP.
- Mengukur kualitas pengiriman (PDR, delay) per window 15 menit / hari ini / all-time.
- Mendeteksi disconnect device dan mereset metrik saat sesi baru dimulai.

### Teknologi Utama

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 10 |
| Bahasa | PHP 8.1+ |
| Database | MySQL (production), SQLite in-memory (testing) |
| Frontend | Blade, TailwindCSS CDN, Chart.js CDN |
| API | Laravel API routes |
| Perangkat | ESP32 |
| Sensor | Soil Moisture, HC-SR04, DHT22 |

---

## 2. Arsitektur Sistem

```txt
ESP32
  ├─ Baca sensor tanah / air / suhu / humidity
  ├─ Hitung skor dan IKP
  ├─ Sinkron NTP (WIB / GMT+7)
  ├─ Tambah counter total_sent
  └─ Kirim JSON ke API Laravel (sent_at + total_sent)

Laravel API
  ├─ Validasi API key
  ├─ Validasi payload
  ├─ Hitung delay_ms = now (UTC) - sent_at (parse pakai timezone device)
  ├─ Simpan ke sensor_readings
  └─ Endpoint pembacaan data + endpoint metrik evaluasi

Database
  └─ Tabel sensor_readings (data sensor + kolom evaluasi)

Dashboard Web ( / )
  ├─ KPI card, detail terbaru, tabel riwayat, chart
  └─ Polling /api/sensor-readings/history tiap 5 detik

Halaman Evaluasi ( /evaluation )
  ├─ PDR & delay per window (recent / today / all)
  ├─ Status dashboard (online, value tampil, threshold stale)
  └─ Polling /api/evaluation/metrics
```

### Alur Data Ringkas

```txt
Sensor fisik -> ESP32 -> POST /api/sensor-data
  -> StoreSensorReadingRequest (auth + validasi)
  -> SensorDataController@store (alias firmware lama, hitung delay_ms)
  -> SensorReading::create()
  -> tabel sensor_readings
  -> DashboardController@index    -> resources/views/dashboard.blade.php
  -> EvaluationController         -> EvaluationService -> resources/views/evaluation.blade.php
```

---

## 3. Struktur Folder Penting

| Path | Fungsi |
|---|---|
| `routes/api.php` | Endpoint API: sensor-data, sensor-readings, evaluation/metrics |
| `routes/web.php` | Route halaman: `/` (dashboard) dan `/evaluation` |
| `app/Http/Controllers/SensorDataController.php` | Penerimaan + pembacaan data sensor, hitung delay_ms |
| `app/Http/Controllers/DashboardController.php` | Data awal dashboard |
| `app/Http/Controllers/EvaluationController.php` | View evaluasi + endpoint metrics JSON |
| `app/Http/Requests/StoreSensorReadingRequest.php` | Authorize API key + validasi payload (termasuk field evaluasi) |
| `app/Models/SensorReading.php` | Eloquent model `sensor_readings` |
| `app/Services/EvaluationService.php` | Hitung PDR, delay, sesi aktif, status dashboard |
| `database/migrations/2026_05_29_000000_create_sensor_readings_table.php` | Tabel utama |
| `database/migrations/2026_05_30_000000_add_evaluation_columns_to_sensor_readings.php` | Tambah `sent_at`, `device_total_sent`, `delay_ms` |
| `database/factories/SensorReadingFactory.php` | Factory untuk testing |
| `resources/views/dashboard.blade.php` | UI dashboard, chart, polling |
| `resources/views/evaluation.blade.php` | UI evaluasi PDR/delay/status |
| `config/services.php` | `iot.api_key` + `iot.device_timezone` |
| `tests/Feature/SensorDataControllerTest.php` | Feature test endpoint POST sensor |
| `tests/Unit/EvaluationServiceTest.php` | Unit test service evaluasi |
| `phpunit.xml` | Konfigurasi PHPUnit (SQLite in-memory) |
| `.env.example` | Template environment |

---

## 4. Modul dan Fitur

## 4.1 Modul API Sensor

Modul ini menerima data dari ESP32.

### Endpoint

```txt
POST /api/sensor-data
```

### File Terkait

- `routes/api.php`
- `app/Http/Controllers/SensorDataController.php`
- `app/Http/Requests/StoreSensorReadingRequest.php`
- `app/Models/SensorReading.php`

### Alur Logic

1. ESP32 mengirim JSON ke `/api/sensor-data`.
2. `StoreSensorReadingRequest` memeriksa `api_key` (`hash_equals`).
3. Request memvalidasi semua field (data sensor + field evaluasi opsional).
4. Controller mengambil data lewat `$request->validated()`.
5. `api_key` dihapus agar tidak disimpan.
6. Alias firmware lama dikonversi:
   - `device_timestamp` → `sent_at`.
   - `total_sent` → `device_total_sent`.
   - Jika `total_sent` belum dikirim, fallback ke `sequence_no`.
   - `device_epoch` dibuang (redundant dengan `sent_at`).
7. `sent_at` di-parse pakai timezone device (`config('services.iot.device_timezone')`, default `Asia/Jakarta`) lalu dikonversi ke UTC.
8. `delay_ms = max(0, now() - sent_at)` dalam milidetik. Negatif (clock device lebih maju) di-clamp ke 0.
9. `SensorReading::create($data)`.
10. Response JSON status `201` berisi `id`, `received_at`, `delay_ms`.

### Response Berhasil

```json
{
  "message": "Data sensor berhasil disimpan.",
  "id": 1,
  "received_at": "2026-05-30T10:00:00.000000Z",
  "delay_ms": 5000
}
```

### Response API Key Salah

```json
{
  "message": "Unauthorized. API key tidak valid."
}
```

Status HTTP: `401`.

---

## 4.2 Modul API Pembacaan Data Terbaru

```txt
GET /api/sensor-readings/latest
```

- `SensorReading::query()->latest()->latest('id')->first()`.
- `latest('id')` jadi tie-breaker saat banyak baris share `created_at` yang sama.
- Jika kosong: `data: null`.

---

## 4.3 Modul API Riwayat Sensor

```txt
GET /api/sensor-readings/history
GET /api/sensor-readings/history?limit=50
```

- `limit` diclamp ke 1..500 (default 50).
- Urutan terbaru → terlama (`latest()->latest('id')`).

| Input `limit` | Hasil |
|---:|---:|
| kosong | 50 |
| 0 atau negatif | 1 |
| 1-500 | sesuai input |
| > 500 | 500 |

---

## 4.4 Modul API Evaluasi

```txt
GET /api/evaluation/metrics
GET /api/evaluation/metrics?reset_on_disconnect=0
```

Mengembalikan PDR, delay, dan status dashboard untuk 3 window. Logic dijelaskan di [Section 12](#12-modul-evaluasi-pdr--delay).

Response shape:

```json
{
  "metrics": {
    "recent": { "...": "metrik 15 menit terakhir" },
    "today":  { "...": "metrik sejak 00:00 hari ini" },
    "all":    { "...": "metrik all-time" }
  },
  "dashboard_status": { "online": true, "value_displayed": true, "latest_age_seconds": 4, "threshold_seconds": 25, "..." : "..." },
  "active_session_start": "2026-05-30T09:55:30+00:00",
  "reset_on_disconnect": true,
  "generated_at": "2026-05-30T10:00:05+00:00"
}
```

---

## 4.5 Modul Dashboard Web

### Route

```txt
GET /
```

Nama route: `dashboard`.

### File Terkait

- `routes/web.php`
- `app/Http/Controllers/DashboardController.php`
- `resources/views/dashboard.blade.php`

### Data yang Disiapkan Controller

| Variable | Isi |
|---|---|
| `$latest` | Data sensor terbaru (`SensorReading::latest()->first()`) |
| `$readings` | 50 data terbaru |
| `$chartLabels` | Label waktu untuk grafik (`H:i:s`) |
| `$moistureData` | Data kelembapan tanah |
| `$waterData` | Data level air |
| `$temperatureData` | Data suhu |
| `$humidityData` | Data humidity |
| `$ikpData` | Data IKP |

`$readings` dibalik urutannya untuk chart agar grafik bergerak dari data lama ke terbaru.

---

## 4.6 Modul Halaman Evaluasi

### Route

```txt
GET /evaluation
```

Nama route: `evaluation`.

### File Terkait

- `routes/web.php`
- `app/Http/Controllers/EvaluationController.php`
- `app/Services/EvaluationService.php`
- `resources/views/evaluation.blade.php`

### Logic Controller

- `index()`: render view `evaluation` dengan `EvaluationService::fullReport(resetOnDisconnect: false)`. Halaman tidak reset ke 0 saat device disconnected → user tetap melihat angka historis.
- `metrics()`: endpoint JSON `/api/evaluation/metrics`. Default `reset_on_disconnect=true`. Frontend halaman evaluasi memanggil endpoint ini untuk polling.

### Tampilan

- 3 kartu status: Dashboard Online, Value Tampil, Threshold Stale.
- Tabel metrik per window (15 menit / hari ini / all-time): Sent, Received, Lost, PDR, delay min/avg/max, samples.
- Auto-refresh tiap 5 detik.

---

## 4.7 Modul Tampilan KPI Card

| Kartu | Data | Keterangan |
|---|---|---|
| Kelembapan Tanah | `moisture_percent`, `soil_condition` | Persentase + status Kering/Lembab/Basah |
| Cadangan Air | `water_level_percent`, `water_status` | Persen + status |
| Suhu Udara | `temperature`, `dht_ok` | Suhu + status DHT22 |
| IKP | `ikp`, `watering_status` | Indeks + keputusan penyiraman |

### Empty State

```txt
Belum ada data sensor
Kirim data ESP32 ke /api/sensor-data
```

---

## 4.8 Modul Grafik Sensor (Chart.js)

| Dataset | Field Database | Warna |
|---|---|---|
| Moisture % | `moisture_percent` | Hijau |
| Water % | `water_level_percent` | Biru |
| Temperature °C | `temperature` | Kuning |
| Humidity % | `humidity` | Ungu |
| IKP | `ikp` | Merah |

Initial render pakai data dari controller; setelah itu polling API history. Data API dibalik agar sumbu X bergerak lama → terbaru.

---

## 4.9 Modul Detail Terbaru

| Label | Field |
|---|---|
| Device | `device_id` |
| Sequence | `sequence_no` |
| Soil Raw | `soil_raw` |
| Jarak Air | `distance_cm` |
| Volume Air | `water_volume_ml` |
| Humidity | `humidity` |
| Skor T/A/S | `soil_score`, `water_score`, `temp_score` |

---

## 4.10 Modul Tabel Riwayat

Menampilkan 20 baris pertama dari hasil polling.

| Kolom | Field |
|---|---|
| Waktu | `created_at` |
| Tanah | `moisture_percent`, `soil_condition` |
| Air | `water_level_percent` |
| Suhu | `temperature` |
| Humidity | `humidity` |
| IKP | `ikp` |
| Status | `watering_status` |

Teks dari API diamankan pakai `escapeHtml()` sebelum masuk DOM.

---

## 4.11 Modul Auto-Refresh / Polling

```js
const POLL_INTERVAL_MS = 5000;
const HISTORY_LIMIT = 50;
const HISTORY_ROWS = 20;
```

| Status | Kondisi |
|---|---|
| Connecting... | Halaman baru dibuka |
| Live | API berhasil diakses |
| Disconnected | API gagal / error |

---

## 5. Database

## 5.1 Tabel `sensor_readings`

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---:|---|
| `id` | bigint | Tidak | Primary key |
| `device_id` | string | Tidak | ID perangkat ESP32 |
| `sequence_no` | unsignedBigInteger | Ya | Nomor urut data dari ESP32 |
| `sent_at` | timestamp | Ya | **Waktu kirim ESP32 (NTP), disimpan UTC, indexed** |
| `device_total_sent` | unsignedBigInteger | Ya | **Counter kumulatif total request dari ESP32** |
| `delay_ms` | integer | Ya | **`now() - sent_at` saat diterima server, ms (clamp ≥ 0)** |
| `soil_raw` | unsignedSmallInteger | Tidak | ADC sensor tanah |
| `moisture_percent` | decimal(5,2) | Tidak | Kelembapan tanah |
| `soil_condition` | string(30) | Tidak | Status tanah |
| `distance_cm` | decimal(6,2) | Ya | Jarak permukaan air |
| `water_level_percent` | decimal(5,2) | Ya | Persen level air |
| `water_volume_ml` | decimal(8,2) | Ya | Estimasi volume air |
| `water_status` | string(50) | Tidak | Status cadangan air |
| `temperature` | decimal(5,2) | Ya | Suhu udara |
| `humidity` | decimal(5,2) | Ya | Kelembapan udara |
| `dht_ok` | boolean | Tidak | Status sensor DHT22 |
| `soil_score` | unsignedTinyInteger | Tidak | Skor tanah |
| `water_score` | unsignedTinyInteger | Tidak | Skor air |
| `temp_score` | unsignedTinyInteger | Tidak | Skor suhu |
| `ikp` | unsignedSmallInteger | Tidak | Indeks Kesiapan Penyiraman |
| `watering_status` | string(80) | Tidak | Keputusan penyiraman |
| `created_at` | timestamp | Ya | Waktu data masuk server |
| `updated_at` | timestamp | Ya | Waktu data diubah |

### Index

| Index | Tujuan |
|---|---|
| `device_id` | Filter per perangkat |
| `sequence_no` | Lookup nomor urut |
| `sent_at` | Query window evaluasi |
| `device_id`, `created_at` | Riwayat per perangkat |

### Migration File

- `2026_05_29_000000_create_sensor_readings_table.php` — schema dasar.
- `2026_05_30_000000_add_evaluation_columns_to_sensor_readings.php` — menambah `sent_at`, `device_total_sent`, `delay_ms` dan index `sent_at`.

---

## 5.2 Model `SensorReading`

Memakai `HasFactory` (factory di `database/factories/SensorReadingFactory.php`).

### Field `$fillable`

```php
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
```

### Cast

| Field | Cast |
|---|---|
| `sequence_no` | integer |
| `sent_at` | datetime |
| `device_total_sent` | integer |
| `delay_ms` | integer |
| `soil_raw` | integer |
| `moisture_percent` | float |
| `distance_cm` | float |
| `water_level_percent` | float |
| `water_volume_ml` | float |
| `temperature` | float |
| `humidity` | float |
| `dht_ok` | boolean |
| `soil_score` | integer |
| `water_score` | integer |
| `temp_score` | integer |
| `ikp` | integer |

---

## 6. Validasi Payload

`StoreSensorReadingRequest`.

### API Key

```php
return hash_equals((string) config('services.iot.api_key'), (string) $this->input('api_key'));
```

`hash_equals()` mencegah timing attack.

### Rules Validasi

| Field | Rule |
|---|---|
| `api_key` | required, string |
| `device_id` | required, string, max:80 |
| `sequence_no` | nullable, integer, min:0 |
| `sent_at` | nullable, date |
| `device_timestamp` | nullable, date *(alias firmware lama → `sent_at`)* |
| `total_sent` | nullable, integer, min:0 *(alias → `device_total_sent`)* |
| `device_epoch` | nullable, integer, min:0 *(diterima tapi tidak disimpan)* |
| `soil_raw` | required, integer, min:0, max:4095 |
| `moisture_percent` | required, numeric, min:0, max:100 |
| `soil_condition` | required, string, in: Kering/Lembab/Basah |
| `distance_cm` | nullable, numeric, min:0, max:400 |
| `water_level_percent` | nullable, numeric, min:0, max:100 |
| `water_volume_ml` | nullable, numeric, min:0, max:999999 |
| `water_status` | required, string, max:50 |
| `temperature` | nullable, numeric, min:-40, max:80 |
| `humidity` | nullable, numeric, min:0, max:100 |
| `dht_ok` | required, boolean |
| `soil_score` | required, integer, min:0, max:100 |
| `water_score` | required, integer, min:0, max:100 |
| `temp_score` | required, integer, min:0, max:100 |
| `ikp` | required, integer, min:0, max:300 |
| `watering_status` | required, string, max:80 |

---

## 7. Format JSON dari ESP32

### Contoh Payload Lengkap

```json
{
  "api_key": "kode_rahasia_anda",
  "device_id": "esp32-001",
  "sequence_no": 1,
  "sent_at": "2026-05-30 17:00:00",
  "total_sent": 10,
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

`sent_at` format `Y-m-d H:i:s` tanpa offset; firmware berasumsi WIB (`Asia/Jakarta`). Server parse pakai `config('services.iot.device_timezone')` lalu konversi ke UTC. `total_sent` adalah counter kumulatif yang naik 1 setiap kirim packet, dipakai untuk hitung sent vs received.

### Field Wajib

`api_key`, `device_id`, `soil_raw`, `moisture_percent`, `soil_condition`, `water_status`, `dht_ok`, `soil_score`, `water_score`, `temp_score`, `ikp`, `watering_status`.

### Field Opsional

`sequence_no`, `sent_at`, `total_sent`, `distance_cm`, `water_level_percent`, `water_volume_ml`, `temperature`, `humidity`.

### Alias Firmware Lama

| Alias diterima | Disimpan sebagai |
|---|---|
| `device_timestamp` | `sent_at` |
| `total_sent` (atau fallback `sequence_no`) | `device_total_sent` |
| `device_epoch` | *(dibuang)* |

### Saat Sensor Air Error

```json
{
  "distance_cm": null,
  "water_level_percent": null,
  "water_volume_ml": null,
  "water_status": "Sensor air error"
}
```

### Saat DHT22 Error

```json
{
  "temperature": null,
  "humidity": null,
  "dht_ok": false
}
```

---

## 8. Konfigurasi Project

## 8.1 Environment

`.env.example`:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
IOT_API_KEY=your_secure_api_key_here

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

### Variable Penting

| Variable | Fungsi |
|---|---|
| `APP_KEY` | Key enkripsi Laravel |
| `APP_DEBUG` | Mode debug |
| `APP_URL` | URL dasar (dipakai di evaluation status) |
| `IOT_API_KEY` | API key ESP32 |
| `IOT_DEVICE_TIMEZONE` | Timezone device, default `Asia/Jakarta`. Set kalau firmware kirim non-WIB |
| `DB_*` | Konfigurasi database |

## 8.2 Services Config

`config/services.php`:

```php
'iot' => [
    'api_key' => env('IOT_API_KEY'),
    'device_timezone' => env('IOT_DEVICE_TIMEZONE', 'Asia/Jakarta'),
],
```

Setelah ubah `.env`:

```powershell
php artisan config:clear
```

---

## 9. Instalasi dan Menjalankan Project

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iot_dashboard
DB_USERNAME=root
DB_PASSWORD=

IOT_API_KEY=ganti_dengan_api_key_rahasia
IOT_DEVICE_TIMEZONE=Asia/Jakarta
```

Generate key acak:

```powershell
php artisan tinker
```

```php
Str::random(64)
```

Migrate + serve:

```powershell
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

Akses:

```txt
http://IP-LAPTOP:8000/             -> dashboard
http://IP-LAPTOP:8000/evaluation   -> halaman evaluasi
http://IP-LAPTOP:8000/api/sensor-data
```

---

## 10. Konfigurasi ESP32

```cpp
const char* SERVER_URL = "http://192.168.1.10:8000/api/sensor-data";
const char* API_KEY = "ganti_dengan_api_key_rahasia";
const bool USE_HTTPS = false;
```

Production HTTPS:

```cpp
const char* SERVER_URL = "https://domainanda.com/api/sensor-data";
const bool USE_HTTPS = true;
```

### Catatan untuk Field Evaluasi

- ESP32 sebaiknya sinkron NTP sebelum kirim. Format `sent_at`: `"Y-m-d H:i:s"` waktu lokal device (WIB default).
- `total_sent` di-increment setiap pengiriman (termasuk yang gagal lalu retry, sesuai keinginan: itu yang bikin PDR akurat).
- ESP32 dan laptop satu jaringan WiFi.
- Firewall Windows izinkan port 8000 saat pakai `php artisan serve`.

---

## 11. Pengujian API

## 11.1 POST Data Sensor

```powershell
$body = @{
  api_key = "ganti_dengan_api_key_rahasia"
  device_id = "esp32-001"
  sequence_no = 1
  sent_at = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
  total_sent = 1
  soil_raw = 3000
  moisture_percent = 50.0
  soil_condition = "Lembab"
  distance_cm = 12.5
  water_level_percent = 62.5
  water_volume_ml = 937.5
  water_status = "Cukup"
  temperature = 29.5
  humidity = 70.2
  dht_ok = $true
  soil_score = 30
  water_score = 10
  temp_score = 10
  ikp = 50
  watering_status = "Perlu dipantau"
} | ConvertTo-Json

Invoke-RestMethod `
  -Uri "http://127.0.0.1:8000/api/sensor-data" `
  -Method Post `
  -ContentType "application/json" `
  -Headers @{ Accept = "application/json" } `
  -Body $body
```

## 11.2 Data Terbaru

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/sensor-readings/latest" -Headers @{ Accept = "application/json" }
```

## 11.3 Riwayat

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/sensor-readings/history?limit=10" -Headers @{ Accept = "application/json" }
```

## 11.4 Metrik Evaluasi

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/evaluation/metrics" -Headers @{ Accept = "application/json" }
```

## 11.5 Automated Test

```powershell
php artisan test
```

PHPUnit dikonfigurasi pakai SQLite in-memory (`phpunit.xml`), jadi tidak butuh MySQL. Lihat [Section 16](#16-testing).

---

## 12. Modul Evaluasi (PDR + Delay)

`App\Services\EvaluationService` adalah pusat logic evaluasi. Dipakai oleh `EvaluationController` (web + JSON endpoint).

### 12.1 Window Waktu

| Key | Range | Label |
|---|---|---|
| `recent` | 15 menit terakhir | "15 Menit Terakhir" |
| `today` | sejak `00:00` hari ini | "Hari Ini" |
| `all` | sejak data pertama | "All-time" |

### 12.2 Konstanta

```php
EvaluationService::VALUE_FRESH_THRESHOLD_SECONDS = 25;
```

Data lebih tua dari threshold dianggap stale → device disconnected.

### 12.3 Sesi Aktif (`activeSessionStart`)

- Ambil reading paling baru. Jika umur > threshold → `null` (disconnected).
- Telusuri 1000 reading terakhir (terbaru → terlama). Cari gap antar-reading > threshold.
- Reading pertama setelah gap = awal sesi aktif. Kalau tidak ada gap, sesi mulai dari reading paling lama yang dicek.

Tujuan: setelah ESP32 mati lalu reconnect, metrik dihitung sejak packet pertama sesi baru, bukan tercemar history sesi lama.

### 12.4 Hitung Sent (`device_total_sent`)

`Sent` dihitung dari counter kumulatif per device:

1. Ambil semua reading di window yang punya `device_total_sent`, urut per device + waktu.
2. Untuk reading pertama device: `sent += 1`.
3. Untuk reading berikutnya: `sent += current - previous` (counter naik).
4. Jika `current < previous` → counter reset (ESP32 reboot): hitung sebagai 1 packet baru, mulai akumulasi ulang.

### 12.5 Edge Cases PDR

- **Tidak ada `device_total_sent` valid**: `sent = received`, `pdr_estimated = true`, PDR = 100%.
- **Counter parsial** (sebagian row tidak punya counter): `received` direduksi ke jumlah row dengan counter saja, `pdr_estimated = true`.
- **PDR > 100%** (received > sent karena duplikasi atau out-of-order): clamp ke 100%.
- **Sent = 0 dan received = 0**: PDR = 0%.

Formula:

```txt
pdr = round((received / sent) * 100, 2)
lost = max(0, sent - received)
```

### 12.6 Hitung Delay

Delay diambil dari kolom `delay_ms` (sudah diisi server saat POST).

```sql
SELECT AVG(delay_ms) avg_ms,
       MIN(delay_ms) min_ms,
       MAX(delay_ms) max_ms,
       COUNT(*)      n
  FROM sensor_readings
 WHERE delay_ms IS NOT NULL
   AND created_at BETWEEN :from AND :to
```

Output: `delay_avg_ms`, `delay_min_ms`, `delay_max_ms`, `delay_samples`.

### 12.7 Reset on Disconnect

`metrics($window, $sessionStart, $resetOnDisconnect = true)`:

- `true` (default, dipakai `/api/evaluation/metrics` polling) → kalau device disconnected, kembalikan `zeroMetrics()` dengan `reset = true`.
- `false` (dipakai `/evaluation` saat initial render) → tampilkan angka historis, jangan reset.

Window `from` di-clamp ke `max(window.from, activeSessionStart)` agar tidak menghitung packet sesi sebelumnya.

### 12.8 Status Dashboard (`dashboardStatus`)

| Field | Arti |
|---|---|
| `online` | Selalu `true` (request sampai = server hidup) |
| `url` | `config('app.url')` |
| `value_displayed` | `true` jika reading paling baru ≤ threshold detik |
| `latest_age_seconds` | Umur reading paling baru (detik) |
| `latest_at` | Timestamp ISO8601 reading paling baru |
| `threshold_seconds` | `VALUE_FRESH_THRESHOLD_SECONDS` |

### 12.9 Output `fullReport`

```php
[
  'metrics' => [ 'recent' => [...], 'today' => [...], 'all' => [...] ],
  'dashboard_status' => [ ... ],
  'active_session_start' => '2026-05-30T09:55:30+00:00' | null,
  'reset_on_disconnect' => true|false,
  'generated_at' => '2026-05-30T10:00:05+00:00',
]
```

---

## 13. Penjelasan Logic IKP

IKP = `Indeks Kesiapan Penyiraman`. Dihitung di ESP32, bukan di Laravel. Laravel hanya validasi range + simpan + tampilkan.

| Field | Range |
|---|---:|
| `soil_score` | 0-100 |
| `water_score` | 0-100 |
| `temp_score` | 0-100 |
| `ikp` | 0-300 |

Semakin besar IKP, semakin tinggi kebutuhan perhatian. Keputusan teks dikirim lewat `watering_status`.

| IKP | `watering_status` (contoh) |
|---:|---|
| 30 | Tidak perlu disiram |
| 50 | Perlu dipantau |
| 100+ | Perlu disiram |

Batas keputusan = kode ESP32, bukan Laravel.

---

## 14. Keamanan

- API key di `.env` (`IOT_API_KEY`), bukan di kode.
- Bandingkan pakai `hash_equals()` (timing-safe).
- `api_key` tidak disimpan ke database.
- Jangan commit `.env`.
- Production: HTTPS wajib.
- Pertimbangkan rate limiting pada `/api/sensor-data` jika dibuka ke internet.
- Endpoint baca (`history`, `latest`, `evaluation/metrics`) saat ini publik—batasi via firewall / auth jika dashboard internal.

---

## 15. Troubleshooting

## 15.1 `401 Unauthorized`

- `api_key` ESP32 ≠ `IOT_API_KEY`.
- Config Laravel masih cache lama → `php artisan config:clear`.

## 15.2 `422 Unprocessable Entity`

- Field wajib hilang / tipe salah / di luar range.
- `soil_condition` bukan `Kering`/`Lembab`/`Basah`.
- `sent_at` format invalid.

Cek response JSON Laravel untuk detail rule yang gagal.

## 15.3 Dashboard Kosong

- Belum ada data di `sensor_readings`.
- ESP32 belum berhasil POST.
- Belum migrate (`php artisan migrate`).

## 15.4 ESP32 Tidak Connect ke Laravel Lokal

- Beda jaringan, server listen `127.0.0.1`, firewall port 8000, ESP32 pakai `localhost`.
- Solusi: `php artisan serve --host=0.0.0.0 --port=8000` + IP laptop di `SERVER_URL`.

## 15.5 Status Dashboard `Disconnected`

- Endpoint history gagal / server mati / network error.

## 15.6 Delay Negatif atau Sangat Besar

- Clock ESP32 tidak sinkron NTP.
- `IOT_DEVICE_TIMEZONE` tidak match dengan timezone yang dipakai firmware.
- Server clock drift (cek NTP server).
- Negatif tidak akan tersimpan: server clamp ke 0. Tapi delay yang sangat besar (jam-jaman) menandakan misconfig timezone.

## 15.7 PDR Selalu 100% atau `pdr_estimated = true`

- ESP32 belum kirim `total_sent` / `device_total_sent` → server fallback `sent = received`.
- Pastikan firmware update kirim counter setiap request.

## 15.8 Metrik Reset Tiap Polling

- Data terakhir lebih tua dari 25 detik → device dianggap disconnected, metric reset.
- Naikkan `VALUE_FRESH_THRESHOLD_SECONDS` atau pastikan ESP32 kirim minimal tiap < 25 detik.
- Untuk lihat angka historis tanpa reset: `/api/evaluation/metrics?reset_on_disconnect=0`.

---

## 16. Testing

### Konfigurasi

`phpunit.xml` memakai SQLite in-memory:

```xml
<env name="DB_CONNECTION" value="sqlite" force="true"/>
<env name="DB_DATABASE" value=":memory:" force="true"/>
```

Tidak butuh MySQL untuk test.

### Test yang Sudah Ada

| File | Cakupan |
|---|---|
| `tests/Feature/SensorDataControllerTest.php` | Reject API key salah, simpan payload + hitung `delay_ms`, clamp delay negatif ke 0 |
| `tests/Unit/EvaluationServiceTest.php` | Reset saat data stale, opsi skip reset, partial counter, counter reset device |

### Factory

`database/factories/SensorReadingFactory.php` menyediakan default lengkap (`device_id = esp32-001`, `delay_ms = 100`, dst).

Pakai di test:

```php
SensorReading::factory()->create([
    'created_at' => Carbon::parse('2026-05-30 10:00:30'),
    'device_total_sent' => 5,
]);
```

### Jalankan

```powershell
php artisan test
php artisan test --filter=EvaluationServiceTest
php artisan test --testsuite=Feature
```

### Saran Test Tambahan

- Feature test endpoint `/api/evaluation/metrics`.
- Test alias firmware lama (`device_timestamp`, `total_sent` fallback ke `sequence_no`).
- Test `IOT_DEVICE_TIMEZONE` non-WIB.
- Test PDR > 100% di-clamp ke 100.

---

## 17. Catatan Pengembangan Lanjutan

- Login admin untuk dashboard + evaluasi.
- Filter data per `device_id`.
- Export CSV/Excel.
- Notifikasi (email/Telegram) saat air rendah, tanaman kering, atau PDR turun.
- Realtime update via WebSocket (Pusher / Laravel Reverb).
- Rate limiting `/api/sensor-data`.
- API token per perangkat (Sanctum) jika multi-device.
- Halaman detail per perangkat.
- Persist `active_session_start` ke tabel terpisah agar `activeSessionStart()` tidak scan 1000 row tiap polling.
- Index `(device_id, created_at, device_total_sent)` jika data > jutaan row.

---

## 18. Ringkasan Endpoint

| Method | Endpoint | Fungsi | Auth |
|---|---|---|---|
| POST | `/api/sensor-data` | Terima data sensor + hitung delay | `api_key` payload |
| GET | `/api/sensor-readings/latest` | Reading terbaru | — |
| GET | `/api/sensor-readings/history` | Riwayat (limit 1-500) | — |
| GET | `/api/evaluation/metrics` | PDR + delay + status (3 window) | — |
| GET | `/` | Halaman dashboard | — |
| GET | `/evaluation` | Halaman evaluasi | — |

---

## 19. Ringkasan File dan Tanggung Jawab

| File | Tanggung Jawab |
|---|---|
| `StoreSensorReadingRequest.php` | Authorize API key + validasi payload (termasuk alias firmware lama) |
| `SensorDataController.php` | Simpan sensor, hitung `delay_ms`, sediakan latest + history |
| `DashboardController.php` | Data awal halaman dashboard |
| `EvaluationController.php` | View `/evaluation` + endpoint JSON metrics |
| `EvaluationService.php` | Window range, sesi aktif, hitung PDR + delay, status dashboard |
| `SensorReading.php` | Eloquent model `sensor_readings` |
| `SensorReadingFactory.php` | Factory untuk testing |
| `dashboard.blade.php` | UI dashboard, chart, table, polling |
| `evaluation.blade.php` | UI evaluasi (PDR, delay, status) |
| `api.php` | Endpoint API |
| `web.php` | Route halaman |
| `services.php` | `iot.api_key` + `iot.device_timezone` |
| `2026_05_29_*_create_sensor_readings_table.php` | Schema dasar |
| `2026_05_30_*_add_evaluation_columns_to_sensor_readings.php` | Kolom evaluasi |
| `phpunit.xml` | Konfigurasi PHPUnit (SQLite in-memory) |

---

## 20. Kesimpulan

Project sekarang punya alur lengkap:

1. ESP32 baca sensor + sinkron NTP + naikkan counter.
2. ESP32 POST JSON (`sent_at` + `total_sent` + data sensor).
3. Laravel validasi + hitung `delay_ms` + simpan.
4. Dashboard menampilkan kondisi terbaru, grafik, detail, riwayat (auto-refresh 5 detik).
5. Halaman evaluasi menghitung PDR + delay per window dengan deteksi disconnect dan handling counter reset.
6. Test PHPUnit menjamin reject API key salah, perhitungan delay benar, dan logic evaluasi tahan terhadap edge case (counter reset, partial counter, stale data).

Dokumentasi ini dipakai sebagai panduan instalasi, pengembangan, debugging, dan presentasi project.
