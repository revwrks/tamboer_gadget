<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { useAuth } from "@/composables/useAuth";
import { extractApiError, formatDate } from "@/utils/formatters";

const toast = useToast();
const { user: currentUser } = useAuth();

const users = ref([]);
const isLoading = ref(false);
const isSaving = ref(false);
const searchQuery = ref("");

const userDialog = ref(false);
const deleteUserDialog = ref(false);
const userForm = ref({});
const submitted = ref(false);

const fetchUsers = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get("users");
        users.value = response.data;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Memuat",
            detail: extractApiError(error, "Gagal mengambil daftar akun."),
            life: 4000,
        });
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchUsers();
});

const filteredData = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return users.value;
    return users.value.filter(
        (item) =>
            (item.username || "").toLowerCase().includes(q) ||
            (item.nama || "").toLowerCase().includes(q) ||
            (item.level || "").toLowerCase().includes(q)
    );
});

const openNew = () => {
    userForm.value = {
        username: "",
        nama: "",
        level: "sales",
        password: "",
    };
    submitted.value = false;
    userDialog.value = true;
};

const editUser = (usr) => {
    userForm.value = { ...usr, password: "" };
    submitted.value = false;
    userDialog.value = true;
};

const passwordInvalid = computed(() => {
    if (!userForm.value.id_user) {
        return !userForm.value.password || userForm.value.password.length < 6;
    }
    return !!userForm.value.password && userForm.value.password.length < 6;
});

const saveUser = async () => {
    submitted.value = true;

    if (!userForm.value.username?.trim() || !userForm.value.nama?.trim() || !userForm.value.level || passwordInvalid.value) {
        return;
    }

    isSaving.value = true;
    const payload = {
        username: userForm.value.username.trim(),
        nama: userForm.value.nama.trim(),
        level: userForm.value.level,
    };
    if (userForm.value.password) {
        payload.password = userForm.value.password;
    }

    try {
        if (userForm.value.id_user) {
            await axios.put(`users/${userForm.value.id_user}`, payload);
            toast.add({ severity: "success", summary: "Berhasil", detail: "Data akun diperbarui.", life: 3000 });
        } else {
            await axios.post("users", payload);
            toast.add({ severity: "success", summary: "Berhasil", detail: "Akun baru ditambahkan.", life: 3000 });
        }
        userDialog.value = false;
        userForm.value = {};
        await fetchUsers();
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Simpan",
            detail: extractApiError(error, "Gagal menyimpan akun pengguna."),
            life: 4000,
        });
    } finally {
        isSaving.value = false;
    }
};

const confirmDeleteUser = (usr) => {
    userForm.value = usr;
    deleteUserDialog.value = true;
};

const executeDeleteUser = async () => {
    try {
        await axios.delete(`users/${userForm.value.id_user}`);
        toast.add({ severity: "success", summary: "Dihapus", detail: "Akun pengguna berhasil dihapus.", life: 3000 });
        deleteUserDialog.value = false;
        userForm.value = {};
        await fetchUsers();
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Gagal Hapus",
            detail: extractApiError(error, "Gagal menghapus akun."),
            life: 4000,
        });
    }
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">
                    Tim &amp; Hak Akses
                </h1>
                <p class="text-xs sm:text-sm text-muted mt-0.5">
                    Kelola akun Owner dan Kasir / Sales toko
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="min-h-[38px] px-4 py-2 rounded-xl text-xs font-semibold bg-ink text-surface hover:opacity-90 flex items-center gap-2 transition-opacity"
                    @click="openNew"
                >
                    <i class="pi pi-plus text-xs"></i>
                    <span>Tambah Pengguna</span>
                </button>
                <button
                    type="button"
                    class="w-9 h-9 rounded-xl bg-surface border border-hairline hover:bg-subtle text-muted hover:text-ink flex items-center justify-center transition-colors"
                    aria-label="Muat ulang daftar akun"
                    @click="fetchUsers"
                >
                    <i class="pi pi-refresh text-xs" :class="isLoading ? 'pi-spin' : ''"></i>
                </button>
            </div>
        </div>

        <div class="bg-surface border border-hairline rounded-2xl p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <h2 class="text-sm font-bold text-ink">Daftar Akun Terdaftar ({{ filteredData.length }})</h2>
                <div class="relative w-full sm:w-64">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-tertiary text-xs pointer-events-none"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama atau username..."
                        class="w-full min-h-[38px] text-xs pl-8 pr-3.5 py-2 rounded-xl bg-canvas border border-hairline text-ink placeholder:text-tertiary focus:outline-none focus:border-accent"
                    />
                </div>
            </div>

            <DataTable
                :value="filteredData"
                dataKey="id_user"
                :paginator="true"
                :rows="10"
                responsiveLayout="scroll"
                stripedRows
                class="p-datatable-sm"
            >
                <template #empty>
                    <div class="text-center py-8 text-xs text-muted">
                        Tidak ada akun pengguna yang sesuai dengan pencarian.
                    </div>
                </template>

                <Column field="nama" header="Pengguna" sortable style="min-width: 14rem">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-subtle border border-hairline text-ink flex items-center justify-center font-bold text-xs">
                                {{ data.nama ? data.nama.charAt(0).toUpperCase() : "U" }}
                            </div>
                            <div>
                                <div class="font-semibold text-xs text-ink">
                                    {{ data.nama }}
                                    <span
                                        v-if="data.id_user === currentUser?.id_user"
                                        class="ml-1.5 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-accent-soft text-accent-ink"
                                    >
                                        Anda
                                    </span>
                                </div>
                                <div class="text-xs text-muted font-mono">@{{ data.username }}</div>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="level" header="Hak Akses" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase border"
                            :class="
                                data.level === 'owner'
                                    ? 'bg-accent-soft text-accent-ink border-amber-500/30'
                                    : 'bg-subtle text-muted border-hairline'
                            "
                        >
                            <i :class="data.level === 'owner' ? 'pi pi-shield text-[10px]' : 'pi pi-user text-[10px]'"></i>
                            {{ data.level }}
                        </span>
                    </template>
                </Column>

                <Column field="created_at" header="Terdaftar" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="text-xs text-muted">{{ formatDate(data.created_at) }}</span>
                    </template>
                </Column>

                <Column header="Aksi" style="min-width: 7rem" :exportable="false">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                class="w-8 h-8 rounded-lg bg-subtle text-muted hover:text-ink hover:bg-hover flex items-center justify-center transition-colors"
                                aria-label="Edit akun"
                                v-tooltip.top="'Edit Akun'"
                                @click="editUser(data)"
                            >
                                <i class="pi pi-pencil text-xs"></i>
                            </button>

                            <button
                                type="button"
                                class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-colors disabled:opacity-35 disabled:cursor-not-allowed"
                                :disabled="data.id_user === currentUser?.id_user"
                                aria-label="Hapus akun"
                                v-tooltip.top="data.id_user === currentUser?.id_user ? 'Akun aktif Anda' : 'Hapus Akun'"
                                @click="confirmDeleteUser(data)"
                            >
                                <i class="pi pi-trash text-xs"></i>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Add / Edit User Dialog -->
        <Dialog
            v-model:visible="userDialog"
            modal
            :header="userForm.id_user ? 'Perbarui Akun Karyawan' : 'Tambah Akun Baru'"
            :style="{ width: '440px', maxWidth: '95vw' }"
        >
            <div class="space-y-4 pt-2 text-xs">
                <div>
                    <label for="user-username" class="block font-semibold text-muted mb-1.5">Username</label>
                    <input
                        id="user-username"
                        v-model.trim="userForm.username"
                        type="text"
                        placeholder="Username login"
                        class="w-full min-h-[40px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink focus:outline-none focus:border-accent"
                    />
                    <small v-if="submitted && !userForm.username" class="text-rose-600 dark:text-rose-400 mt-1 block">
                        Username wajib diisi.
                    </small>
                </div>

                <div>
                    <label for="user-nama" class="block font-semibold text-muted mb-1.5">Nama Lengkap</label>
                    <input
                        id="user-nama"
                        v-model.trim="userForm.nama"
                        type="text"
                        placeholder="Nama karyawan"
                        class="w-full min-h-[40px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink focus:outline-none focus:border-accent"
                    />
                    <small v-if="submitted && !userForm.nama" class="text-rose-600 dark:text-rose-400 mt-1 block">
                        Nama lengkap wajib diisi.
                    </small>
                </div>

                <div>
                    <span class="block font-semibold text-muted mb-1.5">Hak Akses</span>
                    <div class="grid grid-cols-2 gap-1.5 bg-subtle p-1 rounded-xl border border-hairline">
                        <button
                            type="button"
                            class="min-h-[36px] rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors"
                            :class="userForm.level === 'owner' ? 'bg-ink text-surface' : 'text-muted hover:text-ink'"
                            @click="userForm.level = 'owner'"
                        >
                            <i class="pi pi-shield text-xs"></i>
                            <span>Owner</span>
                        </button>
                        <button
                            type="button"
                            class="min-h-[36px] rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors"
                            :class="userForm.level === 'sales' ? 'bg-ink text-surface' : 'text-muted hover:text-ink'"
                            @click="userForm.level = 'sales'"
                        >
                            <i class="pi pi-user text-xs"></i>
                            <span>Sales / Kasir</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="user-password" class="block font-semibold text-muted mb-1.5">
                        Kata Sandi
                        <span v-if="userForm.id_user" class="font-normal text-tertiary">(Kosongkan jika tidak diubah)</span>
                    </label>
                    <input
                        id="user-password"
                        v-model="userForm.password"
                        type="password"
                        placeholder="Minimal 6 karakter"
                        class="w-full min-h-[40px] text-sm px-3 py-2 rounded-xl bg-canvas border border-hairline text-ink focus:outline-none focus:border-accent"
                    />
                    <small v-if="submitted && passwordInvalid" class="text-rose-600 dark:text-rose-400 mt-1 block">
                        Kata sandi minimal 6 karakter.
                    </small>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Batal" text severity="secondary" @click="userDialog = false" />
                    <Button label="Simpan" :loading="isSaving" @click="saveUser" />
                </div>
            </template>
        </Dialog>

        <!-- Delete Confirm Dialog -->
        <Dialog
            v-model:visible="deleteUserDialog"
            header="Hapus Akun Pengguna"
            modal
            :style="{ width: '420px', maxWidth: '90vw' }"
        >
            <div class="flex items-start gap-3 pt-2">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <i class="pi pi-exclamation-triangle text-lg"></i>
                </div>
                <div>
                    <p class="font-bold text-sm text-ink">Hapus akun ini?</p>
                    <p class="text-xs text-muted mt-1 leading-relaxed">
                        Pengguna <b>{{ userForm.nama }} (@{{ userForm.username }})</b> akan dihapus permanen.
                    </p>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Batal" text severity="secondary" @click="deleteUserDialog = false" />
                    <Button label="Hapus Akun" severity="danger" @click="executeDeleteUser" />
                </div>
            </template>
        </Dialog>
    </div>
</template>
