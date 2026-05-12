<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ariatyx Games</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=Inter:wght@400;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, nav { font-family: 'Oswald', sans-serif; }
    </style>
</head>
<body class="bg-[#f9f9f9] text-[#111] antialiased">

    <nav class="bg-[#111] text-white py-4 px-8 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center gap-10">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white rotate-45 flex items-center justify-center">
                    <div class="w-4 h-4 bg-red-600 -rotate-45"></div>
                </div>
                <span class="text-xl tracking-tighter font-bold uppercase">Ariatyx Games</span>
            </div>

            <ul class="hidden lg:flex gap-8 text-xs font-bold uppercase tracking-widest text-gray-400">
                <li><a href="#who-we-are" class="hover:text-white transition">Who We Are</a></li>
                <li><a href="#our-games" class="hover:text-white transition">Our Game</a></li>
                <li><a href="#about-site" class="hover:text-white transition">About This Site</a></li>
            </ul>
        </div>

        <div class="flex items-center gap-6">
            @if (Route::has('login'))
                @auth
                    <a href="{{ route('dashboard') }}" class="text-xs font-bold uppercase tracking-widest hover:text-red-500">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-xs font-bold uppercase tracking-widest hover:text-red-500">Sign In</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="bg-red-600 hover:bg-red-700 text-white text-[10px] px-4 py-2 rounded font-bold uppercase tracking-widest transition">
                            Register
                        </a>
                    @endif
                @endauth
            @endif
        </div>
    </nav>

    <section id="who-we-are" class="w-full bg-[#ebebeb] py-20 px-6">
        <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-0 bg-white shadow-2xl overflow-hidden">
            <div class="relative h-[400px] md:h-auto overflow-hidden bg-black">
                <img src="{{ asset('images/whoarewe.png') }}" 
                    alt="Ariatyx Hero" 
                    class="w-full h-full object-cover transform hover:scale-105 transition duration-1000 opacity-80">
                
                <div class="absolute inset-0 bg-gradient-to-r from-black via-transparent to-transparent opacity-60"></div>
            </div>

            <div class="p-12 flex flex-col justify-center">
                <span class="text-xs font-bold tracking-[0.3em] text-gray-400 uppercase mb-2">The Studio</span>
                <h1 class="text-6xl font-black uppercase italic leading-[0.9] mb-6 tracking-tighter">
                    ARIATYX<span class="text-red-600 text-7xl">.</span>
                </h1>
                <div class="border-l-4 border-red-600 pl-6 mb-8">
                    <p class="text-gray-700 leading-relaxed text-lg">
                        Ariatyx Games is an emerging independent studio dedicated to pushing the boundaries of interactive entertainment. 
                        We focus on creating high-octane experiences that blend classic arcade nostalgia with modern technical precision. 
                        Our mission is simple: to build games that are easy to pick up, impossible to put down, and technically seamless for players worldwide.
                    </p>
                </div>
                <a href="#our-games" class="w-fit px-8 py-3 border-2 border-black font-bold uppercase tracking-widest text-sm hover:bg-black hover:text-white transition text-center">
                    Check out the game
                </a>
            </div>
        </div>
    </section>

    <section id="our-games" class="py-24 bg-white">
    <div class="max-w-6xl mx-auto px-6 text-center">
        <h2 class="text-4xl font-black uppercase italic mb-12">Our Game</h2>
        
        <div class="flex justify-center">
            <a href="{{ Auth::check() ? route('dashboard') : route('login') }}" class="group cursor-pointer w-full max-w-sm block">
                <div class="h-96 bg-[#111] mb-4 overflow-hidden relative shadow-xl border-b-4 border-transparent group-hover:border-red-600 transition-all">
                    <div class="absolute inset-0 bg-red-600 opacity-0 group-hover:opacity-20 transition"></div>
                    <div class="flex flex-col items-center justify-center h-full p-8">
                        <span class="text-red-600 text-xs font-bold tracking-widest uppercase mb-2">Play Now</span>
                        <h3 class="text-white text-5xl font-black italic uppercase leading-none mb-4">BULLET<br>DROP</h3>
                        <div class="w-12 h-1 bg-white mb-4"></div>
                        <p class="text-gray-400 text-xs uppercase tracking-tighter">
                            {{ Auth::check() ? 'Click to Open Dashboard' : 'Click to Sign In & Play' }}
                        </p>
                    </div>
                </div>
                <h3 class="font-bold uppercase tracking-widest text-sm text-[#111]">Bullet Drop: Official Version</h3>
            </a>
        </div>
    </div>
</section>

    <section id="about-site" class="bg-[#111] text-white py-24">
        <div class="max-w-3xl mx-auto px-6 text-center">
            <h2 class="text-4xl font-black uppercase italic mb-8">About This Site</h2>
            <p class="text-lg text-gray-400 leading-loose italic">
                "This platform serves as a decentralized portal for community engagement and technical innovation. 
                Inspired by the high-energy aesthetic of modern gaming interfaces, it utilizes Laravel 12 to provide 
                a seamless user experience, from secure authentication to real-time data analysis."
            </p>
        </div>
    </section>

    <footer class="bg-[#111] border-t border-gray-800 py-10 text-center">
        <div class="w-6 h-6 bg-red-600 rotate-45 mx-auto mb-4"></div>
        <p class="text-[10px] text-gray-600 uppercase tracking-[0.3em]">
            &copy; 2026 ARIATYX GAMES, INC. ALL RIGHTS RESERVED.
        </p>
    </footer>

</body>
</html>
