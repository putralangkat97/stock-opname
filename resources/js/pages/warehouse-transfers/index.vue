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
    WarehouseTransfer,
    WarehouseTransferStatusValue,
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
    warehouseTransfers: { data: WarehouseTransfer[] };
    warehouses: Warehouse[];
    products: SimpleProduct[];
}>();

const breadcrumbs = [
    { label: "Warehouse Transfers", href: "/warehouse-transfers" },
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

function statusVariant(status: WarehouseTransferStatusValue) {
    if (status === "Rejected") return "destructive";
    if (status === "Completed") return "default";
    if (status === "In Transit") return "outline";
    return "secondary"; // Pending
}

// --- create dialog ---
const dialogOpen = ref(false);

interface DraftLine {
    product_id: string;
    qty: number;
}

const form = useForm({
    from_warehouse_id: "",
    to_warehouse_id: "",
    date: new Date().toISOString().slice(0, 10),
    notes: "",
    items: [{ product_id: "", qty: 1 }] as DraftLine[],
});

// Only products that exist at the source warehouse can be transferred out of
// it — the destination gets a new row automatically on complete() if this
// SKU doesn't exist there yet.
const productsForSourceWarehouse = computed(() =>
    props.products.filter(
        (p) => String(p.warehouse_id) === String(form.from_warehouse_id),
    ),
);

const toWarehouseOptions = computed(() =>
    props.warehouses.filter(
        (w) => String(w.id) !== String(form.from_warehouse_id),
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
    form.post("/warehouse-transfers", {
        onSuccess: () => (dialogOpen.value = false),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Warehouse Transfers</h1>
                <Button @click="openCreateDialog">New Transfer</Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Transfer #</TableHead>
                        <TableHead>From</TableHead>
                        <TableHead>To</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="warehouseTransfers.data.length === 0"
                        :colspan="6"
                    >
                        No warehouse transfers yet.
                    </TableEmpty>
                    <TableRow
                        v-for="transfer in warehouseTransfers.data"
                        :key="transfer.id"
                    >
                        <TableCell class="font-mono text-sm">
                            {{ transfer.transfer_number }}
                        </TableCell>
                        <TableCell>
                            {{ transfer.from_warehouse.name }}
                        </TableCell>
                        <TableCell>{{ transfer.to_warehouse.name }}</TableCell>
                        <TableCell>{{ transfer.date }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(transfer.status)">
                                {{ transfer.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Link :href="`/warehouse-transfers/${transfer.id}`">
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
                    <DialogTitle>New Warehouse Transfer</DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <Field>
                            <FieldLabel>From Warehouse</FieldLabel>
                            <Select v-model="form.from_warehouse_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Source warehouse"
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
                            <FieldError
                                :errors="[form.errors.from_warehouse_id]"
                            />
                        </Field>

                        <Field>
                            <FieldLabel>To Warehouse</FieldLabel>
                            <Select v-model="form.to_warehouse_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Destination warehouse"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="w in toWarehouseOptions"
                                        :key="w.id"
                                        :value="String(w.id)"
                                    >
                                        {{ w.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError
                                :errors="[form.errors.to_warehouse_id]"
                            />
                        </Field>

                        <Field>
                            <FieldLabel>Date</FieldLabel>
                            <Input v-model="form.date" type="date" />
                            <FieldError :errors="[form.errors.date]" />
                        </Field>
                    </div>

                    <!-- Line items — no unit_price here either, a transfer moves
                         stock, it doesn't re-price it. -->
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <FieldLabel>Items</FieldLabel>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="!form.from_warehouse_id"
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
                                                v-for="p in productsForSourceWarehouse"
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
                                    stockFor(line.product_id) !== null &&
                                    line.qty > stockFor(line.product_id)!
                                "
                                class="text-sm text-destructive"
                            >
                                Only {{ stockFor(line.product_id) }} in stock at
                                the source — this will be rejected when marked
                                In Transit.
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
