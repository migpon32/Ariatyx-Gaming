<x-guest-layout>
    <div class="flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-3xl bg-white shadow-2xl border-t-4 border-red-600">
            <div class="grid gap-0 md:grid-cols-[1fr_320px]">
                <div class="p-8 sm:p-10">
                    <div class="mb-8 flex items-center gap-4">
                        <a href="/">
                            <img src="{{ asset('images/logo.png') }}" alt="Ariatyx Logo" class="h-14 w-auto">
                        </a>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-red-600">Account Security</p>
                            <h1 class="text-2xl font-black uppercase italic tracking-tighter text-[#111]">Set Up Two-Factor Authentication</h1>
                        </div>
                    </div>

                    @if (session('status'))
                        <div class="mb-5 border-l-4 border-green-600 bg-green-50 p-3 text-xs font-bold uppercase text-green-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="space-y-5 text-sm text-gray-700">
                        <p>
                            Install Google Authenticator, Microsoft Authenticator, Authy, or any Android authenticator app that supports time-based one-time passwords.
                        </p>

                        <ol class="list-decimal space-y-2 pl-5">
                            <li>Open your authenticator app and choose to add a new account.</li>
                            <li>Scan the QR code, or enter the manual setup key below.</li>
                            <li>Type the current 6-digit code from the app to enable 2FA.</li>
                        </ol>
                    </div>

                    <div class="mt-6">
                        <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                            Manual Setup Key
                        </label>
                        <div class="break-all bg-gray-100 border border-gray-200 p-4 font-mono text-sm font-bold tracking-widest text-[#111]">
                            {{ trim(chunk_split($secret, 4, ' ')) }}
                        </div>
                    </div>

                    <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-8">
                        @csrf

                        <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                            6-Digit Verification Code
                        </label>
                        <input
                            type="text"
                            name="code"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            autocomplete="one-time-code"
                            class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-lg font-bold tracking-[0.35em] outline-none transition"
                            required
                            autofocus
                        >
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />

                        <button type="submit" class="mt-5 w-full bg-red-600 hover:bg-red-700 text-white font-bold uppercase tracking-widest py-4 transition duration-300 shadow-lg">
                            Verify and Enable 2FA
                        </button>
                    </form>
                </div>

                <div class="flex flex-col items-center justify-center gap-4 bg-[#111] p-8 text-white">
                    <div class="bg-white p-4">
                        {!! $qrCodeSvg !!}
                    </div>
                    <p class="max-w-xs text-center text-xs font-bold uppercase tracking-widest text-gray-300">
                        Scan this code with your authenticator app.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
