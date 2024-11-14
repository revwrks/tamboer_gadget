<script setup>
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dropdown from "primevue/dropdown";

const reportData = ref(null);
const availableDates = ref([]); // Ensure availableDates is initialized as an array
const salesDetails = ref([]);
const selectedMonth = ref(null);
const selectedYear = ref(null);
const loading = ref(false);
const toast = useToast();

// Format currency for IDR
const formatCurrency = (value) => {
    if (value != null) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(value);
    }
    return "-";
};

// Fetch available dates (months and years) from the server
const fetchAvailableDates = async () => {
    try {
        const response = await axios.get("/api/available-dates");
        availableDates.value = Array.isArray(response.data)
            ? response.data
            : []; // Ensure it's an array
        console.log("Fetched Available Dates:", response.data);
    } catch (error) {
        console.error("Error fetching available dates:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch available dates",
            life: 3000,
        });
    }
};

// Fetch report data based on selected month and year
const fetchReportData = async () => {
    if (!selectedMonth.value || !selectedYear.value) return;

    loading.value = true;
    try {
        const response = await axios.get("/api/report", {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value,
            },
        });
        reportData.value = response.data;
    } catch (error) {
        console.error("Error fetching report data:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch report data",
            life: 3000,
        });
    } finally {
        loading.value = false;
    }
};
const fetchSalesDetails = async () => {
    if (!selectedMonth.value || !selectedYear.value) return;

    try {
        const response = await axios.get("/api/sales-details", {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value,
            },
        });
        salesDetails.value = response.data;
    } catch (error) {
        console.error("Error fetching sales details:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch sales details",
            life: 3000,
        });
    }
};

const monthOptions = computed(() =>
    availableDates.value.map((date) => ({
        label: date.month_name,
        value: date.month,
    })),
);

const yearOptions = computed(() =>
    availableDates.value
        .map((date) => ({ label: date.year.toString(), value: date.year }))
        .filter((v, i, a) => a.findIndex((t) => t.value === v.value) === i),
);

// Watch for month or year change and refetch report data and sales details
watch([selectedMonth, selectedYear], () => {
    fetchReportData();
    fetchSalesDetails();
});

// Initial fetch for available dates
onMounted(() => {
    fetchAvailableDates();
});
</script>

<template>
    <div class="card">
        <h2 class="text-xl font-bold mb-4">Monthly Sales Report</h2>

        <!-- Filters for Month and Year -->
        <div class="flex gap-4 mb-4">
            <Dropdown
                v-model="selectedMonth"
                :options="monthOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select Month"
                class="w-full"
            />
            <Dropdown
                v-model="selectedYear"
                :options="yearOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select Year"
                class="w-full"
            />
        </div>

        <div v-if="loading" class="p-4 text-center">
            <i class="pi pi-spin pi-spinner text-3xl text-primary"></i>
            <p>Loading report...</p>
        </div>

        <DataTable v-if="reportData" :value="[reportData]" dataKey="month">
            <Column field="phones_sold" header="Unit Terjual" :sortable="true">
                <template #body="slotProps">
                    {{ slotProps.data.phones_sold }}
                </template>
            </Column>
            <Column field="total_income" header="Total Pemasukan" :sortable="true">
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.total_income) }}
                </template>
            </Column>
            <Column
                field="total_expenses"
                header="Total Pengeluaran"
                :sortable="true"
            >
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.total_expenses) }}
                </template>
            </Column>
            <Column field="profit" header="Laba" :sortable="true">
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.profit) }}
                </template>
            </Column>
        </DataTable>

        <!-- Detailed Sales Data Table -->
        <h3
            v-if="reportData"
            :value="[reportData]"
            dataKey="month"
            class="text-lg font-bold mt-6 mb-4"
        >
            Detailed Sales for {{ selectedMonth }} {{ selectedYear }}
        </h3>
        <div
            v-if="salesDetails.length"
            :value="salesDetails"
            dataKey="id"
            class="card"
        >
            <DataTable
                ref="dt"
                :value="salesDetails"
                dataKey="id"
                :paginator="true"
                :rows="10"
                scrollable
                scrollHeight="400px"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} penjualan"
                sortField="tanggal"
                :sortOrder="-1"
            >
                <!-- Tanggal Terjual -->
                <Column field="tanggal" header="Tanggal Terjual" sortable>
                    <template #body="slotProps">
                        {{
                            new Date(slotProps.data.tanggal).toLocaleDateString(
                                "id-ID",
                            )
                        }}
                    </template>
                </Column>

                <!-- Sales (Penjual) -->
                <Column field="users.nama" header="Penjual" sortable>
                    <template #body="slotProps">
                        {{ slotProps.data.users?.nama || "Unknown" }}
                    </template>
                </Column>

                <!-- Stocks Details -->
                <Column field="stok_hp.nama" header="Tipe HP" sortable>
                    {{ slotProps.data.stok_hp.nama }}</Column
                >
                <Column field="stok_hp.warna" header="Warna" sortable>
                    {{ slotProps.data.stok_hp.warna }}</Column
                >
                <Column field="stok_hp.imei" header="IMEI" sortable>
                    {{ slotProps.data.stok_hp.imei }}</Column
                >

                <!-- Harga Jual -->
                <Column field="harga_jual" header="Harga Jual" sortable>
                    <template #body="slotProps">
                        {{ formatCurrency(slotProps.data.harga_jual) }}
                    </template>
                </Column>
            </DataTable>
        </div>

        <p v-else class="text-center text-gray-500">
            Select a month and year to view the report.
        </p>
    </div>
</template>

<style scoped>
.card {
    max-width: 800px;
    margin: 0 auto;
}
</style>
