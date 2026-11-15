<template>
    <Dialog 
        v-model:visible="visible" 
        modal 
        header="Struk Pembayaran" 
        :style="{ width: '95vw', maxWidth: '420px' }"
        :closable="true"
    >
        <div class="flex flex-col items-center">
            <!-- Thermal Receipt Preview Container -->
            <div id="thermal-receipt" class="receipt-container bg-white text-black p-4 rounded border border-gray-300 shadow-sm w-full font-mono text-xs">
                <!-- Header -->
                <div class="text-center pb-2 border-b border-dashed border-gray-400">
                    <h2 class="text-base font-bold tracking-wider">TAMBOER GADGET</h2>
                    <p class="text-[10px] text-gray-600">Pusat Jual Beli Smartphone & Aksesoris</p>
                    <p class="text-[10px] text-gray-600">Jl. Tambur Raya | WA: 0812-3456-7890</p>
                </div>

                <!-- Meta Info -->
                <div class="py-2 border-b border-dashed border-gray-400 space-y-0.5 text-[11px]">
                    <div class="flex justify-between">
                        <span>No. Faktur:</span>
                        <span class="font-bold">{{ invoice.no_invoice || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Waktu:</span>
                        <span>{{ invoice.created_at || invoice.tanggal }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kasir:</span>
                        <span>{{ invoice.kasir || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Pelanggan:</span>
                        <span class="font-bold">{{ invoice.nama_pembeli || 'Umum' }}</span>
                    </div>
                    <div v-if="invoice.no_hp_pembeli" class="flex justify-between">
                        <span>No. WA:</span>
                        <span>{{ invoice.no_hp_pembeli }}</span>
                    </div>
                </div>

                <!-- Item List -->
                <div class="py-2 border-b border-dashed border-gray-400 space-y-2">
                    <div v-for="(item, idx) in (invoice.items || [])" :key="idx" class="text-[11px]">
                        <div class="flex justify-between font-bold">
                            <span>{{ item.nama }}</span>
                            <span>{{ formatRupiah(item.harga_jual) }}</span>
                        </div>
                        <div v-if="item.imei" class="text-[9px] text-gray-600 pl-1">
                            IMEI: {{ item.imei }}
                        </div>
                        <div v-if="item.warna || item.ram || item.storage" class="text-[9px] text-gray-500 pl-1">
                            {{ [item.warna, item.ram, item.storage].filter(Boolean).join(' / ') }}
                        </div>
                        <div v-if="item.garansi" class="text-[9px] text-gray-500 pl-1">
                            Garansi: {{ item.garansi }}
                        </div>
                    </div>
                </div>

                <!-- Totals -->
                <div class="py-2 border-b border-dashed border-gray-400 space-y-1 text-[11px]">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span>{{ formatRupiah(invoice.subtotal || invoice.total) }}</span>
                    </div>
                    <div v-if="invoice.diskon && invoice.diskon > 0" class="flex justify-between text-red-600">
                        <span>Diskon:</span>
                        <span>- {{ formatRupiah(invoice.diskon) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold pt-1 border-t border-dotted border-gray-300">
                        <span>TOTAL:</span>
                        <span>{{ formatRupiah(invoice.total) }}</span>
                    </div>
                    <div class="flex justify-between pt-1">
                        <span>Metode:</span>
                        <span class="font-semibold">{{ invoice.met_pem }}</span>
                    </div>
                    <div v-if="invoice.uang_bayar > 0" class="flex justify-between">
                        <span>Uang Diterima:</span>
                        <span>{{ formatRupiah(invoice.uang_bayar) }}</span>
                    </div>
                    <div v-if="invoice.kembalian !== undefined && invoice.kembalian !== null" class="flex justify-between font-bold">
                        <span>Kembalian:</span>
                        <span>{{ formatRupiah(invoice.kembalian) }}</span>
                    </div>
                </div>

                <!-- Footer / Warranty Rules -->
                <div class="pt-3 text-center text-[9px] text-gray-600 space-y-1 leading-tight">
                    <p class="font-semibold">=== KETENTUAN GARANSI ===</p>
                    <p>1. Segel toko wajib utuh & tidak rusak.</p>
                    <p>2. Tidak berlaku akibat jatuh, kena cairan / human error.</p>
                    <p>3. Wajib membawa struk ini saat klaim garansi.</p>
                    <p class="pt-2 font-bold text-[10px] text-black">Terima Kasih Atas Kunjungan Anda!</p>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between w-full gap-2">
                <Button label="Tutup" icon="pi pi-times" severity="secondary" text @click="visible = false" />
                <Button label="Cetak Struk (Print)" icon="pi pi-print" severity="primary" @click="printReceipt" />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    invoice: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
});

const formatRupiah = (val) => {
    if (val === null || val === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(val);
};

const printReceipt = () => {
    window.print();
};
</script>

<style scoped>
/* Print stylesheet khusus kertas roll thermal 58mm / 80mm */
@media print {
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
        line-height: 1.2 !important;
        background: white !important;
        color: black !important;
    }
}
</style>
