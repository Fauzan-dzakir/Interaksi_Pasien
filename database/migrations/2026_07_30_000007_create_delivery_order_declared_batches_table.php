<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deklarasi alat oleh unit saat membuat order — unit memilih dari alat yang
 * SUDAH ditandai dipakai (bukan mengetik bebas), supaya ada rujukan pembanding
 * saat CSSD menghitung fisik isi kiriman. TIDAK menggantikan pendataan fisik
 * CSSD (yang tetap independen) — ini murni referensi/deklarasi dari sisi unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_order_declared_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_batch_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['delivery_order_id', 'item_batch_id'], 'do_declared_batches_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_declared_batches');
    }
};
