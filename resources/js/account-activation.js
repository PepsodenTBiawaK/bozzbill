export default function accountActivation(checkUrl, initialTab = 'login', initialPhone = '') {
    return {
        tab: initialTab === 'activation' ? 'activation' : 'login',
        step: 1,
        phone: initialPhone,
        activationCode: '',
        checking: false,
        activating: false,
        checkError: '',
        activationNotice: '',
        memberInfo: null,
        showPasswordLogin: false,
        showPasswordAct: false,
        showForgotPasswordModal: false,
        async checkPhone() {
            if (this.checking) return;
            this.checkError = '';
            this.activationNotice = '';
            if (!this.phone || this.phone.trim().length < 8) {
                this.checkError = 'Masukkan nomor WhatsApp yang valid (minimal 8 digit).';
                return;
            }

            const checkedPhone = this.phone;
            const checkedCode = this.activationCode;
            if (checkedCode && !/^[0-9]{8}$/.test(checkedCode)) {
                this.checkError = 'Masukkan kode aktivasi 8 digit dari pengurus.';
                return;
            }
            this.checking = true;
            try {
                const response = await fetch(checkUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ phone: checkedPhone, activation_code: checkedCode }),
                });
                const data = await response.json();
                if (this.phone !== checkedPhone || this.activationCode !== checkedCode || this.tab !== 'activation') return;
                if (!response.ok) {
                    this.checkError = response.status === 429
                        ? 'Terlalu banyak percobaan. Tunggu sebentar sebelum mencoba lagi.'
                        : data.message || 'Nomor WhatsApp atau kode aktivasi tidak valid.';
                    return;
                }
                if (data.status === 'already_active') {
                    this.phone = data.phone || checkedPhone;
                    this.memberInfo = null;
                    this.step = 1;
                    this.activationNotice = data.message;
                    this.tab = 'login';
                    this.$nextTick(() => this.$refs.loginPassword.focus());
                } else if (data.status === 'ready') {
                    this.phone = data.user.phone;
                    this.memberInfo = data.user;
                    this.step = 2;
                }
            } catch {
                if (this.phone === checkedPhone && this.tab === 'activation') {
                    this.checkError = 'Gagal menghubungi server. Periksa koneksi internet Anda.';
                }
            } finally {
                this.checking = false;
            }
        },
        submitActivation(event) {
            if (this.activating) {
                event.preventDefault();
                return;
            }
            this.activating = true;
        },
        resetSubmission() {
            this.activating = false;
        },
    };
}
