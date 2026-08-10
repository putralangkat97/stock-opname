<script setup lang="ts">
import { ref, watch } from "vue";
import { router, useForm, usePage, Link } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AppLayout from "@/layouts/app-layout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
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
import type { PaginatedData, Warehouse } from "@/types/models";

defineProps<{
    warehouses: PaginatedData<Warehouse>;
}>();

const breadcrumbs = [{ label: "Warehouses", href: "/warehouses" }];

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true },
);

const dialogOpen = ref(false);
const editingWarehouse = ref<Warehouse | null>(null);

const form = useForm({
    code: "",
    name: "",
    location: "",
    manager: "",
    phone: "",
    total_capacity: 0,
});

function openCreateDialog() {
    editingWarehouse.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEditDialog(warehouse: Warehouse) {
    editingWarehouse.value = warehouse;
    form.clearErrors();
    form.code = warehouse.code;
    form.name = warehouse.name;
    form.location = warehouse.location ?? "";
    form.manager = warehouse.manager ?? "";
    form.phone = warehouse.phone ?? "";
    form.total_capacity = warehouse.total_capacity;
    dialogOpen.value = true;
}

function submit() {
    if (editingWarehouse.value) {
        form.put(`/warehouses/${editingWarehouse.value.id}`, {
            onSuccess: () => (dialogOpen.value = false),
        });
    } else {
        form.post("/warehouses", {
            onSuccess: () => (dialogOpen.value = false),
        });
    }
}

const deletingWarehouse = ref<Warehouse | null>(null);

function confirmDelete() {
    if (!deletingWarehouse.value) return;
    router.delete(`/warehouses/${deletingWarehouse.value.id}`, {
        onSuccess: () => (deletingWarehouse.value = null),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Warehouses</h1>
                <Button @click="openCreateDialog">Add Warehouse</Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Location</TableHead>
                        <TableHead>Manager</TableHead>
                        <TableHead>Capacity</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="warehouses.data.length === 0"
                        :colspan="6"
                    >
                        No warehouses yet.
                    </TableEmpty>
                    <TableRow
                        v-for="warehouse in warehouses.data"
                        :key="warehouse.id"
                    >
                        <TableCell class="font-mono text-sm">
                            {{ warehouse.code }}
                        </TableCell>
                        <TableCell>{{ warehouse.name }}</TableCell>
                        <TableCell>{{ warehouse.location ?? "—" }}</TableCell>
                        <TableCell>{{ warehouse.manager ?? "—" }}</TableCell>
                        <TableCell>{{ warehouse.total_capacity }}</TableCell>
                        <TableCell class="flex justify-end gap-2 text-right">
                            <Link :href="`/warehouses/${warehouse.id}`">
                                <Button variant="outline" size="sm">
                                    Manage Racks
                                </Button>
                            </Link>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openEditDialog(warehouse)"
                            >
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deletingWarehouse = warehouse"
                            >
                                Delete
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <div
                class="flex items-center justify-between text-sm text-muted-foreground"
            >
                <span>
                    Page {{ warehouses.current_page }} of
                    {{ warehouses.last_page }}
                </span>
                <div class="flex gap-2">
                    <Button
                        v-for="link in warehouses.links"
                        :key="link.label"
                        variant="outline"
                        size="sm"
                        :disabled="!link.url"
                        :class="{ 'bg-muted': link.active }"
                        v-html="link.label"
                        @click="
                            link.url &&
                            router.get(link.url, {}, { preserveState: true })
                        "
                    />
                </div>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            editingWarehouse
                                ? "Edit Warehouse"
                                : "Add Warehouse"
                        }}
                    </DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <Field>
                            <FieldLabel>Code</FieldLabel>
                            <Input v-model="form.code" />
                            <FieldError :errors="[form.errors.code]" />
                        </Field>
                        <Field>
                            <FieldLabel>Name</FieldLabel>
                            <Input v-model="form.name" />
                            <FieldError :errors="[form.errors.name]" />
                        </Field>
                        <Field>
                            <FieldLabel>Location</FieldLabel>
                            <Input v-model="form.location" />
                            <FieldError :errors="[form.errors.location]" />
                        </Field>
                        <Field>
                            <FieldLabel>Manager</FieldLabel>
                            <Input v-model="form.manager" />
                            <FieldError :errors="[form.errors.manager]" />
                        </Field>
                        <Field>
                            <FieldLabel>Phone</FieldLabel>
                            <Input v-model="form.phone" />
                            <FieldError :errors="[form.errors.phone]" />
                        </Field>
                        <Field>
                            <FieldLabel>Total Capacity</FieldLabel>
                            <Input
                                v-model.number="form.total_capacity"
                                type="number"
                                min="0"
                            />
                            <FieldError
                                :errors="[form.errors.total_capacity]"
                            />
                        </Field>
                    </div>

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            {{
                                editingWarehouse
                                    ? "Save Changes"
                                    : "Create Warehouse"
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog
            :open="!!deletingWarehouse"
            @update:open="(v) => !v && (deletingWarehouse = null)"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this warehouse?</AlertDialogTitle>
                    <AlertDialogDescription>
                        "{{ deletingWarehouse?.name }}" will be removed. This
                        can't be undone, and will fail if any racks, bins, or
                        products still reference it.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deletingWarehouse = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction @click="confirmDelete">
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
