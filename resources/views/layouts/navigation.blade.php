<nav x-data="{ open: false }" class="sticky top-0 border-b border-white/10 font-['Inter'] z-50 shadow-2xl" style="background-color: #000 !important;">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            
            {{-- Logo / Brand --}}
            <div class="flex items-center">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                        <img src="{{ asset('images/logo.png') }}" alt="Ariatyx Logo" class="h-6 w-auto object-contain transition-transform duration-300 group-hover:scale-110">
                        <span class="text-white font-black uppercase tracking-tighter text-lg font-['Oswald']">ARIATYX GAMES</span>
                    </a>
                </div>
            </div>

            {{-- Desktop Navigation --}}
            <div class="hidden sm:flex sm:items-center ml-auto">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 bg-transparent hover:text-white focus:outline-none transition">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.214a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="bg-[#111] border border-white/10 shadow-2xl py-1">

                            {{-- HOME (TOP) --}}
                            <x-dropdown-link :href="route('dashboard')" class="uppercase text-[9px] font-bold tracking-widest text-gray-300 hover:bg-white/5 hover:text-white">
                                {{ __('Home') }}
                            </x-dropdown-link>

                            {{-- PROFILE (MIDDLE) --}}
                            <x-dropdown-link :href="route('profile.edit')" class="uppercase text-[9px] font-bold tracking-widest text-gray-300 hover:bg-white/5 hover:text-white">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('two-factor.recovery-codes')" class="uppercase text-[9px] font-bold tracking-widest text-gray-300 hover:bg-white/5 hover:text-white">
                                {{ __('2FA Security') }}
                            </x-dropdown-link>

                            {{-- LOGOUT --}}
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="uppercase text-[9px] font-bold tracking-widest text-red-500 hover:bg-white/5">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>

                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Mobile Hamburger --}}
            <div class="flex items-center sm:hidden ml-auto">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-white/10 focus:outline-none transition duration-150">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div x-show="open" 
         x-transition
         class="absolute top-full sm:hidden z-50 mt-0 w-64 shadow-2xl border-l border-white/10"
         style="background-color: #000000 !important; right: 0 !important;"
         @click.away="open = false">
        
        <div class="px-4 py-3">
            
            {{-- User Info --}}
            <div class="mb-4 pb-3 border-b border-white/10">
                <div class="font-medium text-sm text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-xs text-gray-400 mt-1 break-all">{{ Auth::user()->email }}</div>
            </div>

            <div class="space-y-2">

                {{-- HOME (TOP) --}}
                <x-responsive-nav-link :href="route('dashboard')" class="text-white uppercase text-[11px] font-bold tracking-[0.2em] hover:bg-white/10 rounded-md px-3 py-2 transition flex items-center w-full">
                    {{ __('Home') }}
                </x-responsive-nav-link>

                {{-- PROFILE (MIDDLE) --}}
                <x-responsive-nav-link :href="route('profile.edit')" class="text-white uppercase text-[11px] font-bold tracking-[0.2em] hover:bg-white/10 rounded-md px-3 py-2 transition flex items-center w-full">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('two-factor.recovery-codes')" class="text-white uppercase text-[11px] font-bold tracking-[0.2em] hover:bg-white/10 rounded-md px-3 py-2 transition flex items-center w-full">
                    {{ __('2FA Security') }}
                </x-responsive-nav-link>

                {{-- LOGOUT --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" 
                        onclick="event.preventDefault(); this.closest('form').submit();" 
                        class="text-red-500 uppercase text-[11px] font-bold tracking-[0.2em] hover:bg-white/10 rounded-md px-3 py-2 transition flex items-center w-full">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>

            </div>
        </div>
    </div>
</nav>
