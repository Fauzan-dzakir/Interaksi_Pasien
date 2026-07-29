<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * assets = benda fisik yang dilacak sepanjang hidupnya.
 *
 *  - asset_type = set  -> satu set fisik (mis. Set Bedah Minor #1). Punya identitas,
 *                         foto rakitan, dan checklist isi. Satu barcode mewakili set.
 *  - asset_type = item -> satu alat lepasan berbarcode sendiri. Untuk unit pemesan,
 *                         stoknya ditampilkan teragregasi per jenis ("Gunting Mayo: 5").
 *
 * Baris ini TIDAK PERNAH dihapus dan identitasnya bertahan lintas siklus, meski
 * barcode-nya berganti tiap kali selesai dekontaminasi. current_code selalu terisi:
 * barcode lama tetap berlaku selama pencucian dan baru ditimpa saat barcode baru discan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('current_code', 32)->unique();
            $table->string('asset_type', 16)->index();

            $table->foreignId('instrument_set_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->restrictOnDelete();

            $table->string('status', 32)->index();
            $table->timestamp('status_changed_at');

            // Khusus set: false bila ada isi yang tidak ditemukan saat checklist.
            $table->boolean('is_complete')->default(true)->index();
            $table->string('assembled_photo_path')->nullable();

            // null = available stock (bebas dipesan unit mana pun).
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('sterilization_method', 16)->nullable();
            $table->unsignedInteger('cycle_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'batch_id']);
            $table->index(['asset_type', 'status']);
        });

        // Riwayat barcode: barcode lama tetap bisa dicari saat audit walau sudah diganti.
        Schema::create('asset_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'issued_at']);
        });

        // Jejak audit per aset, bukti "alat terakhir di mana, jam berapa, oleh siapa".
        Schema::create('asset_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('input_method', 16)->default('manual');
            $table->string('station_context')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_admin_override')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['asset_id', 'occurred_at']);
            $table->index('is_admin_override');
        });

        // Checklist isi set saat barcode baru dibuat: dicentang = ada, disilang = hilang.
        Schema::create('set_content_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('expected_quantity')->default(1);
            $table->boolean('is_present')->default(true);
            $table->foreignId('checked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'checked_at']);
            $table->index('is_present');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('set_content_checks');
        Schema::dropIfExists('asset_events');
        Schema::dropIfExists('asset_codes');
        Schema::dropIfExists('assets');
    }
};
