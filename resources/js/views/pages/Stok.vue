<script setup>
import { FilterMatchMode } from "@primevue/core/api";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, computed } from "vue";
import { useRouter } from "vue-router";
import ExcelJS from "exceljs";
import BarcodeScanner from "../../components/BarcodeScanner.vue";
import { useAuth } from "@/composables/useAuth";

const router = useRouter();
const { user, isOwner } = useAuth();
const toast = useToast();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const products = ref([]);
const isLoading = ref(false);

// Filter states
const selectedCategory = ref("all");
const selectedCondition = ref("all");
const showOnlyReady = ref(true);

const dt = ref();
const productDialog = ref(false);
const deleteProductDialog = ref(false);
const deleteProductsDialog = ref(false);
const product = ref({});
const selectedProducts = ref([]);
const submitted = ref(false);
const imeiSearching = ref(false);

const fetchProducts = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get("stocks", {
            params: {
                status: showOnlyReady.value ? "ada" : "all",
            },
        });
        products.value = response.data;
    } catch (error) {
        console.error("Error fetching products:", error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchProducts();
});

// Format currency
const formatCurrency = (value) => {
    return (parseFloat(value) || 0).toLocaleString("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
};

// Filtered data computed for table
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

// Inventory Summary Metrics
const stockMetrics = computed(() => {
    const ready = products.value.filter((p) => p.status === "ada");
    const totalAsset = ready.reduce((acc, p) => acc + (parseFloat(p.harga_masuk) || 0), 0);
    return {
        totalReady: ready.length,
        gadgetReady: ready.filter((p) => p.kategori === "gadget").length,
        aksesorisReady: ready.filter((p) => p.kategori === "aksesoris").length,
        sparepartReady: ready.filter((p) => p.kategori === "sparepart").length,
        totalAssetValue: totalAsset,
    };
});

// Copy IMEI to clipboard
const copyImei = (imei) => {
    if (!imei) return;
    navigator.clipboard.writeText(imei);
    toast.add({ severity: "info", summary: "Tersalin", detail: `IMEI ${imei} disalin ke clipboard`, life: 2000 });
};

// Open New Dialog
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

// Edit Existing Product
const editProduct = (prod) => {
    product.value = {
        ...prod,
        kategori: prod.kategori || "gadget",
        kondisi: prod.kondisi || "baru",
    };
    submitted.value = false;
    productDialog.value = true;
};

// Save Product (Create or Update)
const saveProduct = async () => {
    submitted.value = true;

    if (!product.value.nama?.trim() || !product.value.brand?.trim() || product.value.harga_masuk === null || product.value.harga_jual === null) {
        toast.add({
            severity: "warn",
            summary: "Data Belum Lengkap",
            detail: "Nama barang, brand, harga masuk, dan harga jual wajib diisi!",
            life: 3000,
        });
        return;
    }

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
        harga_masuk: parseFloat(product.value.harga_masuk) || 0,
        harga_jual: parseFloat(product.value.harga_jual) || 0,
        id_sales: product.value.id_sales || user.value?.id_user || null,
        status: product.value.status || "ada",
        catatan: product.value.catatan || null,
    };

    try {
        if (product.value.id_stock) {
            await axios.put(`stocks/${product.value.id_stock}`, payload);
            toast.add({ severity: "success", summary: "Berhasil", detail: "Data stok diperbarui", life: 3000 });
        } else {
            await axios.post("stocks", payload);
            toast.add({ severity: "success", summary: "Berhasil", detail: "Stok baru berhasil ditambahkan", life: 3000 });
        }
        productDialog.value = false;
        product.value = {};
        fetchProducts();
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Simpan",
            detail: error.response?.data?.error || "Gagal menyimpan data stok",
            life: 4000,
        });
    }
};

// Delete single product
const confirmDeleteProduct = (prod) => {
    product.value = prod;
    deleteProductDialog.value = true;
};

const executeDeleteProduct = async () => {
    try {
        await axios.delete(`stocks/${product.value.id_stock}`);
        toast.add({ severity: "success", summary: "Dihapus", detail: "Data stok berhasil dihapus", life: 3000 });
        deleteProductDialog.value = false;
        product.value = {};
        fetchProducts();
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Menghapus",
            detail: error.response?.data?.error || "Gagal menghapus stok",
            life: 4000,
        });
    }
};

// Auto fill IMEI scanner via TAC Lookup & History
const handleImeiScanned = async (scannedImei) => {
    const clean = (scannedImei || "").replace(/\D/g, "");
    if (!clean || clean.length < 8) return;
    if (product.value.id_stock) return;

    imeiSearching.value = true;
    try {
        // 1. Prioritas utama: Cek TAC Lookup Engine (Database GSMA TAC lokal & history)
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
                summary: "TAC Teridentifikasi (" + sourceLabel + ")",
                detail: `${data.brand} ${data.nama} ${data.storage ? data.storage : ''} terdeteksi otomatis`,
                life: 3500,
            });
            return;
        }

        // 2. Fallback: Cek exact IMEI di stok sebelumnya
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
                detail: `Spesifikasi unit ${exactRes.data.nama} diisi otomatis`,
                life: 3000,
            });
            return;
        }

        // 3. Jika belum terdaftar di TAC
        toast.add({
            severity: "info",
            summary: "TAC Baru Ditemukan",
            detail: `TAC ${clean.substring(0, 8)} belum terdaftar. Isi Brand & Model sekali, sistem akan otomatis menyimpannya.`,
            life: 4000,
        });
    } catch (error) {
        // Silently continue for new TAC
    } finally {
        imeiSearching.value = false;
    }
};

// Direct sell from stock table
const sellDirect = (prod) => {
    router.push({
        path: "/pages/penjualan",
    });
};

// Export to Excel
const exportToExcel = async () => {
    if (!products.value.length) {
        toast.add({ severity: "warn", summary: "Peringatan", detail: "Tidak ada data stok untuk di-export", life: 2500 });
        return;
    }

    try {
        const workbook = new ExcelJS.Workbook();
        workbook.creator = "Tamboer Gadget";
        const ws = workbook.addWorksheet("Data Stok");

        ws.columns = [
            { header: "No", key: "no", width: 5 },
            { header: "Tanggal", key: "tanggal", width: 13 },
            { header: "Kategori", key: "kategori", width: 13 },
            { header: "Brand", key: "brand", width: 14 },
            { header: "Nama Barang", key: "nama", width: 28 },
            { header: "Kondisi", key: "kondisi", width: 11 },
            { header: "Warna", key: "warna", width: 12 },
            { header: "RAM/Storage", key: "specs", width: 14 },
            { header: "IMEI", key: "imei", width: 18 },
            { header: "Harga Masuk", key: "harga_masuk", width: 16 },
            { header: "Harga Jual", key: "harga_jual", width: 16 },
            { header: "Status", key: "status", width: 11 },
            { header: "Sales", key: "sales", width: 15 },
        ];

        ws.getRow(1).font = { bold: true, color: { argb: "FFFFFFFF" } };
        ws.getRow(1).fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FF1C1917" } };

        filteredData.value.forEach((item, idx) => {
            ws.addRow({
                no: idx + 1,
                tanggal: item.tanggal,
                kategori: (item.kategori || "gadget").toUpperCase(),
                brand: item.brand,
                nama: item.nama,
                kondisi: (item.kondisi || "baru").toUpperCase(),
                warna: item.warna,
                specs: [item.ram, item.storage].filter(Boolean).join(" / "),
                imei: item.imei || "-",
                harga_masuk: Number(item.harga_masuk) || 0,
                harga_jual: Number(item.harga_jual) || 0,
                status: item.status === "ada" ? "READY" : "TERJUAL",
                sales: item.sales?.nama || "-",
            });
        });

        ws.getColumn("harga_masuk").numFmt = "#,##0";
        ws.getColumn("harga_jual").numFmt = "#,##0";

        const buffer = await workbook.xlsx.writeBuffer();
        let binary = "";
        const bytes = new Uint8Array(buffer);
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        const excelBase64 = window.btoa(binary);

        const form = document.createElement("form");
        form.method = "POST";
        form.action = `/api/download-excel-proxy`;
        form.enctype = "multipart/form-data";
        form.style.display = "none";

        const filenameInput = document.createElement("input");
        filenameInput.name = "filename";
        filenameInput.value = `Stok_Tamboer_${new Date().toISOString().slice(0, 10)}.xlsx`;
        form.appendChild(filenameInput);

        const dataInput = document.createElement("input");
        dataInput.name = "base64";
        dataInput.value = excelBase64;
        form.appendChild(dataInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        toast.add({ severity: "success", summary: "Sukses", detail: "File Excel berhasil di-download", life: 3000 });
    } catch (e) {
        console.error(e);
        toast.add({ severity: "error", summary: "Gagal", detail: "Gagal membuat file Excel", life: 3000 });
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Top Header & Metric Cards -->
        <div class="bg-white border border-[#e7e5df] rounded-2xl p-5 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-[#e7e5df]">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-[#1c1917] text-white flex items-center justify-center font-bold text-lg shadow-sm">
                        <i class="pi pi-box text-amber-400"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-[#1c1917] tracking-tight">
                            Katalog & Stok Toko
                        </h1>
                        <p class="text-xs text-[#78716c] mt-0.5">
                            Pencatatan inventaris smartphone, aksesoris, dan suku cadang
                        </p>
                    </div>
                </div>

                <!-- Action Hub -->
                <div class="flex items-center flex-wrap gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#1c1917] hover:bg-[#292524] text-white flex items-center gap-2 shadow-sm transition-all"
                        @click="openNew"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Tambah Stok Unit</span>
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#f0ede6] hover:bg-[#e7e5df] text-[#1c1917] flex items-center gap-2 border border-[#e7e5df] transition-all"
                        @click="exportToExcel"
                    >
                        <i class="pi pi-file-excel text-xs text-amber-700 dark:text-amber-400"></i>
                        <span>Export Excel</span>
                    </button>
                    <button
                        type="button"
                        class="w-8 h-8 rounded-xl bg-[#f0ede6] hover:bg-[#e7e5df] text-[#78716c] hover:text-[#1c1917] flex items-center justify-center border border-[#e7e5df] transition-all"
                        @click="fetchProducts"
                        v-tooltip.top="'Refresh Data'"
                    >
                        <i class="pi pi-refresh text-xs" :class="isLoading ? 'pi-spin' : ''"></i>
                    </button>
                </div>
            </div>

            <!-- Mini Metric Badges -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-4 text-xs">
                <div class="p-3.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
                    <span class="text-[#78716c] block font-medium text-[11px]">Total Ready</span>
                    <span class="text-lg font-bold text-[#1c1917] mt-0.5 block">{{ stockMetrics.totalReady }} Item</span>
                </div>
                <div class="p-3.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
                    <span class="text-[#78716c] block font-medium text-[11px]">Smartphone Ready</span>
                    <span class="text-lg font-bold text-amber-800 mt-0.5 block">{{ stockMetrics.gadgetReady }} Unit</span>
                </div>
                <div class="p-3.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
                    <span class="text-[#78716c] block font-medium text-[11px]">Aksesoris Ready</span>
                    <span class="text-lg font-bold text-stone-800 mt-0.5 block">{{ stockMetrics.aksesorisReady }} Pcs</span>
                </div>
                <div v-if="isOwner" class="p-3.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
                    <span class="text-[#78716c] block font-medium text-[11px]">Valuasi Modal Etalase</span>
                    <span class="text-lg font-bold text-amber-800 dark:text-amber-400 font-mono mt-0.5 block">{{ formatCurrency(stockMetrics.totalAssetValue) }}</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar & Search -->
        <div class="bg-white border border-[#e7e5df] rounded-2xl p-4 sm:p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Category Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                <button
                    v-for="cat in [
                        { id: 'all', label: 'Semua Kategori' },
                        { id: 'gadget', label: 'Smartphone' },
                        { id: 'aksesoris', label: 'Aksesoris' },
                        { id: 'sparepart', label: 'Sparepart' }
                    ]"
                    :key="cat.id"
                    type="button"
                    class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all"
                    :class="selectedCategory === cat.id
                        ? 'bg-[#1c1917] text-white'
                        : 'bg-[#f0ede6] text-[#78716c] hover:bg-[#e7e5df] hover:text-[#1c1917]'"
                    @click="selectedCategory = cat.id"
                >
                    {{ cat.label }}
                </button>
            </div>

            <!-- Search & Condition -->
            <div class="flex items-center gap-3 self-stretch md:self-auto">
                <!-- Condition Dropdown -->
                <div class="flex items-center gap-1 shrink-0">
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                        :class="selectedCondition === 'all' ? 'bg-[#1c1917] text-white' : 'bg-[#f0ede6] text-[#78716c] hover:bg-[#e7e5df]'"
                        @click="selectedCondition = 'all'"
                    >Semua</button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                        :class="selectedCondition === 'baru' ? 'bg-stone-800 text-white dark:bg-stone-700' : 'bg-[#f0ede6] text-[#78716c] hover:bg-[#e7e5df]'"
                        @click="selectedCondition = 'baru'"
                    >Baru</button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                        :class="selectedCondition === 'second' ? 'bg-amber-700 text-white' : 'bg-[#f0ede6] text-[#78716c] hover:bg-[#e7e5df]'"
                        @click="selectedCondition = 'second'"
                    >Second</button>
                </div>

                <!-- Search Input -->
                <div class="relative flex-1 md:w-64">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#78716c] text-xs"></i>
                    <input
                        v-model="filters['global'].value"
                        type="text"
                        placeholder="Cari nama, brand, IMEI..."
                        class="w-full text-xs pl-8 pr-4 py-2 rounded-xl bg-[#faf9f5] border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                    />
                </div>
            </div>
        </div>

        <!-- Inventory Data Table -->
        <div class="bg-white border border-[#e7e5df] rounded-2xl p-5 shadow-sm">
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
                    <div class="text-center py-10 text-[#78716c]">
                        Tidak ada unit stok yang sesuai dengan filter.
                    </div>
                </template>

                <!-- Barang & Brand -->
                <Column header="Unit / Barang" sortable field="nama" style="min-width: 14rem">
                    <template #body="{ data }">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-bold uppercase text-amber-800 tracking-wider">{{ data.brand }}</span>
                                <span
                                    class="text-[9px] font-bold uppercase px-1.5 py-0.2 rounded border"
                                    :class="data.kondisi === 'second'
                                        ? 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/40'
                                        : 'bg-stone-100 text-stone-800 border-stone-300 dark:bg-stone-800 dark:text-stone-200 dark:border-stone-700'"
                                >
                                    {{ data.kondisi || 'Baru' }}
                                </span>
                            </div>
                            <span class="font-bold text-xs text-[#1c1917] block mt-0.5">
                                {{ data.nama }}
                            </span>
                        </div>
                    </template>
                </Column>

                <!-- Spesifikasi -->
                <Column header="Spesifikasi & Garansi" style="min-width: 13rem">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-1 text-[11px]">
                            <span v-if="data.ram || data.storage" class="px-1.5 py-0.5 rounded bg-[#f6f5f0] text-[#44403c] font-mono text-[10px] border border-[#e7e5df]">
                                {{ [data.ram, data.storage].filter(Boolean).join('/') }}
                            </span>
                            <span v-if="data.warna && data.warna !== '-'" class="px-1.5 py-0.5 rounded bg-[#f6f5f0] text-[#78716c] text-[10px] border border-[#e7e5df]">
                                {{ data.warna }}
                            </span>
                            <span v-if="data.garansi" class="px-1.5 py-0.5 rounded bg-stone-100 text-[#44403c] border border-[#e7e5df] text-[10px]">
                                {{ data.garansi }}
                            </span>
                        </div>
                    </template>
                </Column>

                <!-- IMEI / Seri -->
                <Column header="IMEI / SN" style="min-width: 11rem">
                    <template #body="{ data }">
                        <div v-if="data.imei" class="flex items-center gap-1 font-mono text-xs text-[#78716c]">
                            <span>{{ data.imei }}</span>
                            <button
                                type="button"
                                class="text-[#78716c] hover:text-[#1c1917] transition-colors p-1"
                                v-tooltip.top="'Salin IMEI'"
                                @click="copyImei(data.imei)"
                            >
                                <i class="pi pi-copy text-[10px]"></i>
                            </button>
                        </div>
                        <span v-else class="text-xs text-[#a8a29e] italic">Tanpa IMEI</span>
                    </template>
                </Column>

                <!-- Harga Masuk (Owner Only) -->
                <Column v-if="isOwner" field="harga_masuk" header="Modal" sortable style="min-width: 8rem">
                    <template #body="{ data }">
                        <span class="text-xs text-[#78716c] font-mono font-medium">
                            {{ formatCurrency(data.harga_masuk) }}
                        </span>
                    </template>
                </Column>

                <!-- Harga Jual -->
                <Column field="harga_jual" header="Harga Toko" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="text-xs font-bold font-mono text-[#1c1917]">
                            {{ formatCurrency(data.harga_jual) }}
                        </span>
                    </template>
                </Column>

                <!-- Status -->
                <Column field="status" header="Status" sortable style="min-width: 7rem">
                    <template #body="{ data }">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border"
                            :class="data.status === 'ada'
                                ? 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/40'
                                : 'bg-stone-100 text-stone-600 border-stone-200 dark:bg-stone-800 dark:text-stone-400 dark:border-stone-700'"
                        >
                            {{ data.status === 'ada' ? 'Ready' : 'Terjual' }}
                        </span>
                    </template>
                </Column>

                <!-- Penanggungjawab -->
                <Column field="sales.nama" header="Penanggungjawab" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="text-xs text-[#78716c]">{{ data.sales?.nama || 'Petugas' }}</span>
                    </template>
                </Column>

                <!-- Actions -->
                <Column header="Aksi" style="min-width: 8rem" :exportable="false">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                class="w-8 h-8 rounded-lg bg-[#f0ede6] text-[#1c1917] hover:bg-[#1c1917] hover:text-white flex items-center justify-center transition-colors"
                                v-tooltip.top="'Jual via POS'"
                                :disabled="data.status === 'terjual'"
                                :class="data.status === 'terjual' ? 'opacity-40 cursor-not-allowed' : ''"
                                @click="sellDirect(data)"
                            >
                                <i class="pi pi-shopping-cart text-xs"></i>
                            </button>

                            <button
                                v-if="isOwner"
                                type="button"
                                class="w-8 h-8 rounded-lg bg-[#f0ede6] text-[#78716c] hover:bg-[#1c1917] hover:text-white flex items-center justify-center transition-colors"
                                v-tooltip.top="'Edit Data'"
                                @click="editProduct(data)"
                            >
                                <i class="pi pi-pencil text-xs"></i>
                            </button>

                            <button
                                v-if="isOwner"
                                type="button"
                                class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-colors"
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

        <!-- ============================================================== -->
        <!-- MODAL TAMBAH / EDIT PRODUK                                     -->
        <!-- ============================================================== -->
        <Dialog
            v-model:visible="productDialog"
            modal
            :header="product.id_stock ? 'Perbarui Data Stok' : 'Tambah Stok Unit Baru'"
            :style="{ width: '600px', maxWidth: '95vw' }"
            class="rounded-2xl bg-white"
        >
            <div class="space-y-4 pt-2 text-xs">
                <!-- Segmented Controls: Kategori & Kondisi -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-[#78716c] mb-1.5">Kategori</label>
                        <div class="grid grid-cols-3 gap-1 bg-[#f0ede6] p-1 rounded-xl border border-[#e7e5df]">
                            <button
                                v-for="c in ['gadget', 'aksesoris', 'sparepart']"
                                :key="c"
                                type="button"
                                class="py-1.5 rounded-lg text-[11px] font-semibold capitalize transition-all"
                                :class="product.kategori === c ? 'bg-[#1c1917] text-white shadow-sm' : 'text-[#78716c]'"
                                @click="product.kategori = c"
                            >
                                {{ c }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-[#78716c] mb-1.5">Kondisi</label>
                        <div class="grid grid-cols-3 gap-1 bg-[#f0ede6] p-1 rounded-xl border border-[#e7e5df]">
                            <button
                                v-for="k in ['baru', 'second', 'refurbished']"
                                :key="k"
                                type="button"
                                class="py-1.5 rounded-lg text-[11px] font-semibold capitalize transition-all"
                                :class="product.kondisi === k ? 'bg-[#1c1917] text-white shadow-sm' : 'text-[#78716c]'"
                                @click="product.kondisi = k"
                            >
                                {{ k }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Brand & Nama Barang -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-[#78716c] mb-1">Brand</label>
                        <input
                            v-model.trim="product.brand"
                            type="text"
                            placeholder="Apple, Samsung, dsb"
                            class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-[#78716c] mb-1">Nama / Model Barang</label>
                        <input
                            v-model.trim="product.nama"
                            type="text"
                            placeholder="iPhone 15 Pro Max 256GB"
                            class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                        />
                    </div>
                </div>

                <!-- IMEI Barcode Scanner Input -->
                <div>
                    <label class="block font-semibold text-[#78716c] mb-1">
                        Nomor IMEI / Serial Number
                        <span v-if="product.kategori === 'aksesoris'" class="text-[#a8a29e] font-normal">(Opsional untuk aksesoris)</span>
                    </label>
                    <BarcodeScanner
                        v-model="product.imei"
                        @scanned="handleImeiScanned"
                    />
                    <small v-if="imeiSearching" class="text-amber-800 mt-1 block">
                        <i class="pi pi-spin pi-spinner mr-1"></i>Mencari kecocokan IMEI...
                    </small>
                </div>

                <!-- Specs: RAM, Storage, Warna -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-[#78716c] mb-1">RAM</label>
                        <input
                            v-model.trim="product.ram"
                            type="text"
                            placeholder="Misal: 8 GB"
                            class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-[#78716c] mb-1">Storage</label>
                        <input
                            v-model.trim="product.storage"
                            type="text"
                            placeholder="Misal: 256 GB"
                            class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-[#78716c] mb-1">Warna</label>
                        <input
                            v-model.trim="product.warna"
                            type="text"
                            placeholder="Black Titanium"
                            class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                        />
                    </div>
                </div>

                <!-- Kelengkapan & Garansi -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-[#78716c] mb-1">Kelengkapan</label>
                        <input
                            v-model.trim="product.kelengkapan"
                            type="text"
                            placeholder="Fullset / Unit+Charger"
                            class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-[#78716c] mb-1">Garansi</label>
                        <input
                            v-model.trim="product.garansi"
                            type="text"
                            placeholder="Garansi Toko 1 Bulan"
                            class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                        />
                    </div>
                </div>

                <!-- Harga Masuk & Harga Jual -->
                <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
                    <div>
                        <label class="block font-semibold text-[#78716c] mb-1">Harga Masuk (Modal)</label>
                        <input
                            v-model.number="product.harga_masuk"
                            type="number"
                            placeholder="Rp 0"
                            class="w-full text-sm font-bold font-mono p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-[#1c1917] mb-1">Harga Jual (Toko)</label>
                        <input
                            v-model.number="product.harga_jual"
                            type="number"
                            placeholder="Rp 0"
                            class="w-full text-sm font-bold font-mono p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                        />
                    </div>
                </div>

                <!-- Catatan -->
                <div>
                    <label class="block font-semibold text-[#78716c] mb-1">Catatan Khusus Unit</label>
                    <textarea
                        v-model.trim="product.catatan"
                        rows="2"
                        placeholder="Misal: Battery Health 98%, mulus no minus, segel toko terpasang..."
                        class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917] resize-none"
                    ></textarea>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Batal" text severity="secondary" @click="productDialog = false" />
                    <Button
                        label="Simpan Unit"
                        icon="pi pi-check"
                        severity="primary"
                        class="font-bold bg-[#1c1917] hover:bg-[#292524] text-white border-0"
                        @click="saveProduct"
                    />
                </div>
            </template>
        </Dialog>

        <!-- Delete Confirm Dialog -->
        <Dialog
            v-model:visible="deleteProductDialog"
            header="Konfirmasi Hapus"
            modal
            :style="{ width: '420px', maxWidth: '90vw' }"
            class="rounded-2xl bg-white"
        >
            <div class="flex items-center gap-3 pt-2">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-200">
                    <i class="pi pi-exclamation-triangle text-lg"></i>
                </div>
                <div>
                    <p class="font-bold text-[#1c1917]">Hapus barang ini?</p>
                    <p class="text-xs text-[#78716c] mt-1">
                        <b>{{ product.nama }}</b> akan dihapus dari etalase stok toko.
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
