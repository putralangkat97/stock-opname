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
    WarehouseTransfer,
    WarehouseTransferStatusValue,
} from "@/types/models";

const props = defineProps<{
    warehouseTransfer: WarehouseTransfer;
}>();

const breadcrumbs = [
    { label: "Warehouse Transfers", href: "/warehouse-transfers" },
    { label: props.warehouseTransfer.transfer_number },
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
    return "secondary";
}

const markInTransitDialogOpen = ref(false);
const completeDialogOpen = ref(false);
const rejectDialogOpen = ref(false);
const processing = ref(false);

function markInTransit() {
    processing.value = true;
    router.post(
        `/warehouse-transfers/${props.warehouseTransfer.id}/mark-in-transit`,
        {},
        {
            onFinish: () => {
                processing.value = false;
                markInTransitDialogOpen.value = false;
            },
        },
    );
}

function complete() {
    processing.value = true;
    router.post(
        `/warehouse-transfers/${props.warehouseTransfer.id}/complete`,
        {},
        {
            onFinish: () => {
                processing.value = false;
                completeDialogOpen.value = false;
            },
        },
    );
}

function reject() {
    processing.value = true;
    router.post(
        `/warehouse-transfers/${props.warehouseTransfer.id}/reject`,
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
                        {{ warehouseTransfer.transfer_number }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ warehouseTransfer.fromWarehouse.name }} →
                        {{ warehouseTransfer.toWarehouse.name }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge
                        :variant="statusVariant(warehouseTransfer.status)"
                        class="text-sm"
                    >
                        {{ warehouseTransfer.status }}
                    </Badge>

                    <!-- Pending: can mark In Transit (deducts from source) or Reject -->
                    <template v-if="warehouseTransfer.status === 'Pending'">
                        <Button
                            variant="outline"
                            @click="rejectDialogOpen = true"
                        >
                            Reject
                        </Button>
                        <Button @click="markInTransitDialogOpen = true">
                            Mark In Transit
                        </Button>
                    </template>

                    <!-- In Transit: can Complete (adds to destination) or Reject (reverses source) -->
                    <template
                        v-else-if="warehouseTransfer.status === 'In Transit'"
                    >
                        <Button
                            variant="outline"
                            @click="rejectDialogOpen = true"
                        >
                            Reject
                        </Button>
                        <Button @click="completeDialogOpen = true">
                            Complete
                        </Button>
                    </template>
                </div>
            </div>

            <!-- Simple stage indicator -->
            <div class="flex items-center gap-2 text-sm">
                <span
                    class="rounded-full px-3 py-1"
                    :class="
                        warehouseTransfer.status !== 'Rejected'
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground'
                    "
                >
                    1. Pending
                </span>
                <span class="text-muted-foreground">→</span>
                <span
                    class="rounded-full px-3 py-1"
                    :class="
                        ['In Transit', 'Completed'].includes(
                            warehouseTransfer.status,
                        )
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground'
                    "
                >
                    2. In Transit
                </span>
                <span class="text-muted-foreground">→</span>
                <span
                    class="rounded-full px-3 py-1"
                    :class="
                        warehouseTransfer.status === 'Completed'
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground'
                    "
                >
                    3. Completed
                </span>
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
                        <p>{{ warehouseTransfer.date }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Transferred By</p>
                        <p>{{ warehouseTransfer.transferredBy.name }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Received By</p>
                        <p>{{ warehouseTransfer.receivedBy?.name ?? "—" }}</p>
                    </div>
                    <div v-if="warehouseTransfer.notes" class="col-span-full">
                        <p class="text-muted-foreground">Notes</p>
                        <p>{{ warehouseTransfer.notes }}</p>
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
                                v-for="item in warehouseTransfer.items"
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

            <Link href="/warehouse-transfers">
                <Button variant="outline">Back to list</Button>
            </Link>
        </div>

        <AlertDialog v-model:open="markInTransitDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Mark this transfer In Transit?
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Stock will be deducted from
                        {{ warehouseTransfer.fromWarehouse.name }} now — it
                        won't be added to the destination until you Complete the
                        transfer.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        :disabled="processing"
                        @click="markInTransit"
                    >
                        Mark In Transit
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="completeDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Complete this transfer?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Stock will be added to
                        {{ warehouseTransfer.toWarehouse.name }} for every line
                        item, creating a new product row there if this is the
                        first time these SKUs have stock at that warehouse.
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

        <AlertDialog v-model:open="rejectDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Reject this transfer?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <template
                            v-if="warehouseTransfer.status === 'In Transit'"
                        >
                            Stock already deducted from
                            {{ warehouseTransfer.fromWarehouse.name }}
                            will be restored — nothing is lost.
                        </template>
                        <template v-else>
                            No stock has moved yet, so nothing to reverse.
                        </template>
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
