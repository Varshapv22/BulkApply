<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class DatabaseToolController extends Controller
{
    // Deliberately excludes route:cache/config:cache/optimize: this app has at
    // least one closure-based route (the /extension route in routes/web.php),
    // which route:cache cannot serialize and would break at runtime. The
    // :clear variants below are always safe regardless of that.
    private const ACTIONS = [
        'cache:clear' => 'Clear application cache',
        'config:clear' => 'Clear config cache',
        'route:clear' => 'Clear route cache',
        'view:clear' => 'Clear compiled views',
        'queue:restart' => 'Restart queue workers',
        'storage:link' => 'Re-link public storage',
        'migrate' => 'Run pending migrations',
    ];

    public function index()
    {
        Artisan::call('migrate:status');

        return Inertia::render('Admin/DatabaseTools/Index', [
            'actions' => self::ACTIONS,
            'migrationStatus' => Artisan::output(),
        ]);
    }

    public function run(string $action)
    {
        if (!array_key_exists($action, self::ACTIONS)) {
            abort(404);
        }

        Artisan::call($action);
        $output = trim(Artisan::output());
        AuditLog::record('db_tool.run', null, ['command' => $action]);

        return back()->with('status', self::ACTIONS[$action] . ' — ' . ($output ?: 'done') . '.');
    }
}
