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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2)->default(0);
            $table->string('billing_interval')->default('monthly'); // monthly | yearly
            $table->unsignedInteger('email_limit')->nullable(); // null = unlimited
            $table->unsignedInteger('resume_limit')->nullable();
            $table->unsignedInteger('daily_application_limit')->nullable();
            $table->unsignedInteger('queue_priority')->default(0);
            $table->unsignedInteger('storage_limit_mb')->nullable();
            $table->boolean('chrome_extension_access')->default(true);
            $table->boolean('ats_checker_access')->default(true);
            $table->boolean('api_access')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
