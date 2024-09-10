<script setup>
import { FilterMatchMode } from "@primevue/core/api";
import axios from "axios";
import Calendar from "primevue/calendar";
import { useToast } from "primevue/usetoast";
import { onMounted, ref, computed } from "vue";

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }, // Global filter setup
});

const penjualan = ref();

const fetchPenjualan = async () => {
    try {
        const response = await axios.get("/api/penjualan");
        penjualan.value = response.data;
    } catch (error) {
        console.error("Error fetching penjualan:", error);
    }
};

onMounted(fetchPenjualan);

const toast = useToast();
const dt = ref();
const penjualanDialog = ref(false);
const penjualanItem = ref({});
const selectedPenjualan = ref();

const filteredData = computed(() => {
    const filterValue = filters.value.global.value?.toLowerCase() || "";

    return penjualan.value.filter((item) => {
        return (
            item.stok_hp.imei.toLowerCase().includes(filterValue) ||
            item.stok_hp.brand.toLowerCase().includes(filterValue) ||
            item.stok_hp.nama.toLowerCase().includes(filterValue) ||
            item.stok_hp.warna.toLowerCase().includes(filterValue) ||
            item.stok_hp.harga_masuk.toString().toLowerCase().includes(filterValue) ||
            item.harga_jual.toString().toLowerCase().includes(filterValue) ||
            item.users.nama.toLowerCase().includes(filterValue)
        );
    });
});

const submitted = ref(false);

function formatCurrency(value) {
    if (value != null) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0, // Ensure no decimals
            maximumFractionDigits: 0, // No decimals
        }).format(Math.round(value)); // Rounds the value to the nearest integer
    }
    return "-";
}

function getStatusSeverity(status) {
    return status === "terjual" ? "warning" : null;
}

function editPenjualan(item) {
    penjualanItem.value = { ...item };
    penjualanDialog.value = true;
}

function hideDialog() {
    penjualanDialog.value = false;
    submitted.value = false;
}

function savePenjualan() {
    submitted.value = true;

    if (penjualanItem?.value.id_penjualan) {
        axios
            .put(`/api/penjualan/${penjualanItem.value.id_penjualan}`, {
                ...penjualanItem.value,
            })
            .then(() => {
                const index = findIndexById(penjualanItem.value.id_penjualan);
                penjualan.value[index] = penjualanItem.value;
                fetchPenjualan(); // Refresh data after update
                toast.add({
                    severity: "success",
                    summary: "Successful",
                    detail: "Penjualan Updated",
                    life: 3000,
                });
            })
            .catch((error) => {
                toast.add({
                    severity: "error",
                    summary: "Error",
                    detail: "Failed to update penjualan",
                    life: 3000,
                });
            });
    }

    penjualanDialog.value = false;
    penjualanItem.value = {};
}

function findIndexById(id) {
    let index = -1;
    for (let i = 0; i < penjualan.value.length; i++) {
        if (penjualan.value[i].id_penjualan === id) {
            index = i;
            break;
        }
    }

    return index;
}
</script>

<template>
    <div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button
                        label="Delete"
                        icon="pi pi-trash"
                        severity="secondary"
                        @click="confirmDeleteSelected"
                        :disabled="!selectedPenjualan || !selectedPenjualan.length"
                    />
                </template>
            </Toolbar>

            <DataTable
                ref="dt"
                v-model:selection="selectedPenjualan"
                :value="penjualan"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} penjualan"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Manage Penjualan</h4>
                        <InputText
                            v-model="filters.global.value"
                            placeholder="Search..."
                            class="w-full md:w-auto"
                        />
                    </div>
                </template>

                <!-- Tanggal Terjual -->
                <Column field="tanggal" header="Tanggal Terjual" sortable>
                    <template #body="slotProps">
                        {{ new Date(slotProps.data.tanggal).toLocaleDateString("id-ID") }}
                    </template>
                </Column>

                <!-- Sales -->
                <Column field="users.nama" header="Sales" sortable>
                    {{ slotProps.data.users.nama || 'Unknown' }}</Column>

                <!-- Stocks Details -->
                <Column field="stok_hp.nama" header="Nama HP" sortable>
                    {{ slotProps.data.stok_hp.nama }}</Column>
                <Column field="stok_hp.warna" header="Warna" sortable>
                    {{ slotProps.data.stok_hp.warna }}</Column>
                <Column field="stok_hp.imei" header="IMEI" sortable>
                    {{ slotProps.data.stok_hp.imei }}</Column>

                <!-- Harga Jual -->
                <Column field="harga_jual" header="Harga Jual" sortable>
                    <template #body="slotProps">
                        {{ formatCurrency(slotProps.data.harga_jual) }}
                    </template>
                </Column>

                <!-- Actions -->
                <Column :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button
                            icon="pi pi-pencil"
                            outlined
                            rounded
                            class="mr-2"
                            @click="editPenjualan(slotProps.data)"
                        />
                        <Button
                            icon="pi pi-trash"
                            outlined
                            rounded
                            severity="danger"
                            @click="confirmDeletePenjualan(slotProps.data)"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Edit Penjualan Dialog -->
        <Dialog
            v-model:visible="penjualanDialog"
            :style="{ width: '450px' }"
            header="Penjualan Details"
            :modal="true"
        >
            <div class="flex flex-col gap-6">
                <!-- Display existing fields for editing the sale -->
                <div>
                    <label for="tanggal" class="block font-bold mb-3">Tanggal Terjual</label>
                    <Calendar id="tanggal" v-model.trim="penjualanItem.tanggal" required fluid />
                </div>

                <div>
                    <label for="harga_jual" class="block font-bold mb-3">Harga Jual</label>
                    <InputNumber
                        id="harga_jual"
                        v-model="penjualanItem.harga_jual"
                        required
                        mode="currency"
                        currency="IDR"
                        locale="id-ID"
                        fluid
                    />
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    icon="pi pi-times"
                    text
                    @click="hideDialog"
                />
                <Button label="Save" icon="pi pi-check" @click="savePenjualan" />
            </template>
        </Dialog>
    </div>
</template>
