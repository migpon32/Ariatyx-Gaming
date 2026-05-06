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

            <form method="POST" action="{{ route('password.store') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-4">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
                        class="w-full bg-gray-50 border border-gray-200 p-3 text-sm outline-none"
                        required
                        readonly
                    >

                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mb-4 relative">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        New Password
                    </label>

                    <input
                        id="reset_password"
                        type="password"
                        name="password"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 pr-12 text-sm outline-none"
                        required
                        autocomplete="new-password"
                    >

                    <button
                        type="button"
                        onclick="togglePassword('reset_password', this)"
                        class="absolute right-3 top-8 text-gray-500 hover:text-red-600 text-lg"
                    >
                        👁
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
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 pr-12 text-sm outline-none"
                        required
                        autocomplete="new-password"
                    >

                    <button
                        type="button"
                        onclick="togglePassword('reset_password_confirmation', this)"
                        class="absolute right-3 top-8 text-gray-500 hover:text-red-600 text-lg"
                    >
                        👁
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