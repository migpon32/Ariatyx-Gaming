<x-guest-layout>
    <div class="flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md bg-white shadow-2xl p-10 border-t-4 border-red-600">

            <div class="mb-8 flex justify-center">
                <a href="/">
                    <img src="{{ asset('images/logo.png') }}" alt="Ariatyx Logo" class="h-16 w-auto">
                </a>
            </div>

            <h2 class="text-2xl font-black uppercase italic mb-8 text-[#111] tracking-tighter">
                Sign In
            </h2>

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-600 text-green-700 text-xs font-bold uppercase">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none transition"
                        required
                        autofocus
                    >

                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mb-6 relative">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Password
                    </label>

                    <input
                        id="login_password"
                        type="password"
                        name="password"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 pr-12 text-sm outline-none transition"
                        required
                        autocomplete="current-password"
                    >

                    <button
                        type="button"
                        onclick="togglePassword('login_password', this)"
                        class="absolute right-3 top-8 text-gray-500 hover:text-red-600 text-lg"
                    >
                        👁
                    </button>

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex flex-col gap-3">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold uppercase tracking-widest py-4 transition duration-300 shadow-lg">
                        Sign In
                    </button>

                    <a href="{{ route('register') }}" class="w-full bg-[#111] hover:bg-gray-800 text-white text-center font-bold uppercase tracking-widest py-4 transition duration-300">
                        Register
                    </a>
                </div>

                <div class="mt-8 text-center">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-red-600 transition">
                            Forgot Your Password?
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <p class="mt-8 text-[10px] text-gray-400 uppercase tracking-[0.3em]">
            &copy; 2026 ARIATYX GAMES
        </p>
    </div>

    <script>
        function togglePassword(id, button) {
            const input = document.getElementById(id);

            if (input.type === "password") {
                input.type = "text";
                button.textContent = "🙈";
            } else {
                input.type = "password";
                button.textContent = "👁";
            }
        }
    </script>
</x-guest-layout>