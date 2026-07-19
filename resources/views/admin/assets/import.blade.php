<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data Aset - Dashboard Opaset Bulog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">

    <div class="max-w-3xl mx-auto px-6 py-10">

        <div class="mb-8">
            <p class="text-sm font-medium text-orange-600 tracking-wide uppercase">Dashboard Opaset Bulog</p>
            <h1 class="text-2xl font-semibold text-slate-900 mt-1">Import Data Aset dari Excel</h1>
            <p class="text-slate-500 mt-2 text-sm">
                Upload file mapping KD List (format sesuai template resmi) untuk memasukkan data aset
                dan kontrak/mitra kerjasama ke database. Data diambil dari sheet pertama, baris 5 s/d 592.
            </p>
        </div>

        {{-- Pesan hasil import --}}
        @if (session('result'))
            @php $result = session('result'); @endphp
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                <p class="font-medium text-emerald-800 mb-2">Import selesai</p>
                <ul class="text-sm text-emerald-700 space-y-1">
                    <li>Aset baru dibuat: <span class="font-semibold">{{ $result['assets_created'] }}</span></li>
                    <li>Aset diperbarui (sudah ada sebelumnya): <span class="font-semibold">{{ $result['assets_updated'] }}</span></li>
                    <li>Data kontrak/mitra dibuat: <span class="font-semibold">{{ $result['kontrak_created'] }}</span></li>
                </ul>

                @if (!empty($result['errors']))
                    <div class="mt-4 pt-4 border-t border-emerald-200">
                        <p class="font-medium text-amber-700 mb-1">{{ count($result['errors']) }} baris dilewati / bermasalah:</p>
                        <ul class="text-xs text-amber-700 max-h-40 overflow-y-auto space-y-0.5">
                            @foreach ($result['errors'] as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        {{-- Pesan error validasi --}}
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-5">
                <p class="font-medium text-red-800 mb-2">Terjadi kesalahan</p>
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form upload --}}
        <form action="{{ route('admin.assets.import.store') }}" method="POST" enctype="multipart/form-data"
              class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            @csrf

            <label for="excel_file" class="block text-sm font-medium text-slate-700 mb-2">
                File Excel (.xlsx / .xls)
            </label>

            <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required
                   class="block w-full text-sm text-slate-600
                          file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                          file:text-sm file:font-medium file:bg-orange-50 file:text-orange-700
                          hover:file:bg-orange-100 border border-slate-200 rounded-lg p-2">

            <p class="text-xs text-slate-400 mt-2">Maksimal ukuran file 10MB.</p>

            <button type="submit"
                    class="mt-5 inline-flex items-center gap-2 bg-slate-900 text-white text-sm font-medium
                           px-5 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                Upload &amp; Import
            </button>
        </form>

        <div class="mt-6 text-xs text-slate-400">
            Catatan: proses import akan membuat data aset baru berdasarkan <span class="font-mono">Sub Asset Code</span>,
            atau memperbarui data aset yang kodenya sudah ada. Setiap baris kontrak/mitra pada file akan ditambahkan
            sebagai data baru terkait aset tersebut.
        </div>

    </div>

</body>
</html>