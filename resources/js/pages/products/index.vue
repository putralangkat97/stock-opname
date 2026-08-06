<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AppLayout from "@/layouts/app-layout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    TableEmpty,
} from "@/components/ui/table";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogCancel,
    AlertDialogAction,
} from "@/components/ui/alert-dialog";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Field, FieldLabel, FieldError } from "@/components/ui/field";
import type {
    PaginatedData,
    Product,
    Category,
    Brand,
    Unit,
    Warehouse,
    BinLocation,
} from "@/types/models";

const props = defineProps<{
    products: PaginatedData<Product>;
    filters: { warehouse_id?: string; category_id?: string; search?: string };
    categories: Category[];
    brands: Brand[];
    units: Unit[];
    warehouses: Warehouse[];
    binLocations: BinLocation[];
}>();

const breadcrumbs = [{ label: "Products", href: "/products" }];

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true },
);

const search = ref(props.filters.search ?? "");
const categoryFilter = ref(props.filters.category_id ?? "");
const warehouseFilter = ref(props.filters.warehouse_id ?? "");

function applyFilters() {
    router.get(
        "/products",
        {
            search: search.value || undefined,
            category_id: categoryFilter.value || undefined,
            warehouse_id: warehouseFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

const dialogOpen = ref(false);
const editingProduct = ref<Product | null>(null);

const form = useForm({
    category_id: "",
    brand_id: "",
    unit_id: "",
    warehouse_id: "",
    bin_location_id: "",
    sku: "",
    barcode: "",
    name: "",
    stock: 0,
    min_stock: 0,
    max_stock: undefined as number | undefined,
    cost_price: 0,
    selling_price: 0,
});

const binOptionsForSelectedWarehouse = computed(() =>
    props.binLocations.filter(
        (bin) => String(bin.warehouse_id) === String(form.warehouse_id),
    ),
);

function openCreateDialog() {
    editingProduct.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEditDialog(product: Product) {
    editingProduct.value = product;
    form.clearErrors();
    form.category_id = String(product.category.id);
    form.brand_id = String(product.brand.id);
    form.unit_id = String(product.unit.id);
    form.warehouse_id = String(product.warehouse.id);
    form.bin_location_id = product.bin_location
        ? String(product.bin_location.id)
        : "";
    form.sku = product.sku;
    form.barcode = product.barcode ?? "";
    form.name = product.name;
    form.min_stock = product.min_stock;
    form.max_stock = product.max_stock ?? undefined;
    form.cost_price = Number(product.cost_price);
    form.selling_price = Number(product.selling_price);
    // stock is intentionally NOT editable here — see UpdateProductRequest note.
    dialogOpen.value = true;
}

function submit() {
    if (editingProduct.value) {
        form.put(`/products/${editingProduct.value.id}`, {
            onSuccess: () => (dialogOpen.value = false),
        });
    } else {
        form.post("/products", {
            onSuccess: () => (dialogOpen.value = false),
        });
    }
}

const deletingProduct = ref<Product | null>(null);

function confirmDelete() {
    if (!deletingProduct.value) return;
    router.delete(`/products/${deletingProduct.value.id}`, {
        onSuccess: () => (deletingProduct.value = null),
    });
}

function statusVariant(status: Product["status"]) {
    if (status === "Out of Stock") return "destructive";
    if (status === "Low Stock") return "secondary";
    return "default";
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Products</h1>
                <Button @click="openCreateDialog">Add Product</Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-2">
                <Input
                    v-model="search"
                    placeholder="Search by name or SKU..."
                    class="max-w-xs"
                    @keyup.enter="applyFilters"
                />
                <Select
                    v-model="categoryFilter"
                    @update:model-value="applyFilters"
                >
                    <SelectTrigger class="w-48">
                        <SelectValue placeholder="All categories" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All categories</SelectItem>
                        <SelectItem
                            v-for="category in categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select
                    v-model="warehouseFilter"
                    @update:model-value="applyFilters"
                >
                    <SelectTrigger class="w-48">
                        <SelectValue placeholder="All warehouses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All warehouses</SelectItem>
                        <SelectItem
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :value="String(warehouse.id)"
                        >
                            {{ warehouse.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button variant="outline" @click="applyFilters">Search</Button>
            </div>

            <!-- Table -->
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>SKU</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Warehouse</TableHead>
                        <TableHead>Stock</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="products.data.length === 0" :colspan="6">
                        No products found.
                    </TableEmpty>
                    <TableRow
                        v-for="product in products.data"
                        :key="product.id"
                    >
                        <TableCell class="font-mono text-sm">
                            {{ product.sku }}
                        </TableCell>
                        <TableCell>{{ product.name }}</TableCell>
                        <TableCell>{{ product.warehouse.name }}</TableCell>
                        <TableCell>
                            {{ product.stock }}
                            {{ product.unit.symbol }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(product.status)">
                                {{ product.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="flex justify-end gap-2 text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openEditDialog(product)"
                            >
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deletingProduct = product"
                            >
                                Delete
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <!-- Simple prev/next pager -->
            <div
                class="flex items-center justify-between text-sm text-muted-foreground"
            >
                <span>
                    Page {{ products.current_page }} of
                    {{ products.last_page }}
                </span>
                <div class="flex gap-2">
                    <Button
                        v-for="link in products.links"
                        :key="link.label"
                        variant="outline"
                        size="sm"
                        :disabled="!link.url"
                        :class="{ 'bg-muted': link.active }"
                        v-html="link.label"
                        @click="
                            link.url &&
                            router.get(link.url, {}, { preserveState: true })
                        "
                    />
                </div>
            </div>
        </div>

        <!-- Create / Edit dialog -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingProduct ? "Edit Product" : "Add Product" }}
                    </DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <Field>
                            <FieldLabel>Category</FieldLabel>
                            <Select v-model="form.category_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select category"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="c in categories"
                                        :key="c.id"
                                        :value="String(c.id)"
                                    >
                                        {{ c.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError :errors="[form.errors.category_id]" />
                        </Field>

                        <Field>
                            <FieldLabel>Brand</FieldLabel>
                            <Select v-model="form.brand_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select brand" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="b in brands"
                                        :key="b.id"
                                        :value="String(b.id)"
                                    >
                                        {{ b.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError :errors="[form.errors.brand_id]" />
                        </Field>

                        <Field>
                            <FieldLabel>Unit</FieldLabel>
                            <Select v-model="form.unit_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select unit" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="u in units"
                                        :key="u.id"
                                        :value="String(u.id)"
                                    >
                                        {{ u.name }} ({{ u.symbol }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError :errors="[form.errors.unit_id]" />
                        </Field>

                        <Field>
                            <FieldLabel>Warehouse</FieldLabel>
                            <Select
                                v-model="form.warehouse_id"
                                :disabled="!!editingProduct"
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select warehouse"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="w in warehouses"
                                        :key="w.id"
                                        :value="String(w.id)"
                                    >
                                        {{ w.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError :errors="[form.errors.warehouse_id]" />
                        </Field>
                    </div>

                    <Field>
                        <FieldLabel>Bin Location (optional)</FieldLabel>
                        <Select v-model="form.bin_location_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Unassigned" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Unassigned</SelectItem>
                                <SelectItem
                                    v-for="bin in binOptionsForSelectedWarehouse"
                                    :key="bin.id"
                                    :value="String(bin.id)"
                                >
                                    {{ bin.code }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FieldError :errors="[form.errors.bin_location_id]" />
                    </Field>

                    <div class="grid grid-cols-2 gap-4">
                        <Field>
                            <FieldLabel>SKU</FieldLabel>
                            <Input v-model="form.sku" />
                            <FieldError :errors="[form.errors.sku]" />
                        </Field>
                        <Field>
                            <FieldLabel>Barcode</FieldLabel>
                            <Input v-model="form.barcode" />
                            <FieldError :errors="[form.errors.barcode]" />
                        </Field>
                    </div>

                    <Field>
                        <FieldLabel>Name</FieldLabel>
                        <Input v-model="form.name" />
                        <FieldError :errors="[form.errors.name]" />
                    </Field>

                    <div class="grid grid-cols-3 gap-4">
                        <Field v-if="!editingProduct">
                            <FieldLabel>Initial Stock</FieldLabel>
                            <Input
                                v-model.number="form.stock"
                                type="number"
                                min="0"
                            />
                            <FieldError :errors="[form.errors.stock]" />
                        </Field>
                        <Field>
                            <FieldLabel>Min Stock</FieldLabel>
                            <Input
                                v-model.number="form.min_stock"
                                type="number"
                                min="0"
                            />
                            <FieldError :errors="[form.errors.min_stock]" />
                        </Field>
                        <Field>
                            <FieldLabel>Max Stock</FieldLabel>
                            <Input
                                v-model.number="form.max_stock"
                                type="number"
                                min="0"
                            />
                            <FieldError :errors="[form.errors.max_stock]" />
                        </Field>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <Field>
                            <FieldLabel>Cost Price</FieldLabel>
                            <Input
                                v-model.number="form.cost_price"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                            <FieldError :errors="[form.errors.cost_price]" />
                        </Field>
                        <Field>
                            <FieldLabel>Selling Price</FieldLabel>
                            <Input
                                v-model.number="form.selling_price"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                            <FieldError :errors="[form.errors.selling_price]" />
                        </Field>
                    </div>

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            {{
                                editingProduct
                                    ? "Save Changes"
                                    : "Create Product"
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete confirmation -->
        <AlertDialog
            :open="!!deletingProduct"
            @update:open="(v) => !v && (deletingProduct = null)"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this product?</AlertDialogTitle>
                    <AlertDialogDescription>
                        "{{ deletingProduct?.name }}" will be removed. This
                        can't be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deletingProduct = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction @click="confirmDelete">
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
