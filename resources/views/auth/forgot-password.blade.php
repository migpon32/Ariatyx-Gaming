<x-guest-layout>
    <div class="flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md bg-white shadow-2xl p-10 border-t-4 border-red-600">

            <div class="mb-8 flex justify-center">
                <a href="/">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-16 w-auto">
                </a>
            </div>

            <h2 class="text-2xl font-black uppercase italic mb-4 text-[#111] tracking-tighter">
                Reset Password
            </h2>

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-600 text-green-700 text-xs font-bold uppercase">
                    {{ session('status') }}
                </div>
            @endif

            <p class="text-[10px] text-gray-500 mb-8 uppercase font-bold tracking-widest leading-tight">
                Enter your Gmail account below. You will answer your three security questions before creating a new password.
            </p>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-6">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Gmail Account
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none"
                        required
                        autofocus
                    >

                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <button type="submit" class="w-full bg-[#111] hover:bg-red-600 text-white font-bold uppercase tracking-widest py-4 transition shadow-lg">
                    Continue
                </button>

                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-[#111]">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
    