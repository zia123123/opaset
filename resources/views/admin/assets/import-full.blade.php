<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Data Terbaru - Dashboard Opaset Bulog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">

    <div class="max-w-3xl mx-auto px-6 py-10">

        <div class="mb-8">
            <p class="text-sm font-medium text-orange-600 tracking-wide uppercase">Dashboard Opaset Bulog</p>
            <h1 class="text-2xl font-semibold text-slate-900 mt-1">Update Data Terbaru (1 File Lengkap)</h1>
            <p class="text-slate-500 mt-2 text-sm">
                Upload file mapping bulanan terbaru (format seperti "MAPPING KD LIST NON KD LIST"). File ini otomatis
                membaca <b>semua sheet</b> data aset di dalamnya (KD List, Non KD List, dst — sheet "LONGLAT" dilewati
                otomatis), dan memperbarui: data aset, koordinat lokasi (Latitude/Longitude), status, serta seluruh
                data mitra/kontrak (termasuk Usaha &amp; Jenis Usaha) sekaligus dalam satu proses.
            </p>
        </div>

        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-xs text-red-700">
            ⚠️ <b>Mode RESET TOTAL:</b> seluruh data aset & kontrak yang ada di database saat ini akan <b>DIHAPUS BERSIH</b>
            terlebih dahulu, baru diisi ulang dari file yang diupload. Bukan update/gabung — hasil akhirnya akan
            persis sama dengan isi file ini. Data <b>Status Pendayagunaan / Kondisi Fisik / Keterangan</b> (yang
            disimpan terpisah di tabel aset) juga ikut terhapus — kalau masih dibutuhkan, upload ulang file itu
            setelah proses ini selesai lewat
            <a href="{{ route('admin.assets.import-pendayagunaan') }}" class="underline font-medium">halaman import status pendayagunaan</a>.
        </div>

        @if (session('result'))
            @php $r = session('result'); @endphp
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                <p class="font-medium text-emerald-800 mb-2">Import selesai</p>
                <ul class="text-sm text-emerald-700 space-y-1">
                    <li>Sheet yang diproses: <span class="font-semibold">{{ implode(', ', $r['processed_sheets']) ?: '-' }}</span></li>
                    @if (!empty($r['skipped_sheets']))
                        <li>Sheet yang dilewati (bukan sheet data): <span class="font-semibold">{{ implode(', ', $r['skipped_sheets']) }}</span></li>
                    @endif
                    <li>Aset baru dibuat: <span class="font-semibold">{{ $r['assets_created'] }}</span></li>
                    <li>Aset diperbarui: <span class="font-semibold">{{ $r['assets_updated'] }}</span></li>
                    <li>Data kontrak/mitra dibuat: <span class="font-semibold">{{ $r['kontrak_created'] }}</span></li>
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

        <form action="{{ route('admin.assets.import-full.store') }}" method="POST" enctype="multipart/form-data"
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

            <p class="text-xs text-slate-400 mt-2">Maksimal 20MB.</p>

            <div id="sheet-picker" class="mt-4 hidden">
                <p class="text-sm font-medium text-slate-700 mb-2">
                    Pilih sheet yang mau diproses <span class="text-slate-400 font-normal">(kosongkan semua = proses semua sheet otomatis)</span>
                </p>
                <div id="sheet-list" class="space-y-1.5 border border-slate-200 rounded-lg p-3 bg-slate-50"></div>
            </div>
            <p id="sheet-loading" class="text-xs text-slate-400 mt-2 hidden">Membaca daftar sheet...</p>

            <button type="submit"
                    class="mt-5 inline-flex items-center gap-2 bg-slate-900 text-white text-sm font-medium
                           px-5 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                Upload &amp; Update Semua Data
            </button>
        </form>

        <script>
            const fileInput = document.getElementById('excel_file');
            const sheetPicker = document.getElementById('sheet-picker');
            const sheetList = document.getElementById('sheet-list');
            const sheetLoading = document.getElementById('sheet-loading');

            fileInput.addEventListener('change', async () => {
                if (!fileInput.files.length) return;

                sheetPicker.classList.add('hidden');
                sheetLoading.classList.remove('hidden');

                const formData = new FormData();
                formData.append('excel_file', fileInput.files[0]);
                formData.append('_token', document.querySelector('input[name="_token"]').value);

                try {
                    const res = await fetch('{{ route('admin.assets.import-full.sheets') }}', {
                        method: 'POST',
                        body: formData,
                    });
                    const data = await res.json();

                    sheetList.innerHTML = data.sheets.map(name => `
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="sheets[]" value="${name}" class="rounded border-slate-300 accent-orange-600">
                            <span>${name}</span>
                        </label>
                    `).join('');

                    sheetPicker.classList.remove('hidden');
                } catch (e) {
                    sheetList.innerHTML = '<p class="text-xs text-red-500">Gagal membaca daftar sheet, tapi tetap bisa diupload (semua sheet akan diproses otomatis).</p>';
                    sheetPicker.classList.remove('hidden');
                } finally {
                    sheetLoading.classList.add('hidden');
                }
            });
        </script>

    </div>

</body>
</html>