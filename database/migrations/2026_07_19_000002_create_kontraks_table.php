<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sumber: sheet "KD LIST", kolom N, Q, R, S, T, U, V, AA, AB, AC
     * (kolom yang tidak hidden), baris 5 s/d 592.
     * Satu aset (assets) bisa punya banyak baris kontrak/mitra kerjasama.
     */
    public function up(): void
    {
        Schema::create('kontraks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnDelete();

            // Kolom N "Address"
            $table->string('address')->nullable();

            // Kolom Q "Nama Mitra Kerjasama"
            $table->string('nama_mitra_kerjasama')->nullable();

            // Kolom R "Jenis Usaha"
            $table->string('jenis_usaha')->nullable();

            // Kolom S "Tanggal Penandatanganan Kontrak"
            $table->date('tanggal_penandatanganan_kontrak')->nullable();

            // Kolom T "Luas Tanah Sesuai Kontrak (m2)"
            $table->decimal('luas_tanah_kontrak', 15, 2)->nullable();

            // Kolom U "Luas Bangunan Sesuai Kontrak (m2)"
            $table->decimal('luas_bangunan_kontrak', 15, 2)->nullable();

            // Kolom V "Nilai Kontrak (include ppn)"
            $table->decimal('nilai_kontrak', 18, 2)->nullable();

            // Kolom AA "Masa Kerjasama" (mis: "5 Tahun", "1 Tahun")
            $table->string('masa_kerjasama', 50)->nullable();

            // Kolom AB "Tanggal Mulai Kerjasama"
            $table->date('tanggal_mulai_kerjasama')->nullable();

            // Kolom AC "Tanggal Berakhir Kerjasama"
            $table->date('tanggal_berakhir_kerjasama')->nullable();

            $table->timestamps();

            $table->index('nama_mitra_kerjasama');
            $table->index('tanggal_berakhir_kerjasama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontraks');
    }
};
