<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mapping Aset Terdayaguna (KD List) - Bulog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body class="bg-slate-100 min-h-screen">

    {{-- Header banner --}}
    <div class="bg-gradient-to-r from-[#0F2A5C] to-[#1E4C9A] border-b-4 border-orange-400">
        <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="text-orange-300 text-xs font-semibold tracking-widest uppercase">BUMN Untuk Indonesia &middot; Bulog</p>
                <h1 class="text-white text-2xl font-extrabold tracking-tight mt-0.5">
                    DATA MAPPING ASET TERDAYAGUNA (KD LIST)
                </h1>
                <p class="text-white/80 text-sm mt-1">
                    Update {{ $updatePeriode }} &nbsp;|&nbsp; Total Aset : <span class="font-semibold">{{ $total }}</span> Aset
                </p>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('public.assets.dashboard') }}" class="text-white font-medium">Dashboard</a>
                <a href="{{ route('public.assets.index') }}" class="text-white/80 hover:text-white">Data Aset</a>
                <a href="{{ route('login') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded-lg">Login Admin</a>
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-8">

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

            {{-- Kiri: breakdown per wilayah --}}
            <div class="xl:col-span-3">
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">
                            Rincian Aset Terdayaguna per Kedudukan
                        </h2>
                        <span class="text-xs bg-[#0F2A5C] text-white px-3 py-1 rounded-full">
                            Total {{ $total }} Aset
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @forelse ($regionSummary as $region)
                            <div class="border border-slate-200 rounded-lg p-3 hover:border-orange-300 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-semibold text-slate-800 truncate" title="{{ $region['kedudukan'] }}">
                                        {{ $region['kedudukan'] ?? '(Tanpa Kedudukan)' }}
                                    </p>
                                    <span class="text-xs font-bold text-slate-500">{{ $region['total'] }} Aset</span>
                                </div>
                                <div class="grid grid-cols-6 gap-1">
                                    @foreach ($categorySummary as $i => $cat)
                                        <div class="text-center">
                                            <div class="text-[13px] font-bold" style="color: {{ $cat['color'] }}">
                                                {{ $region['counts'][$i] }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400 col-span-full text-center py-10">
                                Belum ada data. Silakan import data lewat halaman admin.
                            </p>
                        @endforelse
                    </div>

                    <p class="text-xs text-slate-400 mt-4">
                        Angka berwarna menunjukkan jumlah aset terdayaguna per kategori 1–6 (lihat legenda di samping), untuk setiap kedudukan/wilayah.
                    </p>
                </div>
            </div>

            {{-- Kanan: legenda kategori --}}
            <div class="xl:col-span-1">
                <div class="bg-[#0F2A5C] rounded-xl shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-[#0B2047]">
                        <h2 class="text-white text-sm font-semibold uppercase tracking-wide">Legenda Kategori Aset</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        @foreach ($categorySummary as $cat)
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center text-white text-xs font-bold"
                                     style="background-color: {{ $cat['color'] }}">
                                    {{ $cat['no'] }}
                                </div>
                                <div>
                                    <p class="text-white text-sm font-semibold">{{ $cat['label'] }} - {{ $cat['count'] }} aset</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Bawah: ringkasan, donut chart, keterangan --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

            {{-- Ringkasan total per kategori --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="bg-[#0F2A5C] px-4 py-3">
                    <h3 class="text-white text-sm font-semibold uppercase tracking-wide text-center">
                        Ringkasan Total Aset Terdayaguna per Kategori
                    </h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($categorySummary as $cat)
                        <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                            <span class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $cat['color'] }}"></span>
                                <span class="text-slate-700">{{ $cat['no'] }}. {{ $cat['label'] }}</span>
                            </span>
                            <span class="flex items-center gap-3">
                                <span class="font-semibold text-slate-800">{{ $cat['count'] }}</span>
                                <span class="text-slate-400 text-xs w-12 text-right">{{ number_format($cat['percent'], 1, ',', '.') }}%</span>
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="bg-[#0F2A5C] px-4 py-2.5 flex items-center justify-between text-sm">
                    <span class="text-white font-semibold">TOTAL</span>
                    <span class="text-white font-semibold">{{ $total }} &middot; 100%</span>
                </div>
            </div>

            {{-- Donut chart --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="bg-[#0F2A5C] px-4 py-3">
                    <h3 class="text-white text-sm font-semibold uppercase tracking-wide text-center">
                        Proporsi Aset Terdayaguna per Kategori
                    </h3>
                </div>
                <div class="p-5">
                    <canvas id="categoryDonut" height="220"></canvas>
                </div>
            </div>

            {{-- Keterangan --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="bg-[#0F2A5C] px-4 py-3">
                    <h3 class="text-white text-sm font-semibold uppercase tracking-wide text-center italic">
                        Keterangan
                    </h3>
                </div>
                <div class="p-5">
                    <ul class="text-sm text-slate-600 space-y-2 list-disc list-inside">
                        <li>Data ini hanya menampilkan aset dengan status <span class="font-medium">Terdayaguna</span> per kedudukan.</li>
                        <li>Angka berwarna pada tiap kedudukan menunjukkan jumlah aset per kategori 1–6.</li>
                        <li>Sumber data: update {{ $updatePeriode }}.</li>
                        @if ($otherCount > 0)
                            <li>{{ $otherCount }} aset memiliki jenis di luar 6 kategori baku dan belum masuk grafik.</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('categoryDonut');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: @json(array_column($categorySummary, 'label')),
                datasets: [{
                    data: @json(array_column($categorySummary, 'count')),
                    backgroundColor: @json(array_column($categorySummary, 'color')),
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (item) => `${item.label}: ${item.raw} aset`
                        }
                    }
                },
                cutout: '62%',
            }
        });
    </script>

</body>
</html>