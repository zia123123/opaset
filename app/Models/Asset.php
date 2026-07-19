<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_urut',
        'rm',
        'kedudukan',
        'nama_aset',
        'sub_asset_code',
        'jenis_aset',
        'asset_code',
        'luas_tanah',
        'luas_bangunan',
        'status',
        'tipe_aset',
    ];

    protected $casts = [
        'no_urut' => 'integer',
        'luas_tanah' => 'decimal:2',
        'luas_bangunan' => 'decimal:2',
    ];

    public function kontraks(): HasMany
    {
        return $this->hasMany(Kontrak::class);
    }
}