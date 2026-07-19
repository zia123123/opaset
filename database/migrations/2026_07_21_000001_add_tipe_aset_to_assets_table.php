<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom "Sub Asset Status" pada excel (kolom K, hidden), nilainya
     * "KD List" atau "Non KD List" — dipakai untuk membedakan sumber data.
     * Default 'KD List' supaya 510 data yang sudah ada tetap konsisten
     * (data itu semua berasal dari sheet KD List).
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('tipe_aset', 20)->default('KD List')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('tipe_aset');
        });
    }
};
