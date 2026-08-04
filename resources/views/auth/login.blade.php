<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Dashboard Opaset Bulog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-sm">

        <div class="text-center mb-6">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTwQYJbcBBnIkAPTEYqOMTkwzDN6saTZK_anLV4Q2Bxvlxle8xD-i52Exc&s=10"
                 alt="Icon" class="w-16 h-16 mx-auto mb-3 rounded-full object-contain">
            <p class="text-sm font-semibold text-orange-600 tracking-wide uppercase">Bulog</p>
            <h1 class="text-xl font-bold text-slate-900 mt-1">Login Admin</h1>
            <p class="text-slate-500 text-sm mt-1">Dashboard Opaset Bulog</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.store') }}" method="POST" class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" id="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300">
                Ingat saya
            </label>

            <button type="submit"
                    class="w-full bg-slate-900 text-white text-sm font-medium py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                Masuk
            </button>
        </form>

        <p class="text-center text-xs text-slate-400 mt-6">
            <a href="{{ route('public.assets.dashboard') }}" class="hover:underline">&larr; Kembali ke halaman publik</a>
        </p>
    </div>

</body>
</html>