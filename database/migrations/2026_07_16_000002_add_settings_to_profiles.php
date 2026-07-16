<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('id');
            $table->unsignedTinyInteger('send_start_hour')->nullable()->after('email_body');
            $table->unsignedTinyInteger('send_end_hour')->nullable()->after('send_start_hour');
            $table->boolean('send_weekdays_only')->default(false)->after('send_end_hour');
            $table->unsignedInteger('max_emails_per_hour')->default(0)->after('send_weekdays_only');
            $table->unsignedTinyInteger('followup_days')->default(0)->after('max_emails_per_hour');
            $table->string('webhook_url')->nullable()->after('followup_days');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id', 'send_start_hour', 'send_end_hour', 'send_weekdays_only',
                'max_emails_per_hour', 'followup_days', 'webhook_url',
            ]);
        });
    }
};
