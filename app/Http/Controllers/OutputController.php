<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OutputController extends Controller
{
    public function full()
    {
        return view('output.full', ['client' => $this->clientConfig()]);
    }

    public function lower()
    {
        return view('output.lower', ['client' => $this->clientConfig()]);
    }

    public function check(Request $request)
    {
        return view('output.check', ['client' => $this->clientConfig()]);
    }

    /** Konfigurasi websocket yang dibaca halaman output/kontrol. */
    public static function clientConfig(): array
    {
        $reverb = config('broadcasting.connections.reverb.options', []);

        return [
            'key' => config('broadcasting.connections.reverb.key', env('REVERB_APP_KEY')),
            'port' => (int) ($reverb['port'] ?? env('REVERB_PORT', 8080)),
        ];
    }
}
