<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Last time the scheduled/manual Gmail sync ran for this account —
            // throttles the automatic sync and drives the "last synced" label.
            $table->timestamp('gmail_synced_at')->nullable()->after('mail_from_name');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('gmail_synced_at');
        });
    }
};
