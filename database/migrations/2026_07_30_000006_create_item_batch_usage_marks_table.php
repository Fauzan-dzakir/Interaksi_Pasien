<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanda "dipakai/belum" per alat anggota SET yang sedang dipegang unit —
 * dipakai di halaman Buat Order, karena status ItemBatch cuma satu nilai
 * untuk satu set utuh (satu QR), padahal unit perlu menandai isinya
 * satu-satu (mis. gunting dipakai, needle holder tidak).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_batch_usage_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->boolean('is_used')->default(false);
            $table->foreignId('marked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            $table->unique(['item_batch_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_batch_usage_marks');
    }
};
