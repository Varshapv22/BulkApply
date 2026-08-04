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
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->index('read_at');
        });

        Schema::table('login_histories', function (Blueprint $table) {
            $table->index('email');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('plan_payment_requests', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropIndex(['read_at']);
        });

        Schema::table('login_histories', function (Blueprint $table) {
            $table->dropIndex(['email']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('plan_payment_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
