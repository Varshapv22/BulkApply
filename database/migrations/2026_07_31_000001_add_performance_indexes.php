<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->index('send_batch_id');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('api_request_logs', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('login_histories', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex(['send_batch_id']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('api_request_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('login_histories', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
