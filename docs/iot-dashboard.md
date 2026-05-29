# Dokumentasi IoT Dashboard Monitoring Tanaman

Dokumen ini menjelaskan alur data ESP32 ke Laravel API, format JSON, konfigurasi environment, dan cara menjalankan dashboard.

## Ringkasan Sistem

Sistem menerima data sensor dari ESP32 melalui HTTP POST, menyimpan data ke tabel `sensor_readings`, lalu menampilkan data terbaru dan riwayat pada dashboard web.

Komponen utama:

- ESP32 sebagai pengirim data sensor.
- Laravel API sebagai penerima dan penyimpan data.
- Database sebagai penyimpanan riwayat sensor.
- Dashboard Blade sebagai tampilan monitoring.

## Sensor ESP32

Sensor yang digunakan:

| Sensor | Fungsi | Pin ESP32 |
|---|---|---|
| Capacitive Soil Moisture Sensor v2.0 | Membaca kelembapan tanah | GPIO32 / D32 |
| HC-SR04 | Mengukur jarak permukaan air | TRIG GPIO5 / D5, ECHO GPIO18 / D18 |
| DHT22 | Membaca suhu dan kelembapan udara | GPIO16 / D16 |

## Endpoint API

Endpoint untuk menerima data dari ESP32:

```txt
POST /api/sensor-data
```

Contoh lokal:

```txt
http://192.168.1.10:8000/api/sensor-data
```

Contoh production:

```txt
https://domainanda.com/api/sensor-data
```

Endpoint baca data:

```txt
GET /api/sensor-readings/latest
GET /api/sensor-readings/history
```

## Konfigurasi Laravel

Tambahkan API key ke `.env`:

```env
IOT_API_KEY=kode_rahasia_anda
```

Pastikan `config/services.php` memiliki konfigurasi berikut:

```php
'iot' => [
    'api_key' => env('IOT_API_KEY'),
],
```

Request ESP32 harus mengirim field `api_key` dengan nilai sama seperti `IOT_API_KEY`.

Jika API key salah, server mengembalikan:

```json
{
  "message": "Unauthorized. API key tidak valid."
}
```

Status HTTP: `401`.

## Konfigurasi ESP32

Untuk local Laravel, gunakan IP laptop/server. Jangan pakai `localhost`, karena `localhost` pada ESP32 berarti ESP32 itu sendiri.

```cpp
const char* SERVER_URL = "http://192.168.1.10:8000/api/sensor-data";
const char* API_KEY = "kode_rahasia_anda";
const bool USE_HTTPS = false;
```

Untuk production HTTPS:

```cpp
const char* SERVER_URL = "https://domainanda.com/api/sensor-data";
const char* API_KEY = "kode_rahasia_anda";
const bool USE_HTTPS = true;
```

Jika memakai `php artisan serve`, jalankan dengan bind ke semua interface:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Dashboard dapat dibuka di:

```txt
http://IP-LAPTOP:8000/dashboard
```

## Format JSON ESP32

Payload JSON yang dikirim ESP32 sudah sesuai dengan controller dan validasi Laravel.

```json
{
  "api_key": "kode_rahasia_anda",
  "device_id": "POT-001",
  "sequence_no": 1,
  "soil_raw": 2404,
  "moisture_percent": 99.67,
  "soil_condition": "Basah",
  "distance_cm": 10.25,
  "water_level_percent": 73.75,
  "water_volume_ml": 1106.25,
  "water_status": "Cukup",
  "temperature": 29.5,
  "humidity": 75.2,
  "dht_ok": true,
  "soil_score": 10,
  "water_score": 10,
  "temp_score": 10,
  "ikp": 30,
  "watering_status": "Tidak perlu disiram"
}
```

## Field JSON

| Field | Tipe | Wajib | Keterangan |
|---|---:|---:|---|
| `api_key` | string | Ya | Kunci autentikasi ESP32 |
| `device_id` | string | Ya | ID perangkat, contoh `POT-001` |
| `sequence_no` | integer | Tidak | Nomor urut pengiriman data |
| `soil_raw` | integer | Ya | Nilai ADC soil sensor, 0 sampai 4095 |
| `moisture_percent` | number | Ya | Persentase kelembapan tanah, 0 sampai 100 |
| `soil_condition` | string | Ya | `Kering`, `Lembab`, atau `Basah` |
| `distance_cm` | number/null | Tidak | Jarak sensor HC-SR04 ke permukaan air |
| `water_level_percent` | number/null | Tidak | Persentase cadangan air, 0 sampai 100 |
| `water_volume_ml` | number/null | Tidak | Estimasi volume air dalam ml |
| `water_status` | string | Ya | Status cadangan air, contoh `Rendah`, `Sedang`, `Cukup` |
| `temperature` | number/null | Tidak | Suhu udara dari DHT22 |
| `humidity` | number/null | Tidak | Kelembapan udara dari DHT22 |
| `dht_ok` | boolean | Ya | Status pembacaan DHT22 |
| `soil_score` | integer | Ya | Skor kelembapan tanah |
| `water_score` | integer | Ya | Skor cadangan air |
| `temp_score` | integer | Ya | Skor suhu |
| `ikp` | integer | Ya | Indeks Kesiapan Penyiraman |
| `watering_status` | string | Ya | Keputusan/status penyiraman |

## Nilai Null Saat Sensor Error

Jika HC-SR04 gagal membaca jarak, ESP32 mengirim:

```json
{
  "distance_cm": null,
  "water_level_percent": null,
  "water_volume_ml": null,
  "water_status": "Sensor air error"
}
```

Jika DHT22 gagal membaca suhu atau humidity, ESP32 mengirim:

```json
{
  "temperature": null,
  "humidity": null,
  "dht_ok": false
}
```

Laravel menerima nilai `null` untuk field tersebut.

## Validasi Laravel

Aturan utama validasi:

- `soil_raw`: integer 0-4095.
- `moisture_percent`: numeric 0-100.
- `soil_condition`: hanya `Kering`, `Lembab`, `Basah`.
- `distance_cm`: nullable numeric 0-400.
- `water_level_percent`: nullable numeric 0-100.
- `temperature`: nullable numeric -40 sampai 80.
- `humidity`: nullable numeric 0-100.
- `dht_ok`: boolean.
- `ikp`: integer 0-300.

## Response Berhasil

Jika data berhasil disimpan, server mengembalikan:

```json
{
  "message": "Data sensor berhasil disimpan.",
  "id": 1,
  "received_at": "2026-05-30T10:00:00.000000Z"
}
```

Status HTTP: `201`.

## Test API dengan PowerShell

```powershell
$body = @{
  api_key = "kode_rahasia_anda"
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

## Troubleshooting

### `401 Unauthorized`

Penyebab: `api_key` ESP32 tidak sama dengan `IOT_API_KEY` di `.env`.

Perbaikan:

```powershell
php artisan config:clear
```

Pastikan ESP32 memakai API key yang sama.

### ESP32 gagal connect ke Laravel lokal

Penyebab umum:

- ESP32 dan laptop beda jaringan.
- Laravel masih berjalan di `127.0.0.1`.
- Firewall Windows memblokir port 8000.
- ESP32 memakai `localhost`.

Perbaikan:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Gunakan IP laptop pada `SERVER_URL`.

### `422 Unprocessable Entity`

Penyebab: format JSON tidak sesuai rules validasi.

Cek response server di Serial Monitor. Laravel biasanya mengirim detail field yang gagal.

### Dashboard kosong

Penyebab: belum ada data di tabel `sensor_readings` atau ESP32 belum berhasil POST.

Cek API latest:

```txt
http://IP-LAPTOP:8000/api/sensor-readings/latest
```

## Alur Data

```txt
ESP32
  -> baca soil moisture, HC-SR04, DHT22
  -> hitung moisture, water level, volume, IKP
  -> buat JSON
  -> POST /api/sensor-data
  -> Laravel validasi API key dan payload
  -> simpan ke sensor_readings
  -> dashboard baca data terbaru dan riwayat
```
