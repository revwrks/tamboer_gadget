<script setup>
import { computed } from "vue";
import { useAuth } from "@/composables/useAuth";
import AppMenuItem from './AppMenuItem.vue';

const { isOwner } = useAuth();

const ownermodel = [
    {
        label: 'Home',
        items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/dashboard' }]
    },
    {
        label: 'Pembukuan',
        items: [
            {
                label: 'Stok',
                icon: 'pi pi-fw pi-mobile',
                to: '/pages/stok'
            },
            {
                label: 'Penjualan',
                icon: 'pi pi-fw pi-shopping-bag',
                to: '/pages/penjualan'
            },
            {
                label: 'Akun',
                icon: 'pi pi-fw pi-user',
                to: '/pages/akun'
            },
            {
                label: 'Laporan',
                icon: 'pi pi-fw pi-book',
                to: '/pages/laporan'
            }
        ]
    }
];

const salesmodel = [
    {
        label: 'Home',
        items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/dashboard' }]
    },
    {
        label: 'Pembukuan',
        items: [
            {
                label: 'Stok',
                icon: 'pi pi-fw pi-mobile',
                to: '/pages/stok'
            },
            {
                label: 'Penjualan',
                icon: 'pi pi-fw pi-shopping-bag',
                to: '/pages/penjualan'
            }
        ]
    }
];

const model = computed(() => {
    return isOwner.value ? ownermodel : salesmodel;
});
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>

