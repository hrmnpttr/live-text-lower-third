<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->string('background_path')->nullable()->after('logo_path');  // gambar background full layar
            $table->string('watermark_path')->nullable()->after('background_path'); // krusifiks ber-corpus / logo gereja (PNG transparan)
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['background_path', 'watermark_path']);
        });
    }
};
