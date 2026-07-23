<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->timestamp('applied_at')->nullable()->after('sent_at');
            $table->string('auto_apply_status')->nullable()->after('applied_at');
            $table->text('auto_apply_error')->nullable()->after('auto_apply_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['applied_at', 'auto_apply_status', 'auto_apply_error']);
        });
    }
};
