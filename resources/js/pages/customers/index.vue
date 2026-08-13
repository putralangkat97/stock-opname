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
    DialogDescription,
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
import type { PaginatedData, Customer } from "@/types/models";
import { LoaderIcon } from "@lucide/vue";

defineProps<{
    customers: PaginatedData<Customer>;
}>();

const breadcrumbs = [{ label: "Customers", href: "/customers" }];

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
const editingCustomer = ref<Customer | null>(null);

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
    editingCustomer.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEditDialog(customer: Customer) {
    editingCustomer.value = customer;
    form.clearErrors();
    form.code = customer.code;
    form.name = customer.name;
    form.contact_person = customer.contact_person ?? "";
    form.email = customer.email ?? "";
    form.phone = customer.phone ?? "";
    form.address = customer.address ?? "";
    form.city = customer.city ?? "";
    dialogOpen.value = true;
}

function submit() {
    if (editingCustomer.value) {
        form.put(`/customers/${editingCustomer.value.id}`, {
            onSuccess: () => (dialogOpen.value = false),
        });
    } else {
        form.post("/customers", {
            onSuccess: () => (dialogOpen.value = false),
        });
    }
}

const deletingCustomer = ref<Customer | null>(null);

function confirmDelete() {
    if (!deletingCustomer.value) return;
    router.delete(`/customers/${deletingCustomer.value.id}`, {
        onSuccess: () => (deletingCustomer.value = null),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Customers</h1>
                <Button @click="openCreateDialog">Add Customer</Button>
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
                    <TableEmpty v-if="customers.data.length === 0" :colspan="6">
                        No customers yet.
                    </TableEmpty>
                    <TableRow
                        v-for="customer in customers.data"
                        :key="customer.id"
                    >
                        <TableCell class="font-mono text-sm">{{
                            customer.code
                        }}</TableCell>
                        <TableCell>{{ customer.name }}</TableCell>
                        <TableCell>{{
                            customer.contact_person ?? "—"
                        }}</TableCell>
                        <TableCell>{{ customer.phone ?? "—" }}</TableCell>
                        <TableCell>{{ customer.city ?? "—" }}</TableCell>
                        <TableCell class="flex justify-end gap-2 text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openEditDialog(customer)"
                            >
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deletingCustomer = customer"
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
                <span
                    >Page {{ customers.current_page }} of
                    {{ customers.last_page }}</span
                >
                <div class="flex gap-2">
                    <Button
                        v-for="link in customers.links"
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
                        {{ editingCustomer ? "Edit Customer" : "Add Customer" }}
                    </DialogTitle>
                    <DialogDescription>Form customer</DialogDescription>
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
                            <FieldError
                                :errors="[form.errors.contact_person]"
                            />
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
                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            <LoaderIcon
                                v-if="form.processing"
                                class="animate-spin"
                            />
                            <template v-else>
                                {{
                                    editingCustomer
                                        ? "Save Changes"
                                        : "Create Customer"
                                }}
                            </template>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog
            :open="!!deletingCustomer"
            @update:open="(v) => !v && (deletingCustomer = null)"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete this customer?</AlertDialogTitle>
                    <AlertDialogDescription>
                        "{{ deletingCustomer?.name }}" will be removed. This
                        can't be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="deletingCustomer = null"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction @click="confirmDelete"
                        >Delete</AlertDialogAction
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
