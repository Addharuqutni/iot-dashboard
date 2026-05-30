# IoT Dashboard Monitoring Tanaman

Aplikasi untuk menerima data sensor dari ESP32, menyimpan riwayat pembacaan ke database, dan menampilkan kondisi tanaman pada dashboard web secara real-time.

## Gambaran Project

Project ini dibuat untuk monitoring tanaman berbasis IoT. ESP32 membaca beberapa sensor, menghitung status tanaman, lalu mengirim data ke Laravel API. Laravel memvalidasi data, menyimpan data ke tabel `sensor_readings`, dan menampilkan data pada dashboard.

Sensor yang digunakan:

| Sensor | Fungsi |
|---|---|
| Capacitive Soil Moisture Sensor | Membaca kelembapan tanah |
| HC-SR04 | Mengukur jarak permukaan air / cadangan air |
| DHT22 | Membaca suhu dan kelembapan udara |

---

## Fitur Utama

- Terima data sensor dari ESP32 lewat REST API.
- Validasi API key dari ESP32.
- Validasi payload sensor sebelum disimpan.
- Simpan riwayat pembacaan sensor ke database.
- Dashboard monitoring tanaman berbasis Blade.
- KPI card untuk kelembapan tanah, cadangan air, suhu, dan IKP.
- Grafik sensor menggunakan Chart.js.
- Tabel riwayat data terbaru.
- Auto-refresh dashboard tiap 2 detik.
- Watchdog status: Live → Disconnected jika > 4 detik tanpa data.
- Halaman evaluasi sistem: Packet Delivery Ratio, delay pengiriman, status online.
- Empty state jika data sensor belum tersedia.

---

## Tech Stack

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 10 |
| Bahasa | PHP 8.1+ |
| Database | MySQL |
| Frontend | Blade |
| Styling | TailwindCSS CDN |
| Grafik | Chart.js CDN |
| Build Tool | Vite |
| Testing | PHPUnit |
| Device | ESP32 |

---

## Arsitektur Singkat

```txt
ESP32
  -> baca sensor
  -> hitung moisture, water level, skor, IKP
  -> kirim JSON ke Laravel API

Laravel API
  -> validasi API key
  -> validasi payload
  -> simpan ke database
  -> sediakan endpoint data terbaru dan riwayat

Dashboard
  -> ambil data awal dari controller
  -> polling API tiap 5 detik
  -> update KPI, grafik, detail, dan tabel
```

---

## Struktur Project

```txt
app/
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   └── SensorDataController.php
│   └── Requests/
│       └── StoreSensorReadingRequest.php
├── Models/
│   └── SensorReading.php

config/
└── services.php

database/
└── migrations/
    └── 2026_05_29_000000_create_sensor_readings_table.php

resources/
└── views/
    └── dashboard.blade.php

routes/
├── api.php
└── web.php

docs/
└── iot-dashboard.md
```

### File Penting

| File | Fungsi |
|---|---|
| `routes/api.php` | Endpoint API sensor dan data dashboard |
| `routes/web.php` | Route halaman dashboard |
| `SensorDataController.php` | Logic simpan dan baca data sensor |
| `DashboardController.php` | Logic data awal dashboard |
| `StoreSensorReadingRequest.php` | Validasi API key dan payload |
| `SensorReading.php` | Model tabel `sensor_readings` |
| `dashboard.blade.php` | UI dashboard, chart, polling JS |
| `docs/iot-dashboard.md` | Dokumentasi teknis lengkap |

---

## Kebutuhan Sistem

- PHP 8.1 atau lebih baru
- Composer
- Node.js dan npm
- MySQL / MariaDB
- Laravel 10
- ESP32 dalam jaringan yang sama jika testing lokal

---

## Instalasi

### 1. Clone / buka project

```powershell
git clone <url-repository>
cd iot-dashboard
```

Jika project sudah ada lokal, langsung masuk folder project.

### 2. Install dependency PHP

```powershell
composer install
```

### 3. Install dependency frontend

```powershell
npm install
```

### 4. Buat file environment

```powershell
Copy-Item .env.example .env
```

### 5. Generate app key

```powershell
php artisan key:generate
```

### 6. Atur database

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iot_dashboard
DB_USERNAME=root
DB_PASSWORD=
```

### 7. Atur API key ESP32

Edit `.env`:

```env
IOT_API_KEY=ganti_dengan_api_key_rahasia
```

Generate API key acak jika perlu:

```powershell
php artisan tinker
```

```php
Str::random(64)
```

### 8. Jalankan migration

```powershell
php artisan migrate
```

### 9. Jalankan server lokal

Agar ESP32 bisa akses server dari jaringan WiFi yang sama:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Buka dashboard:

```txt
http://IP-LAPTOP:8000/
```

Contoh:

```txt
http://192.168.1.10:8000/
```

---

## Konfigurasi ESP32

Gunakan IP laptop/server, bukan `localhost`.

```cpp
const char* SERVER_URL = "http://192.168.1.10:8000/api/sensor-data";
const char* API_KEY = "ganti_dengan_api_key_rahasia";
const bool USE_HTTPS = false;
```

Untuk production HTTPS:

```cpp
const char* SERVER_URL = "https://domainanda.com/api/sensor-data";
const char* API_KEY = "ganti_dengan_api_key_rahasia";
const bool USE_HTTPS = true;
```

Catatan:

- `API_KEY` harus sama dengan `IOT_API_KEY` pada `.env`.
- ESP32 dan laptop harus satu jaringan jika server lokal.
- Firewall Windows perlu mengizinkan port `8000` jika ESP32 gagal connect.

---

## Endpoint API

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/sensor-data` | Menerima data sensor dari ESP32 |
| `GET` | `/api/sensor-readings/latest` | Mengambil data sensor terbaru |
| `GET` | `/api/sensor-readings/history` | Mengambil riwayat data sensor |
| `GET` | `/api/evaluation/metrics` | Metrik evaluasi (PDR, delay, status) |
| `GET` | `/` | Menampilkan dashboard |
| `GET` | `/evaluation` | Halaman evaluasi sistem |

### Query Parameter History

```txt
GET /api/sensor-readings/history?limit=50
```

Aturan `limit`:

- Default: `50`
- Minimum: `1`
- Maksimum: `500`

---

## Format Payload

Payload dikirim ESP32 ke:

```txt
POST /api/sensor-data
```

Contoh payload lengkap:

```json
{
  "api_key": "ganti_dengan_api_key_rahasia",
  "device_id": "POT-001",
  "sequence_no": 1,
  "sent_at": "2026-05-30T10:00:00Z",
  "total_sent": 1,
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

### Field Penting

| Field | Tipe | Keterangan |
|---|---|---|
| `api_key` | string | Kunci autentikasi ESP32 |
| `device_id` | string | ID perangkat |
| `sent_at` | string (ISO8601) | Timestamp NTP saat ESP32 mengirim. Dipakai untuk hitung delay. |
| `total_sent` | integer | Counter total request kumulatif ESP32. Dipakai untuk hitung PDR. |
| `soil_raw` | integer | Nilai ADC sensor tanah 0-4095 |
| `moisture_percent` | number | Kelembapan tanah 0-100 |
| `soil_condition` | string | `Kering`, `Lembab`, atau `Basah` |
| `water_level_percent` | number/null | Persentase cadangan air |
| `temperature` | number/null | Suhu udara |
| `humidity` | number/null | Kelembapan udara |
| `dht_ok` | boolean | Status pembacaan DHT22 |
| `ikp` | integer | Indeks Kesiapan Penyiraman |
| `watering_status` | string | Status/keputusan penyiraman |

---

## Testing API

### Test POST data sensor

```powershell
$body = @{
  api_key = "ganti_dengan_api_key_rahasia"
  device_id = "POT-001"
  sequence_no = 1
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

### Test data terbaru

```powershell
Invoke-RestMethod `
  -Uri "http://127.0.0.1:8000/api/sensor-readings/latest" `
  -Headers @{ Accept = "application/json" }
```

### Test riwayat data

```powershell
Invoke-RestMethod `
  -Uri "http://127.0.0.1:8000/api/sensor-readings/history?limit=10" `
  -Headers @{ Accept = "application/json" }
```

---

## Troubleshooting

### `401 Unauthorized`

Penyebab:

- `api_key` tidak sama dengan `IOT_API_KEY`.
- Config Laravel masih cache nilai lama.

Solusi:

```powershell
php artisan config:clear
```

### `422 Unprocessable Entity`

Penyebab:

- Payload tidak sesuai rules validasi.
- Field wajib tidak ada.
- Tipe data salah.
- Nilai sensor di luar range.

Solusi:

- Cek response JSON dari API.
- Cocokkan payload dengan dokumentasi.
- Cek Serial Monitor ESP32.

### Dashboard kosong

Penyebab:

- Belum ada data sensor masuk.
- Migration belum dijalankan.
- ESP32 belum berhasil POST.

Solusi:

```powershell
php artisan migrate
```

Cek endpoint:

```txt
http://IP-LAPTOP:8000/api/sensor-readings/latest
```

### ESP32 tidak bisa connect

Penyebab:

- ESP32 dan laptop beda jaringan.
- Server Laravel hanya berjalan di `127.0.0.1`.
- Firewall memblokir port `8000`.
- ESP32 memakai `localhost`.

Solusi:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Gunakan IP laptop pada `SERVER_URL`.

---

## Dokumentasi Lengkap

Dokumentasi teknis detail tersedia di:

[docs/iot-dashboard.md](docs/iot-dashboard.md)

Isi dokumentasi lengkap:

- Penjelasan tiap modul
- Logic controller
- Logic dashboard
- Struktur database
- Rules validasi payload
- Detail polling JavaScript
- Penjelasan IKP
- Keamanan API key
- Troubleshooting detail
- Ide pengembangan lanjutan

---

## Status Project

Project sudah mencakup alur utama monitoring IoT:

```txt
ESP32 -> Laravel API -> Database -> Dashboard real-time
```

Siap dipakai untuk pengujian lokal, demo project, dan pengembangan fitur lanjutan.
