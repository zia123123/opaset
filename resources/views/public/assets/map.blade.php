<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }} - Dashboard Opaset Bulog</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

    <style>
        html, body { height: 100%; margin: 0; overflow: hidden; }
        #map { position: absolute; inset: 0; z-index: 0; }
        .leaflet-popup-content { margin: 0; }
        .leaflet-popup-content-wrapper { padding: 0; border-radius: 12px; overflow: hidden; }
        .pin-icon { filter: drop-shadow(0 2px 3px rgba(0,0,0,.35)); }
        .cluster-pin {
            background: linear-gradient(135deg, #0F2A5C, #1E4C9A);
            color: #fff; border-radius: 999px; display: flex; align-items: center;
            justify-content: center; font-weight: 700; font-size: 13px;
            border: 2px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,.3);
        }
        .floating-panel {
            backdrop-filter: blur(6px);
            background: rgba(255,255,255,.95);
        }
        .cat-row.disabled { opacity: .35; }
        .status-btn { color: #64748B; }
        .status-btn.active { background: #0F2A5C; color: #fff; }
        .scrollbar-thin::-webkit-scrollbar { width: 5px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 999px; }
    </style>
</head>
<body class="bg-slate-100">

    {{-- Peta full-page --}}
    <div id="map"></div>

    {{-- Baris tombol atas: logo, back, toggle legend — flex-wrap biar tidak numpuk di layar sempit --}}
    <div id="top-bar" class="absolute top-4 left-4 right-4 z-[900] flex items-center gap-2 flex-wrap">
        <div class="floating-panel shadow-lg rounded-xl w-11 h-11 flex items-center justify-center p-2 flex-shrink-0">
            <img src="https://upload.wikimedia.org/wikipedia/commons/b/b3/Bulog_2024.svg" alt="Bulog" class="w-full h-full object-contain">
        </div>

        <button id="hide-all-toggle"
                class="floating-panel shadow-lg rounded-xl px-2.5 sm:px-3 py-2.5 flex items-center gap-2 text-sm font-medium text-slate-700 hover:bg-white transition-colors flex-shrink-0">
            <span id="hide-all-icon" class="leading-none flex items-center">
                <!-- eye (tampil) -->
                <svg id="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <!-- eye-off (sembunyi), default hidden -->
                <svg id="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden">
                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                    <path d="M6.61 6.61A18.5 18.5 0 0 0 1 12s4 8 11 8a9.26 9.26 0 0 0 5.39-1.61"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
            </span>
            <span class="hidden sm:inline" id="hide-all-label">Sembunyikan Semua Panel</span>
        </button>

    </div>

    {{-- Sidebar kiri: 3 kartu terpisah, ditumpuk mepet ke tepi kiri --}}
    <div id="legend-panel" class="hideable-panel absolute top-20 left-4 z-[900] w-80 max-w-[calc(100vw-2rem)] max-h-[calc(100vh-100px)] overflow-y-auto scrollbar-thin space-y-3">

        {{-- Kartu 1: Legend & Filter --}}
        <div class="floating-panel shadow-lg rounded-xl overflow-hidden">
            <div class="bg-[#0F2A5C] px-4 py-3 flex items-center justify-between">
                <h2 class="text-white text-xs font-semibold uppercase tracking-wide">Legenda &amp; Filter Aset &middot; {{ $tipeAset }}</h2>
                <button id="legend-close" class="text-white/70 hover:text-white text-sm">&times;</button>
            </div>

            {{-- Filter status (paling atas) --}}
            <div class="p-3">
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-2">
                    Status &middot; <span class="font-normal normal-case text-slate-400">pilih salah satu untuk menampilkan titik di peta</span>
                </p>
                <div class="inline-flex w-full rounded-lg border border-slate-200 p-1 bg-slate-50">
                    <button type="button" data-status="Terdayaguna" class="status-btn flex-1 px-2 py-1.5 text-xs font-medium rounded-md">Terdayaguna</button>
                    <button type="button" data-status="Idle" class="status-btn flex-1 px-2 py-1.5 text-xs font-medium rounded-md">Idle</button>
                    <button type="button" data-status="__all__" class="status-btn active flex-1 px-2 py-1.5 text-xs font-medium rounded-md">Semua</button>
                </div>
            </div>

            {{-- Search --}}
            <div class="border-t border-slate-100 p-3">
                <input id="filter-search" type="text" placeholder="Cari nama aset / kedudukan..."
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            {{-- Header kolom --}}
            <div class="border-t border-slate-100 px-3 pt-3 pb-1 flex items-center text-[10px] font-semibold text-slate-400 uppercase tracking-wide">
                <span class="flex-1">Kategori</span>
                <span class="w-14 text-center text-emerald-600">Terdaya</span>
                <span class="w-12 text-center text-slate-500">Idle</span>
                <span class="w-10 text-right">Total</span>
            </div>

            <div class="px-3 pb-2 space-y-1">
                @foreach ($categoryData as $cat)
                    <label class="cat-row flex items-center gap-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer transition-opacity">
                        <input type="checkbox" class="cat-checkbox accent-[#0F2A5C]" data-category="{{ $cat['label'] }}" checked>
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm flex-shrink-0"
                              style="background-color: {{ $cat['color'] }}1A; border: 1.5px solid {{ $cat['color'] }}">
                            {{ $cat['icon'] }}
                        </span>
                        <span class="flex-1 min-w-0 text-xs font-medium text-slate-700 truncate">{{ $cat['label'] }}</span>
                        <span class="w-14 text-center text-xs font-semibold text-emerald-600">{{ $cat['terdayaguna'] }}</span>
                        <span class="w-12 text-center text-xs font-semibold text-slate-400">{{ $cat['idle'] }}</span>
                        <span class="w-10 text-right text-xs font-bold text-slate-700">{{ $cat['total'] }}</span>
                    </label>
                @endforeach

                @if ($otherTotal > 0)
                    <label class="cat-row flex items-center gap-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer transition-opacity">
                        <input type="checkbox" class="cat-checkbox accent-[#0F2A5C]" data-category="__other__" checked>
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm flex-shrink-0 bg-slate-100 border border-slate-300">❔</span>
                        <span class="flex-1 min-w-0 text-xs font-medium text-slate-700 truncate">Lainnya</span>
                        <span class="w-14 text-center text-xs text-slate-300">-</span>
                        <span class="w-12 text-center text-xs text-slate-300">-</span>
                        <span class="w-10 text-right text-xs font-bold text-slate-700">{{ $otherTotal }}</span>
                    </label>
                @endif
            </div>

            {{-- Footer total keseluruhan --}}
            <div class="bg-[#0F2A5C] px-3 py-2.5 flex items-center gap-2 text-xs">
                <span class="flex-1 text-white font-semibold uppercase tracking-wide">Total</span>
                <span class="w-14 text-center text-emerald-300 font-bold">{{ $totalTerdayaguna }}</span>
                <span class="w-12 text-center text-slate-300 font-bold">{{ $totalIdle }}</span>
                <span class="w-10 text-right text-white font-bold">{{ $totalTerdayaguna + $totalIdle }}</span>
            </div>
        </div>

        {{-- Kartu 2: Proporsi kategori (donut, ikut berubah sesuai filter aktif) --}}
        <div class="floating-panel shadow-lg rounded-xl overflow-hidden">
            <div class="bg-[#0F2A5C] px-4 py-2.5">
                <h2 class="text-white text-xs font-semibold uppercase tracking-wide">Proporsi Aset Ditampilkan</h2>
            </div>
            <div class="p-4 flex items-center gap-4">
                <div class="relative w-28 h-28 flex-shrink-0">
                    <canvas id="legend-donut"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-[8px] font-semibold text-slate-400 uppercase tracking-wide">Total</span>
                        <span id="donut-center-total" class="text-base font-extrabold text-[#0F2A5C] leading-tight">-</span>
                        <span class="text-[8px] font-semibold text-slate-400 uppercase tracking-wide">Aset</span>
                    </div>
                </div>
                <ul id="donut-legend" class="flex-1 min-w-0 space-y-1 text-[11px]"></ul>
            </div>
            <p class="px-4 pb-3 text-[10px] text-slate-400">Otomatis menyesuaikan filter status, kategori, dan pencarian yang aktif.</p>
        </div>

        {{-- Kartu 3: Kategori Usaha — dihitung LIVE di browser dari titik yang
             sedang tampil, ikut berubah kalau filter status/kategori/pencarian
             diganti. Lihat fungsi updateUsahaPanel() di bagian script. --}}
        <div class="floating-panel shadow-lg rounded-xl overflow-hidden">
            <div class="bg-[#0F2A5C] px-4 py-2.5 flex items-center justify-between">
                <h2 class="text-white text-xs font-semibold uppercase tracking-wide">Kategori Usaha</h2>
                <span id="usaha-panel-total" class="text-[10px] text-white/70">-</span>
            </div>
            <div id="usaha-panel-list" class="divide-y divide-dashed divide-slate-100 max-h-64 overflow-y-auto scrollbar-thin">
                <div class="px-4 py-6 text-center text-xs text-slate-400">Memuat data...</div>
            </div>
        </div>
    </div>

    {{-- Panel Ringkasan per RM, pojok kanan bawah --}}
    <div id="rm-panel" class="hideable-panel absolute bottom-4 right-4 z-[900] floating-panel shadow-lg rounded-xl overflow-hidden w-80 max-w-[calc(100vw-2rem)]">
        <div class="bg-[#0F2A5C] px-4 py-2.5">
            <h2 class="text-white text-xs font-semibold uppercase tracking-wide">Status Aset per RM &middot; Juni 2026 &middot; {{ $tipeAset }}</h2>
        </div>
        <div class="p-3">
            <div class="rounded-lg border border-slate-200 overflow-hidden text-xs">
                <div class="flex items-center bg-slate-50 px-2.5 py-1.5 text-[10px] font-semibold text-slate-400 uppercase tracking-wide">
                    <span class="w-12">RM</span>
                    <span class="flex-1 text-center text-emerald-600">Terdaya</span>
                    <span class="w-10 text-center">Idle</span>
                    <span class="w-10 text-right">Total</span>
                    <span class="w-4"></span>
                </div>
                @foreach ($rmSummary as $row)
                    <button type="button" data-rm-toggle="{{ $row['rm'] }}"
                            class="rm-row w-full flex items-center px-2.5 py-1.5 border-b border-slate-100 hover:bg-slate-50 transition-colors text-left">
                        <span class="w-12 font-medium text-slate-700">{{ $row['rm'] }}</span>
                        <span class="flex-1 text-center text-emerald-600 font-semibold">{{ $row['terdayaguna'] }}</span>
                        <span class="w-10 text-center text-slate-400 font-semibold">{{ $row['idle'] }}</span>
                        <span class="w-10 text-right font-bold text-slate-700">{{ $row['total'] }}</span>
                        <span class="w-4 text-slate-300 text-[10px] rm-row-chevron">▾</span>
                    </button>
                    <div data-rm-panel="{{ $row['rm'] }}" class="hidden max-h-44 overflow-y-auto scrollbar-thin border-b border-slate-100 bg-slate-50/60">
                        <!-- diisi via JS -->
                    </div>
                @endforeach
                @if ($otherRmTotal > 0)
                    <div class="flex items-center px-2.5 py-1.5 border-b border-slate-100">
                        <span class="w-12 font-medium text-slate-700">Lainnya</span>
                        <span class="flex-1 text-center text-slate-300">-</span>
                        <span class="w-10 text-center text-slate-300">-</span>
                        <span class="w-10 text-right font-bold text-slate-700">{{ $otherRmTotal }}</span>
                        <span class="w-4"></span>
                    </div>
                @endif
                <div class="flex items-center bg-[#0F2A5C] px-2.5 py-1.5">
                    <span class="w-12 font-semibold text-white">Total</span>
                    <span class="flex-1 text-center text-emerald-300 font-bold">{{ $totalTerdayaguna }}</span>
                    <span class="w-10 text-center text-slate-300 font-bold">{{ $totalIdle }}</span>
                    <span class="w-10 text-right font-bold text-white">{{ $totalTerdayaguna + $totalIdle }}</span>
                    <span class="w-4"></span>
                </div>
            </div>
            <p class="text-[10px] text-slate-400 mt-2">Klik salah satu baris RM untuk lihat daftar asetnya.</p>
        </div>
    </div>

    {{-- Panel analitik mengambang --}}
    <div id="analytics-panel" class="hideable-panel absolute top-4 right-4 z-[900] floating-panel shadow-lg rounded-xl p-4 w-72 max-w-[calc(100vw-2rem)]">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <p class="text-[9px] font-medium text-slate-400 uppercase tracking-wide">Aset Tampil</p>
                <p id="stat-total-aset" class="text-lg font-bold text-slate-900 leading-tight">-</p>
            </div>
            <div>
                <p class="text-[9px] font-medium text-slate-400 uppercase tracking-wide">Luas Tanah (m²)</p>
                <p id="stat-luas-tanah" class="text-lg font-bold text-slate-900 leading-tight break-words">-</p>
            </div>
            <div>
                <p class="text-[9px] font-medium text-slate-400 uppercase tracking-wide">Luas Bangunan (m²)</p>
                <p id="stat-luas-bangunan" class="text-lg font-bold text-slate-900 leading-tight break-words">-</p>
            </div>
            <div>
                <p class="text-[9px] font-medium text-slate-400 uppercase tracking-wide">Jml. Mitra</p>
                <p id="stat-jml-mitra" class="text-lg font-bold text-slate-900 leading-tight">-</p>
            </div>
        </div>

        <div class="border-t border-slate-100 mt-3 pt-3">
            <p class="text-[9px] font-medium text-slate-400 uppercase tracking-wide">Total Nilai Kontrak</p>
            <p id="stat-nilai-kontrak" class="text-base font-bold text-emerald-600 leading-snug break-words">-</p>
        </div>
    </div>

    {{-- Loading --}}
    <div id="map-loading" class="absolute inset-0 bg-white/70 flex items-center justify-center z-[950] text-sm text-slate-500">
        Memuat data peta...
    </div>

    <script>
        const map = L.map('map', { zoomControl: false });
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // Di layar mobile (<768px), panel Analitik & RM yang biasanya mengambang
        // di pojok kanan-atas/kanan-bawah dipindah ikut masuk ke dalam sidebar
        // Legend (jadi 1 kolom yang bisa di-scroll), supaya tidak saling tabrakan.
        // Di layar desktop, keduanya dikembalikan ke posisi pojok masing-masing.
        let isMobileLayout = null;
        function applyResponsiveLayout() {
            const mobile = window.innerWidth < 768;
            if (mobile === isMobileLayout) return; // tidak ada perubahan, skip
            isMobileLayout = mobile;

            const legend = document.getElementById('legend-panel');
            const analytics = document.getElementById('analytics-panel');
            const rm = document.getElementById('rm-panel');
            if (!legend || !analytics || !rm) return;

            if (mobile) {
                legend.appendChild(analytics);
                legend.appendChild(rm);
                [analytics, rm].forEach(el => {
                    el.classList.remove('absolute', 'top-4', 'right-4', 'bottom-4', 'w-72', 'w-80');
                    el.classList.add('w-full');
                    el.style.maxWidth = 'none';
                });
            } else {
                document.body.appendChild(analytics);
                document.body.appendChild(rm);
                analytics.classList.add('absolute', 'top-4', 'right-4', 'w-72');
                analytics.classList.remove('w-full');
                analytics.style.maxWidth = '';
                rm.classList.add('absolute', 'bottom-4', 'right-4', 'w-80');
                rm.classList.remove('w-full');
                rm.style.maxWidth = '';
            }
        }

        // Posisikan sidebar legend persis di bawah top-bar (yang tingginya bisa berubah
        // kalau tombol-tombolnya wrap ke 2 baris di layar sempit), supaya tidak overlap
        // ataupun kepotong di bagian bawah layar.
        function layoutPanels() {
            applyResponsiveLayout();

            const topBar = document.getElementById('top-bar');
            const legend = document.getElementById('legend-panel');
            if (!topBar || !legend) return;

            const margin = 12;
            const top = topBar.getBoundingClientRect().bottom + margin;

            legend.style.top = top + 'px';
            legend.style.maxHeight = (window.innerHeight - top - margin) + 'px';
        }
        layoutPanels();
        window.addEventListener('resize', layoutPanels);

        // Batas wilayah Indonesia, dipakai supaya peta otomatis center di area yang
        // tidak ketutup panel legend (kiri) maupun panel analitik (kanan).
        const indonesiaBounds = L.latLngBounds([-3, 98], [7, 130.5]);

        function fitIndonesia() {
            layoutPanels();
            const margin = 16;

            // Ukur lebar/tinggi panel yang sedang tampil secara nyata (bukan angka tebakan),
            // supaya Indonesia selalu center pas di area yang benar-benar kosong dari panel.
            const rectOf = (id) => {
                const el = document.getElementById(id);
                if (!el || el.classList.contains('hidden') || el.offsetParent === null) return null;
                return el.getBoundingClientRect();
            };

            const left = rectOf('legend-panel');
            const analytics = rectOf('analytics-panel');
            const rm = rectOf('rm-panel');

            const leftPad = left ? left.right + margin : margin;
            const topPad = left ? left.top : 70;

            const rightPad = Math.max(analytics ? analytics.width : 0, rm ? rm.width : 0) + margin;
            const bottomPad = rm ? (window.innerHeight - rm.top) + margin : margin;

            map.fitBounds(indonesiaBounds, {
                paddingTopLeft: [leftPad, topPad],
                paddingBottomRight: [rightPad, bottomPad],
            });

            // Jaga supaya peta tidak ke-zoom out berlebihan gara-gara padding panel
            if (map.getZoom() < 5) {
                map.setZoom(5);
            }
        }
        fitIndonesia();
        window.addEventListener('resize', fitIndonesia);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const clusterGroup = L.markerClusterGroup({
            maxClusterRadius: 45,
            iconCreateFunction: function (cluster) {
                const count = cluster.getChildCount();
                const size = count < 10 ? 32 : count < 50 ? 40 : 48;
                return L.divIcon({
                    html: `<div class="cluster-pin" style="width:${size}px;height:${size}px;font-size:${count < 100 ? 12 : 11}px">${count}</div>`,
                    className: '',
                    iconSize: [size, size],
                });
            }
        });
        map.addLayer(clusterGroup);

        // Metadata kategori (warna + icon) dari backend, supaya konsisten dengan legend
        const categoryMeta = {
            @foreach ($categoryData as $cat)
                "{{ $cat['label'] }}": { color: "{{ $cat['color'] }}", icon: "{{ $cat['icon'] }}" },
            @endforeach
        };
        const defaultMeta = { color: '#475569', icon: '❔' };

        function metaFor(jenisAset) {
            return categoryMeta[jenisAset] || defaultMeta;
        }

        function categoryKeyFor(jenisAset) {
            return categoryMeta[jenisAset] ? jenisAset : '__other__';
        }

        let allPoints = [];
        let currentStatus = 'Terdayaguna';
        let activeCategories = new Set([...Object.keys(categoryMeta), '__other__']);

        // Donut chart proporsi kategori — datanya di-update tiap kali filter berubah,
        // lihat fungsi updateDonut() yang dipanggil dari render().
        const donutLabels = [...Object.keys(categoryMeta), 'Lainnya'];
        const donutColors = [...Object.values(categoryMeta).map(m => m.color), '#94A3B8'];

        const donutChart = new Chart(document.getElementById('legend-donut'), {
            type: 'doughnut',
            data: {
                labels: donutLabels,
                datasets: [{
                    data: new Array(donutLabels.length).fill(0),
                    backgroundColor: donutColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (item) => `${item.label}: ${item.raw} aset` } }
                },
                cutout: '68%',
            }
        });

        function updateDonut(points) {
            const counts = Object.keys(categoryMeta).map(label => points.filter(p => p.jenis_aset === label).length);
            const otherCount = points.filter(p => !categoryMeta[p.jenis_aset]).length;
            const data = [...counts, otherCount];

            donutChart.data.datasets[0].data = data;
            donutChart.update();

            document.getElementById('donut-center-total').textContent = points.length.toLocaleString('id-ID');

            const legendEl = document.getElementById('donut-legend');
            legendEl.innerHTML = donutLabels.map((label, i) => {
                if (data[i] === 0) return '';
                return `
                    <li class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:${donutColors[i]}"></span>
                        <span class="flex-1 min-w-0 truncate text-slate-600">${label}</span>
                        <span class="font-semibold text-slate-800">${data[i]}</span>
                    </li>`;
            }).join('') || '<li class="text-slate-400">Tidak ada data untuk filter ini.</li>';
        }

        // Metadata (warna + icon) kategori Usaha dari backend, urutan tetap sesuai legenda resmi
        const usahaMeta = {
            @foreach ($usahaMetaList as $label => $meta)
                "{{ $label }}": { color: "{{ $meta['color'] }}", icon: "{{ $meta['icon'] }}" },
            @endforeach
        };

        // Panel "Kategori Usaha" — dihitung ulang tiap render() dari kontrak
        // milik titik yang sedang tampil (ikut filter status/kategori/pencarian)
        function updateUsahaPanel(points) {
            const counts = {};
            points.forEach(p => {
                // Group by Sub Asset Code: 1 aset dihitung sekali saja untuk kategori
                // usahanya, walau asetnya punya lebih dari satu kontrak.
                const kontrakWithUsaha = (p.kontraks || []).find(k => k.usaha);
                if (!kontrakWithUsaha) return;
                counts[kontrakWithUsaha.usaha] = (counts[kontrakWithUsaha.usaha] || 0) + 1;
            });

            const total = Object.values(counts).reduce((a, b) => a + b, 0);
            document.getElementById('usaha-panel-total').textContent = total.toLocaleString('id-ID') + ' unit';

            // Urutan tetap sesuai legenda resmi dulu, baru kategori lain (kalau ada) diurutkan dari terbanyak
            const knownLabels = Object.keys(usahaMeta).filter(l => counts[l]);
            const extraLabels = Object.keys(counts)
                .filter(l => !usahaMeta[l])
                .sort((a, b) => counts[b] - counts[a]);
            const labels = [...knownLabels, ...extraLabels];

            const listEl = document.getElementById('usaha-panel-list');
            listEl.innerHTML = labels.map(label => {
                const meta = usahaMeta[label] || { color: '#64748B', icon: '📍' };
                const count = counts[label];
                const percent = total > 0 ? (count / total * 100).toFixed(1).replace('.', ',') : '0,0';
                return `
                    <div class="flex items-center gap-2.5 px-4 py-2">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm flex-shrink-0" style="background-color:${meta.color}">${meta.icon}</span>
                        <span class="flex-1 min-w-0 text-xs font-medium text-slate-700 truncate">${label}</span>
                        <span class="text-xs font-bold text-slate-800">${count}</span>
                        <span class="w-12 text-right text-[10px] font-semibold" style="color:${meta.color}">${percent}%</span>
                    </div>`;
            }).join('') || '<div class="px-4 py-6 text-center text-xs text-slate-400">Tidak ada data untuk filter ini.</div>';
        }

        function pinIcon(point) {
            const meta = metaFor(point.jenis_aset);
            const isIdle = point.status !== 'Terdayaguna';
            const opacity = isIdle ? 0.5 : 1;
            const ringColor = isIdle ? '#94A3B8' : '#ffffff';

            const svg = `
                <div style="position:relative; width:34px; height:46px; opacity:${opacity}">
                    <svg width="34" height="46" viewBox="0 0 30 42" class="pin-icon">
                        <path d="M15 0C6.7 0 0 6.7 0 15c0 11.25 15 27 15 27s15-15.75 15-27C30 6.7 23.3 0 15 0z"
                              fill="${meta.color}" stroke="${ringColor}" stroke-width="1.5"/>
                        <circle cx="15" cy="15" r="10.5" fill="#fff"/>
                    </svg>
                    <div style="position:absolute; top:4px; left:0; width:30px; text-align:center; font-size:13px; line-height:22px;">${meta.icon}</div>
                    ${isIdle ? '<div style="position:absolute; top:0; right:-2px; width:10px; height:10px; background:#94A3B8; border:2px solid #fff; border-radius:999px;"></div>' : ''}
                </div>`;

            return L.divIcon({
                html: svg,
                className: '',
                iconSize: [34, 46],
                iconAnchor: [17, 46],
                popupAnchor: [0, -40],
            });
        }

        function formatNumber(n) { return Number(n ?? 0).toLocaleString('id-ID'); }
        function formatRupiah(n) { return 'Rp ' + Number(n ?? 0).toLocaleString('id-ID'); }

        function popupHtml(p) {
            const meta = metaFor(p.jenis_aset);
            const statusColor = p.status === 'Terdayaguna' ? '#10B981' : '#64748B';

            let kontrakHtml = '';
            if (p.status === 'Terdayaguna' && p.kontraks.length > 0) {
                kontrakHtml = p.kontraks.map(k => `
                    <div class="mt-2 pt-2 border-t border-slate-100 text-xs text-slate-600">
                        <div class="font-medium text-slate-800">${k.nama_mitra_kerjasama ?? '-'}</div>
                        <div>${k.usaha ?? '-'} &middot; ${k.jenis_usaha ?? '-'}</div>
                        <div>${formatRupiah(k.nilai_kontrak)}</div>
                        <div class="text-slate-400">${k.tanggal_mulai_kerjasama ?? '-'} s/d ${k.tanggal_berakhir_kerjasama ?? '-'}</div>
                    </div>
                `).join('');
            } else if (p.status !== 'Terdayaguna') {
                kontrakHtml = `<div class="mt-2 pt-2 border-t border-slate-100 text-xs text-slate-400">Aset idle, belum ada mitra kerjasama.</div>`;
            }

            return `
                <div style="min-width:230px; font-family: ui-sans-serif, system-ui, sans-serif;">
                    <div style="background:${statusColor}" class="px-4 py-2 flex items-center gap-2">
                        <span style="font-size:16px">${meta.icon}</span>
                        <span class="text-white text-[11px] font-semibold uppercase tracking-wide">${p.status} &middot; ${p.jenis_aset ?? '-'}</span>
                    </div>
                    <div class="p-4">
                        <div class="font-semibold text-slate-900 text-sm">${p.nama_aset}</div>
                        <div class="text-xs text-slate-500 mb-2">${p.kedudukan} &middot; ${p.rm}</div>
                        <div class="text-xs text-slate-600">Tanah ${formatNumber(p.luas_tanah)} m² &middot; Bangunan ${formatNumber(p.luas_bangunan)} m²</div>
                        <div class="text-[10px] mt-1 ${p.is_precise ? 'text-emerald-600' : 'text-amber-500'}">
                            ${p.is_precise ? '📍 Koordinat presisi (GPS)' : '〜 Koordinat perkiraan wilayah'}
                        </div>
                        ${kontrakHtml}
                        <a href="${p.detail_url}" class="inline-block mt-3 text-xs font-medium text-orange-600 hover:underline">Lihat detail &rarr;</a>
                    </div>
                </div>
            `;
        }

        function render(points) {
            clusterGroup.clearLayers();
            const markers = points.map(p => L.marker([p.lat, p.lng], { icon: pinIcon(p) }).bindPopup(popupHtml(p)));
            clusterGroup.addLayers(markers);
            updateStats(points);
            updateDonut(points);
            updateUsahaPanel(points);
        }

        function updateStats(points) {
            const totalTanah = points.reduce((sum, p) => sum + Number(p.luas_tanah ?? 0), 0);
            const totalBangunan = points.reduce((sum, p) => sum + Number(p.luas_bangunan ?? 0), 0);
            const totalNilaiKontrak = points.reduce((sum, p) => sum + p.kontraks.reduce((s, k) => s + Number(k.nilai_kontrak ?? 0), 0), 0);
            const totalMitra = points.reduce((sum, p) => sum + p.kontraks.length, 0);

            document.getElementById('stat-total-aset').textContent = formatNumber(points.length);
            document.getElementById('stat-luas-tanah').textContent = formatNumber(totalTanah);
            document.getElementById('stat-luas-bangunan').textContent = formatNumber(totalBangunan);
            document.getElementById('stat-jml-mitra').textContent = formatNumber(totalMitra);
            document.getElementById('stat-nilai-kontrak').textContent = formatRupiah(totalNilaiKontrak);
        }

        function applyFilter() {
            const q = document.getElementById('filter-search').value.toLowerCase();

            const filtered = allPoints.filter(p => {
                const matchSearch = !q || p.nama_aset.toLowerCase().includes(q) || (p.kedudukan ?? '').toLowerCase().includes(q);
                const matchStatus = currentStatus === '__all__' || p.status === currentStatus;
                const matchCategory = activeCategories.has(categoryKeyFor(p.jenis_aset));
                return matchSearch && matchStatus && matchCategory;
            });

            render(filtered);
        }


        // Tombol master: sembunyikan/tampilkan SEMUA panel (legend, analitik, RM,
        // badge tipe, tombol switch/infografis) sekaligus, biar tinggal peta saja.
        let allPanelsHidden = false;
        document.getElementById('hide-all-toggle').addEventListener('click', () => {
            allPanelsHidden = !allPanelsHidden;

            document.querySelectorAll('.hideable-panel').forEach(el => {
                el.classList.toggle('hidden', allPanelsHidden);
            });

            document.getElementById('icon-eye').classList.toggle('hidden', allPanelsHidden);
            document.getElementById('icon-eye-off').classList.toggle('hidden', !allPanelsHidden);
            document.getElementById('hide-all-label').textContent = allPanelsHidden
                ? 'Tampilkan Semua Panel'
                : 'Sembunyikan Semua Panel';

            setTimeout(fitIndonesia, 50);
        });

        // Toggle per-kategori on/off
        document.querySelectorAll('.cat-checkbox').forEach(cb => {
            cb.addEventListener('change', () => {
                const key = cb.dataset.category;
                if (cb.checked) activeCategories.add(key); else activeCategories.delete(key);
                cb.closest('.cat-row').classList.toggle('disabled', !cb.checked);
                applyFilter();
            });
        });

        // Filter status
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.status-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentStatus = btn.dataset.status;
                applyFilter();
            });
        });

        document.getElementById('filter-search').addEventListener('input', applyFilter);

        // Accordion per baris RM — klik baris untuk expand/collapse ringkasan per Kedudukan
        function renderRmAssetList(rm) {
            const items = allPoints.filter(p => p.rm === rm);

            const counts = {};
            items.forEach(p => {
                const k = p.kedudukan || '(Tanpa Kedudukan)';
                if (!counts[k]) counts[k] = { terdayaguna: 0, idle: 0 };
                if (p.status === 'Terdayaguna') counts[k].terdayaguna++;
                else counts[k].idle++;
            });

            const sorted = Object.entries(counts).sort((a, b) => (b[1].terdayaguna + b[1].idle) - (a[1].terdayaguna + a[1].idle));

            return `
                <div class="flex items-center px-3 py-1 text-[9px] font-semibold text-slate-400 uppercase tracking-wide bg-slate-100">
                    <span class="flex-1 min-w-0">Kedudukan</span>
                    <span class="w-8 text-center text-emerald-600">Terd</span>
                    <span class="w-8 text-center text-slate-400">Idle</span>
                    <span class="w-5"></span>
                </div>
            ` + sorted.map(([kedudukan, c]) => `
                <div class="flex items-center gap-1 px-3 py-1.5 border-b border-slate-100 last:border-b-0">
                    <span class="text-[11px] font-medium text-slate-700 truncate flex-1 min-w-0">${kedudukan}</span>
                    <span class="w-8 text-center text-[11px] font-bold text-emerald-600">${c.terdayaguna}</span>
                    <span class="w-8 text-center text-[11px] font-bold text-slate-400">${c.idle}</span>
                    <button type="button" class="focus-kedudukan-btn flex-shrink-0 w-5 h-5 rounded-md flex items-center justify-center text-[10px] hover:bg-orange-100 transition-colors"
                            data-kedudukan="${kedudukan}" title="Fokuskan peta ke ${kedudukan}">
                        🎯
                    </button>
                </div>
            `).join('') || '<div class="px-3 py-3 text-center text-[11px] text-slate-400">Tidak ada aset untuk RM ini.</div>';
        }

        // Fokuskan/zoom peta ke sebaran titik pada kedudukan tertentu
        function focusMapOnKedudukan(kedudukan) {
            const points = allPoints.filter(p => p.kedudukan === kedudukan);
            if (points.length === 0) return;

            if (points.length === 1) {
                map.flyTo([points[0].lat, points[0].lng], 14, { duration: 0.8 });
            } else {
                const bounds = L.latLngBounds(points.map(p => [p.lat, p.lng]));
                map.flyToBounds(bounds, { padding: [60, 60], maxZoom: 14, duration: 0.8 });
            }
        }

        // Event delegation, supaya tombol yang dibuat dinamis (hasil innerHTML) tetap kepencet
        document.getElementById('rm-panel').addEventListener('click', (e) => {
            const btn = e.target.closest('.focus-kedudukan-btn');
            if (!btn) return;
            e.stopPropagation(); // biar tidak ikut toggle accordion RM-nya
            focusMapOnKedudukan(btn.dataset.kedudukan);
        });

        document.querySelectorAll('.rm-row').forEach(btn => {
            btn.addEventListener('click', () => {
                const rm = btn.dataset.rmToggle;
                const panel = document.querySelector(`[data-rm-panel="${rm}"]`);
                const chevron = btn.querySelector('.rm-row-chevron');
                const isOpen = !panel.classList.contains('hidden');

                // Tutup semua panel lain dulu (biar cuma 1 yang kebuka)
                document.querySelectorAll('[data-rm-panel]').forEach(p => p.classList.add('hidden'));
                document.querySelectorAll('.rm-row-chevron').forEach(c => c.textContent = '▾');

                if (!isOpen) {
                    panel.innerHTML = renderRmAssetList(rm);
                    panel.classList.remove('hidden');
                    chevron.textContent = '▴';
                }
            });
        });

        fetch('{{ route($mapDataRoute) }}')
            .then(res => res.json())
            .then(data => {
                allPoints = data;
                render(allPoints);
                document.getElementById('map-loading').remove();
            });
    </script>

</body>
</html>