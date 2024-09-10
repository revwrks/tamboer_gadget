import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { fileURLToPath, URL } from "node:url";

import { PrimeVueResolver } from "@primevue/auto-import-resolver";
import vue from "@vitejs/plugin-vue";
import Components from "unplugin-vue-components/vite";

export default defineConfig({
    optimizeDeps: {
        noDiscovery: true,
    },
    build: {
        outDir: "dist",
        rollupOptions: {
            input: "resources/js/app.js",
        },
    },
    server: {
        host: "127.0.0.1", // or 'localhost'
        port: 5173, // Ensure this matches the port where Vite is running
    },
    plugins: [
        laravel({
            input: ["resources/js/app.js"],
            refresh: true,
        }),
        vue(),
        Components({
            resolvers: [PrimeVueResolver()],
        }),
    ],
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
        },
    },
});
