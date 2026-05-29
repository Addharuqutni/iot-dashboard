<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Menentukan pola host yang dipercaya oleh aplikasi.
     *
     * Function ini membantu Laravel membatasi host valid, termasuk semua
     * subdomain dari APP_URL jika konfigurasi tersebut digunakan.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        return [
            $this->allSubdomainsOfApplicationUrl(),
        ];
    }
}
