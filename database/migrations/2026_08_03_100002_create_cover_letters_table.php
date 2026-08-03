<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cover_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('file_path')->nullable();
            $table->text('text')->nullable();
            $table->string('mode')->default('file'); // 'file' | 'text'
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Migrate the existing single cover letter slot from profiles into its own row per user.
        $profiles = DB::table('profiles')
            ->where(function ($q) {
                $q->whereNotNull('cover_letter_path')->orWhereNotNull('cover_letter_text');
            })
            ->get();

        foreach ($profiles as $profile) {
            $isFile = filled($profile->cover_letter_path);

            DB::table('cover_letters')->insert([
                'user_id' => $profile->user_id,
                'name' => $isFile ? ($profile->cover_letter_name ?? 'Cover Letter') : 'Cover Letter',
                'file_path' => $isFile ? $profile->cover_letter_path : null,
                'text' => $isFile ? null : $profile->cover_letter_text,
                'mode' => $isFile ? 'file' : 'text',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cover_letters');
    }
};
