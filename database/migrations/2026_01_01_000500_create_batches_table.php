<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch = kumpulan alat milik satu unit yang MENETAP lintas siklus.
 *
 * Contoh: "IBS OK-1" berisi 1 set bedah minor + 2 alat satuan. Tiap siklus IBS
 * memesan ulang batch ini (boleh menambah/mengurangi isinya). Alat yang tidak
 * dipesan ulang keluar dari batch dan kembali menjadi available stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
