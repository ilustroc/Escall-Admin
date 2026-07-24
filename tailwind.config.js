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
                escall: {
                    50: '#EEF4FF',
                    100: '#D9E6FF',
                    500: '#155EEF',
                    600: '#073DC7',
                    700: '#0735A8',
                    950: '#07142B',
                },
            },
            boxShadow: {
                card: '0 1px 2px rgba(7, 20, 43, 0.04), 0 8px 24px rgba(7, 20, 43, 0.06)',
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
