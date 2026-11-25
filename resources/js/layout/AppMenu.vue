<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";
import { useAuth } from "@/composables/useAuth";

const route = useRoute();
const { isOwner } = useAuth();

const navGroups = computed(() => {
    const groups = [
        {
            label: "Operasional",
            items: [
                { label: "Ringkasan", icon: "pi pi-home", to: "/dashboard" },
                { label: "Kasir POS", icon: "pi pi-shopping-bag", to: "/pages/penjualan" },
                { label: "Inventaris", icon: "pi pi-box", to: "/pages/stok" },
            ],
        },
    ];

    if (isOwner.value) {
        groups.push({
            label: "Manajemen",
            items: [
                { label: "Laporan Keuangan", icon: "pi pi-chart-bar", to: "/pages/laporan" },
                { label: "Tim & Akses", icon: "pi pi-users", to: "/pages/akun" },
            ],
        });
    }

    return groups;
});

const isActive = (to) => route.path === to;
</script>

<template>
    <div class="space-y-5">
        <div v-for="group in navGroups" :key="group.label" class="space-y-1">
            <div class="px-2.5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-tertiary">
                {{ group.label }}
            </div>
            <ul class="space-y-0.5">
                <li v-for="item in group.items" :key="item.to">
                    <router-link
                        :to="item.to"
                        :aria-current="isActive(item.to) ? 'page' : undefined"
                        class="relative min-h-[44px] lg:min-h-[38px] px-3 py-2 rounded-xl flex items-center gap-3 text-sm transition-colors"
                        :class="
                            isActive(item.to)
                                ? 'bg-subtle text-ink font-semibold'
                                : 'text-muted hover:text-ink hover:bg-subtle/60 font-medium'
                        "
                    >
                        <span
                            v-if="isActive(item.to)"
                            class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-accent"
                            aria-hidden="true"
                        ></span>
                        <i
                            :class="[item.icon, 'text-sm', isActive(item.to) ? 'text-accent' : 'text-tertiary']"
                            aria-hidden="true"
                        ></i>
                        <span>{{ item.label }}</span>
                    </router-link>
                </li>
            </ul>
        </div>
    </div>
</template>
