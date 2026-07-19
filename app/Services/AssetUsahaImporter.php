<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AssetUsahaImporter
{
    protected int $matchedAssets = 0;
    protected int $unmatchedAssets = 0;
    protected int $kontrakUpdated = 0;
    protected array $errors = [];

    /**
     * Import satu file "bahan mapping" (bisa file terdayaguna maupun idle,
     * strukturnya sama). Setiap baris direlasikan ke tabel assets lewat
     * kolom D "Sub Asset Code" (kolom ini hidden di Excel tapi tetap dibaca).
     *
     * Kolom E "Jenis Aset" dipakai untuk memastikan/melengkapi jenis_aset di assets.
     * Kolom Q "Usaha" & R "Jenis Usaha" dipakai untuk melengkapi data di kontraks,
     * dicocokkan berurutan (baris ke-n dengan sub_asset_code sama -> kontrak ke-n
     * milik aset tsb, urut berdasarkan id).
     */
    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $maxRow = $sheet->getHighestDataRow();

        // Pelacak urutan kemunculan sub_asset_code, untuk mencocokkan
        // baris ke-n dengan kontrak ke-n milik aset yang sama.
        $occurrence = [];

        DB::transaction(function () use ($sheet, $maxRow, &$occurrence) {
            for ($row = 2; $row <= $maxRow; $row++) {
                try {
                    $this->processRow($sheet, $row, $occurrence);
                } catch (\Throwable $e) {
                    $this->errors[] = "Baris {$row}: " . $e->getMessage();
                }
            }
        });

        return [
            'matched_assets' => $this->matchedAssets,
            'unmatched_assets' => $this->unmatchedAssets,
            'kontrak_updated' => $this->kontrakUpdated,
            'errors' => $this->errors,
        ];
    }

    protected function processRow($sheet, int $row, array &$occurrence): void
    {
        $subAssetCode = trim((string) $this->val($sheet, "D{$row}"));

        if ($subAssetCode === '') {
            return; // baris kosong / bukan baris data
        }

        $asset = Asset::where('sub_asset_code', $subAssetCode)->first();

        if (! $asset) {
            $this->unmatchedAssets++;
            $this->errors[] = "Baris {$row}: Sub Asset Code {$subAssetCode} tidak ditemukan di data aset utama.";
            return;
        }

        $this->matchedAssets++;

        // Lengkapi / perbarui jenis aset kalau ada nilainya
        $jenisAset = $this->val($sheet, "E{$row}");
        if ($jenisAset && $jenisAset !== $asset->jenis_aset) {
            $asset->update(['jenis_aset' => $jenisAset]);
        }

        $usaha = $this->val($sheet, "Q{$row}");
        $jenisUsaha = $this->val($sheet, "R{$row}");

        if (! $usaha && ! $jenisUsaha) {
            return; // baris aset idle, tidak ada data kontrak/usaha
        }

        $index = $occurrence[$subAssetCode] ?? 0;
        $occurrence[$subAssetCode] = $index + 1;

        $kontrak = $asset->kontraks()->orderBy('id')->skip($index)->take(1)->first();

        if (! $kontrak) {
            $this->errors[] = "Baris {$row}: tidak ada kontrak ke-" . ($index + 1) . " pada aset {$subAssetCode} untuk dicocokkan.";
            return;
        }

        $kontrak->update([
            'usaha' => $usaha,
            'jenis_usaha' => $jenisUsaha ?: $kontrak->jenis_usaha,
        ]);

        $this->kontrakUpdated++;
    }

    protected function val($sheet, string $coordinate)
    {
        $value = $sheet->getCell($coordinate)->getCalculatedValue();

        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}