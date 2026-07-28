<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Serah-terima alat steril dari CSSD kembali ke unit.
 * Mencakup dua jalur pada alur: diambil sendiri oleh unit, atau "Dikirim Langsung" oleh CSSD.
 *
 * Alat yang diambil dalam satu kali serah-terima dikelompokkan sebagai satu batch pengambilan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickups', function (Blueprint $table) {
            $table->id();
            $table->string('pickup_number', 32)->unique();
            $table->foreignId('origin_unit_id')->constrained('units')->restrictOnDelete();
            $table->string('delivery_method', 24);
            $table->foreignId('dispatched_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('dispatched_at');
            $table->string('receiver_name')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['origin_unit_id', 'confirmed_at']);
        });

        Schema::create('pickup_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_batch_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['pickup_id', 'item_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_items');
        Schema::dropIfExists('pickups');
    }
};
