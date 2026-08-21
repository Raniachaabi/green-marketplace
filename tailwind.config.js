import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'

/**
 * The design tokens in app.css store each colour as space-separated RGB
 * channels (e.g. `182 198 63`), not a hex string — that is what lets
 * Tailwind generate opacity variants (bg-primary/10, dark:bg-primary/15…).
 * A plain `var(--color-x)` hex reference cannot take an alpha modifier at
 * all: Tailwind silently drops those utilities instead of erroring, which
 * is why every /NN opacity variant on a token colour used to render fully
 * opaque or not at all.
 */
function withOpacity(variable) {
    return ({ opacityValue }) => opacityValue === undefined
        ? `rgb(var(${variable}))`
        : `rgb(var(${variable}) / ${opacityValue})`
}

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
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
                primary: {
                    DEFAULT: withOpacity('--color-primary'),
                    foreground: withOpacity('--color-primary-foreground'),
                },
                secondary: {
                    DEFAULT: withOpacity('--color-secondary'),
                    foreground: withOpacity('--color-secondary-foreground'),
                },
                background: withOpacity('--color-background'),
                foreground: withOpacity('--color-foreground'),
                card: {
                    DEFAULT: withOpacity('--color-card'),
                    foreground: withOpacity('--color-card-foreground'),
                },
                popover: {
                    DEFAULT: withOpacity('--color-popover'),
                    foreground: withOpacity('--color-popover-foreground'),
                },
                muted: {
                    DEFAULT: withOpacity('--color-muted'),
                    foreground: withOpacity('--color-muted-foreground'),
                },
                accent: {
                    DEFAULT: withOpacity('--color-accent'),
                    foreground: withOpacity('--color-accent-foreground'),
                },
                border: withOpacity('--color-border'),
                input: withOpacity('--color-input'),
                ring: withOpacity('--color-ring'),
                destructive: {
                    DEFAULT: withOpacity('--color-destructive'),
                    foreground: withOpacity('--color-destructive-foreground'),
                },
            },
            borderRadius: {
                DEFAULT: 'var(--radius)',
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
