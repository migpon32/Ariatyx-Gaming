<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-[#ebebeb] px-4 py-10">
        <div class="mx-auto max-w-xl bg-white shadow-2xl border-t-4 border-red-600 p-8 sm:p-10">
            <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-red-600">Account Security</p>
            <h1 class="mt-2 text-2xl font-black uppercase italic tracking-tighter text-[#111]">
                Disable Two-Factor Authentication
            </h1>

            <p class="mt-6 text-sm text-gray-700">
                Disabling 2FA removes your authenticator secret, recovery codes, and remembered devices. Ariatyx Gaming will ask you to set up 2FA again before using protected pages.
            </p>

            <form method="POST" action="{{ route('two-factor.destroy') }}" class="mt-8">
                @csrf
                @method('DELETE')

                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold uppercase tracking-widest py-4 transition duration-300 shadow-lg">
                    Disable 2FA
                </button>
            </form>

            <a href="{{ route('two-factor.recovery-codes') }}" class="mt-6 block text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-red-600 transition">
                Keep 2FA Enabled
            </a>
        </div>
    </div>
</x-app-layout>
