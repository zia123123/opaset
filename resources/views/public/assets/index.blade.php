<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Aset - Dashboard Opaset Bulog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-[#0F2A5C] to-[#1E4C9A] border-b-4 border-orange-400">
        <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
            <div>
                <p class="text-orange-300 text-xs font-semibold tracking-widest uppercase">Bulog</p>
                <h1 class="text-white text-xl font-bold mt-0.5">Data Mapping Aset (KD List)</h1>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('public.assets.dashboard') }}" class="text-white/80 hover:text-white">Dashboard</a>
                <a href="{{ route('public.assets.index') }}" class="text-white font-medium">Data Aset</a>
                <a href="{{ route('login') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded-lg">Login Admin</a>
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-8">

        {{-- Filter --}}
        <form method="GET" class="bg-white border border-slate-200 rounded-xl p-4 mb-6 shadow-sm grid grid-cols-1 md:grid-cols-6 gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama aset / kode..."
                   class="md:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">

            <select name="kedudukan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Kedudukan</option>
                @foreach ($filterOptions['kedudukan'] as $opt)
                    <option value="{{ $opt }}" @selected(request('kedudukan') === $opt)>{{ $opt }}</option>
                @endforeach
            </select>

            <select name="jenis_aset" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Jenis Aset</option>
                @foreach ($filterOptions['jenis_aset'] as $opt)
                    <option value="{{ $opt }}" @selected(request('jenis_aset') === $opt)>{{ $opt }}</option>
                @endforeach
            </select>

            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                @foreach ($filterOptions['status'] as $opt)
                    <option value="{{ $opt }}" @selected(request('status') === $opt)>{{ $opt }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-slate-900 hover:bg-slate-700 text-white text-sm font-medium rounded-lg px-3 py-2">
                    Filter
                </button>
                @if (request()->anyFilled(['q','kedudukan','jenis_aset','status']))
                    <a href="{{ route('public.assets.index') }}" class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg px-3 py-2">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <p class="text-sm text-slate-500 mb-3">
            Menampilkan {{ $assets->firstItem() ?? 0 }}–{{ $assets->lastItem() ?? 0 }} dari {{ $assets->total() }} aset
        </p>

        {{-- Tabel --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">RM</th>
                            <th class="px-4 py-3 text-left">Kedudukan</th>
                            <th class="px-4 py-3 text-left">Nama Aset</th>
                            <th class="px-4 py-3 text-left">Jenis Aset</th>
                            <th class="px-4 py-3 text-left">Kode Aset</th>
                            <th class="px-4 py-3 text-right">Luas Tanah (m²)</th>
                            <th class="px-4 py-3 text-right">Luas Bangunan (m²)</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-center">Mitra</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($assets as $asset)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-600">{{ $asset->rm }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $asset->kedudukan }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">
                                    <a href="{{ route('public.assets.show', $asset) }}" class="hover:text-orange-600 hover:underline">
                                        {{ $asset->nama_aset }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $asset->jenis_aset }}</td>
                                <td class="px-4 py-3 text-slate-500 font-mono text-xs">{{ $asset->asset_code }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ number_format($asset->luas_tanah ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ number_format($asset->luas_bangunan ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'px-2 py-0.5 rounded-full text-xs font-medium',
                                        'bg-emerald-100 text-emerald-700' => $asset->status === 'Terdayaguna',
                                        'bg-amber-100 text-amber-700' => $asset->status !== 'Terdayaguna',
                                    ])>
                                        {{ $asset->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ $asset->kontraks_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-slate-400">
                                    Belum ada data. Silakan import data lewat halaman admin.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $assets->links() }}
        </div>
    </div>

</body>
</html>