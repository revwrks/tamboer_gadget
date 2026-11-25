<script setup>
import { nextTick, onBeforeUnmount, ref } from "vue";
import { Html5QrcodeScanner } from "html5-qrcode";
import Button from "primevue/button";
import Dialog from "primevue/dialog";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: "Scan barcode IMEI atau ketik manual...",
    },
});

const emit = defineEmits(["update:modelValue", "scanned"]);

const readerId = `barcode-reader-${Math.random().toString(36).slice(2, 9)}`;
const isCameraOpen = ref(false);
const cameraError = ref("");
let html5QrcodeScanner = null;

const handleInput = (event) => {
    const val = event.target.value;
    emit("update:modelValue", val);
    const cleaned = (val || "").replace(/\D/g, "");
    if (cleaned.length === 15) {
        emit("scanned", cleaned);
    }
};

const handleKeyDown = (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        if (props.modelValue?.trim()) {
            emit("scanned", props.modelValue.trim());
        }
    }
};

const openCameraScanner = () => {
    cameraError.value = "";
    isCameraOpen.value = true;
    nextTick(() => {
        if (!window.isSecureContext && window.location.hostname !== "localhost" && window.location.hostname !== "127.0.0.1") {
            cameraError.value = "Akses kamera memerlukan koneksi HTTPS atau localhost.";
            return;
        }
        startScanner();
    });
};

const startScanner = () => {
    stopScanner();
    try {
        html5QrcodeScanner = new Html5QrcodeScanner(
            readerId,
            {
                fps: 10,
                qrbox: { width: 260, height: 120 },
                supportedScanTypes: [0],
                rememberLastUsedCamera: true,
            },
            false
        );
        html5QrcodeScanner.render(onScanSuccess, () => {});
    } catch {
        cameraError.value = "Kamera tidak dapat diakses. Pastikan izin kamera telah diberikan.";
    }
};

const stopScanner = () => {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear().catch(() => {});
        html5QrcodeScanner = null;
    }
};

const onScanSuccess = (decodedText) => {
    const cleaned = (decodedText || "").trim();
    emit("update:modelValue", cleaned);
    emit("scanned", cleaned);
    isCameraOpen.value = false;
    stopScanner();
};

onBeforeUnmount(() => {
    stopScanner();
});
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 w-full">
            <div class="relative flex-1">
                <i class="pi pi-barcode absolute left-3.5 top-1/2 -translate-y-1/2 text-tertiary text-sm pointer-events-none" aria-hidden="true"></i>
                <input
                    :value="modelValue"
                    type="text"
                    :placeholder="placeholder"
                    :disabled="disabled"
                    class="w-full min-h-[40px] pl-10 pr-3.5 py-2 rounded-xl bg-canvas border border-hairline text-sm font-mono text-ink placeholder:text-tertiary focus:outline-none focus:border-accent transition-colors disabled:opacity-50"
                    @input="handleInput"
                    @keydown="handleKeyDown"
                />
            </div>
            <button
                type="button"
                class="min-h-[40px] px-3.5 rounded-xl bg-subtle hover:bg-hover text-ink border border-hairline flex items-center justify-center gap-2 text-xs font-semibold transition-colors shrink-0 disabled:opacity-50"
                aria-label="Buka pemindai kamera"
                v-tooltip.top="'Pindai via Kamera'"
                :disabled="disabled"
                @click="openCameraScanner"
            >
                <i class="pi pi-camera text-sm"></i>
                <span class="hidden sm:inline">Kamera</span>
            </button>
        </div>

        <Dialog
            v-model:visible="isCameraOpen"
            modal
            header="Pindai Barcode IMEI"
            :style="{ width: '92vw', maxWidth: '460px' }"
            @hide="stopScanner"
        >
            <div class="space-y-3">
                <p class="text-xs text-muted bg-canvas p-3 rounded-xl border border-hairline">
                    Arahkan kamera ke barcode IMEI pada dus kemasan atau stiker bodi unit.
                </p>

                <div
                    v-if="cameraError"
                    role="alert"
                    class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-medium"
                >
                    {{ cameraError }}
                </div>

                <div
                    v-else
                    :id="readerId"
                    class="w-full rounded-xl overflow-hidden border border-hairline bg-canvas"
                ></div>
            </div>

            <template #footer>
                <Button label="Tutup" icon="pi pi-times" text severity="secondary" @click="isCameraOpen = false" />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
:deep([id^="barcode-reader-"]) {
    border: none !important;
}
:deep(#html5-qrcode-button-camera-start),
:deep(#html5-qrcode-button-camera-stop),
:deep(#html5-qrcode-button-camera-permission) {
    background-color: var(--text-primary) !important;
    color: var(--bg-surface) !important;
    border: none !important;
    padding: 8px 14px !important;
    border-radius: 10px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    margin: 6px !important;
    cursor: pointer !important;
}
</style>
