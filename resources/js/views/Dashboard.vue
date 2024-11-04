<script setup>
import { useLayout } from "@/layout/composables/layout";
import { onMounted, ref, watch, computed } from "vue";
import axios from "axios";

const { getPrimary, getSurface, isDarkTheme } = useLayout();

const products = ref(null);
const chartData = ref(null);
const chartOptions = ref(null);

// State for report data of multiple months
const totalPhonesSold = ref(0);
const totalProfit = ref(0);
const totalPhonesSoldEver = ref(0);
const totalProfitEver = ref(0);

// Fetch report data for the current month
const fetchReportData = async () => {
    try {
        const currentDate = new Date();
        const response = await axios.get("/api/report", {
            params: {
                month: currentDate.getMonth() + 1,
                year: currentDate.getFullYear(),
            },
        });

        // Update the dashboard with report data
        totalPhonesSold.value = response.data.phones_sold;
        totalProfit.value = response.data.profit;
        totalPhonesSoldEver.value = response.data.total_phones_sold_ever;
        totalProfitEver.value = response.data.total_profit_ever; // Update total phones sold ever
    } catch (error) {
        console.error("Error fetching report data:", error);
    }
};

onMounted(() => {
    fetchReportData();
    fetchUserData(); // Fetch data on component mount
});

const user = ref({});
const fetchUserData = async () => {
    try {
        // Assuming the user ID (token) is stored in localStorage
        const id_user = localStorage.getItem("token");

        // Fetch user data based on their ID
        const response = await axios.get(`/api/users/${id_user}`);

        // Set the user data
        user.value = response.data;
    } catch (error) {
        console.error("Error fetching user data:", error);
    }
};

// Only show "harga jual" and edit buttons if the user is an owner
const isOwner = computed(() => user.value.level === "owner");

const formatCurrency = (value) => {
    return value.toLocaleString("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    });
};
</script>

<template>
    <div class="grid grid-cols-12 gap-8">
        <!-- Welcome Message -->
        <div class="col-span-12">
            <div class="card p-4 mb-6">
                <!-- Only display welcome message if user data is loaded -->
                <template v-if="user && user.nama">
                    <h2 class="text-xl font-bold">Selamat Datang, {{ user.nama }}!</h2>
                    <p class="text-muted">
                        Kamu login sebagai 
                        <strong>{{ user.level }}</strong
                        >.
                    </p>
                </template>
                <!-- Fallback if user data is not yet loaded -->
                <template v-else>
                    <p>Loading user information...</p>
                </template>
            </div>
        </div>

        <!-- Updated Penjualan Global Card -->
        <div class="col-span-12 lg:col-span-6 xl:col-span-3">
            <div class="card mb-0">
                <div class="flex justify-between mb-4">
                    <div>
                        <span class="block text-muted-color font-medium mb-4"
                            >Penjualan Global</span
                        >
                        <div
                            class="text-surface-900 dark:text-surface-0 font-medium text-xl"
                        >
                            {{ totalPhonesSoldEver }}
                        </div>
                    </div>
                    <div
                        class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border"
                        style="width: 2.5rem; height: 2.5rem"
                    >
                        <i
                            class="pi pi-shopping-cart text-blue-500 !text-xl"
                        ></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Updated Keuntungan Card -->
        <template v-if="isOwner">
            <div class="col-span-12 lg:col-span-6 xl:col-span-3">
                <div class="card mb-0">
                    <div class="flex justify-between mb-4">
                        <div>
                            <span
                                class="block text-muted-color font-medium mb-4"
                                >Keuntungan</span
                            >
                            <div
                                class="text-surface-900 dark:text-surface-0 font-medium text-xl"
                            >
                                {{ formatCurrency(totalProfitEver) }}
                            </div>
                        </div>
                        <div
                            class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-border"
                            style="width: 2.5rem; height: 2.5rem"
                        >
                            <i
                                class="pi pi-dollar text-orange-500 !text-xl"
                            ></i>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
