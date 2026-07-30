<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master data "ruangan/lokasi" per unit (mis. Ruang Operasi 1-5 untuk IBS).
 * Dipakai sebagai pilihan dropdown saat unit membuat order, supaya lokasi asal
 * alat tercatat konsisten (bukan teks bebas) dan bisa diatur oleh Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['unit_id', 'name']);
        });

        // Permintaan lokasi baru dari unit yang belum ada di daftar — Admin yang
        // menyetujui dan menambahkannya ke master data pickup_locations.
        Schema::create('pickup_location_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 16)->default('pending')->index();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_location_requests');
        Schema::dropIfExists('pickup_locations');
    }
};
