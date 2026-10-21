import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            // System fonts, the same as the Bootstrap pages
            fontFamily: {
                sans: [...defaultTheme.fontFamily.sans],
            },

            // Warm Ivory & Espresso (the same colors as public/css/theme.css), used by the login/register pages
            colors: {
                crema: {
                    ivory: '#F5F0E8',
                    espresso: '#725746',
                    'espresso-hover': '#5E4739',
                    roast: '#302720',
                    latte: '#D9CFC2',
                    'latte-tint': '#F0E9DF',
                    muted: '#6B6058',
                    rust: '#9A3B2E',
                },
            },
        },
    },

    plugins: [forms],
};
