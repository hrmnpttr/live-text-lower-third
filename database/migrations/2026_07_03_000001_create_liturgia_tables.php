<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tema tampilan — warna liturgi + gaya aksen + logo/watermark
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                  // "Adven", "Masa Biasa", "HUT Paroki"
            $table->string('color_key')->default('hijau');           // merah|hijau|putih|ungu|pink|custom
            $table->string('accent')->default('#c9b878');            // warna aksen (emas dsb)
            $table->string('bg_tint')->default('rgba(13,27,46,.92)');// tint background box/layar
            $table->string('accent_style')->default('garis');        // garis|bulat
            $table->string('watermark')->default('salib');           // salib|none
            $table->string('logo_path')->nullable();                 // upload logo paroki/event
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Bank konten: lagu, doa, mazmur, bacaan, ordinarium, pengumuman
        Schema::create('library_items', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();          // lagu|doa|mazmur|bacaan|ordinarium|pengumuman|lainnya
            $table->string('code')->nullable();       // "PS 326", "MB 401"
            $table->string('title');
            $table->string('set_name')->nullable();   // set ordinarium: "Misa Kita 1" dst
            $table->json('sections')->nullable();     // [{name, notation, body}]
            $table->json('tags')->nullable();
            $table->timestamps();
        });

        // Event misa / ibadah
        Schema::create('masses', function (Blueprint $table) {
            $table->id();
            $table->string('title');                       // "Misa 2 — Hari Minggu Biasa XIV"
            $table->dateTime('celebrated_at')->nullable();
            $table->string('priest')->nullable();          // romo selebran
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_template')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Rundown per misa
        Schema::create('mass_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mass_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->string('header')->nullable();          // "LAGU PEMBUKA", "MAZMUR TANGGAPAN"
            $table->string('title')->nullable();           // "Mari Menghadap Altar Tuhan"
            $table->foreignId('library_item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('section_index')->nullable(); // ambil satu section saja dari bank
            $table->longText('body')->nullable();          // teks manual, blok dipisah baris kosong
            $table->text('notation')->nullable();          // markup not angka (not:/syl:)
            $table->string('display')->default('both');    // both|full|lower
            $table->boolean('title_only')->default(false); // tampil judul saja (mis. bacaan)
            $table->timestamps();
            $table->index(['mass_id', 'sort']);
        });

        // State live tunggal — apa yang sedang tayang
        Schema::create('live_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mass_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('item_index')->default(0);
            $table->unsignedInteger('block_index')->default(0);
            $table->string('mode')->default('both');       // both|full|lower|clear
            $table->string('preset')->default('scrim');    // transparan|scrim|glass|solid
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->json('quick')->nullable();             // {header, text, target}
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_states');
        Schema::dropIfExists('mass_items');
        Schema::dropIfExists('masses');
        Schema::dropIfExists('library_items');
        Schema::dropIfExists('themes');
    }
};
