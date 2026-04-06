/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      screens: {
        'xs': '475px',
        'sm': '640px',
        'md': '768px',
        'lg': '1024px',
        'xl': '1280px',
        '2xl': '1536px',
      },
      colors: {
        orange: {
          50: '#FFF0EB',
          100: '#FFD9CC',
          400: '#FF8A5C',
          500: '#FF6B35',
          600: '#E55A2B',
          700: '#CC4A22',
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
      maxWidth: {
        'app': '1024px',
        'app-lg': '1280px',
      },
    },
  },
  plugins: [],
}