<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import { useLayout } from "@/layout/composables/layout";
import { useAuth } from "@/composables/useAuth";
import { extractApiError, formatCurrency, formatDate } from "@/utils/formatters";

const router = useRouter();
const { isDarkTheme } = useLayout();
const { user, isOwner } = useAuth();

const isLoading = ref(true);
const loadError = ref("");
const analyticsPayload = ref(null);

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
    ready_sparepart: 0,
    total_sold_ever: 0,
    total_profit_ever: 0,
});

const trendChartData = ref(null);
const trendChartOptions = ref(null);
const categoryChartData = ref(null);
const categoryChartOptions = ref(null);

const salesPerformance = ref([]);
const salesDetailDialog = ref(false);
const selectedSales = ref("");
const selectedSalesItems = ref([]);

const currentPeriodLabel = computed(() => {
    const now = new Date();
    return now.toLocaleDateString("id-ID", { month: "long", year: "numeric" });
});

const hasCategorySales = computed(() => {
    const counts = categoryChartData.value?.datasets?.[0]?.data || [];
    return counts.some((val) => val > 0);
});

const setupCharts = () => {
    const payload = analyticsPayload.value;
    if (!payload || !isOwner.value) return;

    const isDark = isDarkTheme.value;
    const textMuted = isDark ? "#a1a1aa" : "#52525b";
    const textInk = isDark ? "#f5f5f7" : "#111113";
    const gridColor = isDark ? "rgba(255, 255, 255, 0.07)" : "rgba(0, 0, 0, 0.06)";
    const accentColor = isDark ? "#f59e0b" : "#b45309";
    const barColor = isDark ? "#3a3a42" : "#18181b";

    const trend = payload.trend || { labels: [], revenue: [], profit: [] };

    trendChartData.value = {
        labels: trend.labels || [],
        datasets: [
            {
                type: "bar",
                label: "Omset Bersih",
                backgroundColor: barColor,
                borderRadius: 6,
                data: trend.revenue || [],
            },
            {
                type: "line",
                label: "Laba Bersih",
                borderColor: accentColor,
                backgroundColor: accentColor,
                pointBackgroundColor: accentColor,
                pointBorderColor: isDark ? "#161619" : "#ffffff",
                borderWidth: 2.5,
                tension: 0.35,
                fill: false,
                data: trend.profit || [],
            },
        ],
    };

    trendChartOptions.value = {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: textInk,
                    font: { weight: 600, family: "'Plus Jakarta Sans', sans-serif" },
                },
            },
            tooltip: {
                callbacks: {
                    label: (ctx) => `${ctx.dataset.label || ""}: ${formatCurrency(ctx.parsed.y)}`,
                },
            },
        },
        scales: {
            x: {
                ticks: { color: textMuted, font: { weight: 500, family: "'Plus Jakarta Sans', sans-serif" } },
                grid: { display: false },
            },
            y: {
                ticks: {
                    color: textMuted,
                    callback: (val) => (val >= 1000000 ? `${val / 1000000} Jt` : val),
                    font: { family: "'JetBrains Mono', monospace" },
                },
                grid: { color: gridColor },
            },
        },
    };

    const cat = payload.category_breakdown || { labels: [], data: [] };
    categoryChartData.value = {
        labels: cat.labels || [],
        datasets: [
            {
                data: cat.data || [],
                backgroundColor: isDark
                    ? ["#f59e0b", "#a1a1aa", "#52525b"]
                    : ["#18181b", "#b45309", "#a1a1aa"],
                borderWidth: 2,
                borderColor: isDark ? "#161619" : "#ffffff",
            },
        ],
    };

    categoryChartOptions.value = {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: "bottom",
                labels: {
                    color: textInk,
                    usePointStyle: true,
                    font: { weight: 500, family: "'Plus Jakarta Sans', sans-serif" },
                },
            },
        },
    };
};

watch(isDarkTheme, () => {
    setupCharts();
});

const fetchDashboardData = async () => {
    isLoading.value = true;
    loadError.value = "";
    try {
        const requests = [axios.get("analytics/dashboard")];
        if (isOwner.value) {
            requests.push(axios.get("sales-performance"));
        }

        const [analyticsRes, perfRes] = await Promise.all(requests);

        if (analyticsRes?.data) {
            analyticsPayload.value = analyticsRes.data;
            cards.value = analyticsRes.data.cards || cards.value;
            setupCharts();
        }

        if (perfRes?.data) {
            salesPerformance.value = perfRes.data;
        }
    } catch (error) {
        loadError.value = extractApiError(error, "Gagal memuat data ringkasan.");
    } finally {
        isLoading.value = false;
    }
};

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
        <!-- Quiet Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">
                    Halo, {{ user?.nama || "Rekan" }}
                </h1>
                <p class="text-xs sm:text-sm text-muted mt-0.5">
                    Periode {{ currentPeriodLabel }}
                </p>
            </div>

            <div class="flex items-center flex-wrap gap-2">
                <button
                    type="button"
                    class="min-h-[38px] px-4 py-2 rounded-xl text-xs font-semibold bg-ink text-surface hover:opacity-90 flex items-center gap-2 transition-opacity"
                    @click="router.push('/pages/penjualan')"
                >
                    <i class="pi pi-shopping-bag text-xs text-accent"></i>
                    <span>Buka Kasir POS</span>
                </button>
                <button
                    type="button"
                    class="min-h-[38px] px-3.5 py-2 rounded-xl text-xs font-semibold bg-surface border border-hairline hover:bg-subtle text-ink flex items-center gap-2 transition-colors"
                    @click="router.push('/pages/stok')"
                >
                    <i class="pi pi-box text-xs text-muted"></i>
                    <span>Kelola Stok</span>
                </button>
                <button
                    type="button"
                    class="w-9 h-9 rounded-xl bg-surface border border-hairline hover:bg-subtle text-muted hover:text-ink flex items-center justify-center transition-colors"
                    aria-label="Muat ulang data"
                    v-tooltip.top="'Muat Ulang'"
                    @click="fetchDashboardData"
                >
                    <i class="pi pi-refresh text-xs" :class="isLoading ? 'pi-spin' : ''"></i>
                </button>
            </div>
        </div>

        <!-- Error State -->
        <div
            v-if="loadError"
            role="alert"
            class="bg-surface border border-rose-500/30 rounded-2xl p-5 flex items-center justify-between gap-4"
        >
            <div class="text-sm text-rose-600 dark:text-rose-400 font-medium">
                {{ loadError }}
            </div>
            <button
                type="button"
                class="min-h-[36px] px-3.5 py-1.5 rounded-lg bg-subtle hover:bg-hover text-ink text-xs font-semibold"
                @click="fetchDashboardData"
            >
                Coba Lagi
            </button>
        </div>

        <!-- Loading Skeleton -->
        <div v-else-if="isLoading" class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <div class="lg:col-span-5 h-36 rounded-2xl bg-surface border border-hairline animate-pulse"></div>
            <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="h-36 rounded-2xl bg-surface border border-hairline animate-pulse"></div>
                <div class="h-36 rounded-2xl bg-surface border border-hairline animate-pulse"></div>
                <div class="h-36 rounded-2xl bg-surface border border-hairline animate-pulse"></div>
            </div>
        </div>

        <!-- OWNER VIEW -->
        <template v-else-if="isOwner">
            <!-- Asymmetric Financial Hierarchy -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Featured Net Profit Card -->
                <div class="lg:col-span-5 bg-surface border border-hairline rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="text-xs font-semibold text-muted">Laba Bersih Bulan Ini</span>
                            <div class="text-2xl sm:text-3xl font-bold font-mono tabular-nums text-ink mt-1.5">
                                {{ formatCurrency(cards.profit_this_month) }}
                            </div>
                        </div>
                        <span
                            v-if="cards.growth_profit_pct !== null"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold tabular-nums"
                            :class="
                                cards.growth_profit_pct >= 0
                                    ? 'bg-accent-soft text-accent-ink'
                                    : 'bg-rose-500/10 text-rose-600 dark:text-rose-400'
                            "
                        >
                            {{ cards.growth_profit_pct >= 0 ? "+" : "" }}{{ cards.growth_profit_pct }}% MoM
                        </span>
                    </div>
                    <div class="mt-5 pt-3.5 border-t border-hairline flex items-center justify-between text-xs text-muted">
                        <span>Akumulasi Laba Global</span>
                        <span class="font-mono tabular-nums font-semibold text-ink">
                            {{ formatCurrency(cards.total_profit_ever) }}
                        </span>
                    </div>
                </div>

                <!-- Secondary Operational & Financial Metrics -->
                <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-surface border border-hairline rounded-2xl p-5 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-muted">Omset Bersih</span>
                            <div class="text-xl font-bold font-mono tabular-nums text-ink mt-1.5">
                                {{ formatCurrency(cards.revenue_this_month) }}
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-hairline text-xs text-muted tabular-nums">
                            <span class="font-semibold text-ink">{{ cards.sold_this_month }}</span> unit terjual bulan ini
                        </div>
                    </div>

                    <div class="bg-surface border border-hairline rounded-2xl p-5 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-muted">Nilai Aset Ready</span>
                            <div class="text-xl font-bold font-mono tabular-nums text-ink mt-1.5">
                                {{ formatCurrency(cards.ready_asset_value) }}
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-hairline text-xs text-muted tabular-nums">
                            <span class="font-semibold text-ink">{{ cards.ready_stock_count }}</span> item siap jual
                        </div>
                    </div>

                    <div class="bg-surface border border-hairline rounded-2xl p-5 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-muted">Total Unit Terjual</span>
                            <div class="text-xl font-bold font-mono tabular-nums text-ink mt-1.5">
                                {{ cards.total_sold_ever }} <span class="text-xs font-normal text-muted">Unit</span>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-hairline text-xs text-muted tabular-nums">
                            {{ cards.ready_gadget }} HP &bull; {{ cards.ready_aksesoris }} Aksesoris
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                <div class="lg:col-span-8 bg-surface border border-hairline rounded-2xl p-6">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-ink">Tren Omset &amp; Laba 6 Bulan Terakhir</h2>
                        <p class="text-xs text-muted mt-0.5">Perbandingan omset bersih dan laba bersih bulanan</p>
                    </div>
                    <div style="height: 290px">
                        <Chart
                            v-if="trendChartData"
                            type="bar"
                            :data="trendChartData"
                            :options="trendChartOptions"
                            class="w-full h-full"
                        />
                    </div>
                </div>

                <div class="lg:col-span-4 bg-surface border border-hairline rounded-2xl p-6 flex flex-col">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-ink">Kategori Terjual Bulan Ini</h2>
                        <p class="text-xs text-muted mt-0.5">Proporsi unit per kategori produk</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center" style="min-height: 290px">
                        <Chart
                            v-if="categoryChartData && hasCategorySales"
                            type="doughnut"
                            :data="categoryChartData"
                            :options="categoryChartOptions"
                            class="w-full h-full"
                        />
                        <div v-else class="text-center text-xs text-muted px-4">
                            Belum ada transaksi penjualan pada bulan ini.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Team Performance -->
            <div class="bg-surface border border-hairline rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-ink">Performa Tim Penjualan</h2>
                        <p class="text-xs text-muted mt-0.5">Kontribusi penjualan bulan {{ currentPeriodLabel }}</p>
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
                        <div class="text-center py-8 text-xs text-muted">
                            Belum ada transaksi penjualan yang tercatat pada bulan ini.
                        </div>
                    </template>

                    <Column field="nama_sales" header="Karyawan" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-subtle border border-hairline text-ink flex items-center justify-center font-bold text-xs">
                                    {{ data.nama_sales ? data.nama_sales.charAt(0).toUpperCase() : "S" }}
                                </div>
                                <span class="font-semibold text-xs text-ink">{{ data.nama_sales }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="jumlah_unit" header="Unit Terjual" sortable style="width: 10rem">
                        <template #body="{ data }">
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold tabular-nums bg-subtle hover:bg-hover text-ink border border-hairline transition-colors"
                                @click="showSalesDetail(data)"
                            >
                                {{ data.jumlah_unit }} Unit
                            </button>
                        </template>
                    </Column>

                    <Column field="total_revenue" header="Omset Bersih" sortable style="min-width: 11rem">
                        <template #body="{ data }">
                            <span class="font-mono tabular-nums text-xs text-ink">
                                {{ formatCurrency(data.total_revenue) }}
                            </span>
                        </template>
                    </Column>

                    <Column field="total_laba" header="Kontribusi Laba" sortable style="min-width: 11rem">
                        <template #body="{ data }">
                            <span class="font-bold font-mono tabular-nums text-xs text-accent-ink">
                                {{ formatCurrency(data.total_laba) }}
                            </span>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </template>

        <!-- SALES / KASIR OPERATIONAL VIEW -->
        <template v-else>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-surface border border-hairline rounded-2xl p-5">
                    <span class="text-xs font-semibold text-muted">Unit Terjual Bulan Ini</span>
                    <div class="text-2xl font-bold font-mono tabular-nums text-ink mt-1.5">
                        {{ cards.sold_this_month }} <span class="text-xs font-normal text-muted">Unit</span>
                    </div>
                </div>
                <div class="bg-surface border border-hairline rounded-2xl p-5">
                    <span class="text-xs font-semibold text-muted">Smartphone Ready</span>
                    <div class="text-2xl font-bold font-mono tabular-nums text-ink mt-1.5">
                        {{ cards.ready_gadget }} <span class="text-xs font-normal text-muted">Unit</span>
                    </div>
                </div>
                <div class="bg-surface border border-hairline rounded-2xl p-5">
                    <span class="text-xs font-semibold text-muted">Total Stok Tersedia</span>
                    <div class="text-2xl font-bold font-mono tabular-nums text-ink mt-1.5">
                        {{ cards.ready_stock_count }} <span class="text-xs font-normal text-muted">Item</span>
                    </div>
                </div>
                <div class="bg-surface border border-hairline rounded-2xl p-5">
                    <span class="text-xs font-semibold text-muted">Total Penjualan Toko</span>
                    <div class="text-2xl font-bold font-mono tabular-nums text-ink mt-1.5">
                        {{ cards.total_sold_ever }} <span class="text-xs font-normal text-muted">Unit</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button
                    type="button"
                    class="text-left bg-surface border border-hairline hover:border-hairline-strong rounded-2xl p-6 transition-colors flex items-center justify-between gap-4"
                    @click="router.push('/pages/penjualan')"
                >
                    <div>
                        <h2 class="text-base font-bold text-ink">Mulai Transaksi Kasir</h2>
                        <p class="text-xs text-muted mt-1">Pindai IMEI, hitung kembalian, dan cetak struk thermal.</p>
                    </div>
                    <i class="pi pi-arrow-right text-muted"></i>
                </button>

                <button
                    type="button"
                    class="text-left bg-surface border border-hairline hover:border-hairline-strong rounded-2xl p-6 transition-colors flex items-center justify-between gap-4"
                    @click="router.push('/pages/stok')"
                >
                    <div>
                        <h2 class="text-base font-bold text-ink">Cek &amp; Tambah Inventaris</h2>
                        <p class="text-xs text-muted mt-1">Lihat ketersediaan stok etalase atau catat unit masuk.</p>
                    </div>
                    <i class="pi pi-arrow-right text-muted"></i>
                </button>
            </div>
        </template>

        <!-- Sales Detail Modal -->
        <Dialog
            v-model:visible="salesDetailDialog"
            :style="{ width: '820px', maxWidth: '95vw' }"
            :header="'Rincian Penjualan: ' + selectedSales"
            modal
            dismissableMask
        >
            <DataTable
                :value="selectedSalesItems"
                dataKey="id_penjualan"
                :rows="10"
                :paginator="selectedSalesItems.length > 10"
                class="p-datatable-sm"
                stripedRows
                scrollable
                scrollHeight="400px"
            >
                <Column field="tanggal" header="Tanggal" sortable style="min-width: 7rem">
                    <template #body="{ data }">
                        <span class="text-xs text-muted">{{ formatDate(data.tanggal) }}</span>
                    </template>
                </Column>
                <Column field="no_invoice" header="No. Faktur" style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-semibold text-ink">{{ data.no_invoice }}</span>
                    </template>
                </Column>
                <Column field="brand" header="Brand" sortable>
                    <template #body="{ data }">
                        <span class="text-xs font-semibold uppercase text-accent-ink">{{ data.brand }}</span>
                    </template>
                </Column>
                <Column field="tipe_hp" header="Model" sortable style="min-width: 10rem">
                    <template #body="{ data }">
                        <span class="text-xs font-semibold text-ink">{{ data.tipe_hp }}</span>
                    </template>
                </Column>
                <Column field="imei" header="IMEI / Seri" style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="font-mono text-xs text-muted">{{ data.imei || "-" }}</span>
                    </template>
                </Column>
                <Column field="harga_jual" header="Harga Jual" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="font-bold font-mono tabular-nums text-xs text-ink">
                            {{ formatCurrency(data.harga_jual) }}
                        </span>
                    </template>
                </Column>
                <Column field="met_pem" header="Metode" sortable>
                    <template #body="{ data }">
                        <span class="text-[11px] font-semibold uppercase px-2 py-0.5 rounded bg-subtle text-muted">
                            {{ data.met_pem }}
                        </span>
                    </template>
                </Column>
            </DataTable>
            <template #footer>
                <Button label="Tutup" icon="pi pi-times" severity="secondary" text @click="salesDetailDialog = false" />
            </template>
        </Dialog>
    </div>
</template>
