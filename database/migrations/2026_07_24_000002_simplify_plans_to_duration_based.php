<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->default(30)->after('price');
        });

        // Map the existing four demo plans onto duration-only tiers, keeping their IDs
        // (and therefore existing subscriptions/payment requests) intact.
        $renames = [
            'Free'       => ['name' => 'Free', 'duration_days' => 7],
            'Basic'      => ['name' => '1 Month', 'duration_days' => 30],
            'Pro'        => ['name' => '3 Month', 'duration_days' => 90],
            'Enterprise' => ['name' => '9 Month', 'duration_days' => 270],
        ];

        foreach ($renames as $oldName => $data) {
            DB::table('plans')->where('name', $oldName)->update($data);
        }

        // Plans no longer differ by feature/quota — every plan is now unlimited across the board.
        DB::table('plans')->update([
            'email_limit' => null,
            'resume_limit' => null,
        ]);

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'billing_interval',
                'daily_application_limit',
                'queue_priority',
                'storage_limit_mb',
                'chrome_extension_access',
                'ats_checker_access',
                'api_access',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('billing_interval')->default('monthly')->after('price');
            $table->unsignedInteger('daily_application_limit')->nullable();
            $table->unsignedInteger('queue_priority')->default(0);
            $table->unsignedInteger('storage_limit_mb')->nullable();
            $table->boolean('chrome_extension_access')->default(true);
            $table->boolean('ats_checker_access')->default(true);
            $table->boolean('api_access')->default(true);
            $table->dropColumn('duration_days');
        });
    }
};
