<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Katalog set alat (mis. "Set Bedah Minor") beserta komposisi isinya.
 * Dipakai saat pendataan CSSD memilih jalur "Per Set".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instrument_sets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('instrument_set_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrument_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['instrument_set_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrument_set_items');
        Schema::dropIfExists('instrument_sets');
    }
};
