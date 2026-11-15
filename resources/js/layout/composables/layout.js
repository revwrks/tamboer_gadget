import { computed, reactive, readonly } from 'vue';

const initialDark = typeof localStorage !== 'undefined' && localStorage.getItem('tamboer_dark_theme') === 'true';
if (initialDark && typeof document !== 'undefined') {
    document.documentElement.classList.add('app-dark');
}

const layoutConfig = reactive({
    preset: 'Aura',
    primary: (typeof localStorage !== 'undefined' && localStorage.getItem('tamboer_primary_color')) || 'amber',
    surface: (typeof localStorage !== 'undefined' && localStorage.getItem('tamboer_surface_color')) || 'stone',
    darkTheme: initialDark,
    menuMode: 'static'
});

const layoutState = reactive({
    staticMenuDesktopInactive: false,
    overlayMenuActive: false,
    profileSidebarVisible: false,
    configSidebarVisible: false,
    staticMenuMobileActive: false,
    menuHoverActive: false,
    activeMenuItem: null
});

export function useLayout() {
    const setPrimary = (value) => {
        layoutConfig.primary = value;
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem('tamboer_primary_color', value);
        }
    };

    const setSurface = (value) => {
        layoutConfig.surface = value;
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem('tamboer_surface_color', value);
        }
    };

    const setPreset = (value) => {
        layoutConfig.preset = value;
    };

    const setActiveMenuItem = (item) => {
        layoutState.activeMenuItem = item.value || item;
    };

    const setMenuMode = (mode) => {
        layoutConfig.menuMode = mode;
    };

    const toggleDarkMode = () => {
        if (!document.startViewTransition) {
            executeDarkModeToggle();
            return;
        }

        document.startViewTransition(() => executeDarkModeToggle());
    };

    const executeDarkModeToggle = () => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme;
        document.documentElement.classList.toggle('app-dark');
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem('tamboer_dark_theme', layoutConfig.darkTheme ? 'true' : 'false');
        }
    };

    const onMenuToggle = () => {
        if (layoutConfig.menuMode === 'overlay') {
            layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
        }

        if (window.innerWidth > 991) {
            layoutState.staticMenuDesktopInactive = !layoutState.staticMenuDesktopInactive;
        } else {
            layoutState.staticMenuMobileActive = !layoutState.staticMenuMobileActive;
        }
    };

    const resetMenu = () => {
        layoutState.overlayMenuActive = false;
        layoutState.staticMenuMobileActive = false;
        layoutState.menuHoverActive = false;
    };

    const isSidebarActive = computed(() => layoutState.overlayMenuActive || layoutState.staticMenuMobileActive);

    const isDarkTheme = computed(() => layoutConfig.darkTheme);

    const getPrimary = computed(() => layoutConfig.primary);

    const getSurface = computed(() => layoutConfig.surface);

    return {
        layoutConfig: readonly(layoutConfig),
        layoutState: readonly(layoutState),
        onMenuToggle,
        isSidebarActive,
        isDarkTheme,
        getPrimary,
        getSurface,
        setActiveMenuItem,
        toggleDarkMode,
        setPrimary,
        setSurface,
        setPreset,
        resetMenu,
        setMenuMode
    };
}
