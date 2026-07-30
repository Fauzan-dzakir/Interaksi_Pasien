<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checklist QC per tahap (Penerimaan/Dekontaminasi/Bersih-Packaging/Steril) yang
 * diisi CSSD di halaman detail alat — dokumentasi tambahan, bukan pengganti scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_batch_stage_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_batch_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 32);
            $table->string('item_key', 64);
            $table->string('item_label', 255);
            $table->boolean('is_present')->default(true);
            $table->string('note', 255)->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['item_batch_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_batch_stage_checks');
    }
};
