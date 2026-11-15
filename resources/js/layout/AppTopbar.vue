<script setup>
import { useLayout } from "@/layout/composables/layout";
import AppConfigurator from "./AppConfigurator.vue";
import { useRouter } from "vue-router";
import { useToast } from "primevue/usetoast";
import { useAuth } from "@/composables/useAuth";

const { onMenuToggle, toggleDarkMode, isDarkTheme } = useLayout();
const router = useRouter();
const toast = useToast();
const { logout: authLogout } = useAuth();

const logout = async () => {
    await authLogout();

    toast.add({
        severity: "success",
        summary: "Logout Berhasil",
        detail: "Sesi telah berakhir dengan aman.",
        life: 3000,
    });

    // Redirect to login page
    router.push("/");
};
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-logo-container">
            <button
                class="layout-menu-button layout-topbar-action"
                @click="onMenuToggle"
                aria-label="Toggle Menu"
            >
                <i class="pi pi-bars"></i>
            </button>
            <router-link to="/dashboard" class="layout-topbar-logo flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-stone-900 dark:bg-stone-800 flex items-center justify-center text-amber-400 shadow-sm border border-stone-800 dark:border-stone-700">
                    <i class="pi pi-bolt text-base"></i>
                </div>
                <div class="flex flex-col">
                    <span class="font-extrabold text-base tracking-tight leading-none text-stone-900 dark:text-stone-100">
                        Tamboer<span class="text-amber-600 dark:text-amber-400">Gadget</span>
                    </span>
                    <span class="text-[9px] font-semibold text-stone-400 dark:text-stone-500 uppercase tracking-widest mt-0.5">
                        Boutique & POS
                    </span>
                </div>
            </router-link>
        </div>

        <div class="layout-topbar-actions">
            <div class="layout-config-menu">
                <button
                    type="button"
                    class="layout-topbar-action"
                    @click="toggleDarkMode"
                    v-tooltip.bottom="isDarkTheme ? 'Mode Terang (Nordic Linen)' : 'Mode Gelap (Obsidian Stone)'"
                    aria-label="Toggle Dark Mode"
                >
                    <i
                        :class="[
                            'pi',
                            { 'pi-moon text-amber-400': isDarkTheme, 'pi-sun text-stone-700': !isDarkTheme },
                        ]"
                    ></i>
                </button>
                <div class="relative">
                    <button
                        v-styleclass="{
                            selector: '@next',
                            enterFromClass: 'hidden',
                            enterActiveClass: 'animate-scalein',
                            leaveToClass: 'hidden',
                            leaveActiveClass: 'animate-fadeout',
                            hideOnOutsideClick: true,
                        }"
                        type="button"
                        class="layout-topbar-action layout-topbar-action-highlight"
                        v-tooltip.bottom="'Pilihan Aksen & Palet'"
                        aria-label="Palette Configurator"
                    >
                        <i class="pi pi-palette"></i>
                    </button>
                    <AppConfigurator />
                </div>
            </div>

            <button
                class="layout-topbar-menu-button layout-topbar-action"
                v-styleclass="{
                    selector: '@next',
                    enterFromClass: 'hidden',
                    enterActiveClass: 'animate-scalein',
                    leaveToClass: 'hidden',
                    leaveActiveClass: 'animate-fadeout',
                    hideOnOutsideClick: true,
                }"
                aria-label="More Options"
            >
                <i class="pi pi-ellipsis-v"></i>
            </button>

            <div class="layout-topbar-menu hidden lg:block">
                <div class="layout-topbar-menu-content">
                    <button type="button" class="layout-topbar-action" @click="logout" v-tooltip.bottom="'Keluar Akun'">
                        <i class="pi pi-sign-out text-rose-500"></i>
                        <span>Logout</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
