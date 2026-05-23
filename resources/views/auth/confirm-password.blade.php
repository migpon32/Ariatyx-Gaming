<x-guest-layout>
    <div class="flex min-h-screen flex-col items-center justify-center p-6">
        <div class="w-full max-w-md bg-white shadow-2xl p-10 border-t-4 border-red-600">
            <div class="mb-8 flex justify-center">
                <a href="/">
                    <img src="{{ asset('images/logo.png') }}" alt="Ariatyx Logo" class="h-16 w-auto">
                </a>
            </div>

            <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-red-600">
                Security Check
            </p>

            <h1 class="mt-2 text-2xl font-black uppercase italic mb-4 text-[#111] tracking-tighter">
                Confirm Password
            </h1>

            <p class="mb-6 text-sm text-gray-600">
                Enter your password to continue with this protected account action.
            </p>

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="mb-6">
                    <label for="password" class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none transition"
                        required
                        autofocus
                    >

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold uppercase tracking-widest py-4 transition duration-300 shadow-lg">
                    Confirm
                </button>
            </form>

            <button
                type="button"
                onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ route('dashboard') }}'"
                class="mt-4 w-full bg-[#111] hover:bg-gray-800 text-white font-bold uppercase tracking-widest py-4 transition duration-300"
            >
                Back
            </button>
        </div>

        <p class="mt-8 text-[10px] text-gray-400 uppercase tracking-[0.3em]">
            &copy; 2026 ARIATYX GAMES
        </p>
    </div>
</x-guest-layout>
