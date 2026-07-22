<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta Provinsi - Dashboard Opaset Bulog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        html, body { height: 100%; margin: 0; overflow: hidden; }
        #map-wrap { position: absolute; inset: 0; }
        #map { position: absolute; inset: 0; z-index: 0; background: #fff; }
        #line-layer { position: absolute; inset: 0; z-index: 5; pointer-events: none; }

        .region-box {
            background: #fff;
            border: 1.5px solid #1E3A8A;
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,.12);
            padding: 4px 7px;
            font-family: ui-sans-serif, system-ui, sans-serif;
            width: 112px;
        }
        .region-box .title { font-weight: 800; font-size: 9px; color: #1E3A8A; line-height: 1.2; }
        .region-box .row { display: flex; align-items: center; gap: 3px; font-size: 8.5px; font-weight: 700; line-height: 1.5; }
        .region-box .dot { width: 6px; height: 6px; border-radius: 999px; flex-shrink: 0; }
        .region-box .code { color: #334155; width: 16px; }
        .region-box .count { color: #0F172A; }

        .zone-top, .zone-bottom { position: absolute; left: 0; right: 0; z-index: 10; display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; padding: 0 8px; }
        .zone-top { top: 8px; }
        .zone-bottom { bottom: 8px; }
        .zone-left, .zone-right { position: absolute; top: 56px; bottom: 56px; z-index: 10; display: flex; flex-direction: column; justify-content: space-evenly; gap: 6px; }
        .zone-left { left: 8px; }
        .zone-right { right: 8px; }

        .floating-panel { background: rgba(255,255,255,.97); backdrop-filter: blur(6px); }
        .marker-dot { width: 12px; height: 12px; border-radius: 999px; background: #DC2626; border: 2px solid #fff; box-shadow: 0 0 0 1px #DC2626; }
    </style>
</head>
<body class="bg-slate-100">

    <div id="map-wrap">
        <div id="map"></div>
        <svg id="line-layer"></svg>

        <div class="zone-top" id="zone-top"></div>
        <div class="zone-bottom" id="zone-bottom"></div>
        <div class="zone-left" id="zone-left"></div>
        <div class="zone-right" id="zone-right"></div>
    </div>

    {{-- Header --}}
    <div class="absolute top-2 left-1/2 -translate-x-1/2 z-[900] flex items-center gap-2">
        <div class="floating-panel shadow-lg rounded-lg px-3 py-1.5">
            <p class="text-[10px] font-bold italic text-[#0F2A5C]">Update {{ $updatePeriode }}</p>
        </div>
        <div class="bg-[#0F2A5C] text-white shadow-lg rounded-lg px-3 py-1.5">
            <p class="text-[11px] font-extrabold tracking-wide">TOTAL ASET TERDAYAGUNA : {{ $total }} ASET</p>
        </div>
    </div>

    <a href="{{ route('public.assets.map') }}"
       class="absolute top-2 left-2 z-[900] floating-panel shadow-lg rounded-lg px-2.5 py-1.5 flex items-center gap-1 text-[11px] font-medium text-slate-600 hover:text-orange-600 transition-colors">
        <span>&larr;</span> Peta Interaktif
    </a>

    {{-- Legenda kode kategori --}}
    <div class="absolute top-2 right-2 z-[900] floating-panel shadow-lg rounded-lg px-3 py-2">
        <p class="text-[9px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Kode Kategori</p>
        <div class="grid grid-cols-2 gap-x-3 gap-y-0.5">
            @foreach ($categoryList as $label => $meta)
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $meta['color'] }}"></span>
                    <span class="text-[9px] font-semibold text-slate-600">{{ $meta['code'] }}</span>
                    <span class="text-[8px] text-slate-400">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div id="map-loading" class="absolute inset-0 bg-white/70 flex items-center justify-center z-[950] text-sm text-slate-500">
        Memuat peta...
    </div>

    <script>
        const map = L.map('map', { zoomControl: false, dragging: true, scrollWheelZoom: true });
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const regions = @json($regionSummary);
        const categories = @json(array_values($categoryList));
        const indonesiaBounds = L.latLngBounds(regions.map(r => [r.lat, r.lng]));

        // Zona penempatan label per wilayah, meniru pola infografis resmi
        const zoneOf = {
            'KALBAR': 'top', 'KALTENG': 'top', 'KALSEL': 'top', 'KALTIM DAN KALTARA': 'top',
            'SULTENG': 'top', 'SULUT DAN GORONTALO': 'top', 'SULTRA': 'top',

            'ACEH': 'left', 'SUMUT': 'left', 'SUMBAR': 'left', 'RIAU DAN KEPRI': 'left',
            'JAMBI': 'left', 'BENGKULU': 'left',

            'SUMSEL DAN BABEL': 'bottom', 'LAMPUNG': 'bottom', 'KANTOR PUSAT': 'bottom',
            'DKI DAN BANTEN': 'bottom', 'JABAR': 'bottom', 'YOGYAKARTA': 'bottom',
            'JATENG': 'bottom', 'JATIM': 'bottom', 'BALI': 'bottom', 'NTB': 'bottom',

            'SULSEL DAN SULBAR': 'right', 'MALUKU DAN MALUT': 'right',
            'PAPUA DAN PABAR': 'right', 'NTT': 'right',
        };

        function regionBoxHtml(region) {
            const rows = categories
                .map((cat, i) => ({ ...cat, count: region.counts[i] }))
                .filter(c => c.count > 0)
                .sort((a, b) => a.code.localeCompare(b.code))
                .map(c => `
                    <div class="row">
                        <span class="dot" style="background:${c.color}"></span>
                        <span class="code">${c.code}</span>
                        <span class="count">${c.count}</span>
                    </div>
                `).join('');

            return `<div class="title">${region.kedudukan} (${region.total})</div>${rows}`;
        }

        const boxEls = {};
        regions.forEach(region => {
            const zone = zoneOf[region.kedudukan.toUpperCase()] || 'bottom';
            const container = document.getElementById(`zone-${zone}`);

            const box = document.createElement('div');
            box.className = 'region-box';
            box.innerHTML = regionBoxHtml(region);
            container.appendChild(box);

            boxEls[region.kedudukan] = { el: box, zone, region };
        });

        const markers = {};
        regions.forEach(region => {
            const dotIcon = L.divIcon({ className: '', html: '<div class="marker-dot"></div>', iconSize: [12, 12], iconAnchor: [6, 6] });
            markers[region.kedudukan] = L.marker([region.lat, region.lng], { icon: dotIcon }).addTo(map);
        });

        const svg = document.getElementById('line-layer');

        function anchorPointOf(zone, rect, wrapRect) {
            const x = rect.left - wrapRect.left;
            const y = rect.top - wrapRect.top;
            switch (zone) {
                case 'top': return [x + rect.width / 2, y + rect.height];
                case 'bottom': return [x + rect.width / 2, y];
                case 'left': return [x + rect.width, y + rect.height / 2];
                default: return [x, y + rect.height / 2];
            }
        }

        function drawLines() {
            const wrapRect = document.getElementById('map-wrap').getBoundingClientRect();
            svg.setAttribute('width', wrapRect.width);
            svg.setAttribute('height', wrapRect.height);
            svg.innerHTML = '';

            Object.values(boxEls).forEach(({ el, zone, region }) => {
                const marker = markers[region.kedudukan];
                const point = map.latLngToContainerPoint(marker.getLatLng());
                const boxRect = el.getBoundingClientRect();
                const [ex, ey] = anchorPointOf(zone, boxRect, wrapRect);

                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', point.x);
                line.setAttribute('y1', point.y);
                line.setAttribute('x2', ex);
                line.setAttribute('y2', ey);
                line.setAttribute('stroke', '#DC2626');
                line.setAttribute('stroke-width', '1.8');
                line.setAttribute('opacity', '0.9');
                svg.appendChild(line);
            });
        }

        function fitIndonesia() {
            map.fitBounds(indonesiaBounds, {
                paddingTopLeft: [140, 90],
                paddingBottomRight: [140, 90],
            });
            if (map.getZoom() > 6) map.setZoom(6);
        }

        fitIndonesia();
        map.on('move zoom resize', drawLines);
        window.addEventListener('resize', () => { fitIndonesia(); setTimeout(drawLines, 100); });

        setTimeout(() => {
            drawLines();
            document.getElementById('map-loading').remove();
        }, 200);
    </script>

</body>
</html>