/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './src/Views/**/*.php',
        './public/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
            },
            colors: {
                brand: {
                    50:  '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                },
            },
            boxShadow: {
                'card': '0 10px 30px -10px rgb(15 23 42 / 0.15), 0 1px 2px rgb(15 23 42 / 0.05)',
            },
        },
    },
    plugins: [],
};
