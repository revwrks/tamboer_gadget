<script setup>
import { computed, ref } from "vue";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import { formatCurrency } from "@/utils/formatters";

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    invoice: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["update:modelValue"]);

const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});

const paperWidth = ref("58mm");

const storeInfo = computed(() => ({
    name: props.invoice?.store?.name || "Tamboer Gadget",
    tagline: props.invoice?.store?.tagline || "Boutique Smartphone & Aksesoris",
    address: props.invoice?.store?.address || "",
    contact: props.invoice?.store?.contact || "",
    warrantyNotes: props.invoice?.store?.warranty_notes || [
        "Segel toko wajib utuh dan tidak rusak.",
        "Garansi tidak berlaku akibat jatuh, cairan, atau kesalahan pemakaian.",
        "Wajib membawa struk ini saat klaim garansi.",
    ],
}));

const printReceipt = () => {
    window.print();
};
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Struk Pembayaran"
        :style="{ width: '95vw', maxWidth: '440px' }"
        :closable="true"
    >
        <div class="space-y-4">
            <!-- Paper Width Switcher -->
            <div class="flex items-center justify-between text-xs bg-canvas border border-hairline rounded-xl p-2">
                <span class="font-medium text-muted pl-1">Ukuran Kertas Thermal</span>
                <div class="inline-flex gap-1 bg-subtle p-0.5 rounded-lg">
                    <button
                        v-for="w in ['58mm', '80mm']"
                        :key="w"
                        type="button"
                        class="px-2.5 py-1 rounded-md text-xs font-semibold transition-colors"
                        :class="paperWidth === w ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink'"
                        @click="paperWidth = w"
                    >
                        {{ w }}
                    </button>
                </div>
            </div>

            <!-- Thermal Receipt Preview Container -->
            <div class="flex justify-center">
                <div
                    id="thermal-receipt"
                    :data-paper-width="paperWidth"
                    class="receipt-container bg-white text-zinc-900 p-4 rounded-xl border border-zinc-300 shadow-sm w-full font-mono text-xs tabular-nums"
                    :style="{ maxWidth: paperWidth === '58mm' ? '300px' : '380px' }"
                >
                    <div class="text-center pb-2.5 border-b border-dashed border-zinc-400">
                        <h2 class="text-sm font-bold tracking-wider uppercase">{{ storeInfo.name }}</h2>
                        <p v-if="storeInfo.tagline" class="text-[10px] text-zinc-600">{{ storeInfo.tagline }}</p>
                        <p v-if="storeInfo.address || storeInfo.contact" class="text-[10px] text-zinc-600 mt-0.5">
                            {{ [storeInfo.address, storeInfo.contact].filter(Boolean).join(' · ') }}
                        </p>
                    </div>

                    <div class="py-2 border-b border-dashed border-zinc-400 space-y-0.5 text-[11px]">
                        <div class="flex justify-between gap-2">
                            <span>No. Faktur</span>
                            <span class="font-bold">{{ invoice.no_invoice || "-" }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span>Waktu</span>
                            <span>{{ invoice.created_at || invoice.tanggal || "-" }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span>Kasir</span>
                            <span>{{ invoice.kasir || "-" }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span>Pelanggan</span>
                            <span class="font-bold">{{ invoice.nama_pembeli || "Pelanggan Umum" }}</span>
                        </div>
                        <div v-if="invoice.no_hp_pembeli" class="flex justify-between gap-2">
                            <span>Kontak</span>
                            <span>{{ invoice.no_hp_pembeli }}</span>
                        </div>
                    </div>

                    <div class="py-2 border-b border-dashed border-zinc-400 space-y-2">
                        <div v-for="(item, idx) in invoice.items || []" :key="idx" class="text-[11px]">
                            <div class="flex justify-between gap-2 font-bold">
                                <span>{{ item.nama }}</span>
                                <span class="shrink-0">{{ formatCurrency(item.harga_jual) }}</span>
                            </div>
                            <div v-if="item.imei" class="text-[10px] text-zinc-600 pl-1">
                                IMEI: {{ item.imei }}
                            </div>
                            <div v-if="item.warna || item.ram || item.storage" class="text-[10px] text-zinc-600 pl-1">
                                {{ [item.warna, item.ram, item.storage].filter(Boolean).join(" / ") }}
                            </div>
                            <div v-if="item.garansi" class="text-[10px] text-zinc-600 pl-1">
                                Garansi: {{ item.garansi }}
                            </div>
                        </div>
                    </div>

                    <div class="py-2 border-b border-dashed border-zinc-400 space-y-1 text-[11px]">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>{{ formatCurrency(invoice.subtotal ?? invoice.total) }}</span>
                        </div>
                        <div v-if="invoice.diskon && invoice.diskon > 0" class="flex justify-between text-rose-700">
                            <span>Diskon</span>
                            <span>-{{ formatCurrency(invoice.diskon) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold pt-1 border-t border-dotted border-zinc-400">
                            <span>TOTAL</span>
                            <span>{{ formatCurrency(invoice.total) }}</span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span>Metode</span>
                            <span class="font-semibold">{{ invoice.met_pem || "-" }}</span>
                        </div>
                        <div v-if="invoice.uang_bayar > 0" class="flex justify-between">
                            <span>Bayar</span>
                            <span>{{ formatCurrency(invoice.uang_bayar) }}</span>
                        </div>
                        <div v-if="invoice.kembalian !== undefined && invoice.kembalian !== null" class="flex justify-between font-bold">
                            <span>Kembalian</span>
                            <span>{{ formatCurrency(invoice.kembalian) }}</span>
                        </div>
                    </div>

                    <div class="pt-2.5 text-center text-[10px] text-zinc-700 space-y-0.5 leading-snug">
                        <p class="font-bold uppercase tracking-wide text-zinc-900">Ketentuan Garansi</p>
                        <p v-for="(note, nIdx) in storeInfo.warrantyNotes" :key="nIdx">
                            {{ nIdx + 1 }}. {{ note }}
                        </p>
                        <p class="pt-2 font-bold text-zinc-900">Terima kasih atas kunjungan Anda</p>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between w-full gap-2">
                <Button label="Tutup" icon="pi pi-times" severity="secondary" text @click="visible = false" />
                <Button label="Cetak Struk" icon="pi pi-print" @click="printReceipt" />
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
@media print {
    @page {
        margin: 0;
    }
    :global(body *) {
        visibility: hidden !important;
    }
    :global(#thermal-receipt),
    :global(#thermal-receipt *) {
        visibility: visible !important;
    }
    :global(#thermal-receipt) {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 58mm !important;
        max-width: 58mm !important;
        padding: 2mm !important;
        margin: 0 !important;
        box-shadow: none !important;
        border: none !important;
        font-size: 10px !important;
        line-height: 1.25 !important;
        background: #ffffff !important;
        color: #000000 !important;
    }
    :global(#thermal-receipt[data-paper-width="80mm"]) {
        width: 80mm !important;
        max-width: 80mm !important;
    }
}
</style>
