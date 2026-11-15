import "./bootstrap";
import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";

import { definePreset } from "@primevue/themes";
import Aura from "@primevue/themes/aura";
import PrimeVue from "primevue/config";
import ConfirmationService from "primevue/confirmationservice";
import ToastService from "primevue/toastservice";

import "@/assets/styles.scss";
import "@/assets/tailwind.css";

// Nordic Warm Cashmere Preset (Amber Primary + Stone Surface)
const NordicAura = definePreset(Aura, {
    semantic: {
        primary: {
            50: "#fffbeb",
            100: "#fef3c7",
            200: "#fde68a",
            300: "#fcd34d",
            400: "#fbbf24",
            500: "#f59e0b",
            600: "#d97706",
            700: "#b45309",
            800: "#92400e",
            900: "#78350f",
            950: "#451a03"
        },
        colorScheme: {
            light: {
                surface: {
                    0: "#ffffff",
                    50: "#fafaf9",
                    100: "#f5f5f4",
                    200: "#e7e5e4",
                    300: "#d6d3d1",
                    400: "#a8a29e",
                    500: "#78716c",
                    600: "#57534e",
                    700: "#44403c",
                    800: "#292524",
                    900: "#1c1917",
                    950: "#0c0a09"
                }
            },
            dark: {
                surface: {
                    0: "#ffffff",
                    50: "#fafaf9",
                    100: "#f5f5f4",
                    200: "#e7e5e4",
                    300: "#d6d3d1",
                    400: "#a8a29e",
                    500: "#78716c",
                    600: "#57534e",
                    700: "#44403c",
                    800: "#292524",
                    900: "#1c1917",
                    950: "#0c0a09"
                }
            }
        }
    }
});

const app = createApp(App);

app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: NordicAura,
        options: {
            darkModeSelector: ".app-dark",
        },
    },
});
app.use(ToastService);
app.use(ConfirmationService);

app.mount("#app");
