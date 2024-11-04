<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import DataTable from "primevue/datatable";
import Column from "primevue/column";

// State for report data of multiple months
const reportData = ref([]);
const selectedReport = ref(null);
const filters = ref({
    global: { value: null, matchMode: "contains" },
});

// Function to get month and year strings for a specific date
const getMonthYearString = (date) => {
    return date.toLocaleString("id-ID", { month: "long", year: "numeric" });
};

// Generate a list of the last N months
const generateMonthList = (numMonths) => {
    const months = [];
    const currentDate = new Date();

    for (let i = 0; i < numMonths; i++) {
        const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
        months.push({
            label: getMonthYearString(date),
            date: date,
        });
    }
    return months;
};

// Initialize month list (e.g., last 6 months)
const months = ref(generateMonthList(3));

const fetchReportData = async () => {
    try {
        // Loop over each month and fetch data for that month
        reportData.value = [];

        for (const month of months.value) {
            const response = await axios.get("/api/report", {
                params: { month: month.date.getMonth() + 1, year: month.date.getFullYear() },
            });

            reportData.value.push({
                month: month.label,
                ...response.data, // Spread the fetched report data into the object
            });
        }
    } catch (error) {
        console.error("Error fetching report data:", error);
    }
};


onMounted(() => {
    fetchReportData(); // Initial fetch
});

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
</script>

<template>
    <div class="card">
        <h2 class="text-xl font-bold mb-4">Monthly Sales Reports</h2>

        <div v-for="report in reportData" :key="report.month" class="mb-6">
            <h3 class="text-lg font-semibold mb-2">{{ report.month }}</h3>

            <!-- Data Table for each month -->
            <DataTable
                ref="dt"
                v-model:selection="selectedReport"
                :value="[report]"
                dataKey="id"
                :paginator="false"
                :filters="filters"
            >
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
    </div>
</template>
