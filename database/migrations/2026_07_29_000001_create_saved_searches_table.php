<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->string('location')->nullable();
            $table->string('site')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_baselined')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'last_checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};
