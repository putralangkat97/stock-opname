<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { router, useForm, usePage, Link } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AppLayout from "@/layouts/app-layout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
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
import type {
    StockOpname,
    StockOpnameStatusValue,
    StockOpnameItemStatusValue,
} from "@/types/models";

const props = defineProps<{
    stockOpname: StockOpname;
}>();

const breadcrumbs = [
    { label: "Stock Opnames", href: "/stock-opnames" },
    { label: props.stockOpname.opname_number },
];

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        // "All lines must be counted before completing" lands here.
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true },
);

function statusVariant(status: StockOpnameStatusValue) {
    if (status === "Approved") return "default";
    return "secondary";
}

function itemStatusVariant(status: StockOpnameItemStatusValue) {
    if (status === "Shortage") return "destructive";
    if (status === "Surplus") return "outline";
    if (status === "Matched") return "default";
    return "secondary"; // Uncounted
}

function variance(item: { system_qty: number; physical_qty: number | null }) {
    return item.physical_qty === null
        ? null
        : item.physical_qty - item.system_qty;
}

// --- per-line count recording ---
// One useForm per line, keyed by item id, so each row submits independently
// without disturbing the others' input state.
const countForms = Object.fromEntries(
    (props.stockOpname.items ?? []).map((item) => [
        item.id,
        useForm({ physical_qty: item.physical_qty ?? 0 }),
    ]),
);

function recordCount(itemId: number) {
    countForms[itemId].post(`/stock-opname-items/${itemId}/record-count`, {
        preserveScroll: true,
    });
}

const canCount = computed(() => props.stockOpname.status === "In Progress");
const allCounted = computed(() =>
    (props.stockOpname.items ?? []).every((i) => i.physical_qty !== null),
);

// --- lifecycle actions ---
const startDialogOpen = ref(false);
const completeDialogOpen = ref(false);
const approveDialogOpen = ref(false);
const rejectDialogOpen = ref(false);
const processing = ref(false);

function start() {
    processing.value = true;
    router.post(
        `/stock-opnames/${props.stockOpname.id}/start`,
        {},
        {
            onFinish: () => {
                processing.value = false;
                startDialogOpen.value = false;
            },
        },
    );
}

function complete() {
    processing.value = true;
    router.post(
        `/stock-opnames/${props.stockOpname.id}/complete`,
        {},
        {
            onFinish: () => {
                processing.value = false;
                completeDialogOpen.value = false;
            },
        },
    );
}

function approve() {
    processing.value = true;
    router.post(
        `/stock-opnames/${props.stockOpname.id}/approve`,
        {},
        {
            onFinish: () => {
                processing.value = false;
                approveDialogOpen.value = false;
            },
        },
    );
}

function reject() {
    processing.value = true;
    router.post(
        `/stock-opnames/${props.stockOpname.id}/reject`,
        {},
        {
            onFinish: () => {
                processing.value = false;
                rejectDialogOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">
                        {{ stockOpname.title }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ stockOpname.opname_number }} —
                        {{ stockOpname.warehouse.name }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge
                        :variant="statusVariant(stockOpname.status)"
                        class="text-sm"
                    >
                        {{ stockOpname.status }}
                    </Badge>

                    <Button
                        v-if="stockOpname.status === 'Draft'"
                        @click="startDialogOpen = true"
                    >
                        Start Counting
                    </Button>

                    <template v-else-if="stockOpname.status === 'In Progress'">
                        <Button
                            :disabled="!allCounted"
                            :title="
                                !allCounted
                                    ? 'All lines must be counted first'
                                    : ''
                            "
                            @click="completeDialogOpen = true"
                        >
                            Complete
                        </Button>
                    </template>

                    <template v-else-if="stockOpname.status === 'Completed'">
                        <Button
                            variant="outline"
                            @click="rejectDialogOpen = true"
                        >
                            Send Back for Recount
                        </Button>
                        <Button @click="approveDialogOpen = true">
                            Approve
                        </Button>
                    </template>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4"
                >
                    <div>
                        <p class="text-muted-foreground">Assigned To</p>
                        <p>{{ stockOpname.assigned_to.name }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Start Date</p>
                        <p>{{ stockOpname.start_date }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Completed Date</p>
                        <p>{{ stockOpname.completed_date ?? "—" }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Approved By</p>
                        <p>{{ stockOpname.approved_by?.name ?? "—" }}</p>
                    </div>
                    <div v-if="stockOpname.notes" class="col-span-full">
                        <p class="text-muted-foreground">Notes</p>
                        <p>{{ stockOpname.notes }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Variance summary — only meaningful once at least Completed -->
            <Card v-if="['Completed', 'Approved'].includes(stockOpname.status)">
                <CardHeader>
                    <CardTitle>Variance Summary</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4"
                >
                    <div>
                        <p class="text-muted-foreground">System Qty</p>
                        <p>{{ stockOpname.total_system_qty }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Physical Qty</p>
                        <p>{{ stockOpname.total_physical_qty }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Variance Qty</p>
                        <p
                            :class="
                                stockOpname.total_variance_qty !== 0
                                    ? 'text-destructive'
                                    : ''
                            "
                        >
                            {{ stockOpname.total_variance_qty > 0 ? "+" : ""
                            }}{{ stockOpname.total_variance_qty }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Variance Value</p>
                        <p>{{ stockOpname.total_variance_value }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Items</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>SKU</TableHead>
                                <TableHead>Product</TableHead>
                                <TableHead>System Qty</TableHead>
                                <TableHead>Physical Qty</TableHead>
                                <TableHead>Variance</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Scanned By</TableHead>
                                <TableHead v-if="canCount" class="text-right">
                                    Count
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="item in stockOpname.items"
                                :key="item.id"
                            >
                                <TableCell class="font-mono text-sm">
                                    {{ item.product_sku_snapshot }}
                                </TableCell>
                                <TableCell>
                                    {{ item.product_name_snapshot }}
                                </TableCell>
                                <TableCell>{{ item.system_qty }}</TableCell>
                                <TableCell>
                                    {{ item.physical_qty ?? "—" }}
                                </TableCell>
                                <TableCell>
                                    <span
                                        v-if="variance(item) !== null"
                                        :class="
                                            variance(item) !== 0
                                                ? 'text-destructive'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ variance(item)! > 0 ? "+" : ""
                                        }}{{ variance(item) }}
                                    </span>
                                    <span v-else class="text-muted-foreground">
                                        —
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="
                                            itemStatusVariant(item.status)
                                        "
                                    >
                                        {{ item.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    {{ item.scanned_by?.name ?? "—" }}
                                </TableCell>
                                <TableCell v-if="canCount" class="text-right">
                                    <form
                                        class="flex justify-end gap-2"
                                        @submit.prevent="recordCount(item.id)"
                                    >
                                        <Input
                                            v-model.number="
                                                countForms[item.id].physical_qty
                                            "
                                            type="number"
                                            min="0"
                                            class="w-24"
                                        />
                                        <Button
                                            type="submit"
                                            size="sm"
                                            :disabled="
                                                countForms[item.id].processing
                                            "
                                        >
                                            Record
                                        </Button>
                                    </form>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Link href="/stock-opnames">
                <Button variant="outline">Back to list</Button>
            </Link>
        </div>

        <AlertDialog v-model:open="startDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Start counting?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This opens the opname for scanning — no stock changes
                        yet, this just moves it from Draft to In Progress.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction :disabled="processing" @click="start">
                        Start Counting
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="completeDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Complete this opname?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This locks in the counts and rolls up the variance
                        totals — still no stock changes yet, that only happens
                        on final Approval.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction :disabled="processing" @click="complete">
                        Complete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="approveDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Approve this opname?</AlertDialogTitle>
                    <AlertDialogDescription>
                        For every line with a variance, a Stock Adjustment will
                        be created and applied automatically — surplus lines add
                        stock, shortage lines remove it. This is the step that
                        actually changes real inventory. Can't be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction :disabled="processing" @click="approve">
                        Approve
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="rejectDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Send back for recount?</AlertDialogTitle>
                    <AlertDialogDescription>
                        No stock has changed yet — this just reopens the opname
                        to In Progress so the counts can be corrected.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Keep it</AlertDialogCancel>
                    <AlertDialogAction :disabled="processing" @click="reject">
                        Send Back
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
