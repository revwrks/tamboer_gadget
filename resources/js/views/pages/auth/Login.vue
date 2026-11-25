<script setup>
import { ref } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import { useToast } from "primevue/usetoast";
import { useAuth } from "@/composables/useAuth";
import { useLayout } from "@/layout/composables/layout";
import { extractApiError } from "@/utils/formatters";
import BrandMark from "@/components/BrandMark.vue";

const username = ref("");
const password = ref("");
const isLoading = ref(false);
const errorMessage = ref("");

const toast = useToast();
const router = useRouter();
const { setAuth } = useAuth();
const { isDarkTheme, toggleDarkMode } = useLayout();

const login = async () => {
    errorMessage.value = "";

    if (!username.value.trim() || !password.value) {
        errorMessage.value = "Username dan kata sandi wajib diisi.";
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.post("login", {
            username: username.value.trim(),
            password: password.value,
        });

        setAuth(response.data.token, response.data.user);

        toast.add({
            severity: "success",
            summary: "Login Berhasil",
            detail: `Selamat datang kembali, ${response.data.user.nama}.`,
            life: 2500,
        });

        router.push("/dashboard");
    } catch (error) {
        errorMessage.value = extractApiError(error, "Gagal masuk ke sistem. Periksa kembali kredensial Anda.");
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Toast />
    <div class="min-h-screen bg-canvas text-ink flex flex-col justify-between p-4 sm:p-6">
        <div class="flex justify-end">
            <button
                type="button"
                class="w-9 h-9 rounded-lg border border-hairline bg-surface hover:bg-subtle text-muted hover:text-ink flex items-center justify-center transition-colors"
                :aria-label="isDarkTheme ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
                @click="toggleDarkMode"
            >
                <i :class="isDarkTheme ? 'pi pi-sun text-sm text-accent' : 'pi pi-moon text-sm'"></i>
            </button>
        </div>

        <main class="w-full max-w-[400px] mx-auto my-auto py-8">
            <div class="bg-surface border border-hairline rounded-2xl p-6 sm:p-8 shadow-sm">
                <div class="flex flex-col items-center text-center mb-7">
                    <div class="w-12 h-12 rounded-xl bg-subtle border border-hairline flex items-center justify-center text-ink mb-4">
                        <BrandMark :size="24" />
                    </div>
                    <h1 class="text-xl font-bold tracking-tight text-ink">
                        Tamboer Gadget
                    </h1>
                    <p class="text-xs text-muted mt-1">
                        Masuk untuk mengelola kasir POS dan inventaris toko
                    </p>
                </div>

                <div
                    v-if="errorMessage"
                    role="alert"
                    class="mb-4 px-3.5 py-2.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-medium"
                >
                    {{ errorMessage }}
                </div>

                <form @submit.prevent="login" class="space-y-4">
                    <div class="space-y-1.5">
                        <label for="username" class="block text-xs font-semibold text-muted">
                            Username
                        </label>
                        <input
                            id="username"
                            v-model.trim="username"
                            type="text"
                            autocomplete="username"
                            placeholder="Masukkan username"
                            class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl bg-canvas border border-hairline text-sm text-ink placeholder:text-tertiary focus:outline-none focus:border-accent transition-colors"
                            autofocus
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="block text-xs font-semibold text-muted">
                            Kata Sandi
                        </label>
                        <Password
                            inputId="password"
                            v-model="password"
                            placeholder="Masukkan kata sandi"
                            :toggleMask="true"
                            fluid
                            :feedback="false"
                            inputClass="w-full min-h-[44px] text-sm px-3.5 py-2.5 rounded-xl bg-canvas border border-hairline text-ink placeholder:text-tertiary"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full min-h-[44px] py-2.5 rounded-xl font-semibold text-sm bg-ink text-surface hover:opacity-90 flex items-center justify-center gap-2 transition-opacity disabled:opacity-50 cursor-pointer mt-2"
                        :disabled="isLoading"
                    >
                        <i v-if="isLoading" class="pi pi-spin pi-spinner text-sm"></i>
                        <span>{{ isLoading ? "Memverifikasi..." : "Masuk ke Sistem" }}</span>
                    </button>
                </form>
            </div>
        </main>

        <footer class="text-center text-xs text-tertiary py-2">
            &copy; {{ new Date().getFullYear() }} Tamboer Gadget
        </footer>
    </div>
</template>
