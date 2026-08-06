<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { router, useForm, usePage, Link } from "@inertiajs/vue3";
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Field, FieldLabel, FieldError } from "@/components/ui/field";
import type {
    GoodsReceipt,
    GoodsReceiptStatusValue,
    Supplier,
    Warehouse,
} from "@/types/models";

interface SimpleProduct {
    id: number;
    sku: string;
    name: string;
    warehouse_id: number;
    cost_price: string;
}

const props = defineProps<{
    goodsReceipts: { data: GoodsReceipt[] };
    suppliers: Supplier[];
    warehouses: Warehouse[];
    products: SimpleProduct[];
}>();

const breadcrumbs = [{ label: "Goods Receipts", href: "/goods-receipts" }];

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true },
);

function statusVariant(status: GoodsReceiptStatusValue) {
    if (status === "Cancelled") return "destructive";
    if (status === "Received") return "default";
    return "secondary"; // Draft
}

// --- create dialog ---
const dialogOpen = ref(false);

interface DraftLine {
    product_id: string;
    qty: number;
    unit_price: number;
}

const form = useForm({
    supplier_id: "",
    warehouse_id: "",
    po_number: "",
    date: new Date().toISOString().slice(0, 10),
    notes: "",
    items: [{ product_id: "", qty: 1, unit_price: 0 }] as DraftLine[],
});

const productsForSelectedWarehouse = computed(() =>
    props.products.filter(
        (p) => String(p.warehouse_id) === String(form.warehouse_id),
    ),
);

function openCreateDialog() {
    form.reset();
    form.clearErrors();
    form.date = new Date().toISOString().slice(0, 10);
    dialogOpen.value = true;
}

function addLine() {
    form.items.push({ product_id: "", qty: 1, unit_price: 0 });
}

function removeLine(index: number) {
    if (form.items.length > 1) form.items.splice(index, 1);
}

function onProductPicked(index: number, productId: string) {
    const product = props.products.find((p) => String(p.id) === productId);
    if (product) form.items[index].unit_price = Number(product.cost_price);
}

const estimatedTotal = computed(() =>
    form.items.reduce((sum, line) => sum + line.qty * line.unit_price, 0),
);

function submit() {
    form.post("/goods-receipts", {
        onSuccess: () => (dialogOpen.value = false),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Goods Receipts</h1>
                <Button @click="openCreateDialog">New Receipt</Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Receipt #</TableHead>
                        <TableHead>Supplier</TableHead>
                        <TableHead>Warehouse</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Total</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="goodsReceipts.data.length === 0"
                        :colspan="7"
                    >
                        No goods receipts yet.
                    </TableEmpty>
                    <TableRow
                        v-for="receipt in goodsReceipts.data"
                        :key="receipt.id"
                    >
                        <TableCell class="font-mono text-sm">
                            {{ receipt.receipt_number }}
                        </TableCell>
                        <TableCell>{{ receipt.supplier.name }}</TableCell>
                        <TableCell>{{ receipt.warehouse.name }}</TableCell>
                        <TableCell>{{ receipt.date }}</TableCell>
                        <TableCell>{{ receipt.total_amount }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(receipt.status)">
                                {{ receipt.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Link :href="`/goods-receipts/${receipt.id}`">
                                <Button variant="outline" size="sm">
                                    View
                                </Button>
                            </Link>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Create dialog -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>New Goods Receipt</DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <Field>
                            <FieldLabel>Warehouse</FieldLabel>
                            <Select v-model="form.warehouse_id">
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

                        <Field>
                            <FieldLabel>Supplier</FieldLabel>
                            <Select v-model="form.supplier_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select supplier"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="s in suppliers"
                                        :key="s.id"
                                        :value="String(s.id)"
                                    >
                                        {{ s.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError :errors="[form.errors.supplier_id]" />
                        </Field>

                        <Field>
                            <FieldLabel>PO Number (optional)</FieldLabel>
                            <Input v-model="form.po_number" />
                        </Field>

                        <Field>
                            <FieldLabel>Date</FieldLabel>
                            <Input v-model="form.date" type="date" />
                            <FieldError :errors="[form.errors.date]" />
                        </Field>
                    </div>

                    <!-- Line items -->
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <FieldLabel>Items</FieldLabel>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addLine"
                            >
                                Add Item
                            </Button>
                        </div>

                        <div
                            v-for="(line, index) in form.items"
                            :key="index"
                            class="grid grid-cols-[2fr_1fr_1fr_auto] items-end gap-2"
                        >
                            <Field>
                                <Select
                                    v-model="line.product_id"
                                    @update:model-value="
                                        (v) =>
                                            onProductPicked(index, v as string)
                                    "
                                >
                                    <SelectTrigger>
                                        <SelectValue
                                            placeholder="Select product"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="p in productsForSelectedWarehouse"
                                            :key="p.id"
                                            :value="String(p.id)"
                                        >
                                            {{ p.sku }} — {{ p.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </Field>
                            <Field>
                                <Input
                                    v-model.number="line.qty"
                                    type="number"
                                    min="1"
                                    placeholder="Qty"
                                />
                            </Field>
                            <Field>
                                <Input
                                    v-model.number="line.unit_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="Unit price"
                                />
                            </Field>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                :disabled="form.items.length === 1"
                                @click="removeLine(index)"
                            >
                                ✕
                            </Button>
                        </div>
                        <FieldError :errors="[form.errors.items]" />

                        <div class="text-right text-sm text-muted-foreground">
                            Estimated total: {{ estimatedTotal.toFixed(2) }}
                        </div>
                    </div>

                    <Field>
                        <FieldLabel>Notes (optional)</FieldLabel>
                        <Input v-model="form.notes" />
                    </Field>

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            Create as Draft
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
