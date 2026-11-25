<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useToast } from "primevue/usetoast";
import { useAuth } from "@/composables/useAuth";
import { useLayout } from "@/layout/composables/layout";
import BrandMark from "@/components/BrandMark.vue";
import AppMenu from "./AppMenu.vue";

const router = useRouter();
const toast = useToast();
const { user, isOwner, logout: authLogout } = useAuth();
const {
    isDesktopCollapsed,
    isMobileSidebarOpen,
    isDarkTheme,
    toggleDarkMode,
    closeMobileSidebar,
} = useLayout();

const userInitial = computed(() => {
    const name = user.value?.nama || user.value?.username || "U";
    return name.charAt(0).toUpperCase();
});

const roleLabel = computed(() => (isOwner.value ? "Owner" : "Kasir / Sales"));

const handleLogout = async () => {
    await authLogout();
    toast.add({
        severity: "success",
        summary: "Keluar Berhasil",
        detail: "Sesi Anda telah berakhir dengan aman.",
        life: 2500,
    });
    router.push({ name: "login" });
};
</script>

<template>
    <aside
        aria-label="Navigasi Utama"
        class="fixed inset-y-0 left-0 z-50 w-[248px] bg-surface border-r border-hairline flex flex-col transition-transform duration-200 ease-out"
        :class="[
            isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full',
            isDesktopCollapsed ? 'lg:-translate-x-full' : 'lg:translate-x-0',
        ]"
    >
        <!-- Brand Header -->
        <div class="h-14 px-4 flex items-center justify-between border-b border-hairline shrink-0">
            <router-link
                to="/dashboard"
                class="flex items-center gap-2.5 text-ink hover:opacity-90 transition-opacity"
            >
                <div class="w-8 h-8 rounded-lg bg-subtle border border-hairline flex items-center justify-center text-ink">
                    <BrandMark :size="18" />
                </div>
                <div class="leading-tight">
                    <span class="font-bold text-sm tracking-tight text-ink block">
                        Tamboer Gadget
                    </span>
                    <span class="text-[11px] font-medium text-tertiary block">
                        POS &amp; Inventaris
                    </span>
                </div>
            </router-link>

            <button
                type="button"
                class="lg:hidden w-9 h-9 rounded-lg flex items-center justify-center text-muted hover:text-ink hover:bg-subtle transition-colors"
                aria-label="Tutup navigasi"
                @click="closeMobileSidebar"
            >
                <i class="pi pi-times text-sm"></i>
            </button>
        </div>

        <!-- Navigation Groups -->
        <nav class="flex-1 overflow-y-auto studio-scrollbar px-3 py-4">
            <AppMenu />
        </nav>

        <!-- Sidebar Footer: User & Session Controls -->
        <div class="p-3 border-t border-hairline space-y-2 shrink-0">
            <div class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl bg-canvas border border-hairline">
                <div class="w-8 h-8 rounded-lg bg-subtle border border-hairline flex items-center justify-center text-xs font-bold text-ink shrink-0">
                    {{ userInitial }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold text-ink truncate">
                        {{ user?.nama || "Pengguna" }}
                    </div>
                    <div class="text-[11px] text-tertiary truncate">
                        {{ roleLabel }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-1.5">
                <button
                    type="button"
                    class="min-h-[36px] px-2.5 py-1.5 rounded-lg text-xs font-medium text-muted hover:text-ink hover:bg-subtle border border-hairline flex items-center justify-center gap-1.5 transition-colors"
                    @click="toggleDarkMode"
                >
                    <i :class="isDarkTheme ? 'pi pi-sun text-xs' : 'pi pi-moon text-xs'"></i>
                    <span>{{ isDarkTheme ? "Terang" : "Gelap" }}</span>
                </button>

                <button
                    type="button"
                    class="min-h-[36px] px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-500/10 border border-hairline flex items-center justify-center gap-1.5 transition-colors"
                    @click="handleLogout"
                >
                    <i class="pi pi-sign-out text-xs"></i>
                    <span>Keluar</span>
                </button>
            </div>
        </div>
    </aside>
</template>
