<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * item_batches = satu baris untuk satu barcode/QR yang aktif.
 *
 * Satu batch mewakili satu benda fisik yang dilacak:
 *   - batch_type = set        -> satu set alat yang dirakit (quantity selalu 1)
 *   - batch_type = individual -> sekumpulan alat sejenis dalam satu kemasan (quantity = jumlah pcs)
 *
 * Baris ini TIDAK PERNAH dihapus. Saat barcode diganti (mis. setelah alat bersih
 * dirakit ulang jadi satu set), baris lama berstatus 'superseded' dan ditautkan
 * ke baris baru lewat tabel item_batch_supersessions — supaya riwayat lintas
 * siklus tetap bisa ditelusuri saat audit kehilangan alat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_batches', function (Blueprint $table) {
            $table->id();
            $table->string('public_code', 32)->unique();
            $table->string('batch_type', 16)->index();
            $table->foreignId('instrument_set_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);

            $table->string('status', 40)->index();
            $table->timestamp('status_changed_at');

            $table->string('sterilization_method', 16)->nullable();
            $table->string('assembled_photo_path')->nullable();

            // Unit pemilik/asal — tetap melekat walau order sudah selesai,
            // supaya unit bisa menelusuri alatnya lintas siklus.
            $table->foreignId('origin_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('current_delivery_order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();

            $table->timestamps();

            $table->index(['origin_unit_id', 'status']);
            $table->index(['current_delivery_order_id', 'status']);
        });

        // Jejak audit per batch — inti bukti "alat terakhir ada di mana, jam berapa, oleh siapa".
        Schema::create('item_batch_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('input_method', 16)->default('manual');
            $table->string('station_context')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_admin_override')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['item_batch_id', 'occurred_at']);
        });

        // Penggantian barcode: banyak barcode lama boleh mengarah ke satu barcode baru
        // (mis. beberapa alat lepasan dirakit jadi satu set setelah bersih).
        Schema::create('item_batch_supersessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('old_item_batch_id')->unique()->constrained('item_batches')->cascadeOnDelete();
            $table->foreignId('new_item_batch_id')->constrained('item_batches')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('new_item_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_batch_supersessions');
        Schema::dropIfExists('item_batch_events');
        Schema::dropIfExists('item_batches');
    }
};
