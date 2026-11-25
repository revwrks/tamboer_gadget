import { ref, computed } from "vue";
import axios from "axios";

function safeParseUser(raw) {
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch {
        localStorage.removeItem("user");
        return null;
    }
}

const token = ref(localStorage.getItem("token") || "");
const user = ref(safeParseUser(localStorage.getItem("user")));
const sessionVerified = ref(false);
let verifyPromise = null;

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value);
    const isOwner = computed(() => user.value?.level === "owner");
    const isSales = computed(() => user.value?.level === "sales");

    const setAuth = (newToken, newUser) => {
        token.value = newToken;
        user.value = newUser;
        sessionVerified.value = true;
        localStorage.setItem("token", newToken);
        localStorage.setItem("user", JSON.stringify(newUser));
        if (window.axios) {
            window.axios.defaults.headers.common["Authorization"] = `Bearer ${newToken}`;
        }
    };

    const clearAuth = () => {
        token.value = "";
        user.value = null;
        sessionVerified.value = false;
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
        } catch {
            // Ignore API errors on logout and always clear local session
        } finally {
            clearAuth();
        }
    };

    const fetchMe = async () => {
        if (!token.value) return null;
        try {
            const response = await axios.get("me");
            user.value = response.data;
            sessionVerified.value = true;
            localStorage.setItem("user", JSON.stringify(response.data));
            return response.data;
        } catch (error) {
            if (error.response?.status === 401) {
                clearAuth();
            }
            throw error;
        }
    };

    const verifySession = async () => {
        if (!token.value) {
            sessionVerified.value = false;
            return null;
        }
        if (sessionVerified.value && user.value) {
            return user.value;
        }
        if (!verifyPromise) {
            verifyPromise = fetchMe().finally(() => {
                verifyPromise = null;
            });
        }
        return verifyPromise;
    };

    return {
        token,
        user,
        sessionVerified,
        isAuthenticated,
        isOwner,
        isSales,
        setAuth,
        clearAuth,
        logout,
        fetchMe,
        verifySession,
    };
}
