<script setup>
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dropdown from "primevue/dropdown";
import ExcelJS from "exceljs";

const reportData = ref(null);
const availableDates = ref([]); // Ensure availableDates is initialized as an array
const salesDetails = ref([]);
const salesPerformance = ref([]);
const selectedMonth = ref(null);
const selectedYear = ref(null);
const loading = ref(false);
const toast = useToast();

// Sales detail modal
const salesDetailDialog = ref(false);
const selectedSales = ref(null);
const selectedSalesItems = ref([]);

//Month Names Mapping
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
        const response = await axios.get("available-dates");
        availableDates.value = Array.isArray(response.data)
            ? response.data
            : [];

        // Auto select latest month & year on initial mount
        if (availableDates.value.length > 0) {
            if (!selectedMonth.value || !selectedYear.value) {
                selectedMonth.value = availableDates.value[0].month;
                selectedYear.value = availableDates.value[0].year;
            }
        } else {
            const now = new Date();
            selectedMonth.value = now.getMonth() + 1;
            selectedYear.value = now.getFullYear();
        }
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
        const response = await axios.get("report", {
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
        const response = await axios.get("sales-details", {
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

// Fetch sales performance
const fetchSalesPerformance = async () => {
    if (!selectedMonth.value || !selectedYear.value) return;

    try {
        const response = await axios.get("sales-performance", {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value,
            },
        });
        salesPerformance.value = response.data;
    } catch (error) {
        console.error("Error fetching sales performance:", error);
    }
};

// Show detail modal when clicking on sales count
const showSalesDetail = (sales) => {
    selectedSales.value = sales.nama_sales;
    selectedSalesItems.value = sales.items || [];
    salesDetailDialog.value = true;
};

const monthOptions = computed(() => {
    let filteredDates = availableDates.value;
    if (selectedYear.value) {
        filteredDates = filteredDates.filter(date => date.year === selectedYear.value);
    }
    return filteredDates
        .map((date) => ({ label: date.month_name, value: date.month }))
        .filter((v, i, a) => a.findIndex((t) => t.value === v.value) === i);
});

const yearOptions = computed(() => {
    let filteredDates = availableDates.value;
    if (selectedMonth.value) {
        filteredDates = filteredDates.filter(date => date.month === selectedMonth.value);
    }
    return filteredDates
        .map((date) => ({ label: date.year.toString(), value: date.year }))
        .filter((v, i, a) => a.findIndex((t) => t.value === v.value) === i);
});

// Computed property for selected month name
const selectedMonthName = computed(() => monthNames[selectedMonth.value]);

// Watch for month or year change and refetch report data and sales details
watch([selectedMonth, selectedYear], () => {
    fetchReportData();
    fetchSalesDetails();
    fetchSalesPerformance();
});

// Export to Excel - improved version
const exportToExcel = async () => {
    if (!salesDetails.value.length && !salesPerformance.value.length) {
        toast.add({
            severity: "warn",
            summary: "Warning",
            detail: "Tidak ada data untuk di-export",
            life: 3000,
        });
        return;
    }

    const monthLabel = selectedMonthName.value || "";
    const yearLabel = selectedYear.value || "";
    const fileName = `Laporan_Penjualan_${monthLabel}_${yearLabel}.xlsx`;

    const workbook = new ExcelJS.Workbook();
    workbook.creator = "Tamboer Gadget";
    workbook.created = new Date();

    // === Sheet 1: Detail Penjualan ===
    if (salesDetails.value.length) {
        const ws1 = workbook.addWorksheet("Detail Penjualan");

        ws1.columns = [
            { header: "No", key: "no", width: 5 },
            { header: "Tanggal", key: "tanggal", width: 14 },
            { header: "Penjual", key: "penjual", width: 20 },
            { header: "Brand", key: "brand", width: 15 },
            { header: "Tipe HP", key: "tipe", width: 30 },
            { header: "Warna", key: "warna", width: 15 },
            { header: "IMEI", key: "imei", width: 20 },
            { header: "Harga Jual", key: "harga", width: 18 },
            { header: "Metode Pembayaran", key: "metode", width: 22 },
        ];

        // Style the header
        ws1.getRow(1).font = { bold: true, color: { argb: "FFFFFFFF" } };
        ws1.getRow(1).fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FF4F81BD" } };
        ws1.getRow(1).alignment = { vertical: "middle", horizontal: "center" };

        let totalHarga = 0;
        salesDetails.value.forEach((item, index) => {
            const harga = Number(item.harga_jual) || 0;
            totalHarga += harga;
            ws1.addRow({
                no: index + 1,
                tanggal: new Date(item.tanggal).toLocaleDateString("id-ID"),
                penjual: item.users?.nama || item.user?.nama || "Unknown",
                brand: item.stok_hp?.brand || "-",
                tipe: item.stok_hp?.nama || "-",
                warna: item.stok_hp?.warna || "-",
                imei: item.stok_hp?.imei || "-",
                harga: harga,
                metode: item.met_pem || "-",
            });
        });

        // Add summary row
        const summaryRow = ws1.addRow({
            imei: "TOTAL",
            harga: totalHarga,
        });
        summaryRow.font = { bold: true };
        summaryRow.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFDCE6F1" } };

        // Format currency column
        ws1.getColumn("harga").numFmt = "#,##0";
    }

    // === Sheet 2: Performa Sales ===
    if (salesPerformance.value.length) {
        const ws2 = workbook.addWorksheet("Performa Sales");

        ws2.columns = [
            { header: "No", key: "no", width: 5 },
            { header: "Nama Sales", key: "nama", width: 25 },
            { header: "Unit Terjual", key: "unit", width: 15 },
            { header: "Total Penjualan", key: "total", width: 20 },
            { header: "Total Laba", key: "laba", width: 20 },
        ];

        ws2.getRow(1).font = { bold: true, color: { argb: "FFFFFFFF" } };
        ws2.getRow(1).fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FF9BBB59" } };
        ws2.getRow(1).alignment = { vertical: "middle", horizontal: "center" };

        let totalUnits = 0;
        let totalRevenue = 0;
        let totalLaba = 0;

        salesPerformance.value.forEach((item, index) => {
            const unit = item.jumlah_unit || 0;
            const revenue = Number(item.total_revenue) || 0;
            const laba = Number(item.total_laba) || 0;
            totalUnits += unit;
            totalRevenue += revenue;
            totalLaba += laba;
            ws2.addRow({
                no: index + 1,
                nama: item.nama_sales,
                unit: unit,
                total: revenue,
                laba: laba,
            });
        });

        const summaryRow = ws2.addRow({
            nama: "TOTAL",
            unit: totalUnits,
            total: totalRevenue,
            laba: totalLaba,
        });
        summaryRow.font = { bold: true };
        summaryRow.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFEBF1DE" } };

        ws2.getColumn("total").numFmt = "#,##0";
        ws2.getColumn("unit").numFmt = "#,##0";
        ws2.getColumn("laba").numFmt = "#,##0";
    }

    // === Sheet 3: Ringkasan ===
    if (reportData.value) {
        const ws3 = workbook.addWorksheet("Ringkasan");

        ws3.columns = [
            { header: "Keterangan", key: "keterangan", width: 25 },
            { header: "Nilai", key: "nilai", width: 20 },
        ];

        ws3.getRow(1).font = { bold: true, color: { argb: "FFFFFFFF" } };
        ws3.getRow(1).fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFF79646" } };

        ws3.addRow({ keterangan: "Unit Terjual", nilai: reportData.value.phones_sold || 0 });
        ws3.addRow({ keterangan: "Total Pemasukan", nilai: reportData.value.total_income || 0 });
        ws3.addRow({ keterangan: "Total Pengeluaran", nilai: reportData.value.total_expenses || 0 });
        
        const labaRow = ws3.addRow({ keterangan: "Laba", nilai: reportData.value.profit || 0 });
        labaRow.font = { bold: true };
        labaRow.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFFDE9D9" } };

        // Format values
        for (let i = 3; i <= 5; i++) {
            ws3.getCell(`B${i}`).numFmt = "#,##0";
        }
        ws3.getCell(`B2`).numFmt = "#,##0";
    }

    // Save via Proxy
    try {
        const buffer = await workbook.xlsx.writeBuffer();
        let binary = '';
        const bytes = new Uint8Array(buffer);
        const len = bytes.byteLength;
        for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        const excelBase64 = window.btoa(binary);

        const pathParts = window.location.pathname.split('/');
        const publicIndex = pathParts.indexOf('public');
        const basePath = publicIndex !== -1 
            ? pathParts.slice(0, publicIndex + 1).join('/') 
            : '';
        const baseUrl = window.location.origin + basePath;

        const form = document.createElement("form");
        form.method = "POST";
        form.action = `${baseUrl}/api/download-excel-proxy`;
        form.enctype = "multipart/form-data";
        form.style.display = "none";

        const filenameInput = document.createElement("input");
        filenameInput.type = "hidden";
        filenameInput.name = "filename";
        filenameInput.value = fileName;
        form.appendChild(filenameInput);

        const dataInput = document.createElement("input");
        dataInput.type = "hidden";
        dataInput.name = "base64";
        dataInput.value = excelBase64;
        form.appendChild(dataInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        toast.add({
            severity: "success",
            summary: "Success",
            detail: `File ${fileName} berhasil di-download`,
            life: 3000,
        });
    } catch (error) {
        console.error("Export error:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Gagal membuat file Excel.",
            life: 3000,
        });
    }
};

// Initial fetch for available dates
onMounted(() => {
    fetchAvailableDates();
});
</script>

<template>
    <div class="p-4 md:p-6 lg:p-8">
        <div class="bg-white border border-[#e7e5df] shadow-sm rounded-2xl p-6 max-w-5xl mx-auto">
            <div class="flex flex-wrap items-center justify-between mb-6 pb-4 border-b border-[#e7e5df]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#1c1917] text-white flex items-center justify-center font-bold">
                        <i class="pi pi-chart-bar text-amber-400"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-[#1c1917] tracking-tight">
                            Laporan Penjualan Bulanan
                        </h2>
                        <p class="text-xs text-[#78716c]">Rekapitulasi omset, pengeluaran modal, dan laba bersih</p>
                    </div>
                </div>
                <button
                    v-if="reportData"
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#1c1917] hover:bg-[#292524] text-white flex items-center gap-2 shadow-sm transition-all mt-2 md:mt-0"
                    @click="exportToExcel"
                >
                    <i class="pi pi-file-excel text-xs text-amber-400"></i>
                    <span>Export Excel</span>
                </button>
            </div>

        <!-- Filters for Month and Year -->
        <div class="flex flex-col sm:flex-row gap-3 mb-5">
            <Dropdown
                v-model="selectedMonth"
                :options="monthOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Pilih Bulan"
                :showClear="true"
                class="w-full"
            />
            <Dropdown
                v-model="selectedYear"
                :options="yearOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Pilih Tahun"
                :showClear="true"
                class="w-full"
            />
        </div>

        <div v-if="loading" class="p-8 text-center text-[#78716c]">
            <i class="pi pi-spin pi-spinner text-2xl text-[#1c1917] mb-2 block"></i>
            <p class="text-xs">Memuat laporan penjualan...</p>
        </div>

        <!-- Executive KPI Cards -->
        <div v-if="reportData" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">
            <div class="p-4 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
                <span class="text-[11px] text-[#78716c] block font-medium uppercase">Unit Terjual</span>
                <span class="text-2xl font-bold font-mono text-[#1c1917] mt-1 block">
                    {{ reportData.phones_sold || 0 }} <span class="text-xs font-normal text-[#78716c]">Barang</span>
                </span>
            </div>

            <div class="p-4 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
                <span class="text-[11px] text-[#78716c] block font-medium uppercase">Total Omset</span>
                <span class="text-2xl font-bold font-mono text-[#1c1917] mt-1 block">
                    {{ formatCurrency(reportData.total_income) }}
                </span>
            </div>

            <div class="p-4 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
                <span class="text-[11px] text-[#78716c] block font-medium uppercase">Modal Terjual (HPP)</span>
                <span class="text-2xl font-bold font-mono text-amber-800 mt-1 block">
                    {{ formatCurrency(reportData.total_expenses) }}
                </span>
            </div>

            <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200 dark:bg-amber-950/40 dark:border-amber-800/40">
                <span class="text-[11px] text-amber-800 dark:text-amber-400 block font-medium uppercase">Laba Bersih</span>
                <span class="text-2xl font-bold font-mono text-amber-800 dark:text-amber-400 mt-1 block">
                    {{ formatCurrency(reportData.profit) }}
                </span>
            </div>
        </div>

        <!-- Sales Performance Table -->
        <template v-if="salesPerformance.length > 0">
            <h3 class="text-base font-bold mt-6 mb-3 text-[#1c1917]">
                <i class="pi pi-users mr-1.5 text-amber-800"></i>
                Performa Sales — {{ selectedMonthName }} {{ selectedYear }}
            </h3>
            <DataTable
                :value="salesPerformance"
                dataKey="id_sales"
                :rows="10"
                class="p-datatable-sm"
                stripedRows
                responsiveLayout="scroll"
            >
                <Column field="nama_sales" header="Nama Sales" sortable>
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <div class="flex items-center justify-center bg-[#f0ede6] text-[#1c1917] rounded-lg font-bold text-xs" style="width: 1.75rem; height: 1.75rem">
                                {{ slotProps.data.nama_sales ? slotProps.data.nama_sales.charAt(0).toUpperCase() : 'S' }}
                            </div>
                            <span class="font-semibold text-xs text-[#1c1917]">{{ slotProps.data.nama_sales }}</span>
                        </div>
                    </template>
                </Column>
                <Column field="jumlah_unit" header="Unit Terjual" sortable>
                    <template #body="slotProps">
                        <button
                            type="button"
                            class="px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-[#f0ede6] hover:bg-[#e7e5df] text-[#1c1917] border border-[#e7e5df]"
                            @click="showSalesDetail(slotProps.data)"
                            v-tooltip.top="'Klik untuk lihat detail'"
                        >
                            {{ slotProps.data.jumlah_unit }} Unit
                        </button>
                    </template>
                </Column>
                <Column field="total_revenue" header="Total Penjualan" sortable>
                    <template #body="slotProps">
                        <span class="font-medium font-mono text-xs text-[#1c1917]">
                            {{ formatCurrency(Number(slotProps.data.total_revenue)) }}
                        </span>
                    </template>
                </Column>
                <Column field="total_laba" header="Laba" sortable>
                    <template #body="slotProps">
                        <span class="font-bold font-mono text-xs text-amber-800 dark:text-amber-400">
                            {{ formatCurrency(Number(slotProps.data.total_laba)) }}
                        </span>
                    </template>
                </Column>
            </DataTable>
        </template>

        <!-- Detailed Sales Data Table -->
        <h3
            v-if="reportData"
            :value="[reportData]"
            dataKey="month"
            class="text-base font-bold mt-8 mb-3 text-[#1c1917]"
        >
            Detail Penjualan Pada {{ selectedMonthName }} {{ selectedYear }}
        </h3>
        <div
            v-if="salesDetails.length"
            :value="salesDetails"
            dataKey="id"
        >
            <DataTable
                ref="dt"
                :value="salesDetails"
                dataKey="id"
                :paginator="true"
                :rows="10"
                responsiveLayout="scroll"
                scrollable
                scrollHeight="400px"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} penjualan"
                sortField="tanggal"
                :sortOrder="-1"
                class="p-datatable-sm"
                stripedRows
            >
                <!-- Tanggal Terjual -->
                <Column field="tanggal" header="Tanggal Terjual" sortable>
                    <template #body="slotProps">
                        <span class="text-xs text-[#44403c]">{{
                            new Date(slotProps.data.tanggal).toLocaleDateString(
                                "id-ID",
                            )
                        }}</span>
                    </template>
                </Column>

                <!-- Sales (Penjual) -->
                <Column field="users.nama" header="Penjual" sortable>
                    <template #body="slotProps">
                        <span class="text-xs text-[#44403c]">{{ slotProps.data.users?.nama || slotProps.data.user?.nama || "Unknown" }}</span>
                    </template>
                </Column>

                <!-- Stocks Details -->
                <Column field="stok_hp.nama" header="Tipe HP" sortable>
                    <template #body="slotProps">
                        <span class="text-xs font-semibold text-[#1c1917]">{{ slotProps.data.stok_hp?.nama || "-" }}</span>
                    </template>
                </Column>
                <Column field="stok_hp.warna" header="Warna" sortable>
                    <template #body="slotProps">
                        <span class="text-xs text-[#78716c]">{{ slotProps.data.stok_hp?.warna || "-" }}</span>
                    </template>
                </Column>
                <Column field="stok_hp.imei" header="IMEI" sortable>
                    <template #body="slotProps">
                        <span class="text-xs font-mono text-[#78716c]">{{ slotProps.data.stok_hp?.imei || "-" }}</span>
                    </template>
                </Column>

                <!-- Harga Jual -->
                <Column field="harga_jual" header="Harga Jual" sortable>
                    <template #body="slotProps">
                        <span class="text-xs font-bold font-mono text-[#1c1917]">{{ formatCurrency(slotProps.data.harga_jual) }}</span>
                    </template>
                </Column>
            </DataTable>
        </div>

            <p v-else-if="!reportData" class="text-center text-[#78716c] mt-6 text-xs">
                Pilih bulan dan tahun untuk melihat laporan.
            </p>
        </div>

        <!-- Sales Detail Modal -->
        <Dialog
            v-model:visible="salesDetailDialog"
            :style="{ width: '750px', maxWidth: '95vw' }"
            :header="'Detail Penjualan — ' + selectedSales"
            :modal="true"
            :dismissableMask="true"
            class="rounded-2xl bg-white"
        >
            <DataTable
                :value="selectedSalesItems"
                dataKey="imei"
                :rows="10"
                :paginator="selectedSalesItems.length > 10"
                class="p-datatable-sm"
                stripedRows
                scrollable
                scrollHeight="400px"
            >
                <Column field="tanggal" header="Tanggal" sortable style="min-width: 7rem">
                    <template #body="slotProps">
                        <span class="text-xs text-[#44403c]">{{ new Date(slotProps.data.tanggal).toLocaleDateString("id-ID") }}</span>
                    </template>
                </Column>
                <Column field="brand" header="Brand" sortable>
                    <template #body="slotProps">
                        <span class="text-xs font-semibold text-amber-800 uppercase">{{ slotProps.data.brand }}</span>
                    </template>
                </Column>
                <Column field="tipe_hp" header="Tipe HP" sortable>
                    <template #body="slotProps">
                        <span class="text-xs font-semibold text-[#1c1917]">{{ slotProps.data.tipe_hp }}</span>
                    </template>
                </Column>
                <Column field="warna" header="Warna" sortable>
                    <template #body="slotProps">
                        <span class="text-xs text-[#78716c]">{{ slotProps.data.warna }}</span>
                    </template>
                </Column>
                <Column field="imei" header="IMEI" sortable>
                    <template #body="slotProps">
                        <span class="text-xs font-mono text-[#78716c]">{{ slotProps.data.imei }}</span>
                    </template>
                </Column>
                <Column field="harga_jual" header="Harga Jual" sortable>
                    <template #body="slotProps">
                        <span class="text-xs font-bold font-mono text-[#1c1917]">{{ formatCurrency(Number(slotProps.data.harga_jual)) }}</span>
                    </template>
                </Column>
                <Column field="met_pem" header="Metode" sortable>
                    <template #body="slotProps">
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-[#f0ede6] text-[#44403c]">{{ slotProps.data.met_pem }}</span>
                    </template>
                </Column>
            </DataTable>
            <template #footer>
                <Button
                    label="Tutup"
                    icon="pi pi-times"
                    text
                    @click="salesDetailDialog = false"
                />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
/* Scoped styles if needed */
</style>
