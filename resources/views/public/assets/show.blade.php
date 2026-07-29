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
    <div class="bg-gradient-to-r from-[#0F2A5C] to-[#1E4C9A] border-b-4 border-orange-400">
        <div class="max-w-5xl mx-auto px-6 py-5 flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="text-orange-300 text-xs font-semibold tracking-widest uppercase">BUMN Untuk Indonesia &middot; Bulog</p>
                <h1 class="text-white text-lg font-bold mt-0.5">Detail Aset</h1>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('public.assets.dashboard') }}" class="text-white/80 hover:text-white">Dashboard</a>
                <a href="{{ route('public.assets.map') }}" class="text-white/80 hover:text-white">Peta</a>
                <a href="{{ route('public.assets.map-non-kd') }}" class="text-white/80 hover:text-white">Peta Non KD</a>
                <a href="{{ route('public.assets.index') }}" class="text-white/80 hover:text-white">Data Aset</a>
            </nav>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-6 py-8">

        <a href="{{ route('public.assets.index') }}" class="text-sm text-slate-500 hover:text-orange-600 mb-4 inline-flex items-center gap-1">
            <span>&larr;</span> Kembali ke daftar aset
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

            <div class="px-6 pt-3">
                <span @class([
                    'inline-block px-2.5 py-1 rounded-full text-[11px] font-semibold',
                    'bg-blue-100 text-blue-700' => $asset->tipe_aset === 'KD List',
                    'bg-purple-100 text-purple-700' => $asset->tipe_aset !== 'KD List',
                ])>
                    {{ $asset->tipe_aset }}
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

        {{-- Status Pendayagunaan (khususnya buat aset Idle) --}}
        @if ($asset->status_pendayagunaan || $asset->kondisi_fisik || $asset->keterangan)
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6">
                <h3 class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-3">Status Pendayagunaan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @if ($asset->status_pendayagunaan)
                        <div>
                            <dt class="text-slate-400 text-xs">Status Pendayagunaan</dt>
                            <dd class="text-slate-800 font-medium">{{ $asset->status_pendayagunaan }}</dd>
                        </div>
                    @endif
                    @if ($asset->kondisi_fisik)
                        <div>
                            <dt class="text-slate-400 text-xs">Kondisi Fisik</dt>
                            <dd class="text-slate-800 font-medium">{{ $asset->kondisi_fisik }}</dd>
                        </div>
                    @endif
                    @if ($asset->keterangan)
                        <div class="md:col-span-2">
                            <dt class="text-slate-400 text-xs">Keterangan</dt>
                            <dd class="text-slate-700">{{ $asset->keterangan }}</dd>
                        </div>
                    @endif
                </div>
            </div>
        @endif

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
                            <dt class="text-slate-400 text-xs">Luas Tanah (Kontrak)</dt>
                            <dd class="text-slate-700">{{ $kontrak->luas_tanah_kontrak ? number_format($kontrak->luas_tanah_kontrak, 0, ',', '.') . ' m²' : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 text-xs">Luas Bangunan (Kontrak)</dt>
                            <dd class="text-slate-700">{{ $kontrak->luas_bangunan_kontrak ? number_format($kontrak->luas_bangunan_kontrak, 0, ',', '.') . ' m²' : '-' }}</dd>
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