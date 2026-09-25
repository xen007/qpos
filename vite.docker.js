import { defineConfig } from 'vite';
import react from "@vitejs/plugin-react";
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    server: {
        hmr: {
            host: "0.0.0.0",
        },
        port: 3000,
        host: true,
    },
    plugins: [
        laravel({
            input: ["resources/js/app.jsx", "resources/js/dashboard.js"],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
});
