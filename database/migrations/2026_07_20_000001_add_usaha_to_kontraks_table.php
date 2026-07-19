<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahan dari file "bahan mapping terdayaguna/idle": kolom "Usaha"
     * (kategori umum, mis. "Usaha Komersial", "Tempat Tinggal", "Kantor"),
     * berbeda dari "Jenis Usaha" yang sudah ada (lebih spesifik, mis. "KULINER / COFFEE SHOP").
     */
    public function up(): void
    {
        Schema::table('kontraks', function (Blueprint $table) {
            $table->string('usaha')->nullable()->after('jenis_usaha');
        });
    }

    public function down(): void
    {
        Schema::table('kontraks', function (Blueprint $table) {
            $table->dropColumn('usaha');
        });
    }
};