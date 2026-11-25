import axios from "axios";

window.axios = axios;

const apiPath = window.location.pathname.includes("/public/")
    ? window.location.pathname.split("/public/")[0] + "/public/api"
    : "/api";

window.axios.defaults.baseURL = apiPath;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.headers.common["Accept"] = "application/json";

const savedToken = localStorage.getItem("token");
if (savedToken) {
    window.axios.defaults.headers.common["Authorization"] = `Bearer ${savedToken}`;
}

window.axios.interceptors.request.use(
    (config) => {
        const currentToken = localStorage.getItem("token");
        if (currentToken) {
            config.headers.Authorization = `Bearer ${currentToken}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const requestUrl = error.config?.url || "";
        const isLoginRequest = requestUrl.endsWith("login") || requestUrl.includes("/login");

        if (error.response && error.response.status === 401 && !isLoginRequest) {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            delete window.axios.defaults.headers.common["Authorization"];

            const currentPath = window.location.pathname;
            const loginPath = window.location.pathname.includes("/public/")
                ? window.location.pathname.split("/public/")[0] + "/public/"
                : "/";

            if (currentPath !== loginPath && !currentPath.endsWith("/public/") && currentPath !== "/") {
                window.location.assign(loginPath);
            }
        }
        return Promise.reject(error);
    }
);
