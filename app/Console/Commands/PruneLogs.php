<?php

namespace App\Console\Commands;

use App\Models\ApiRequestLog;
use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\WebhookLog;
use Illuminate\Console\Command;

class PruneLogs extends Command
{
    protected $signature = 'app:prune-logs {--days=90 : Delete log rows older than this many days}';
    protected $description = 'Delete old rows from log/audit tables that otherwise grow unbounded';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $deleted = [
            'api_request_logs' => ApiRequestLog::where('created_at', '<', $cutoff)->delete(),
            'login_histories'  => LoginHistory::where('created_at', '<', $cutoff)->delete(),
            'audit_logs'       => AuditLog::where('created_at', '<', $cutoff)->delete(),
            'webhook_logs'     => WebhookLog::where('created_at', '<', $cutoff)->delete(),
        ];

        foreach ($deleted as $table => $count) {
            $this->info("Pruned {$count} row(s) from {$table}.");
        }

        return self::SUCCESS;
    }
}
