<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Kontrak;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AssetFullImporter
{
    protected int $startRow = 5;

    protected int $assetsCreated = 0;
    protected int $assetsUpdated = 0;
    protected int $kontrakCreated = 0;
    protected array $errors = [];
    protected array $processedSheets = [];

    /**
     * Import 1 file yang berisi 1 atau lebih sheet data aset (mis. "KD LIST JUL",
     * "NON KD LIST JUL"). Setiap sheet bernama "LONGLAT" dilewati karena data
     * koordinatnya sudah menyatu langsung di kolom Latitude/Longitude tiap baris
     * pada sheet utama.
     *
     * SKEMA: RESET TOTAL — seluruh data aset & kontrak yang ada di database
     * DIHAPUS BERSIH dulu, baru diisi ulang dari nol sesuai isi file ini.
     * Bukan lagi update/merge — jadi hasil akhirnya PERSIS sama dengan isi
     * file yang diupload, tidak ada sisa data lama yang ketinggalan.
     *
     * PERHATIAN: karena field Status Pendayagunaan / Kondisi Fisik / Keterangan
     * juga tersimpan di tabel assets (bukan file ini), data itu ikut terhapus
     * saat reset. Kalau datanya masih dibutuhkan, upload ulang lewat halaman
     * import status pendayagunaan setelah proses ini selesai.
     */
    public function import(string $filePath): array
    {
        // Hapus semua kontrak dulu (karena ada foreign key ke assets),
        // baru hapus semua aset.
        DB::table('kontraks')->delete();
        DB::table('assets')->delete();

        $spreadsheet = IOFactory::load($filePath);

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            if (stripos($sheetName, 'longlat') !== false) {
                continue;
            }

            $sheet = $spreadsheet->getSheetByName($sheetName);
            $endRow = $this->detectEndRow($sheet);

            if ($endRow < $this->startRow) {
                continue; // sheet kosong / bukan sheet data
            }

            $this->processedSheets[] = $sheetName;

            DB::transaction(function () use ($sheet, $endRow) {
                $currentAsset = null;

                for ($row = $this->startRow; $row <= $endRow; $row++) {
                    try {
                        $no = $this->val($sheet, "A{$row}");

                        if ($no !== null && $no !== '') {
                            $currentAsset = $this->upsertAsset($sheet, $row, $no);
                        }

                        if ($currentAsset) {
                            $this->maybeCreateKontrak($sheet, $row, $currentAsset);
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
            'errors' => $this->errors,
        ];
    }

    /**
     * Cari baris terakhir data: baris paling bawah yang kolom A (No)-nya
     * benar-benar terisi angka. Baris kontrak lanjutan (No kosong) selalu
     * berada SEBELUM baris No terakhir ini, jadi aman dipakai sebagai batas.
     */
    protected function detectEndRow($sheet): int
    {
        $highestRow = $sheet->getHighestDataRow();
        $lastNoRow = $this->startRow - 1;

        for ($row = $this->startRow; $row <= $highestRow; $row++) {
            $value = $sheet->getCell("A{$row}")->getCalculatedValue();
            if (is_numeric($value)) {
                $lastNoRow = $row;
            }
        }

        return $lastNoRow;
    }

    protected function upsertAsset($sheet, int $row, $no): ?Asset
    {
        $subAssetCode = trim((string) $this->val($sheet, "E{$row}"));

        if ($subAssetCode === '') {
            $this->errors[] = "Baris {$row}: Sub Asset Code kosong, dilewati.";
            return null;
        }

        $data = [
            'no_urut' => (int) $no,
            'rm' => $this->val($sheet, "B{$row}"),
            'kedudukan' => $this->val($sheet, "C{$row}"),
            'nama_aset' => $this->val($sheet, "D{$row}"),
            'jenis_aset' => $this->normalizeJenisAset($this->val($sheet, "F{$row}")),
            'asset_code' => $this->val($sheet, "H{$row}"),
            'latitude' => $this->fixCoordinate($this->val($sheet, "I{$row}"), 'lat'),
            'longitude' => $this->fixCoordinate($this->val($sheet, "J{$row}"), 'lng'),
            'luas_tanah' => $this->toNumber($this->val($sheet, "K{$row}")),
            'luas_bangunan' => $this->toNumber($this->val($sheet, "L{$row}")),
            'tipe_aset' => $this->val($sheet, "M{$row}") ?? 'KD List',
            'status' => $this->val($sheet, "N{$row}"),
        ];

        // Karena tabel sudah dikosongkan total di awal import(), updateOrCreate di sini
        // cuma jaga-jaga kalau ada Sub Asset Code yang kebetulan dobel di dalam file
        // yang sama (mis. muncul lagi di sheet lain) — bukan skenario normal.
        $isNew = ! Asset::where('sub_asset_code', $subAssetCode)->exists();

        $asset = Asset::updateOrCreate(['sub_asset_code' => $subAssetCode], $data);

        $isNew ? $this->assetsCreated++ : $this->assetsUpdated++;

        return $asset;
    }

    protected function maybeCreateKontrak($sheet, int $row, Asset $asset): void
    {
        $namaMitra = trim((string) $this->val($sheet, "S{$row}"));
        $tglTtd = trim((string) $this->val($sheet, "V{$row}"));

        if ($namaMitra === '' && $tglTtd === '') {
            return; // baris tanpa data kontrak (mis. aset idle)
        }

        Kontrak::create([
            'asset_id' => $asset->id,
            'address' => $this->val($sheet, "P{$row}"),
            'nama_mitra_kerjasama' => $namaMitra !== '' ? $namaMitra : null,
            'jenis_usaha' => $this->val($sheet, "T{$row}"),
            'usaha' => $this->val($sheet, "U{$row}"),
            'tanggal_penandatanganan_kontrak' => $this->toDate($tglTtd),
            'luas_tanah_kontrak' => $this->toNumber($this->val($sheet, "W{$row}")),
            'luas_bangunan_kontrak' => $this->toNumber($this->val($sheet, "X{$row}")),
            'nilai_kontrak' => $this->toNumber($this->val($sheet, "Y{$row}")),
            'masa_kerjasama' => $this->val($sheet, "AD{$row}"),
            'tanggal_mulai_kerjasama' => $this->toDate($this->val($sheet, "AE{$row}")),
            'tanggal_berakhir_kerjasama' => $this->toDate($this->val($sheet, "AF{$row}")),
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

    /**
     * File sumber kadang menulis kategori tanpa spasi di sekitar "/"
     * (mis. "Gedung/Ruang" alih-alih "Gedung / Ruang"), sehingga tidak
     * cocok dengan daftar 6 kategori baku dan jatuh ke "Lainnya". Fungsi
     * ini merapikan spasinya supaya selalu konsisten dengan format baku,
     * apapun format aslinya di file.
     */
    protected function normalizeJenisAset(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        return preg_replace('/\s*\/\s*/', ' / ', trim($value));
    }

    /**
     * Beberapa baris di file sumber kehilangan titik desimalnya (mis. Longitude
     * tertulis "106829439" alih-alih "106.829439", atau Latitude "-7283395"
     * alih-alih "-7.283395") — kemungkinan besar karena format sel Excel-nya.
     * Fungsi ini mendeteksi & membetulkan otomatis dengan mencoba beberapa
     * posisi titik desimal, lalu memilih yang menghasilkan koordinat masuk
     * akal untuk wilayah Indonesia. Kalau tidak ada satupun yang masuk akal,
     * kembalikan null (lebih aman daripada memasukkan koordinat ngawur).
     */
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

        $this->errors[] = "Koordinat {$type} '{$value}' tidak bisa dibetulkan otomatis (di luar jangkauan wilayah Indonesia), dikosongkan.";

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