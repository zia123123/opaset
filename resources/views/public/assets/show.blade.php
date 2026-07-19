<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $asset->nama_aset }} - Dashboard Opaset Bulog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">

    {{-- Header --}}
  

    <div class="max-w-5xl mx-auto px-6 py-8">

        <a href="{{ route('public.assets.map') }}" class="text-sm text-slate-500 hover:text-orange-600 mb-4 inline-flex items-center gap-1">
            <span>&larr;</span> Kembali ke map
        </a>

        {{-- Kartu utama: header kategori berwarna + info aset --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center gap-4" style="background: linear-gradient(135deg, {{ $categoryMeta['color'] }}15, {{ $categoryMeta['color'] }}05);">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl flex-shrink-0"
                     style="background-color: {{ $categoryMeta['color'] }}22; border: 2px solid {{ $categoryMeta['color'] }}">
                    {{ $categoryMeta['icon'] }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: {{ $categoryMeta['color'] }}">
                        {{ $asset->jenis_aset ?? 'Kategori belum ditentukan' }}
                    </p>
                    <h2 class="text-xl font-bold text-slate-900 truncate">{{ $asset->nama_aset }}</h2>
                    <p class="text-sm text-slate-500">{{ $asset->kedudukan }} &middot; {{ $asset->rm }}</p>
                </div>
                <span @class([
                    'px-3 py-1.5 rounded-full text-xs font-semibold flex-shrink-0',
                    'bg-emerald-100 text-emerald-700' => $asset->status === 'Terdayaguna',
                    'bg-slate-200 text-slate-600' => $asset->status !== 'Terdayaguna',
                ])>
                    {{ $asset->status === 'Terdayaguna' ? '● Terdayaguna' : '○ Idle' }}
                </span>
            </div>

            {{-- Grid info detail --}}
            <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-slate-100 border-t border-slate-100">
                <div class="p-4">
                    <p class="text-[11px] text-slate-400 uppercase tracking-wide">Kode Aset</p>
                    <p class="text-sm font-semibold text-slate-800 font-mono mt-1">{{ $asset->asset_code ?? '-' }}</p>
                </div>
                <div class="p-4">
                    <p class="text-[11px] text-slate-400 uppercase tracking-wide">Sub Asset Code</p>
                    <p class="text-sm font-semibold text-slate-800 font-mono mt-1">{{ $asset->sub_asset_code }}</p>
                </div>
                <div class="p-4">
                    <p class="text-[11px] text-slate-400 uppercase tracking-wide">Luas Tanah</p>
                    <p class="text-sm font-semibold text-slate-800 mt-1">{{ number_format($asset->luas_tanah ?? 0, 0, ',', '.') }} m²</p>
                </div>
                <div class="p-4">
                    <p class="text-[11px] text-slate-400 uppercase tracking-wide">Luas Bangunan</p>
                    <p class="text-sm font-semibold text-slate-800 mt-1">{{ number_format($asset->luas_bangunan ?? 0, 0, ',', '.') }} m²</p>
                </div>
            </div>
        </div>

        {{-- Ringkasan mitra/kontrak --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4">
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">Jumlah Mitra</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $asset->kontraks->count() }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 col-span-2 md:col-span-1">
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">Total Nilai Kontrak</p>
                <p class="text-xl font-bold text-emerald-600 mt-1">Rp {{ number_format($totalNilaiKontrak ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4">
                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">Status Aset</p>
                <p class="text-sm font-semibold mt-1" style="color: {{ $asset->status === 'Terdayaguna' ? '#059669' : '#64748B' }}">
                    {{ $asset->status }}
                </p>
            </div>
        </div>

        <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3 flex items-center gap-2">
            <span>🤝</span> Mitra / Kontrak Kerjasama
        </h3>

        <div class="space-y-4">
            @forelse ($asset->kontraks as $kontrak)
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden hover:border-orange-300 transition-colors">
                    <div class="px-5 py-3 bg-slate-50 flex items-center justify-between border-b border-slate-100">
                        <p class="font-semibold text-slate-800 text-sm">{{ $kontrak->nama_mitra_kerjasama ?? '-' }}</p>
                        <span class="text-[11px] font-medium text-slate-500 bg-white border border-slate-200 px-2 py-0.5 rounded-full">
                            {{ $kontrak->masa_kerjasama ?? '-' }}
                        </span>
                    </div>
                    <div class="p-5 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <dt class="text-slate-400 text-xs">Usaha</dt>
                            <dd class="text-slate-700 font-medium">{{ $kontrak->usaha ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 text-xs">Jenis Usaha</dt>
                            <dd class="text-slate-700 font-medium">{{ $kontrak->jenis_usaha ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 text-xs">Alamat</dt>
                            <dd class="text-slate-700">{{ $kontrak->address ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 text-xs">Nilai Kontrak</dt>
                            <dd class="text-emerald-600 font-semibold">Rp {{ number_format($kontrak->nilai_kontrak ?? 0, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 text-xs">Tanggal Mulai</dt>
                            <dd class="text-slate-700">{{ optional($kontrak->tanggal_mulai_kerjasama)->format('d M Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 text-xs">Tanggal Berakhir</dt>
                            <dd class="text-slate-700">{{ optional($kontrak->tanggal_berakhir_kerjasama)->format('d M Y') ?? '-' }}</dd>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
                    <p class="text-3xl mb-2">📭</p>
                    <p class="text-sm text-slate-400">Aset ini belum memiliki data mitra/kontrak kerjasama.</p>
                </div>
            @endforelse
        </div>
    </div>

</body>
</html>