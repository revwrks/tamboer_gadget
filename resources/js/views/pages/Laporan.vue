<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import InputText from "primevue/inputtext";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";

const reportData = ref([]);
const selectedReport = ref(null);
const filters = ref({
    global: { value: null, matchMode: "contains" },
});
const currentMonth = ref(new Date().toLocaleString("id-ID", { month: "long", year: "numeric" }));

const fetchReportData = async () => {
    try {
        const response = await axios.get("/api/report");
        reportData.value = [response.data]; // Ensure it's an array for DataTable
    } catch (error) {
        console.error("Error fetching report data:", error);
    }
};

onMounted(fetchReportData);

const toast = useToast();

function formatCurrency(value) {
    if (value != null) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(Math.round(value));
    }
    return "-";
}

function handleMonthChange(newMonth) {
    // Implement function to fetch and display data for the selected month
    console.log(`Fetching data for ${newMonth}`);
}
</script>

<template>
    <div class="card">
        <h2 class="text-xl font-bold mb-4">Monthly Sales Report - {{ currentMonth }}</h2>

        <!-- Month Selector (Optional) -->
        <div class="mb-4">
            <InputText v-model="filters.global.value" placeholder="Search..." class="w-full md:w-auto" />
        </div>

        <DataTable
            ref="dt"
            v-model:selection="selectedReport"
            :value="reportData"
            dataKey="id"
            :paginator="false"
            :filters="filters"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Monthly Report Data</h4>
                    <InputText
                        v-model="filters.global.value"
                        placeholder="Search..."
                        class="w-full md:w-auto"
                    />
                </div>
            </template>

            <!-- Phones Sold -->
            <Column field="phones_sold" header="Unit Terjual" sortable>
                <template #body="slotProps">
                    {{ slotProps.data.phones_sold }}
                </template>
            </Column>

            <!-- Total Income -->
            <Column field="total_income" header="Total Pemasukan" sortable>
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.total_income) }}
                </template>
            </Column>

            <!-- Total Expenses -->
            <Column field="total_expenses" header="Total Pengeluaran" sortable>
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.total_expenses) }}
                </template>
            </Column>

            <!-- Profit -->
            <Column field="profit" header="Keuntungan" sortable>
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.profit) }}
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<style scoped>
.card {
    padding: 2rem;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
</style>
