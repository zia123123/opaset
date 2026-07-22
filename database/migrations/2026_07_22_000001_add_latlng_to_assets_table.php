<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Koordinat presisi (dari data survey/GPS lapangan) per aset, dicocokkan
     * lewat Asset Code. Kalau NULL, peta akan tetap fallback ke posisi
     * acak-tapi-konsisten di sekitar wilayah "Kedudukan" seperti sebelumnya.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('luas_bangunan');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
