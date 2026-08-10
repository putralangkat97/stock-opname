<script setup lang="ts">
import { ref, watch } from "vue";
import { router, useForm, usePage, Link } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AppLayout from "@/layouts/app-layout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
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
import { Field, FieldLabel, FieldError } from "@/components/ui/field";
import type { WarehouseWithRacks, Rack, BinLocation } from "@/types/models";

const props = defineProps<{
    warehouse: WarehouseWithRacks;
}>();

const breadcrumbs = [
    { label: "Warehouses", href: "/warehouses" },
    { label: props.warehouse.name },
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

// --- Rack create/edit dialog ---
const rackDialogOpen = ref(false);
const editingRack = ref<Rack | null>(null);

const rackForm = useForm({
    code: "",
    zone: "",
});

function openCreateRackDialog() {
    editingRack.value = null;
    rackForm.reset();
    rackForm.clearErrors();
    rackDialogOpen.value = true;
}

function openEditRackDialog(rack: Rack) {
    editingRack.value = rack;
    rackForm.clearErrors();
    rackForm.code = rack.code;
    rackForm.zone = rack.zone ?? "";
    rackDialogOpen.value = true;
}

function submitRack() {
    if (editingRack.value) {
        rackForm.put(`/racks/${editingRack.value.id}`, {
            onSuccess: () => (rackDialogOpen.value = false),
        });
    } else {
        rackForm.post(`/warehouses/${props.warehouse.id}/racks`, {
            onSuccess: () => (rackDialogOpen.value = false),
        });
    }
}

const deletingRack = ref<Rack | null>(null);

function confirmDeleteRack() {
    if (!deletingRack.value) return;
    router.delete(`/racks/${deletingRack.value.id}`, {
        onSuccess: () => (deletingRack.value = null),
    });
}

// --- Bin create/edit dialog ---
const binDialogOpen = ref(false);
const editingBin = ref<BinLocation | null>(null);
const binTargetRack = ref<Rack | null>(null);

const binForm = useForm({
    code: "",
    capacity: 0,
});

function openCreateBinDialog(rack: Rack) {
    binTargetRack.value = rack;
    editingBin.value = null;
    binForm.reset();
    binForm.clearErrors();
    binDialogOpen.value = true;
}

function openEditBinDialog(rack: Rack, bin: BinLocation) {
    binTargetRack.value = rack;
    editingBin.value = bin;
    binForm.clearErrors();
    binForm.code = bin.code;
    binForm.capacity = bin.capacity ?? 0;
    binDialogOpen.value = true;
}

function submitBin() {
    if (!binTargetRack.value) return;

    if (editingBin.value) {
        binForm.put(`/bin-locations/${editingBin.value.id}`, {
            onSuccess: () => (binDialogOpen.value = false),
        });
    } else {
        binForm.post(`/racks/${binTargetRack.value.id}/bin-locations`, {
            onSuccess: () => (binDialogOpen.value = false),
        });
    }
}

const deletingBin = ref<BinLocation | null>(null);

function confirmDeleteBin() {
    if (!deletingBin.value) return;
    router.delete(`/bin-locations/${deletingBin.value.id}`, {
        onSuccess: () => (deletingBin.value = null),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">{{ warehouse.name }}</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ warehouse.code }} —
                        {{ warehouse.location ?? "No location set" }}
                    </p>
                </div>
                <Link href="/warehouses">
                    <Button variant="outline">Back to list</Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                </CardHeader>
                <CardContent
                    class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4"
                >
                    <div>
                        <p class="text-muted-foreground">Manager</p>
                        <p>{{ warehouse.manager ?? "—" }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Phone</p>
                        <p>{{ warehouse.phone ?? "—" }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Total Capacity</p>
                        <p>{{ warehouse.total_capacity }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Racks & Bins management -->
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Racks & Bin Locations</h2>
                <Button size="sm" @click="openCreateRackDialog">
                    Add Rack
                </Button>
            </div>

            <p
                v-if="warehouse.racks.length === 0"
                class="text-sm text-muted-foreground"
            >
                No racks yet — add one to start assigning bin locations.
            </p>

            <Card v-for="rack in warehouse.racks" :key="rack.id">
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-base">{{ rack.code }}</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            {{ rack.zone ?? "No zone" }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="openCreateBinDialog(rack)"
                        >
                            Add Bin
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="openEditRackDialog(rack)"
                        >
                            Edit
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="deletingRack = rack"
                        >
                            Delete
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!rack.binLocations?.length"
                        class="text-sm text-muted-foreground"
                    >
                        No bins in this rack yet.
                    </p>
                    <div v-else class="flex flex-wrap gap-2">
                        <div
                            v-for="bin in rack.binLocations"
                            :key="bin.id"
                            class="flex items-center gap-2 rounded-md border px-3 py-1.5"
                        >
                            <Badge variant="secondary">{{ bin.code }}</Badge>
                            <span class="text-xs text-muted-foreground">
                                cap. {{ bin.capacity }}
                            </span>
                            <button
                                type="button"
                                class="text-xs text-muted-foreground hover:text-foreground"
                                @click="openEditBinDialog(rack, bin)"
                            >
                                edit
                            </button>
                            <button
                                type="button"
                                class="text-xs text-destructive hover:underline"
                                @click="deletingBin = bin"
                            >
                                delete
                            </button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Rack dialog -->
        <Dialog v-model:open="rackDialogOpen">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingRack ? "Edit Rack" : "Add Rack" }}
                    </DialogTitle>
                </DialogHeader>
                <form class="flex flex-col gap-4" @submit.prevent="submitRack">
                    <Field>
                        <FieldLabel>Code</FieldLabel>
                        <Input
                            v-model="rackForm.code"
                            placeholder="e.g. RACK-A1"
                        />
                        <FieldError :errors="[rackForm.errors.code]" />
                    </Field>
                    <Field>
                        <FieldLabel>Zone (optional)</FieldLabel>
                        <Input
                            v-model="rackForm.zone"
                            placeholder="e.g. Zone A"
                        />
                        <FieldError :errors="[rackForm.errors.zone]" />
                    </Field>
                    <DialogFooter>
                        <Button type="submit" :disabled="rackForm.processing">
                            {{ editingRack ? "Save Changes" : "Create Rack" }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Bin dialog -->
        <Dialog v-model:open="binDialogOpen">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            editingBin
                                ? "Edit Bin"
                                : `Add Bin to ${binTargetRack?.code}`
                        }}
                    </DialogTitle>
                </DialogHeader>
                <form class="flex flex-col gap-4" @submit.prevent="submitBin">
                    <Field>
                        <FieldLabel>Code</FieldLabel>
                        <Input
                            v-model="binForm.code"
                            placeholder="e.g. BIN-A1-1"
                        />
                        <FieldError :errors="[binForm.errors.code]" />
                    </Field>
                    <Field>
                        <FieldLabel>Capacity</FieldLabel>
                        <Input
                            v-model.number="binForm.capacity"
                            type="number"
                            min="0"
                        />
                        <FieldError :errors="[binForm.errors.capacity]" />
                    </Field>
                    <DialogFooter>
                        <Button type="submit" :disabled="binForm.processing">
                            {{ editingBin ? "Save Changes" : "Create Bin" }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete rack confirmation -->
        <AlertDialog
            :open="!!deletingRack"
            @update:open="(v) => !v && (deletingRack = null)"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this rack?</AlertDialogTitle>
                    <AlertDialogDescription>
                        "{{ deletingRack?.code }}" and all its bin locations
                        will be removed. This will fail if any product is still
                        assigned to a bin in this rack.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deletingRack = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction @click="confirmDeleteRack">
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Delete bin confirmation -->
        <AlertDialog
            :open="!!deletingBin"
            @update:open="(v) => !v && (deletingBin = null)"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this bin?</AlertDialogTitle>
                    <AlertDialogDescription>
                        "{{ deletingBin?.code }}" will be removed. This will
                        fail if any product is still assigned to it.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deletingBin = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction @click="confirmDeleteBin">
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
