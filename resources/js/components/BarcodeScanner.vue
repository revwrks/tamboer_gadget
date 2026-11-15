<template>
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 w-full">
            <span class="p-input-icon-left w-full relative">
                <i class="pi pi-barcode absolute left-3 top-1/2 transform -translate-y-1/2 text-surface-400" />
                <InputText 
                    :modelValue="modelValue"
                    @update:modelValue="$emit('update:modelValue', $event)"
                    placeholder="Scan or type IMEI..." 
                    class="w-full pl-10"
                    :disabled="disabled"
                />
            </span>
            <Button icon="pi pi-camera" aria-label="Camera" @click="openCameraScanner" severity="secondary" v-tooltip.top="'Scan with Camera'" :disabled="disabled"/>
        </div>

        <!-- Dialog for camera scanner -->
        <Dialog v-model:visible="isCameraOpen" modal header="Scan Barcode" :style="{ width: '90vw', maxWidth: '500px' }" @hide="stopScanner">
            <div id="reader" width="100%" class="rounded-xl overflow-hidden shadow-lg border border-surface-200 dark:border-surface-700"></div>
            <div class="flex justify-end gap-2 mt-4">
                <Button label="Cancel" icon="pi pi-times" @click="isCameraOpen = false" text />
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Html5QrcodeScanner } from 'html5-qrcode';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';

const props = defineProps({
    modelValue: String,
    disabled: Boolean
});
const emit = defineEmits(['update:modelValue', 'scanned']);

const isCameraOpen = ref(false);
let html5QrcodeScanner = null;

const openCameraScanner = () => {
    isCameraOpen.value = true;
    nextTick(() => {
        startScanner();
    });
};

const startScanner = () => {
    // If it's already initialized, return
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
    }
    
    html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        { fps: 10, qrbox: { width: 250, height: 100 }, supportedScanTypes: [0] },
        /* verbose= */ false
    );
    
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
};

const stopScanner = () => {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear().catch(error => {
            console.error("Failed to clear html5QrcodeScanner. ", error);
        });
        html5QrcodeScanner = null;
    }
};

const onScanSuccess = (decodedText, decodedResult) => {
    emit('update:modelValue', decodedText);
    emit('scanned', decodedText);
    isCameraOpen.value = false;
    stopScanner();
};

const onScanFailure = (error) => {
    // handle scan failure, usually better to ignore and keep scanning
    // console.warn(`Code scan error = ${error}`);
};

onBeforeUnmount(() => {
    stopScanner();
});
</script>

<style scoped>
/* Adjust html5-qrcode UI to blend better with PrimeVue/Tailwind */
:deep(#reader) {
    border: none !important;
}
:deep(#reader__scan_region) {
    background-color: var(--surface-100);
}
:deep(#reader__dashboard_section_csr span) {
    color: var(--text-color);
}
:deep(#reader__dashboard_section_swaplink) {
    color: var(--primary-color);
    text-decoration: underline;
}
</style>

