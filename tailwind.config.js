/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./public/assets/js/**/*.js"
  ],
  safelist: [
    // Hero filter chip per-category colors (dynamically assembled in index.php)
    'bg-emerald-500/20', 'border-emerald-400/40', 'hover:bg-emerald-500/30', 'hover:border-emerald-400/60', 'text-emerald-300/70',
    'bg-teal-500/20', 'border-teal-400/40', 'hover:bg-teal-500/30', 'hover:border-teal-400/60', 'text-teal-300/70',
    'bg-amber-500/20', 'border-amber-400/40', 'hover:bg-amber-500/30', 'hover:border-amber-400/60', 'text-amber-300/70',
    'bg-rose-500/20', 'border-rose-400/40', 'hover:bg-rose-500/30', 'hover:border-rose-400/60', 'text-rose-300/70',
  ],
  darkMode: ['selector', '[data-theme="dark"]'],
  theme: {
    extend: {
      fontFamily: {
        'sans': ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
        'serif': ['Playfair Display', 'Georgia', 'serif'],
      },
      colors: {
        // Dark Glassmorphism brand colors
        'brand': {
          'dark': '#1a2c32',
          'darker': '#132024',
          'yellow': '#fde047',
          'text': '#f1f5f9',
          'muted': '#94a3b8',
        },
        // Legacy tropical palette (for gradual migration)
        'tropical': {
          'cyan': {
            50: '#ecfeff',
            100: '#cffafe',
            200: '#a5f3fc',
            300: '#67e8f9',
            400: '#22d3ee',
            500: '#06b6d4',
            600: '#0891b2',
            700: '#0e7490',
          },
          'sun': {
            50: '#fffbeb',
            100: '#fef3c7',
            200: '#fde68a',
            300: '#fcd34d',
            400: '#fbbf24',
            500: '#f59e0b',
            600: '#d97706',
            700: '#b45309',
          },
          'coral': {
            50: '#fff7ed',
            100: '#ffedd5',
            200: '#fed7aa',
            300: '#fdba74',
            400: '#fb923c',
            500: '#f97316',
            600: '#ea580c',
          },
          'sand': {
            50: '#FFFDF7',
            100: '#FFFBEB',
            200: '#FEF3C7',
            300: '#FDE68A',
          },
        },
        'warm': {
          50: '#fafaf9',
          100: '#f5f5f4',
          200: '#e7e5e4',
          300: '#d6d3d1',
          400: '#a8a29e',
          500: '#78716c',
          600: '#57534e',
          700: '#44403c',
          800: '#292524',
          900: '#1c1917',
        },
      },
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
        '3xl': '2rem',
      },
      boxShadow: {
        'card': '0 4px 12px rgba(0, 0, 0, 0.08)',
        'card-hover': '0 8px 24px rgba(0, 0, 0, 0.12)',
        'soft': '0 2px 8px rgba(0, 0, 0, 0.06)',
        'soft-lg': '0 4px 16px rgba(0, 0, 0, 0.08)',
        'sunny': '0 4px 16px rgba(251, 191, 36, 0.2)',
        'glass': '0 8px 32px rgba(0, 0, 0, 0.3)',
      },
      backgroundImage: {
        'hero-gradient': 'linear-gradient(to bottom, rgba(0,0,0,0), rgba(0,0,0,0.3), rgba(0,0,0,0.8))',
      },
      animation: {
        'fade-in-up': 'fade-in-up 0.6s ease-out forwards',
        'bounce-slow': 'bounce 2s infinite',
      },
      keyframes: {
        'fade-in-up': {
          '0%': { opacity: '0', transform: 'translateY(20px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
      },
      // Animation delay utilities (use as delay-100, delay-200, etc.)
      animationDelay: {
        '100': '100ms',
        '200': '200ms',
        '300': '300ms',
        '400': '400ms',
        '500': '500ms',
        '600': '600ms',
      },
    },
  },
  plugins: [
    // Animation delay plugin
    function({ matchUtilities, theme }) {
      matchUtilities(
        {
          'delay': (value) => ({
            animationDelay: value,
          }),
        },
        { values: theme('animationDelay') }
      )
    },
  ],
}
