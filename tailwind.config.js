import forms from '@tailwindcss/forms';

export default {
    content: ['./resources/**/*.blade.php', './resources/**/*.js'],
    theme: {
        extend: {
            colors: { ink: '#172033', muted: '#667085', brand: { 50: '#eef4ff', 100: '#dbe8ff', 500: '#4776e6', 600: '#3866d6', 700: '#2f55b8' } },
        },
    },
    plugins: [forms],
};
