<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MonitoringController extends Controller
{
    public function index()
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : null;

        return Inertia::render('Admin/Monitoring/Index', [
            'cpuLoad' => $load ? ['1min' => round($load[0], 2), '5min' => round($load[1], 2), '15min' => round($load[2], 2)] : null,
            'memory' => $this->memoryInfo(),
            'disk' => [
                'free_gb' => round(@disk_free_space('/') / 1024 / 1024 / 1024, 1),
                'total_gb' => round(@disk_total_space('/') / 1024 / 1024 / 1024, 1),
            ],
            'database' => $this->databaseStatus(),
            'queue' => [
                'connection' => config('queue.default'),
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
            ],
            'redis' => 'Not configured — this app runs the database queue driver.',
        ]);
    }

    private function memoryInfo(): ?array
    {
        if (!is_readable('/proc/meminfo')) {
            return null;
        }

        $lines = file('/proc/meminfo');
        $data = [];
        foreach ($lines as $line) {
            if (preg_match('/^(MemTotal|MemAvailable):\s+(\d+)/', $line, $m)) {
                $data[$m[1]] = (int) $m[2];
            }
        }

        if (!isset($data['MemTotal'], $data['MemAvailable'])) {
            return null;
        }

        return [
            'total_mb' => round($data['MemTotal'] / 1024),
            'available_mb' => round($data['MemAvailable'] / 1024),
            'used_percent' => round((1 - $data['MemAvailable'] / $data['MemTotal']) * 100),
        ];
    }

    private function databaseStatus(): array
    {
        try {
            DB::connection()->getPdo();
            return ['connected' => true, 'driver' => config('database.default')];
        } catch (\Throwable $e) {
            return ['connected' => false, 'driver' => config('database.default'), 'error' => $e->getMessage()];
        }
    }
}
