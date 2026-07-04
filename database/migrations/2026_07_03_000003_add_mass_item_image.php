<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mass_items', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('notation'); // gambar full layar (thumbnail, poster)
        });
    }

    public function down(): void
    {
        Schema::table('mass_items', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
