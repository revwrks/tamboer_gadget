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
        const response = await axios.get("/api/users");
        users.value = response.data;
    } catch (error) {
        console.error("Error fetching users:", error);
    }
};

onMounted(fetchUsers);

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
        const userData = {
            ...user.value,
            level: user.value.level.value,  // Send only the 'value' part of level
        };

        if (user.value.id_user) {
            // Update existing user
            axios
                .put(`/api/users/${user.value.id_user}`, userData)
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
                .post("/api/users", userData)
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
        .delete(`/api/users/${user.value.id_user}`)
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
    const requests = ids.map((id) => axios.delete(`/api/users/${id}`));

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
    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button
                        label="New"
                        icon="pi pi-plus"
                        severity="secondary"
                        class="mr-2"
                        @click="openNew"
                    />
                    <Button
                        label="Delete"
                        icon="pi pi-trash"
                        severity="secondary"
                        @click="confirmDeleteSelected"
                        :disabled="!selectedUsers || !selectedUsers.length"
                    />
                </template>

                <template #end>
                    <Button
                        label="Export"
                        icon="pi pi-upload"
                        severity="secondary"
                        @click="exportCSV($event)"
                    />
                </template>
            </Toolbar>

            <DataTable
                ref="dt"
                v-model:selection="selectedUsers"
                :value="users"
                dataKey="id_user"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} users"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-center justify-between"
                    >
                        <h4 class="m-0">Manage Users</h4>
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText
                                v-model="filters.global.value"
                                placeholder="Search..."
                            />
                        </IconField>
                    </div>
                </template>
                <Column
                    selectionMode="multiple"
                    style="width: 3rem"
                    :exportable="false"
                ></Column>

                <Column field="username" header="Username" sortable></Column>
                <Column field="nama" header="Nama" sortable></Column>
                <Column field="level" header="Level" sortable></Column>

                <Column :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button
                            icon="pi pi-pencil"
                            outlined
                            rounded
                            class="mr-2"
                            @click="editUser(slotProps.data)"
                        />
                        <Button
                            icon="pi pi-trash"
                            outlined
                            rounded
                            severity="danger"
                            @click="confirmDeleteUser(slotProps.data)"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog
            v-model:visible="userDialog"
            :style="{ width: '450px' }"
            header="User Details"
            :modal="true"
        >
            <div class="flex flex-col gap-6">
                <div>
                    <label for="username" class="block font-bold mb-3"
                        >Username</label
                    >
                    <InputText
                        id="username"
                        v-model.trim="user.username"
                        required
                        :invalid="submitted && !user.username"
                        fluid
                    />
                    <small
                        v-if="submitted && !user.username"
                        class="text-red-500"
                        >Username is required.</small
                    >
                </div>

                <div>
                    <label for="nama" class="block font-bold mb-3">Nama</label>
                    <InputText
                        id="nama"
                        v-model.trim="user.nama"
                        required
                        :invalid="submitted && !user.nama"
                        fluid
                    />
                    <small v-if="submitted && !user.nama" class="text-red-500"
                        >Nama is required.</small
                    >
                </div>

                <div>
                    <label for="level" class="block font-bold mb-3"
                        >Level</label
                    >
                    <Dropdown
                        id="level"
                        v-model="user.level"
                        :options="[
                            { label: 'Owner', value: 'owner' },
                            { label: 'Sales', value: 'sales' },
                        ]"
                        optionLabel="label"
                        required
                        :invalid="submitted && !user.level"
                        fluid
                    />
                    <small v-if="submitted && !user.level" class="text-red-500"
                        >Level is required.</small
                    >
                </div>

                <div v-if="!user.id_user">
                    <label for="password" class="block font-bold mb-3"
                        >Password</label
                    >
                    <InputText
                        id="password"
                        v-model="user.password"
                        type="password"
                        required
                        :invalid="submitted && !user.password"
                        fluid
                    />
                    <small
                        v-if="submitted && !user.password"
                        class="text-red-500"
                        >Password is required.</small
                    >
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    icon="pi pi-times"
                    class="p-button-text"
                    @click="hideDialog"
                />
                <Button
                    label="Save"
                    icon="pi pi-check"
                    class="p-button-text"
                    @click="saveUser"
                />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="deleteUserDialog"
            :style="{ width: '450px' }"
            header="Confirm"
            :modal="true"
            @hide="deleteUserDialog = false"
        >
            <p>Are you sure you want to delete {{ user.nama }}?</p>

            <template #footer>
                <Button
                    label="No"
                    icon="pi pi-times"
                    class="p-button-text"
                    @click="deleteUserDialog = false"
                />
                <Button
                    label="Yes"
                    icon="pi pi-check"
                    class="p-button-text"
                    @click="deleteUser"
                />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="deleteUsersDialog"
            :style="{ width: '450px' }"
            header="Confirm"
            :modal="true"
            @hide="deleteUsersDialog = false"
        >
            <p>Are you sure you want to delete the selected users?</p>

            <template #footer>
                <Button
                    label="No"
                    icon="pi pi-times"
                    class="p-button-text"
                    @click="deleteUsersDialog = false"
                />
                <Button
                    label="Yes"
                    icon="pi pi-check"
                    class="p-button-text"
                    @click="deleteSelectedUsers"
                />
            </template>
        </Dialog>
    </div>
</template>
