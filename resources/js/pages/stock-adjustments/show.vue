<script setup lang="ts">
import { ref, watch } from "vue";
import { router, usePage, Link } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AppLayout from "@/layouts/app-layout.vue";
import { Button } from "@/components/ui/button";
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
    StockAdjustment,
    StockAdjustmentStatusValue,
} from "@/types/models";

const props = defineProps<{
    stockAdjustment: StockAdjustment;
}>();

const breadcrumbs = [
    { label: "Stock Adjustments", href: "/stock-adjustments" },
    { label: props.stockAdjustment.adjustment_number },
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
    return "secondary";
}

const approveDialogOpen = ref(false);
const rejectDialogOpen = ref(false);
const processing = ref(false);

function approve() {
    processing.value = true;
    router.post(
        `/stock-adjustments/${props.stockAdjustment.id}/approve`,
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
        `/stock-adjustments/${props.stockAdjustment.id}/reject`,
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
                        {{ stockAdjustment.adjustment_number }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ stockAdjustment.warehouse.name }} —
                        {{ stockAdjustment.reason }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge
                        :variant="
                            stockAdjustment.type === 'IN'
                                ? 'default'
                                : 'secondary'
                        "
                    >
                        {{ stockAdjustment.type }}
                    </Badge>
                    <Badge
                        :variant="statusVariant(stockAdjustment.status)"
                        class="text-sm"
                    >
                        {{ stockAdjustment.status }}
                    </Badge>
                    <template v-if="stockAdjustment.status === 'Pending'">
                        <Button
                            variant="outline"
                            @click="rejectDialogOpen = true"
                        >
                            Reject
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
                        <p class="text-muted-foreground">Date</p>
                        <p>{{ stockAdjustment.date }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Adjusted By</p>
                        <p>{{ stockAdjustment.adjustedBy.name }}</p>
                    </div>
                    <div v-if="stockAdjustment.notes" class="col-span-full">
                        <p class="text-muted-foreground">Notes</p>
                        <p>{{ stockAdjustment.notes }}</p>
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
                                <TableHead>Qty</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="item in stockAdjustment.items"
                                :key="item.id"
                            >
                                <TableCell class="font-mono text-sm">
                                    {{ item.product_sku_snapshot }}
                                </TableCell>
                                <TableCell>
                                    {{ item.product_name_snapshot }}
                                </TableCell>
                                <TableCell>{{ item.qty }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Link href="/stock-adjustments">
                <Button variant="outline">Back to list</Button>
            </Link>
        </div>

        <AlertDialog v-model:open="approveDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Approve this adjustment?
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Stock will be
                        {{
                            stockAdjustment.type === "IN"
                                ? "added to"
                                : "removed from"
                        }}
                        {{ stockAdjustment.warehouse.name }} for every line
                        item. This can't be undone.
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
                    <AlertDialogTitle>Reject this adjustment?</AlertDialogTitle>
                    <AlertDialogDescription>
                        No stock has been changed yet, so nothing to reverse —
                        this just marks the adjustment as Rejected.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Keep it</AlertDialogCancel>
                    <AlertDialogAction :disabled="processing" @click="reject">
                        Reject
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
