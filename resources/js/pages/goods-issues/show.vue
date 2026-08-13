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
import type { GoodsIssue, GoodsIssueStatusValue } from "@/types/models";

const props = defineProps<{
    goodsIssue: GoodsIssue;
}>();

const breadcrumbs = [
    { label: "Goods Issues", href: "/goods-issues" },
    { label: props.goodsIssue.issue_number },
];

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        // Insufficient-stock rejections from approve() land here as a flash
        // error now (see GoodsIssueController::approve()'s try/catch).
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true },
);

function statusVariant(status: GoodsIssueStatusValue) {
    if (status === "Cancelled") return "destructive";
    if (status === "Issued") return "default";
    return "secondary";
}

const approveDialogOpen = ref(false);
const cancelDialogOpen = ref(false);
const processing = ref(false);

function approve() {
    processing.value = true;
    router.post(
        `/goods-issues/${props.goodsIssue.id}/approve`,
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
        `/goods-issues/${props.goodsIssue.id}/cancel`,
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
                        {{ goodsIssue.issue_number }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ goodsIssue.warehouse.name }} →
                        {{ goodsIssue.customer.name }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge
                        :variant="statusVariant(goodsIssue.status)"
                        class="text-sm"
                    >
                        {{ goodsIssue.status }}
                    </Badge>
                    <template v-if="goodsIssue.status === 'Draft'">
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
                        <p class="text-muted-foreground">SO Number</p>
                        <p>{{ goodsIssue.so_number ?? "—" }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Date</p>
                        <p>{{ goodsIssue.date }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Issued By</p>
                        <p>{{ goodsIssue.issued_by.name }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Total</p>
                        <p>{{ goodsIssue.total_amount }}</p>
                    </div>
                    <div v-if="goodsIssue.notes" class="col-span-full">
                        <p class="text-muted-foreground">Notes</p>
                        <p>{{ goodsIssue.notes }}</p>
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
                                v-for="item in goodsIssue.items"
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
                                    {{ goodsIssue.total_amount }}
                                </TableCell>
                            </TableRow>
                        </TableFooter>
                    </Table>
                </CardContent>
            </Card>

            <Link href="/goods-issues">
                <Button variant="outline">Back to list</Button>
            </Link>
        </div>

        <AlertDialog v-model:open="approveDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Approve this issue?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Stock will be deducted from
                        {{ goodsIssue.warehouse.name }} for every line item. If
                        any line exceeds available stock, the whole approval
                        will be rejected — nothing partial. This can't be
                        undone.
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
                    <AlertDialogTitle>Cancel this issue?</AlertDialogTitle>
                    <AlertDialogDescription>
                        No stock has been deducted yet, so nothing to reverse —
                        this just marks the issue as Cancelled.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Keep it</AlertDialogCancel>
                    <AlertDialogAction :disabled="processing" @click="cancel">
                        Cancel Issue
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
