<script setup>
import { useLayout } from "@/layout/composables/layout";
import { onMounted, ref, computed, watch } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import { useAuth } from "@/composables/useAuth";

const router = useRouter();
const { isDarkTheme } = useLayout();
const { user, isOwner } = useAuth();

// Loading states
const isLoading = ref(true);

// Analytics cards state
const cards = ref({
    sold_this_month: 0,
    revenue_this_month: 0,
    profit_this_month: 0,
    growth_revenue_pct: null,
    growth_profit_pct: null,
    ready_stock_count: 0,
    ready_asset_value: 0,
    ready_gadget: 0,
    ready_aksesoris: 0,
    total_sold_ever: 0,
    total_profit_ever: 0,
});

// Chart states
const trendChartData = ref(null);
const trendChartOptions = ref(null);
const categoryChartData = ref(null);
const categoryChartOptions = ref(null);

// Sales performance state
const salesPerformance = ref([]);
const salesDetailDialog = ref(false);
const selectedSales = ref(null);
const selectedSalesItems = ref([]);

const formatCurrency = (value) => {
    return (parseFloat(value) || 0).toLocaleString("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
};

const currentMonthName = computed(() => {
    const months = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];
    return months[new Date().getMonth()];
});

// Configure Charts for Nordic Theme (Adaptive Light & Dark)
const setupCharts = (trend, categoryBreakdown) => {
    const isDark = isDarkTheme.value;
    const textColor = isDark ? "#a8a29e" : "#78716c";
    const textDark = isDark ? "#fafaf9" : "#1c1917";
    const gridColor = isDark ? "rgba(255, 255, 255, 0.08)" : "rgba(0, 0, 0, 0.05)";

    // 1. Trend Chart (Revenue vs Profit Bar/Line Combo)
    trendChartData.value = {
        labels: trend.labels || [],
        datasets: [
            {
                type: "bar",
                label: "Omset Penjualan",
                backgroundColor: isDark ? "#44403c" : "#1c1917",
                hoverBackgroundColor: isDark ? "#57534e" : "#292524",
                borderRadius: 8,
                data: trend.revenue || [],
            },
            {
                type: "line",
                label: "Laba Bersih",
                borderColor: isDark ? "#fbbf24" : "#b45309",
                backgroundColor: isDark ? "#fbbf24" : "#b45309",
                pointBackgroundColor: isDark ? "#fbbf24" : "#b45309",
                pointBorderColor: isDark ? "#1c1917" : "#ffffff",
                pointHoverBackgroundColor: isDark ? "#f59e0b" : "#92400e",
                borderWidth: 2.5,
                tension: 0.35,
                fill: false,
                data: trend.profit || [],
            },
        ],
    };

    trendChartOptions.value = {
        maintainAspectRatio: false,
        aspectRatio: 0.75,
        plugins: {
            legend: {
                labels: {
                    color: textDark,
                    font: { weight: 600, family: "'Plus Jakarta Sans', sans-serif" },
                },
            },
            tooltip: {
                callbacks: {
                    label: function (context) {
                        let label = context.dataset.label || "";
                        if (label) label += ": ";
                        if (context.parsed.y !== null) {
                            label += formatCurrency(context.parsed.y);
                        }
                        return label;
                    },
                },
            },
        },
        scales: {
            x: {
                ticks: {
                    color: textColor,
                    font: { weight: 500, family: "'Plus Jakarta Sans', sans-serif" },
                },
                grid: {
                    display: false,
                },
            },
            y: {
                ticks: {
                    color: textColor,
                    callback: function (val) {
                        return val >= 1000000 ? val / 1000000 + " Jt" : val;
                    },
                    font: { family: "'JetBrains Mono', monospace" },
                },
                grid: {
                    color: gridColor,
                },
            },
        },
    };

    // 2. Category Breakdown Doughnut Chart
    categoryChartData.value = {
        labels: categoryBreakdown?.labels || ["Smartphone", "Aksesoris", "Sparepart"],
        datasets: [
            {
                data: categoryBreakdown?.data || [0, 0, 0],
                backgroundColor: isDark
                    ? ["#fbbf24", "#f59e0b", "#a8a29e", "#57534e"]
                    : ["#1c1917", "#b45309", "#d97706", "#a8a29e"],
                hoverBackgroundColor: isDark
                    ? ["#fcd34d", "#fbbf24", "#d6d3d1", "#78716c"]
                    : ["#292524", "#92400e", "#b45309", "#78716c"],
                borderWidth: 2,
                borderColor: isDark ? "#1c1917" : "#ffffff",
            },
        ],
    };

    categoryChartOptions.value = {
        maintainAspectRatio: false,
        aspectRatio: 0.8,
        plugins: {
            legend: {
                position: "bottom",
                labels: {
                    color: textDark,
                    usePointStyle: true,
                    font: { weight: 500, family: "'Plus Jakarta Sans', sans-serif" },
                },
            },
        },
    };
};

// Re-render chart on theme toggle
watch(isDarkTheme, () => {
    if (trendChartData.value) {
        fetchDashboardData();
    }
});

// Fetch Dashboard Analytics
const fetchDashboardData = async () => {
    isLoading.value = true;
    try {
        const [analyticsRes, perfRes] = await Promise.all([
            axios.get("analytics/dashboard"),
            axios.get("sales-performance"),
        ]);

        if (analyticsRes.data) {
            cards.value = analyticsRes.data.cards;
            setupCharts(analyticsRes.data.trend, analyticsRes.data.category_breakdown);
        }

        if (perfRes.data) {
            salesPerformance.value = perfRes.data;
        }
    } catch (error) {
        console.error("Error fetching dashboard data:", error);
    } finally {
        isLoading.value = false;
    }
};

// Show detail modal when clicking on sales performance
const showSalesDetail = (sales) => {
    selectedSales.value = sales.nama_sales;
    selectedSalesItems.value = sales.items || [];
    salesDetailDialog.value = true;
};

onMounted(() => {
    fetchDashboardData();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Top Bar: Greeting & Quick Action Hub -->
        <div class="bg-white border border-[#e7e5df] rounded-2xl p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-[#1c1917] text-white flex items-center justify-center font-bold text-lg shadow-sm">
                        <i class="pi pi-bolt text-amber-400"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-[#1c1917] tracking-tight">
                            Halo, {{ user?.nama || 'Rekan' }}!
                        </h1>
                        <p class="text-xs text-[#78716c] mt-0.5">
                            Dashboard Tamboer Gadget &bull; Periode <span class="font-semibold text-[#1c1917]">{{ currentMonthName }} {{ new Date().getFullYear() }}</span>
                        </p>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex items-center flex-wrap gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#1c1917] hover:bg-[#292524] text-white flex items-center gap-2 shadow-sm transition-all"
                        @click="router.push('/pages/penjualan')"
                    >
                        <i class="pi pi-shopping-cart text-xs text-amber-400"></i>
                        <span>Kasir POS</span>
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#f0ede6] hover:bg-[#e7e5df] text-[#1c1917] flex items-center gap-2 border border-[#e7e5df] transition-all"
                        @click="router.push('/pages/stok')"
                    >
                        <i class="pi pi-box text-xs"></i>
                        <span>Kelola Stok</span>
                    </button>
                    <button
                        v-if="isOwner"
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#f0ede6] hover:bg-[#e7e5df] text-[#1c1917] flex items-center gap-2 border border-[#e7e5df] transition-all"
                        @click="router.push('/pages/laporan')"
                    >
                        <i class="pi pi-file-excel text-xs text-amber-700 dark:text-amber-400"></i>
                        <span>Laporan Keuangan</span>
                    </button>
                    <button
                        type="button"
                        class="w-8 h-8 rounded-xl bg-[#f0ede6] hover:bg-[#e7e5df] text-[#78716c] hover:text-[#1c1917] flex items-center justify-center border border-[#e7e5df] transition-all"
                        @click="fetchDashboardData"
                        v-tooltip.top="'Refresh Data'"
                    >
                        <i class="pi pi-refresh text-xs" :class="isLoading ? 'pi-spin' : ''"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Laba Bersih Bulan Ini (Owner) / Penjualan Bulan Ini (Staff) -->
            <div class="bg-white border border-[#e7e5df] rounded-2xl p-5 shadow-sm">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#78716c]">
                            {{ isOwner ? 'Laba Bersih Bulan Ini' : 'Unit Terjual Bulan Ini' }}
                        </span>
                        <div class="text-2xl font-bold font-mono text-[#1c1917] mt-1">
                            {{ isOwner ? formatCurrency(cards.profit_this_month) : cards.sold_this_month + ' Unit' }}
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400 flex items-center justify-center border border-amber-200/50 dark:border-amber-800/40">
                        <i class="pi pi-wallet text-lg"></i>
                    </div>
                </div>
                <div class="text-xs text-[#78716c] flex items-center gap-1.5 pt-2 border-t border-[#e7e5df]">
                    <span v-if="cards.growth_profit_pct !== null" :class="cards.growth_profit_pct >= 0 ? 'text-amber-800 dark:text-amber-400 font-bold' : 'text-rose-600 font-bold'">
                        {{ cards.growth_profit_pct >= 0 ? '+' : '' }}{{ cards.growth_profit_pct }}%
                    </span>
                    <span v-if="cards.growth_profit_pct !== null">dibanding bulan lalu</span>
                    <span v-else>Periode aktif {{ currentMonthName }}</span>
                </div>
            </div>

            <!-- Card 2: Omset Penjualan Bulan Ini -->
            <div class="bg-white border border-[#e7e5df] rounded-2xl p-5 shadow-sm">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#78716c]">
                            {{ isOwner ? 'Omset Penjualan' : 'Unit Handphone Ready' }}
                        </span>
                        <div class="text-2xl font-bold font-mono text-[#1c1917] mt-1">
                            {{ isOwner ? formatCurrency(cards.revenue_this_month) : cards.ready_gadget + ' Unit' }}
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center border border-amber-200/50">
                        <i class="pi pi-chart-line text-lg"></i>
                    </div>
                </div>
                <div class="text-xs text-[#78716c] flex items-center gap-1.5 pt-2 border-t border-[#e7e5df]">
                    <span class="font-bold text-[#1c1917]">{{ cards.sold_this_month }} transaksi</span>
                    <span>telah berhasil dibukukan</span>
                </div>
            </div>

            <!-- Card 3: Valuasi Aset Stok Ready (Modal Toko) -->
            <div class="bg-white border border-[#e7e5df] rounded-2xl p-5 shadow-sm">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#78716c]">
                            {{ isOwner ? 'Nilai Aset Ready' : 'Total Stok Tersedia' }}
                        </span>
                        <div class="text-2xl font-bold font-mono text-[#1c1917] mt-1">
                            {{ isOwner ? formatCurrency(cards.ready_asset_value) : cards.ready_stock_count + ' Barang' }}
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-stone-100 text-[#1c1917] flex items-center justify-center border border-[#e7e5df]">
                        <i class="pi pi-box text-lg"></i>
                    </div>
                </div>
                <div class="text-xs text-[#78716c] flex items-center gap-1.5 pt-2 border-t border-[#e7e5df]">
                    <span class="font-bold text-[#1c1917]">{{ cards.ready_stock_count }} item</span>
                    <span>siap dipajang & dijual</span>
                </div>
            </div>

            <!-- Card 4: Akumulasi Unit Terjual Seumur Hidup -->
            <div class="bg-white border border-[#e7e5df] rounded-2xl p-5 shadow-sm">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#78716c]">
                            Penjualan Global
                        </span>
                        <div class="text-2xl font-bold font-mono text-[#1c1917] mt-1">
                            {{ cards.total_sold_ever }} <span class="text-sm font-normal text-[#78716c]">Unit</span>
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-stone-100 text-[#1c1917] flex items-center justify-center border border-[#e7e5df]">
                        <i class="pi pi-history text-lg"></i>
                    </div>
                </div>
                <div class="text-xs text-[#78716c] flex items-center gap-1.5 pt-2 border-t border-[#e7e5df]">
                    <span v-if="isOwner">Total Laba: <b class="text-amber-800 dark:text-amber-400 font-mono">{{ formatCurrency(cards.total_profit_ever) }}</b></span>
                    <span v-else>Rekor transaksi seumur toko</span>
                </div>
            </div>
        </div>

        <!-- Charts & Visual Analytics (Owner Only) -->
        <div v-if="isOwner" class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            <!-- Left: 6-Month Trend (Omset vs Laba) -->
            <div class="lg:col-span-8 bg-white border border-[#e7e5df] rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-[#1c1917]">
                            Tren Omset & Laba Bersih (6 Bulan Terakhir)
                        </h2>
                        <p class="text-xs text-[#78716c]">Grafik perbandingan performa penjualan vs laba bersih bulanan</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/40">
                        Aktif
                    </span>
                </div>
                <div style="height: 300px;">
                    <Chart
                        v-if="trendChartData"
                        type="bar"
                        :data="trendChartData"
                        :options="trendChartOptions"
                        class="w-full h-full"
                    />
                    <div v-else class="h-full flex items-center justify-center text-[#78716c]">
                        Memuat data grafik...
                    </div>
                </div>
            </div>

            <!-- Right: Sales by Category Doughnut -->
            <div class="lg:col-span-4 bg-white border border-[#e7e5df] rounded-2xl p-6 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-base font-bold text-[#1c1917]">
                        Proporsi Kategori Terjual
                    </h2>
                    <p class="text-xs text-[#78716c]">Distribusi unit terjual bulan ini</p>
                </div>
                <div style="height: 300px;">
                    <Chart
                        v-if="categoryChartData"
                        type="doughnut"
                        :data="categoryChartData"
                        :options="categoryChartOptions"
                        class="w-full h-full"
                    />
                    <div v-else class="h-full flex items-center justify-center text-[#78716c]">
                        Memuat data kategori...
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard & Sales Performance (Owner Only) -->
        <div v-if="isOwner" class="bg-white border border-[#e7e5df] rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-[#1c1917]">
                        Performa Kasir & Sales &bull; {{ currentMonthName }} {{ new Date().getFullYear() }}
                    </h2>
                    <p class="text-xs text-[#78716c]">Peringkat kontribusi penjualan per karyawan pada bulan berjalan</p>
                </div>
            </div>

            <DataTable
                :value="salesPerformance"
                dataKey="id_sales"
                responsiveLayout="scroll"
                stripedRows
                class="p-datatable-sm"
            >
                <template #empty>
                    <div class="text-center py-6 text-[#78716c]">
                        Belum ada data transaksi untuk bulan ini.
                    </div>
                </template>

                <Column field="nama_sales" header="Nama Karyawan" sortable>
                    <template #body="{ data }">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-[#f0ede6] text-[#1c1917] flex items-center justify-center font-bold text-xs">
                                {{ data.nama_sales ? data.nama_sales.charAt(0).toUpperCase() : 'S' }}
                            </div>
                            <span class="font-semibold text-xs text-[#1c1917]">{{ data.nama_sales }}</span>
                        </div>
                    </template>
                </Column>

                <Column field="jumlah_unit" header="Unit Terjual" sortable style="width: 10rem">
                    <template #body="{ data }">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-[#f0ede6] hover:bg-[#e7e5df] text-[#1c1917] border border-[#e7e5df]"
                            v-tooltip.top="'Klik untuk lihat rincian unit'"
                            @click="showSalesDetail(data)"
                        >
                            {{ data.jumlah_unit }} Unit
                        </button>
                    </template>
                </Column>

                <Column field="total_revenue" header="Total Omset" sortable style="min-width: 11rem">
                    <template #body="{ data }">
                        <span class="font-medium font-mono text-xs text-[#1c1917]">
                            {{ formatCurrency(data.total_revenue) }}
                        </span>
                    </template>
                </Column>

                <Column field="total_laba" header="Kontribusi Laba" sortable style="min-width: 11rem">
                    <template #body="{ data }">
                        <span class="font-bold font-mono text-xs text-amber-800 dark:text-amber-400">
                            {{ formatCurrency(data.total_laba) }}
                        </span>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Sales Detail Modal -->
        <Dialog
            v-model:visible="salesDetailDialog"
            :style="{ width: '800px', maxWidth: '95vw' }"
            :header="'Rincian Penjualan: ' + selectedSales"
            modal
            dismissableMask
            class="rounded-2xl bg-white"
        >
            <DataTable
                :value="selectedSalesItems"
                dataKey="no_invoice"
                :rows="10"
                :paginator="selectedSalesItems.length > 10"
                class="p-datatable-sm"
                stripedRows
                scrollable
                scrollHeight="400px"
            >
                <Column field="tanggal" header="Tanggal" sortable style="min-width: 7rem">
                    <template #body="{ data }">
                        <span class="text-xs text-[#44403c]">{{ new Date(data.tanggal).toLocaleDateString("id-ID") }}</span>
                    </template>
                </Column>
                <Column field="no_invoice" header="No Faktur" style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-semibold text-[#1c1917]">{{ data.no_invoice }}</span>
                    </template>
                </Column>
                <Column field="brand" header="Brand" sortable>
                    <template #body="{ data }">
                        <span class="text-xs text-amber-800 font-semibold uppercase">{{ data.brand }}</span>
                    </template>
                </Column>
                <Column field="tipe_hp" header="Item / Model" sortable style="min-width: 10rem">
                    <template #body="{ data }">
                        <span class="text-xs font-semibold text-[#1c1917]">{{ data.tipe_hp }}</span>
                    </template>
                </Column>
                <Column field="imei" header="IMEI / Seri" style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="font-mono text-xs text-[#78716c]">{{ data.imei || '-' }}</span>
                    </template>
                </Column>
                <Column field="harga_jual" header="Harga Jual" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="font-bold font-mono text-xs text-[#1c1917]">{{ formatCurrency(data.harga_jual) }}</span>
                    </template>
                </Column>
                <Column field="met_pem" header="Metode" sortable>
                    <template #body="{ data }">
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-[#f0ede6] text-[#44403c]">{{ data.met_pem }}</span>
                    </template>
                </Column>
            </DataTable>
            <template #footer>
                <Button
                    label="Tutup"
                    icon="pi pi-times"
                    severity="secondary"
                    @click="salesDetailDialog = false"
                />
            </template>
        </Dialog>
    </div>
</template>
