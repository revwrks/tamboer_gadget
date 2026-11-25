import AppLayout from "@/layout/AppLayout.vue";
import { useAuth } from "@/composables/useAuth";
import { createRouter, createWebHistory } from "vue-router";

const basePath = window.location.pathname.includes("/public/")
    ? window.location.pathname.split("/public/")[0] + "/public/"
    : "/";

const router = createRouter({
    history: createWebHistory(basePath),
    routes: [
        {
            path: "/",
            name: "login",
            component: () => import("@/views/pages/auth/Login.vue"),
            meta: { guestOnly: true, title: "Masuk" },
        },
        {
            path: "/",
            component: AppLayout,
            meta: { requiresAuth: true },
            children: [
                {
                    path: "/dashboard",
                    name: "dashboard",
                    component: () => import("@/views/Dashboard.vue"),
                    meta: { requiresAuth: true, title: "Ringkasan", subtitle: "Ringkasan operasional dan performa toko" },
                },
                {
                    path: "/pages/penjualan",
                    name: "penjualan",
                    component: () => import("@/views/pages/Penjualan.vue"),
                    meta: { requiresAuth: true, title: "Kasir POS", subtitle: "Transaksi cepat dan riwayat penjualan" },
                },
                {
                    path: "/pages/stok",
                    name: "stok",
                    component: () => import("@/views/pages/Stok.vue"),
                    meta: { requiresAuth: true, title: "Inventaris", subtitle: "Katalog unit, IMEI, dan status etalase" },
                },
                {
                    path: "/pages/laporan",
                    name: "laporan",
                    component: () => import("@/views/pages/Laporan.vue"),
                    meta: { requiresAuth: true, role: "owner", title: "Laporan Keuangan", subtitle: "Rekapitulasi omset, HPP, dan laba bersih" },
                },
                {
                    path: "/pages/akun",
                    name: "akun",
                    component: () => import("@/views/pages/Akun.vue"),
                    meta: { requiresAuth: true, role: "owner", title: "Tim & Akses", subtitle: "Manajemen akun owner dan kasir" },
                },
            ],
        },
        {
            path: "/auth/access",
            name: "accessDenied",
            component: () => import("@/views/pages/auth/Access.vue"),
            meta: { title: "Akses Ditolak" },
        },
        {
            path: "/auth/error",
            name: "error",
            component: () => import("@/views/pages/auth/Error.vue"),
            meta: { title: "Terjadi Kesalahan" },
        },
        {
            path: "/pages/notfound",
            name: "notfound",
            component: () => import("@/views/pages/NotFound.vue"),
            meta: { title: "Halaman Tidak Ditemukan" },
        },
        {
            path: "/:pathMatch(.*)*",
            redirect: { name: "notfound" },
        },
    ],
});

router.beforeEach(async (to, from, next) => {
    const { token, user, verifySession, clearAuth } = useAuth();
    const requiresAuth = to.matched.some((record) => record.meta?.requiresAuth);
    const guestOnly = to.matched.some((record) => record.meta?.guestOnly);
    const requiredRole = to.meta?.role;

    if (requiresAuth && !token.value) {
        return next({ name: "login" });
    }

    if (guestOnly && token.value) {
        return next({ name: "dashboard" });
    }

    if (requiresAuth && token.value) {
        try {
            const verifiedUser = await verifySession();
            if (!verifiedUser) {
                clearAuth();
                return next({ name: "login" });
            }
            if (requiredRole && verifiedUser.level !== requiredRole) {
                return next({ name: "accessDenied" });
            }
        } catch {
            clearAuth();
            return next({ name: "login" });
        }
    } else if (requiresAuth && requiredRole && user.value?.level !== requiredRole) {
        return next({ name: "accessDenied" });
    }

    if (to.meta?.title) {
        document.title = `${to.meta.title} — Tamboer Gadget`;
    }

    next();
});

export default router;
