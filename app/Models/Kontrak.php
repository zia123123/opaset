<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kontrak extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'address',
        'nama_mitra_kerjasama',
        'jenis_usaha',
        'usaha',
        'tanggal_penandatanganan_kontrak',
        'luas_tanah_kontrak',
        'luas_bangunan_kontrak',
        'nilai_kontrak',
        'masa_kerjasama',
        'tanggal_mulai_kerjasama',
        'tanggal_berakhir_kerjasama',
    ];

    protected $casts = [
        'tanggal_penandatanganan_kontrak' => 'date',
        'tanggal_mulai_kerjasama' => 'date',
        'tanggal_berakhir_kerjasama' => 'date',
        'luas_tanah_kontrak' => 'decimal:2',
        'luas_bangunan_kontrak' => 'decimal:2',
        'nilai_kontrak' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
