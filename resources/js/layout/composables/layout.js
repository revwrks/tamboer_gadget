import { computed, reactive, readonly } from "vue";

function resolveInitialDark() {
    if (typeof localStorage !== "undefined") {
        const stored = localStorage.getItem("tamboer_dark_theme");
        if (stored === "true") return true;
        if (stored === "false") return false;
    }
    if (typeof window !== "undefined" && window.matchMedia) {
        return window.matchMedia("(prefers-color-scheme: dark)").matches;
    }
    return false;
}

const initialDark = resolveInitialDark();
if (initialDark && typeof document !== "undefined") {
    document.documentElement.classList.add("app-dark");
}

const layoutState = reactive({
    darkTheme: initialDark,
    desktopSidebarCollapsed: false,
    mobileSidebarOpen: false,
});

export function useLayout() {
    const applyDarkMode = (nextDark) => {
        layoutState.darkTheme = nextDark;
        if (typeof document !== "undefined") {
            document.documentElement.classList.toggle("app-dark", nextDark);
        }
        if (typeof localStorage !== "undefined") {
            localStorage.setItem("tamboer_dark_theme", nextDark ? "true" : "false");
        }
    };

    const toggleDarkMode = () => {
        const next = !layoutState.darkTheme;
        if (typeof document !== "undefined" && document.startViewTransition) {
            document.startViewTransition(() => applyDarkMode(next));
        } else {
            applyDarkMode(next);
        }
    };

    const toggleSidebar = () => {
        if (typeof window !== "undefined" && window.innerWidth >= 1024) {
            layoutState.desktopSidebarCollapsed = !layoutState.desktopSidebarCollapsed;
        } else {
            layoutState.mobileSidebarOpen = !layoutState.mobileSidebarOpen;
        }
    };

    const closeMobileSidebar = () => {
        layoutState.mobileSidebarOpen = false;
    };

    const isDarkTheme = computed(() => layoutState.darkTheme);
    const isDesktopCollapsed = computed(() => layoutState.desktopSidebarCollapsed);
    const isMobileSidebarOpen = computed(() => layoutState.mobileSidebarOpen);

    return {
        layoutState: readonly(layoutState),
        isDarkTheme,
        isDesktopCollapsed,
        isMobileSidebarOpen,
        toggleDarkMode,
        toggleSidebar,
        closeMobileSidebar,
    };
}
