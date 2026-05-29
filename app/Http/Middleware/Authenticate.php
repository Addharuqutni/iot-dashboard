<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Menentukan tujuan redirect saat user belum login.
     *
     * Function ini mengembalikan null untuk request JSON agar API tidak
     * diarahkan ke halaman login, dan route login untuk request web biasa.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
