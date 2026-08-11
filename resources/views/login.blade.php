<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BACT 2027</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
</head>
<body class="bg-[#FFFFFF] flex items-center justify-center min-h-screen relative">
    <a href="/" class="absolute top-6 left-6 text-gray-500 hover:text-[#E19404] transition font-medium text-sm flex items-center">&larr; Back to Home</a>
    
    <div class="w-full max-w-md p-8 border border-gray-100 rounded-2xl shadow-xl bg-white">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.png') }}" alt="Logo BACT" class="h-14 mx-auto mb-4">
            <h2 class="text-xl font-bold text-gray-800">Welcome back!</h2>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm text-center font-medium">
                {{ session('success') }}
            </div>
        @endif
        
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm text-center font-medium">
                Email atau password yang Anda masukkan salah.
            </div>
        @endif

        <form action="/login" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="contoh@email.com" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required placeholder="Masukkan password" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FBE39D] focus:border-[#E19404] outline-none transition">
            </div>

            <div class="flex items-center justify-between mt-2">
                <label class="flex items-center text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 mr-2 text-[#E19404] focus:ring-[#E19404]"> Remember me
                </label>
                <a href="{{ route('forgot-password') }}" class="text-sm text-[#E19404] font-semibold hover:underline">Lupa Password?</a>
            </div>

            <button type="submit" class="w-full bg-[#FFC32D] hover:bg-[#E19404] text-[#FFFFFF] font-bold py-3 px-4 rounded-full transition shadow-md mt-6">
                LOGIN
            </button>
        </form>
        
        <p class="text-center text-sm text-gray-600 mt-6">Belum punya akun? <a href="/register" class="text-[#E19404] font-bold hover:underline">Daftar sekarang</a></p>
    </div>
</body>
</html>