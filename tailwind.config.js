/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
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
      }
    },
  },
  plugins: [],
}