<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useForm, usePage, Link } from "@inertiajs/vue3";
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
    StockAdjustment,
    StockAdjustmentStatusValue,
    StockAdjustmentTypeValue,
    StockAdjustmentReasonValue,
    Warehouse,
} from "@/types/models";

interface SimpleProduct {
    id: number;
    sku: string;
    name: string;
    warehouse_id: number;
    stock: number;
}

const props = defineProps<{
    stockAdjustments: { data: StockAdjustment[] };
    warehouses: Warehouse[];
    products: SimpleProduct[];
}>();

const breadcrumbs = [
    { label: "Stock Adjustments", href: "/stock-adjustments" },
];

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true },
);

function statusVariant(status: StockAdjustmentStatusValue) {
    if (status === "Rejected") return "destructive";
    if (status === "Approved") return "default";
    return "secondary"; // Pending
}

const REASONS: StockAdjustmentReasonValue[] = [
    "Damaged",
    "Expired",
    "Lost",
    "Found",
    "Correction",
];

// --- create dialog ---
const dialogOpen = ref(false);

interface DraftLine {
    product_id: string;
    qty: number;
}

const form = useForm({
    warehouse_id: "",
    type: "" as StockAdjustmentTypeValue | "",
    reason: "" as StockAdjustmentReasonValue | "",
    date: new Date().toISOString().slice(0, 10),
    notes: "",
    items: [{ product_id: "", qty: 1 }] as DraftLine[],
});

const productsForSelectedWarehouse = computed(() =>
    props.products.filter(
        (p) => String(p.warehouse_id) === String(form.warehouse_id),
    ),
);

function stockFor(productId: string): number | null {
    const product = props.products.find((p) => String(p.id) === productId);
    return product ? product.stock : null;
}

function openCreateDialog() {
    form.reset();
    form.clearErrors();
    form.date = new Date().toISOString().slice(0, 10);
    dialogOpen.value = true;
}

function addLine() {
    form.items.push({ product_id: "", qty: 1 });
}

function removeLine(index: number) {
    if (form.items.length > 1) form.items.splice(index, 1);
}

function submit() {
    form.post("/stock-adjustments", {
        onSuccess: () => (dialogOpen.value = false),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Stock Adjustments</h1>
                <Button @click="openCreateDialog">New Adjustment</Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Adjustment #</TableHead>
                        <TableHead>Warehouse</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Reason</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="stockAdjustments.data.length === 0"
                        :colspan="7"
                    >
                        No stock adjustments yet.
                    </TableEmpty>
                    <TableRow
                        v-for="adj in stockAdjustments.data"
                        :key="adj.id"
                    >
                        <TableCell class="font-mono text-sm">
                            {{ adj.adjustment_number }}
                        </TableCell>
                        <TableCell>{{ adj.warehouse.name }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    adj.type === 'IN' ? 'default' : 'secondary'
                                "
                            >
                                {{ adj.type }}
                            </Badge>
                        </TableCell>
                        <TableCell>{{ adj.reason }}</TableCell>
                        <TableCell>{{ adj.date }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(adj.status)">
                                {{ adj.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Link :href="`/stock-adjustments/${adj.id}`">
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
                    <DialogTitle>New Stock Adjustment</DialogTitle>
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
                            <FieldLabel>Date</FieldLabel>
                            <Input v-model="form.date" type="date" />
                            <FieldError :errors="[form.errors.date]" />
                        </Field>

                        <Field>
                            <FieldLabel>Type</FieldLabel>
                            <Select v-model="form.type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="IN"
                                        >IN — add stock</SelectItem
                                    >
                                    <SelectItem value="OUT"
                                        >OUT — remove stock</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <FieldError :errors="[form.errors.type]" />
                        </Field>

                        <Field>
                            <FieldLabel>Reason</FieldLabel>
                            <Select v-model="form.reason">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select reason" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="r in REASONS"
                                        :key="r"
                                        :value="r"
                                    >
                                        {{ r }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError :errors="[form.errors.reason]" />
                        </Field>
                    </div>

                    <!-- Line items — no unit_price/subtotal here, an adjustment
                         corrects quantity, not value. -->
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
                            class="flex flex-col gap-1"
                        >
                            <div
                                class="grid grid-cols-[2fr_1fr_auto] items-end gap-2"
                            >
                                <Field>
                                    <Select v-model="line.product_id">
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
                                                {{ p.sku }} — {{ p.name }} ({{
                                                    p.stock
                                                }}
                                                in stock)
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
                            <p
                                v-if="
                                    form.type === 'OUT' &&
                                    stockFor(line.product_id) !== null &&
                                    line.qty > stockFor(line.product_id)!
                                "
                                class="text-sm text-destructive"
                            >
                                Only {{ stockFor(line.product_id) }} in stock —
                                this will be rejected on approval.
                            </p>
                        </div>
                        <FieldError :errors="[form.errors.items]" />
                    </div>

                    <Field>
                        <FieldLabel>Notes (optional)</FieldLabel>
                        <Input v-model="form.notes" />
                    </Field>

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            Create as Pending
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
