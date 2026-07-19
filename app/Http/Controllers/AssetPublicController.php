<?php

namespace App\Http\Controllers;

use App\Models\Asset;
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
            'Rumah Perusahaan' => ['no' => 1, 'color' => '#DC2626', 'icon' => '🏠'],
            'Gedung / Ruang' => ['no' => 2, 'color' => '#7C3AED', 'icon' => '🏢'],
            'Lahan Kosong' => ['no' => 3, 'color' => '#16A34A', 'icon' => '🍀'],
            'Gudang' => ['no' => 4, 'color' => '#C2410C', 'icon' => '📦'],
            'Wisma / GSG / Hotel' => ['no' => 5, 'color' => '#2563EB', 'icon' => '🏨'],
            'Kawasan Bisnis' => ['no' => 6, 'color' => '#991B1B', 'icon' => '💼'],
        ];
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

        $isNonKd = $tipeAset === 'Non KD List';

        return view('public.assets.map', compact(
            'categoryData', 'otherTotal', 'totalTerdayaguna', 'totalIdle', 'rmSummary', 'otherRmTotal'
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
            $base = $coords[strtoupper((string) $asset->kedudukan)] ?? $fallback;

            // Seed dari sub_asset_code supaya posisi random-nya konsisten setiap kali diakses.
            // Radius kecil (~±0.12 derajat, ±13km) supaya titik tetap berada di area kota,
            // tidak melenceng jauh sampai ke laut.
            mt_srand(crc32($asset->sub_asset_code));
            $lat = $base[0] + (mt_rand(-120, 120) / 1000);
            $lng = $base[1] + (mt_rand(-120, 120) / 1000);

            $kontraks = $asset->kontraks->map(fn ($k) => [
                'nama_mitra_kerjasama' => $k->nama_mitra_kerjasama,
                'jenis_usaha' => $k->jenis_usaha,
                'usaha' => $k->usaha,
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
                'lat' => $lat,
                'lng' => $lng,
                'kontraks' => $kontraks,
                'detail_url' => route('public.assets.show', $asset),
            ];
        });
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

        return view('public.assets.dashboard', [
            'total' => $total,
            'categorySummary' => $categorySummary,
            'regionSummary' => $regionSummary,
            'otherCount' => $otherCount,
            'updatePeriode' => 'Juni 2026',
        ]);
    }
}