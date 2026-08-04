<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Opaset Bulog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">

    {{-- Top bar --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <p class="text-sm text-slate-400">
                <span class="text-slate-300">Home</span>
                <span class="mx-1">/</span>
                <span class="font-semibold text-slate-700">Dashboard</span>
            </p>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    System Online
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-slate-500 hover:text-red-600 border border-slate-200 hover:border-red-200 px-3 py-1.5 rounded-full transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-16">

        <div class="text-center mb-14">
            <div class="w-16 h-1 mx-auto mb-5 rounded-full bg-gradient-to-r from-[#0F2A5C] to-orange-400"></div>
            <h1 class="text-3xl font-extrabold text-[#0F2A5C]">Pusat Akses Cepat Aplikasi Internal</h1>
            <p class="text-slate-500 mt-2">Pilih aplikasi yang ingin Anda akses</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($menus as $menu)
                <a href="{{ $menu['url'] }}"
                   class="bg-white border border-slate-100 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all p-8 flex flex-col items-center text-center">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-slate-100 to-orange-50 flex items-center justify-center text-4xl mb-5">
                        {{ $menu['icon'] }}
                    </div>
                    <p class="font-bold text-[#0F2A5C]">{{ $menu['label'] }}</p>
                </a>
            @endforeach
        </div>
    </div>

</body>
</html>
