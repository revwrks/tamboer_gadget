<script setup>
import { FilterMatchMode } from "@primevue/core/api";
import axios from "axios";
import Calendar from "primevue/calendar";
import { useToast } from "primevue/usetoast";
import { onMounted, onUnmounted, ref, computed } from "vue";

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }, // Global filter setup
});

const products = ref();

const fetchProducts = async () => {
    try {
        const response = await axios.get("/api/stocks");
        products.value = response.data;
    } catch (error) {
        console.error("Error fetching products:", error);
    }
};

// Start polling when component is mounted

const intervalId = ref();

onMounted(() => {
    fetchUserData();
    fetchProducts(); // Initial fetch
    intervalId.value = setInterval(fetchProducts, 10000); // Poll every 10 seconds
});

// Clear the interval when the component is destroyed
onUnmounted(() => {
    if (intervalId.value) {
        clearInterval(intervalId.value);
    }
});

const user = ref({});
const toast = useToast();
const dt = ref();
const productDialog = ref(false);
const deleteProductDialog = ref(false);
const deleteProductsDialog = ref(false);
const product = ref({});
const selectedProducts = ref();
const penjualanDialog = ref(false); // New modal for harga jual input

// Fetch user data to determine the level
const fetchUserData = async () => {
    try {
        // Assuming the user ID (token) is stored in localStorage
        const id_user = localStorage.getItem("token");

        // Fetch user data based on their ID
        const response = await axios.get(`/api/users/${id_user}`);

        // Set the user data
        user.value = response.data;
    } catch (error) {
        console.error("Error fetching user data:", error);
    }
};

const filteredData = computed(() => {
    const filterValue = filters.value.global.value?.toLowerCase() || "";

    return products.value.filter((item) => {
        return (
            item.imei.toLowerCase().includes(filterValue) ||
            item.brand.toLowerCase().includes(filterValue) ||
            item.nama.toLowerCase().includes(filterValue) ||
            item.warna.toLowerCase().includes(filterValue) ||
            item.harga_masuk.toLowerCase().includes(filterValue) ||
            item.harga_jual.toLowerCase().includes(filterValue) ||
            item.status.toLowerCase().includes(filterValue)
        );
    });
});

// Only show "harga jual" and edit buttons if the user is an owner
const isOwner = computed(() => user.value.level === "owner");

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

function getStatusLabel(status) {
    switch (status) {
        case "ada":
            return "Ready";
        case "terjual":
            return "Sold";
        default:
            return status;
    }
}

function getStatusSeverity(status) {
    switch (status) {
        case "ada":
            return "success"; // Green color for Ready
        case "terjual":
            return "danger"; // Red color for Sold
        default:
            return null;
    }
}

function openNew() {
    product.value = {};
    product.value.tanggal = new Date(); // Set current date
    submitted.value = false;
    productDialog.value = true;
}

function hideDialog() {
    productDialog.value = false;
    submitted.value = false;
}

function hideDialogPenjualan() {
    penjualanDialog.value = false;
    submitted.value = false;
}

function saveProduct() {
    submitted.value = true;

    if (product?.value.nama?.trim()) {
        if (product.value.id_stock) {
            // Update existing product
            axios
                .put(`/api/stocks/${product.value.id_stock}`, {
                    ...product.value,
                    status: product.value.status || "ada", // Keep status if exists, otherwise default to 'ada'
                })
                .then(() => {
                    const index = findIndexById(product.value.id_stock);
                    products.value[index] = product.value;
                    fetchProducts(); // Refresh data after product update
                    toast.add({
                        severity: "success",
                        summary: "Successful",
                        detail: "Product Updated",
                        life: 3000,
                    });
                })
                .catch((error) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to update product",
                        life: 3000,
                    });
                });
        } else {
            // Create new product (set id to null to allow auto-increment)
            axios
                .post("/api/stocks", {
                    ...product.value,
                    id: null, // Laravel will auto-increment this field
                    status: "ada", // Default status to 'ada'
                })
                .then((response) => {
                    product.value.id_stock = response.data.stok_hp.id_stock; // Assuming `id` is returned in the response
                    products.value.push(product.value);
                    fetchProducts(); // Refresh data after product update
                    toast.add({
                        severity: "success",
                        summary: "Successful",
                        detail: "Product Created",
                        life: 3000,
                    });
                })
                .catch((error) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to create product",
                        life: 3000,
                    });
                });
        }

        productDialog.value = false;
        product.value = {};
    }
}

// Custom sorting function for sorting by status
function customSort(event) {
    const statusPriority = { ada: 1, terjual: 2 }; // Lower number means higher priority (i.e., 'ada' comes first)

    event.data.sort((a, b) => {
        return statusPriority[a.status] - statusPriority[b.status];
    });
}

// Open harga jual dialog for sales and submit penjualan
function openPenjualanDialog(prod) {
    product.value = { ...prod };
    penjualanDialog.value = true; // Show modal for sales to input harga jual
}

function createPenjualan() {
    // Get the current user's ID from the token
    const id_user = localStorage.getItem("token");

    // Send request to create penjualan
    axios
        .post("/api/penjualan", {
            tanggal: new Date(), // Set current date
            id_stock: product.value.id_stock,
            harga_jual: product.value.harga_jual, // Sales input
            id_sales: id_user, // Automatically set the sales ID based on current user
        })
        .then((response) => {
            // Update stock status to "terjual"
            axios
                .put(`/api/stocks/${product.value.id_stock}`, {
                    status: "terjual",
                })
                .then(() => {
                    fetchProducts(); // Refresh stock data
                    toast.add({
                        severity: "success",
                        summary: "Success",
                        detail: "Penjualan created and stock updated",
                        life: 3000,
                    });
                })
                .catch((error) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to update stock status",
                        life: 3000,
                    });
                });
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to create penjualan",
                life: 3000,
            });
        });
    penjualanDialog.value = false; // Close the modal
    product.value = {};
}

function editProduct(prod) {
    product.value = { ...prod };
    productDialog.value = true;
}

function confirmDeleteProduct(prod) {
    product.value = prod;
    deleteProductDialog.value = true;
}

function deleteProduct() {
    axios
        .delete(`/api/stocks/${product.value.id_stock}`)
        .then(() => {
            products.value = products.value.filter(
                (val) => val.id_stock !== product.value.id_stock,
            );
            fetchProducts(); // Refresh data after product update
            toast.add({
                severity: "success",
                summary: "Successful",
                detail: "Product Deleted",
                life: 3000,
            });
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to delete product",
                life: 3000,
            });
        });

    deleteProductDialog.value = false;
    product.value = {};
}

function findIndexById(id) {
    let index = -1;
    for (let i = 0; i < products.value.length; i++) {
        if (products.value[i].id_stock === id) {
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
    deleteProductsDialog.value = true;
}

function deleteSelectedProducts() {
    const ids = selectedProducts.value.map((product) => product.id_stock);
    const requests = ids.map((id) => axios.delete(`/api/stocks/${id}`));

    Promise.all(requests)
        .then(() => {
            products.value = products.value.filter(
                (val) => !ids.includes(val.id_stock),
            );
            fetchProducts(); // Refresh data after product update
            toast.add({
                severity: "success",
                summary: "Successful",
                detail: "Products Deleted",
                life: 3000,
            });
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to delete selected products",
                life: 3000,
            });
        });

    deleteProductsDialog.value = false;
    selectedProducts.value = null;
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
                        :disabled="
                            !selectedProducts || !selectedProducts.length
                        "
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
                v-model:selection="selectedProducts"
                :value="products"
                dataKey="id_stock"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
                sortField="status"
                :sortOrder="1"
                :customSort="true"
                @sort="customSort"
            >
                <template #header>
                    <div
                        class="flex flex-wrap gap-2 items-center justify-between"
                    >
                        <h4 class="m-0">Manage Products</h4>
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

                <Column field="tanggal" header="Tanggal" sortable>
                    <template #body="slotProps">
                        {{
                            new Date(slotProps.data.tanggal).toLocaleDateString(
                                "id-ID",
                            )
                        }}
                    </template>
                </Column>

                <Column
                    field="status"
                    header="Status"
                    sortable
                    style="min-width: 5rem"
                >
                    <template #body="slotProps">
                        <Tag
                            :value="getStatusLabel(slotProps.data.status)"
                            :severity="getStatusSeverity(slotProps.data.status)"
                        />
                    </template>
                </Column>

                <Column
                    field="nama"
                    header="Nama"
                    sortable
                    style="min-width: 12rem"
                ></Column>

                <Column
                    field="warna"
                    header="Warna"
                    sortable
                    style="min-width: 12rem"
                ></Column>

                <Column
                    field="harga_masuk"
                    header="Harga Masuk"
                    sortable
                    style="min-width: 8rem"
                >
                    <template #body="slotProps">
                        {{ formatCurrency(slotProps.data.harga_masuk) }}
                    </template>
                </Column>

                <template v-if="isOwner">
                    <Column
                        field="harga_jual"
                        header="Harga Jual"
                        sortable
                        style="min-width: 8rem"
                    >
                        <template #body="slotProps">
                            {{ formatCurrency(slotProps.data.harga_jual) }}
                        </template>
                    </Column>
                </template>

                <Column
                    field="brand"
                    header="Brand"
                    sortable
                    style="min-width: 12rem"
                ></Column>

                <Column
                    field="imei"
                    header="IMEI"
                    sortable
                    style="min-width: 12rem"
                ></Column>

                <Column :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button
                            icon="pi pi-money-bill"
                            outlined
                            rounded
                            class="mr-2"
                            @click="openPenjualanDialog(slotProps.data)"
                            :disabled="slotProps.data.status === 'terjual'"
                        />
                        <template v-if="isOwner">
                            <Button
                                icon="pi pi-pencil"
                                outlined
                                rounded
                                class="mr-2"
                                @click="editProduct(slotProps.data)"
                            />
                        </template>
                        <Button
                            icon="pi pi-trash"
                            outlined
                            rounded
                            severity="danger"
                            @click="confirmDeleteProduct(slotProps.data)"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog
            v-model:visible="productDialog"
            :style="{ width: '450px' }"
            header="Product Details"
            :modal="true"
        >
            <div class="flex flex-col gap-6">
                <div>
                    <label for="tanggal" class="block font-bold mb-3"
                        >Tanggal</label
                    >
                    <DatePicker
                        id="tanggal"
                        v-model.trim="product.tanggal"
                        required
                        dateFormat="dd/mm/yy"
                        autofocus
                        :invalid="submitted && !product.tanggal"
                        fluid
                        showIcon
                        :disabled="!!product.id_stock"
                    />
                    <small
                        v-if="submitted && !product.tanggal"
                        class="text-red-500"
                        >Tanggal is required.</small
                    >
                </div>

                <div>
                    <label for="imei" class="block font-bold mb-3">IMEI</label>
                    <InputText
                        id="imei"
                        v-model.trim="product.imei"
                        required
                        :invalid="submitted && !product.imei"
                        fluid
                        :disabled="!!product.id_stock"
                    />
                    <small
                        v-if="submitted && !product.imei"
                        class="text-red-500"
                        >IMEI is required.</small
                    >
                </div>

                <div>
                    <label for="brand" class="block font-bold mb-3"
                        >Brand</label
                    >
                    <InputText
                        id="brand"
                        v-model.trim="product.brand"
                        required
                        :invalid="submitted && !product.brand"
                        fluid
                    />
                    <small
                        v-if="submitted && !product.brand"
                        class="text-red-500"
                        >Brand is required.</small
                    >
                </div>

                <div>
                    <label for="nama" class="block font-bold mb-3">Nama</label>
                    <InputText
                        id="nama"
                        v-model.trim="product.nama"
                        required
                        :invalid="submitted && !product.nama"
                        fluid
                    />
                    <small
                        v-if="submitted && !product.nama"
                        class="text-red-500"
                        >Nama is required.</small
                    >
                </div>

                <div>
                    <label for="warna" class="block font-bold mb-3"
                        >Warna</label
                    >
                    <InputText
                        id="warna"
                        v-model.trim="product.warna"
                        required
                        :invalid="submitted && !product.warna"
                        fluid
                    />
                    <small
                        v-if="submitted && !product.warna"
                        class="text-red-500"
                        >Warna is required.</small
                    >
                </div>

                <div>
                    <label for="harga_masuk" class="block font-bold mb-3"
                        >Harga Masuk</label
                    >
                    <InputNumber
                        id="harga_masuk"
                        v-model="product.harga_masuk"
                        required
                        mode="currency"
                        currency="IDR"
                        locale="id-ID"
                        fluid
                        :invalid="submitted && !product.harga_masuk"
                    />
                    <small
                        v-if="submitted && !product.harga_masuk"
                        class="text-red-500"
                        >Harga Masuk is required.</small
                    >
                </div>

                <div>
                    <label for="harga_jual" class="block font-bold mb-3"
                        >Harga Jual</label
                    >
                    <InputNumber
                        id="harga_jual"
                        v-model="product.harga_jual"
                        required
                        mode="currency"
                        currency="IDR"
                        locale="id-ID"
                        fluid
                        :invalid="submitted && !product.harga_jual"
                    />
                    <small
                        v-if="submitted && !product.harga_jual"
                        class="text-red-500"
                        >Harga Jual is required.</small
                    >
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    icon="pi pi-times"
                    text
                    @click="hideDialog"
                />
                <Button label="Save" icon="pi pi-check" @click="saveProduct" />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="penjualanDialog"
            :style="{ width: '450px' }"
            header="Harga Jual"
            :modal="true"
        >
            <div class="flex flex-col gap-6">
                <div>
                    <label for="harga_jual" class="block font-bold mb-3"
                        >Harga Jual</label
                    >
                    <InputNumber
                        id="harga_jual"
                        v-model="product.harga_jual"
                        required
                        mode="currency"
                        currency="IDR"
                        locale="id-ID"
                        fluid
                        :invalid="submitted && !product.harga_jual"
                    />
                    <small
                        v-if="submitted && !product.harga_jual"
                        class="text-red-500"
                        >Harga Jual is required.</small
                    >
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    icon="pi pi-times"
                    text
                    @click="hideDialogPenjualan"
                />
                <Button
                    label="Save"
                    icon="pi pi-check"
                    @click="createPenjualan"
                />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="deleteProductDialog"
            :style="{ width: '450px' }"
            header="Confirm"
            :modal="true"
        >
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="product"
                    >Are you sure you want to delete <b>{{ product.name }}</b
                    >?</span
                >
            </div>
            <template #footer>
                <Button
                    label="No"
                    icon="pi pi-times"
                    text
                    @click="deleteProductDialog = false"
                />
                <Button label="Yes" icon="pi pi-check" @click="deleteProduct" />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="deleteProductsDialog"
            :style="{ width: '450px' }"
            header="Confirm"
            :modal="true"
        >
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="product"
                    >Are you sure you want to delete the selected
                    products?</span
                >
            </div>
            <template #footer>
                <Button
                    label="No"
                    icon="pi pi-times"
                    text
                    @click="deleteProductsDialog = false"
                />
                <Button
                    label="Yes"
                    icon="pi pi-check"
                    text
                    @click="deleteSelectedProducts"
                />
            </template>
        </Dialog>
    </div>
</template>
