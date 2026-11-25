<script setup>
import { useRouter } from "vue-router";
import BrandMark from "@/components/BrandMark.vue";

const router = useRouter();
</script>

<template>
    <div class="min-h-screen bg-canvas text-ink flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-surface border border-hairline rounded-2xl p-8 text-center shadow-sm space-y-5">
            <div class="text-3xl font-bold font-mono tabular-nums text-accent">404</div>
            <div class="space-y-1.5">
                <h1 class="text-xl font-bold text-ink">Halaman Tidak Ditemukan</h1>
                <p class="text-sm text-muted leading-relaxed">
                    Rute yang Anda tuju tidak tersedia atau telah dipindahkan.
                </p>
            </div>
            <div class="flex items-center justify-center gap-2 pt-2">
                <button
                    type="button"
                    class="min-h-[40px] px-4 py-2 rounded-xl bg-ink text-surface text-xs font-semibold hover:opacity-90 transition-opacity"
                    @click="router.push('/dashboard')"
                >
                    Kembali ke Ringkasan
                </button>
            </div>
            <div class="pt-3 border-t border-hairline flex items-center justify-center gap-2 text-xs text-tertiary">
                <BrandMark :size="14" />
                <span>Tamboer Gadget</span>
            </div>
        </div>
    </div>
</template>
