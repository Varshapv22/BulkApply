<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Per-user outbound email credentials — each account sends its own
            // job applications through its own Gmail, never a shared one.
            $table->string('mail_username')->nullable()->after('webhook_url');
            $table->text('mail_password')->nullable()->after('mail_username'); // encrypted at rest
            $table->string('mail_from_name')->nullable()->after('mail_password');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['mail_username', 'mail_password', 'mail_from_name']);
        });
    }
};
