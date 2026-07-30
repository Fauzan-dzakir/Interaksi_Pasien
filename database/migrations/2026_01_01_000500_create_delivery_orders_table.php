<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiket pengiriman alat kotor dari unit ke CSSD.
 *
 * Saat dibuat unit, isinya HANYA informasi dasar (petugas pengantar, jam kirim,
 * jumlah box) — rincian alat sengaja tidak diminta di tahap ini karena
 * pendataan fisik adalah tugas dan tanggung jawab CSSD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->foreignId('origin_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('submitted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('courier_name');
            $table->timestamp('sent_at');
            $table->unsignedInteger('box_count')->default(1);
            $table->string('status', 32)->index();
            $table->text('notes')->nullable();
            $table->timestamp('intake_recorded_at')->nullable();
            $table->foreignId('intake_recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['origin_unit_id', 'status']);
        });

        // Jejak audit level order — append-only, tidak pernah di-update/hapus.
        Schema::create('delivery_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['delivery_order_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_events');
        Schema::dropIfExists('delivery_orders');
    }
};
