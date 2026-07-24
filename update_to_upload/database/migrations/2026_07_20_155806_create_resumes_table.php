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
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('file_path');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Migrate existing resumes from profiles table
        $profiles = \Illuminate\Support\Facades\DB::table('profiles')->whereNotNull('resume_path')->get();
        foreach ($profiles as $profile) {
            \Illuminate\Support\Facades\DB::table('resumes')->insert([
                'user_id' => $profile->user_id,
                'name' => $profile->resume_name ?? 'Default Resume',
                'file_path' => $profile->resume_path,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resumes');
    }
};
