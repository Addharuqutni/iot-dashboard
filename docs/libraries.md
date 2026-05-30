# Dokumentasi Library Project

Dokumen ini menjelaskan library, package, dan API yang digunakan pada project IoT Dashboard Monitoring Tanaman.

---

## 1. Ringkasan

Project memakai beberapa jenis dependency:

- Library backend dari Composer (`composer.json`).
- Library development dan testing dari Composer (`require-dev`).
- Library frontend build dari npm (`package.json`).
- Library frontend CDN langsung dari Blade.
- API browser native untuk polling dashboard dan evaluasi.
- Library ESP32/Arduino untuk pengiriman data sensor + sinkron NTP.

---

## 2. Backend PHP / Laravel

Daftar library utama backend dari `composer.json` bagian `require`.

| Library | Versi | Status | Fungsi |
|---|---:|---|---|
| `php` | `^8.1` | Wajib | Runtime utama |
| `laravel/framework` | `^10.10` | Wajib | Framework utama: routing, controller, form request, Eloquent ORM, config, migration, Blade, service container |
| `laravel/sanctum` | `^3.3` | Terpasang | Auth token; **belum dipakai aktif** pada endpoint IoT (auth saat ini pakai API key sederhana di payload) |
| `laravel/tinker` | `^2.8` | Terpasang | REPL untuk debug manual + generate API key (`Str::random(64)`) |
| `guzzlehttp/guzzle` | `^7.2` | Terpasang | HTTP client PHP, dependency umum Laravel |
| `nesbot/carbon` | bawaan Laravel | Aktif | **Manipulasi tanggal/waktu**: dipakai `EvaluationService` untuk window range, parsing `sent_at` dengan timezone device, dan diff untuk delay |

### Pemakaian Laravel di Project

| Fitur | Library / Komponen |
|---|---|
| Routing API + Web | `Illuminate\Support\Facades\Route` |
| Controller | `App\Http\Controllers\*` |
| Form Request validation | `Illuminate\Foundation\Http\FormRequest`, `Illuminate\Validation\Rule` |
| Model `SensorReading` | Eloquent ORM |
| Migration database | Schema builder Laravel |
| Service container | `EvaluationService` di-inject ke `EvaluationController` lewat constructor |
| View dashboard + evaluasi | Blade |
| Konfigurasi | `config/services.php` (`iot.api_key`, `iot.device_timezone`) |
| Generate API key manual | `laravel/tinker` |
| Timezone handling | `Carbon::parse($value, $deviceTimezone)->utc()` |

---

## 3. Development dan Testing PHP

Daftar library dari `composer.json` bagian `require-dev`.

| Library | Versi | Status | Fungsi |
|---|---:|---|---|
| `fakerphp/faker` | `^1.9.1` | Tersedia | Data dummy untuk factory; saat ini factory `SensorReadingFactory` masih pakai value statis, bisa diganti `$this->faker` jika butuh randomisasi |
| `laravel/pint` | `^1.0` | Tersedia | Formatter PHP gaya Laravel |
| `laravel/sail` | `^1.18` | Tersedia | Environment Docker, project bisa jalan tanpa Sail |
| `mockery/mockery` | `^1.4.4` | Tersedia | Mock object saat testing |
| `nunomaduro/collision` | `^7.0` | Aktif | Tampilan error CLI |
| `phpunit/phpunit` | `^10.1` | **Aktif** | Framework testing untuk unit + feature test |
| `spatie/laravel-ignition` | `^2.0` | Aktif | Halaman error/debug saat dev |

### Test yang Sudah Berjalan

| File | Library yang Dipakai |
|---|---|
| `tests/Feature/SensorDataControllerTest.php` | `phpunit/phpunit`, `RefreshDatabase`, `Carbon::setTestNow`, `postJson` helper |
| `tests/Unit/EvaluationServiceTest.php` | `phpunit/phpunit`, `RefreshDatabase`, `Carbon::setTestNow`, `SensorReading::factory()`, `app(EvaluationService::class)` |

### Konfigurasi Test

`phpunit.xml` memakai SQLite in-memory:

```xml
<env name="DB_CONNECTION" value="sqlite" force="true"/>
<env name="DB_DATABASE" value=":memory:" force="true"/>
```

Tidak butuh MySQL untuk jalankan `php artisan test`.

---

## 4. JavaScript / Frontend Build

Daftar library dari `package.json`.

| Library | Versi | Status | Fungsi |
|---|---:|---|---|
| `vite` | `^5.0.0` | Tersedia | Build tool frontend |
| `laravel-vite-plugin` | `^1.0.0` | Tersedia | Integrasi Vite dengan Laravel |
| `axios` | `^1.6.4` | Tersedia | HTTP client; **belum dipakai aktif** karena dashboard memakai native `fetch()` |

### Catatan

Dashboard dan halaman evaluasi memakai TailwindCSS + Chart.js dari CDN, bukan bundle Vite. Vite tetap tersedia untuk pengembangan asset frontend kustom di masa depan.

---

## 5. Library Frontend CDN

Library ini dipanggil langsung pada Blade.

```html
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

| Library | Sumber | Dipakai di | Fungsi |
|---|---|---|---|
| TailwindCSS | `cdn.tailwindcss.com` | `dashboard.blade.php`, `evaluation.blade.php` | Styling utility-first |
| Chart.js | `cdn.jsdelivr.net/npm/chart.js` | `dashboard.blade.php` | Grafik garis sensor |

### TailwindCSS

Dipakai untuk:

- Layout responsive (grid, flex).
- Card KPI dashboard.
- Tabel metrik evaluasi.
- Card status (Online, Value Tampil, Threshold Stale).
- Badge status koneksi (Live/Disconnected).
- Empty state.

### Chart.js

Dipakai untuk grafik sensor di dashboard:

```html
<canvas id="sensorChart"></canvas>
```

| Dataset | Field | Warna |
|---|---|---|
| Moisture % | `moisture_percent` | Hijau |
| Water % | `water_level_percent` | Biru |
| Temperature °C | `temperature` | Kuning |
| Humidity % | `humidity` | Ungu |
| IKP | `ikp` | Merah |

Halaman evaluasi (`/evaluation`) **tidak** memakai Chart.js — hanya Tailwind untuk tabel + card.

---

## 6. API Browser Native

Dashboard dan halaman evaluasi memakai API bawaan browser tanpa library tambahan.

| API | Dipakai di | Fungsi |
|---|---|---|
| `fetch()` | dashboard, evaluation | GET `/api/sensor-readings/history?limit=50` dan `/api/evaluation/metrics` |
| `setInterval()` | dashboard, evaluation | Polling 5 detik |
| `Date` | dashboard, evaluation | Format tanggal `created_at` dan `generated_at` |
| DOM API | dashboard, evaluation | Update KPI, detail, tabel, status, chart |
| `console.error()` | dashboard, evaluation | Log error polling |

### Alasan Memakai `fetch()`

`fetch()` cukup karena:

- Hanya method `GET`.
- Header `Accept: application/json`.
- Tidak butuh interceptor atau base URL.

Karena itu `axios` belum dipakai aktif walau sudah ada di `package.json`.

---

## 7. Library ESP32 / Arduino

Kode ESP32 memakai library Arduino/ESP32 untuk WiFi, HTTP, HTTPS, sinkron NTP, dan sensor DHT.

| Library | Fungsi | Relevansi Field Evaluasi |
|---|---|---|
| `WiFi.h` | Connect ke WiFi | — |
| `HTTPClient.h` | POST JSON ke Laravel | Mengirim payload sensor + `sent_at` + `total_sent` |
| `WiFiClientSecure.h` | Koneksi HTTPS production | — |
| `time.h` / `configTime()` | **Sinkron NTP** | Mengisi `sent_at` dengan waktu akurat (WIB, GMT+7) |
| `DHT.h` | Baca DHT22 | Mengisi `temperature`, `humidity`, `dht_ok` |

### Contoh Include

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <time.h>
#include "DHT.h"
```

### Konfigurasi NTP (WIB)

```cpp
const long GMT_OFFSET_SEC = 7 * 3600;   // WIB
const int  DAYLIGHT_OFFSET_SEC = 0;
configTime(GMT_OFFSET_SEC, DAYLIGHT_OFFSET_SEC, "pool.ntp.org", "time.nist.gov");
```

Format `sent_at` yang dikirim: `"Y-m-d H:i:s"` waktu lokal device. Server parse pakai `config('services.iot.device_timezone')`. Default Laravel = `Asia/Jakarta`, jadi cocok dengan firmware default.

### Counter `total_sent`

Counter di-increment setiap pengiriman packet (termasuk yang gagal lalu retry). Nilai disimpan di `device_total_sent` di server dan dipakai untuk hitung PDR.

### Relasi dengan Laravel

ESP32 kirim payload JSON ke:

```txt
POST /api/sensor-data
```

Laravel:

1. Validasi `api_key` (`hash_equals`).
2. Parse `sent_at` pakai timezone device → konversi UTC.
3. Hitung `delay_ms = max(0, now() - sent_at)`.
4. Map alias firmware lama (`device_timestamp` → `sent_at`, `total_sent` → `device_total_sent`, fallback `sequence_no`).
5. Simpan ke `sensor_readings`.

---

## 8. Dependency Tidak Langsung

Composer dan npm memasang dependency turunan otomatis.

Contoh dependency turunan Composer:

- Symfony components (HttpFoundation, Console, dll).
- Illuminate components (Database, Routing, dll).
- PSR interfaces.
- PHPUnit components (`phpunit/php-code-coverage`, dll).
- `nesbot/carbon` (bawaan Laravel, dipakai langsung di `EvaluationService`).

Contoh dependency turunan npm:

- Dependency internal Vite (`esbuild`, `rollup`).
- Dependency `laravel-vite-plugin`.

Lihat:

- `composer.lock`
- `package-lock.json` (jika tersedia)
- `vendor/`
- `node_modules/`

---

## 9. Library yang Terpasang tetapi Belum Dipakai Aktif

| Library | Alasan Belum Aktif | Kapan Pertimbangkan Pakai |
|---|---|---|
| `laravel/sanctum` | Endpoint IoT pakai API key sederhana di payload | Multi-device dengan token per device, atau dashboard publik dengan login |
| `axios` | Dashboard pakai native `fetch()` | Butuh interceptor, base URL, error handling global, retry otomatis |
| `laravel/sail` | Project bisa jalan tanpa Docker | Standardisasi environment dev tim |
| `fakerphp/faker` | Factory pakai value statis | Test butuh data variatif (multi-device, multi-window) |
| `mockery/mockery` | Belum ada test dengan mock | Mocking external service (notifikasi, queue) |

---

## 10. Rekomendasi Pengembangan Library

### Jika Dashboard Makin Kompleks

- Asset bundling Vite penuh.
- File JS terpisah per halaman (`dashboard.js`, `evaluation.js`).
- `axios` dengan interceptor untuk error handling global.
- Chart.js juga di halaman evaluasi (grafik PDR/delay over time).

### Jika API Dibuka ke Internet

- `laravel/sanctum` token per device.
- Rate limiting endpoint `/api/sensor-data` (`throttle` middleware).
- HTTPS wajib.
- Auth ringan (basic / signed URL) untuk endpoint baca + evaluasi.

### Jika Testing Diperluas

- Feature test `/api/evaluation/metrics` (return shape + edge case).
- Test alias firmware lama (`device_timestamp`, fallback `sequence_no`).
- Test `IOT_DEVICE_TIMEZONE` non-WIB.
- Pakai `$this->faker` di `SensorReadingFactory` untuk variasi data.
- Mock `Carbon::setTestNow()` skenario disconnect → reconnect berurutan.

### Jika Real-time

- Laravel Reverb / Pusher menggantikan polling.
- Channel broadcast tiap kali `SensorReading` dibuat (Eloquent event).

---

## 11. Ringkasan Cepat

| Area | Library Utama |
|---|---|
| Backend framework | Laravel 10 |
| Database ORM | Eloquent |
| API validation | Laravel Form Request |
| Datetime / timezone | Carbon (bawaan Laravel) |
| Service layer | `EvaluationService` (POPO + service container) |
| Dashboard view | Blade |
| Halaman evaluasi | Blade |
| Styling | TailwindCSS CDN |
| Chart | Chart.js CDN |
| Polling | Browser `fetch()` + `setInterval` |
| Build frontend | Vite + Laravel Vite Plugin |
| Testing | PHPUnit (SQLite in-memory) |
| Test factory | `SensorReadingFactory` (Eloquent factory + Faker) |
| Formatting | Laravel Pint |
| ESP32 WiFi/API | `WiFi.h`, `HTTPClient.h`, `WiFiClientSecure.h` |
| ESP32 sensor | `DHT.h` |
| ESP32 waktu | `time.h` / `configTime` (NTP) |

---

## 12. Referensi File

| File | Keterangan |
|---|---|
| `composer.json` | Daftar library PHP production + development |
| `package.json` | Daftar library JavaScript |
| `phpunit.xml` | Konfigurasi PHPUnit (SQLite in-memory) |
| `resources/views/dashboard.blade.php` | CDN TailwindCSS + Chart.js, polling fetch |
| `resources/views/evaluation.blade.php` | CDN TailwindCSS, polling metrik evaluasi |
| `app/Http/Controllers/SensorDataController.php` | Pakai `Carbon::parse(...)->utc()` + `diffInMilliseconds()` |
| `app/Http/Controllers/EvaluationController.php` | Inject `EvaluationService` via constructor |
| `app/Services/EvaluationService.php` | Pakai `Carbon::now()`, `subMinutes`, `startOfDay`, `diffInSeconds` + `SensorReading` Eloquent query |
| `app/Http/Requests/StoreSensorReadingRequest.php` | Pakai `Rule::in(...)` + `hash_equals` |
| `app/Models/SensorReading.php` | Pakai `HasFactory` trait dari Eloquent |
| `database/factories/SensorReadingFactory.php` | Eloquent factory (Faker tersedia kalau dibutuhkan) |
| `tests/Feature/SensorDataControllerTest.php` | PHPUnit + `RefreshDatabase` + `Carbon::setTestNow` |
| `tests/Unit/EvaluationServiceTest.php` | PHPUnit + `RefreshDatabase` + `app(EvaluationService::class)` |
