<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dropdown from "primevue/dropdown";

const reportData = ref(null);
const availableDates = ref([]); // Ensure availableDates is initialized as an array
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
        const response = await axios.get("/api/report");
        availableDates.value = Array.isArray(response.data) ? response.data : []; // Ensure it's an array
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

// Watch for month or year change and refetch report data
watch([selectedMonth, selectedYear], fetchReportData);

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
                :options="availableDates.value.map(date => ({ label: date.month_name, value: date.month }))"
                optionLabel="label"
                optionValue="value"
                placeholder="Select Month"
                class="w-full"
            />
            <Dropdown
                v-model="selectedYear"
                :options="availableDates.value.map(date => ({ label: date.year.toString(), value: date.year })).filter((v, i, a) => a.findIndex(t => t.value === v.value) === i)"
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

        <DataTable
            v-if="reportData"
            :value="[reportData]"
            dataKey="month"
            :paginator="false"
            :rows="5"
        >
            <Column field="phones_sold" header="Units Sold" :sortable="true">
                <template #body="slotProps">
                    {{ slotProps.data.phones_sold }}
                </template>
            </Column>
            
            <Column field="total_income" header="Total Income" :sortable="true">
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.total_income) }}
                </template>
            </Column>
            
            <Column field="total_expenses" header="Total Expenses" :sortable="true">
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.total_expenses) }}
                </template>
            </Column>
            
            <Column field="profit" header="Profit" :sortable="true">
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.profit) }}
                </template>
            </Column>
        </DataTable>

        <p v-else class="text-center text-gray-500">Select a month and year to view the report.</p>
    </div>
</template>

<style scoped>
.card {
    max-width: 800px;
    margin: 0 auto;
}
</style>
