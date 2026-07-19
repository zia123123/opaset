<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Detail Usaha - Dashboard Opaset Bulog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">

    <div class="max-w-3xl mx-auto px-6 py-10">

        <div class="mb-8">
            <p class="text-sm font-medium text-orange-600 tracking-wide uppercase">Dashboard Opaset Bulog</p>
            <h1 class="text-2xl font-semibold text-slate-900 mt-1">Import Detail Jenis Usaha</h1>
            <p class="text-slate-500 mt-2 text-sm">
                Upload file "bahan mapping" (terdayaguna dan/atau idle). File ini dipakai untuk melengkapi
                <span class="font-medium">Jenis Aset</span>, <span class="font-medium">Usaha</span>, dan
                <span class="font-medium">Jenis Usaha</span> pada data yang sudah ada, dicocokkan lewat
                <span class="font-mono text-xs">Sub Asset Code</span>. Bisa pilih 2 file sekaligus (terdayaguna + idle).
            </p>
        </div>

        @if (session('result'))
            @php $r = session('result'); @endphp
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                <p class="font-medium text-emerald-800 mb-2">Import selesai</p>
                <ul class="text-sm text-emerald-700 space-y-1">
                    <li>Aset yang cocok (Sub Asset Code ditemukan): <span class="font-semibold">{{ $r['matched_assets'] }}</span></li>
                    <li>Aset tidak ditemukan: <span class="font-semibold">{{ $r['unmatched_assets'] }}</span></li>
                    <li>Data kontrak diperbarui (Usaha / Jenis Usaha): <span class="font-semibold">{{ $r['kontrak_updated'] }}</span></li>
                </ul>

                @if (!empty($r['errors']))
                    <div class="mt-4 pt-4 border-t border-emerald-200">
                        <p class="font-medium text-amber-700 mb-1">{{ count($r['errors']) }} catatan:</p>
                        <ul class="text-xs text-amber-700 max-h-48 overflow-y-auto space-y-0.5">
                            @foreach ($r['errors'] as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-5">
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.assets.import-usaha.store') }}" method="POST" enctype="multipart/form-data"
              class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            @csrf

            <label for="excel_files" class="block text-sm font-medium text-slate-700 mb-2">
                File Excel (.xlsx / .xls) - bisa pilih lebih dari satu
            </label>

            <input type="file" name="excel_files[]" id="excel_files" accept=".xlsx,.xls" multiple required
                   class="block w-full text-sm text-slate-600
                          file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                          file:text-sm file:font-medium file:bg-orange-50 file:text-orange-700
                          hover:file:bg-orange-100 border border-slate-200 rounded-lg p-2">

            <p class="text-xs text-slate-400 mt-2">Maksimal 10MB per file.</p>

            <button type="submit"
                    class="mt-5 inline-flex items-center gap-2 bg-slate-900 text-white text-sm font-medium
                           px-5 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                Upload &amp; Proses
            </button>
        </form>

        <p class="mt-6 text-xs text-slate-400">
            Pastikan data aset utama (KD List) sudah di-import lebih dulu lewat
            <a href="{{ route('admin.assets.import') }}" class="text-orange-600 hover:underline">halaman import utama</a>,
            karena proses ini hanya melengkapi data yang sudah ada, bukan membuat aset baru.
        </p>

    </div>

</body>
</html>