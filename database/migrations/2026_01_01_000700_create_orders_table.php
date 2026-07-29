<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesanan alat steril oleh unit, alur checkout seperti marketplace.
 *
 * Unit memilih dari available stock (atau memesan ulang isi batch lamanya),
 * lalu CSSD mengalokasikan aset konkret dan menyiapkannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();

            $table->string('status', 32)->index();
            $table->string('fulfillment_method', 16);
            $table->timestamp('needed_at')->nullable();
            $table->text('notes')->nullable();

            // Serah terima: foto jadi bukti alat benar-benar berpindah tangan.
            $table->foreignId('prepared_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('prepared_at')->nullable();
            $table->string('handover_photo_path')->nullable();
            $table->string('receiver_name')->nullable();
            $table->timestamp('handed_over_at')->nullable();

            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();

            $table->timestamps();

            $table->index(['unit_id', 'status']);
        });

        // Baris permintaan: unit memesan per JENIS, aset konkret dipilih CSSD saat menyiapkan.
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('line_type', 16);
            $table->foreignId('instrument_set_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity_requested')->default(1);
            $table->unsignedInteger('quantity_fulfilled')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });

        // Aset konkret yang dialokasikan CSSD untuk memenuhi pesanan.
        Schema::create('order_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'asset_id']);
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['order_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('order_assets');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
