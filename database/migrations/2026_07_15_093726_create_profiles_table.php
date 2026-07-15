<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-row profile: the applicant's identity, resume/cover-letter files,
     * and the email template used for every application.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('resume_name')->nullable();
            $table->string('cover_letter_path')->nullable();
            $table->string('cover_letter_name')->nullable();
            $table->string('email_subject')->default('Application for {job_title} at {company}');
            $table->text('email_body')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
