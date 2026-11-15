<script setup>
import FloatingConfigurator from "@/components/FloatingConfigurator.vue";
import { ref } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import { useToast } from "primevue/usetoast";
import { useAuth } from "@/composables/useAuth";

const username = ref("");
const password = ref("");
const isLoading = ref(false);
const toast = useToast();
const router = useRouter();
const { setAuth } = useAuth();

const login = async () => {
    if (!username.value.trim() || !password.value) {
        toast.add({
            severity: "warn",
            summary: "Perhatian",
            detail: "Username dan password wajib diisi",
            life: 3000,
        });
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.post("login", {
            username: username.value,
            password: password.value,
        });

        // Save Sanctum token & user in auth store
        setAuth(response.data.token, response.data.user);

        toast.add({
            severity: "success",
            summary: "Login Berhasil",
            detail: `Selamat datang kembali, ${response.data.user.nama}!`,
            life: 2500,
        });

        router.push("/dashboard");
    } catch (error) {
        let errorMessage = "Terjadi kesalahan sistem";
        if (error.response && error.response.data) {
            if (typeof error.response.data.error === "string") {
                errorMessage = error.response.data.error === "Invalid credentials"
                    ? "Username atau Password yang dimasukkan salah!"
                    : error.response.data.error;
            } else if (typeof error.response.data.error === "object") {
                errorMessage = Object.values(error.response.data.error).flat().join(", ");
            }
        }
        toast.add({ severity: "error", summary: "Login Gagal", detail: errorMessage, life: 5000 });
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Toast />
    <FloatingConfigurator />

    <!-- Nordic Warm Linen Canvas -->
    <div class="relative min-h-screen min-w-[100vw] flex items-center justify-center p-4 bg-[#f6f5f0]">
        <!-- Centered Card Container -->
        <div class="w-full max-w-md relative z-10">
            <div class="bg-white p-8 sm:p-10 rounded-3xl border border-[#e7e5df] shadow-sm">
                <!-- Brand Header -->
                <div class="text-center mb-8">
                    <div class="w-14 h-14 rounded-2xl bg-stone-900 mx-auto flex items-center justify-center text-amber-400 shadow-sm mb-4">
                        <i class="pi pi-bolt text-2xl"></i>
                    </div>

                    <h1 class="text-2xl font-extrabold text-stone-900 tracking-tight">
                        Tamboer <span class="text-amber-800">Gadget</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1.5 font-medium">
                        Sistem Kasir POS & Inventaris Smartphone
                    </p>
                </div>

                <!-- Form Login -->
                <form @submit.prevent="login" class="space-y-4">
                    <!-- Username -->
                    <div class="space-y-1.5">
                        <label for="username" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Username
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                                <i class="pi pi-user text-sm"></i>
                            </span>
                            <input
                                id="username"
                                v-model.trim="username"
                                type="text"
                                placeholder="Masukkan username"
                                class="w-full text-sm pl-10 pr-4 py-3 rounded-2xl bg-stone-50/70 border border-stone-200 text-stone-900 placeholder:text-stone-400 focus:bg-white focus:border-stone-800 focus:outline-none focus:ring-1 focus:ring-stone-800 transition-all"
                                autofocus
                            />
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <label for="password" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Kata Sandi
                        </label>
                        <div class="relative">
                            <Password
                                id="password"
                                v-model="password"
                                placeholder="Masukkan password"
                                :toggleMask="true"
                                fluid
                                :feedback="false"
                                class="w-full"
                                inputClass="w-full text-sm py-3 px-4 rounded-2xl bg-stone-50/70 border border-stone-200 text-stone-900 placeholder:text-stone-400 focus:bg-white focus:border-stone-800 focus:outline-none focus:ring-1 focus:ring-stone-800 transition-all"
                            />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full py-3.5 rounded-2xl font-bold text-sm text-stone-50 transition-all nordic-btn flex items-center justify-center gap-2 bg-stone-900 hover:bg-stone-800 shadow-sm mt-6 cursor-pointer"
                        :disabled="isLoading"
                    >
                        <i v-if="isLoading" class="pi pi-spin pi-spinner text-base"></i>
                        <i v-else class="pi pi-arrow-right text-base"></i>
                        <span>{{ isLoading ? "Mengautentikasi..." : "Masuk ke Sistem" }}</span>
                    </button>
                </form>

                <!-- Footer Hint -->
                <div class="mt-8 pt-6 border-t border-stone-100 text-center">
                    <p class="text-[11px] text-stone-400 font-medium">
                        &copy; {{ new Date().getFullYear() }} Tamboer Gadget Store &bull; Boutique & Clean POS
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
