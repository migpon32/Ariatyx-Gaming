<x-guest-layout>
    <div class="flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md bg-white shadow-2xl p-10 border-t-4 border-red-600">

            <div class="mb-8 flex justify-center">
                <a href="/">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-16 w-auto">
                </a>
            </div>

            <h2 class="text-2xl font-black uppercase italic mb-8 text-[#111] tracking-tighter">
                Reset Password
            </h2>

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-600 text-green-700 text-xs font-bold uppercase">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Gmail Account
                    </label>

                    <input
                        type="email"
                        value="{{ $email }}"
                        class="w-full bg-gray-50 border border-gray-200 p-3 text-sm outline-none"
                        readonly
                    >
                </div>

                <div class="mb-6 border-t border-gray-100 pt-6">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#111] mb-4">
                        Security Questions
                    </h3>

                    @foreach ($questions as $index => $question)
                        <div class="mb-4">
                            <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                                {{ $question }}
                            </label>

                            <input
                                type="text"
                                name="security_answer_{{ $index + 1 }}"
                                value="{{ old('security_answer_' . ($index + 1)) }}"
                                oninput="forceUppercase(this)"
                                class="w-full uppercase bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none"
                                required
                            >

                            <x-input-error :messages="$errors->get('security_answer_' . ($index + 1))" class="mt-2" />
                        </div>
                    @endforeach
                </div>

                <div class="mb-4 relative">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        New Password
                    </label>

                    <input
                        id="reset_password"
                        type="password"
                        name="password"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 pr-16 text-sm outline-none"
                        required
                        autocomplete="new-password"
                    >

                    <button
                        type="button"
                        onclick="togglePassword('reset_password', this)"
                        class="absolute right-3 top-8 text-gray-500 hover:text-red-600 text-[10px] font-bold uppercase"
                    >
                        Show
                    </button>

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mb-8 relative">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Confirm New Password
                    </label>

                    <input
                        id="reset_password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 pr-16 text-sm outline-none"
                        required
                        autocomplete="new-password"
                    >

                    <button
                        type="button"
                        onclick="togglePassword('reset_password_confirmation', this)"
                        class="absolute right-3 top-8 text-gray-500 hover:text-red-600 text-[10px] font-bold uppercase"
                    >
                        Show
                    </button>
                </div>

                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold uppercase tracking-widest py-4 transition shadow-lg">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(id, button) {
            const input = document.getElementById(id);

            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Hide';
            } else {
                input.type = 'password';
                button.textContent = 'Show';
            }
        }

        function forceUppercase(input) {
            input.value = input.value.toUpperCase();
        }
    </script>
</x-guest-layout>
