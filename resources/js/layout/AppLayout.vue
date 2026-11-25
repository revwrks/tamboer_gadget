<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { useLayout } from "@/layout/composables/layout";
import AppFooter from "./AppFooter.vue";
import AppSidebar from "./AppSidebar.vue";
import AppTopbar from "./AppTopbar.vue";

const route = useRoute();
const { isDesktopCollapsed, isMobileSidebarOpen, closeMobileSidebar } = useLayout();
const menuTriggerRef = ref(null);

const handleKeyDown = (event) => {
    if (event.key === "Escape" && isMobileSidebarOpen.value) {
        closeMobileSidebar();
        menuTriggerRef.value?.focus?.();
    }
};

watch(
    () => route.fullPath,
    () => {
        if (isMobileSidebarOpen.value) {
            closeMobileSidebar();
        }
    }
);

onMounted(() => {
    window.addEventListener("keydown", handleKeyDown);
});

onBeforeUnmount(() => {
    window.removeEventListener("keydown", handleKeyDown);
});
</script>

<template>
    <div class="min-h-screen bg-canvas text-ink flex">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[1100] focus:px-3.5 focus:py-2 focus:rounded-lg focus:bg-surface focus:text-ink focus:border focus:border-hairline focus:shadow-md text-xs font-semibold"
        >
            Lewati ke konten utama
        </a>

        <!-- Mobile Backdrop -->
        <div
            v-if="isMobileSidebarOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden transition-opacity"
            aria-hidden="true"
            @click="closeMobileSidebar"
        ></div>

        <!-- Sidebar -->
        <AppSidebar />

        <!-- Main Column -->
        <div
            class="flex-1 min-w-0 flex flex-col min-h-screen transition-[padding] duration-200"
            :class="isDesktopCollapsed ? 'lg:pl-0' : 'lg:pl-[248px]'"
        >
            <AppTopbar @register-trigger="(el) => (menuTriggerRef = el)" />

            <main id="main-content" class="flex-1 w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <router-view />
            </main>

            <AppFooter />
        </div>
    </div>
    <Toast />
</template>
