import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Filament/**/*.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                leaf: {
                    50: '#eef6f0', 100: '#d7e9dd', 200: '#b0d3bd',
                    300: '#84b898', 400: '#5c9a75', 500: '#4a7c59',
                    600: '#3a6347', 700: '#2e4e39', 800: '#22392a',
                    900: '#14301f',
                },
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'Noto Sans Arabic', 'sans-serif'],
            },
            boxShadow: {
                card: '0 1px 2px rgba(20, 48, 31, 0.04), 0 8px 24px -12px rgba(20, 48, 31, 0.12)',
                'card-hover': '0 4px 8px rgba(20, 48, 31, 0.06), 0 16px 32px -12px rgba(20, 48, 31, 0.18)',
            },
        },
    },
    plugins: [forms, typography],
}
