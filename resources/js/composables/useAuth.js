import { ref, computed } from "vue";
import axios from "axios";

// Shared reactive state across components
const token = ref(localStorage.getItem("token") || "");
const user = ref(JSON.parse(localStorage.getItem("user") || "null"));

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value);
    const isOwner = computed(() => user.value?.level === "owner");
    const isSales = computed(() => user.value?.level === "sales");

    const setAuth = (newToken, newUser) => {
        token.value = newToken;
        user.value = newUser;
        localStorage.setItem("token", newToken);
        localStorage.setItem("user", JSON.stringify(newUser));
        if (window.axios) {
            window.axios.defaults.headers.common["Authorization"] = `Bearer ${newToken}`;
        }
    };

    const clearAuth = () => {
        token.value = "";
        user.value = null;
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        if (window.axios) {
            delete window.axios.defaults.headers.common["Authorization"];
        }
    };

    const logout = async () => {
        try {
            if (token.value) {
                await axios.post("logout");
            }
        } catch (error) {
            // Ignore logout API failure and proceed with local cleanup
            console.warn("Logout API failed or session expired:", error);
        } finally {
            clearAuth();
        }
    };

    const fetchMe = async () => {
        if (!token.value) return null;
        try {
            const response = await axios.get("me");
            user.value = response.data;
            localStorage.setItem("user", JSON.stringify(response.data));
            return response.data;
        } catch (error) {
            if (error.response?.status === 401) {
                clearAuth();
            }
            throw error;
        }
    };

    return {
        token,
        user,
        isAuthenticated,
        isOwner,
        isSales,
        setAuth,
        clearAuth,
        logout,
        fetchMe,
    };
}
