<script setup>
import { computed, onMounted, ref, watch } from "vue";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Select from "primevue/select";
import ExcelJS from "exceljs";
import { downloadExcelBuffer, extractApiError, formatCurrency, formatDate } from "@/utils/formatters";

const toast = useToast();

const reportData = ref(null);
const availableDates = ref([]);
const salesDetails = ref([]);
const salesPerformance = ref([]);
const selectedPeriod = ref(null);
const loading = ref(false);

const salesDetailDialog = ref(false);
const selectedSales = ref("");
const selectedSalesItems = ref([]);

const monthNames = {
    1: "Januari",
    2: "Februari",
    3: "Maret",
    4: "April",
    5: "Mei",
    6: "Juni",
    7: "Juli",
    8: "Agustus",
    9: "September",
    10: "Oktober",
    11: "November",
    12: "Desember",
};

const periodOptions = computed(() => {
    if (availableDates.value.length > 0) {
        return availableDates.value.map((d) => ({
            label: `${monthNames[d.month] || d.month_name} ${d.year}`,
            value: `${d.year}-${d.month}`,
            year: d.year,
            month: d.month,
        }));
    }
    const now = new Date();
    const m = now.getMonth() + 1;
    const y = now.getFullYear();
    return [{ label: `${monthNames[m]} ${y}`, value: `${y}-${m}`, year: y, month: m }];
});

const activeYearMonth = computed(() => {
    if (!selectedPeriod.value) return null;
    const [y, m] = String(selectedPeriod.value).split("-").map(Number);
    if (!y || !m) return null;
    return { year: y, month: m };
});

const selectedPeriodLabel = computed(() => {
    if (!activeYearMonth.value) return "";
    return `${monthNames[activeYearMonth.value.month]} ${activeYearMonth.value.year}`;
});

const profitMarginPct = computed(() => {
    if (!reportData.value || !reportData.value.total_income) return 0;
    return Math.round((reportData.value.profit / reportData.value.total_income) * 100);
});

const fetchAvailableDates = async () => {
    try {
        const response = await axios.get("available-dates");
        availableDates.value = Array.isArray(response.data) ? response.data : [];
        if (availableDates.value.length > 0) {
            selectedPeriod.value = `${availableDates.value[0].year}-${availableDates.value[0].month}`;
        } else {
            const now = new Date();
            selectedPeriod.value = `${now.getFullYear()}-${now.getMonth() + 1}`;
        }
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Memuat",
            detail: extractApiError(error, "Gagal mengambil daftar periode laporan."),
            life: 3000,
        });
    }
};

const fetchPeriodData = async () => {
    if (!activeYearMonth.value) return;
    loading.value = true;
    const params = {
        month: activeYearMonth.value.month,
        year: activeYearMonth.value.year,
    };

    try {
        const [reportRes, detailsRes, perfRes] = await Promise.all([
            axios.get("report", { params }),
            axios.get("sales-details", { params }),
            axios.get("sales-performance", { params }),
        ]);
        reportData.value = reportRes.data;
        salesDetails.value = detailsRes.data;
        salesPerformance.value = perfRes.data;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Memuat",
            detail: extractApiError(error, "Gagal mengambil data laporan."),
            life: 3000,
        });
    } finally {
        loading.value = false;
    }
};

watch(selectedPeriod, () => {
    fetchPeriodData();
});

const showSalesDetail = (sales) => {
    selectedSales.value = sales.nama_sales;
    selectedSalesItems.value = sales.items || [];
    salesDetailDialog.value = true;
};

const exportToExcel = async () => {
    if (!salesDetails.value.length && !salesPerformance.value.length) {
        toast.add({ severity: "warn", summary: "Peringatan", detail: "Tidak ada data untuk di-export.", life: 3000 });
        return;
    }

    const ym = activeYearMonth.value;
    const fileName = `Laporan_Penjualan_${monthNames[ym.month]}_${ym.year}.xlsx`;

    const workbook = new ExcelJS.Workbook();
    workbook.creator = "Tamboer Gadget";
    workbook.created = new Date();

    if (salesDetails.value.length) {
        const ws1 = workbook.addWorksheet("Detail Penjualan");
        ws1.columns = [
            { header: "No", key: "no", width: 5 },
            { header: "No. Faktur", key: "no_invoice", width: 18 },
            { header: "Tanggal", key: "tanggal", width: 14 },
            { header: "Kasir", key: "penjual", width: 18 },
            { header: "Brand", key: "brand", width: 14 },
            { header: "Model", key: "tipe", width: 26 },
            { header: "IMEI", key: "imei", width: 18 },
            { header: "Harga Jual", key: "harga", width: 16 },
            { header: "Diskon", key: "diskon", width: 14 },
            { header: "Netto", key: "netto", width: 16 },
            { header: "Metode", key: "metode", width: 14 },
        ];

        ws1.getRow(1).font = { bold: true, color: { argb: "FFFFFFFF" } };
        ws1.getRow(1).fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FF18181B" } };

        let totalNet = 0;
        let totalDiskon = 0;
        salesDetails.value.forEach((item, index) => {
            const harga = Number(item.harga_jual) || 0;
            const diskon = Number(item.diskon) || 0;
            const netto = harga - diskon;
            totalNet += netto;
            totalDiskon += diskon;
            ws1.addRow({
                no: index + 1,
                no_invoice: item.no_invoice || "-",
                tanggal: formatDate(item.tanggal),
                penjual: item.user?.nama || item.users?.nama || "Petugas",
                brand: item.stok_hp?.brand || "-",
                tipe: item.stok_hp?.nama || "-",
                imei: item.stok_hp?.imei || "-",
                harga,
                diskon,
                netto,
                metode: item.met_pem || "-",
            });
        });

        const summaryRow = ws1.addRow({ imei: "TOTAL", diskon: totalDiskon, netto: totalNet });
        summaryRow.font = { bold: true };
        ws1.getColumn("harga").numFmt = "#,##0";
        ws1.getColumn("diskon").numFmt = "#,##0";
        ws1.getColumn("netto").numFmt = "#,##0";
    }

    if (salesPerformance.value.length) {
        const ws2 = workbook.addWorksheet("Performa Sales");
        ws2.columns = [
            { header: "No", key: "no", width: 5 },
            { header: "Nama Sales", key: "nama", width: 24 },
            { header: "Unit Terjual", key: "unit", width: 14 },
            { header: "Omset Bersih", key: "total", width: 18 },
            { header: "Laba Bersih", key: "laba", width: 18 },
        ];
        ws2.getRow(1).font = { bold: true, color: { argb: "FFFFFFFF" } };
        ws2.getRow(1).fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FF18181B" } };

        salesPerformance.value.forEach((item, index) => {
            ws2.addRow({
                no: index + 1,
                nama: item.nama_sales,
                unit: item.jumlah_unit || 0,
                total: Number(item.total_revenue) || 0,
                laba: Number(item.total_laba) || 0,
            });
        });
        ws2.getColumn("total").numFmt = "#,##0";
        ws2.getColumn("laba").numFmt = "#,##0";
    }

    if (reportData.value) {
        const ws3 = workbook.addWorksheet("Ringkasan");
        ws3.columns = [
            { header: "Keterangan", key: "keterangan", width: 26 },
            { header: "Nilai", key: "nilai", width: 20 },
        ];
        ws3.getRow(1).font = { bold: true, color: { argb: "FFFFFFFF" } };
        ws3.getRow(1).fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFB45309" } };

        ws3.addRow({ keterangan: "Unit Terjual", nilai: reportData.value.phones_sold || 0 });
        ws3.addRow({ keterangan: "Omset Kotor", nilai: reportData.value.gross_income || 0 });
        ws3.addRow({ keterangan: "Total Diskon", nilai: reportData.value.total_discount || 0 });
        ws3.addRow({ keterangan: "Omset Bersih", nilai: reportData.value.total_income || 0 });
        ws3.addRow({ keterangan: "Modal Terjual (HPP)", nilai: reportData.value.total_expenses || 0 });
        const labaRow = ws3.addRow({ keterangan: "Laba Bersih", nilai: reportData.value.profit || 0 });
        labaRow.font = { bold: true };
        ws3.getColumn("nilai").numFmt = "#,##0";
    }

    try {
        const buffer = await workbook.xlsx.writeBuffer();
        downloadExcelBuffer(buffer, fileName);
        toast.add({ severity: "success", summary: "Berhasil", detail: `${fileName} berhasil diunduh.`, life: 3000 });
    } catch {
        toast.add({ severity: "error", summary: "Error", detail: "Gagal membuat file Excel.", life: 3000 });
    }
};

onMounted(() => {
    fetchAvailableDates();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header & Period Filter -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">
                    Laporan Keuangan
                </h1>
                <p class="text-xs sm:text-sm text-muted mt-0.5">
                    Rekapitulasi omset bersih, diskon, HPP modal, dan laba bulanan
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <Select
                    v-model="selectedPeriod"
                    :options="periodOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Pilih Periode"
                    class="w-48 text-xs"
                />

                <button
                    v-if="reportData"
                    type="button"
                    class="min-h-[38px] px-4 py-2 rounded-xl text-xs font-semibold bg-ink text-surface hover:opacity-90 flex items-center gap-2 transition-opacity"
                    @click="exportToExcel"
                >
                    <i class="pi pi-file-excel text-xs text-accent"></i>
                    <span>Export Excel</span>
                </button>
            </div>
        </div>

        <!-- Loading Skeleton -->
        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="h-28 rounded-2xl bg-surface border border-hairline animate-pulse"></div>
        </div>

        <!-- Executive KPI Strip -->
        <div v-else-if="reportData" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-surface border border-hairline rounded-2xl p-5">
                <span class="text-xs text-muted font-medium block">Unit Terjual</span>
                <div class="text-2xl font-bold font-mono tabular-nums text-ink mt-1.5">
                    {{ reportData.phones_sold || 0 }} <span class="text-xs font-normal text-muted">Unit</span>
                </div>
            </div>

            <div class="bg-surface border border-hairline rounded-2xl p-5">
                <span class="text-xs text-muted font-medium block">Omset Bersih</span>
                <div class="text-2xl font-bold font-mono tabular-nums text-ink mt-1.5">
                    {{ formatCurrency(reportData.total_income) }}
                </div>
                <span v-if="reportData.total_discount > 0" class="text-[11px] text-muted font-mono tabular-nums mt-1 block">
                    Diskon: -{{ formatCurrency(reportData.total_discount) }}
                </span>
            </div>

            <div class="bg-surface border border-hairline rounded-2xl p-5">
                <span class="text-xs text-muted font-medium block">Modal Terjual (HPP)</span>
                <div class="text-2xl font-bold font-mono tabular-nums text-ink mt-1.5">
                    {{ formatCurrency(reportData.total_expenses) }}
                </div>
            </div>

            <div class="bg-surface border border-accent/40 rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-accent-ink font-semibold">Laba Bersih</span>
                    <span class="text-[11px] font-mono tabular-nums font-semibold px-2 py-0.5 rounded bg-accent-soft text-accent-ink">
                        {{ profitMarginPct }}%
                    </span>
                </div>
                <div class="text-2xl font-bold font-mono tabular-nums text-accent-ink mt-1.5">
                    {{ formatCurrency(reportData.profit) }}
                </div>
            </div>
        </div>

        <!-- Sales Performance Table -->
        <div v-if="salesPerformance.length > 0" class="bg-surface border border-hairline rounded-2xl p-5 space-y-3">
            <div>
                <h2 class="text-base font-bold text-ink">Performa Tim — {{ selectedPeriodLabel }}</h2>
                <p class="text-xs text-muted">Kontribusi penjualan dan laba bersih per kasir</p>
            </div>
            <DataTable
                :value="salesPerformance"
                dataKey="id_sales"
                :rows="10"
                class="p-datatable-sm"
                stripedRows
                responsiveLayout="scroll"
            >
                <Column field="nama_sales" header="Nama Sales" sortable>
                    <template #body="{ data }">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-subtle border border-hairline text-ink flex items-center justify-center font-bold text-xs">
                                {{ data.nama_sales ? data.nama_sales.charAt(0).toUpperCase() : "S" }}
                            </div>
                            <span class="font-semibold text-xs text-ink">{{ data.nama_sales }}</span>
                        </div>
                    </template>
                </Column>
                <Column field="jumlah_unit" header="Unit Terjual" sortable>
                    <template #body="{ data }">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold tabular-nums bg-subtle hover:bg-hover text-ink border border-hairline"
                            @click="showSalesDetail(data)"
                        >
                            {{ data.jumlah_unit }} Unit
                        </button>
                    </template>
                </Column>
                <Column field="total_revenue" header="Omset Bersih" sortable>
                    <template #body="{ data }">
                        <span class="font-mono tabular-nums text-xs text-ink">
                            {{ formatCurrency(data.total_revenue) }}
                        </span>
                    </template>
                </Column>
                <Column field="total_laba" header="Laba Bersih" sortable>
                    <template #body="{ data }">
                        <span class="font-bold font-mono tabular-nums text-xs text-accent-ink">
                            {{ formatCurrency(data.total_laba) }}
                        </span>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Detailed Sales Table -->
        <div class="bg-surface border border-hairline rounded-2xl p-5 space-y-3">
            <div>
                <h2 class="text-base font-bold text-ink">Detail Penjualan — {{ selectedPeriodLabel }}</h2>
                <p class="text-xs text-muted">Rincian unit terjual pada periode terpilih</p>
            </div>

            <DataTable
                :value="salesDetails"
                dataKey="id_penjualan"
                :paginator="true"
                :rows="10"
                :rowsPerPageOptions="[10, 25, 50]"
                responsiveLayout="scroll"
                sortField="tanggal"
                :sortOrder="-1"
                class="p-datatable-sm"
                stripedRows
            >
                <template #empty>
                    <div class="text-center py-8 text-xs text-muted">
                        Belum ada data penjualan pada periode {{ selectedPeriodLabel }}.
                    </div>
                </template>

                <Column field="no_invoice" header="Faktur" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-semibold text-ink">{{ data.no_invoice || "-" }}</span>
                    </template>
                </Column>

                <Column field="tanggal" header="Tanggal" sortable style="min-width: 7.5rem">
                    <template #body="{ data }">
                        <span class="text-xs text-muted">{{ formatDate(data.tanggal) }}</span>
                    </template>
                </Column>

                <Column field="user.nama" header="Kasir" sortable style="min-width: 8rem">
                    <template #body="{ data }">
                        <span class="text-xs text-muted">{{ data.user?.nama || data.users?.nama || "Petugas" }}</span>
                    </template>
                </Column>

                <Column field="stok_hp.nama" header="Unit" sortable style="min-width: 12rem">
                    <template #body="{ data }">
                        <span class="text-xs font-semibold text-ink">{{ data.stok_hp?.nama || "-" }}</span>
                    </template>
                </Column>

                <Column field="stok_hp.imei" header="IMEI" style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="text-xs font-mono tabular-nums text-muted">{{ data.stok_hp?.imei || "-" }}</span>
                    </template>
                </Column>

                <Column field="harga_jual" header="Netto" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <div>
                            <span class="text-xs font-bold font-mono tabular-nums text-ink block">
                                {{ formatCurrency((Number(data.harga_jual) || 0) - (Number(data.diskon) || 0)) }}
                            </span>
                            <span v-if="data.diskon > 0" class="text-[10px] font-mono tabular-nums text-rose-600 dark:text-rose-400">
                                Diskon -{{ formatCurrency(data.diskon) }}
                            </span>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Sales Detail Modal -->
        <Dialog
            v-model:visible="salesDetailDialog"
            :style="{ width: '780px', maxWidth: '95vw' }"
            :header="'Rincian Penjualan — ' + selectedSales"
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
                <Column field="brand" header="Brand" sortable>
                    <template #body="{ data }">
                        <span class="text-xs font-semibold text-accent-ink uppercase">{{ data.brand }}</span>
                    </template>
                </Column>
                <Column field="tipe_hp" header="Model" sortable>
                    <template #body="{ data }">
                        <span class="text-xs font-semibold text-ink">{{ data.tipe_hp }}</span>
                    </template>
                </Column>
                <Column field="imei" header="IMEI" sortable>
                    <template #body="{ data }">
                        <span class="text-xs font-mono tabular-nums text-muted">{{ data.imei || "-" }}</span>
                    </template>
                </Column>
                <Column field="harga_jual" header="Harga Jual" sortable>
                    <template #body="{ data }">
                        <span class="text-xs font-bold font-mono tabular-nums text-ink">
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
                <Button label="Tutup" icon="pi pi-times" text severity="secondary" @click="salesDetailDialog = false" />
            </template>
        </Dialog>
    </div>
</template>
