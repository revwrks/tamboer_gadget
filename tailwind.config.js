/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: ['selector', '[class*="app-dark"]'],
    content: ['./index.html', './resources/**/*.{vue,js,ts,jsx,tsx,blade.php}'],
    plugins: [require('tailwindcss-primeui')],
    theme: {
        screens: {
            sm: '576px',
            md: '768px',
            lg: '992px',
            xl: '1200px',
            '2xl': '1920px',
        },
        extend: {
            colors: {
                canvas: 'var(--bg-canvas)',
                surface: 'var(--bg-surface)',
                elevated: 'var(--bg-elevated)',
                subtle: 'var(--bg-subtle)',
                hover: 'var(--bg-hover)',
                ink: 'var(--text-primary)',
                muted: 'var(--text-secondary)',
                tertiary: 'var(--text-tertiary)',
                hairline: 'var(--border-subtle)',
                'hairline-strong': 'var(--border-strong)',
                accent: {
                    DEFAULT: 'var(--accent)',
                    hover: 'var(--accent-hover)',
                    soft: 'var(--accent-soft)',
                    ink: 'var(--accent-ink)',
                },
            },
        },
    },
};
