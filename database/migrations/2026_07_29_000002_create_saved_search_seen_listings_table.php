<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_search_seen_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_search_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint', 64);
            $table->timestamp('first_seen_at');

            $table->unique(['saved_search_id', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_search_seen_listings');
    }
};
