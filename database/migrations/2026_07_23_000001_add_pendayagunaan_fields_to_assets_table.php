<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data tambahan (biasanya buat aset Idle) — kolom B "Status Pendayagunaan",
     * C "Kondisi Fisik", dan D "Keterangan" dari file terpisah, ditautkan
     * lewat Sub Asset Code.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('status_pendayagunaan')->nullable()->after('status');
            $table->string('kondisi_fisik')->nullable()->after('status_pendayagunaan');
            $table->text('keterangan')->nullable()->after('kondisi_fisik');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['status_pendayagunaan', 'kondisi_fisik', 'keterangan']);
        });
    }
};
