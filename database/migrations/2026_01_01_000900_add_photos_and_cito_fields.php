<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiga penambahan sesuai kebutuhan lapangan:
 *
 * 1. Foto contoh pada katalog alat & set, supaya petugas dan unit bisa
 *    mengenali alat secara visual, tidak hanya dari namanya.
 * 2. Penanda CITO pada pesanan untuk pasien gawat yang alatnya harus
 *    dikirim saat itu juga.
 * 3. Foto verifikasi saat unit memesan, sebagai bukti awal bila kemudian
 *    ada alat yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('notes');
        });

        Schema::table('instrument_sets', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('description');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_cito')->default(false)->after('fulfillment_method')->index();
            $table->string('verification_photo_path')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['is_cito', 'verification_photo_path']);
        });

        Schema::table('instrument_sets', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
