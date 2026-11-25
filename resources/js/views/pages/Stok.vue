<script setup>
import { FilterMatchMode } from "@primevue/core/api";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { computed, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import ExcelJS from "exceljs";
import BarcodeScanner from "../../components/BarcodeScanner.vue";
import { useAuth } from "@/composables/useAuth";
import { downloadExcelBuffer, extractApiError, formatCurrency } from "@/utils/formatters";

const router = useRouter();
const { user, isOwner } = useAuth();
const toast = useToast();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const products = ref([]);
const isLoading = ref(false);
const isSaving = ref(false);

const selectedStatus = ref("ada");
const selectedCategory = ref("all");
const selectedCondition = ref("all");

const dt = ref();
const productDialog = ref(false);
const deleteProductDialog = ref(false);
const product = ref({});
const submitted = ref(false);
const imeiSearching = ref(false);

const fetchProducts = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get("stocks", {
            params: { status: selectedStatus.value },
        });
        products.value = response.data;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Memuat",
            detail: extractApiError(error, "Gagal mengambil data inventaris."),
            life: 4000,
        });
    } finally {
        isLoading.value = false;
    }
};

watch(selectedStatus, () => {
    fetchProducts();
});

onMounted(() => {
    fetchProducts();
});

const filteredData = computed(() => {
    let list = products.value;

    if (selectedCategory.value !== "all") {
        list = list.filter((p) => p.kategori === selectedCategory.value);
    }

    if (selectedCondition.value !== "all") {
        list = list.filter((p) => (p.kondisi || "baru") === selectedCondition.value);
    }

    const query = filters.value.global.value?.toLowerCase().trim();
    if (query) {
        list = list.filter((item) => {
            return (
                (item.nama || "").toLowerCase().includes(query) ||
                (item.brand || "").toLowerCase().includes(query) ||
                (item.imei || "").toLowerCase().includes(query) ||
                (item.warna || "").toLowerCase().includes(query) ||
                (item.ram || "").toLowerCase().includes(query) ||
                (item.storage || "").toLowerCase().includes(query) ||
                (item.sales?.nama || "").toLowerCase().includes(query)
            );
        });
    }

    return list;
});

const stockMetrics = computed(() => {
    const ready = products.value.filter((p) => p.status === "ada");
    const totalAsset = ready.reduce((acc, p) => acc + (parseFloat(p.harga_masuk) || 0), 0);
    return {
        totalReady: ready.length,
        gadgetReady: ready.filter((p) => p.kategori === "gadget").length,
        aksesorisReady: ready.filter((p) => p.kategori === "aksesoris").length,
        totalAssetValue: totalAsset,
    };
});

const liveMargin = computed(() => {
    const masuk = parseFloat(product.value.harga_masuk) || 0;
    const jual = parseFloat(product.value.harga_jual) || 0;
    const profit = jual - masuk;
    const pct = masuk > 0 ? Math.round((profit / masuk) * 100) : null;
    return { profit, pct };
});

const copyImei = (imei) => {
    if (!imei) return;
    navigator.clipboard.writeText(imei);
    toast.add({ severity: "info", summary: "Tersalin", detail: `IMEI ${imei} disalin`, life: 2000 });
};

const openNew = () => {
    product.value = {
        tanggal: new Date().toISOString().slice(0, 10),
        kategori: "gadget",
        kondisi: "baru",
        warna: "-",
        brand: "",
        nama: "",
        imei: "",
        ram: "",
        storage: "",
        kelengkapan: "Fullset",
        garansi: "Garansi Toko 1 Bulan",
        harga_masuk: null,
        harga_jual: null,
        catatan: "",
        status: "ada",
    };
    submitted.value = false;
    productDialog.value = true;
};

const editProduct = (prod) => {
    product.value = {
        ...prod,
        kategori: prod.kategori || "gadget",
        kondisi: prod.kondisi || "baru",
    };
    submitted.value = false;
    productDialog.value = true;
};

const saveProduct = async () => {
    submitted.value = true;

    const missingOwnerCost = isOwner.value && (product.value.harga_masuk === null || product.value.harga_masuk === "");
    if (!product.value.nama?.trim() || !product.value.brand?.trim() || product.value.harga_jual === null || product.value.harga_jual === "" || missingOwnerCost) {
        toast.add({
            severity: "warn",
            summary: "Data Belum Lengkap",
            detail: "Pastikan brand, nama barang, dan harga sudah diisi.",
            life: 3000,
        });
        return;
    }

    isSaving.value = true;
    const payload = {
        tanggal: product.value.tanggal,
        imei: product.value.imei ? product.value.imei.trim() : null,
        brand: product.value.brand.trim(),
        kategori: product.value.kategori || "gadget",
        nama: product.value.nama.trim(),
        warna: product.value.warna || "-",
        ram: product.value.ram || null,
        storage: product.value.storage || null,
        kondisi: product.value.kondisi || "baru",
        kelengkapan: product.value.kelengkapan || null,
        garansi: product.value.garansi || null,
        harga_jual: parseFloat(product.value.harga_jual) || 0,
        id_sales: product.value.id_sales || user.value?.id_user || null,
        status: product.value.status || "ada",
        catatan: product.value.catatan || null,
    };

    if (isOwner.value) {
        payload.harga_masuk = parseFloat(product.value.harga_masuk) || 0;
    }

    try {
        if (product.value.id_stock) {
            await axios.put(`stocks/${product.value.id_stock}`, payload);
            toast.add({ severity: "success", summary: "Berhasil", detail: "Data stok diperbarui.", life: 3000 });
        } else {
            await axios.post("stocks", payload);
            toast.add({ severity: "success", summary: "Berhasil", detail: "Stok baru ditambahkan.", life: 3000 });
        }
        productDialog.value = false;
        product.value = {};
        await fetchProducts();
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Simpan",
            detail: extractApiError(error, "Gagal menyimpan data stok."),
            life: 4000,
        });
    } finally {
        isSaving.value = false;
    }
};

const confirmDeleteProduct = (prod) => {
    product.value = prod;
    deleteProductDialog.value = true;
};

const executeDeleteProduct = async () => {
    try {
        await axios.delete(`stocks/${product.value.id_stock}`);
        toast.add({ severity: "success", summary: "Dihapus", detail: "Data stok dihapus.", life: 3000 });
        deleteProductDialog.value = false;
        product.value = {};
        await fetchProducts();
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Hapus",
            detail: extractApiError(error, "Gagal menghapus stok."),
            life: 4000,
        });
    }
};

const handleImeiScanned = async (scannedImei) => {
    const clean = (scannedImei || "").replace(/\D/g, "");
    if (!clean || clean.length < 8) return;
    if (product.value.id_stock) return;

    imeiSearching.value = true;
    try {
        const tacRes = await axios.get(`stocks/lookup-tac/${clean}`);
        if (tacRes.data && tacRes.data.found) {
            const data = tacRes.data;
            product.value.brand = data.brand || product.value.brand;
            product.value.nama = data.nama || product.value.nama;
            if (data.storage) product.value.storage = data.storage;
            if (data.ram) product.value.ram = data.ram;
            if (data.warna && data.warna !== "-") product.value.warna = data.warna;
            if (data.kategori) product.value.kategori = data.kategori;

            const sourceLabel = data.source === "tac_dictionary" ? "Database TAC" : "Riwayat Stok";
            toast.add({
                severity: "success",
                summary: `TAC Teridentifikasi (${sourceLabel})`,
                detail: `${data.brand} ${data.nama} ${data.storage || ""} diisi otomatis.`,
                life: 3500,
            });
            return;
        }

        const exactRes = await axios.get(`stocks/search-imei/${clean}`);
        if (exactRes.data && exactRes.data.id_stock) {
            product.value.brand = exactRes.data.brand || product.value.brand;
            product.value.nama = exactRes.data.nama || product.value.nama;
            product.value.warna = exactRes.data.warna || product.value.warna;
            product.value.harga_jual = exactRes.data.harga_jual || product.value.harga_jual;
            if (exactRes.data.storage) product.value.storage = exactRes.data.storage;
            if (exactRes.data.ram) product.value.ram = exactRes.data.ram;

            toast.add({
                severity: "success",
                summary: "Unit Terdaftar Ditemukan",
                detail: `Spesifikasi ${exactRes.data.nama} diisi otomatis.`,
                life: 3000,
            });
            return;
        }

        toast.add({
            severity: "info",
            summary: "TAC Baru",
            detail: `TAC ${clean.substring(0, 8)} belum terdaftar. Isi detail sekali, sistem akan menyimpannya otomatis.`,
            life: 4000,
        });
    } catch {
        // Ignore lookup error for new TAC
    } finally {
        imeiSearching.value = false;
    }
};

const sellDirect = (prod) => {
    router.push({
        name: "penjualan",
        query: { addStock: prod.id_stock },
    });
};

const exportToExcel = async () => {
    if (!filteredData.value.length) {
        toast.add({ severity: "warn", summary: "Peringatan", detail: "Tidak ada data stok untuk di-export.", life: 2500 });
        return;
    }

    try {
        const workbook = new ExcelJS.Workbook();
        workbook.creator = "Tamboer Gadget";
        const ws = workbook.addWorksheet("Data Stok");

        const columns = [
            { header: "No", key: "no", width: 5 },
            { header: "Tanggal", key: "tanggal", width: 13 },
            { header: "Kategori", key: "kategori", width: 13 },
            { header: "Brand", key: "brand", width: 14 },
            { header: "Nama Barang", key: "nama", width: 28 },
            { header: "Kondisi", key: "kondisi", width: 12 },
            { header: "Warna", key: "warna", width: 13 },
            { header: "RAM / Storage", key: "specs", width: 15 },
            { header: "IMEI", key: "imei", width: 18 },
        ];

        if (isOwner.value) {
            columns.push({ header: "Harga Masuk", key: "harga_masuk", width: 16 });
        }

        columns.push(
            { header: "Harga Jual", key: "harga_jual", width: 16 },
            { header: "Status", key: "status", width: 11 },
            { header: "Penanggungjawab", key: "sales", width: 16 }
        );

        ws.columns = columns;
        ws.getRow(1).font = { bold: true, color: { argb: "FFFFFFFF" } };
        ws.getRow(1).fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FF18181B" } };

        filteredData.value.forEach((item, idx) => {
            const row = {
                no: idx + 1,
                tanggal: item.tanggal,
                kategori: (item.kategori || "gadget").toUpperCase(),
                brand: item.brand,
                nama: item.nama,
                kondisi: (item.kondisi || "baru").toUpperCase(),
                warna: item.warna,
                specs: [item.ram, item.storage].filter(Boolean).join(" / "),
                imei: item.imei || "-",
                harga_jual: Number(item.harga_jual) || 0,
                status: item.status === "ada" ? "READY" : "TERJUAL",
                sales: item.sales?.nama || "-",
            };
            if (isOwner.value) {
                row.harga_masuk = Number(item.harga_masuk) || 0;
            }
            ws.addRow(row);
        });

        if (isOwner.value) {
            ws.getColumn("harga_masuk").numFmt = "#,##0";
        }
        ws.getColumn("harga_jual").numFmt = "#,##0";

        const buffer = await workbook.xlsx.writeBuffer();
        downloadExcelBuffer(buffer, `Stok_Tamboer_${new Date().toISOString().slice(0, 10)}.xlsx`);

        toast.add({ severity: "success", summary: "Berhasil", detail: "File Excel berhasil diunduh.", life: 3000 });
    } catch {
        toast.add({ severity: "error", summary: "Gagal", detail: "Gagal membuat file Excel.", life: 3000 });
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header & Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">
                    Katalog &amp; Inventaris
                </h1>
                <p class="text-xs sm:text-sm text-muted mt-0.5">
                    Pencatatan stok smartphone, aksesoris, dan suku cadang
                </p>
            </div>

            <div class="flex items-center flex-wrap gap-2">
                <button
                    type="button"
                    class="min-h-[38px] px-4 py-2 rounded-xl text-xs font-semibold bg-ink text-surface hover:opacity-90 flex items-center gap-2 transition-opacity"
                    @click="openNew"
                >
                    <i class="pi pi-plus text-xs"></i>
                    <span>Tambah Stok</span>
                </button>
                <button
                    type="button"
                    class="min-h-[38px] px-3.5 py-2 rounded-xl text-xs font-semibold bg-surface border border-hairline hover:bg-subtle text-ink flex items-center gap-2 transition-colors"
                    @click="exportToExcel"
                >
                    <i class="pi pi-file-excel text-xs text-accent"></i>
                    <span>Export Excel</span>
                </button>
                <button
                    type="button"
                    class="w-9 h-9 rounded-xl bg-surface border border-hairline hover:bg-subtle text-muted hover:text-ink flex items-center justify-center transition-colors"
                    aria-label="Muat ulang stok"
                    @click="fetchProducts"
                >
                    <i class="pi pi-refresh text-xs" :class="isLoading ? 'pi-spin' : ''"></i>
                </button>
            </div>
        </div>

        <!-- Summary Strip -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
            <div class="bg-surface border border-hairline rounded-2xl p-4">
                <span class="text-xs text-muted font-medium block">Total Ready</span>
                <span class="text-xl font-bold font-mono tabular-nums text-ink mt-1 block">
                    {{ stockMetrics.totalReady }} Item
                </span>
            </div>
            <div class="bg-surface border border-hairline rounded-2xl p-4">
                <span class="text-xs text-muted font-medium block">Smartphone Ready</span>
                <span class="text-xl font-bold font-mono tabular-nums text-ink mt-1 block">
                    {{ stockMetrics.gadgetReady }} Unit
                </span>
            </div>
            <div class="bg-surface border border-hairline rounded-2xl p-4">
                <span class="text-xs text-muted font-medium block">Aksesoris Ready</span>
                <span class="text-xl font-bold font-mono tabular-nums text-ink mt-1 block">
                    {{ stockMetrics.aksesorisReady }} Pcs
                </span>
            </div>
            <div v-if="isOwner" class="bg-surface border border-hairline rounded-2xl p-4">
                <span class="text-xs text-muted font-medium block">Valuasi Modal Etalase</span>
                <span class="text-xl font-bold font-mono tabular-nums text-accent-ink mt-1 block">
                    {{ formatCurrency(stockMetrics.totalAssetValue) }}
                </span>
            </div>
        </div>

        <!-- Unified Filter & Search Toolbar -->
        <div class="bg-surface border border-hairline rounded-2xl p-4 space-y-3">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Status Segment -->
                    <div class="inline-flex p-0.5 rounded-xl bg-subtle border border-hairline">
                        <button
                            v-for="st in [
                                { id: 'ada', label: 'Ready' },
                                { id: 'terjual', label: 'Terjual' },
                                { id: 'all', label: 'Semua' },
                            ]"
                            :key="st.id"
                            type="button"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                            :class="selectedStatus === st.id ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink'"
                            @click="selectedStatus = st.id"
                        >
                            {{ st.label }}
                        </button>
                    </div>

                    <!-- Category Filter -->
                    <div class="flex items-center gap-1 overflow-x-auto">
                        <button
                            v-for="cat in [
                                { id: 'all', label: 'Semua Kategori' },
                                { id: 'gadget', label: 'Smartphone' },
                                { id: 'aksesoris', label: 'Aksesoris' },
                                { id: 'sparepart', label: 'Sparepart' },
                            ]"
                            :key="cat.id"
                            type="button"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors"
                            :class="selectedCategory === cat.id ? 'bg-ink text-surface' : 'bg-subtle text-muted hover:text-ink'"
                            @click="selectedCategory = cat.id"
                        >
                            {{ cat.label }}
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex p-0.5 rounded-xl bg-subtle border border-hairline">
                        <button
                            v-for="cond in [
                                { id: 'all', label: 'Semua' },
                                { id: 'baru', label: 'Baru' },
                                { id: 'second', label: 'Second' },
                            ]"
                            :key="cond.id"
                            type="button"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                            :class="selectedCondition === cond.id ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink'"
                            @click="selectedCondition = cond.id"
                        >
                            {{ cond.label }}
                        </button>
                    </div>

                    <div class="relative flex-1 sm:w-64">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-tertiary text-xs pointer-events-none"></i>
                        <input
                            v-model="filters['global'].value"
                            type="text"
                            placeholder="Cari model, brand, IMEI..."
                            class="w-full min-h-[38px] text-xs pl-8 pr-3.5 py-2 rounded-xl bg-canvas border border-hairline text-ink placeholder:text-tertiary focus:outline-none focus:border-accent"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="bg-surface border border-hairline rounded-2xl p-5">
            <DataTable
                ref="dt"
                :value="filteredData"
                :paginator="true"
                :rows="15"
                :rowsPerPageOptions="[15, 30, 50]"
                dataKey="id_stock"
                responsiveLayout="scroll"
                stripedRows
                class="p-datatable-sm"
            >
                <template #empty>
                    <div class="text-center py-10 space-y-2">
                        <p class="text-sm font-medium text-muted">Tidak ada unit stok yang sesuai dengan filter.</p>
                        <button
                            type="button"
                            class="text-xs font-semibold text-accent-ink hover:underline"
                            @click="openNew"
                        >
                            Tambah unit baru ke inventaris
                        </button>
                    </div>
                </template>

                <Column header="Unit / Barang" sortable field="nama" style="min-width: 14rem">
                    <template #body="{ data }">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-accent-ink">{{ data.brand }}</span>
                                <span class="text-[10px] font-semibold uppercase px-1.5 py-0.5 rounded bg-subtle text-muted border border-hairline">
                                    {{ data.kondisi || "Baru" }}
                                </span>
                            </div>
                            <span class="font-bold text-xs text-ink block mt-0.5">{{ data.nama }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="Spesifikasi" style="min-width: 12rem">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-1 text-[11px]">
                            <span v-if="data.ram || data.storage" class="px-1.5 py-0.5 rounded bg-canvas text-ink font-mono border border-hairline">
                                {{ [data.ram, data.storage].filter(Boolean).join("/") }}
                            </span>
                            <span v-if="data.warna && data.warna !== '-'" class="px-1.5 py-0.5 rounded bg-canvas text-muted border border-hairline">
                                {{ data.warna }}
                            </span>
                            <span v-if="data.garansi" class="px-1.5 py-0.5 rounded bg-canvas text-muted border border-hairline">
                                {{ data.garansi }}
                            </span>
                        </div>
                    </template>
                </Column>

                <Column header="IMEI / SN" style="min-width: 11rem">
                    <template #body="{ data }">
                        <div v-if="data.imei" class="flex items-center gap-1 font-mono tabular-nums text-xs text-muted">
                            <span>{{ data.imei }}</span>
                            <button
                                type="button"
                                class="w-7 h-7 rounded-md text-tertiary hover:text-ink hover:bg-subtle flex items-center justify-center transition-colors"
                                aria-label="Salin IMEI"
                                v-tooltip.top="'Salin IMEI'"
                                @click="copyImei(data.imei)"
                            >
                                <i class="pi pi-copy text-[11px]"></i>
                            </button>
                        </div>
                        <span v-else class="text-xs text-tertiary italic">Tanpa IMEI</span>
                    </template>
                </Column>

                <Column v-if="isOwner" field="harga_masuk" header="Modal" sortable style="min-width: 8rem">
                    <template #body="{ data }">
                        <span class="text-xs text-muted font-mono tabular-nums">
                            {{ formatCurrency(data.harga_masuk) }}
                        </span>
                    </template>
                </Column>

                <Column field="harga_jual" header="Harga Jual" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="text-xs font-bold font-mono tabular-nums text-ink">
                            {{ formatCurrency(data.harga_jual) }}
                        </span>
                    </template>
                </Column>

                <Column field="status" header="Status" sortable style="min-width: 7rem">
                    <template #body="{ data }">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border"
                            :class="
                                data.status === 'ada'
                                    ? 'bg-accent-soft text-accent-ink border-amber-500/30'
                                    : 'bg-subtle text-muted border-hairline'
                            "
                        >
                            {{ data.status === "ada" ? "Ready" : "Terjual" }}
                        </span>
                    </template>
                </Column>

                <Column field="sales.nama" header="Petugas" sortable style="min-width: 8rem">
                    <template #body="{ data }">
                        <span class="text-xs text-muted">{{ data.sales?.nama || "Petugas" }}</span>
                    </template>
                </Column>

                <Column header="Aksi" style="min-width: 8rem" :exportable="false">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                class="w-8 h-8 rounded-lg bg-subtle text-ink hover:bg-ink hover:text-surface flex items-center justify-center transition-colors disabled:opacity-35 disabled:cursor-not-allowed"
                                aria-label="Jual di kasir POS"
                                v-tooltip.top="'Jual via POS'"
                                :disabled="data.status === 'terjual'"
                                @click="sellDirect(data)"
                            >
                                <i class="pi pi-shopping-cart text-xs"></i>
                            </button>

                            <button
                                v-if="isOwner"
                                type="button"
                                class="w-8 h-8 rounded-lg bg-subtle text-muted hover:text-ink hover:bg-hover flex items-center justify-center transition-colors"
                                aria-label="Edit unit"
                                v-tooltip.top="'Edit Data'"
                                @click="editProduct(data)"
                            >
                                <i class="pi pi-pencil text-xs"></i>
                            </button>

                            <button
                                v-if="isOwner"
                                type="button"
                                class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-colors"
                                aria-label="Hapus unit"
                                v-tooltip.top="'Hapus Unit'"
                                @click="confirmDeleteProduct(data)"
                            >
                                <i class="pi pi-trash text-xs"></i>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Add / Edit Product Dialog -->
        <Dialog
            v-model:visible="productDialog"
            modal
            :header="product.id_stock ? 'Perbarui Data Stok' : 'Tambah Stok Unit Baru'"
            :style="{ width: '600px', maxWidth: '95vw' }"
        >
            <div class="space-y-4 pt-2 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <span class="block font-semibold text-muted mb-1.5">Kategori</span>
                        <div class="grid grid-cols-3 gap-1 bg-subtle p-1 rounded-xl border border-hairline">
                            <button
                                v-for="c in ['gadget', 'aksesoris', 'sparepart']"
                                :key="c"
                                type="button"
                                class="py-1.5 rounded-lg text-xs font-semibold capitalize transition-colors"
                                :class="product.kategori === c ? 'bg-ink text-surface' : 'text-muted hover:text-ink'"
                                @click="product.kategori = c"
                            >
                                {{ c }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <span class="block font-semibold text-muted mb-1.5">Kondisi</span>
                        <div class="grid grid-cols-3 gap-1 bg-subtle p-1 rounded-xl border border-hairline">
                            <button
                                v-for="k in ['baru', 'second', 'refurbished']"
                                :key="k"
                                type="button"
                                class="py-1.5 rounded-lg text-xs font-semibold capitalize transition-colors"
                                :class="product.kondisi === k ? 'bg-ink text-surface' : 'text-muted hover:text-ink'"
                                @click="product.kondisi = k"
                            >
                                {{ k }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="prod-brand" class="block font-semibold text-muted mb-1">Brand</label>
                        <input
                            id="prod-brand"
                            v-model.trim="product.brand"
                            type="text"
                            placeholder="Apple, Samsung..."
                            class="w-full min-h-[40px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink focus:outline-none focus:border-accent"
                        />
                        <small v-if="submitted && !product.brand" class="text-rose-600 dark:text-rose-400 mt-1 block">Brand wajib diisi.</small>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="prod-nama" class="block font-semibold text-muted mb-1">Nama / Model Barang</label>
                        <input
                            id="prod-nama"
                            v-model.trim="product.nama"
                            type="text"
                            placeholder="iPhone 15 Pro Max"
                            class="w-full min-h-[40px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink focus:outline-none focus:border-accent"
                        />
                        <small v-if="submitted && !product.nama" class="text-rose-600 dark:text-rose-400 mt-1 block">Nama barang wajib diisi.</small>
                    </div>
                </div>

                <div>
                    <span class="block font-semibold text-muted mb-1">
                        Nomor IMEI / Serial Number
                        <span v-if="product.kategori === 'aksesoris'" class="text-tertiary font-normal">(Opsional)</span>
                    </span>
                    <BarcodeScanner v-model="product.imei" @scanned="handleImeiScanned" />
                    <small v-if="imeiSearching" class="text-accent-ink mt-1 block">
                        <i class="pi pi-spin pi-spinner mr-1"></i>Mencari kecocokan TAC IMEI...
                    </small>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="prod-ram" class="block font-semibold text-muted mb-1">RAM</label>
                        <input
                            id="prod-ram"
                            v-model.trim="product.ram"
                            type="text"
                            placeholder="8GB"
                            class="w-full min-h-[40px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink focus:outline-none focus:border-accent"
                        />
                    </div>
                    <div>
                        <label for="prod-storage" class="block font-semibold text-muted mb-1">Storage</label>
                        <input
                            id="prod-storage"
                            v-model.trim="product.storage"
                            type="text"
                            placeholder="256GB"
                            class="w-full min-h-[40px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink focus:outline-none focus:border-accent"
                        />
                    </div>
                    <div>
                        <label for="prod-warna" class="block font-semibold text-muted mb-1">Warna</label>
                        <input
                            id="prod-warna"
                            v-model.trim="product.warna"
                            type="text"
                            placeholder="Black Titanium"
                            class="w-full min-h-[40px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink focus:outline-none focus:border-accent"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="prod-kelengkapan" class="block font-semibold text-muted mb-1">Kelengkapan</label>
                        <input
                            id="prod-kelengkapan"
                            v-model.trim="product.kelengkapan"
                            type="text"
                            placeholder="Fullset"
                            class="w-full min-h-[40px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink focus:outline-none focus:border-accent"
                        />
                    </div>
                    <div>
                        <label for="prod-garansi" class="block font-semibold text-muted mb-1">Garansi</label>
                        <input
                            id="prod-garansi"
                            v-model.trim="product.garansi"
                            type="text"
                            placeholder="Garansi Toko 1 Bulan"
                            class="w-full min-h-[40px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink focus:outline-none focus:border-accent"
                        />
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-canvas border border-hairline space-y-2">
                    <div :class="isOwner ? 'grid grid-cols-2 gap-3' : ''">
                        <div v-if="isOwner">
                            <label for="prod-modal" class="block font-semibold text-muted mb-1">Harga Masuk (Modal)</label>
                            <input
                                id="prod-modal"
                                v-model.number="product.harga_masuk"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="w-full min-h-[40px] text-sm font-bold font-mono tabular-nums px-3 py-2 rounded-xl bg-surface border border-hairline text-ink focus:outline-none focus:border-accent"
                            />
                        </div>
                        <div>
                            <label for="prod-jual" class="block font-semibold text-ink mb-1">Harga Jual (Toko)</label>
                            <input
                                id="prod-jual"
                                v-model.number="product.harga_jual"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="w-full min-h-[40px] text-sm font-bold font-mono tabular-nums px-3 py-2 rounded-xl bg-surface border border-hairline text-ink focus:outline-none focus:border-accent"
                            />
                        </div>
                    </div>
                    <div v-if="isOwner && product.harga_jual > 0" class="text-xs text-muted flex items-center justify-between pt-1">
                        <span>Estimasi Margin</span>
                        <span class="font-mono tabular-nums font-semibold" :class="liveMargin.profit >= 0 ? 'text-accent-ink' : 'text-rose-600 dark:text-rose-400'">
                            {{ formatCurrency(liveMargin.profit) }}
                            <template v-if="liveMargin.pct !== null">({{ liveMargin.pct }}%)</template>
                        </span>
                    </div>
                </div>

                <div>
                    <label for="prod-catatan" class="block font-semibold text-muted mb-1">Catatan Unit</label>
                    <textarea
                        id="prod-catatan"
                        v-model.trim="product.catatan"
                        rows="2"
                        placeholder="Battery health, kondisi fisik, kelengkapan tambahan..."
                        class="w-full text-sm p-3 rounded-xl bg-canvas border border-hairline text-ink focus:outline-none focus:border-accent resize-none"
                    ></textarea>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Batal" text severity="secondary" @click="productDialog = false" />
                    <Button label="Simpan Unit" icon="pi pi-check" :loading="isSaving" @click="saveProduct" />
                </div>
            </template>
        </Dialog>

        <!-- Delete Confirm Dialog -->
        <Dialog
            v-model:visible="deleteProductDialog"
            header="Konfirmasi Hapus"
            modal
            :style="{ width: '420px', maxWidth: '90vw' }"
        >
            <div class="flex items-start gap-3 pt-2">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <i class="pi pi-exclamation-triangle text-lg"></i>
                </div>
                <div>
                    <p class="font-bold text-sm text-ink">Hapus barang ini?</p>
                    <p class="text-xs text-muted mt-1 leading-relaxed">
                        <b>{{ product.nama }}</b> akan dihapus permanen dari etalase stok toko.
                    </p>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Batal" text severity="secondary" @click="deleteProductDialog = false" />
                    <Button label="Hapus" severity="danger" @click="executeDeleteProduct" />
                </div>
            </template>
        </Dialog>
    </div>
</template>
