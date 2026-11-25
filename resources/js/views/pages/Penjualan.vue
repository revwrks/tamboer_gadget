<script setup>
import { FilterMatchMode } from "@primevue/core/api";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import BarcodeScanner from "../../components/BarcodeScanner.vue";
import ThermalReceipt from "../../components/ThermalReceipt.vue";
import { useAuth } from "@/composables/useAuth";
import { extractApiError, formatCurrency, formatDate } from "@/utils/formatters";

const route = useRoute();
const router = useRouter();
const { user, isOwner } = useAuth();
const toast = useToast();

const activeTab = ref("pos");

const penjualan = ref([]);
const stokItemsList = ref([]);
const isLoading = ref(false);

const posSearch = ref("");
const posCategory = ref("all");
const posCondition = ref("all");
const historyFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const mobileCartDrawer = ref(false);
const receiptDialog = ref(false);
const currentReceipt = ref({});
const deleteDialog = ref(false);
const selectedSaleToDelete = ref(null);

const cart = ref([]);
const paymentMethod = ref("Cash");
const discount = ref(0);
const cashReceived = ref(0);
const buyerName = ref("");
const buyerPhone = ref("");
const saleNotes = ref("");
const isSubmitting = ref(false);

const fetchPenjualan = async () => {
    try {
        const response = await axios.get("penjualan");
        penjualan.value = response.data;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Memuat",
            detail: extractApiError(error, "Gagal memuat riwayat transaksi."),
            life: 4000,
        });
    }
};

const fetchStokItems = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get("stocks");
        stokItemsList.value = response.data;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Memuat",
            detail: extractApiError(error, "Gagal mengambil katalog stok."),
            life: 4000,
        });
    } finally {
        isLoading.value = false;
    }
};

const filteredReadyStocks = computed(() => {
    let list = stokItemsList.value.filter((s) => s.status === "ada");

    if (posCategory.value !== "all") {
        list = list.filter((s) => s.kategori === posCategory.value);
    }

    if (posCondition.value !== "all") {
        list = list.filter((s) => (s.kondisi || "baru") === posCondition.value);
    }

    if (posSearch.value.trim()) {
        const q = posSearch.value.toLowerCase().trim();
        list = list.filter(
            (s) =>
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

const categoryCounts = computed(() => {
    const ready = stokItemsList.value.filter((s) => s.status === "ada");
    return {
        all: ready.length,
        gadget: ready.filter((s) => s.kategori === "gadget").length,
        aksesoris: ready.filter((s) => s.kategori === "aksesoris").length,
        sparepart: ready.filter((s) => s.kategori === "sparepart").length,
    };
});

const cartSubtotal = computed(() => {
    return cart.value.reduce((acc, item) => acc + (parseFloat(item.harga_jual) || 0), 0);
});

const normalizedDiscount = computed(() => {
    const val = parseFloat(discount.value) || 0;
    return Math.max(0, Math.min(val, cartSubtotal.value));
});

const isDiscountExcessive = computed(() => {
    return (parseFloat(discount.value) || 0) > cartSubtotal.value && cartSubtotal.value > 0;
});

const cartTotal = computed(() => {
    return Math.max(0, cartSubtotal.value - normalizedDiscount.value);
});

const changeDue = computed(() => {
    if (paymentMethod.value !== "Cash") return 0;
    return Math.max(0, (parseFloat(cashReceived.value) || 0) - cartTotal.value);
});

const isUnderpaid = computed(() => {
    if (paymentMethod.value !== "Cash") return false;
    return (parseFloat(cashReceived.value) || 0) < cartTotal.value;
});

const quickCashOptions = computed(() => {
    const total = cartTotal.value;
    if (total <= 0) return [50000, 100000, 200000, 500000];
    const step = total >= 1000000 ? 100000 : 50000;
    const base = Math.ceil(total / step) * step;
    const candidates = [base, base + step, base + step * 2, base + step * 5];
    return [...new Set(candidates)].filter((amt) => amt >= total).slice(0, 4);
});

const isItemInCart = (id_stock) => cart.value.some((c) => c.id_stock === id_stock);

const addToCart = (stock) => {
    if (isItemInCart(stock.id_stock)) {
        toast.add({ severity: "warn", summary: "Perhatian", detail: "Unit ini sudah ada di keranjang.", life: 1500 });
        return;
    }
    cart.value.push({ ...stock });
    toast.add({ severity: "success", summary: "Ditambahkan", detail: `${stock.nama} masuk keranjang.`, life: 1200 });
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
        toast.add({ severity: "error", summary: "Tidak Ditemukan", detail: "Tidak ada unit ready dengan IMEI tersebut.", life: 3000 });
    }
};

const setExactMoney = () => {
    cashReceived.value = cartTotal.value;
};

const setQuickMoney = (amount) => {
    cashReceived.value = amount;
};

const processCheckout = async () => {
    if (cart.value.length === 0) {
        toast.add({ severity: "warn", summary: "Keranjang Kosong", detail: "Pilih minimal 1 barang.", life: 2500 });
        return;
    }

    if (isDiscountExcessive.value) {
        toast.add({ severity: "error", summary: "Diskon Tidak Valid", detail: "Diskon melebihi subtotal belanja.", life: 3000 });
        return;
    }

    if (paymentMethod.value === "Cash" && isUnderpaid.value) {
        toast.add({ severity: "error", summary: "Uang Kurang", detail: "Nominal uang diterima kurang dari total akhir.", life: 3000 });
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
            diskon: normalizedDiscount.value,
            uang_bayar: paymentMethod.value === "Cash" ? parseFloat(cashReceived.value) || cartTotal.value : cartTotal.value,
            kembalian: changeDue.value,
            nama_pembeli: buyerName.value || null,
            no_hp_pembeli: buyerPhone.value || null,
            catatan: saleNotes.value || null,
        };

        const res = await axios.post("penjualan/checkout", payload);
        const invoiceData = res.data.data;

        toast.add({
            severity: "success",
            summary: "Checkout Berhasil",
            detail: `Faktur ${invoiceData.no_invoice} tersimpan.`,
            life: 3000,
        });

        await Promise.all([fetchPenjualan(), fetchStokItems()]);
        mobileCartDrawer.value = false;
        clearCart();

        await printInvoice(invoiceData.no_invoice);
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Checkout Gagal",
            detail: extractApiError(error, "Gagal memproses transaksi."),
            life: 5000,
        });
    } finally {
        isSubmitting.value = false;
    }
};

const printInvoice = async (no_invoice) => {
    try {
        const res = await axios.get(`penjualan/invoice/${encodeURIComponent(no_invoice)}`);
        currentReceipt.value = res.data;
        receiptDialog.value = true;
    } catch {
        toast.add({ severity: "error", summary: "Gagal Cetak", detail: "Invoice tidak ditemukan.", life: 3000 });
    }
};

const confirmDeleteSale = (sale) => {
    selectedSaleToDelete.value = sale;
    deleteDialog.value = true;
};

const executeDeleteSale = async () => {
    if (!selectedSaleToDelete.value) return;
    try {
        await axios.delete(`penjualan/${selectedSaleToDelete.value.id_penjualan}`);
        toast.add({
            severity: "success",
            summary: "Dibatalkan",
            detail: "Transaksi dihapus & unit dikembalikan ke etalase.",
            life: 3000,
        });
        deleteDialog.value = false;
        selectedSaleToDelete.value = null;
        await Promise.all([fetchPenjualan(), fetchStokItems()]);
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Hapus",
            detail: extractApiError(error, "Gagal menghapus transaksi."),
            life: 4000,
        });
    }
};

onMounted(async () => {
    await Promise.all([fetchPenjualan(), fetchStokItems()]);

    const addStockId = Number(route.query.addStock);
    if (addStockId) {
        const target = stokItemsList.value.find((s) => s.id_stock === addStockId && s.status === "ada");
        if (target) {
            activeTab.value = "pos";
            addToCart(target);
        }
        router.replace({ query: {} });
    }
});
</script>

<template>
    <div class="space-y-6 pb-24 lg:pb-6">
        <!-- Header & Mode Switcher -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">
                    Kasir POS
                </h1>
                <p class="text-xs sm:text-sm text-muted mt-0.5 tabular-nums">
                    Kasir: <span class="font-semibold text-ink">{{ user?.nama || "Petugas" }}</span>
                    &bull; {{ filteredReadyStocks.length }} unit siap jual
                </p>
            </div>

            <div class="inline-flex p-1 rounded-xl bg-subtle border border-hairline self-start sm:self-auto">
                <button
                    type="button"
                    class="min-h-[36px] px-4 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-2 transition-colors"
                    :class="activeTab === 'pos' ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink'"
                    @click="activeTab = 'pos'"
                >
                    <i class="pi pi-shopping-bag text-xs"></i>
                    <span>Kasir</span>
                    <span
                        v-if="cart.length > 0"
                        class="px-1.5 py-0.5 rounded-full text-[10px] bg-accent text-white font-bold tabular-nums"
                    >
                        {{ cart.length }}
                    </span>
                </button>

                <button
                    type="button"
                    class="min-h-[36px] px-4 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-2 transition-colors"
                    :class="activeTab === 'history' ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink'"
                    @click="activeTab = 'history'"
                >
                    <i class="pi pi-history text-xs"></i>
                    <span>Riwayat</span>
                </button>
            </div>
        </div>

        <!-- MODE 1: KASIR POS -->
        <div v-show="activeTab === 'pos'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left: Product Catalog -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-4">
                <div class="bg-surface border border-hairline rounded-2xl p-4 sm:p-5 space-y-3.5">
                    <BarcodeScanner
                        v-model="posSearch"
                        placeholder="Pindai IMEI atau cari nama, brand, tipe..."
                        @scanned="handleBarcodeScanned"
                    />

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-hairline">
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                            <button
                                v-for="cat in [
                                    { id: 'all', label: `Semua (${categoryCounts.all})` },
                                    { id: 'gadget', label: `Smartphone (${categoryCounts.gadget})` },
                                    { id: 'aksesoris', label: `Aksesoris (${categoryCounts.aksesoris})` },
                                    { id: 'sparepart', label: `Sparepart (${categoryCounts.sparepart})` },
                                ]"
                                :key="cat.id"
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors tabular-nums"
                                :class="posCategory === cat.id ? 'bg-ink text-surface' : 'bg-subtle text-muted hover:text-ink'"
                                @click="posCategory = cat.id"
                            >
                                {{ cat.label }}
                            </button>
                        </div>

                        <div class="inline-flex p-0.5 rounded-xl bg-subtle border border-hairline self-end sm:self-auto shrink-0">
                            <button
                                v-for="cond in [
                                    { id: 'all', label: 'Semua' },
                                    { id: 'baru', label: 'Baru' },
                                    { id: 'second', label: 'Second' },
                                ]"
                                :key="cond.id"
                                type="button"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors"
                                :class="posCondition === cond.id ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink'"
                                @click="posCondition = cond.id"
                            >
                                {{ cond.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Product Grid -->
                <div v-if="filteredReadyStocks.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3.5">
                    <button
                        v-for="item in filteredReadyStocks"
                        :key="item.id_stock"
                        type="button"
                        class="text-left bg-surface border rounded-2xl p-4 flex flex-col justify-between transition-colors group"
                        :class="
                            isItemInCart(item.id_stock)
                                ? 'border-accent ring-1 ring-accent bg-accent-soft/40'
                                : 'border-hairline hover:border-hairline-strong'
                        "
                        @click="addToCart(item)"
                    >
                        <div class="w-full">
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-accent-ink">
                                    {{ item.brand }}
                                </span>
                                <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded bg-subtle text-muted border border-hairline">
                                    {{ item.kondisi || "Baru" }}
                                </span>
                            </div>

                            <h3 class="font-bold text-ink text-sm leading-snug">
                                {{ item.nama }}
                            </h3>

                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <span v-if="item.storage || item.ram" class="text-[11px] font-mono px-2 py-0.5 rounded bg-canvas text-muted border border-hairline">
                                    {{ [item.ram, item.storage].filter(Boolean).join(" / ") }}
                                </span>
                                <span v-if="item.warna && item.warna !== '-'" class="text-[11px] px-2 py-0.5 rounded bg-canvas text-muted border border-hairline">
                                    {{ item.warna }}
                                </span>
                            </div>

                            <div v-if="item.imei" class="mt-2 text-[11px] font-mono tabular-nums text-tertiary flex items-center gap-1">
                                <i class="pi pi-qrcode text-[10px]"></i>
                                <span>{{ item.imei }}</span>
                            </div>
                        </div>

                        <div class="w-full mt-4 pt-3 border-t border-hairline flex items-center justify-between">
                            <div>
                                <span class="text-[11px] text-tertiary block">Harga</span>
                                <span class="text-sm font-bold text-ink font-mono tabular-nums">
                                    {{ formatCurrency(item.harga_jual) }}
                                </span>
                            </div>

                            <span
                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                :class="
                                    isItemInCart(item.id_stock)
                                        ? 'bg-accent text-white'
                                        : 'bg-subtle text-ink group-hover:bg-ink group-hover:text-surface'
                                "
                            >
                                <i :class="isItemInCart(item.id_stock) ? 'pi pi-check text-xs' : 'pi pi-plus text-xs'"></i>
                            </span>
                        </div>
                    </button>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-surface border border-hairline rounded-2xl p-12 text-center space-y-2">
                    <i class="pi pi-search text-2xl text-tertiary block"></i>
                    <h3 class="font-bold text-ink text-sm">Tidak Ada Barang Ready</h3>
                    <p class="text-xs text-muted">Ubah kata kunci pencarian atau filter kategori untuk melihat unit lain.</p>
                </div>
            </div>

            <!-- Right: Sticky Shopping Cart (Desktop) -->
            <div class="hidden lg:block lg:col-span-5 xl:col-span-4 sticky top-20">
                <div class="bg-surface border border-hairline rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-hairline">
                        <div class="flex items-center gap-2">
                            <h2 class="font-bold text-sm text-ink">Keranjang</h2>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold tabular-nums bg-subtle text-ink border border-hairline">
                                {{ cart.length }}
                            </span>
                        </div>
                        <button
                            v-if="cart.length > 0"
                            type="button"
                            class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline"
                            @click="clearCart"
                        >
                            Kosongkan
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <input
                            v-model="buyerName"
                            type="text"
                            placeholder="Nama pembeli (opsional)"
                            class="w-full min-h-[38px] text-xs px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink placeholder:text-tertiary focus:outline-none focus:border-accent"
                        />
                        <input
                            v-model="buyerPhone"
                            type="text"
                            placeholder="No. HP / WA (opsional)"
                            class="w-full min-h-[38px] text-xs px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink placeholder:text-tertiary focus:outline-none focus:border-accent"
                        />
                    </div>

                    <!-- Cart Items -->
                    <div class="max-h-[240px] overflow-y-auto studio-scrollbar space-y-2 pr-1">
                        <div v-if="cart.length === 0" class="py-8 text-center text-muted">
                            <p class="text-xs font-medium">Keranjang masih kosong</p>
                            <p class="text-[11px] text-tertiary mt-0.5">Pilih unit di sebelah kiri atau pindai IMEI.</p>
                        </div>

                        <div
                            v-for="(c, idx) in cart"
                            :key="c.id_stock"
                            class="p-3 rounded-xl bg-canvas border border-hairline flex items-start justify-between gap-3"
                        >
                            <div class="min-w-0 flex-1">
                                <span class="text-[10px] font-bold uppercase text-accent-ink block">{{ c.brand }}</span>
                                <h4 class="text-xs font-semibold text-ink truncate">{{ c.nama }}</h4>
                                <span class="text-xs font-bold text-ink font-mono tabular-nums block mt-0.5">
                                    {{ formatCurrency(c.harga_jual) }}
                                </span>
                            </div>
                            <button
                                type="button"
                                class="w-7 h-7 rounded-lg text-tertiary hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-500/10 flex items-center justify-center transition-colors shrink-0"
                                aria-label="Hapus dari keranjang"
                                @click="removeFromCart(idx)"
                            >
                                <i class="pi pi-trash text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Totals & Payment -->
                    <div class="space-y-3 pt-3 border-t border-hairline text-xs">
                        <div class="flex justify-between text-muted">
                            <span>Subtotal</span>
                            <span class="font-semibold text-ink font-mono tabular-nums">{{ formatCurrency(cartSubtotal) }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <label for="pos-discount" class="text-muted">Diskon (Rp)</label>
                            <input
                                id="pos-discount"
                                v-model.number="discount"
                                type="number"
                                min="0"
                                class="w-32 min-h-[36px] text-right text-xs px-2.5 py-1.5 rounded-lg bg-canvas border border-hairline font-mono tabular-nums font-semibold text-ink focus:outline-none focus:border-accent"
                            />
                        </div>
                        <p v-if="isDiscountExcessive" class="text-[11px] text-rose-600 dark:text-rose-400 text-right">
                            Diskon melebihi subtotal.
                        </p>

                        <div class="p-3 rounded-xl bg-canvas border border-hairline flex justify-between items-center">
                            <span class="font-bold text-ink uppercase text-[11px] tracking-wider">Total Akhir</span>
                            <span class="font-bold text-lg text-ink font-mono tabular-nums">
                                {{ formatCurrency(cartTotal) }}
                            </span>
                        </div>

                        <div>
                            <span class="block text-[11px] font-semibold text-muted mb-1.5">Metode Pembayaran</span>
                            <div class="grid grid-cols-3 gap-1.5">
                                <button
                                    v-for="m in ['Cash', 'Transfer', 'QRIS']"
                                    :key="m"
                                    type="button"
                                    class="min-h-[36px] rounded-lg text-xs font-semibold transition-colors border"
                                    :class="
                                        paymentMethod === m
                                            ? 'bg-ink text-surface border-ink'
                                            : 'bg-canvas border-hairline text-muted hover:text-ink'
                                    "
                                    @click="paymentMethod = m"
                                >
                                    {{ m }}
                                </button>
                            </div>
                        </div>

                        <div v-if="paymentMethod === 'Cash'" class="space-y-2 bg-canvas p-3 rounded-xl border border-hairline">
                            <div class="flex justify-between items-center">
                                <label for="pos-cash" class="font-semibold text-[11px] text-muted">Uang Diterima</label>
                                <button
                                    type="button"
                                    class="text-[11px] font-semibold text-accent-ink hover:underline"
                                    @click="setExactMoney"
                                >
                                    Uang Pas
                                </button>
                            </div>

                            <input
                                id="pos-cash"
                                v-model.number="cashReceived"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="w-full min-h-[40px] text-sm font-bold font-mono tabular-nums px-3 py-1.5 rounded-lg bg-surface border border-hairline text-ink focus:outline-none focus:border-accent"
                            />

                            <div class="grid grid-cols-2 gap-1.5">
                                <button
                                    v-for="amt in quickCashOptions"
                                    :key="amt"
                                    type="button"
                                    class="py-1.5 px-2 rounded-lg text-[11px] font-mono tabular-nums font-semibold bg-surface border border-hairline text-muted hover:text-ink transition-colors"
                                    @click="setQuickMoney(amt)"
                                >
                                    {{ formatCurrency(amt) }}
                                </button>
                            </div>

                            <div class="flex justify-between items-center pt-2 border-t border-hairline">
                                <span class="text-xs font-semibold text-muted">Kembalian</span>
                                <span
                                    class="font-bold text-sm font-mono tabular-nums"
                                    :class="isUnderpaid ? 'text-rose-600 dark:text-rose-400' : 'text-accent-ink'"
                                >
                                    {{ isUnderpaid ? "Kurang " + formatCurrency(cartTotal - (cashReceived || 0)) : formatCurrency(changeDue) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="w-full min-h-[44px] py-3 rounded-xl font-bold text-xs uppercase tracking-wider bg-ink text-surface hover:opacity-90 transition-opacity flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
                        :disabled="cart.length === 0 || isDiscountExcessive || (paymentMethod === 'Cash' && isUnderpaid) || isSubmitting"
                        @click="processCheckout"
                    >
                        <i v-if="isSubmitting" class="pi pi-spin pi-spinner text-xs"></i>
                        <span>{{ isSubmitting ? "Memproses..." : "Bayar & Cetak Struk" }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Floating Cart Bar -->
        <div v-show="activeTab === 'pos'" class="lg:hidden fixed bottom-4 left-4 right-4 z-30">
            <button
                type="button"
                class="w-full bg-surface border border-hairline shadow-lg p-3.5 rounded-2xl flex items-center justify-between gap-3"
                @click="mobileCartDrawer = true"
            >
                <div class="flex items-center gap-3 text-left">
                    <div class="w-10 h-10 rounded-xl bg-ink text-surface flex items-center justify-center">
                        <i class="pi pi-shopping-bag text-sm text-accent"></i>
                    </div>
                    <div>
                        <span class="text-[11px] font-medium text-muted block tabular-nums">
                            {{ cart.length }} Item di Keranjang
                        </span>
                        <span class="text-base font-bold text-ink font-mono tabular-nums">
                            {{ formatCurrency(cartTotal) }}
                        </span>
                    </div>
                </div>
                <span class="px-4 py-2 rounded-xl bg-ink text-surface font-semibold text-xs">
                    Checkout
                </span>
            </button>
        </div>

        <!-- Mobile Bottom Sheet Dialog (Full Feature Parity) -->
        <Dialog
            v-model:visible="mobileCartDrawer"
            position="bottom"
            modal
            header="Rincian Pembayaran POS"
            :style="{ width: '100vw', maxWidth: '600px', margin: 0 }"
        >
            <div class="space-y-4 pb-2 text-xs">
                <div class="max-h-[200px] overflow-y-auto space-y-2 pr-1">
                    <div v-if="cart.length === 0" class="py-6 text-center text-muted">
                        Keranjang masih kosong.
                    </div>
                    <div
                        v-for="(c, idx) in cart"
                        :key="c.id_stock"
                        class="p-2.5 rounded-xl bg-canvas border border-hairline flex justify-between items-center gap-2"
                    >
                        <div class="min-w-0">
                            <h4 class="text-xs font-semibold text-ink truncate">{{ c.nama }}</h4>
                            <span class="text-xs font-bold text-ink font-mono tabular-nums">{{ formatCurrency(c.harga_jual) }}</span>
                        </div>
                        <button
                            type="button"
                            class="w-8 h-8 rounded-lg text-tertiary hover:text-rose-600 flex items-center justify-center shrink-0"
                            aria-label="Hapus item"
                            @click="removeFromCart(idx)"
                        >
                            <i class="pi pi-trash text-xs"></i>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <input
                        v-model="buyerName"
                        type="text"
                        placeholder="Nama pembeli"
                        class="w-full min-h-[42px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink"
                    />
                    <input
                        v-model="buyerPhone"
                        type="text"
                        placeholder="No. HP / WA"
                        class="w-full min-h-[42px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink"
                    />
                </div>

                <div class="flex items-center justify-between gap-2">
                    <span class="text-muted font-medium">Diskon (Rp)</span>
                    <input
                        v-model.number="discount"
                        type="number"
                        min="0"
                        class="w-36 min-h-[40px] text-right text-sm px-3 py-1.5 rounded-xl bg-canvas border border-hairline font-mono tabular-nums font-semibold text-ink"
                    />
                </div>

                <div class="p-3 rounded-xl bg-canvas border border-hairline flex justify-between items-center">
                    <span class="font-semibold text-muted">Total Akhir</span>
                    <span class="text-lg font-bold text-ink font-mono tabular-nums">{{ formatCurrency(cartTotal) }}</span>
                </div>

                <div class="grid grid-cols-3 gap-1.5">
                    <button
                        v-for="m in ['Cash', 'Transfer', 'QRIS']"
                        :key="m"
                        type="button"
                        class="min-h-[40px] rounded-xl text-xs font-semibold border transition-colors"
                        :class="paymentMethod === m ? 'bg-ink text-surface border-ink' : 'bg-canvas border-hairline text-muted'"
                        @click="paymentMethod = m"
                    >
                        {{ m }}
                    </button>
                </div>

                <div v-if="paymentMethod === 'Cash'" class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-muted">Uang Diterima</span>
                        <button type="button" class="text-accent-ink font-semibold" @click="setExactMoney">Uang Pas</button>
                    </div>
                    <input
                        v-model.number="cashReceived"
                        type="number"
                        min="0"
                        placeholder="Nominal uang tunai"
                        class="w-full min-h-[44px] text-base font-bold font-mono tabular-nums px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink"
                    />
                    <div class="grid grid-cols-2 gap-1.5">
                        <button
                            v-for="amt in quickCashOptions"
                            :key="amt"
                            type="button"
                            class="min-h-[36px] rounded-lg text-xs font-mono tabular-nums font-semibold bg-canvas border border-hairline text-muted"
                            @click="setQuickMoney(amt)"
                        >
                            {{ formatCurrency(amt) }}
                        </button>
                    </div>
                    <div class="flex justify-between items-center pt-1">
                        <span class="text-muted">Kembalian</span>
                        <span class="font-bold font-mono tabular-nums" :class="isUnderpaid ? 'text-rose-600' : 'text-accent-ink'">
                            {{ isUnderpaid ? "Kurang " + formatCurrency(cartTotal - (cashReceived || 0)) : formatCurrency(changeDue) }}
                        </span>
                    </div>
                </div>

                <button
                    type="button"
                    class="w-full min-h-[44px] rounded-xl font-bold text-xs uppercase tracking-wider bg-ink text-surface disabled:opacity-40"
                    :disabled="cart.length === 0 || isDiscountExcessive || (paymentMethod === 'Cash' && isUnderpaid) || isSubmitting"
                    @click="processCheckout"
                >
                    {{ isSubmitting ? "Memproses..." : "Selesaikan & Cetak Struk" }}
                </button>
            </div>
        </Dialog>

        <!-- MODE 2: RIWAYAT TRANSAKSI -->
        <div v-show="activeTab === 'history'" class="bg-surface border border-hairline rounded-2xl p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-ink">Riwayat Transaksi</h2>
                    <p class="text-xs text-muted">Daftar transaksi yang tercatat pada sistem kasir</p>
                </div>
                <div class="relative w-full sm:w-64">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-tertiary text-xs pointer-events-none"></i>
                    <input
                        v-model="historyFilters['global'].value"
                        type="text"
                        placeholder="Cari faktur, pembeli, IMEI..."
                        class="w-full min-h-[38px] text-xs pl-8 pr-3.5 py-2 rounded-xl bg-canvas border border-hairline text-ink placeholder:text-tertiary focus:outline-none focus:border-accent"
                    />
                </div>
            </div>

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
                    <div class="text-center py-8 text-xs text-muted">
                        Belum ada transaksi penjualan yang tercatat.
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
                        <span class="text-xs text-muted">{{ data.user?.nama || "Petugas" }}</span>
                    </template>
                </Column>

                <Column header="Barang Terjual" style="min-width: 14rem">
                    <template #body="{ data }">
                        <div class="text-xs">
                            <span class="font-semibold text-ink">{{ data.stok_hp?.nama || "Item" }}</span>
                            <div v-if="data.stok_hp?.imei" class="text-[11px] font-mono text-tertiary">
                                IMEI: {{ data.stok_hp.imei }}
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="nama_pembeli" header="Pembeli" style="min-width: 9rem">
                    <template #body="{ data }">
                        <div class="text-xs">
                            <span class="font-medium text-ink">{{ data.nama_pembeli || "Umum" }}</span>
                            <div v-if="data.no_hp_pembeli" class="text-[11px] text-tertiary">{{ data.no_hp_pembeli }}</div>
                        </div>
                    </template>
                </Column>

                <Column field="harga_jual" header="Nominal" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <div>
                            <span class="font-bold text-ink font-mono tabular-nums text-xs block">
                                {{ formatCurrency((Number(data.harga_jual) || 0) - (Number(data.diskon) || 0)) }}
                            </span>
                            <span v-if="data.diskon > 0" class="text-[10px] font-mono tabular-nums text-rose-600 dark:text-rose-400">
                                Diskon -{{ formatCurrency(data.diskon) }}
                            </span>
                        </div>
                    </template>
                </Column>

                <Column field="met_pem" header="Metode" sortable style="min-width: 6.5rem">
                    <template #body="{ data }">
                        <span class="text-[11px] font-semibold uppercase px-2 py-0.5 rounded bg-subtle text-muted border border-hairline">
                            {{ data.met_pem }}
                        </span>
                    </template>
                </Column>

                <Column header="Aksi" style="min-width: 7rem" :exportable="false">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                class="w-8 h-8 rounded-lg bg-subtle text-muted hover:text-ink hover:bg-hover flex items-center justify-center transition-colors"
                                aria-label="Cetak struk thermal"
                                v-tooltip.top="'Cetak Struk'"
                                @click="printInvoice(data.no_invoice)"
                            >
                                <i class="pi pi-print text-xs"></i>
                            </button>

                            <button
                                v-if="isOwner"
                                type="button"
                                class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-colors"
                                aria-label="Batalkan transaksi"
                                v-tooltip.top="'Batalkan & Kembalikan Stok'"
                                @click="confirmDeleteSale(data)"
                            >
                                <i class="pi pi-trash text-xs"></i>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Thermal Receipt Modal (v-model matches modelValue prop) -->
        <ThermalReceipt v-model="receiptDialog" :invoice="currentReceipt" />

        <!-- Confirm Delete Sale Dialog -->
        <Dialog
            v-model:visible="deleteDialog"
            header="Batalkan Transaksi"
            modal
            :style="{ width: '440px', maxWidth: '90vw' }"
        >
            <div class="flex items-start gap-3 pt-2">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <i class="pi pi-exclamation-triangle text-lg"></i>
                </div>
                <div>
                    <p class="font-bold text-sm text-ink">Batalkan transaksi ini?</p>
                    <p class="text-xs text-muted mt-1 leading-relaxed">
                        Faktur <b>{{ selectedSaleToDelete?.no_invoice }}</b> akan dihapus dan unit
                        <b>{{ selectedSaleToDelete?.stok_hp?.nama }}</b> dikembalikan ke status Ready.
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
