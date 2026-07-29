<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Kontrak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetPublicController extends Controller
{
    /**
     * Urutan & warna kategori aset, mengikuti legenda pada infografis resmi.
     */
    public static function categories(): array
    {
        return [
            'Rumah Perusahaan' => ['no' => 1, 'color' => '#DC2626', 'icon' => '🏠', 'code' => 'RP'],
            'Gedung / Ruang' => ['no' => 2, 'color' => '#7C3AED', 'icon' => '🏢', 'code' => 'GR'],
            'Lahan Kosong' => ['no' => 3, 'color' => '#16A34A', 'icon' => '🍀', 'code' => 'LK'],
            'Gudang' => ['no' => 4, 'color' => '#C2410C', 'icon' => '📦', 'code' => 'GU'],
            'Wisma / GSG / Hotel' => ['no' => 5, 'color' => '#2563EB', 'icon' => '🏨', 'code' => 'WG'],
            'Kawasan Bisnis' => ['no' => 6, 'color' => '#991B1B', 'icon' => '💼', 'code' => 'KB'],
        ];
    }

    /**
     * Icon & warna untuk kategori "Usaha" (kolom kontraks.usaha), dipakai di
     * panel "Kategori Usaha" pada dashboard. Kalau ada nilai baru yang belum
     * terdaftar di sini, tetap tampil pakai warna/icon default (fallback).
     */
    public static function usahaMeta(): array
    {
        return [
            'Usaha Komersial' => ['color' => '#0F2A5C', 'icon' => '🏪'],
            'Tempat Tinggal' => ['color' => '#16A34A', 'icon' => '🏠'],
            'Kantor' => ['color' => '#2563EB', 'icon' => '🏢'],
            'Sarana Olahraga' => ['color' => '#EA580C', 'icon' => '🏃'],
            'Penyimpanan' => ['color' => '#7C3AED', 'icon' => '🚪'],
            'GSG' => ['color' => '#CA8A04', 'icon' => '🏛️'],
            'Pertanian' => ['color' => '#0D9488', 'icon' => '🌱'],
            'Hotel' => ['color' => '#DB2777', 'icon' => '🏨'],
            'Hub Pengiriman' => ['color' => '#0891B2', 'icon' => '🚚'],
            'Penginapan' => ['color' => '#DC2626', 'icon' => '🛏️'],
            'Peternakan' => ['color' => '#78350F', 'icon' => '🐄'],
        ];
    }

    /**
     * Palet warna siklik — dipakai untuk "Jenis Usaha" yang nilainya dinamis
     * (bukan daftar tetap seperti "Usaha"), supaya tetap ada variasi warna.
     */
    protected static function paletteColor(int $index): string
    {
        $palette = ['#0F2A5C', '#DC2626', '#7C3AED', '#16A34A', '#EA580C', '#2563EB', '#CA8A04', '#0D9488', '#DB2777', '#0891B2', '#78350F', '#64748B'];

        return $palette[$index % count($palette)];
    }

    /**
     * Ringkasan "Jenis Usaha" (kolom kontraks.jenis_usaha), diurutkan dari
     * yang terbanyak. Bisa discope ke satu tipe_aset (untuk halaman peta),
     * atau semua data kalau $tipeAset null (untuk dashboard).
     */
    protected function jenisUsahaSummary(?string $tipeAset = null): array
    {
        $query = Kontrak::whereNotNull('jenis_usaha')->where('jenis_usaha', '!=', '');

        if ($tipeAset !== null) {
            $query->whereHas('asset', fn ($q) => $q->where('tipe_aset', $tipeAset));
        }

        $counts = $query->select('jenis_usaha', DB::raw('count(*) as total'))
            ->groupBy('jenis_usaha')
            ->orderByDesc('total')
            ->get();

        $total = (int) $counts->sum('total');

        $summary = $counts->values()->map(function ($row, $i) use ($total) {
            return [
                'label' => $row->jenis_usaha,
                'color' => self::paletteColor($i),
                'count' => (int) $row->total,
                'percent' => $total > 0 ? round($row->total / $total * 100, 1) : 0,
            ];
        });

        return [$summary, $total];
    }

    /**
     * Halaman tabel data aset (publik, tanpa login).
     */
    public function index(Request $request): View
    {
        $query = Asset::query()->withCount('kontraks');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_aset', 'like', "%{$search}%")
                    ->orWhere('sub_asset_code', 'like', "%{$search}%")
                    ->orWhere('asset_code', 'like', "%{$search}%");
            });
        }

        foreach (['kedudukan', 'jenis_aset', 'status', 'rm', 'tipe_aset'] as $filter) {
            if ($value = $request->input($filter)) {
                $query->where($filter, $value);
            }
        }

        $assets = $query->orderBy('kedudukan')->orderBy('nama_aset')
            ->paginate(25)
            ->withQueryString();

        $filterOptions = [
            'kedudukan' => Asset::select('kedudukan')->distinct()->orderBy('kedudukan')->pluck('kedudukan')->filter(),
            'jenis_aset' => Asset::select('jenis_aset')->distinct()->orderBy('jenis_aset')->pluck('jenis_aset')->filter(),
            'status' => Asset::select('status')->distinct()->orderBy('status')->pluck('status')->filter(),
            'rm' => Asset::select('rm')->distinct()->orderBy('rm')->pluck('rm')->filter(),
            'tipe_aset' => Asset::select('tipe_aset')->distinct()->orderBy('tipe_aset')->pluck('tipe_aset')->filter(),
        ];

        return view('public.assets.index', compact('assets', 'filterOptions'));
    }

    /**
     * Halaman detail satu aset beserta daftar kontrak/mitranya (publik).
     */
    public function show(Asset $asset): View
    {
        $asset->load('kontraks');

        $categories = self::categories();
        $categoryMeta = $categories[$asset->jenis_aset] ?? ['no' => null, 'color' => '#475569', 'icon' => '📍'];

        $totalNilaiKontrak = $asset->kontraks->sum('nilai_kontrak');

        return view('public.assets.show', compact('asset', 'categoryMeta', 'totalNilaiKontrak'));
    }

    /**
     * Halaman peta interaktif untuk data KD List (publik, tanpa login).
     */
    public function map(): View
    {
        return $this->renderMap('KD List');
    }

    /**
     * Halaman peta interaktif untuk data Non KD List — sengaja dipisah dari
     * peta KD List (halaman & data sendiri, tidak digabung).
     */
    public function mapNonKd(): View
    {
        return $this->renderMap('Non KD List');
    }

    protected function renderMap(string $tipeAset): View
    {
        $categories = self::categories();

        $counts = Asset::where('tipe_aset', $tipeAset)
            ->select('jenis_aset', 'status', DB::raw('count(*) as total'))
            ->groupBy('jenis_aset', 'status')
            ->get();

        $categoryData = [];
        foreach ($categories as $label => $meta) {
            $terdayaguna = (int) $counts->where('jenis_aset', $label)->where('status', 'Terdayaguna')->sum('total');
            $idle = (int) $counts->where('jenis_aset', $label)->where('status', '!=', 'Terdayaguna')->sum('total');

            $categoryData[] = [
                'no' => $meta['no'],
                'label' => $label,
                'color' => $meta['color'],
                'icon' => $meta['icon'],
                'terdayaguna' => $terdayaguna,
                'idle' => $idle,
                'total' => $terdayaguna + $idle,
            ];
        }

        // Aset dengan jenis_aset di luar 6 kategori baku
        $knownLabels = array_keys($categories);
        $otherTotal = (int) $counts->whereNotIn('jenis_aset', $knownLabels)->sum('total');

        $totalTerdayaguna = (int) $counts->where('status', 'Terdayaguna')->sum('total');
        $totalIdle = (int) $counts->where('status', '!=', 'Terdayaguna')->sum('total');

        // Ringkasan per RM (Regional Manager), sesuai tabel "Status Aset per Bulan & RM"
        $rmCounts = Asset::where('tipe_aset', $tipeAset)
            ->select('rm', 'status', DB::raw('count(*) as total'))
            ->groupBy('rm', 'status')
            ->get();

        $rmOrder = ['RM I', 'RM II', 'RM III', 'RM IV', 'RM V', 'RM VI'];
        $rmSummary = [];
        foreach ($rmOrder as $rm) {
            $terdayaguna = (int) $rmCounts->where('rm', $rm)->where('status', 'Terdayaguna')->sum('total');
            $idle = (int) $rmCounts->where('rm', $rm)->where('status', '!=', 'Terdayaguna')->sum('total');
            $rmSummary[] = ['rm' => $rm, 'terdayaguna' => $terdayaguna, 'idle' => $idle, 'total' => $terdayaguna + $idle];
        }
        // RM lain di luar RM I-VI (kalau ada), jaga-jaga
        $knownRm = $rmOrder;
        $otherRmTotal = (int) $rmCounts->whereNotIn('rm', $knownRm)->sum('total');

        // Metadata kategori Usaha (warna + icon) untuk panel "Kategori Usaha" di peta.
        // Datanya sendiri dihitung live di sisi browser dari titik yang sedang tampil
        // (ikut berubah kalau filter status/kategori/pencarian diganti) — lihat map.blade.php.
        $usahaMetaList = self::usahaMeta();

        $isNonKd = $tipeAset === 'Non KD List';

        return view('public.assets.map', compact(
            'categoryData', 'otherTotal', 'totalTerdayaguna', 'totalIdle', 'rmSummary', 'otherRmTotal',
            'usahaMetaList'
        ) + [
            'tipeAset' => $tipeAset,
            'mapDataRoute' => $isNonKd ? 'public.assets.map-non-kd.data' : 'public.assets.map.data',
            'pageTitle' => $isNonKd ? 'Peta Sebaran Aset · Non KD List' : 'Peta Sebaran Aset · KD List',
            'switchUrl' => $isNonKd ? route('public.assets.map') : route('public.assets.map-non-kd'),
            'switchLabel' => $isNonKd ? 'Lihat Peta KD List' : 'Lihat Peta Non KD List',
        ]);
    }

    /**
     * Data JSON untuk marker peta KD List.
     */
    public function mapData(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->buildMapPoints('KD List'));
    }

    /**
     * Data JSON untuk marker peta Non KD List — endpoint terpisah dari KD List.
     */
    public function mapDataNonKd(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->buildMapPoints('Non KD List'));
    }

    /**
     * Satu titik per aset, dengan koordinat di-jitter secara acak-tapi-konsisten
     * di sekitar titik pusat wilayah "Kedudukan"-nya, supaya sebaran tag tetap
     * berada di area kota yang benar. Discope ke satu tipe_aset saja
     * (KD List atau Non KD List) supaya kedua peta tidak tercampur.
     */
    protected function buildMapPoints(string $tipeAset)
    {
        $coords = config('kedudukan_map');
        $fallback = [-2.5, 118]; // titik tengah Indonesia, untuk kedudukan yang belum ada di config

        $assets = Asset::where('tipe_aset', $tipeAset)
            ->with(['kontraks' => function ($q) {
                $q->orderBy('id');
            }])->get();

        return $assets->map(function (Asset $asset) use ($coords, $fallback) {
            if ($asset->latitude !== null && $asset->longitude !== null) {
                // Koordinat presisi dari data survey/GPS lapangan (dicocokkan lewat Asset Code)
                $lat = (float) $asset->latitude;
                $lng = (float) $asset->longitude;
            } else {
                // Fallback: posisi acak-tapi-konsisten di sekitar wilayah "Kedudukan"-nya,
                // untuk aset yang belum ada koordinat presisinya.
                $base = $coords[strtoupper((string) $asset->kedudukan)] ?? $fallback;

                mt_srand(crc32($asset->sub_asset_code));
                $lat = $base[0] + (mt_rand(-120, 120) / 1000);
                $lng = $base[1] + (mt_rand(-120, 120) / 1000);
            }

            $kontraks = $asset->kontraks->map(fn ($k) => [
                'nama_mitra_kerjasama' => $k->nama_mitra_kerjasama,
                'jenis_usaha' => $k->jenis_usaha,
                'usaha' => $k->usaha,
                'luas_tanah_kontrak' => $k->luas_tanah_kontrak,
                'luas_bangunan_kontrak' => $k->luas_bangunan_kontrak,
                'nilai_kontrak' => $k->nilai_kontrak,
                'masa_kerjasama' => $k->masa_kerjasama,
                'tanggal_mulai_kerjasama' => optional($k->tanggal_mulai_kerjasama)->format('d M Y'),
                'tanggal_berakhir_kerjasama' => optional($k->tanggal_berakhir_kerjasama)->format('d M Y'),
            ]);

            return [
                'id' => $asset->id,
                'nama_aset' => $asset->nama_aset,
                'kedudukan' => $asset->kedudukan,
                'rm' => $asset->rm,
                'jenis_aset' => $asset->jenis_aset,
                'status' => $asset->status,
                'luas_tanah' => $asset->luas_tanah,
                'luas_bangunan' => $asset->luas_bangunan,
                'kondisi_fisik' => $asset->kondisi_fisik,
                'status_pendayagunaan' => $asset->status_pendayagunaan,
                'is_precise' => $asset->latitude !== null && $asset->longitude !== null,
                'lat' => $lat,
                'lng' => $lng,
                'kontraks' => $kontraks,
                'detail_url' => route('public.assets.show', $asset),
            ];
        });
    }

    /**
     * Halaman peta gaya infografis: 1 titik per Kedudukan/wilayah, dengan
     * label box selalu terbuka (bukan hover/klik) menampilkan total aset
     * terdayaguna beserta breakdown 6 kategori — meniru desain infografis resmi.
     */
    public function mapProvinsi(): View
    {
        $categories = self::categories();

        $rows = Asset::where('status', 'Terdayaguna')
            ->select('kedudukan', 'jenis_aset', DB::raw('count(*) as total'))
            ->groupBy('kedudukan', 'jenis_aset')
            ->get()
            ->groupBy('kedudukan');

        $coordsConfig = config('kedudukan_map');

        $regionSummary = [];
        foreach ($rows as $kedudukan => $items) {
            if (! $kedudukan) {
                continue;
            }

            $counts = [];
            $regionTotal = 0;
            foreach ($categories as $label => $meta) {
                $count = (int) optional($items->firstWhere('jenis_aset', $label))->total;
                $counts[] = $count;
                $regionTotal += $count;
            }

            $coord = $coordsConfig[strtoupper($kedudukan)] ?? null;

            if (! $coord) {
                continue; // skip kalau belum ada titik koordinat wilayahnya
            }

            $regionSummary[] = [
                'kedudukan' => $kedudukan,
                'total' => $regionTotal,
                'counts' => $counts,
                'lat' => $coord[0],
                'lng' => $coord[1],
            ];
        }

        usort($regionSummary, fn ($a, $b) => $b['total'] <=> $a['total']);

        $total = (int) collect($regionSummary)->sum('total');

        return view('public.assets.map-provinsi', [
            'categoryList' => $categories,
            'regionSummary' => $regionSummary,
            'total' => $total,
            'updatePeriode' => 'Juni 2026',
        ]);
    }

    /**
     * Halaman dashboard / infografis (publik, tanpa login).
     */
    public function dashboard(): View
    {
        $categories = self::categories();

        $total = Asset::where('status', 'Terdayaguna')->count();

        $perCategoryCounts = Asset::where('status', 'Terdayaguna')
            ->select('jenis_aset', DB::raw('count(*) as total'))
            ->groupBy('jenis_aset')
            ->pluck('total', 'jenis_aset');

        $categorySummary = [];
        foreach ($categories as $label => $meta) {
            $count = (int) ($perCategoryCounts[$label] ?? 0);
            $categorySummary[] = [
                'no' => $meta['no'],
                'label' => $label,
                'color' => $meta['color'],
                'count' => $count,
                'percent' => $total > 0 ? round($count / $total * 100, 1) : 0,
            ];
        }

        // Rekap per kategori yang datanya tidak masuk 6 kategori baku (jaga-jaga)
        $otherCount = $perCategoryCounts->except(array_keys($categories))->sum();

        // Breakdown per wilayah (kedudukan), kolom sesuai urutan kategori
        $rows = Asset::where('status', 'Terdayaguna')
            ->select('kedudukan', 'jenis_aset', DB::raw('count(*) as total'))
            ->groupBy('kedudukan', 'jenis_aset')
            ->get()
            ->groupBy('kedudukan');

        $regionSummary = [];
        foreach ($rows as $kedudukan => $items) {
            $counts = [];
            $regionTotal = 0;
            foreach ($categories as $label => $meta) {
                $count = (int) optional($items->firstWhere('jenis_aset', $label))->total;
                $counts[] = $count;
                $regionTotal += $count;
            }
            $regionSummary[] = [
                'kedudukan' => $kedudukan,
                'total' => $regionTotal,
                'counts' => $counts,
            ];
        }
        usort($regionSummary, fn ($a, $b) => strcmp($a['kedudukan'] ?? '', $b['kedudukan'] ?? ''));

        // Ringkasan "Kategori Usaha" (kolom kontraks.usaha), diurutkan dari yang terbanyak
        $usahaMeta = self::usahaMeta();
        $usahaCounts = Kontrak::whereNotNull('usaha')
            ->where('usaha', '!=', '')
            ->select('usaha', DB::raw('count(*) as total'))
            ->groupBy('usaha')
            ->orderByDesc('total')
            ->get();

        $totalUsaha = (int) $usahaCounts->sum('total');
        $usahaSummary = $usahaCounts->map(function ($row) use ($usahaMeta, $totalUsaha) {
            $meta = $usahaMeta[$row->usaha] ?? ['color' => '#64748B', 'icon' => '📍'];
            return [
                'label' => $row->usaha,
                'color' => $meta['color'],
                'icon' => $meta['icon'],
                'count' => (int) $row->total,
                'percent' => $totalUsaha > 0 ? round($row->total / $totalUsaha * 100, 1) : 0,
            ];
        })->values();

        [$jenisUsahaSummary, $totalJenisUsaha] = $this->jenisUsahaSummary();

        return view('public.assets.dashboard', [
            'total' => $total,
            'categorySummary' => $categorySummary,
            'regionSummary' => $regionSummary,
            'otherCount' => $otherCount,
            'usahaSummary' => $usahaSummary,
            'totalUsaha' => $totalUsaha,
            'jenisUsahaSummary' => $jenisUsahaSummary,
            'totalJenisUsaha' => $totalJenisUsaha,
            'updatePeriode' => 'Juni 2026',
        ]);
    }
}