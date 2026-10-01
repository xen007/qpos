import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/js/app.jsx",
                "resources/js/dashboard.js",
                "resources/js/theme.js",
                "resources/js/frontend.js",
                "resources/js/shell.js",
                "resources/js/table-actions.js",
                "resources/js/backend-toast.jsx",
                "resources/css/app.css",
            ],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
});
