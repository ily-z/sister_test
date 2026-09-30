<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ERP System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Mencegah zoom otomatis di HP saat klik input */
        input { font-size: 16px !important; }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center h-screen">

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg border border-slate-200 p-8">
        
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Backoffice ERP</h1>
            <p class="text-sm text-slate-500 mt-1">Masukkan kredensial Anda untuk masuk</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-5 border border-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            
            <!-- Input Nama -->
            <div class="mb-5">
                <label for="nama" class="block text-sm font-medium text-slate-700 mb-1">Nama</label>
                <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required autofocus
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                    placeholder="Masukkan nama Anda">
            </div>

            <!-- Password -->
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                    placeholder="••••••••">
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between mb-6 text-sm">
                <label class="flex items-center text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="mr-2 rounded text-blue-600 focus:ring-blue-500">
                    Ingat saya
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium py-2.5 rounded-lg transition-colors shadow-sm">
                Masuk Sistem
            </button>
        </form>

    </div>

</body>
</html>