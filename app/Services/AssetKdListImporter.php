<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Kontrak;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AssetKdListImporter
{
    /**
     * Baris pertama data (setelah header). Baris terakhir data dideteksi
     * otomatis lewat penanda tanda-tangan ("Jakarta, ...") yang selalu ada
     * tepat setelah baris data terakhir, baik di sheet KD List maupun Non KD List.
     */
    protected int $startRow = 5;

    protected int $assetsCreated = 0;
    protected int $assetsUpdated = 0;
    protected int $kontrakCreated = 0;
    protected array $errors = [];

    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);

        // Ambil sheet paling awal (KD List atau Non KD List, tergantung file yang diupload)
        $sheet = $spreadsheet->getSheet(0);
        $endRow = $this->detectEndRow($sheet);

        DB::transaction(function () use ($sheet, $endRow) {
            $currentAsset = null;

            for ($row = $this->startRow; $row <= $endRow; $row++) {
                try {
                    $no = $this->val($sheet, "A{$row}");

                    // Kolom A terisi = baris pertama sebuah grup aset baru
                    if ($no !== null && $no !== '') {
                        $currentAsset = $this->upsertAsset($sheet, $row, $no);
                    }

                    if ($currentAsset) {
                        $this->maybeCreateKontrak($sheet, $row, $currentAsset);
                    }
                } catch (\Throwable $e) {
                    $this->errors[] = "Baris {$row}: " . $e->getMessage();
                }
            }
        });

        return [
            'assets_created' => $this->assetsCreated,
            'assets_updated' => $this->assetsUpdated,
            'kontrak_created' => $this->kontrakCreated,
            'errors' => $this->errors,
        ];
    }

    /**
     * Cari baris terakhir data dengan menyisir sampai ketemu baris penanda
     * tanda-tangan (isinya mengandung kata "Jakarta"), yang selalu muncul
     * tepat setelah blok data pada file mapping ini. Kalau tidak ketemu,
     * fallback ke baris terakhir yang punya isi di sheet.
     */
    protected function detectEndRow($sheet): int
    {
        $highestRow = $sheet->getHighestDataRow();

        for ($row = $this->startRow; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col !== 'AF'; $col++) {
                $value = $sheet->getCell("{$col}{$row}")->getCalculatedValue();
                if (is_string($value) && str_contains($value, 'Jakarta')) {
                    return $row - 1;
                }
            }
        }

        return $highestRow;
    }

    protected function upsertAsset($sheet, int $row, $no): Asset
    {
        $subAssetCode = trim((string) $this->val($sheet, "E{$row}"));

        if ($subAssetCode === '') {
            // Tidak ada kode unik, lewati (data tidak lengkap)
            throw new \RuntimeException('Sub Asset Code kosong, baris dilewati.');
        }

        $data = [
            'no_urut' => (int) $no,
            'rm' => $this->val($sheet, "B{$row}"),
            'kedudukan' => $this->val($sheet, "C{$row}"),
            'nama_aset' => $this->val($sheet, "D{$row}"),
            'jenis_aset' => $this->val($sheet, "F{$row}"),
            'asset_code' => $this->val($sheet, "H{$row}"),
            'luas_tanah' => $this->toNumber($this->val($sheet, "I{$row}")),
            'luas_bangunan' => $this->toNumber($this->val($sheet, "J{$row}")),
            'status' => $this->val($sheet, "L{$row}"),
            'tipe_aset' => $this->val($sheet, "K{$row}") ?? 'KD List',
        ];

        $existing = Asset::where('sub_asset_code', $subAssetCode)->first();

        $asset = Asset::updateOrCreate(
            ['sub_asset_code' => $subAssetCode],
            $data
        );

        if ($existing) {
            $this->assetsUpdated++;
        } else {
            $this->assetsCreated++;
        }

        return $asset;
    }

    protected function maybeCreateKontrak($sheet, int $row, Asset $asset): void
    {
        $namaMitra = trim((string) $this->val($sheet, "Q{$row}"));
        $tglTtd = trim((string) $this->val($sheet, "S{$row}"));

        // Baris tanpa data kontrak sama sekali (aset tanpa mitra, mis. status Idle)
        if ($namaMitra === '' && $tglTtd === '') {
            return;
        }

        Kontrak::create([
            'asset_id' => $asset->id,
            'address' => $this->val($sheet, "N{$row}"),
            'nama_mitra_kerjasama' => $namaMitra !== '' ? $namaMitra : null,
            'jenis_usaha' => $this->val($sheet, "R{$row}"),
            'tanggal_penandatanganan_kontrak' => $this->toDate($tglTtd),
            'luas_tanah_kontrak' => $this->toNumber($this->val($sheet, "T{$row}")),
            'luas_bangunan_kontrak' => $this->toNumber($this->val($sheet, "U{$row}")),
            'nilai_kontrak' => $this->toNumber($this->val($sheet, "V{$row}")),
            'masa_kerjasama' => $this->val($sheet, "AA{$row}"),
            'tanggal_mulai_kerjasama' => $this->toDate($this->val($sheet, "AB{$row}")),
            'tanggal_berakhir_kerjasama' => $this->toDate($this->val($sheet, "AC{$row}")),
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

        // Bersihkan format seperti "Rp 1.000.000" jika ada
        $clean = preg_replace('/[^0-9,.\-]/', '', (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function toDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Kolom tanggal di file ini disimpan sebagai teks format d/m/Y
        try {
            return Carbon::createFromFormat('d/m/Y', (string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            // fallback kalau formatnya beda / excel serial date
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
    }
}