import axios from 'axios';
window.axios = axios;

const apiPath = window.location.pathname.includes('/public/') 
    ? window.location.pathname.split('/public/')[0] + '/public/api' 
    : '/api';

window.axios.defaults.baseURL = apiPath;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

// Initialize Bearer token if exists
const savedToken = localStorage.getItem('token');
if (savedToken) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${savedToken}`;
}

// Request interceptor to ensure latest token is always sent
window.axios.interceptors.request.use((config) => {
    const currentToken = localStorage.getItem('token');
    if (currentToken) {
        config.headers.Authorization = `Bearer ${currentToken}`;
    }
    return config;
}, (error) => {
    return Promise.reject(error);
});

// Response interceptor to handle 401 Unauthorized globally
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            delete window.axios.defaults.headers.common['Authorization'];
            
            // Redirect to login if not already on login page
            const currentPath = window.location.pathname;
            const loginPath = window.location.pathname.includes('/public/')
                ? window.location.pathname.split('/public/')[0] + '/public/'
                : '/';
            
            if (currentPath !== loginPath && !currentPath.endsWith('/public/') && currentPath !== '/') {
                window.location.href = loginPath;
            }
        }
        return Promise.reject(error);
    }
);
