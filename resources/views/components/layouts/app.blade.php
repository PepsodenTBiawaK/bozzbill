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
                        <p class="px-3 mb-2 text-xs font-semibold text-[#888e9b] uppercase tracking-wider">Pengurus</p>
                        <div class="space-y-1">
                            <a 
                                href="{{ route('dashboard') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-[#202227] text-[#dea538] border-l-2 border-[#dea538]' : 'text-[#d4d4d4] hover:bg-[#202227] hover:text-white' }} transition group"
                            >
                                <svg class="w-4 h-4 text-[#888e9b] group-hover:text-[#dea538] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span>Dashboard</span>
                            </a>

                            <a 
                                href="{{ route('evaluating-hc.index') }}" 
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('evaluating-hc.*') ? 'bg-[#202227] text-[#dea538] border-l-2 border-[#dea538]' : 'text-[#d4d4d4] hover:bg-[#202227] hover:text-white' }} transition group"
                            >
                                <svg class="w-4 h-4 {{ request()->routeIs('evaluating-hc.*') ? 'text-[#dea538]' : 'text-[#888e9b] group-hover:text-[#dea538]' }} transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span>Evaluasi HC</span>
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
                            <span>Roster</span>
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
                        <x-player-avatar :player="auth()->user()" class="w-8 h-8 rounded-full bg-[#16171a] border border-[#dea538]/40 text-[#dea538] font-bold text-xs" />
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
                <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="Logo" class="h-8 w-auto" />
                    <img src="{{ asset('images/logo-text.svg') }}" alt="BOZZ BILLIARD" class="h-2.5 sm:h-3.5 w-auto" />
                </a>

                <div class="flex items-center gap-2 shrink-0">
                    <span class="flex items-center gap-2 px-2.5 py-1 rounded-full bg-[#202227] border border-[#333741]">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="text-xs font-bold text-[#dea538]">HC {{ auth()->user()?->handicap?->value ?? '3N' }}</span>
                    </span>
                    <a
                        href="{{ route('profile') }}"
                        aria-label="Buka profil"
                        title="Profil"
                        @if (request()->routeIs('profile*')) aria-current="page" @endif
                        class="flex items-center justify-center w-11 h-11 shrink-0 rounded-full bg-[#202227] border {{ request()->routeIs('profile*') ? 'border-[#dea538]' : 'border-[#333741]' }} hover:bg-[#2b2e35] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#dea538] transition"
                    >
                        <x-player-avatar :player="auth()->user()" class="w-9 h-9 rounded-full text-xs font-bold text-[#dea538]" />
                    </a>
                </div>
            </header>

            <!-- Page Body Content (with bottom padding on mobile for bottom nav) -->
            <main class="flex-1 p-4 sm:px-6 sm:pt-6 lg:p-8 {{ auth()->user()?->isAdmin() ? 'pb-[calc(9rem+env(safe-area-inset-bottom))]' : 'pb-[calc(6rem+env(safe-area-inset-bottom))]' }} lg:pb-8">
                {{ $slot }}
            </main>
        </div>

    </div>

    <!-- ================= MOBILE APP-STYLE BOTTOM NAVIGATION BAR ================= -->
    <!-- (No Hamburger Icon! Sesuai permintaan khusus user untuk ratio mobile friendly) -->
    <nav aria-label="Navigasi utama" class="lg:hidden fixed bottom-0 inset-x-0 bg-[#191919]/95 backdrop-blur-xl border-t border-[#333741] z-50 px-2 py-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))] select-none">
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

            <!-- Tab 5: Drilling -->
            <a 
                href="{{ route('drilling') }}"
                @if (request()->routeIs('drilling*')) aria-current="page" @endif
                class="flex flex-col items-center justify-center py-1.5 rounded-xl transition {{ request()->routeIs('drilling*') ? 'text-[#dea538]' : 'text-[#888e9b] hover:text-white' }}"
            >
                <svg class="w-5 h-5 mb-0.5 {{ request()->routeIs('drilling*') ? 'text-[#dea538]' : 'text-[#888e9b]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" stroke-width="2" />
                    <circle cx="12" cy="12" r="6" stroke-width="2" />
                    <circle cx="12" cy="12" r="2" stroke-width="2" />
                </svg>
                <span class="text-[10px] font-medium tracking-tight">Drilling</span>
            </a>

        </div>
    </nav>

    @if (auth()->user()?->isAdmin())
        <div
            x-data="{
                panelOpen: false,
                bodyOverflow: '',
                openPanel() {
                    this.bodyOverflow = document.body.style.overflow;
                    this.$refs.adminPanel.showModal();
                    this.panelOpen = true;
                    document.body.style.overflow = 'hidden';
                },
                closePanel() {
                    this.$refs.adminPanel.close();
                },
                afterClose() {
                    this.panelOpen = false;
                    document.body.style.overflow = this.bodyOverflow;
                    this.$refs.adminPanelTrigger.focus();
                }
            }"
            @resize.window="if (panelOpen && window.innerWidth >= 1024) closePanel()"
        >
            <button
                x-ref="adminPanelTrigger"
                type="button"
                @click="openPanel()"
                aria-haspopup="dialog"
                aria-controls="admin-panel"
                :aria-expanded="panelOpen"
                class="lg:hidden fixed right-4 bottom-[calc(5rem+env(safe-area-inset-bottom))] z-40 inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-[#191919] border border-[#dea538]/60 text-[#dea538] text-xs font-bold shadow-lg hover:bg-[#252830] transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Pengurus
            </button>

            <dialog
                id="admin-panel"
                x-ref="adminPanel"
                aria-labelledby="admin-panel-title"
                @close="afterClose()"
                @click="if ($event.target === $refs.adminPanel) closePanel()"
                class="lg:hidden fixed inset-x-0 bottom-0 top-auto m-0 w-full max-w-none max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-t-3xl border border-[#333741] bg-[#202227] text-[#fafafa] p-0 backdrop:bg-black/70 backdrop:backdrop-blur-sm"
            >
                <div class="p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 id="admin-panel-title" class="text-lg font-bold text-white">Menu Pengurus</h2>
                        <button type="button" @click="closePanel()" autofocus aria-label="Tutup panel pengurus" class="w-9 h-9 rounded-xl bg-[#16171a] border border-[#333741] text-[#959595] hover:text-white flex items-center justify-center transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <nav aria-label="Navigasi pengurus" class="space-y-2">
                        <a href="{{ route('dashboard') }}" class="block p-4 rounded-2xl border {{ request()->routeIs('dashboard') ? 'bg-[#dea538]/10 border-[#dea538]/40' : 'bg-[#16171a] border-[#333741] hover:border-[#dea538]/40' }} transition">
                            <span class="block text-sm font-bold text-[#dea538]">Dashboard</span>
                            <span class="block text-xs text-[#959595] mt-1">Ringkasan event, pemain, dan pertandingan.</span>
                        </a>
                        <a href="{{ route('evaluating-hc.index') }}" class="block p-4 rounded-2xl border {{ request()->routeIs('evaluating-hc.*') ? 'bg-[#dea538]/10 border-[#dea538]/40' : 'bg-[#16171a] border-[#333741] hover:border-[#dea538]/40' }} transition">
                            <span class="block text-sm font-bold text-[#dea538]">Evaluasi HC</span>
                            <span class="block text-xs text-[#959595] mt-1">Tinjau rekomendasi handicap anggota.</span>
                        </a>
                    </nav>
                </div>
            </dialog>
        </div>
    @endif

</body>
</html>
