<script setup>
import { FilterMatchMode } from "@primevue/core/api";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, computed } from "vue";
import BarcodeScanner from "../../components/BarcodeScanner.vue";
import ThermalReceipt from "../../components/ThermalReceipt.vue";
import { useAuth } from "@/composables/useAuth";

const { user, isOwner } = useAuth();
const toast = useToast();

// View mode: 'pos' (Kasir Cepat) atau 'history' (Riwayat Transaksi)
const activeTab = ref("pos");

// Data state
const penjualan = ref([]);
const stokItemsList = ref([]);
const isLoading = ref(false);

// Filters & Search
const posSearch = ref("");
const posCategory = ref("all");
const posCondition = ref("all");
const historyFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// Modal state
const mobileCartDrawer = ref(false);
const receiptDialog = ref(false);
const currentReceipt = ref({});
const deleteDialog = ref(false);
const selectedSaleToDelete = ref(null);

// POS Cart & Checkout state
const cart = ref([]);
const paymentMethod = ref("Cash");
const discount = ref(0);
const cashReceived = ref(0);
const buyerName = ref("");
const buyerPhone = ref("");
const saleNotes = ref("");
const isSubmitting = ref(false);

// Format currency
const formatCurrency = (value) => {
    return (parseFloat(value) || 0).toLocaleString("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
};

// Fetch data
const fetchPenjualan = async () => {
    try {
        const response = await axios.get("penjualan");
        penjualan.value = response.data;
    } catch (error) {
        console.error("Error fetching penjualan:", error);
    }
};

const fetchStokItems = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get("stocks");
        stokItemsList.value = response.data;
    } catch (error) {
        console.error("Error fetching stok items:", error);
    } finally {
        isLoading.value = false;
    }
};

// Ready stocks filtered for POS Catalog
const filteredReadyStocks = computed(() => {
    let list = stokItemsList.value.filter((s) => s.status === "ada");

    if (posCategory.value !== "all") {
        list = list.filter((s) => s.kategori === posCategory.value);
    }

    if (posCondition.value !== "all") {
        list = list.filter((s) => (s.kondisi || "baru") === posCondition.value);
    }

    if (posSearch.value.trim()) {
        const q = posSearch.value.toLowerCase();
        list = list.filter((s) =>
            (s.imei || "").toLowerCase().includes(q) ||
            (s.nama || "").toLowerCase().includes(q) ||
            (s.brand || "").toLowerCase().includes(q) ||
            (s.warna || "").toLowerCase().includes(q) ||
            (s.ram || "").toLowerCase().includes(q) ||
            (s.storage || "").toLowerCase().includes(q)
        );
    }

    return list;
});

// Category counts
const categoryCounts = computed(() => {
    const ready = stokItemsList.value.filter((s) => s.status === "ada");
    return {
        all: ready.length,
        gadget: ready.filter((s) => s.kategori === "gadget").length,
        aksesoris: ready.filter((s) => s.kategori === "aksesoris").length,
        sparepart: ready.filter((s) => s.kategori === "sparepart").length,
    };
});

// Cart Calculations
const cartSubtotal = computed(() => {
    return cart.value.reduce((acc, item) => acc + (parseFloat(item.harga_jual) || 0), 0);
});

const cartTotal = computed(() => {
    return Math.max(0, cartSubtotal.value - (parseFloat(discount.value) || 0));
});

const changeDue = computed(() => {
    if (paymentMethod.value !== "Cash") return 0;
    return Math.max(0, (parseFloat(cashReceived.value) || 0) - cartTotal.value);
});

const isUnderpaid = computed(() => {
    if (paymentMethod.value !== "Cash") return false;
    return (parseFloat(cashReceived.value) || 0) < cartTotal.value;
});

// Cart Actions
const isItemInCart = (id_stock) => {
    return cart.value.some((c) => c.id_stock === id_stock);
};

const addToCart = (stock) => {
    if (isItemInCart(stock.id_stock)) {
        toast.add({ severity: "warn", summary: "Perhatian", detail: "Unit ini sudah ada di keranjang!", life: 1500 });
        return;
    }
    cart.value.push({ ...stock });
    toast.add({ severity: "success", summary: "Ditambahkan", detail: `${stock.nama} masuk ke keranjang`, life: 1200 });
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const clearCart = () => {
    cart.value = [];
    discount.value = 0;
    cashReceived.value = 0;
    buyerName.value = "";
    buyerPhone.value = "";
    saleNotes.value = "";
};

const handleBarcodeScanned = (scannedImei) => {
    posSearch.value = scannedImei;
    const match = stokItemsList.value.find((s) => s.status === "ada" && s.imei === scannedImei);
    if (match) {
        addToCart(match);
        posSearch.value = "";
    } else {
        toast.add({ severity: "error", summary: "Tidak Ditemukan", detail: "Tidak ada unit ready dengan IMEI tersebut", life: 3000 });
    }
};

const setExactMoney = () => {
    cashReceived.value = cartTotal.value;
};

const setQuickMoney = (amount) => {
    cashReceived.value = amount;
};

// Checkout
const processCheckout = async () => {
    if (cart.value.length === 0) {
        toast.add({ severity: "warn", summary: "Keranjang Kosong", detail: "Pilih minimal 1 barang untuk dijual!", life: 2500 });
        return;
    }

    if (paymentMethod.value === "Cash" && isUnderpaid.value) {
        toast.add({ severity: "error", summary: "Uang Kurang", detail: "Nominal uang yang diterima kurang dari total belanja!", life: 3000 });
        return;
    }

    isSubmitting.value = true;
    try {
        const payload = {
            tanggal: new Date().toISOString().slice(0, 10),
            items: cart.value.map((c) => ({
                id_stock: c.id_stock,
                harga_jual: parseFloat(c.harga_jual) || 0,
            })),
            id_sales: user.value?.id_user,
            met_pem: paymentMethod.value,
            diskon: parseFloat(discount.value) || 0,
            uang_bayar: paymentMethod.value === "Cash" ? (parseFloat(cashReceived.value) || cartTotal.value) : cartTotal.value,
            kembalian: changeDue.value,
            nama_pembeli: buyerName.value || null,
            no_hp_pembeli: buyerPhone.value || null,
            catatan: saleNotes.value || null,
        };

        const res = await axios.post("penjualan/checkout", payload);
        const invoiceData = res.data.data;

        toast.add({ severity: "success", summary: "Checkout Sukses!", detail: `Transaksi ${invoiceData.no_invoice} berhasil disimpan`, life: 3000 });

        // Refresh data
        fetchPenjualan();
        fetchStokItems();

        // Tutup drawer mobile & bersihkan keranjang
        mobileCartDrawer.value = false;
        clearCart();

        // Buka struk thermal
        await printInvoice(invoiceData.no_invoice);

    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Checkout Gagal",
            detail: error.response?.data?.error || "Gagal memproses transaksi",
            life: 5000,
        });
    } finally {
        isSubmitting.value = false;
    }
};

// Print Invoice Modal
const printInvoice = async (no_invoice) => {
    try {
        const res = await axios.get(`penjualan/invoice/${encodeURIComponent(no_invoice)}`);
        currentReceipt.value = res.data;
        receiptDialog.value = true;
    } catch (error) {
        toast.add({ severity: "error", summary: "Gagal Cetak", detail: "Invoice tidak ditemukan atau rusak", life: 3000 });
    }
};

// Delete Sale & Revert Stock
const confirmDeleteSale = (sale) => {
    selectedSaleToDelete.value = sale;
    deleteDialog.value = true;
};

const executeDeleteSale = async () => {
    if (!selectedSaleToDelete.value) return;
    try {
        await axios.delete(`penjualan/${selectedSaleToDelete.value.id_penjualan}`);
        toast.add({ severity: "success", summary: "Dihapus", detail: "Transaksi dihapus & status stok dikembalikan ke etalase", life: 3000 });
        deleteDialog.value = false;
        selectedSaleToDelete.value = null;
        fetchPenjualan();
        fetchStokItems();
    } catch (error) {
        toast.add({ severity: "error", summary: "Gagal Hapus", detail: error.response?.data?.error || "Gagal menghapus transaksi", life: 4000 });
    }
};

onMounted(() => {
    fetchPenjualan();
    fetchStokItems();
});
</script>

<template>
    <div class="space-y-6 pb-24 lg:pb-8">
        <!-- Top Navigation Bar / Mode Selector -->
        <div class="bg-white border border-[#e7e5df] rounded-2xl p-4 sm:p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <!-- Title & Kasir Badge -->
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-[#1c1917] text-white flex items-center justify-center font-bold text-lg shadow-sm">
                        <i class="pi pi-bolt text-amber-400"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-xl sm:text-2xl font-bold text-[#1c1917] tracking-tight">
                                Tamboer POS
                            </h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/40">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                Kasir Online
                            </span>
                        </div>
                        <p class="text-xs text-[#78716c] mt-0.5">
                            Kasir: <span class="font-semibold text-[#1c1917]">{{ user?.nama || 'Petugas' }}</span> &bull; {{ filteredReadyStocks.length }} item siap jual
                        </p>
                    </div>
                </div>

                <!-- Tab Segment Controls (Nordic Pill Style) -->
                <div class="inline-flex p-1 rounded-xl bg-[#f0ede6] border border-[#e7e5df]">
                    <button
                        type="button"
                        class="px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-2"
                        :class="activeTab === 'pos'
                            ? 'bg-[#1c1917] text-white shadow-sm'
                            : 'text-[#78716c] hover:text-[#1c1917]'"
                        @click="activeTab = 'pos'"
                    >
                        <i class="pi pi-shopping-bag"></i>
                        Kasir POS
                        <span v-if="cart.length > 0" class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-amber-400 text-[#1c1917] font-bold">
                            {{ cart.length }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-2"
                        :class="activeTab === 'history'
                            ? 'bg-[#1c1917] text-white shadow-sm'
                            : 'text-[#78716c] hover:text-[#1c1917]'"
                        @click="activeTab = 'history'"
                    >
                        <i class="pi pi-history"></i>
                        Riwayat Penjualan
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODE 1: KASIR POS (ADAPTIVE SPLIT PC / MOBILE)                -->
        <!-- ============================================================== -->
        <div v-show="activeTab === 'pos'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left Side: Product Catalog (7 Cols on Desktop) -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-4">
                <!-- Search & Barcode Scanner Header -->
                <div class="bg-white border border-[#e7e5df] rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                    <!-- Barcode Search Input -->
                    <div>
                        <BarcodeScanner
                            v-model="posSearch"
                            @scanned="handleBarcodeScanned"
                        />
                    </div>

                    <!-- Category & Condition Filter Pills -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-[#e7e5df]">
                        <!-- Category Pills -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all"
                                :class="posCategory === 'all'
                                    ? 'bg-[#1c1917] text-white'
                                    : 'bg-[#f0ede6] text-[#78716c] hover:bg-[#e7e5df] hover:text-[#1c1917]'"
                                @click="posCategory = 'all'"
                            >
                                Semua ({{ categoryCounts.all }})
                            </button>
                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all"
                                :class="posCategory === 'gadget'
                                    ? 'bg-[#1c1917] text-white'
                                    : 'bg-[#f0ede6] text-[#78716c] hover:bg-[#e7e5df] hover:text-[#1c1917]'"
                                @click="posCategory = 'gadget'"
                            >
                                Smartphone ({{ categoryCounts.gadget }})
                            </button>
                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all"
                                :class="posCategory === 'aksesoris'
                                    ? 'bg-[#1c1917] text-white'
                                    : 'bg-[#f0ede6] text-[#78716c] hover:bg-[#e7e5df] hover:text-[#1c1917]'"
                                @click="posCategory = 'aksesoris'"
                            >
                                Aksesoris ({{ categoryCounts.aksesoris }})
                            </button>
                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all"
                                :class="posCategory === 'sparepart'
                                    ? 'bg-[#1c1917] text-white'
                                    : 'bg-[#f0ede6] text-[#78716c] hover:bg-[#e7e5df] hover:text-[#1c1917]'"
                                @click="posCategory = 'sparepart'"
                            >
                                Sparepart ({{ categoryCounts.sparepart }})
                            </button>
                        </div>

                        <!-- Condition Filters (Baru vs Second) -->
                        <div class="flex items-center gap-1 shrink-0 self-end sm:self-auto">
                            <span class="text-[11px] uppercase font-bold text-[#78716c] mr-1">Kondisi:</span>
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                                :class="posCondition === 'all' ? 'bg-[#1c1917] text-white' : 'bg-[#f0ede6] text-[#78716c] hover:bg-[#e7e5df]'"
                                @click="posCondition = 'all'"
                            >Semua</button>
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                                :class="posCondition === 'baru' ? 'bg-stone-800 text-white dark:bg-stone-700' : 'bg-[#f0ede6] text-[#78716c] hover:bg-[#e7e5df]'"
                                @click="posCondition = 'baru'"
                            >Baru</button>
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                                :class="posCondition === 'second' ? 'bg-amber-700 text-white' : 'bg-[#f0ede6] text-[#78716c] hover:bg-[#e7e5df]'"
                                @click="posCondition = 'second'"
                            >Second</button>
                        </div>
                    </div>
                </div>

                <!-- Product Catalog Grid -->
                <div v-if="filteredReadyStocks.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div
                        v-for="item in filteredReadyStocks"
                        :key="item.id_stock"
                        class="bg-white border rounded-2xl p-4 flex flex-col justify-between cursor-pointer select-none transition-all group"
                        :class="isItemInCart(item.id_stock)
                            ? 'border-[#1c1917] ring-1 ring-[#1c1917] bg-[#faf9f5]'
                            : 'border-[#e7e5df] hover:border-[#1c1917] shadow-sm'"
                        @click="addToCart(item)"
                    >
                        <div>
                            <!-- Header: Brand & Condition Tag -->
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-amber-800">
                                    {{ item.brand }}
                                </span>
                                <span
                                    class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full border"
                                    :class="item.kondisi === 'second'
                                        ? 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/40'
                                        : 'bg-stone-100 text-stone-800 border-stone-300 dark:bg-stone-800 dark:text-stone-200 dark:border-stone-700'"
                                >
                                    {{ item.kondisi || 'Baru' }}
                                </span>
                            </div>

                            <!-- Product Name -->
                            <h3 class="font-bold text-[#1c1917] text-sm leading-snug group-hover:text-amber-800 transition-colors">
                                {{ item.nama }}
                            </h3>

                            <!-- Specs Chips (RAM/Storage/Color) -->
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <span v-if="item.storage || item.ram" class="text-[11px] font-medium px-2 py-0.5 rounded-md bg-[#f6f5f0] text-[#44403c] border border-[#e7e5df]">
                                    {{ [item.ram, item.storage].filter(Boolean).join(' / ') }}
                                </span>
                                <span v-if="item.warna && item.warna !== '-'" class="text-[11px] font-medium px-2 py-0.5 rounded-md bg-[#f6f5f0] text-[#78716c] border border-[#e7e5df]">
                                    {{ item.warna }}
                                </span>
                                <span v-if="item.garansi" class="text-[11px] font-medium px-2 py-0.5 rounded-md bg-stone-100 text-[#44403c] border border-[#e7e5df]">
                                    {{ item.garansi }}
                                </span>
                            </div>

                            <!-- IMEI / S/N -->
                            <div v-if="item.imei" class="mt-2 text-[11px] font-mono text-[#78716c] flex items-center gap-1">
                                <i class="pi pi-qrcode text-[10px]"></i>
                                <span>{{ item.imei }}</span>
                            </div>
                        </div>

                        <!-- Footer: Price & Add Action -->
                        <div class="mt-4 pt-3 border-t border-[#e7e5df] flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-[#78716c] block">Harga Jual</span>
                                <span class="text-sm font-bold text-[#1c1917] font-mono">
                                    {{ formatCurrency(item.harga_jual) }}
                                </span>
                            </div>

                            <button
                                type="button"
                                class="w-8 h-8 rounded-xl flex items-center justify-center transition-all"
                                :class="isItemInCart(item.id_stock)
                                    ? 'bg-[#1c1917] text-white shadow-sm'
                                    : 'bg-[#f0ede6] text-[#44403c] group-hover:bg-[#1c1917] group-hover:text-white'"
                            >
                                <i :class="isItemInCart(item.id_stock) ? 'pi pi-check text-xs' : 'pi pi-plus text-xs'"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white border border-[#e7e5df] rounded-2xl p-12 text-center shadow-sm">
                    <div class="w-14 h-14 rounded-full bg-[#f6f5f0] mx-auto flex items-center justify-center text-[#78716c] mb-3">
                        <i class="pi pi-search text-xl"></i>
                    </div>
                    <h3 class="font-bold text-[#1c1917] text-base">Tidak Ada Barang Ready</h3>
                    <p class="text-xs text-[#78716c] mt-1">Coba ganti kata kunci pencarian atau ubah filter kategori/kondisi.</p>
                </div>
            </div>

            <!-- Right Side: Sticky Shopping Cart (Desktop lg:col-span-5) -->
            <div class="hidden lg:block lg:col-span-5 xl:col-span-4 sticky top-6">
                <div class="bg-white border border-[#e7e5df] rounded-2xl p-5 shadow-sm space-y-4">
                    <!-- Cart Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-[#e7e5df]">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-shopping-cart text-[#1c1917] font-bold"></i>
                            <h2 class="font-bold text-base text-[#1c1917] tracking-tight">
                                Keranjang Belanja
                            </h2>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-[#1c1917] text-white">
                                {{ cart.length }}
                            </span>
                        </div>
                        <button
                            v-if="cart.length > 0"
                            type="button"
                            class="text-xs font-medium text-rose-600 hover:text-rose-700 transition-colors"
                            @click="clearCart"
                        >
                            Kosongkan
                        </button>
                    </div>

                    <!-- Customer Detail Form (Collapsible) -->
                    <div class="bg-[#faf9f5] p-3 rounded-xl border border-[#e7e5df] space-y-2">
                        <div class="flex items-center justify-between text-xs font-semibold text-[#78716c]">
                            <span>Informasi Pelanggan (Opsional)</span>
                            <i class="pi pi-user text-xs"></i>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input
                                v-model="buyerName"
                                type="text"
                                placeholder="Nama Pembeli"
                                class="w-full text-xs px-3 py-2 rounded-lg bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                            />
                            <input
                                v-model="buyerPhone"
                                type="text"
                                placeholder="No. WhatsApp"
                                class="w-full text-xs px-3 py-2 rounded-lg bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                            />
                        </div>
                    </div>

                    <!-- Cart Items Scrollable List -->
                    <div class="max-h-[260px] overflow-y-auto space-y-2 pr-1">
                        <div v-if="cart.length === 0" class="py-8 text-center text-[#78716c]">
                            <i class="pi pi-inbox text-2xl mb-1.5 block opacity-50"></i>
                            <p class="text-xs font-medium">Keranjang masih kosong.</p>
                            <p class="text-[11px] opacity-70">Pilih barang di etalase untuk menambahkan.</p>
                        </div>

                        <div
                            v-for="(c, idx) in cart"
                            :key="c.id_stock"
                            class="p-2.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df] flex items-start justify-between gap-3 group"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[10px] font-bold uppercase text-amber-800">{{ c.brand }}</span>
                                    <span class="text-[9px] font-medium px-1.5 py-0.2 rounded bg-[#f0ede6] text-[#78716c]">
                                        {{ c.kondisi || 'Baru' }}
                                    </span>
                                </div>
                                <h4 class="text-xs font-semibold text-[#1c1917] truncate mt-0.5">
                                    {{ c.nama }}
                                </h4>
                                <span class="text-xs font-bold text-[#1c1917] font-mono block mt-0.5">
                                    {{ formatCurrency(c.harga_jual) }}
                                </span>
                            </div>

                            <button
                                type="button"
                                class="w-6 h-6 rounded-lg text-[#78716c] hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors"
                                @click="removeFromCart(idx)"
                            >
                                <i class="pi pi-trash text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Payment Calculations -->
                    <div class="space-y-2.5 pt-3 border-t border-[#e7e5df] text-xs">
                        <!-- Subtotal -->
                        <div class="flex justify-between text-[#78716c]">
                            <span>Subtotal</span>
                            <span class="font-semibold text-[#1c1917] font-mono">{{ formatCurrency(cartSubtotal) }}</span>
                        </div>

                        <!-- Diskon -->
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#78716c]">Diskon (Rp)</span>
                            <input
                                v-model.number="discount"
                                type="number"
                                min="0"
                                class="w-28 text-right text-xs px-2.5 py-1.5 rounded-lg bg-white border border-[#e7e5df] font-mono font-semibold text-rose-600 focus:outline-none focus:border-[#1c1917]"
                            />
                        </div>

                        <!-- Total Bayar -->
                        <div class="p-3 rounded-xl bg-[#f6f5f0] border border-[#e7e5df] flex justify-between items-center">
                            <span class="font-bold text-[#1c1917] uppercase text-[11px] tracking-wider">Total Akhir</span>
                            <span class="font-bold text-lg text-[#1c1917] font-mono">
                                {{ formatCurrency(cartTotal) }}
                            </span>
                        </div>

                        <!-- Metode Bayar Selector -->
                        <div class="space-y-1.5 pt-1">
                            <label class="block text-[11px] font-bold text-[#78716c]">Metode Pembayaran</label>
                            <div class="grid grid-cols-3 gap-1.5">
                                <button
                                    v-for="m in ['Cash', 'Transfer', 'QRIS']"
                                    :key="m"
                                    type="button"
                                    class="py-1.5 rounded-lg text-xs font-semibold transition-all border"
                                    :class="paymentMethod === m
                                        ? 'bg-[#1c1917] text-white border-[#1c1917] shadow-sm'
                                        : 'bg-[#faf9f5] border-[#e7e5df] text-[#78716c] hover:bg-[#f0ede6]'"
                                    @click="paymentMethod = m"
                                >
                                    {{ m }}
                                </button>
                            </div>
                        </div>

                        <!-- Cash Quick Input -->
                        <div v-if="paymentMethod === 'Cash'" class="space-y-2 pt-2 bg-[#faf9f5] p-3 rounded-xl border border-[#e7e5df]">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-[11px] text-[#78716c]">Uang Diterima</span>
                                <button
                                    type="button"
                                    class="text-[11px] font-semibold text-[#1c1917] hover:underline"
                                    @click="setExactMoney"
                                >
                                    Uang Pas
                                </button>
                            </div>

                            <input
                                v-model.number="cashReceived"
                                type="number"
                                class="w-full text-base font-bold font-mono px-3 py-1.5 rounded-lg bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                                placeholder="0"
                            />

                            <!-- Quick Nominal Pills -->
                            <div class="grid grid-cols-4 gap-1 pt-1">
                                <button
                                    v-for="amt in [50000, 100000, 200000, 500000]"
                                    :key="amt"
                                    type="button"
                                    class="py-1 rounded-md text-[10px] font-semibold bg-white border border-[#e7e5df] text-[#78716c] hover:bg-[#f0ede6]"
                                    @click="setQuickMoney(amt)"
                                >
                                    {{ amt >= 1000 ? (amt / 1000) + 'k' : amt }}
                                </button>
                            </div>

                            <!-- Kembalian Highlight -->
                            <div class="flex justify-between items-center pt-2 border-t border-[#e7e5df]">
                                <span class="text-xs font-semibold text-[#78716c]">Kembalian</span>
                                <span class="font-bold text-sm font-mono" :class="isUnderpaid ? 'text-rose-600' : 'text-amber-800 dark:text-amber-400'">
                                    {{ isUnderpaid ? 'Kurang ' + formatCurrency(cartTotal - (cashReceived || 0)) : formatCurrency(changeDue) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Checkout Button -->
                    <button
                        type="button"
                        class="w-full py-3.5 rounded-xl font-bold text-xs uppercase tracking-wider text-white transition-all flex items-center justify-center gap-2 shadow-sm"
                        :class="cart.length === 0 || (paymentMethod === 'Cash' && isUnderpaid)
                            ? 'bg-stone-300 text-stone-500 cursor-not-allowed opacity-60'
                            : 'bg-[#1c1917] hover:bg-[#292524] text-white cursor-pointer'"
                        :disabled="cart.length === 0 || (paymentMethod === 'Cash' && isUnderpaid) || isSubmitting"
                        @click="processCheckout"
                    >
                        <i v-if="isSubmitting" class="pi pi-spin pi-spinner"></i>
                        <i v-else class="pi pi-check-circle"></i>
                        {{ isSubmitting ? 'Memproses Transaksi...' : 'Bayar & Cetak Struk' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MOBILE FLOATING CART BAR & BOTTOM SHEET                         -->
        <!-- ============================================================== -->
        <div class="lg:hidden fixed bottom-4 left-4 right-4 z-40">
            <div
                class="bg-white/95 backdrop-blur-md p-3.5 rounded-2xl border border-[#e7e5df] shadow-xl flex items-center justify-between gap-3 cursor-pointer"
                @click="mobileCartDrawer = true"
            >
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#1c1917] text-white flex items-center justify-center font-bold shadow-sm">
                        <i class="pi pi-shopping-cart text-amber-400"></i>
                    </div>
                    <div>
                        <span class="text-[11px] font-medium text-[#78716c] block uppercase">
                            {{ cart.length }} Item di Keranjang
                        </span>
                        <span class="text-base font-bold text-[#1c1917] font-mono">
                            {{ formatCurrency(cartTotal) }}
                        </span>
                    </div>
                </div>

                <button
                    type="button"
                    class="px-4 py-2 rounded-xl bg-[#1c1917] text-white font-semibold text-xs uppercase tracking-wider flex items-center gap-1.5 shadow-sm"
                >
                    <span>Bayar</span>
                    <i class="pi pi-arrow-right text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Bottom Sheet Dialog -->
        <Dialog
            v-model:visible="mobileCartDrawer"
            position="bottom"
            modal
            :closable="true"
            :style="{ width: '100vw', maxWidth: '600px', margin: 0, borderBottomLeftRadius: 0, borderBottomRightRadius: 0 }"
            class="rounded-t-3xl bg-white"
        >
            <template #header>
                <div class="w-full text-center">
                    <div class="w-10 h-1 bg-[#e7e5df] rounded-full mx-auto mb-2"></div>
                    <h3 class="font-bold text-base text-[#1c1917]">Rincian Pembayaran POS</h3>
                </div>
            </template>

            <!-- Bottom Sheet Content -->
            <div class="space-y-4 pb-4">
                <!-- Cart Items -->
                <div class="max-h-[220px] overflow-y-auto space-y-2 pr-1">
                    <div
                        v-for="(c, idx) in cart"
                        :key="c.id_stock"
                        class="p-2.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df] flex justify-between items-center"
                    >
                        <div>
                            <h4 class="text-xs font-semibold text-[#1c1917]">{{ c.nama }}</h4>
                            <span class="text-xs font-bold text-[#1c1917] font-mono">{{ formatCurrency(c.harga_jual) }}</span>
                        </div>
                        <button
                            type="button"
                            class="w-6 h-6 rounded-lg text-[#78716c] hover:text-rose-600 flex items-center justify-center"
                            @click="removeFromCart(idx)"
                        >
                            <i class="pi pi-trash text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="grid grid-cols-2 gap-2">
                    <input
                        v-model="buyerName"
                        type="text"
                        placeholder="Nama Pembeli"
                        class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917]"
                    />
                    <input
                        v-model="buyerPhone"
                        type="text"
                        placeholder="No. WhatsApp"
                        class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917]"
                    />
                </div>

                <!-- Total & Payment Method -->
                <div class="p-3 rounded-xl bg-[#f6f5f0] border border-[#e7e5df] flex justify-between items-center">
                    <span class="text-xs font-semibold text-[#78716c]">Total Belanja</span>
                    <span class="text-lg font-bold text-[#1c1917] font-mono">{{ formatCurrency(cartTotal) }}</span>
                </div>

                <div class="grid grid-cols-3 gap-1.5">
                    <button
                        v-for="m in ['Cash', 'Transfer', 'QRIS']"
                        :key="m"
                        type="button"
                        class="py-2 rounded-xl text-xs font-semibold transition-all border"
                        :class="paymentMethod === m ? 'bg-[#1c1917] text-white border-[#1c1917]' : 'bg-[#faf9f5] border-[#e7e5df] text-[#78716c]'"
                        @click="paymentMethod = m"
                    >
                        {{ m }}
                    </button>
                </div>

                <!-- Cash input on mobile -->
                <div v-if="paymentMethod === 'Cash'" class="space-y-2">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-[#78716c]">Uang Diterima</span>
                        <button type="button" class="text-[#1c1917] font-semibold" @click="setExactMoney">Uang Pas</button>
                    </div>
                    <input
                        v-model.number="cashReceived"
                        type="number"
                        placeholder="Nominal uang tunai"
                        class="w-full text-base font-bold font-mono p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917]"
                    />
                    <div class="flex justify-between items-center text-xs pt-1">
                        <span class="text-[#78716c]">Kembalian</span>
                        <span class="font-bold font-mono" :class="isUnderpaid ? 'text-rose-600' : 'text-amber-800 dark:text-amber-400'">
                            {{ isUnderpaid ? 'Kurang ' + formatCurrency(cartTotal - (cashReceived || 0)) : formatCurrency(changeDue) }}
                        </span>
                    </div>
                </div>

                <!-- Action Button -->
                <button
                    type="button"
                    class="w-full py-3.5 rounded-xl font-bold text-xs uppercase tracking-wider text-white bg-[#1c1917] shadow-sm"
                    :disabled="cart.length === 0 || (paymentMethod === 'Cash' && isUnderpaid) || isSubmitting"
                    @click="processCheckout"
                >
                    {{ isSubmitting ? 'Memproses...' : 'Selesaikan & Cetak Struk' }}
                </button>
            </div>
        </Dialog>

        <!-- ============================================================== -->
        <!-- MODE 2: RIWAYAT TRANSAKSI PENJUALAN                            -->
        <!-- ============================================================== -->
        <div v-show="activeTab === 'history'" class="bg-white border border-[#e7e5df] rounded-2xl p-5 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-[#e7e5df]">
                <div>
                    <h2 class="text-lg font-bold text-[#1c1917] tracking-tight">
                        Riwayat Transaksi Penjualan
                    </h2>
                    <p class="text-xs text-[#78716c]">Daftar seluruh transaksi yang tercatat dalam sistem kasir</p>
                </div>

                <!-- Global Filter -->
                <div class="relative">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#78716c] text-xs"></i>
                    <input
                        v-model="historyFilters['global'].value"
                        type="text"
                        placeholder="Cari faktur, pembeli, IMEI..."
                        class="text-xs pl-8 pr-4 py-2 rounded-xl bg-[#faf9f5] border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917] w-64"
                    />
                </div>
            </div>

            <!-- Table -->
            <DataTable
                :value="penjualan"
                :paginator="true"
                :rows="10"
                :rowsPerPageOptions="[10, 20, 50]"
                dataKey="id_penjualan"
                :filters="historyFilters"
                responsiveLayout="scroll"
                stripedRows
                class="p-datatable-sm"
            >
                <template #empty>
                    <div class="text-center py-8 text-[#78716c]">
                        Belum ada transaksi penjualan yang tercatat.
                    </div>
                </template>

                <!-- No Faktur -->
                <Column field="no_invoice" header="Faktur" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-semibold text-[#1c1917]">{{ data.no_invoice || '-' }}</span>
                    </template>
                </Column>

                <!-- Tanggal -->
                <Column field="tanggal" header="Tanggal" sortable style="min-width: 7.5rem">
                    <template #body="{ data }">
                        <span class="text-xs text-[#44403c]">{{ new Date(data.tanggal).toLocaleDateString('id-ID') }}</span>
                    </template>
                </Column>

                <!-- Kasir -->
                <Column field="user.nama" header="Kasir" sortable style="min-width: 8rem">
                    <template #body="{ data }">
                        <span class="text-xs font-medium text-[#44403c]">{{ data.user?.nama || 'Petugas' }}</span>
                    </template>
                </Column>

                <!-- Produk Terjual -->
                <Column header="Barang Terjual" style="min-width: 14rem">
                    <template #body="{ data }">
                        <div class="text-xs">
                            <span class="font-semibold text-[#1c1917]">{{ data.stok_hp?.nama || 'Item' }}</span>
                            <div v-if="data.stok_hp?.imei" class="text-[10px] font-mono text-[#78716c]">
                                IMEI: {{ data.stok_hp.imei }}
                            </div>
                        </div>
                    </template>
                </Column>

                <!-- Pembeli -->
                <Column field="nama_pembeli" header="Pembeli" style="min-width: 9rem">
                    <template #body="{ data }">
                        <div class="text-xs">
                            <span class="font-medium text-[#44403c]">{{ data.nama_pembeli || 'Umum' }}</span>
                            <div v-if="data.no_hp_pembeli" class="text-[10px] text-[#78716c]">
                                {{ data.no_hp_pembeli }}
                            </div>
                        </div>
                    </template>
                </Column>

                <!-- Harga Jual -->
                <Column field="harga_jual" header="Nominal" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="font-bold text-[#1c1917] font-mono text-xs">
                            {{ formatCurrency(data.harga_jual) }}
                        </span>
                    </template>
                </Column>

                <!-- Metode Pembayaran -->
                <Column field="met_pem" header="Metode" sortable style="min-width: 6.5rem">
                    <template #body="{ data }">
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-[#f0ede6] text-[#44403c] border border-[#e7e5df]">
                            {{ data.met_pem }}
                        </span>
                    </template>
                </Column>

                <!-- Actions -->
                <Column header="Aksi" style="min-width: 7rem" :exportable="false">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                class="w-8 h-8 rounded-lg bg-[#f0ede6] text-[#44403c] hover:bg-[#1c1917] hover:text-white flex items-center justify-center transition-colors"
                                v-tooltip.top="'Cetak Struk Thermal'"
                                @click="printInvoice(data.no_invoice)"
                            >
                                <i class="pi pi-print text-xs"></i>
                            </button>

                            <button
                                v-if="isOwner"
                                type="button"
                                class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-colors"
                                v-tooltip.top="'Hapus & Kembalikan Stok'"
                                @click="confirmDeleteSale(data)"
                            >
                                <i class="pi pi-trash text-xs"></i>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- ============================================================== -->
        <!-- THERMAL RECEIPT MODAL                                          -->
        <!-- ============================================================== -->
        <ThermalReceipt
            v-model:visible="receiptDialog"
            :invoice="currentReceipt"
        />

        <!-- Confirm Delete Sale Dialog -->
        <Dialog
            v-model:visible="deleteDialog"
            header="Hapus Transaksi Penjualan"
            modal
            :style="{ width: '450px', maxWidth: '90vw' }"
            class="rounded-2xl bg-white"
        >
            <div class="flex items-start gap-3 pt-2">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-200">
                    <i class="pi pi-exclamation-triangle text-lg"></i>
                </div>
                <div>
                    <p class="font-bold text-[#1c1917]">Batalkan transaksi ini?</p>
                    <p class="text-xs text-[#78716c] mt-1 leading-relaxed">
                        Faktur <b>{{ selectedSaleToDelete?.no_invoice }}</b> akan dihapus, dan unit barang 
                        <b>{{ selectedSaleToDelete?.stok_hp?.nama }}</b> akan dikembalikan statusnya menjadi <b>Tersedia (Ready)</b> di etalase.
                    </p>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Batal" text severity="secondary" @click="deleteDialog = false" />
                    <Button label="Hapus & Kembalikan Stok" severity="danger" @click="executeDeleteSale" />
                </div>
            </template>
        </Dialog>
    </div>
</template>
