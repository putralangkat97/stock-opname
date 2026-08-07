<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useForm, usePage, Link } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AppLayout from "@/layouts/app-layout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Checkbox } from "@/components/ui/checkbox";
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
    StockOpname,
    StockOpnameStatusValue,
    Warehouse,
} from "@/types/models";

interface SimpleProduct {
    id: number;
    sku: string;
    name: string;
    warehouse_id: number;
}

interface AssignableUser {
    id: number;
    name: string;
    warehouse_ids: number[];
}

const props = defineProps<{
    stockOpnames: { data: StockOpname[] };
    warehouses: Warehouse[];
    products: SimpleProduct[];
    assignableUsers: AssignableUser[];
}>();

const breadcrumbs = [{ label: "Stock Opnames", href: "/stock-opnames" }];

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true },
);

function statusVariant(status: StockOpnameStatusValue) {
    if (status === "Rejected") return "destructive";
    if (status === "Approved") return "default";
    if (status === "Completed") return "outline";
    if (status === "In Progress") return "outline";
    return "secondary"; // Draft
}

// --- create dialog ---
const dialogOpen = ref(false);

const form = useForm({
    warehouse_id: "",
    assigned_to: "",
    title: "",
    start_date: new Date().toISOString().slice(0, 10),
    notes: "",
    product_ids: [] as number[],
});

const productsForSelectedWarehouse = computed(() =>
    props.products.filter(
        (p) => String(p.warehouse_id) === String(form.warehouse_id),
    ),
);

const assigneesForSelectedWarehouse = computed(() =>
    props.assignableUsers.filter((u) =>
        u.warehouse_ids.map(String).includes(String(form.warehouse_id)),
    ),
);

function openCreateDialog() {
    form.reset();
    form.clearErrors();
    form.start_date = new Date().toISOString().slice(0, 10);
    dialogOpen.value = true;
}

function toggleProduct(productId: number, checked: boolean) {
    if (checked) {
        form.product_ids.push(productId);
    } else {
        form.product_ids = form.product_ids.filter((id) => id !== productId);
    }
}

function selectAllProducts() {
    form.product_ids = productsForSelectedWarehouse.value.map((p) => p.id);
}

function submit() {
    // Server only wants { product_id } per line — map the checkbox selection
    // into that shape here rather than changing the request contract.
    form.transform((data) => ({
        ...data,
        items: data.product_ids.map((id) => ({ product_id: id })),
    })).post("/stock-opnames", {
        onSuccess: () => (dialogOpen.value = false),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Stock Opnames</h1>
                <Button @click="openCreateDialog">New Opname</Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Opname #</TableHead>
                        <TableHead>Title</TableHead>
                        <TableHead>Warehouse</TableHead>
                        <TableHead>Assigned To</TableHead>
                        <TableHead>Start Date</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="stockOpnames.data.length === 0"
                        :colspan="7"
                    >
                        No stock opnames yet.
                    </TableEmpty>
                    <TableRow
                        v-for="opname in stockOpnames.data"
                        :key="opname.id"
                    >
                        <TableCell class="font-mono text-sm">
                            {{ opname.opname_number }}
                        </TableCell>
                        <TableCell>{{ opname.title }}</TableCell>
                        <TableCell>{{ opname.warehouse.name }}</TableCell>
                        <TableCell>{{ opname.assigned_to.name }}</TableCell>
                        <TableCell>{{ opname.start_date }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(opname.status)">
                                {{ opname.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Link :href="`/stock-opnames/${opname.id}`">
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
                    <DialogTitle>New Stock Opname</DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <Field>
                        <FieldLabel>Title</FieldLabel>
                        <Input
                            v-model="form.title"
                            placeholder="e.g. Q3 2026 Full Count"
                        />
                        <FieldError :errors="[form.errors.title]" />
                    </Field>

                    <div class="grid grid-cols-3 gap-4">
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
                            <FieldLabel>Assign To</FieldLabel>
                            <Select
                                v-model="form.assigned_to"
                                :disabled="!form.warehouse_id"
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select supervisor"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="u in assigneesForSelectedWarehouse"
                                        :key="u.id"
                                        :value="String(u.id)"
                                    >
                                        {{ u.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError :errors="[form.errors.assigned_to]" />
                        </Field>

                        <Field>
                            <FieldLabel>Start Date</FieldLabel>
                            <Input v-model="form.start_date" type="date" />
                            <FieldError :errors="[form.errors.start_date]" />
                        </Field>
                    </div>

                    <!-- Product selection — no qty here at all. system_qty is
                         snapshotted server-side from each product's live stock
                         the moment the line is created, never submitted by us. -->
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <FieldLabel>Products to Count</FieldLabel>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="!form.warehouse_id"
                                @click="selectAllProducts"
                            >
                                Select All
                            </Button>
                        </div>
                        <div
                            class="flex max-h-64 flex-col gap-1 overflow-y-auto rounded-md border p-2"
                        >
                            <p
                                v-if="!form.warehouse_id"
                                class="p-2 text-sm text-muted-foreground"
                            >
                                Pick a warehouse first.
                            </p>
                            <label
                                v-for="p in productsForSelectedWarehouse"
                                :key="p.id"
                                class="flex items-center gap-2 rounded p-2 hover:bg-muted"
                            >
                                <Checkbox
                                    :model-value="
                                        form.product_ids.includes(p.id)
                                    "
                                    @update:model-value="
                                        (v) => toggleProduct(p.id, !!v)
                                    "
                                />
                                <span class="text-sm">
                                    {{ p.sku }} — {{ p.name }}
                                </span>
                            </label>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ form.product_ids.length }} product(s) selected
                        </p>
                        <FieldError :errors="[form.errors.items]" />
                    </div>

                    <Field>
                        <FieldLabel>Notes (optional)</FieldLabel>
                        <Input v-model="form.notes" />
                    </Field>

                    <DialogFooter>
                        <Button
                            type="submit"
                            :disabled="
                                form.processing || form.product_ids.length === 0
                            "
                        >
                            Create as Draft
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
