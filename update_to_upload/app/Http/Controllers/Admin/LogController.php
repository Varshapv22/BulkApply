<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $path = storage_path('logs/laravel.log');
        $level = $request->input('level');

        $lines = [];
        if (is_readable($path)) {
            // Read the tail of the file only — this log can grow large over time.
            $size = filesize($path);
            $handle = fopen($path, 'r');
            fseek($handle, max(0, $size - 500_000));
            $chunk = fread($handle, 500_000);
            fclose($handle);

            $entries = preg_split('/(?=\[\d{4}-\d{2}-\d{2})/', $chunk, -1, PREG_SPLIT_NO_EMPTY);
            $lines = collect($entries)->reverse()->take(200)->values();

            if ($level) {
                $lines = $lines->filter(fn ($l) => stripos($l, ".{$level}:") !== false || stripos($l, "{$level}:") !== false)->values();
            }
        }

        return Inertia::render('Admin/Logs/Index', [
            'lines' => $lines,
            'level' => $level,
            'exists' => is_readable($path),
        ]);
    }
}
