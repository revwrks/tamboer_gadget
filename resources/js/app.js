import "./bootstrap";
import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";

import { definePreset } from "@primevue/themes";
import Aura from "@primevue/themes/aura";
import PrimeVue from "primevue/config";
import ConfirmationService from "primevue/confirmationservice";
import ToastService from "primevue/toastservice";
import Tooltip from "primevue/tooltip";

import "@/assets/styles.scss";
import "@/assets/tailwind.css";

const StudioAura = definePreset(Aura, {
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
            950: "#451a03",
        },
        colorScheme: {
            light: {
                surface: {
                    0: "#ffffff",
                    50: "#fafafa",
                    100: "#f5f5f7",
                    200: "#e4e4e7",
                    300: "#d1d1d6",
                    400: "#a1a1aa",
                    500: "#6e6e77",
                    600: "#52525b",
                    700: "#3f3f46",
                    800: "#27272a",
                    900: "#111113",
                    950: "#09090b",
                },
            },
            dark: {
                surface: {
                    0: "#ffffff",
                    50: "#f5f5f7",
                    100: "#e4e4e7",
                    200: "#d1d1d6",
                    300: "#a1a1aa",
                    400: "#8e8e96",
                    500: "#71717a",
                    600: "#3a3a42",
                    700: "#27272c",
                    800: "#1f1f24",
                    900: "#161619",
                    950: "#0d0d0f",
                },
            },
        },
    },
});

const app = createApp(App);

app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: StudioAura,
        options: {
            darkModeSelector: ".app-dark",
        },
    },
});
app.use(ToastService);
app.use(ConfirmationService);
app.directive("tooltip", Tooltip);

app.mount("#app");
