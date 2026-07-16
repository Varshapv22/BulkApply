<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('pipeline_status')->default('applied')->after('status');
            $table->uuid('tracking_id')->nullable()->unique()->after('notes');
            $table->timestamp('opened_at')->nullable()->after('sent_at');
            $table->timestamp('clicked_at')->nullable()->after('opened_at');
            $table->timestamp('followup_at')->nullable()->after('clicked_at');
            $table->unsignedTinyInteger('followup_count')->default(0)->after('followup_at');
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete()->after('id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('id');

            $table->index('pipeline_status');
            $table->index('followup_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropForeign(['email_template_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'pipeline_status', 'tracking_id', 'opened_at', 'clicked_at',
                'followup_at', 'followup_count', 'email_template_id', 'user_id',
            ]);
        });
    }
};
