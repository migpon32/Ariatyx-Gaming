<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-[#ebebeb] px-4 py-10">
        <div class="mx-auto max-w-2xl bg-white shadow-2xl border-t-4 border-red-600 p-8 sm:p-10">
            <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-red-600">Account Security</p>
            <h1 class="mt-2 text-2xl font-black uppercase italic tracking-tighter text-[#111]">
                Recovery Codes
            </h1>

            @if (session('status'))
                <div class="mt-6 border-l-4 border-green-600 bg-green-50 p-3 text-xs font-bold uppercase text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if (count($recoveryCodes) > 0)
                <p class="mt-6 text-sm text-gray-700">
                    Store these codes somewhere private. Each code can be used once if you cannot access your authenticator app.
                </p>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($recoveryCodes as $code)
                        <div class="bg-gray-100 border border-gray-200 p-3 font-mono text-sm font-bold tracking-widest text-[#111]">
                            {{ $code }}
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-6 text-sm text-gray-700">
                    Your recovery codes are already generated and cannot be viewed again. Generate new codes if you need a fresh set.
                </p>
            @endif

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-[#111] hover:bg-gray-800 text-white font-bold uppercase tracking-widest py-4 transition duration-300">
                        Generate New Codes
                    </button>
                </form>

                <a href="{{ route('two-factor.disable') }}" class="flex-1 bg-white border border-red-600 text-red-600 hover:bg-red-50 text-center font-bold uppercase tracking-widest py-4 transition duration-300">
                    Disable 2FA
                </a>
            </div>

            <a href="{{ route('dashboard') }}" class="mt-6 block text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-red-600 transition">
                Continue to Dashboard
            </a>
        </div>
    </div>
</x-app-layout>
