/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    '../app/Views/**/*.php',
    '../public_html/js/**/*.js',
    '../app/Services/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        'navy-blue': '#0f172a',
        'neon-green': '#39FF14',
        'main-dark': '#0b1120',
        'error-red': '#ef4444',
        'cat-yellow': '#f59e0b',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
      },
    },
  },
  plugins: [],
};