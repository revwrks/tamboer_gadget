import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { fileURLToPath, URL } from "node:url";

import { PrimeVueResolver } from "@primevue/auto-import-resolver";
import vue from "@vitejs/plugin-vue";
import Components from "unplugin-vue-components/vite";
import { internalIpV4 } from "internal-ip";

const localIp = internalIpV4(); // Get local IP asynchronously

export default defineConfig({
    optimizeDeps: {
        noDiscovery: true,
    },
    build: {
        outDir: "dist",
        rollupOptions: {
            output: {
                // Ensure proper module system is used
                entryFileNames: `assets/[name].[hash].js`,
                chunkFileNames: `assets/[name].[hash].js`,
                assetFileNames: `assets/[name].[hash].[ext]`,
            },
        },
        manifest: true, // Generate manifest for proper asset loading in Laravel
    },
    server: {
        host: "192.168.1.8", // Ganti ke ip local di bagian 192.168.1.8, angka di belakang gausah
        port: 5173, // Ensure this matches the port where Vite is running
        changeOrigin: true,
        secure: false,
        disableHostCheck: true,
        strictPort: true, // Use the specified port without trying different ones
        watch: {
            usePolling: true, // Sometimes required for certain dev environments (WSL, Docker, etc.)
        },
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
