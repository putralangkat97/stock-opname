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
    DialogDescription,
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
    GoodsIssue,
    GoodsIssueStatusValue,
    Customer,
    Warehouse,
} from "@/types/models";
import { LoaderIcon } from "@lucide/vue";

interface SimpleProduct {
    id: number;
    sku: string;
    name: string;
    warehouse_id: number;
    stock: number;
    selling_price: string;
}

const props = defineProps<{
    goodsIssues: { data: GoodsIssue[] };
    customers: Customer[];
    warehouses: Warehouse[];
    products: SimpleProduct[];
}>();

const breadcrumbs = [{ label: "Goods Issues", href: "/goods-issues" }];

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true },
);

function statusVariant(status: GoodsIssueStatusValue) {
    if (status === "Cancelled") return "destructive";
    if (status === "Issued") return "default";
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
    customer_id: "",
    warehouse_id: "",
    so_number: "",
    date: new Date().toISOString().slice(0, 10),
    notes: "",
    items: [{ product_id: "", qty: 1, unit_price: 0 }] as DraftLine[],
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
    form.items.push({ product_id: "", qty: 1, unit_price: 0 });
}

function removeLine(index: number) {
    if (form.items.length > 1) form.items.splice(index, 1);
}

function onProductPicked(index: number, productId: string) {
    const product = props.products.find((p) => String(p.id) === productId);
    if (product) form.items[index].unit_price = Number(product.selling_price);
}

const estimatedTotal = computed(() =>
    form.items.reduce((sum, line) => sum + line.qty * line.unit_price, 0),
);

function submit() {
    form.post("/goods-issues", {
        onSuccess: () => (dialogOpen.value = false),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Goods Issues</h1>
                <Button @click="openCreateDialog">New Issue</Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Issue #</TableHead>
                        <TableHead>Customer</TableHead>
                        <TableHead>Warehouse</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Total</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="goodsIssues.data.length === 0"
                        :colspan="7"
                    >
                        No goods issues yet.
                    </TableEmpty>
                    <TableRow v-for="issue in goodsIssues.data" :key="issue.id">
                        <TableCell class="font-mono text-sm">
                            {{ issue.issue_number }}
                        </TableCell>
                        <TableCell>{{ issue.customer.name }}</TableCell>
                        <TableCell>{{ issue.warehouse.name }}</TableCell>
                        <TableCell>{{ issue.date }}</TableCell>
                        <TableCell>{{ issue.total_amount }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(issue.status)">
                                {{ issue.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Link :href="`/goods-issues/${issue.id}`">
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
                    <DialogTitle>New Goods Issue</DialogTitle>
                    <DialogDescription>Form goods issues</DialogDescription>
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
                            <FieldLabel>Customer</FieldLabel>
                            <Select v-model="form.customer_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select customer"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="c in customers"
                                        :key="c.id"
                                        :value="String(c.id)"
                                    >
                                        {{ c.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError :errors="[form.errors.customer_id]" />
                        </Field>

                        <Field>
                            <FieldLabel>SO Number (optional)</FieldLabel>
                            <Input v-model="form.so_number" />
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
                            class="flex flex-col gap-1"
                        >
                            <div
                                class="grid grid-cols-[2fr_1fr_1fr_auto] items-end gap-2"
                            >
                                <Field>
                                    <Select
                                        v-model="line.product_id"
                                        @update:model-value="
                                            (v) =>
                                                onProductPicked(
                                                    index,
                                                    v as string,
                                                )
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
                            <p
                                v-if="
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

                        <div class="text-right text-sm text-muted-foreground">
                            Estimated total: {{ estimatedTotal.toFixed(2) }}
                        </div>
                    </div>

                    <Field>
                        <FieldLabel>Notes (optional)</FieldLabel>
                        <Input v-model="form.notes" />
                    </Field>

                    <DialogFooter>
                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            <LoaderIcon
                                v-if="form.processing"
                                class="animate-spin"
                            />
                            <template v-else> Create as Draft </template>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
