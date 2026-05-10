<x-guest-layout>
    <div class="flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md bg-white shadow-2xl p-10 border-t-4 border-red-600">

            <div class="mb-8 flex justify-center">
                <a href="/">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-16 w-auto">
                </a>
            </div>

            <h2 class="text-2xl font-black uppercase italic mb-8 text-[#111] tracking-tighter">
                Create Account
            </h2>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-600">
                    <h3 class="text-sm font-bold text-red-800 uppercase tracking-wider">
                        Registration Failed
                    </h3>

                    <div class="mt-1 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{!! $error !!}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none @error('username') border-red-500 @enderror"
                        required
                        autofocus
                    >

                    @error('username')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none @error('email') border-red-500 @enderror"
                        required
                    >

                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{!! $message !!}</p>
                    @else
                        <p class="text-gray-400 text-[10px] mt-1">
                            We'll never share your email with anyone else.
                        </p>
                    @enderror
                </div>

                <div class="mb-4 relative">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Password
                    </label>

                    <input
                        id="register_password"
                        type="password"
                        name="password"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 pr-16 text-sm outline-none @error('password') border-red-500 @enderror"
                        required
                        autocomplete="new-password"
                    >

                    <button
                        type="button"
                        onclick="togglePassword('register_password', this)"
                        class="absolute right-3 top-8 text-gray-500 hover:text-red-600 text-[10px] font-bold uppercase"
                    >
                        Show
                    </button>

                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @else
                        <p class="text-gray-400 text-[10px] mt-1">Minimum 8 characters.</p>
                    @enderror
                </div>

                <div class="mb-6 relative">
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                        Confirm Password
                    </label>

                    <input
                        id="register_password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 pr-16 text-sm outline-none"
                        required
                        autocomplete="new-password"
                    >

                    <button
                        type="button"
                        onclick="togglePassword('register_password_confirmation', this)"
                        class="absolute right-3 top-8 text-gray-500 hover:text-red-600 text-[10px] font-bold uppercase"
                    >
                        Show
                    </button>
                </div>

                <div class="mb-6 border-t border-gray-100 pt-6">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#111] mb-4">
                        Security Questions
                    </h3>

                    <div class="mb-4">
                        <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                            Security Question 1
                        </label>

                        <input
                            type="text"
                            name="security_question_1"
                            value="{{ old('security_question_1') }}"
                            class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none @error('security_question_1') border-red-500 @enderror"
                            required
                        >

                        <x-input-error :messages="$errors->get('security_question_1')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                            Answer 1
                        </label>

                        <input
                            type="text"
                            name="security_answer_1"
                            value="{{ old('security_answer_1') }}"
                            oninput="forceUppercase(this)"
                            class="w-full uppercase bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none @error('security_answer_1') border-red-500 @enderror"
                            required
                        >

                        <x-input-error :messages="$errors->get('security_answer_1')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                            Security Question 2
                        </label>

                        <input
                            type="text"
                            name="security_question_2"
                            value="{{ old('security_question_2') }}"
                            class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none @error('security_question_2') border-red-500 @enderror"
                            required
                        >

                        <x-input-error :messages="$errors->get('security_question_2')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                            Answer 2
                        </label>

                        <input
                            type="text"
                            name="security_answer_2"
                            value="{{ old('security_answer_2') }}"
                            oninput="forceUppercase(this)"
                            class="w-full uppercase bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none @error('security_answer_2') border-red-500 @enderror"
                            required
                        >

                        <x-input-error :messages="$errors->get('security_answer_2')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                            Security Question 3
                        </label>

                        <input
                            type="text"
                            name="security_question_3"
                            value="{{ old('security_question_3') }}"
                            class="w-full bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none @error('security_question_3') border-red-500 @enderror"
                            required
                        >

                        <x-input-error :messages="$errors->get('security_question_3')" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-2">
                            Answer 3
                        </label>

                        <input
                            type="text"
                            name="security_answer_3"
                            value="{{ old('security_answer_3') }}"
                            oninput="forceUppercase(this)"
                            class="w-full uppercase bg-gray-50 border border-gray-200 focus:ring-1 focus:ring-red-600 p-3 text-sm outline-none @error('security_answer_3') border-red-500 @enderror"
                            required
                        >

                        <x-input-error :messages="$errors->get('security_answer_3')" class="mt-2" />
                    </div>
                </div>

                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold uppercase tracking-widest py-4 transition shadow-lg mb-4">
                    Register
                </button>

                <p class="text-center text-[10px] font-bold uppercase tracking-widest text-gray-400">
                    Already have an account?
                    <a href="{{ route('login') }}" class="text-red-600 hover:underline">Sign In</a>
                </p>
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
