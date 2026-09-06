import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Token warna MAXPORT.
 *
 * Nilainya diambil langsung dari mockup UI/UX (ui/3-52-pm) — sebuah skema
 * Material 3 dengan source color biru #0051D5. Jangan menambahkan warna mentah
 * di Blade; pakai token di bawah supaya seluruh halaman tetap satu tema.
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                // Warna utama — tombol, nav aktif, tautan, focus ring.
                brand: {
                    50: '#EFF3FF',
                    100: '#DBE1FF',  // primary-container: kartu/opsi terpilih
                    200: '#BFCEFF',
                    300: '#96AEFF',
                    400: '#4C82F5',
                    500: '#0F5FE0',
                    600: '#0051D5',  // PRIMARY
                    700: '#0042AE',
                    800: '#003486',
                    900: '#002863',
                },

                // Permukaan. "canvas" = area konten, "surface" = kartu & sidebar.
                canvas: '#F8FAFC',
                surface: {
                    DEFAULT: '#FCF8FA',
                    raised: '#FFFFFF',
                    sunken: '#F6F3F5',
                    field: '#EEEAEC',
                },

                line: {
                    DEFAULT: '#E3DFE1',
                    strong: '#CFC9CC',
                },

                // Panel kiri halaman auth — lavender-grey netral, sesuai mockup
                // Login/Register yang sengaja dipertahankan desainer.
                hero: {
                    DEFAULT: '#C8C9D6',
                    deep: '#B7B9CB',
                },

                ink: {
                    DEFAULT: '#1B1B1D',
                    muted: '#45464D',
                    subtle: '#757684',
                    navy: '#131B2E',   // judul halaman marketing
                },

                success: {
                    50: '#DCFCE7',
                    100: '#BBF7D0',
                    500: '#3ECC72',
                    700: '#15803D',
                },
                danger: {
                    50: '#FFF8F7',
                    100: '#FFDAD6',
                    500: '#BA1A1A',
                    600: '#A92C33',
                    700: '#93000A',
                },
                warning: {
                    50: '#FFF4E5',
                    100: '#FFE2BC',
                    500: '#C8843C',
                    700: '#8A5A16',
                },
            },

            borderRadius: {
                xl: '0.75rem',
                '2xl': '1rem',
            },

            boxShadow: {
                card: '0 1px 2px rgba(27, 27, 29, 0.04), 0 1px 3px rgba(27, 27, 29, 0.03)',
                lift: '0 8px 24px -12px rgba(27, 27, 29, 0.20)',
                button: '0 6px 16px -8px rgba(0, 81, 213, 0.75)',
            },

            letterSpacing: {
                label: '0.08em',
            },
        },
    },

    plugins: [forms],
};
