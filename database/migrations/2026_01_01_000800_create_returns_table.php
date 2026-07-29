<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengembalian alat kotor dari unit ke CSSD.
 *
 * Sengaja memakai KONFIRMASI DUA SISI: unit menyatakan sudah mengirim, lalu CSSD
 * menyatakan sudah menerima. Selama CSSD belum konfirmasi, alat berstatus
 * "Menunggu Konfirmasi CSSD", di situlah selisih/kehilangan antar-serah terdeteksi.
 *
 * Di form ini pula unit menentukan batch-nya dipesan ulang atau dilepas ke stok.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 32)->unique();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();

            // Sisi unit
            $table->foreignId('sent_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('sent_at');
            $table->string('sender_photo_path')->nullable();
            $table->string('courier_name')->nullable();

            // Sisi CSSD
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('receiver_photo_path')->nullable();

            // Keputusan diambil unit saat mengirim balik: batch dilanjut atau dilepas.
            $table->boolean('reorder_batch')->default(true);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'confirmed_at']);
        });

        Schema::create('return_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->boolean('was_used')->default(false);
            $table->timestamps();

            $table->unique(['return_id', 'asset_id']);
        });

        /**
         * Catatan proses sterilisasi untuk kebutuhan laporan PDF.
         * Tahapan, jam, dan petugas TIDAK diinput ulang di sini, semuanya diambil
         * dari jejak audit asset_events. Tabel ini hanya menyimpan hal yang memang
         * tidak ada di jejak audit: metode dan hasil indikator biologi.
         */
        Schema::create('sterilization_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_number', 32)->unique();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('method', 16);
            $table->string('biological_indicator_result', 16)->default('pending');
            $table->timestamp('bi_incubated_at')->nullable();
            $table->timestamp('bi_read_at')->nullable();
            $table->foreignId('bi_read_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sterilization_record_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sterilization_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['sterilization_record_id', 'asset_id'], 'ster_rec_asset_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sterilization_record_assets');
        Schema::dropIfExists('sterilization_records');
        Schema::dropIfExists('return_assets');
        Schema::dropIfExists('returns');
    }
};
