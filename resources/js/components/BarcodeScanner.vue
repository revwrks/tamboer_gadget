<template>
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 w-full">
            <span class="p-input-icon-left w-full relative">
                <i class="pi pi-barcode absolute left-3 top-1/2 transform -translate-y-1/2 text-stone-400" />
                <InputText 
                    :modelValue="modelValue"
                    @update:modelValue="handleInput"
                    @keydown="handleKeyDown"
                    placeholder="Scan barcode IMEI / ketik manual..." 
                    class="w-full pl-10 text-xs font-mono"
                    :disabled="disabled"
                />
            </span>
            <Button 
                icon="pi pi-camera" 
                aria-label="Scan Kamera HP" 
                @click="openCameraScanner" 
                severity="secondary" 
                v-tooltip.top="'Scan Barcode via Kamera HP/Webcam'" 
                :disabled="disabled"
                class="shrink-0 bg-stone-100 hover:bg-stone-200 dark:bg-stone-800 text-stone-800 dark:text-stone-200 border border-stone-300 dark:border-stone-700"
            />
        </div>

        <!-- Dialog for camera scanner -->
        <Dialog 
            v-model:visible="isCameraOpen" 
            modal 
            header="Scan Barcode IMEI via Kamera" 
            :style="{ width: '92vw', maxWidth: '480px' }" 
            @hide="stopScanner"
            class="rounded-2xl"
        >
            <div class="mb-3 text-xs text-stone-600 dark:text-stone-400 flex items-center gap-2 bg-amber-50/70 dark:bg-amber-950/40 p-2.5 rounded-xl border border-amber-200 dark:border-amber-800/40">
                <i class="pi pi-info-circle text-amber-700 dark:text-amber-400 text-sm"></i>
                <span>Arahkan kamera ke barcode IMEI pada dus HP atau stiker bodi perangkat.</span>
            </div>
            <div id="reader" width="100%" class="rounded-xl overflow-hidden shadow-sm border border-stone-200 dark:border-stone-700"></div>
            <div class="flex justify-end gap-2 mt-4">
                <Button label="Tutup" icon="pi pi-times" @click="isCameraOpen = false" text severity="secondary" />
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onBeforeUnmount, nextTick } from 'vue';
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

const handleInput = (val) => {
    emit('update:modelValue', val);
    const cleaned = (val || '').replace(/\D/g, '');
    // Auto-trigger if exactly 15 digits IMEI entered/pasted
    if (cleaned.length === 15) {
        emit('scanned', cleaned);
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        if (props.modelValue) {
            emit('scanned', props.modelValue.trim());
        }
    }
};

const openCameraScanner = () => {
    isCameraOpen.value = true;
    nextTick(() => {
        startScanner();
    });
};

const startScanner = () => {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
    }
    
    html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        { 
            fps: 10, 
            qrbox: { width: 260, height: 120 }, 
            supportedScanTypes: [0],
            rememberLastUsedCamera: true
        },
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
    const cleaned = (decodedText || '').trim();
    emit('update:modelValue', cleaned);
    emit('scanned', cleaned);
    isCameraOpen.value = false;
    stopScanner();
};

const onScanFailure = (error) => {
    // Ignore frame scan failures
};

onBeforeUnmount(() => {
    stopScanner();
});
</script>

<style scoped>
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
:deep(button#html5-qrcode-button-camera-start),
:deep(button#html5-qrcode-button-camera-stop),
:deep(button#html5-qrcode-button-camera-permission) {
    background-color: #1c1917 !important;
    color: #ffffff !important;
    border: none !important;
    padding: 6px 14px !important;
    border-radius: 8px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    margin: 4px !important;
    cursor: pointer !important;
}
</style>
