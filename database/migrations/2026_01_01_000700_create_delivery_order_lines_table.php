<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil pendataan fisik CSSD atas isi satu order ("Pendataan Batch per Order dari Unit").
 * Setiap baris adalah jalur "Per Set" atau "Per Barang".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained()->cascadeOnDelete();
            $table->string('line_type', 16);
            $table->foreignId('instrument_set_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index('delivery_order_id');
        });

        // Tautan baris pendataan -> batch yang dihasilkan, agar bisa ditelusuri dua arah.
        Schema::table('item_batches', function (Blueprint $table) {
            $table->foreignId('delivery_order_line_id')
                ->nullable()
                ->after('current_delivery_order_id')
                ->constrained('delivery_order_lines')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('item_batches', function (Blueprint $table) {
            $table->dropForeign(['delivery_order_line_id']);
            $table->dropColumn('delivery_order_line_id');
        });

        Schema::dropIfExists('delivery_order_lines');
    }
};
