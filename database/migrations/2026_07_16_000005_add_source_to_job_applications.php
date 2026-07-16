<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('source')->nullable()->after('notes');
            $table->string('apply_type')->default('email')->after('source'); // email or link
            $table->string('apply_url')->nullable()->after('apply_type');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['source', 'apply_type', 'apply_url']);
        });
    }
};
