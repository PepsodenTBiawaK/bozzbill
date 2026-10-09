<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'BozzBill - Paspor Komunitas Bozz Billiard' }}</title>

    <!-- Google Fonts: Urbanist -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#292929] text-[#fafafa] font-sans antialiased min-h-screen selection:bg-[#dea538] selection:text-[#191919]">
    
    <div class="flex min-h-screen bg-[#292929]">
        
        <!-- ================= DESKTOP SIDEBAR MENU (Persis Frame Figma 184:1728) ================= -->
        <aside class="hidden lg:flex flex-col w-[253px] shrink-0 bg-[#191919] border-r border-[#333741] sticky top-0 h-screen z-40 select-none">
            
            <!-- Sidebar Header / Logo -->
            <div class="p-6 flex items-center justify-between border-b border-[#2e313a]">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="Logo" class="h-8 w-auto shrink-0" />
                    <img src="{{ asset('images/logo-text.svg') }}" alt="BOZZ BILLIARD" class="h-3.5 w-auto" />
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-6">
                
                <!-- Admin Section (Only for Admin Role) -->
                @if (auth()->user()?->isAdmin())
                    <div>
                        <p class="px-3 mb-2 text-xs font-semibold text-[#888e9b] uppercase tracking-wider">Admin</p>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('home') }}" 
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('home') ? 'bg-[#202227] text-[#dea538]' : 'text-[#d4d4d4] hover:bg-[#202227] hover:text-white' }} transition group"
                            >
                                <svg class="w-4 h-4 text-[#888e9b] group-hover:text-[#dea538] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span>Dashboard</span>
                            </a>

                            <a 
                                href="{{ route('fun-games.quick-logger') }}" 
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('fun-games.quick-logger') ? 'bg-[#202227] text-[#dea538] border-l-2 border-[#dea538]' : 'text-[#d4d4d4] hover:bg-[#202227] hover:text-white' }} transition group"
                            >
                                <svg class="w-4 h-4 text-[#888e9b] group-hover:text-[#dea538] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Input Fun Game</span>
                            </a>

                            <a 
                                href="{{ route('evaluating-hc.index') }}" 
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('evaluating-hc.*') ? 'bg-[#202227] text-[#dea538] border-l-2 border-[#dea538]' : 'text-[#d4d4d4] hover:bg-[#202227] hover:text-white' }} transition group"
                            >
                                <svg class="w-4 h-4 {{ request()->routeIs('evaluating-hc.*') ? 'text-[#dea538]' : 'text-[#888e9b] group-hover:text-[#dea538]' }} transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span>Evaluating HC</span>
                            </a>

                            <a 
                                href="{{ route('roster', ['tab' => 'roster']) }}" 
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-[#d4d4d4] hover:bg-[#202227] hover:text-white transition group"
                            >
                                <svg class="w-4 h-4 text-[#888e9b] group-hover:text-[#dea538] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <span>Add Member</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Member Section -->
                <div>
                    <p class="px-3 mb-2 text-xs font-semibold text-[#888e9b] uppercase tracking-wider">Member</p>
                    <div class="space-y-1">
                        <!-- Home -->
                        <a 
                            href="{{ route('home') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('home') ? 'bg-[#202227] text-[#dea538] border-l-2 border-[#dea538]' : 'text-[#d4d4d4] hover:bg-[#202227] hover:text-white' }} transition"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('home') ? 'text-[#dea538]' : 'text-[#888e9b]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Home</span>
                        </a>

                        <!-- Head To Head -->
                        <a 
                            href="{{ route('head-to-head') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('head-to-head') ? 'bg-[#202227] text-[#dea538] border-l-2 border-[#dea538]' : 'text-[#d4d4d4] hover:bg-[#202227] hover:text-white' }} transition group"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('head-to-head') ? 'text-[#dea538]' : 'text-[#888e9b] group-hover:text-[#dea538]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Head To Head</span>
                        </a>

                        <!-- Fun Game -->
                        <a 
                            href="{{ route('fun-games.index') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('fun-games.*') ? 'bg-[#202227] text-[#dea538] border-l-2 border-[#dea538]' : 'text-[#d4d4d4] hover:bg-[#202227] hover:text-white' }} transition group"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('fun-games.*') ? 'text-[#dea538]' : 'text-[#888e9b] group-hover:text-[#dea538]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                            <span>Fun Game</span>
                        </a>

                        <!-- Drilling -->
                        <a 
                            href="{{ route('drilling') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('drilling*') ? 'bg-[#202227] text-[#dea538] border-l-2 border-[#dea538]' : 'text-[#d4d4d4] hover:bg-[#202227] hover:text-white' }} transition group"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('drilling*') ? 'text-[#dea538]' : 'text-[#888e9b] group-hover:text-[#dea538]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                <circle cx="12" cy="12" r="6" stroke-width="2"/>
                                <circle cx="12" cy="12" r="2" stroke-width="2"/>
                            </svg>
                            <span>Drilling</span>
                        </a>

                        <!-- Roaster -->
                        <a 
                            href="{{ route('roster') }}" 
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('roster*') ? 'bg-[#202227] text-[#dea538] border-l-2 border-[#dea538]' : 'text-[#d4d4d4] hover:bg-[#202227] hover:text-white' }} transition group"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('roster*') ? 'text-[#dea538]' : 'text-[#888e9b] group-hover:text-[#dea538]' }} transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>Roaster</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Profile Nav Button at Bottom of Sidebar (Direct Link to Profile) -->
            <div class="p-3 border-t border-[#2e313a]">
                <a 
                    href="{{ route('profile') }}" 
                    class="w-full flex items-center justify-between p-2.5 rounded-xl {{ request()->routeIs('profile*') ? 'bg-[#2b2e35] text-[#dea538] border border-[#dea538]/40' : 'bg-[#202227] hover:bg-[#2b2e35] text-[#d4d4d4]' }} transition group cursor-pointer"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-[#16171a] border border-[#dea538]/40 flex items-center justify-center text-[#dea538] font-bold text-xs shrink-0">
                            {{ auth()->user()?->initials ?? 'BP' }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-white truncate">{{ auth()->user()?->name ?? 'Player' }}</p>
                            <p class="text-[10px] text-[#dea538] font-semibold">HC {{ auth()->user()?->handicap?->value ?? '3N' }} &bull; Profil</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-[#888e9b] group-hover:text-[#dea538] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

        </aside>

        <!-- ================= MAIN CONTENT AREA ================= -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- Mobile Top Header (Hidden on Desktop) -->
            <header class="lg:hidden flex items-center justify-between px-4 py-3.5 bg-[#191919] border-b border-[#333741] sticky top-0 z-30 select-none">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="Logo" class="h-8 w-auto" />
                    <img src="{{ asset('images/logo-text.svg') }}" alt="BOZZ BILLIARD" class="h-3.5 w-auto" />
                </a>

                <div class="flex items-center gap-2.5">
                    <a 
                        href="{{ route('profile') }}" 
                        class="flex items-center gap-2 px-2.5 py-1 rounded-full bg-[#202227] border {{ request()->routeIs('profile*') ? 'border-[#dea538]' : 'border-[#333741]' }}"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="text-xs font-bold text-[#dea538]">HC {{ auth()->user()?->handicap?->value ?? '3N' }}</span>
                    </a>
                </div>
            </header>

            <!-- Page Body Content (with bottom padding on mobile for bottom nav) -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
                {{ $slot }}
            </main>
        </div>

    </div>

    <!-- ================= MOBILE APP-STYLE BOTTOM NAVIGATION BAR ================= -->
    <!-- (No Hamburger Icon! Sesuai permintaan khusus user untuk ratio mobile friendly) -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-[#191919]/95 backdrop-blur-xl border-t border-[#333741] z-50 px-2 py-2 select-none safe-bottom">
        <div class="grid grid-cols-5 items-center text-center">
            
            <!-- Tab 1: Home -->
            <a 
                href="{{ route('home') }}" 
                class="flex flex-col items-center justify-center py-1.5 rounded-xl transition {{ request()->routeIs('home') ? 'text-[#dea538]' : 'text-[#888e9b] hover:text-white' }}"
            >
                <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('home') ? 'text-[#dea538]' : 'text-[#888e9b]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[10px] font-medium tracking-tight">Beranda</span>
            </a>

            <!-- Tab 2: Head To Head -->
            <a 
                href="{{ route('head-to-head') }}" 
                class="flex flex-col items-center justify-center py-1.5 rounded-xl transition {{ request()->routeIs('head-to-head') ? 'text-[#dea538]' : 'text-[#888e9b] hover:text-white' }}"
            >
                <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('head-to-head') ? 'text-[#dea538]' : 'text-[#888e9b]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-[10px] font-medium tracking-tight">H2H</span>
            </a>

            <!-- Tab 3: Fun Game -->
            <a 
                href="{{ route('fun-games.index') }}" 
                class="flex flex-col items-center justify-center py-1.5 rounded-xl transition {{ request()->routeIs('fun-games.*') ? 'text-[#dea538]' : 'text-[#888e9b] hover:text-white' }}"
            >
                <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('fun-games.*') ? 'text-[#dea538]' : 'text-[#888e9b]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
                <span class="text-[10px] font-medium tracking-tight">Fun Game</span>
            </a>

            <!-- Tab 4: Roster -->
            <a 
                href="{{ route('roster') }}" 
                class="flex flex-col items-center justify-center py-1.5 rounded-xl transition {{ request()->routeIs('roster*') ? 'text-[#dea538]' : 'text-[#888e9b] hover:text-white' }}"
            >
                <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('roster*') ? 'text-[#dea538]' : 'text-[#888e9b]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="text-[10px] font-medium tracking-tight">Roster</span>
            </a>

            <!-- Tab 5: Profil -->
            <a 
                href="{{ route('profile') }}" 
                class="flex flex-col items-center justify-center py-1.5 rounded-xl transition {{ request()->routeIs('profile*') ? 'text-[#dea538]' : 'text-[#888e9b] hover:text-white' }}"
            >
                <div class="w-5 h-5 mb-0.5 rounded-full bg-[#202227] border {{ request()->routeIs('profile*') ? 'border-[#dea538]' : 'border-[#888e9b]' }} flex items-center justify-center text-[9px] font-bold text-[#dea538]">
                    {{ auth()->user()?->initials ?? 'BP' }}
                </div>
                <span class="text-[10px] font-medium tracking-tight">Profil</span>
            </a>

        </div>
    </nav>

</body>
</html>
