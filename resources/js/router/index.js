import AppLayout from '@/layout/AppLayout.vue';
import { createRouter, createWebHistory } from 'vue-router';

const basePath = window.location.pathname.includes('/public/') 
    ? window.location.pathname.split('/public/')[0] + '/public/' 
    : '/';

const router = createRouter({
    history: createWebHistory(basePath),
    routes: [
        {
            path: '/',
            name: 'login',
            component: () => import('@/views/pages/auth/Login.vue'),
            meta: { guestOnly: true }
        },
        {
            path: '/',
            component: AppLayout,
            meta: { requiresAuth: true },
            children: [
                {
                    path: '/dashboard',
                    name: 'dashboard',
                    component: () => import('@/views/Dashboard.vue'),
                    meta: { requiresAuth: true }
                },
                {
                    path: '/pages/stok',
                    name: 'stok',
                    component: () => import('@/views/pages/Stok.vue'),
                    meta: { requiresAuth: true }
                },
                {
                    path: '/pages/penjualan',
                    name: 'penjualan',
                    component: () => import('@/views/pages/Penjualan.vue'),
                    meta: { requiresAuth: true }
                },
                {
                    path: '/pages/laporan',
                    name: 'laporan',
                    component: () => import('@/views/pages/Laporan.vue'),
                    meta: { requiresAuth: true, role: 'owner' }
                },
                {
                    path: '/pages/akun',
                    name: 'akun',
                    component: () => import('@/views/pages/Akun.vue'),
                    meta: { requiresAuth: true, role: 'owner' }
                },
                // Demo / UIKit routes (protected for development)
                {
                    path: '/pages/empty',
                    name: 'empty',
                    component: () => import('@/views/pages/Empty.vue'),
                    meta: { requiresAuth: true }
                },
                {
                    path: '/pages/crud',
                    name: 'crud',
                    component: () => import('@/views/pages/Crud.vue'),
                    meta: { requiresAuth: true }
                },
                {
                    path: '/documentation',
                    name: 'documentation',
                    component: () => import('@/views/pages/Documentation.vue'),
                    meta: { requiresAuth: true }
                }
            ]
        },
        {
            path: '/landing',
            name: 'landing',
            component: () => import('@/views/pages/Landing.vue')
        },
        {
            path: '/pages/notfound',
            name: 'notfound',
            component: () => import('@/views/pages/NotFound.vue')
        },
        {
            path: '/auth/access',
            name: 'accessDenied',
            component: () => import('@/views/pages/auth/Access.vue')
        },
        {
            path: '/auth/error',
            name: 'error',
            component: () => import('@/views/pages/auth/Error.vue')
        }
    ]
});

// Navigation Guard
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');
    const userRaw = localStorage.getItem('user');
    let user = null;
    try {
        user = userRaw ? JSON.parse(userRaw) : null;
    } catch (e) {
        user = null;
    }

    const requiresAuth = to.matched.some(record => record.meta?.requiresAuth);
    const guestOnly = to.matched.some(record => record.meta?.guestOnly);
    const requiredRole = to.meta?.role;

    // 1. Belum login mencoba akses halaman berproteksi
    if (requiresAuth && !token) {
        return next({ name: 'login' });
    }

    // 2. Sudah login mencoba buka halaman login (guest only)
    if (guestOnly && token) {
        return next({ name: 'dashboard' });
    }

    // 3. Hak akses role (misal: halaman akun/laporan butuh level 'owner')
    if (requiresAuth && requiredRole && user?.level !== requiredRole) {
        return next({ name: 'accessDenied' });
    }

    next();
});

export default router;
