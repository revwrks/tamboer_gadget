<script setup>
import { FilterMatchMode } from "@primevue/core/api";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, computed } from "vue";

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const users = ref([]);

const fetchUsers = async () => {
    try {
        const response = await axios.get("users");
        users.value = response.data;
    } catch (error) {
        console.error("Error fetching users:", error);
    }
};

onMounted(() => {
    fetchUsers();
});

const toast = useToast();
const dt = ref();
const userDialog = ref(false);
const deleteUserDialog = ref(false);
const deleteUsersDialog = ref(false);
const user = ref({});
const selectedUsers = ref();

const filteredData = computed(() => {
    const filterValue = filters.value.global.value?.toLowerCase() || "";
    return users.value.filter((item) => {
        return (
            item.username.toLowerCase().includes(filterValue) ||
            item.nama.toLowerCase().includes(filterValue) ||
            item.level.toLowerCase().includes(filterValue)
        );
    });
});

const submitted = ref(false);

function openNew() {
    user.value = {};
    submitted.value = false;
    userDialog.value = true;
}

function hideDialog() {
    userDialog.value = false;
    submitted.value = false;
}

function saveUser() {
    submitted.value = true;

    if (
        user?.value.username?.trim() &&
        user?.value.nama?.trim() &&
        user?.value.level
    ) {
        // Prepare the user data to be sent
        const levelVal = typeof user.value.level === "object" && user.value.level !== null
            ? user.value.level.value
            : user.value.level;

        const userData = {
            ...user.value,
            level: levelVal,
        };

        if (user.value.id_user) {
            // Update existing user
            axios
                .put(`users/${user.value.id_user}`, userData)
                .then(() => {
                    const index = findIndexById(user.value.id_user);
                    users.value[index] = user.value;
                    fetchUsers();
                    toast.add({
                        severity: "success",
                        summary: "Successful",
                        detail: "User Updated",
                        life: 3000,
                    });
                })
                .catch((error) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to update user",
                        life: 3000,
                    });
                });
        } else {
            // Create new user
            axios
                .post("users", userData)
                .then((response) => {
                    user.value.id_user = response.data.user.id_user;
                    users.value.push(user.value);
                    fetchUsers();
                    toast.add({
                        severity: "success",
                        summary: "Successful",
                        detail: "User Created",
                        life: 3000,
                    });
                })
                .catch((error) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to create user",
                        life: 3000,
                    });
                });
        }

        userDialog.value = false;
        user.value = {};
    }
}

function editUser(usr) {
    user.value = { ...usr };
    userDialog.value = true;
}

function confirmDeleteUser(usr) {
    user.value = usr;
    deleteUserDialog.value = true;
}

function deleteUser() {
    axios
        .delete(`users/${user.value.id_user}`)
        .then(() => {
            users.value = users.value.filter(
                (val) => val.id_user !== user.value.id_user,
            );
            fetchUsers();
            toast.add({
                severity: "success",
                summary: "Successful",
                detail: "User Deleted",
                life: 3000,
            });
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to delete user",
                life: 3000,
            });
        });

    deleteUserDialog.value = false;
    user.value = {};
}

function findIndexById(id) {
    let index = -1;
    for (let i = 0; i < users.value.length; i++) {
        if (users.value[i].id_user === id) {
            index = i;
            break;
        }
    }
    return index;
}

function exportCSV() {
    dt.value.exportCSV();
}

function confirmDeleteSelected() {
    deleteUsersDialog.value = true;
}

function deleteSelectedUsers() {
    const ids = selectedUsers.value.map((user) => user.id_user);
    const requests = ids.map((id) => axios.delete(`users/${id}`));

    Promise.all(requests)
        .then(() => {
            users.value = users.value.filter(
                (val) => !ids.includes(val.id_user),
            );
            fetchUsers();
            toast.add({
                severity: "success",
                summary: "Successful",
                detail: "Users Deleted",
                life: 3000,
            });
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to delete selected users",
                life: 3000,
            });
        });

    deleteUsersDialog.value = false;
    selectedUsers.value = null;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header Panel -->
        <div class="bg-white border border-[#e7e5df] rounded-2xl p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-[#1c1917] text-white flex items-center justify-center font-bold text-lg shadow-sm">
                        <i class="pi pi-users text-amber-400"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-[#1c1917] tracking-tight">
                            Manajemen Karyawan & Hak Akses
                        </h1>
                        <p class="text-xs text-[#78716c] mt-0.5">
                            Kelola akun pengguna, level owner, dan kasir toko
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#1c1917] hover:bg-[#292524] text-white flex items-center gap-2 shadow-sm transition-all"
                        @click="openNew"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Tambah Pengguna</span>
                    </button>
                    <button
                        type="button"
                        class="w-8 h-8 rounded-xl bg-[#f0ede6] hover:bg-[#e7e5df] text-[#78716c] hover:text-[#1c1917] flex items-center justify-center border border-[#e7e5df] transition-all"
                        @click="fetchUsers"
                        v-tooltip.top="'Refresh Data'"
                    >
                        <i class="pi pi-refresh text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white border border-[#e7e5df] rounded-2xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between gap-4 pb-2 border-b border-[#e7e5df]">
                <h2 class="text-base font-bold text-[#1c1917]">Daftar Akun Terdaftar</h2>

                <div class="relative w-full sm:w-64">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#78716c] text-xs"></i>
                    <input
                        v-model="filters.global.value"
                        type="text"
                        placeholder="Cari username, nama..."
                        class="w-full text-xs pl-8 pr-4 py-2 rounded-xl bg-[#faf9f5] border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                    />
                </div>
            </div>

            <DataTable
                ref="dt"
                :value="filteredData"
                dataKey="id_user"
                :paginator="true"
                :rows="10"
                responsiveLayout="scroll"
                stripedRows
                class="p-datatable-sm"
            >
                <template #empty>
                    <div class="text-center py-8 text-[#78716c]">
                        Tidak ada data akun yang ditemukan.
                    </div>
                </template>

                <!-- Avatar & Nama -->
                <Column field="nama" header="Nama Pengguna" sortable style="min-width: 14rem">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-[#f0ede6] text-[#1c1917] flex items-center justify-center font-bold text-xs border border-[#e7e5df]">
                                {{ data.nama ? data.nama.charAt(0).toUpperCase() : 'U' }}
                            </div>
                            <div>
                                <span class="font-semibold text-xs text-[#1c1917] block">{{ data.nama }}</span>
                                <span class="text-[11px] text-[#78716c] font-mono">@{{ data.username }}</span>
                            </div>
                        </div>
                    </template>
                </Column>

                <!-- Role / Level -->
                <Column field="level" header="Hak Akses" sortable style="min-width: 8rem">
                    <template #body="{ data }">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border"
                            :class="data.level === 'owner'
                                ? 'bg-amber-50 text-amber-800 border-amber-200'
                                : 'bg-stone-100 text-stone-700 border-stone-200'"
                        >
                            <i :class="data.level === 'owner' ? 'pi pi-crown text-[10px] mr-1 text-amber-600' : 'pi pi-user text-[10px] mr-1'"></i>
                            {{ data.level }}
                        </span>
                    </template>
                </Column>

                <!-- Tanggal Dibuat -->
                <Column field="created_at" header="Terdaftar" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="text-xs text-[#78716c]">
                            {{ data.created_at ? new Date(data.created_at).toLocaleDateString('id-ID') : '-' }}
                        </span>
                    </template>
                </Column>

                <!-- Actions -->
                <Column header="Aksi" style="min-width: 8rem" :exportable="false">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                class="w-8 h-8 rounded-lg bg-[#f0ede6] text-[#78716c] hover:bg-[#1c1917] hover:text-white flex items-center justify-center transition-colors"
                                v-tooltip.top="'Edit Karyawan'"
                                @click="editUser(data)"
                            >
                                <i class="pi pi-pencil text-xs"></i>
                            </button>

                            <button
                                type="button"
                                class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-colors"
                                v-tooltip.top="'Hapus Akun'"
                                @click="confirmDeleteUser(data)"
                            >
                                <i class="pi pi-trash text-xs"></i>
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- User Dialog Modal -->
        <Dialog
            v-model:visible="userDialog"
            modal
            :header="user.id_user ? 'Perbarui Akun Karyawan' : 'Tambah Akun Baru'"
            :style="{ width: '450px', maxWidth: '95vw' }"
            class="rounded-2xl bg-white"
        >
            <div class="space-y-4 pt-2 text-xs">
                <div>
                    <label class="block font-semibold text-[#78716c] mb-1">Username</label>
                    <input
                        v-model.trim="user.username"
                        type="text"
                        placeholder="Username login"
                        class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                    />
                    <small v-if="submitted && !user.username" class="text-rose-600 mt-1 block">Username wajib diisi.</small>
                </div>

                <div>
                    <label class="block font-semibold text-[#78716c] mb-1">Nama Lengkap</label>
                    <input
                        v-model.trim="user.nama"
                        type="text"
                        placeholder="Nama karyawan"
                        class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                    />
                    <small v-if="submitted && !user.nama" class="text-rose-600 mt-1 block">Nama wajib diisi.</small>
                </div>

                <div>
                    <label class="block font-semibold text-[#78716c] mb-1">Level / Hak Akses</label>
                    <div class="grid grid-cols-2 gap-2 bg-[#f0ede6] p-1 rounded-xl border border-[#e7e5df]">
                        <button
                            type="button"
                            class="py-2 rounded-lg text-xs font-semibold transition-all capitalize"
                            :class="(user.level === 'owner' || user.level?.value === 'owner') ? 'bg-[#1c1917] text-white shadow-sm' : 'text-[#78716c]'"
                            @click="user.level = 'owner'"
                        >
                            👑 Owner
                        </button>
                        <button
                            type="button"
                            class="py-2 rounded-lg text-xs font-semibold transition-all capitalize"
                            :class="(user.level === 'sales' || user.level?.value === 'sales') ? 'bg-[#1c1917] text-white shadow-sm' : 'text-[#78716c]'"
                            @click="user.level = 'sales'"
                        >
                            👤 Sales / Kasir
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-[#78716c] mb-1">
                        Kata Sandi
                        <span v-if="user.id_user" class="text-[10px] text-[#78716c] font-normal">(Kosongkan jika tidak ingin mengubah)</span>
                    </label>
                    <input
                        v-model="user.password"
                        type="password"
                        placeholder="Minimal 6 karakter"
                        class="w-full text-xs p-2.5 rounded-xl bg-white border border-[#e7e5df] text-[#1c1917] focus:outline-none focus:border-[#1c1917]"
                    />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Batal" text severity="secondary" @click="userDialog = false" />
                    <Button label="Simpan" severity="primary" class="font-bold bg-[#1c1917] hover:bg-[#292524] text-white border-0" @click="saveUser" />
                </div>
            </template>
        </Dialog>

        <!-- Delete Confirm Dialog -->
        <Dialog
            v-model:visible="deleteUserDialog"
            header="Hapus Akun Pengguna"
            modal
            :style="{ width: '420px', maxWidth: '90vw' }"
            class="rounded-2xl bg-white"
        >
            <div class="flex items-center gap-3 pt-2">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-200">
                    <i class="pi pi-exclamation-triangle text-lg"></i>
                </div>
                <div>
                    <p class="font-bold text-[#1c1917]">Hapus akun ini?</p>
                    <p class="text-xs text-[#78716c] mt-1">
                        Pengguna <b>{{ user.nama }} (@{{ user.username }})</b> akan dihapus secara permanen.
                    </p>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Batal" text severity="secondary" @click="deleteUserDialog = false" />
                    <Button label="Hapus Akun" severity="danger" @click="deleteUser" />
                </div>
            </template>
        </Dialog>
    </div>
</template>



