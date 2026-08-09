<script setup lang="ts">
import { ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
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
import type { PaginatedData, Supplier } from "@/types/models";

defineProps<{
    suppliers: PaginatedData<Supplier>;
}>();

const breadcrumbs = [{ label: "Suppliers", href: "/suppliers" }];

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
const editingSupplier = ref<Supplier | null>(null);

const form = useForm({
    code: "",
    name: "",
    contact_person: "",
    email: "",
    phone: "",
    address: "",
    city: "",
});

function openCreateDialog() {
    editingSupplier.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEditDialog(supplier: Supplier) {
    editingSupplier.value = supplier;
    form.clearErrors();
    form.code = supplier.code;
    form.name = supplier.name;
    form.contact_person = supplier.contact_person ?? "";
    form.email = supplier.email ?? "";
    form.phone = supplier.phone ?? "";
    form.address = supplier.address ?? "";
    form.city = supplier.city ?? "";
    dialogOpen.value = true;
}

function submit() {
    if (editingSupplier.value) {
        form.put(`/suppliers/${editingSupplier.value.id}`, {
            onSuccess: () => (dialogOpen.value = false),
        });
    } else {
        form.post("/suppliers", {
            onSuccess: () => (dialogOpen.value = false),
        });
    }
}

const deletingSupplier = ref<Supplier | null>(null);

function confirmDelete() {
    if (!deletingSupplier.value) return;
    router.delete(`/suppliers/${deletingSupplier.value.id}`, {
        onSuccess: () => (deletingSupplier.value = null),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Suppliers</h1>
                <Button @click="openCreateDialog">Add Supplier</Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Contact Person</TableHead>
                        <TableHead>Phone</TableHead>
                        <TableHead>City</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="suppliers.data.length === 0" :colspan="6">
                        No suppliers yet.
                    </TableEmpty>
                    <TableRow v-for="supplier in suppliers.data" :key="supplier.id">
                        <TableCell class="font-mono text-sm">{{ supplier.code }}</TableCell>
                        <TableCell>{{ supplier.name }}</TableCell>
                        <TableCell>{{ supplier.contact_person ?? "—" }}</TableCell>
                        <TableCell>{{ supplier.phone ?? "—" }}</TableCell>
                        <TableCell>{{ supplier.city ?? "—" }}</TableCell>
                        <TableCell class="flex justify-end gap-2 text-right">
                            <Button variant="outline" size="sm" @click="openEditDialog(supplier)">
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deletingSupplier = supplier"
                            >
                                Delete
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <div class="flex items-center justify-between text-sm text-muted-foreground">
                <span>Page {{ suppliers.current_page }} of {{ suppliers.last_page }}</span>
                <div class="flex gap-2">
                    <Button
                        v-for="link in suppliers.links"
                        :key="link.label"
                        variant="outline"
                        size="sm"
                        :disabled="!link.url"
                        :class="{ 'bg-muted': link.active }"
                        v-html="link.label"
                        @click="link.url && router.get(link.url, {}, { preserveState: true })"
                    />
                </div>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingSupplier ? "Edit Supplier" : "Add Supplier" }}</DialogTitle>
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
                            <FieldLabel>Contact Person</FieldLabel>
                            <Input v-model="form.contact_person" />
                            <FieldError :errors="[form.errors.contact_person]" />
                        </Field>
                        <Field>
                            <FieldLabel>Email</FieldLabel>
                            <Input v-model="form.email" type="email" />
                            <FieldError :errors="[form.errors.email]" />
                        </Field>
                        <Field>
                            <FieldLabel>Phone</FieldLabel>
                            <Input v-model="form.phone" />
                            <FieldError :errors="[form.errors.phone]" />
                        </Field>
                        <Field>
                            <FieldLabel>City</FieldLabel>
                            <Input v-model="form.city" />
                            <FieldError :errors="[form.errors.city]" />
                        </Field>
                    </div>
                    <Field>
                        <FieldLabel>Address</FieldLabel>
                        <Input v-model="form.address" />
                        <FieldError :errors="[form.errors.address]" />
                    </Field>

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">
                            {{ editingSupplier ? "Save Changes" : "Create Supplier" }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog :open="!!deletingSupplier" @update:open="(v) => !v && (deletingSupplier = null)">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this supplier?</AlertDialogTitle>
                    <AlertDialogDescription>
                        "{{ deletingSupplier?.name }}" will be removed. This can't be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deletingSupplier = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="confirmDelete">Delete</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
