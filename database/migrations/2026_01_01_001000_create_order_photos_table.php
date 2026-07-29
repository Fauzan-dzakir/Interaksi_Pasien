<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto barang pada pesanan cuci.
 *
 * Unit tidak melakukan pendataan barang saat memesan, cukup melampirkan
 * beberapa foto alat kotor yang dikirim. Foto inilah bukti awal isi kiriman
 * bila kemudian ada alat yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('photo_path');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_photos');
    }
};
