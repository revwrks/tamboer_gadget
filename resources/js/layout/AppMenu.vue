<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

import AppMenuItem from './AppMenuItem.vue';
const user = ref({}); // Will hold the fetched user data
const model = ref([]); // Will hold the menu items based on the user's role

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

// Function to fetch user data from the API
const fetchUserData = async () => {
  try {
    // Assuming the user ID (token) is stored in localStorage
    const id_user = localStorage.getItem("token");

    // Fetch user data based on their ID
    const response = await axios.get(`/api/users/${id_user}`);

    // Set the user data
    user.value = response.data;

    // Set the menu items based on the user's role (level)
    if (user.value.level === "owner") {
      model.value = ownermodel;
    } else if (user.value.level === "sales") {
      model.value = salesmodel;
    }
  } catch (error) {
    console.error("Error fetching user data:", error);
  }
};

// Fetch user data when the component is mounted
onMounted(fetchUserData);
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
