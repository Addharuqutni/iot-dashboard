# Dokumentasi Project — IoT Dashboard Monitoring Tanaman

Dokumentasi ini menjelaskan project Laravel untuk menerima data sensor dari ESP32, menyimpan data ke database, dan menampilkan kondisi tanaman pada dashboard web secara real-time.

---

## 1. Gambaran Umum

Project ini adalah aplikasi monitoring tanaman berbasis Laravel. Sistem membaca data dari perangkat ESP32 yang terhubung dengan sensor kelembapan tanah, sensor jarak air, dan sensor suhu/kelembapan udara. Data dikirim ke Laravel lewat REST API, divalidasi, disimpan ke database, lalu ditampilkan pada dashboard.

### Tujuan Sistem

- Menerima data sensor dari ESP32.
- Memvalidasi data dan API key sebelum data masuk database.
- Menyimpan riwayat pembacaan sensor.
- Menampilkan kondisi terbaru tanaman.
- Menampilkan grafik perubahan sensor.
- Memberikan status penyiraman melalui nilai IKP.

### Teknologi Utama

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 10 |
| Bahasa | PHP 8.1+ |
| Database | MySQL secara default |
| Frontend | Blade, TailwindCSS CDN, Chart.js CDN |
| API | Laravel API routes |
| Perangkat | ESP32 |
| Sensor | Soil Moisture, HC-SR04, DHT22 |

---

## 2. Arsitektur Sistem

```txt
ESP32
  ├─ Baca sensor tanah
  ├─ Baca jarak air
  ├─ Baca suhu dan humidity
  ├─ Hitung skor dan IKP
  └─ Kirim JSON ke API Laravel

Laravel API
  ├─ Validasi API key
  ├─ Validasi format payload
  ├─ Simpan data ke database
  └─ Sediakan endpoint baca data

Database
  └─ Tabel sensor_readings

Dashboard Web
  ├─ Ambil data terbaru
  ├─ Ambil riwayat data
  ├─ Tampilkan KPI card
  ├─ Tampilkan grafik
  └─ Auto-refresh tiap 5 detik
```

### Alur Data Ringkas

```txt
Sensor fisik -> ESP32 -> POST /api/sensor-data -> StoreSensorReadingRequest
-> SensorDataController@store -> SensorReading::create()
-> tabel sensor_readings -> DashboardController@index
-> resources/views/dashboard.blade.php
```

---

## 3. Struktur Folder Penting

| Path | Fungsi |
|---|---|
| `routes/api.php` | Mendefinisikan endpoint API untuk ESP32 dan dashboard polling |
| `routes/web.php` | Mendefinisikan route halaman dashboard |
| `app/Http/Controllers/SensorDataController.php` | Logic penerimaan dan pembacaan data sensor via API |
| `app/Http/Controllers/DashboardController.php` | Logic penyedia data awal untuk halaman dashboard |
| `app/Http/Requests/StoreSensorReadingRequest.php` | Validasi API key dan payload sensor |
| `app/Models/SensorReading.php` | Model Eloquent untuk tabel sensor_readings |
| `database/migrations/2026_05_29_000000_create_sensor_readings_table.php` | Struktur tabel penyimpanan sensor |
| `resources/views/dashboard.blade.php` | Tampilan dashboard, grafik, tabel, polling JavaScript |
| `config/services.php` | Konfigurasi API key IoT dari `.env` |
| `.env.example` | Template konfigurasi environment |

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
2. Laravel menjalankan `StoreSensorReadingRequest`.
3. Request memeriksa `api_key`.
4. Request memvalidasi semua field sensor.
5. Controller mengambil data valid lewat `$request->validated()`.
6. Field `api_key` dihapus agar tidak disimpan.
7. Data disimpan memakai `SensorReading::create($data)`.
8. Server mengembalikan response JSON status `201`.

### Response Berhasil

```json
{
  "message": "Data sensor berhasil disimpan.",
  "id": 1,
  "received_at": "2026-05-30T10:00:00.000000Z"
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

Modul ini mengambil satu data sensor paling baru.

### Endpoint

```txt
GET /api/sensor-readings/latest
```

### Logic

- Controller menjalankan `SensorReading::latest()->first()`.
- Data terbaru dikembalikan dalam key `data`.
- Jika database kosong, nilai `data` menjadi `null`.

### Contoh Response

```json
{
  "data": {
    "id": 1,
    "device_id": "POT-001",
    "sequence_no": 1,
    "moisture_percent": 50,
    "soil_condition": "Lembab",
    "ikp": 50,
    "watering_status": "Perlu dipantau",
    "created_at": "2026-05-30T10:00:00.000000Z"
  }
}
```

---

## 4.3 Modul API Riwayat Sensor

Modul ini mengambil beberapa data terbaru untuk dashboard, chart, dan tabel.

### Endpoint

```txt
GET /api/sensor-readings/history
GET /api/sensor-readings/history?limit=50
```

### Logic

- Query parameter `limit` dibaca dari request.
- Nilai `limit` dibatasi dari 1 sampai 500.
- Data diambil dengan urutan terbaru ke terlama.
- Response dikembalikan dalam key `data`.

### Batas Limit

| Input `limit` | Hasil |
|---:|---:|
| kosong | 50 |
| 0 atau negatif | 1 |
| 1-500 | sesuai input |
| lebih dari 500 | 500 |

---

## 4.4 Modul Dashboard Web

Dashboard menampilkan data monitoring tanaman dalam bentuk ringkas dan mudah dibaca.

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
| `$latest` | Data sensor terbaru |
| `$readings` | 50 data terbaru |
| `$chartLabels` | Label waktu untuk grafik |
| `$moistureData` | Data kelembapan tanah untuk grafik |
| `$waterData` | Data level air untuk grafik |
| `$temperatureData` | Data suhu untuk grafik |
| `$humidityData` | Data humidity untuk grafik |
| `$ikpData` | Data IKP untuk grafik |

### Logic Controller

1. Ambil data terbaru: `SensorReading::latest()->first()`.
2. Ambil 50 data terbaru: `SensorReading::latest()->limit(50)->get()`.
3. Balik urutan data untuk chart agar grafik bergerak dari data lama ke terbaru.
4. Kirim semua data ke view `dashboard`.

---

## 4.5 Modul Tampilan KPI Card

Dashboard memiliki 4 kartu KPI utama.

| Kartu | Data | Keterangan |
|---|---|---|
| Kelembapan Tanah | `moisture_percent`, `soil_condition` | Menampilkan persentase tanah dan status kering/lembab/basah |
| Cadangan Air | `water_level_percent`, `water_status` | Menampilkan sisa air dalam persen dan status air |
| Suhu Udara | `temperature`, `dht_ok` | Menampilkan suhu dan status sensor DHT22 |
| IKP | `ikp`, `watering_status` | Menampilkan indeks kesiapan penyiraman dan keputusan penyiraman |

### Empty State

Jika belum ada data sensor, dashboard menampilkan pesan:

```txt
Belum ada data sensor
Kirim data ESP32 ke /api/sensor-data
```

Jika data sudah ada, dashboard menampilkan KPI, grafik, detail terbaru, dan tabel riwayat.

---

## 4.6 Modul Grafik Sensor

Grafik dibuat memakai Chart.js.

### Dataset Grafik

| Dataset | Field Database | Warna |
|---|---|---|
| Moisture % | `moisture_percent` | Hijau |
| Water % | `water_level_percent` | Biru |
| Temperature °C | `temperature` | Kuning |
| Humidity % | `humidity` | Ungu |
| IKP | `ikp` | Merah |

### Logic Grafik

- Saat halaman pertama kali dibuka, chart memakai data dari controller.
- Setelah itu JavaScript melakukan polling API history.
- Data API terbaru dibalik urutannya sebelum masuk chart.
- Tujuan: sumbu X bergerak dari data lama ke data terbaru.

---

## 4.7 Modul Detail Terbaru

Panel detail terbaru menampilkan informasi sensor lebih lengkap.

| Label Tampilan | Field |
|---|---|
| Device | `device_id` |
| Sequence | `sequence_no` |
| Soil Raw | `soil_raw` |
| Jarak Air | `distance_cm` |
| Volume Air | `water_volume_ml` |
| Humidity | `humidity` |
| Skor T/A/S | `soil_score`, `water_score`, `temp_score` |

`Skor T/A/S` berarti skor Tanah / Air / Suhu.

---

## 4.8 Modul Tabel Riwayat

Tabel riwayat menampilkan 20 data terbaru dari hasil polling.

### Kolom Tabel

| Kolom | Field |
|---|---|
| Waktu | `created_at` |
| Tanah | `moisture_percent`, `soil_condition` |
| Air | `water_level_percent` |
| Suhu | `temperature` |
| Humidity | `humidity` |
| IKP | `ikp` |
| Status | `watering_status` |

### Logic

- JavaScript mengambil maksimal 50 data lewat API.
- Tabel hanya menampilkan 20 baris pertama.
- Baris pertama adalah data terbaru.
- Teks dari API diamankan memakai `escapeHtml()` sebelum masuk HTML tabel.

---

## 4.9 Modul Auto-Refresh / Polling

Dashboard memperbarui data tanpa reload halaman.

### Konfigurasi JavaScript

```js
const POLL_INTERVAL_MS = 5000;
const HISTORY_LIMIT = 50;
const HISTORY_ROWS = 20;
```

### Logic Polling

1. Fungsi `fetchData()` memanggil `/api/sensor-readings/history?limit=50`.
2. Jika response sukses, data dibaca dari `json.data`.
3. Jika data kosong, empty state ditampilkan.
4. Jika data ada:
   - KPI diperbarui.
   - Detail terbaru diperbarui.
   - Tabel diperbarui.
   - Chart diperbarui.
   - Status koneksi menjadi `Live`.
5. Jika fetch gagal, status koneksi menjadi `Disconnected`.

### Status Indicator

| Status | Kondisi |
|---|---|
| Connecting... | Saat halaman baru dibuka |
| Live | API berhasil diakses |
| Disconnected | API gagal diakses atau server error |

---

## 5. Database

## 5.1 Tabel `sensor_readings`

Tabel ini menyimpan setiap data yang dikirim ESP32.

### Struktur Kolom

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---:|---|
| `id` | bigint | Tidak | Primary key |
| `device_id` | string | Tidak | ID perangkat ESP32 |
| `sequence_no` | unsignedBigInteger | Ya | Nomor urut data dari ESP32 |
| `soil_raw` | unsignedSmallInteger | Tidak | Nilai ADC sensor tanah |
| `moisture_percent` | decimal(5,2) | Tidak | Persentase kelembapan tanah |
| `soil_condition` | string(30) | Tidak | Status tanah |
| `distance_cm` | decimal(6,2) | Ya | Jarak permukaan air |
| `water_level_percent` | decimal(5,2) | Ya | Persentase level air |
| `water_volume_ml` | decimal(8,2) | Ya | Estimasi volume air |
| `water_status` | string(50) | Tidak | Status cadangan air |
| `temperature` | decimal(5,2) | Ya | Suhu udara |
| `humidity` | decimal(5,2) | Ya | Kelembapan udara |
| `dht_ok` | boolean | Tidak | Status sensor DHT22 |
| `soil_score` | unsignedTinyInteger | Tidak | Skor tanah |
| `water_score` | unsignedTinyInteger | Tidak | Skor air |
| `temp_score` | unsignedTinyInteger | Tidak | Skor suhu |
| `ikp` | unsignedSmallInteger | Tidak | Indeks Kesiapan Penyiraman |
| `watering_status` | string(80) | Tidak | Status/keputusan penyiraman |
| `created_at` | timestamp | Ya | Waktu data dibuat |
| `updated_at` | timestamp | Ya | Waktu data diubah |

### Index

| Index | Tujuan |
|---|---|
| `device_id` | Mempercepat filter berdasarkan perangkat |
| `sequence_no` | Mempercepat pencarian nomor urut |
| `device_id`, `created_at` | Mempercepat riwayat data per perangkat |

---

## 5.2 Model `SensorReading`

Model memakai mass assignment lewat `$fillable`.

### Field yang Bisa Disimpan

```php
protected $fillable = [
    'device_id',
    'sequence_no',
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

### Cast Data

Model mengubah tipe data otomatis:

| Field | Cast |
|---|---|
| `sequence_no` | integer |
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

Validasi berada di `StoreSensorReadingRequest`.

### API Key

```php
return hash_equals((string) config('services.iot.api_key'), (string) $this->input('api_key'));
```

`hash_equals()` dipakai agar perbandingan API key lebih aman terhadap timing attack.

### Rules Validasi

| Field | Rule |
|---|---|
| `api_key` | required, string |
| `device_id` | required, string, max:80 |
| `sequence_no` | nullable, integer, min:0 |
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

### Field Wajib

Field ini harus selalu dikirim:

- `api_key`
- `device_id`
- `soil_raw`
- `moisture_percent`
- `soil_condition`
- `water_status`
- `dht_ok`
- `soil_score`
- `water_score`
- `temp_score`
- `ikp`
- `watering_status`

### Field Opsional / Bisa Null

Field ini boleh tidak ada atau bernilai `null` sesuai rules:

- `sequence_no`
- `distance_cm`
- `water_level_percent`
- `water_volume_ml`
- `temperature`
- `humidity`

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

File `.env.example` sudah memiliki konfigurasi utama:

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
| `APP_DEBUG` | Mode debug aplikasi |
| `APP_URL` | URL dasar aplikasi |
| `IOT_API_KEY` | API key yang harus dikirim ESP32 |
| `DB_CONNECTION` | Driver database |
| `DB_DATABASE` | Nama database |
| `DB_USERNAME` | Username database |
| `DB_PASSWORD` | Password database |

## 8.2 Services Config

Konfigurasi IoT berada di `config/services.php`:

```php
'iot' => [
    'api_key' => env('IOT_API_KEY'),
],
```

Jika nilai `.env` berubah, jalankan:

```powershell
php artisan config:clear
```

---

## 9. Instalasi dan Menjalankan Project

## 9.1 Install Dependency PHP

```powershell
composer install
```

## 9.2 Install Dependency Frontend

```powershell
npm install
```

Frontend utama memakai CDN Tailwind dan CDN Chart.js pada Blade, tetapi dependency Vite tetap tersedia untuk asset Laravel.

## 9.3 Buat File `.env`

```powershell
Copy-Item .env.example .env
```

## 9.4 Generate APP_KEY

```powershell
php artisan key:generate
```

## 9.5 Atur Database

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iot_dashboard
DB_USERNAME=root
DB_PASSWORD=
```

## 9.6 Atur API Key ESP32

```env
IOT_API_KEY=ganti_dengan_api_key_rahasia
```

Untuk membuat key acak:

```powershell
php artisan tinker
```

Lalu jalankan:

```php
Str::random(64)
```

## 9.7 Jalankan Migration

```powershell
php artisan migrate
```

## 9.8 Jalankan Server Lokal

Agar bisa diakses ESP32 dalam jaringan yang sama:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Dashboard:

```txt
http://IP-LAPTOP:8000/
```

Endpoint ESP32:

```txt
http://IP-LAPTOP:8000/api/sensor-data
```

---

## 10. Konfigurasi ESP32

ESP32 harus memakai IP laptop/server, bukan `localhost`.

### Local Network

```cpp
const char* SERVER_URL = "http://192.168.1.10:8000/api/sensor-data";
const char* API_KEY = "ganti_dengan_api_key_rahasia";
const bool USE_HTTPS = false;
```

### Production HTTPS

```cpp
const char* SERVER_URL = "https://domainanda.com/api/sensor-data";
const char* API_KEY = "ganti_dengan_api_key_rahasia";
const bool USE_HTTPS = true;
```

### Catatan Jaringan

- ESP32 dan laptop harus satu jaringan WiFi.
- Firewall Windows harus mengizinkan port `8000` jika memakai `php artisan serve`.
- `localhost` pada ESP32 berarti ESP32 sendiri, bukan laptop.

---

## 11. Pengujian API

## 11.1 Test POST Data Sensor dengan PowerShell

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

## 11.2 Test Data Terbaru

```powershell
Invoke-RestMethod `
  -Uri "http://127.0.0.1:8000/api/sensor-readings/latest" `
  -Headers @{ Accept = "application/json" }
```

## 11.3 Test Riwayat Data

```powershell
Invoke-RestMethod `
  -Uri "http://127.0.0.1:8000/api/sensor-readings/history?limit=10" `
  -Headers @{ Accept = "application/json" }
```

---

## 12. Penjelasan Logic IKP

IKP adalah `Indeks Kesiapan Penyiraman`. Nilai ini dikirim dari ESP32, bukan dihitung oleh Laravel.

Laravel hanya:

- menerima `soil_score`, `water_score`, `temp_score`, dan `ikp`;
- memvalidasi range nilai;
- menyimpan ke database;
- menampilkan pada dashboard.

### Range Validasi

| Field | Range |
|---|---:|
| `soil_score` | 0-100 |
| `water_score` | 0-100 |
| `temp_score` | 0-100 |
| `ikp` | 0-300 |

### Interpretasi Umum

Semakin besar nilai IKP, semakin tinggi kebutuhan perhatian/penyiraman sesuai logic ESP32. Teks keputusan dikirim lewat field `watering_status`.

Contoh:

| IKP | `watering_status` |
|---:|---|
| 30 | Tidak perlu disiram |
| 50 | Perlu dipantau |
| 100+ | Perlu disiram |

Catatan: batas keputusan mengikuti kode ESP32, bukan Laravel.

---

## 13. Keamanan

### API Key

- API key disimpan di `.env` sebagai `IOT_API_KEY`.
- ESP32 mengirim API key dalam payload JSON.
- Laravel membandingkan API key memakai `hash_equals()`.
- `api_key` tidak disimpan ke database.

### Hal yang Perlu Dijaga

- Jangan commit file `.env`.
- Gunakan API key panjang dan acak.
- Untuk production, gunakan HTTPS.
- Batasi akses server jika dashboard hanya untuk jaringan internal.
- Pertimbangkan rate limiting tambahan jika endpoint dibuka ke internet.

---

## 14. Troubleshooting

## 14.1 `401 Unauthorized`

Penyebab:

- `api_key` dari ESP32 tidak sama dengan `IOT_API_KEY`.
- Config Laravel masih cache nilai lama.

Solusi:

```powershell
php artisan config:clear
```

Lalu pastikan API key ESP32 sama dengan `.env`.

## 14.2 `422 Unprocessable Entity`

Penyebab:

- Field wajib tidak dikirim.
- Tipe data salah.
- Nilai di luar range validasi.
- `soil_condition` bukan `Kering`, `Lembab`, atau `Basah`.

Solusi:

- Cek response JSON dari Laravel.
- Cocokkan payload dengan tabel rules validasi.
- Cek Serial Monitor ESP32.

## 14.3 Dashboard Kosong

Penyebab:

- Belum ada data di tabel `sensor_readings`.
- ESP32 belum berhasil POST.
- Database belum migrate.

Solusi:

```powershell
php artisan migrate
```

Cek endpoint:

```txt
http://IP-LAPTOP:8000/api/sensor-readings/latest
```

## 14.4 ESP32 Tidak Bisa Connect ke Laravel Lokal

Penyebab umum:

- ESP32 dan laptop beda jaringan.
- Server Laravel hanya listen di `127.0.0.1`.
- Firewall Windows memblokir port.
- ESP32 memakai `localhost`.

Solusi:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Gunakan IP laptop pada `SERVER_URL`.

## 14.5 Status Dashboard `Disconnected`

Penyebab:

- API `/api/sensor-readings/history` gagal diakses.
- Server Laravel mati.
- Network error.
- Response API bukan status 2xx.

Solusi:

- Refresh halaman.
- Cek server Laravel.
- Cek console browser.
- Buka endpoint history langsung di browser.

---

## 15. Catatan Pengembangan Lanjutan

Fitur yang bisa ditambahkan:

- Login admin untuk dashboard.
- Filter data berdasarkan `device_id`.
- Export riwayat sensor ke CSV/Excel.
- Notifikasi jika air rendah atau tanaman kering.
- Grafik per sensor dengan rentang waktu.
- Realtime update memakai WebSocket, bukan polling.
- Rate limiting khusus endpoint ESP32.
- API token per perangkat jika device lebih dari satu.
- Halaman detail perangkat.
- Unit test dan feature test untuk endpoint API.

---

## 16. Ringkasan Endpoint

| Method | Endpoint | Fungsi | Auth |
|---|---|---|---|
| POST | `/api/sensor-data` | Menerima data sensor dari ESP32 | `api_key` payload |
| GET | `/api/sensor-readings/latest` | Mengambil data terbaru | Tidak ada |
| GET | `/api/sensor-readings/history` | Mengambil riwayat data | Tidak ada |
| GET | `/` | Menampilkan dashboard | Tidak ada |

---

## 17. Ringkasan File dan Tanggung Jawab

| File | Tanggung Jawab |
|---|---|
| `StoreSensorReadingRequest.php` | Gerbang validasi dan authorization request ESP32 |
| `SensorDataController.php` | Menyimpan data sensor dan menyediakan API pembacaan |
| `DashboardController.php` | Menyiapkan data awal dashboard |
| `SensorReading.php` | Representasi tabel sensor_readings |
| `dashboard.blade.php` | UI dashboard, chart, table, polling |
| `api.php` | Daftar endpoint API |
| `web.php` | Route dashboard |
| `services.php` | Konfigurasi API key IoT |
| migration sensor readings | Struktur penyimpanan data sensor |

---

## 18. Kesimpulan

Project ini sudah memiliki alur lengkap untuk monitoring tanaman berbasis IoT:

1. ESP32 membaca sensor.
2. ESP32 mengirim payload JSON.
3. Laravel memvalidasi API key dan data.
4. Laravel menyimpan pembacaan sensor.
5. Dashboard menampilkan kondisi terbaru, grafik, detail, dan riwayat.
6. Dashboard memperbarui data otomatis tiap 5 detik.

Dokumentasi ini bisa dipakai sebagai panduan instalasi, pengembangan, debugging, dan presentasi project.
