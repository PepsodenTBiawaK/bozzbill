<x-layouts.guest>
    <div 
        x-data="{
            tab: '{{ $initialTab }}',
            step: 1,
            phone: '{{ old('phone', $initialPhone) }}',
            checking: false,
            checkError: '',
            memberInfo: null,
            showPasswordLogin: false,
            showPasswordAct: false,
            showForgotPasswordModal: false,
            
            async checkPhone() {
                this.checkError = '';
                if (!this.phone || this.phone.trim().length < 8) {
                    this.checkError = 'Masukkan nomor WhatsApp yang valid (minimal 8 digit).';
                    return;
                }
                
                this.checking = true;
                try {
                    const response = await fetch('{{ route('activation.check') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ phone: this.phone })
                    });
                    
                    const data = await response.json();
                    
                    if (!response.ok) {
                        this.checkError = data.message || 'Nomor WhatsApp belum terdaftar di Komunitas Bozz.';
                        return;
                    }
                    
                    if (data.status === 'already_active') {
                        this.checkError = data.message;
                    } else if (data.status === 'ready') {
                        this.memberInfo = data.user;
                        this.step = 2;
                    }
                } catch (e) {
                    this.checkError = 'Gagal menghubungi server. Periksa koneksi internet Anda.';
                } finally {
                    this.checking = false;
                }
            }
        }"
        class="min-h-screen bg-[#141518] relative flex flex-col items-center justify-center p-4 sm:p-6 overflow-x-hidden"
    >
        <!-- Atmospheric Background: Subtle Pool Hall Ambience with the Real Community Photo -->
        <div class="fixed inset-0 pointer-events-none select-none overflow-hidden">
            <img 
                src="{{ asset('images/bozz-community.png') }}" 
                alt="Komunitas Bozz Billiard" 
                class="w-full h-full object-cover object-center opacity-25 filter blur-[2px] scale-105"
            />
            <!-- Deep Vignette & Dark Gradients -->
            <div class="absolute inset-0 bg-radial from-transparent via-[#141518]/70 to-[#141518]"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-[#141518] via-transparent to-[#141518]/80"></div>
        </div>

        <!-- Main Content Container -->
        <div class="w-full max-w-[460px] relative z-10 my-auto">
            
            <!-- Branding Header -->
            <div class="flex flex-col items-center text-center mb-6">
                <div class="flex items-center gap-3 mb-2">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="Bozz Logo" class="h-11 w-auto drop-shadow" />
                    <img src="{{ asset('images/logo-text.svg') }}" alt="BOZZ BILLIARD" class="h-5 w-auto drop-shadow" />
                </div>
                <p class="text-xs uppercase tracking-[0.2em] font-medium text-[#dea538]">Komunitas Biliar Tebet</p>
            </div>

            <!-- Auth Card -->
            <div class="w-full bg-[#202227]/95 backdrop-blur-xl border border-[#333741] rounded-3xl p-6 sm:p-8 relative overflow-hidden shadow-2xl">
                
                <!-- Signature Corner Gold Ribbon (Figma Design Token) -->
                <div class="absolute -top-12 -right-12 w-36 h-36 overflow-hidden pointer-events-none z-10">
                    <div class="absolute top-0 right-0 w-36 h-5 bg-[#fce2b5] transform rotate-45 translate-x-10 translate-y-3 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-36 h-7 bg-[#dea538] transform rotate-45 translate-x-8 translate-y-7 shadow-md"></div>
                </div>

                <!-- Flash Messages -->
                @if (session('status_success'))
                    <div class="mb-5 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs sm:text-sm flex items-start gap-2.5">
                        <svg class="w-5 h-5 shrink-0 text-emerald-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('status_success') }}</span>
                    </div>
                @endif

                @if (session('status_warning'))
                    <div class="mb-5 p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs sm:text-sm flex items-start gap-2.5">
                        <svg class="w-5 h-5 shrink-0 text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ session('status_warning') }}</span>
                    </div>
                @endif

                @if (session('status_info'))
                    <div class="mb-5 p-3.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-300 text-xs sm:text-sm flex items-start gap-2.5">
                        <svg class="w-5 h-5 shrink-0 text-blue-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('status_info') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs sm:text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Segmented Control Tabs (Pill) -->
                <div class="bg-[#151619] border border-[#2e313a] p-1.5 rounded-2xl flex gap-1 mb-6">
                    <button 
                        type="button" 
                        @click="tab = 'login'" 
                        :class="tab === 'login' ? 'bg-[#292c34] text-[#dea538] font-semibold shadow' : 'text-[#d4d4d4]/60 hover:text-white'"
                        class="flex-1 py-2.5 text-center rounded-xl text-sm transition-all duration-200 cursor-pointer"
                    >
                        Masuk
                    </button>
                    <button 
                        type="button" 
                        @click="tab = 'activation'; step = 1; checkError = ''" 
                        :class="tab === 'activation' ? 'bg-[#292c34] text-[#dea538] font-semibold shadow' : 'text-[#d4d4d4]/60 hover:text-white'"
                        class="flex-1 py-2.5 text-center rounded-xl text-sm transition-all duration-200 cursor-pointer"
                    >
                        Aktivasi Akun
                    </button>
                </div>

                <!-- ================= TAB 1: MASUK ================= -->
                <div x-show="tab === 'login'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="mb-5 text-left">
                        <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Silahkan Masuk, Bozz !!</h2>
                        <p class="text-xs sm:text-sm text-[#959595] mt-1">Masukkan No WhatsApp dan password untuk mengakses akun Anda.</p>
                    </div>

                    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                        @csrf

                        <!-- No WhatsApp -->
                        <div>
                            <label for="login_phone" class="block text-xs font-medium text-[#d4d4d4] mb-1.5">No WhatsApp</label>
                            <input 
                                type="tel" 
                                id="login_phone" 
                                name="phone" 
                                value="{{ old('phone', $initialPhone) }}" 
                                placeholder="Contoh: 081234567890" 
                                required 
                                class="w-full bg-[#151619] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-3 text-white placeholder-[#555a66] text-sm sm:text-base transition outline-none"
                            />
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="login_password" class="block text-xs font-medium text-[#d4d4d4]">Password</label>
                                <button 
                                    type="button" 
                                    @click="showForgotPasswordModal = true" 
                                    class="text-xs text-[#dea538] hover:text-[#fce2b5] transition cursor-pointer"
                                >
                                    Lupa Password?
                                </button>
                            </div>
                            <div class="relative">
                                <input 
                                    :type="showPasswordLogin ? 'text' : 'password'" 
                                    id="login_password" 
                                    name="password" 
                                    placeholder="Masukkan password" 
                                    required 
                                    class="w-full bg-[#151619] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-3 pr-11 text-white placeholder-[#555a66] text-sm sm:text-base transition outline-none"
                                />
                                <button 
                                    type="button" 
                                    @click="showPasswordLogin = !showPasswordLogin" 
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#888e9b] hover:text-white cursor-pointer"
                                >
                                    <svg x-show="!showPasswordLogin" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPasswordLogin" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center pt-0.5">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input 
                                    type="checkbox" 
                                    name="remember" 
                                    class="w-4 h-4 rounded bg-[#151619] border-[#333741] text-[#dea538] focus:ring-[#dea538] focus:ring-offset-0" 
                                    checked
                                />
                                <span class="text-xs text-[#a0a5b1]">Ingat saya di perangkat ini</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            class="w-full mt-2 bg-[#dea538] hover:bg-[#c8912c] text-[#141518] font-bold py-3.5 rounded-xl text-sm sm:text-base transition duration-150 shadow-lg shadow-[#dea538]/10 cursor-pointer active:scale-[0.99]"
                        >
                            Masuk Sekarang
                        </button>
                    </form>
                </div>

                <!-- ================= TAB 2: AKTIVASI AKUN ================= -->
                <div x-show="tab === 'activation'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                    
                    <!-- Step 1: Input WhatsApp Number -->
                    <div x-show="step === 1">
                        <div class="mb-5 text-left">
                            <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Silahkan Aktivasi Akun, Bozz !!</h2>
                            <p class="text-xs sm:text-sm text-[#959595] mt-1">Ikuti langkah aktivasi ini agar akun Anda bisa digunakan.</p>
                        </div>

                        <!-- Error Banner -->
                        <div x-show="checkError" class="mb-4 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs sm:text-sm flex items-start gap-2.5" style="display: none;">
                            <svg class="w-4 h-4 shrink-0 mt-0.5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-text="checkError"></span>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-[#d4d4d4] mb-1.5">No WhatsApp Terdaftar</label>
                                <input 
                                    type="tel" 
                                    x-model="phone" 
                                    @keydown.enter.prevent="checkPhone()" 
                                    placeholder="Contoh: 081234567890" 
                                    class="w-full bg-[#151619] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-3 text-white placeholder-[#555a66] text-sm sm:text-base transition outline-none"
                                />
                                <p class="text-[11px] text-[#787e8c] mt-1.5">Nomor harus sudah didaftarkan sebelumnya oleh pengurus Bozz Billiard.</p>
                            </div>

                            <button 
                                type="button" 
                                @click="checkPhone()" 
                                :disabled="checking" 
                                class="w-full mt-2 bg-[#dea538] hover:bg-[#c8912c] disabled:opacity-50 text-[#141518] font-bold py-3.5 rounded-xl text-sm sm:text-base transition duration-150 shadow-lg shadow-[#dea538]/10 cursor-pointer flex items-center justify-center gap-2"
                            >
                                <svg x-show="checking" class="animate-spin h-4 w-4 text-[#141518]" fill="none" viewBox="0 0 24 24" style="display: none;">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="checking ? 'Memeriksa Nomor...' : 'Lanjut Aktivasi'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Password Creation -->
                    <div x-show="step === 2" style="display: none;">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold text-white tracking-tight">Buat Password Baru</h2>
                                <p class="text-xs text-[#959595]">Langkah terakhir aktivasi akun Anda.</p>
                            </div>
                            <button 
                                type="button" 
                                @click="step = 1" 
                                class="text-xs text-[#dea538] hover:underline cursor-pointer"
                            >
                                Ganti Nomor
                            </button>
                        </div>

                        <!-- Member Info Card -->
                        <div class="mb-4 p-3.5 rounded-xl bg-[#151619] border border-[#2e313a] flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-[#dea538]/10 border border-[#dea538]/30 flex items-center justify-center text-[#dea538] font-bold text-sm">
                                    <span x-text="memberInfo ? memberInfo.name.charAt(0).toUpperCase() : 'B'"></span>
                                </div>
                                <div class="text-left">
                                    <p class="font-bold text-white text-sm" x-text="memberInfo ? memberInfo.name : ''"></p>
                                    <p class="text-[11px] text-[#787e8c]" x-text="memberInfo ? memberInfo.phone : ''"></p>
                                </div>
                            </div>
                            <div class="px-2.5 py-1 rounded-md bg-[#dea538]/10 border border-[#dea538]/30 text-[#dea538] text-xs font-bold">
                                HC <span x-text="memberInfo ? memberInfo.handicap : '3N'"></span>
                            </div>
                        </div>

                        <!-- Password Form -->
                        <form method="POST" action="{{ route('activate.post') }}" class="space-y-3.5">
                            @csrf
                            <input type="hidden" name="phone" :value="phone" />

                            <div>
                                <label class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Password Baru</label>
                                <div class="relative">
                                    <input 
                                        :type="showPasswordAct ? 'text' : 'password'" 
                                        name="password" 
                                        required 
                                        placeholder="Minimal 6 karakter" 
                                        class="w-full bg-[#151619] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-3 pr-11 text-white placeholder-[#555a66] text-sm sm:text-base transition outline-none"
                                    />
                                    <button 
                                        type="button" 
                                        @click="showPasswordAct = !showPasswordAct" 
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#888e9b] hover:text-white cursor-pointer"
                                    >
                                        <svg x-show="!showPasswordAct" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showPasswordAct" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Konfirmasi Password</label>
                                <input 
                                    :type="showPasswordAct ? 'text' : 'password'" 
                                    name="password_confirmation" 
                                    required 
                                    placeholder="Ketik ulang password baru" 
                                    class="w-full bg-[#151619] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-3 text-white placeholder-[#555a66] text-sm sm:text-base transition outline-none"
                                />
                            </div>

                            <button 
                                type="submit" 
                                class="w-full mt-2 bg-[#dea538] hover:bg-[#c8912c] text-[#141518] font-bold py-3.5 rounded-xl text-sm sm:text-base transition duration-150 shadow-lg shadow-[#dea538]/10 cursor-pointer"
                            >
                                Aktifkan Akun & Masuk
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="mt-6 pt-5 border-t border-[#2e313a] text-center">
                    <p class="text-[11px] text-[#787e8c]">
                        Paspor & Manajemen Komunitas &bull; Bozz Billiard Tebet
                    </p>
                </div>

            </div>
        </div>

        <!-- Forgot Password Modal -->
        <div 
            x-show="showForgotPasswordModal" 
            x-transition.opacity 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
            style="display: none;"
        >
            <div 
                @click.away="showForgotPasswordModal = false" 
                class="w-full max-w-sm bg-[#202227] border border-[#333741] rounded-2xl p-6 shadow-2xl relative text-left"
            >
                <div class="w-10 h-10 rounded-full bg-[#dea538]/10 border border-[#dea538]/30 flex items-center justify-center text-[#dea538] mb-3.5">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>

                <h3 class="text-lg font-bold text-white mb-2">Lupa Password Akun?</h3>
                <p class="text-xs text-[#a0a5b1] leading-relaxed mb-5">
                    Demi keamanan tanpa biaya SMS gateway, reset password dilakukan langsung oleh pengurus Komunitas Bozz Billiard. Silakan hubungi pengurus di meja biliar atau via WhatsApp pengurus untuk dibuatkan password baru.
                </p>

                <button 
                    type="button" 
                    @click="showForgotPasswordModal = false" 
                    class="w-full bg-[#dea538] hover:bg-[#c8912c] text-[#141518] font-bold py-2.5 rounded-xl text-xs transition cursor-pointer"
                >
                    Saya Mengerti
                </button>
            </div>
        </div>

    </div>
</x-layouts.guest>
