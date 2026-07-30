<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahan kolom user untuk kebutuhan SIM CSSD:
 * - unit_id : unit asal user (nullable, karena Admin tidak terikat unit manapun)
 * - role    : admin | cssd_staff | nakes
 * - is_active : akun dinonaktifkan tanpa dihapus (jejak audit tetap utuh)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('id')->constrained('units')->nullOnDelete();
            $table->string('role', 32)->default('nakes')->after('email')->index();
            $table->boolean('is_active')->default(true)->after('role')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'role', 'is_active']);
        });
    }
};
