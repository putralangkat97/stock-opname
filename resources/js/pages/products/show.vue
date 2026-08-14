<script setup lang="ts">
import { watch } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AppLayout from "@/layouts/app-layout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { Product } from "@/types/models";

const props = defineProps<{
    product: Product;
}>();

const breadcrumbs = [
    { label: "Products", href: "/products" },
    { label: props.product.name },
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

function statusVariant(status: Product["status"]) {
    if (status === "Out of Stock") return "destructive";
    if (status === "Low Stock") return "secondary";
    return "default";
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">{{ product.name }}</h1>
                    <p class="font-mono text-sm text-muted-foreground">
                        {{ product.sku }}
                    </p>
                </div>
                <Badge :variant="statusVariant(product.status)" class="text-sm">
                    {{ product.status }}
                </Badge>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Classification</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4"
                >
                    <div>
                        <p class="text-muted-foreground">Category</p>
                        <p>{{ product.category.name }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Brand</p>
                        <p>{{ product.brand.name }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Unit</p>
                        <p>
                            {{ product.unit.name }} ({{ product.unit.symbol }})
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Barcode</p>
                        <p class="font-mono">{{ product.barcode ?? "—" }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Location</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4"
                >
                    <div>
                        <p class="text-muted-foreground">Warehouse</p>
                        <p>{{ product.warehouse.name }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Bin Location</p>
                        <p>{{ product.bin_location?.code ?? "Unassigned" }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Stock</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4"
                >
                    <div>
                        <p class="text-muted-foreground">Current Stock</p>
                        <p class="text-base font-medium">
                            {{ product.stock }} {{ product.unit.symbol }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Min Stock</p>
                        <p>{{ product.min_stock }} {{ product.unit.symbol }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Max Stock</p>
                        <p>
                            {{
                                product.max_stock !== null
                                    ? `${product.max_stock} ${product.unit.symbol}`
                                    : "—"
                            }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Pricing</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4"
                >
                    <div>
                        <p class="text-muted-foreground">Cost Price</p>
                        <p>{{ product.cost_price }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Selling Price</p>
                        <p>{{ product.selling_price }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Editing happens via the dialog on the index page, not here —
                 keeps the edit form in one place rather than duplicating it
                 across index and show. -->
            <Link href="/products">
                <Button variant="outline">Back to list</Button>
            </Link>
        </div>
    </AppLayout>
</template>
