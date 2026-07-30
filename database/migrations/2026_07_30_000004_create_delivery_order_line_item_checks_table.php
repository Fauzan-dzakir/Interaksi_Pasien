<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checklist kelengkapan isi set saat CSSD mendata baris "Per Set" —
 * tiap alat anggota set dicatat apakah sesuai/ada, beserta catatan opsional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_order_line_item_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_line_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->boolean('is_present')->default(true);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            // Nama index diberi eksplisit — nama bawaan Laravel untuk kombinasi ini
            // melebihi batas 64 karakter identifier MySQL/MariaDB.
            $table->unique(['delivery_order_line_id', 'item_id'], 'do_line_item_checks_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_line_item_checks');
    }
};
