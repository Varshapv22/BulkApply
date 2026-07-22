<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('skills');
            $table->string('linkedin_url')->nullable()->after('bio');
            $table->string('portfolio_url')->nullable()->after('linkedin_url');
            $table->string('photo_path')->nullable()->after('portfolio_url');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['bio', 'linkedin_url', 'portfolio_url', 'photo_path']);
        });
    }
};
