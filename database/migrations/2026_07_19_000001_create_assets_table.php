<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sumber: sheet "KD LIST", kolom A-L (kolom yang tidak hidden),
     * baris 5 s/d 592 pada file mapping Juni 2026.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            // Kolom A "No" pada excel (nomor urut aset, bisa berulang tampil
            // hanya di baris pertama grup kontrak)
            $table->unsignedInteger('no_urut')->nullable();

            // Kolom B "RM"
            $table->string('rm', 50)->nullable();

            // Kolom C "Kedudukan"
            $table->string('kedudukan')->nullable();

            // Kolom D "Nama Aset"
            $table->string('nama_aset');

            // Kolom E "Sub Asset Code" - kode unik per aset
            $table->string('sub_asset_code', 50)->unique();

            // Kolom F "Jenis Aset" (mis: Rumah Perusahaan, Lahan Kosong, dll)
            $table->string('jenis_aset')->nullable();

            // Kolom H "Asset Code"
            $table->string('asset_code', 50)->nullable();

            // Kolom I "Luas Tanah (m2)"
            $table->decimal('luas_tanah', 15, 2)->nullable();

            // Kolom J "Luas Bangunan (m2)"
            $table->decimal('luas_bangunan', 15, 2)->nullable();

            // Kolom L "Status" (mis: Terdayaguna, Idle)
            $table->string('status', 50)->nullable();

            $table->timestamps();

            $table->index('rm');
            $table->index('kedudukan');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
