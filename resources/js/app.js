import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet's default marker icon paths when bundled with Vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

window.L = L;

import './../../vendor/power-components/livewire-powergrid/dist/powergrid';

// ==========================================================================
// SWEETALERT2 INTEGRATION & HOSPITAL THEME CONFIGURATION
// ==========================================================================
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

const isDarkMode = () => document.documentElement.classList.contains('dark');

const getSwalThemeConfig = (isDanger = false) => {
    const dark = isDarkMode();
    return {
        customClass: {
            popup: `swal2-hospital-popup ${dark ? 'dark' : ''} ${isDanger ? 'swal2-hospital-danger' : 'swal2-hospital-emerald'}`,
            title: 'swal2-hospital-title',
            htmlContainer: 'swal2-hospital-html',
            confirmButton: `swal2-hospital-btn ${isDanger ? 'swal2-hospital-btn-danger' : 'swal2-hospital-btn-emerald'}`,
            cancelButton: 'swal2-hospital-btn swal2-hospital-cancel',
            input: 'swal2-hospital-input',
            actions: 'swal2-hospital-actions',
            icon: 'swal2-hospital-icon'
        },
        buttonsStyling: false,
        background: dark ? '#18181b' : '#ffffff',
        color: dark ? '#f4f4f5' : '#18181b',
        backdrop: 'rgba(9, 9, 11, 0.65)'
    };
};

// Global Helper Functions
window.swalConfirm = (message, isDanger = false) => {
    const theme = getSwalThemeConfig(isDanger);
    return Swal.fire({
        ...theme,
        title: isDanger ? 'Konfirmasi Tindakan' : 'Konfirmasi',
        text: message,
        icon: isDanger ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: isDanger ? 'Ya, Lanjutkan' : 'Ya, Setuju',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusCancel: isDanger
    }).then(result => result.isConfirmed);
};

// Safe override for window.alert
const originalAlert = window.alert;
window.alert = (message) => {
    if (window.Swal) {
        Swal.fire({
            ...getSwalThemeConfig(false),
            title: 'Pemberitahuan',
            text: String(message),
            icon: 'info',
            confirmButtonText: 'Tutup'
        });
    } else {
        originalAlert(message);
    }
};

window.swalToast = (message, variant = 'success') => {
    const icon = variant === 'danger' ? 'error' : variant;
    return Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        icon: icon,
        title: message,
        customClass: {
            popup: `swal2-hospital-toast ${isDarkMode() ? 'dark' : ''}`,
            title: 'text-xs font-semibold text-zinc-800 dark:text-zinc-100'
        }
    });
};

// Global Event Listeners for Livewire Dispatches
window.addEventListener('notify', (event) => {
    const data = event.detail || {};
    const message = data.message || (typeof data === 'string' ? data : '');
    const variant = data.variant || 'success';
    if (message) {
        window.swalToast(message, variant);
    }
});

window.addEventListener('swal:alert', (event) => {
    const params = Array.isArray(event.detail) ? event.detail[0] : (event.detail || {});
    const isDanger = params.icon === 'error' || params.icon === 'warning';
    Swal.fire({
        ...getSwalThemeConfig(isDanger),
        title: params.title || 'Informasi',
        text: params.text || params.message || '',
        icon: params.icon || 'info',
        confirmButtonText: params.confirmButtonText || 'Tutup'
    });
});

window.addEventListener('swal:toast', (event) => {
    const params = Array.isArray(event.detail) ? event.detail[0] : (event.detail || {});
    window.swalToast(params.title || params.message || '', params.variant || params.icon || 'success');
});

window.addEventListener('swal:confirm', (event) => {
    const params = Array.isArray(event.detail) ? event.detail[0] : (event.detail || {});
    const isDanger = params.isDanger || params.icon === 'warning' || /hapus|delete|batal/i.test(params.title || '');
    Swal.fire({
        ...getSwalThemeConfig(isDanger),
        title: params.title || 'Konfirmasi',
        text: params.text || params.message || '',
        icon: params.icon || (isDanger ? 'warning' : 'question'),
        showCancelButton: true,
        confirmButtonText: params.confirmButtonText || (isDanger ? 'Ya, Lanjutkan' : 'Ya, Setuju'),
        cancelButtonText: params.cancelButtonText || 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            if (params.componentId && window.Livewire) {
                const component = window.Livewire.find(params.componentId);
                if (component && params.method) {
                    component.call(params.method, ...(params.params || []));
                }
            } else if (params.onConfirm && window.Livewire) {
                window.Livewire.dispatch(params.onConfirm, params.params || {});
            }
        }
    });
});

// Hook into Livewire 3 Directive Initialization to replace native confirm with SweetAlert
const registerLivewireConfirmHook = () => {
    if (!window.Livewire) return;

    window.Livewire.hook('directive.init', ({ el, directive, component, cleanup }) => {
        if (directive.value === 'confirm') {
            const rawMessage = directive.expression || 'Apakah Anda yakin ingin melanjutkan?';
            const isPrompt = directive.modifiers.includes('prompt');

            el.__livewire_confirm = (action, instead) => {
                const isDanger = /hapus|delete|batal|permanen|nonaktif|keluar|warning|peringatan/i.test(rawMessage)
                    || el.getAttribute('variant') === 'danger'
                    || el.classList.contains('text-rose-600')
                    || el.classList.contains('text-red-600')
                    || el.classList.contains('bg-rose-600')
                    || el.classList.contains('bg-red-600');

                const theme = getSwalThemeConfig(isDanger);

                if (isPrompt) {
                    let [question, expected] = rawMessage.split('|');
                    expected = (expected || '').trim();

                    Swal.fire({
                        ...theme,
                        title: question || 'Konfirmasi Tindakan',
                        text: expected ? `Ketik "${expected}" untuk mengonfirmasi:` : 'Ketik konfirmasi Anda:',
                        input: 'text',
                        inputPlaceholder: expected,
                        icon: isDanger ? 'warning' : 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Konfirmasi',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                        inputValidator: (value) => {
                            if (expected && value !== expected) {
                                return `Teks tidak sesuai. Harap ketik "${expected}"`;
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            action();
                        } else {
                            instead();
                        }
                    });
                } else {
                    Swal.fire({
                        ...theme,
                        title: isDanger ? 'Konfirmasi Tindakan' : 'Konfirmasi',
                        text: rawMessage,
                        icon: isDanger ? 'warning' : 'question',
                        showCancelButton: true,
                        confirmButtonText: isDanger ? 'Ya, Lanjutkan' : 'Ya, Setuju',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                        focusCancel: isDanger
                    }).then((result) => {
                        if (result.isConfirmed) {
                            action();
                        } else {
                            instead();
                        }
                    });
                }
            };
        }
    });
};

document.addEventListener('livewire:init', registerLivewireConfirmHook);
if (window.Livewire) {
    registerLivewireConfirmHook();
}

// ==========================================================================
// CURRENCY / RUPIAH INPUT FORMATTER COMPONENT
// ==========================================================================
const registerCurrencyInput = () => {
    const alpine = window.Alpine;
    if (!alpine) return;

    if (alpine.data && typeof alpine.data === 'function') {
        try {
            alpine.data('currencyInput', (config = {}) => {
                const prefix = config.prefix !== undefined ? config.prefix : 'Rp. ';
                return {
                    display: '',
                    prefix: prefix,
                    value: config.value,
                    init() {
                        this.updateDisplay(this.value);
                        this.$watch('value', (newVal) => {
                            const currentDigits = (this.display || '').replace(/\D/g, '');
                            const newDigits = (newVal !== null && newVal !== undefined && newVal !== '') ? String(newVal).replace(/\D/g, '') : '';
                            if (currentDigits !== newDigits) {
                                this.updateDisplay(newVal);
                            }
                        });
                    },
                    updateDisplay(val) {
                        if (val === null || val === undefined || val === '') {
                            this.display = '';
                            return;
                        }
                        const clean = String(val).replace(/\D/g, '');
                        if (!clean) {
                            this.display = '';
                            return;
                        }
                        try {
                            const num = BigInt(clean);
                            this.display = this.prefix + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        } catch (e) {
                            this.display = '';
                        }
                    },
                    onInput(event) {
                        const input = event.target;
                        const raw = input.value;
                        const cursor = input.selectionStart || raw.length;
                        const cleanDigits = raw.replace(/\D/g, '');

                        if (!cleanDigits) {
                            this.display = '';
                            this.value = null;
                            return;
                        }

                        try {
                            const num = BigInt(cleanDigits);
                            const formatted = this.prefix + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            this.display = formatted;
                            this.value = cleanDigits;

                            this.$nextTick(() => {
                                const textBeforeCursor = raw.slice(0, cursor);
                                const digitsBefore = (textBeforeCursor.match(/\d/g) || []).length;
                                let newPos = formatted.length;
                                if (digitsBefore === 0) {
                                    newPos = this.prefix.length;
                                } else {
                                    let count = 0;
                                    for (let i = 0; i < formatted.length; i++) {
                                        if (/\d/.test(formatted[i])) {
                                            count++;
                                            if (count === digitsBefore) {
                                                newPos = i + 1;
                                                break;
                                            }
                                        }
                                    }
                                }
                                input.setSelectionRange(newPos, newPos);
                            });
                        } catch (e) {
                            this.value = cleanDigits;
                        }
                    },
                    onKeyDown(event) {
                        const input = event.target;
                        if (event.key === 'Backspace' && input.selectionStart <= this.prefix.length && input.selectionEnd <= this.prefix.length) {
                            event.preventDefault();
                        }
                    },
                    onFocus(event) {
                        const input = event.target;
                        if (this.display && input.selectionStart < this.prefix.length) {
                            this.$nextTick(() => {
                                input.setSelectionRange(this.prefix.length, this.prefix.length);
                            });
                        }
                    },
                    clearInput() {
                        this.display = '';
                        this.value = null;
                        if (this.$refs && this.$refs.input) {
                            this.$refs.input.focus();
                        }
                    }
                };
            });
        } catch (e) {
            // Silently ignore re-registration
        }
    }
};

document.addEventListener('alpine:init', registerCurrencyInput);
document.addEventListener('livewire:init', registerCurrencyInput);
if (window.Alpine) {
    registerCurrencyInput();
}


