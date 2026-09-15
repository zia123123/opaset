<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Kontrak;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetFullImporter
{
    protected int $assetsCreated = 0;
    protected int $assetsUpdated = 0;
    protected int $kontrakCreated = 0;
    protected array $errors = [];
    protected array $processedSheets = [];
    protected array $skippedSheets = [];
    protected array $kontrakResetFor = [];

    /**
     * Import 1 file yang bisa berisi banyak sheet dengan FORMAT KOLOM YANG
     * BERBEDA-BEDA antar sheet, bahkan berubah tiap bulan (kadang ada kolom
     * Latitude/Longitude/Usaha menyatu, kadang tidak, kadang urutan kolom
     * geser). Makanya importer ini TIDAK hardcode posisi kolom (mis. "kolom I
     * = Latitude") — posisi tiap kolom dicari otomatis berdasarkan TEKS
     * HEADER-nya di setiap sheet, jadi tahan terhadap perubahan format.
     *
     * Setiap sheet dicek dulu: kalau baris manapun (1-6) punya kombinasi sel
     * "No" + "Sub Asset Code", sheet itu dianggap sheet data dan diproses.
     * Sheet yang tidak punya kombinasi itu (mis. sheet PIVOT/rekap) otomatis
     * dilewati — tidak perlu tebak dari nama sheet-nya.
     *
     * Field yang kolomnya TIDAK ditemukan di suatu sheet tidak ikut ditulis
     * (bukan di-null-kan), supaya sheet referensi minimal (mis. sheet yang
     * cuma berisi Sub Asset Code + Latitude/Longitude) bisa dipakai buat
     * MELENGKAPI data dari sheet lain tanpa menimpa/menghapus field lainnya.
     *
     * SKEMA: RESET TOTAL — seluruh data aset & kontrak yang ada di database
     * dihapus bersih dulu, baru diisi ulang dari nol sesuai isi file ini.
     *
     * PERHATIAN: field Status Pendayagunaan / Kondisi Fisik / Keterangan
     * (tersimpan di tabel assets, di luar file ini) ikut terhapus saat reset.
     * Upload ulang lewat halaman import status pendayagunaan kalau perlu.
     */
    /**
     * @param string $filePath
     * @param array<string> $onlySheets Kalau diisi, HANYA sheet dengan nama persis
     *        ini yang diproses (case-insensitive, di-trim), sheet lain diabaikan
     *        total. Kalau dibiarkan kosong, semua sheet di-scan otomatis seperti
     *        biasa (deteksi header "No" + "Sub Asset Code").
     */
    public function import(string $filePath, array $onlySheets = []): array
    {
        DB::table('kontraks')->delete();
        DB::table('assets')->delete();

        $onlySheetsNormalized = array_map(fn ($s) => strtolower(trim($s)), $onlySheets);

        $spreadsheet = IOFactory::load($filePath);

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            if ($onlySheetsNormalized && ! in_array(strtolower(trim($sheetName)), $onlySheetsNormalized, true)) {
                $this->skippedSheets[] = "{$sheetName} (tidak dipilih)";
                continue;
            }

            $sheet = $spreadsheet->getSheetByName($sheetName);
            $map = $this->detectColumnMap($sheet);

            if ($map === null) {
                $this->skippedSheets[] = $sheetName;
                continue;
            }

            $endRow = $this->detectEndRow($sheet, $map);
            if ($endRow < $map['headerRow'] + 1) {
                $this->skippedSheets[] = $sheetName;
                continue;
            }

            $this->processedSheets[] = $sheetName;
            $startRow = $map['headerRow'] + 1;

            DB::transaction(function () use ($sheet, $map, $startRow, $endRow) {
                $currentAsset = null;

                for ($row = $startRow; $row <= $endRow; $row++) {
                    try {
                        $no = $this->cellByField($sheet, $map, 'no_urut', $row);

                        if ($no !== null && $no !== '') {
                            $currentAsset = $this->upsertAsset($sheet, $map, $row);
                        }

                        if ($currentAsset) {
                            $this->maybeCreateKontrak($sheet, $map, $row, $currentAsset);
                        }
                    } catch (\Throwable $e) {
                        $this->errors[] = "Sheet {$sheet->getTitle()} baris {$row}: " . $e->getMessage();
                    }
                }
            });
        }

        return [
            'assets_created' => $this->assetsCreated,
            'assets_updated' => $this->assetsUpdated,
            'kontrak_created' => $this->kontrakCreated,
            'processed_sheets' => $this->processedSheets,
            'skipped_sheets' => $this->skippedSheets,
            'errors' => $this->errors,
        ];
    }

    protected function fieldPatterns(): array
    {
        return [
            'no_urut' => ['type' => 'exact', 'text' => 'no'],
            'rm' => ['type' => 'exact', 'text' => 'rm'],
            'kedudukan' => ['type' => 'contains', 'text' => 'kedudukan'],
            'nama_aset' => ['type' => 'exact', 'text' => 'nama aset'],
            'sub_asset_code' => ['type' => 'contains', 'text' => 'sub asset code'],
            'jenis_aset' => ['type' => 'exact', 'text' => 'jenis aset'],
            'asset_code' => ['type' => 'exact', 'text' => 'asset code'],
            'latitude' => ['type' => 'exact', 'text' => 'latitude'],
            'longitude' => ['type' => 'exact', 'text' => 'longitude'],
            'tipe_aset' => ['type' => 'contains', 'text' => 'sub asset status'],
            'status' => ['type' => 'exact', 'text' => 'status'],
            'luas_tanah' => ['type' => 'custom_luas_tanah_aset'],
            'luas_bangunan' => ['type' => 'custom_luas_bangunan_aset'],
            'address' => ['type' => 'contains', 'text' => 'address'],
            'nama_mitra' => ['type' => 'contains', 'text' => 'nama mitra'],
            'jenis_usaha' => ['type' => 'exact', 'text' => 'jenis usaha'],
            'usaha' => ['type' => 'exact', 'text' => 'usaha'],
            'tgl_ttd' => ['type' => 'contains', 'text' => 'tanggal penandatanganan'],
            'luas_tanah_kontrak' => ['type' => 'custom_luas_tanah_kontrak'],
            'luas_bangunan_kontrak' => ['type' => 'custom_luas_bangunan_kontrak'],
            'nilai_kontrak' => ['type' => 'contains', 'text' => 'nilai kontrak'],
            'masa_kerjasama' => ['type' => 'exact', 'text' => 'masa kerjasama'],
            'tgl_mulai_kerjasama' => ['type' => 'contains', 'text' => 'tanggal mulai kerjasama'],
            'tgl_akhir_kerjasama' => ['type' => 'contains', 'text' => 'tanggal berakhir kerjasama'],
        ];
    }

    /**
     * Cari baris header (1-6) yang punya sel "No" DAN sel "Sub Asset Code" —
     * kalau ketemu, deteksi semua kolom lain berdasarkan baris itu. Kalau
     * tidak ketemu di baris manapun, sheet ini bukan sheet data (dilewati).
     */
    protected function detectColumnMap(Worksheet $sheet): ?array
    {
        for ($headerRow = 1; $headerRow <= 6; $headerRow++) {
            $labels = [];

            for ($colIndex = 1; $colIndex <= 52; $colIndex++) {
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $value = $sheet->getCell("{$letter}{$headerRow}")->getCalculatedValue();
                if (is_string($value) && trim($value) !== '') {
                    $labels[$letter] = strtolower(trim($value));
                }
            }

            $hasNo = in_array('no', $labels, true);
            $hasSubAssetCode = false;
            foreach ($labels as $label) {
                if (str_contains($label, 'sub asset code')) {
                    $hasSubAssetCode = true;
                    break;
                }
            }

            if ($hasNo && $hasSubAssetCode) {
                return [
                    'headerRow' => $headerRow,
                    'columns' => $this->resolveColumns($labels),
                ];
            }
        }

        return null;
    }

    protected function resolveColumns(array $labels): array
    {
        $columns = [];

        foreach ($this->fieldPatterns() as $field => $pattern) {
            $found = null;

            foreach ($labels as $letter => $label) {
                $isMatch = match ($pattern['type']) {
                    'exact' => $label === $pattern['text'],
                    'contains' => str_contains($label, $pattern['text']),
                    'custom_luas_tanah_aset' => str_contains($label, 'luas tanah') && ! str_contains($label, 'kontrak'),
                    'custom_luas_bangunan_aset' => str_contains($label, 'luas bangunan') && ! str_contains($label, 'kontrak'),
                    'custom_luas_tanah_kontrak' => str_contains($label, 'luas tanah') && str_contains($label, 'kontrak'),
                    'custom_luas_bangunan_kontrak' => str_contains($label, 'luas bangunan') && str_contains($label, 'kontrak'),
                    default => false,
                };

                if ($isMatch) {
                    $found = $letter; // terus lanjut cari, biar dapet yang PALING KANAN kalau ada duplikat
                }
            }

            $columns[$field] = $found;
        }

        return $columns;
    }

    protected function detectEndRow(Worksheet $sheet, array $map): int
    {
        $noCol = $map['columns']['no_urut'] ?? null;
        if (! $noCol) {
            return $map['headerRow'];
        }

        $highestRow = $sheet->getHighestDataRow();
        $lastNoRow = $map['headerRow'];

        for ($row = $map['headerRow'] + 1; $row <= $highestRow; $row++) {
            $value = $sheet->getCell("{$noCol}{$row}")->getCalculatedValue();
            if (is_numeric($value)) {
                $lastNoRow = $row;
            }
        }

        return $lastNoRow;
    }

    protected function cellByField($sheet, array $map, string $field, int $row)
    {
        $col = $map['columns'][$field] ?? null;
        if (! $col) {
            return null;
        }

        return $this->val($sheet, "{$col}{$row}");
    }

    protected function upsertAsset($sheet, array $map, int $row): ?Asset
    {
        $subAssetCode = trim((string) $this->cellByField($sheet, $map, 'sub_asset_code', $row));

        if ($subAssetCode === '') {
            $this->errors[] = "Baris {$row}: Sub Asset Code kosong, dilewati.";
            return null;
        }

        $data = [];

        $casters = [
            'no_urut' => fn ($v) => (int) $v,
            'rm' => fn ($v) => $v,
            'kedudukan' => fn ($v) => $v,
            'nama_aset' => fn ($v) => $v,
            'jenis_aset' => fn ($v) => $this->normalizeJenisAset($v),
            'asset_code' => fn ($v) => $v,
            'latitude' => fn ($v) => $this->fixCoordinate($v, 'lat'),
            'longitude' => fn ($v) => $this->fixCoordinate($v, 'lng'),
            'luas_tanah' => fn ($v) => $this->toNumber($v),
            'luas_bangunan' => fn ($v) => $this->toNumber($v),
            'tipe_aset' => fn ($v) => $v,
            'status' => fn ($v) => $v,
        ];

        foreach ($casters as $field => $caster) {
            if (! ($map['columns'][$field] ?? null)) {
                continue;
            }
            $raw = $this->cellByField($sheet, $map, $field, $row);
            if ($raw === null) {
                continue;
            }
            $data[$field] = $caster($raw);
        }

        if (! isset($data['tipe_aset'])) {
            $data['tipe_aset'] = 'KD List';
        }

        $isNew = ! Asset::where('sub_asset_code', $subAssetCode)->exists();

        $asset = Asset::updateOrCreate(['sub_asset_code' => $subAssetCode], $data);

        $isNew ? $this->assetsCreated++ : $this->assetsUpdated++;

        return $asset;
    }

    protected function maybeCreateKontrak($sheet, array $map, int $row, Asset $asset): void
    {
        $namaMitra = trim((string) $this->cellByField($sheet, $map, 'nama_mitra', $row));
        $tglTtd = trim((string) $this->cellByField($sheet, $map, 'tgl_ttd', $row));

        if ($namaMitra === '' && $tglTtd === '') {
            return;
        }

        // Kalau ada beberapa sheet yang sama-sama punya data kontrak untuk aset
        // yang sama (mis. sheet referensi longlat & sheet data utama sama-sama
        // menyertakan info mitra), bersihkan dulu kontrak lama aset ini SEKALI
        // di kemunculan pertama pada import run ini, supaya sheet yang diproses
        // belakangan (biasanya yang lebih baru/lengkap) yang jadi sumber final,
        // bukan malah numpuk jadi dobel.
        if (! isset($this->kontrakResetFor[$asset->id])) {
            $asset->kontraks()->delete();
            $this->kontrakResetFor[$asset->id] = true;
        }

        Kontrak::create([
            'asset_id' => $asset->id,
            'address' => $this->cellByField($sheet, $map, 'address', $row),
            'nama_mitra_kerjasama' => $namaMitra !== '' ? $namaMitra : null,
            'jenis_usaha' => $this->cellByField($sheet, $map, 'jenis_usaha', $row),
            'usaha' => $this->cellByField($sheet, $map, 'usaha', $row),
            'tanggal_penandatanganan_kontrak' => $this->toDate($tglTtd),
            'luas_tanah_kontrak' => $this->toNumber($this->cellByField($sheet, $map, 'luas_tanah_kontrak', $row)),
            'luas_bangunan_kontrak' => $this->toNumber($this->cellByField($sheet, $map, 'luas_bangunan_kontrak', $row)),
            'nilai_kontrak' => $this->toNumber($this->cellByField($sheet, $map, 'nilai_kontrak', $row)),
            'masa_kerjasama' => $this->cellByField($sheet, $map, 'masa_kerjasama', $row),
            'tanggal_mulai_kerjasama' => $this->toDate($this->cellByField($sheet, $map, 'tgl_mulai_kerjasama', $row)),
            'tanggal_berakhir_kerjasama' => $this->toDate($this->cellByField($sheet, $map, 'tgl_akhir_kerjasama', $row)),
        ]);

        $this->kontrakCreated++;
    }

    protected function val($sheet, string $coordinate)
    {
        $value = $sheet->getCell($coordinate)->getCalculatedValue();

        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }

    protected function toNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9,.\-]/', '', (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function normalizeJenisAset(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        return preg_replace('/\s*\/\s*/', ' / ', trim($value));
    }

    protected function fixCoordinate($value, string $type): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = (string) $value;
        $negative = str_starts_with(trim($raw), '-');
        $digitsOnly = preg_replace('/[^0-9]/', '', $raw);

        if ($digitsOnly === '') {
            return null;
        }

        foreach ([1, 2, 3] as $intLen) {
            if (strlen($digitsOnly) <= $intLen) {
                continue;
            }

            $candidate = (float) (substr($digitsOnly, 0, $intLen) . '.' . substr($digitsOnly, $intLen));
            if ($negative) {
                $candidate = -$candidate;
            }

            if ($this->isPlausibleCoordinate($candidate, $type)) {
                return round($candidate, 7);
            }
        }

        $this->errors[] = "Koordinat {$type} '{$value}' tidak bisa dibetulkan otomatis, dikosongkan.";

        return null;
    }

    protected function isPlausibleCoordinate(float $num, string $type): bool
    {
        return $type === 'lat'
            ? $num >= -12 && $num <= 8
            : $num >= 90 && $num <= 142;
    }

    protected function toDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', (string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
    }
}