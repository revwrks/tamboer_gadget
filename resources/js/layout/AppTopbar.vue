<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useLayout } from "@/layout/composables/layout";

const emit = defineEmits(["register-trigger"]);

const route = useRoute();
const router = useRouter();
const { isDarkTheme, isDesktopCollapsed, isMobileSidebarOpen, toggleDarkMode, toggleSidebar } = useLayout();
const triggerButtonRef = ref(null);

const pageTitle = computed(() => route.meta?.title || "Tamboer Gadget");
const showQuickPos = computed(() => route.name !== "penjualan");

const isExpanded = computed(() => {
    if (typeof window !== "undefined" && window.innerWidth >= 1024) {
        return !isDesktopCollapsed.value;
    }
    return isMobileSidebarOpen.value;
});

onMounted(() => {
    emit("register-trigger", triggerButtonRef.value);
});
</script>

<template>
    <header
        class="sticky top-0 z-30 h-14 border-b border-hairline px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4"
        style="background: var(--glass-bar); backdrop-filter: blur(12px);"
    >
        <div class="flex items-center gap-3 min-w-0">
            <button
                ref="triggerButtonRef"
                type="button"
                class="w-9 h-9 rounded-lg border border-hairline bg-surface hover:bg-subtle text-muted hover:text-ink flex items-center justify-center transition-colors shrink-0"
                aria-label="Alihkan navigasi"
                :aria-expanded="isExpanded"
                @click="toggleSidebar"
            >
                <i class="pi pi-bars text-sm"></i>
            </button>

            <h2 class="text-sm sm:text-base font-semibold text-ink truncate">
                {{ pageTitle }}
            </h2>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button
                v-if="showQuickPos"
                type="button"
                class="min-h-[36px] px-3.5 py-1.5 rounded-lg bg-ink text-surface hover:opacity-90 text-xs font-semibold flex items-center gap-2 transition-opacity"
                @click="router.push({ name: 'penjualan' })"
            >
                <i class="pi pi-shopping-bag text-xs text-accent"></i>
                <span class="hidden sm:inline">Buka Kasir</span>
                <span class="sm:hidden">Kasir</span>
            </button>

            <button
                type="button"
                class="w-9 h-9 rounded-lg border border-hairline bg-surface hover:bg-subtle text-muted hover:text-ink flex items-center justify-center transition-colors"
                :aria-label="isDarkTheme ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
                v-tooltip.bottom="isDarkTheme ? 'Mode Terang' : 'Mode Gelap'"
                @click="toggleDarkMode"
            >
                <i :class="isDarkTheme ? 'pi pi-sun text-sm text-accent' : 'pi pi-moon text-sm'"></i>
            </button>
        </div>
    </header>
</template>
