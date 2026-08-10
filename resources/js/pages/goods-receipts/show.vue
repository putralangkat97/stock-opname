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
    TableFooter,
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
import type { GoodsReceipt, GoodsReceiptStatusValue } from "@/types/models";

const props = defineProps<{
    goodsReceipt: GoodsReceipt;
}>();

const breadcrumbs = [
    { label: "Goods Receipts", href: "/goods-receipts" },
    { label: props.goodsReceipt.receipt_number },
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

function statusVariant(status: GoodsReceiptStatusValue) {
    if (status === "Cancelled") return "destructive";
    if (status === "Received") return "default";
    return "secondary";
}

const approveDialogOpen = ref(false);
const cancelDialogOpen = ref(false);
const processing = ref(false);

function approve() {
    processing.value = true;
    router.post(
        `/goods-receipts/${props.goodsReceipt.id}/approve`,
        {},
        {
            onFinish: () => {
                processing.value = false;
                approveDialogOpen.value = false;
            },
        },
    );
}

function cancel() {
    processing.value = true;
    router.post(
        `/goods-receipts/${props.goodsReceipt.id}/cancel`,
        {},
        {
            onFinish: () => {
                processing.value = false;
                cancelDialogOpen.value = false;
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
                        {{ goodsReceipt.receipt_number }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ goodsReceipt.supplier.name }} →
                        {{ goodsReceipt.warehouse.name }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge
                        :variant="statusVariant(goodsReceipt.status)"
                        class="text-sm"
                    >
                        {{ goodsReceipt.status }}
                    </Badge>
                    <template v-if="goodsReceipt.status === 'Draft'">
                        <Button
                            variant="outline"
                            @click="cancelDialogOpen = true"
                        >
                            Cancel
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
                        <p class="text-muted-foreground">PO Number</p>
                        <p>{{ goodsReceipt.po_number ?? "—" }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Date</p>
                        <p>{{ goodsReceipt.date }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Received By</p>
                        <!-- NOTE: relation name is receivedBy (camelCase), matching
                             the GoodsReceipt::receivedBy() method — Eloquent relations
                             keep their PHP method-name casing in JSON, unlike DB columns. -->
                        <p>{{ goodsReceipt.receivedBy.name }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Total</p>
                        <p>{{ goodsReceipt.total_amount }}</p>
                    </div>
                    <div v-if="goodsReceipt.notes" class="col-span-full">
                        <p class="text-muted-foreground">Notes</p>
                        <p>{{ goodsReceipt.notes }}</p>
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
                                <TableHead>Unit Price</TableHead>
                                <TableHead>Subtotal</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="item in goodsReceipt.items"
                                :key="item.id"
                            >
                                <TableCell class="font-mono text-sm">
                                    {{ item.product_sku_snapshot }}
                                </TableCell>
                                <TableCell>
                                    {{ item.product_name_snapshot }}
                                </TableCell>
                                <TableCell>{{ item.qty }}</TableCell>
                                <TableCell>{{ item.unit_price }}</TableCell>
                                <TableCell>{{ item.subtotal }}</TableCell>
                            </TableRow>
                        </TableBody>
                        <TableFooter>
                            <TableRow>
                                <TableCell
                                    colspan="4"
                                    class="text-right font-medium"
                                >
                                    Total
                                </TableCell>
                                <TableCell class="font-medium">
                                    {{ goodsReceipt.total_amount }}
                                </TableCell>
                            </TableRow>
                        </TableFooter>
                    </Table>
                </CardContent>
            </Card>

            <Link href="/goods-receipts">
                <Button variant="outline">Back to list</Button>
            </Link>
        </div>

        <AlertDialog v-model:open="approveDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Approve this receipt?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Stock will be added to
                        {{ goodsReceipt.warehouse.name }} for every line item.
                        This can't be undone.
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

        <AlertDialog v-model:open="cancelDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Cancel this receipt?</AlertDialogTitle>
                    <AlertDialogDescription>
                        No stock has been added yet, so nothing to reverse —
                        this just marks the receipt as Cancelled.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Keep it</AlertDialogCancel>
                    <AlertDialogAction :disabled="processing" @click="cancel">
                        Cancel Receipt
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
