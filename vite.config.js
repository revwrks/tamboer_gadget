import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import { fileURLToPath, URL } from "node:url";

import { PrimeVueResolver } from "@primevue/auto-import-resolver";
import vue from "@vitejs/plugin-vue";
import Components from "unplugin-vue-components/vite";

export default ({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");
    const rawAssetUrl = env.ASSET_URL || "";
    const assetUrl = rawAssetUrl === "null" || rawAssetUrl === "undefined" ? "" : rawAssetUrl;
    const base = assetUrl
        ? (assetUrl.endsWith("/") ? assetUrl : assetUrl + "/") + "build/"
        : "/build/";

    return defineConfig({
        base: mode === "production" ? base : "/",
        optimizeDeps: {
            noDiscovery: true,
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
};
