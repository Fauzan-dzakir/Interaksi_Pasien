<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom-kolom di sini dicek satu per satu (bukan langsung ditambah) karena
        // migrasi ini pernah gagal di tengah jalan pada satu server (ALTER TABLE
        // MySQL tidak transaksional, jadi sebagian kolom bisa sudah ada padahal
        // migrasi belum tercatat "Ran").
        if (! Schema::hasColumn('items', 'image_path')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('image_path')->nullable()->after('notes');
            });
        }

        if (! Schema::hasColumn('delivery_orders', 'photos')) {
            Schema::table('delivery_orders', function (Blueprint $table) {
                $table->json('photos')->nullable()->after('notes');
            });
        }

        if (! Schema::hasColumn('item_batches', 'sterilization_photos')) {
            Schema::table('item_batches', function (Blueprint $table) {
                $table->json('sterilization_photos')->nullable()->after('sterilization_method');
            });
        }
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn('photos');
        });

        Schema::table('item_batches', function (Blueprint $table) {
            $table->dropColumn('sterilization_photos');
        });
    }
};
