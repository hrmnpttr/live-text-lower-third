<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_states', function (Blueprint $table) {
            $table->string('align')->default('center')->after('preset'); // left|center|right
        });
    }

    public function down(): void
    {
        Schema::table('live_states', function (Blueprint $table) {
            $table->dropColumn('align');
        });
    }
};
