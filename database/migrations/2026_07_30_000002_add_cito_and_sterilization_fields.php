<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dicek per kolom (bukan langsung ditambah) — lihat catatan yang sama
        // di migrasi 2026_07_30_000001_add_new_features_columns.
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_orders', 'is_cito')) {
                $table->boolean('is_cito')->default(false)->after('box_count');
            }
            if (! Schema::hasColumn('delivery_orders', 'needed_at')) {
                $table->dateTime('needed_at')->nullable()->after('is_cito');
            }
            if (! Schema::hasColumn('delivery_orders', 'pickup_location')) {
                $table->string('pickup_location')->nullable()->after('needed_at');
            }
        });

        if (! Schema::hasColumn('items', 'sterilization_method')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('sterilization_method', 32)->nullable()->after('material_sensitivity');
            });
        }

        if (! Schema::hasColumn('item_batches', 'sterilization_expired_at')) {
            Schema::table('item_batches', function (Blueprint $table) {
                $table->dateTime('sterilization_expired_at')->nullable()->after('sterilization_method');
            });
        }
    }

    public function down(): void
    {
        Schema::table('item_batches', function (Blueprint $table) {
            $table->dropColumn('sterilization_expired_at');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('sterilization_method');
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn(['is_cito', 'needed_at', 'pickup_location']);
        });
    }
};
