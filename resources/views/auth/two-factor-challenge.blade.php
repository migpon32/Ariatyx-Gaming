<x-guest-layout>
    <div class="flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md bg-white shadow-2xl p-10 border-t-4 border-red-600">
            <div class="mb-8 flex justify-center">
                <a href="/">
                    <img src="{{ asset('images/logo.png') }}" alt="Ariatyx Logo" class="h-16 w-auto">
                </a>
            </div>

            <h1 class="text-2xl font-black uppercase italic mb-3 text-[#111] tracking-tighter">
                Two-Factor Check
            </h1>

            <p class="mb-6 text-sm text-gray-600">
                Enter the current 6-digit code from your authenticator app to finish signing in.
            </p>

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-600 text-green-700 text-xs font-bold uppercase">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('two-factor.verify') }}">
                @csrf

                <div class="mb-5">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Authenticator Code
                    </label>
                    <input
                        type="text"
                        name="code"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        autocomplete="one-time-code"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-lg font-bold tracking-[0.35em] outline-none transition"
                        autofocus
                    >
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div class="mb-5">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Recovery Code
                    </label>
                    <input
                        type="text"
                        name="recovery_code"
                        autocomplete="one-time-code"
                        placeholder="Use only if you lost your authenticator"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm font-bold uppercase tracking-widest outline-none transition"
                    >
                    <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
                </div>

                <label class="mb-6 flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-gray-500">
                    <input type="checkbox" name="remember_device" value="1" class="border-gray-300 text-red-600 focus:ring-red-600">
                    Remember this device for 30 days
                </label>

                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold uppercase tracking-widest py-4 transition duration-300 shadow-lg">
                    Verify and Continue
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
