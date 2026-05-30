# Dokumentasi Library Project

Dokumen ini menjelaskan library, package, dan API yang digunakan pada project IoT Dashboard Monitoring Tanaman.

---

## 1. Ringkasan

Project memakai beberapa jenis dependency:

- Library backend dari Composer.
- Library development dan testing dari Composer.
- Library frontend build dari npm.
- Library frontend CDN langsung dari Blade.
- API browser native untuk polling dashboard.
- Library ESP32/Arduino untuk pengiriman data sensor.

---

## 2. Backend PHP / Laravel

Daftar library utama backend berasal dari `composer.json` bagian `require`.

| Library | Versi | Status | Fungsi |
|---|---:|---|---|
| `php` | `^8.1` | Wajib | Runtime utama project Laravel |
| `laravel/framework` | `^10.10` | Wajib | Framework utama untuk routing, controller, request validation, Eloquent ORM, config, migration, dan Blade |
| `laravel/sanctum` | `^3.3` | Terpasang | Package autentikasi token/API Laravel; belum dipakai aktif pada endpoint IoT saat ini |
| `laravel/tinker` | `^2.8` | Terpasang | REPL Laravel untuk debug manual dan generate API key lewat `Str::random()` |
| `guzzlehttp/guzzle` | `^7.2` | Terpasang | HTTP client PHP; dependency umum Laravel untuk kebutuhan request keluar jika nanti dibutuhkan |

### Pemakaian di Project

| Fitur | Library yang Terlibat |
|---|---|
| Routing API | `laravel/framework` |
| Controller | `laravel/framework` |
| Form Request validation | `laravel/framework` |
| Model `SensorReading` | `laravel/framework` / Eloquent ORM |
| Migration database | `laravel/framework` |
| View dashboard | `laravel/framework` / Blade |
| Generate API key manual | `laravel/tinker` |

---

## 3. Development dan Testing PHP

Daftar library development berasal dari `composer.json` bagian `require-dev`.

| Library | Versi | Fungsi |
|---|---:|---|
| `fakerphp/faker` | `^1.9.1` | Membuat data palsu/dummy untuk factory dan testing |
| `laravel/pint` | `^1.0` | Formatter kode PHP sesuai style Laravel |
| `laravel/sail` | `^1.18` | Environment Docker untuk development Laravel |
| `mockery/mockery` | `^1.4.4` | Membuat mock object saat testing |
| `nunomaduro/collision` | `^7.0` | Membuat tampilan error CLI lebih rapi dan mudah dibaca |
| `phpunit/phpunit` | `^10.1` | Framework testing untuk unit test dan feature test |
| `spatie/laravel-ignition` | `^2.0` | Halaman error/debug Laravel saat mode development |

### Catatan

- Project sudah membawa struktur test default Laravel pada folder `tests/`.
- Endpoint IoT dapat diuji menggunakan feature test Laravel/PHPUnit.
- `laravel/pint` bisa dipakai untuk menjaga format kode tetap konsisten.

---

## 4. JavaScript / Frontend Build

Daftar library JavaScript berasal dari `package.json`.

| Library | Versi | Status | Fungsi |
|---|---:|---|---|
| `vite` | `^5.0.0` | Terpasang | Build tool modern untuk asset frontend |
| `laravel-vite-plugin` | `^1.0.0` | Terpasang | Integrasi Vite dengan Laravel |
| `axios` | `^1.6.4` | Terpasang | HTTP client JavaScript; belum dipakai aktif di dashboard karena dashboard memakai native `fetch()` |

### Catatan

Dashboard saat ini memakai TailwindCSS dan Chart.js dari CDN, bukan bundle Vite. Vite tetap tersedia sebagai standar Laravel untuk pengembangan asset frontend jika nanti project menambah file JavaScript/CSS custom.

---

## 5. Library Frontend CDN

Library ini dipanggil langsung pada `resources/views/dashboard.blade.php`.

```html
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

| Library | Sumber | Fungsi |
|---|---|---|
| TailwindCSS | `https://cdn.tailwindcss.com` | Styling dashboard menggunakan utility class |
| Chart.js | `https://cdn.jsdelivr.net/npm/chart.js` | Membuat grafik garis untuk data sensor |

### TailwindCSS

TailwindCSS dipakai untuk:

- Layout responsive.
- Card KPI.
- Warna status.
- Tabel riwayat.
- Spacing dan typography.
- Empty state.

Contoh penggunaan class:

```html
<body class="min-h-screen bg-slate-100 text-slate-700">
```

### Chart.js

Chart.js dipakai untuk grafik sensor pada elemen canvas:

```html
<canvas id="sensorChart"></canvas>
```

Dataset yang ditampilkan:

| Dataset | Field |
|---|---|
| Moisture % | `moisture_percent` |
| Water % | `water_level_percent` |
| Temperature °C | `temperature` |
| Humidity % | `humidity` |
| IKP | `ikp` |

---

## 6. API Browser Native

Dashboard memakai API bawaan browser tanpa library tambahan.

| API | Fungsi |
|---|---|
| `fetch()` | Mengambil data riwayat sensor dari `/api/sensor-readings/history?limit=50` |
| `setInterval()` | Menjalankan polling otomatis tiap 5 detik |
| `Date` | Format tanggal dan jam pembacaan sensor |
| DOM API | Mengubah isi KPI, detail, tabel, status live, dan chart |
| `console.error()` | Menampilkan error polling di browser console |

### Alasan Memakai `fetch()`

`fetch()` cukup untuk kebutuhan dashboard karena request hanya sederhana:

- Method `GET`.
- Header `Accept: application/json`.
- Tidak perlu interceptor.
- Tidak perlu konfigurasi global.

Karena itu, `axios` belum dipakai aktif meskipun sudah tersedia di `package.json`.

---

## 7. Library ESP32 / Arduino

Kode ESP32 memakai library Arduino/ESP32 untuk WiFi, HTTP request, HTTPS, dan sensor DHT.

| Library | Fungsi |
|---|---|
| `WiFi.h` | Menghubungkan ESP32 ke jaringan WiFi |
| `HTTPClient.h` | Mengirim HTTP POST ke Laravel API |
| `WiFiClientSecure.h` | Mendukung koneksi HTTPS saat production |
| `DHT.h` | Membaca sensor DHT22 untuk suhu dan humidity |

### Contoh Include

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include "DHT.h"
```

### Relasi dengan Laravel

ESP32 memakai `HTTPClient.h` untuk mengirim payload JSON ke endpoint:

```txt
POST /api/sensor-data
```

Laravel menerima payload tersebut, memvalidasi `api_key`, lalu menyimpan data ke tabel `sensor_readings`.

---

## 8. Dependency Tidak Langsung

Composer dan npm juga memasang dependency turunan secara otomatis. Dependency ini tidak ditulis langsung di `composer.json` atau `package.json`, tetapi dibutuhkan oleh package utama.

Contoh dependency turunan Composer:

- Symfony components.
- Illuminate components.
- PSR interfaces.
- PHPUnit components.

Contoh dependency turunan npm:

- Dependency internal Vite.
- Dependency plugin Laravel Vite.

Untuk daftar lengkap, lihat:

- `composer.lock`
- `package-lock.json` jika tersedia
- folder `vendor/`
- folder `node_modules/`

---

## 9. Library yang Terpasang tetapi Belum Dipakai Aktif

| Library | Alasan Belum Aktif |
|---|---|
| `laravel/sanctum` | Endpoint IoT saat ini memakai API key sederhana dari payload, bukan token Sanctum |
| `axios` | Dashboard memakai native `fetch()` untuk polling API |
| `laravel/sail` | Project bisa berjalan lokal tanpa Docker Sail |
| `fakerphp/faker` | Belum ada factory khusus untuk `SensorReading` |
| `mockery/mockery` | Belum ada test dengan mock object khusus |

Library ini tetap berguna jika project dikembangkan lebih lanjut.

---

## 10. Rekomendasi Pengembangan Library

### Jika Dashboard Makin Kompleks

Pertimbangkan memakai:

- Asset bundling Vite penuh.
- File JS terpisah untuk logic dashboard.
- `axios` jika butuh interceptor, base URL, atau error handling global.

### Jika API Dibuka ke Internet

Pertimbangkan memakai:

- `laravel/sanctum` untuk token per device.
- Rate limiting khusus endpoint ESP32.
- HTTPS wajib.

### Jika Testing Ditambah

Pertimbangkan membuat:

- Feature test untuk `POST /api/sensor-data`.
- Feature test untuk API key salah.
- Feature test untuk validasi payload.
- Factory `SensorReadingFactory` memakai `fakerphp/faker`.

---

## 11. Ringkasan Cepat

| Area | Library Utama |
|---|---|
| Backend | Laravel Framework |
| Database ORM | Eloquent ORM |
| API validation | Laravel Form Request |
| Dashboard view | Blade |
| Styling | TailwindCSS CDN |
| Chart | Chart.js CDN |
| Polling | Browser `fetch()` |
| Build frontend | Vite + Laravel Vite Plugin |
| Testing | PHPUnit |
| Formatting | Laravel Pint |
| ESP32 WiFi/API | `WiFi.h`, `HTTPClient.h`, `WiFiClientSecure.h` |
| ESP32 sensor | `DHT.h` |

---

## 12. Referensi File

| File | Keterangan |
|---|---|
| `composer.json` | Daftar library PHP production dan development |
| `package.json` | Daftar library JavaScript |
| `resources/views/dashboard.blade.php` | CDN TailwindCSS dan Chart.js, logic polling dashboard |
| `app/Http/Controllers/SensorDataController.php` | Pemakaian Laravel controller dan response JSON |
| `app/Http/Requests/StoreSensorReadingRequest.php` | Pemakaian Laravel Form Request validation |
| `app/Models/SensorReading.php` | Pemakaian Eloquent model |
